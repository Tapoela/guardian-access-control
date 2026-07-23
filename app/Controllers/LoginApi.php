<?php


namespace App\Controllers;

use CodeIgniter\Controller;

class LoginApi extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 900;

    public function login()
    {
        header('Content-Type: application/json');

        // DEBUG: Log that we received the request
        log_message('info', 'Login attempt received');

        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setStatusCode(405)
                ->setJSON(['success' => false, 'message' => 'Method not allowed']);
        }

        try {
            $db = \Config\Database::connect();
            $clientIp = $this->getClientIP();

            log_message('info', "Login attempt from IP: {$clientIp}");
            log_message('info', 'POST data: ' . json_encode($_POST));
            log_message('info', 'REQUEST: ' . json_encode($this->request->getPost()));

            // Check rate limit
            if ($this->isIPLocked($clientIp, $db)) {
                log_message('warning', "IP {$clientIp} is locked");
                return $this->response->setStatusCode(429)
                    ->setJSON(['success' => false, 'message' => 'Too many login attempts. Try again later.']);
            }

            $username = trim($this->request->getPost('username') ?? '');
            $password = $this->request->getPost('password') ?? '';
            $secure_key = $this->request->getPost('secure_key') ?? '';

            log_message('info', "Extracted - Username: '{$username}', Password exists: " . (!empty($password) ? 'yes' : 'no') . ", Secure Key: '{$secure_key}'");

            // Validate inputs
            if (empty($username) || empty($password) || empty($secure_key)) {
                log_message('warning', 'Missing required fields - Username: ' . (empty($username) ? 'MISSING' : 'OK') . 
                    ', Password: ' . (empty($password) ? 'MISSING' : 'OK') . 
                    ', Secure Key: ' . (empty($secure_key) ? 'MISSING' : 'OK'));
                $this->logFailedAttempt($clientIp, $db, 'Missing required fields');
                return $this->response->setStatusCode(400)
                    ->setJSON(['success' => false, 'message' => 'Missing required fields']);
            }

            // Validate secure key
            $server_secure_key = env('SECURE_KEY');
            if (empty($server_secure_key)) {
                log_message('error', 'SECURE_KEY not configured in .env');
                return $this->response->setStatusCode(500)
                    ->setJSON(['success' => false, 'message' => 'Server configuration error']);
            }

            log_message('info', "DEBUG: Received key: '{$secure_key}' (length: " . strlen($secure_key) . ")");
            log_message('info', "DEBUG: Expected key: '{$server_secure_key}' (length: " . strlen($server_secure_key) . ")");

            if (!hash_equals($secure_key, $server_secure_key)) {
                log_message('warning', 'Invalid secure key provided');
                $this->logFailedAttempt($clientIp, $db, 'Invalid secure key');
                return $this->response->setStatusCode(401)
                    ->setJSON(['success' => false, 'message' => 'Invalid credentials']);
            }

            // Get user from database
            log_message('info', "Looking up user: {$username}");
            
            $query = $db->table('users')
                ->select('id, username, email, password_hash, is_active')
                ->groupStart()
                ->where('username', $username)
                ->orWhere('email', $username)
                ->groupEnd()
                ->where('is_active', 1);
            
            log_message('info', 'Query: ' . $query->getCompiledSelect());
            
            $result = $query->get();
            
            if ($result === false) {
                log_message('error', 'Database query failed: ' . $db->error());
                return $this->response->setStatusCode(500)
                    ->setJSON(['success' => false, 'message' => 'Database error']);
            }
            
            $user = $result->getRow();

            if (!$user) {
                log_message('warning', "User not found: {$username}");
                $this->logFailedAttempt($clientIp, $db, "User not found: {$username}");
                usleep(random_int(300000, 500000));
                return $this->response->setStatusCode(401)
                    ->setJSON(['success' => false, 'message' => 'Invalid credentials']);
            }

            // Verify password using password_verify (for hashed passwords)
            log_message('info', "Password provided: '{$password}'");
            log_message('info', "Hash in DB: '{$user->password_hash}'");
            log_message('info', "Hash length: " . strlen($user->password_hash));
            
            $isPasswordValid = password_verify($password, $user->password_hash);
            log_message('info', "password_verify result: " . ($isPasswordValid ? 'TRUE' : 'FALSE'));
            
            if (!$isPasswordValid) {
                log_message('warning', "Invalid password for: {$username}");
                $this->logFailedAttempt($clientIp, $db, "Invalid password for: {$username}");
                usleep(random_int(300000, 500000));
                return $this->response->setStatusCode(401)
                    ->setJSON(['success' => false, 'message' => 'Invalid credentials']);
            }

            // Successful login
            log_message('info', "Successful login for: {$username}");
            $this->clearFailedAttempts($clientIp, $db);

            // Log to audit
            try {
                \App\Models\AuditModel::log('user_login', 'auth', $user->id, "User {$user->email} logged in from {$clientIp}");
            } catch (\Exception $e) {
                log_message('error', 'Audit log failed: ' . $e->getMessage());
            }

            return $this->response->setJSON([
                'success' => true,
                'user' => [
                    'id'    => $user->id,      // ← Change to object notation
                    'username' => $user->username,   // ← Change to object notation
                    'email' => $user->email,   // ← Change to object notation
                ],
                'message' => 'Login successful'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Login error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    private function isIPLocked(string $ip, $db): bool
    {
        try {
            $cache = cache();
            $lockKey = 'l' . md5('lock' . $ip);
            
            if ($cache->get($lockKey)) {
                return true;
            }
        } catch (\Exception $e) {
            log_message('error', 'Cache check failed: ' . $e->getMessage());
        }

        // Check database
        $recentAttempts = $db->table('login_attempts')
            ->where('ip_address', $ip)
            ->where('created_at >', date('Y-m-d H:i:s', strtotime('-15 minutes')))
            ->countAllResults();

        return $recentAttempts >= self::MAX_ATTEMPTS;
    }

    private function logFailedAttempt(string $ip, $db, string $reason): void
    {
        try {
            $db->table('login_attempts')->insert([
                'ip_address'  => $ip,
                'email'       => $this->request->getPost('username') ?? 'unknown',
                'reason'      => $reason,
                'user_agent'  => substr($this->request->getUserAgent(), 0, 255),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log attempt: ' . $e->getMessage());
        }
    }

    private function clearFailedAttempts(string $ip, $db): void
    {
        try {
            $cache = cache();
            $cache->delete('l' . md5('lock' . $ip));
        } catch (\Exception $e) {
            log_message('error', 'Cache delete failed: ' . $e->getMessage());
        }

        try {
            $db->table('login_attempts')
                ->where('created_at <', date('Y-m-d H:i:s', strtotime('-24 hours')))
                ->delete();
        } catch (\Exception $e) {
            log_message('error', 'Cleanup failed: ' . $e->getMessage());
        }
    }

    private function getClientIP(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}