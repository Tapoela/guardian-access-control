<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\AuditModel;
use CodeIgniter\Controller;

class Auth extends Controller
{
    private $appSecretKey;
    private const LOGIN_ATTEMPT_LIMIT = 5;
    private const LOGIN_ATTEMPT_WINDOW = 900; // 15 minutes in seconds

    public function __construct()
    {
        $this->appSecretKey = env('SECURE_KEY', 'GuardianControlWp@140586');
    }

    public function login()
    {
        helper(['form']);
        $error = null;
        $email = null;

        // Detect if API/app request
        $isApi = $this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json';

        if ($isApi) {
            $appKey = $this->request->getHeaderLine('X-Guardian-App-Key');
            if ($appKey !== $this->appSecretKey) {
                log_message('warning', "[AUTH] Unauthorized app login attempt from {$this->request->getIPAddress()}");
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'Unauthorized app'
                ])->setStatusCode(401);
            }
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $email = trim($this->request->getPost('email'));
            $password = $this->request->getPost('password');
            $ip = $this->getClientIP();

            // OPTIMIZED: Check rate limiting BEFORE database query
            if ($this->isRateLimited($ip)) {
                log_message('warning', "[AUTH] Rate limit exceeded for IP {$ip}");
                $error = 'Too many login attempts. Please try again in 15 minutes.';
                
                if ($isApi) {
                    return $this->response->setJSON([
                        'success' => false,
                        'error' => $error
                    ])->setStatusCode(429);
                }
                return view('login', ['error' => $error, 'email' => '']);
            }

            // Validate input
            if (empty($email) || empty($password)) {
                $error = 'Email and password are required.';
                if ($isApi) {
                    return $this->response->setJSON([
                        'success' => false,
                        'error' => $error
                    ])->setStatusCode(400);
                }
                return view('login', ['error' => $error, 'email' => $email]);
            }

            // Single optimized query with JOINs instead of multiple queries
            $db = \Config\Database::connect();
            $user = $db->table('users u')
                ->select('u.id, u.username, u.email, u.password_hash, u.site_id, u.is_active, r.id as role_id, r.name as role_name, s.name as site_name')
                ->join('user_roles ur', 'ur.user_id = u.id', 'left')
                ->join('roles r', 'r.id = ur.role_id', 'left')
                ->join('sites s', 's.id = u.site_id', 'left')
                ->where('u.email', $email)
                ->get()
                ->getRowArray();

            // Account doesn't exist or is inactive
            if (!$user) {
                $this->recordFailedAttempt($ip, $email);
                log_message('info', "[AUTH] Login failed: unknown email {$email} from {$ip}");
                $error = 'Invalid email or password.';
                
                // Timing attack prevention
                usleep(random_int(300000, 500000));
                
                if ($isApi) {
                    return $this->response->setJSON([
                        'success' => false,
                        'error' => $error
                    ])->setStatusCode(401);
                }
                return view('login', ['error' => $error, 'email' => $email]);
            }

            // Account is disabled
            if (!$user['is_active']) {
                $this->recordFailedAttempt($ip, $email);
                log_message('warning', "[AUTH] Login attempted on inactive account: {$email} from {$ip}");
                $error = 'This account has been disabled. Contact your administrator.';
                
                if ($isApi) {
                    return $this->response->setJSON([
                        'success' => false,
                        'error' => $error
                    ])->setStatusCode(403);
                }
                return view('login', ['error' => $error, 'email' => $email]);
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->recordFailedAttempt($ip, $email);
                log_message('info', "[AUTH] Login failed: incorrect password for {$email} from {$ip}");
                $error = 'Invalid email or password.';
                
                // Timing attack prevention
                usleep(random_int(300000, 500000));
                
                if ($isApi) {
                    return $this->response->setJSON([
                        'success' => false,
                        'error' => $error
                    ])->setStatusCode(401);
                }
                return view('login', ['error' => $error, 'email' => $email]);
            }

            // ✅ LOGIN SUCCESSFUL
            $siteId = isset($user['site_id']) && $user['site_id'] !== null ? (int)$user['site_id'] : null;
            $roleId = $user['role_id'] ?? '';
            $roleName = $user['role_name'] ?? '';
            $siteName = $user['site_name'] ?? '';

            // Clear failed login attempts on success
            $this->clearFailedAttempts($ip, $email);

            // Log successful login
            try {
                \App\Models\AuditModel::log('login_success', 'user', $user['id'], 
                    "User {$email} logged in from IP {$ip}");
            } catch (\Exception $e) {
                log_message('error', 'Audit log failed: ' . $e->getMessage());
            }

            // Set session for web
            if (!$isApi) {
                session()->set([
                    'user_id'    => $user['id'],
                    'username'   => $user['username'],
                    'email'      => $user['email'],
                    'role_id'    => $roleId,
                    'role_name'  => $roleName,
                    'site_id'    => $siteId,
                    'site_name'  => $siteName,
                    'isLoggedIn' => true,
                    'login_time' => time(),
                    'login_ip'   => $ip,
                ]);

                log_message('info', "[AUTH] Login successful for {$email} from {$ip}");
                return redirect()->to('/dashboard');
            } else {
                // Return JSON token for app
                $token = $this->generateApiToken($user['id'], $ip);
                
                return $this->response->setJSON([
                    'success' => true,
                    'user' => [
                        'id'        => $user['id'],
                        'username'  => $user['username'],
                        'email'     => $user['email'],
                        'role'      => $roleName,
                        'site_id'   => $siteId,
                        'site_name' => $siteName,
                    ],
                    'token' => $token,
                ]);
            }
        }

        // GET request: show login form
        return view('login', ['error' => null, 'email' => null]);
    }

    /**
     * Check if IP has exceeded rate limit
     * FIXED: Use safe cache key format (alphanumeric only)
     */
    private function isRateLimited(string $ip): bool
    {
        $cache = cache();
        // FIXED: Use alphanumeric cache key format
        $key = 'r' . md5('ratelimit' . $ip);
        
        try {
            $attempts = $cache->get($key) ?? 0;
            return $attempts >= self::LOGIN_ATTEMPT_LIMIT;
        } catch (\Exception $e) {
            log_message('error', 'Cache check failed: ' . $e->getMessage());
            // Fallback to database check
            $db = \Config\Database::connect();
            $recentAttempts = $db->table('login_attempts')
                ->where('ip_address', $ip)
                ->where('created_at >', date('Y-m-d H:i:s', strtotime('-15 minutes')))
                ->where('success', 0)
                ->countAllResults();
            
            return $recentAttempts >= self::LOGIN_ATTEMPT_LIMIT;
        }
    }

    /**
     * Record failed login attempt
     * FIXED: Use safe cache key format (alphanumeric only)
     */
    private function recordFailedAttempt(string $ip, string $email): void
    {
        $db = \Config\Database::connect();
        $cache = cache();
        
        // FIXED: Use alphanumeric cache key format
        $key = 'a' . md5('attempts' . $ip);
        
        try {
            $attempts = ($cache->get($key) ?? 0) + 1;
            $cache->save($key, $attempts, self::LOGIN_ATTEMPT_WINDOW);
        } catch (\Exception $e) {
            log_message('error', 'Cache save failed: ' . $e->getMessage());
        }

        // Log to database for audit
        try {
            $db->table('login_attempts')->insert([
                'email'      => $email,
                'ip_address' => $ip,
                'success'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to insert login attempt: ' . $e->getMessage());
        }
    }

    /**
     * Clear failed attempts on successful login
     * FIXED: Use safe cache key format (alphanumeric only)
     */
    private function clearFailedAttempts(string $ip, string $email): void
    {
        $cache = cache();
        
        // FIXED: Use alphanumeric cache key format
        try {
            $cache->delete('r' . md5('ratelimit' . $ip));
            $cache->delete('a' . md5('attempts' . $ip));
        } catch (\Exception $e) {
            log_message('error', 'Cache delete failed: ' . $e->getMessage());
        }

        // Optionally log success to database
        try {
            $db = \Config\Database::connect();
            $db->table('login_attempts')->insert([
                'email'      => $email,
                'ip_address' => $ip,
                'success'    => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log success: ' . $e->getMessage());
        }
    }

    /**
     * Generate API token for app-based authentication
     */
    private function generateApiToken(int $userId, string $ip): string
    {
        $payload = [
            'user_id'  => $userId,
            'ip'       => $ip,
            'iat'      => time(),
            'exp'      => time() + (86400 * 7), // 7 days
        ];

        // Simple base64 token (use JWT library for production)
        $token = base64_encode(json_encode($payload));
        
        $db = \Config\Database::connect();
        try {
            $db->table('api_tokens')->insert([
                'user_id'    => $userId,
                'token'      => hash('sha256', $token),
                'ip_address' => $ip,
                'expires_at' => date('Y-m-d H:i:s', $payload['exp']),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to insert API token: ' . $e->getMessage());
        }

        return $token;
    }

    /**
     * Get real client IP (handles proxies)
     */
    private function getClientIP(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        }
        if (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        }
        if (!empty($_SERVER['HTTP_FORWARDED'])) {
            return $_SERVER['HTTP_FORWARDED'];
        }
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return 'unknown';
    }

    public function logout()
    {
        $userId = session()->get('user_id');
        $ip = $this->getClientIP();

        // Log logout
        if ($userId) {
            try {
                \App\Models\AuditModel::log('logout', 'user', $userId, 
                    "User logged out from IP {$ip}");
            } catch (\Exception $e) {
                log_message('error', 'Audit log failed: ' . $e->getMessage());
            }

            // Invalidate API tokens for this user
            try {
                $db = \Config\Database::connect();
                $db->table('api_tokens')
                    ->where('user_id', $userId)
                    ->delete();
            } catch (\Exception $e) {
                log_message('error', 'Failed to delete API tokens: ' . $e->getMessage());
            }
        }

        session()->destroy();
        
        return redirect()->to('/login')
            ->with('success', 'You have been logged out successfully.');
    }
}