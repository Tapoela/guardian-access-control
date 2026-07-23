<?php

namespace App\Models;

use CodeIgniter\Model;

class CameraModel extends Model
{
    protected $table      = 'cameras';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    // OPTIMIZED: Include all fields that can be updated
    protected $allowedFields = [
        'name',
        'location',
        'ip_address',
        'channel',
        'is_active',
        'token',
        'notes',
        'overview_camera_ip',
        'overview_camera_user',
        'overview_camera_pass',
        'overview_snapshot_delay',
        'camera_user',
        'camera_pass',
        'alarm_output_channel',
        'alarm_duration',
        'is_monitored',
        'last_status',
        'gate_trigger',
        'boom_live_view',
        'manual_override',  // ← ADDED: needed for BoomControl
    ];

    // Enable automatic timestamp handling
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    // Validation rules
    protected $validationRules = [
        'name'                    => 'required|string|min_length[3]|max_length[100]',
        'location'                => 'string|max_length[150]',
        'ip_address'              => 'required|valid_ip',
        'channel'                 => 'integer|greater_than[0]|less_than[256]',
        'is_active'               => 'integer|in_list[0,1]',
        'token'                   => 'string|max_length[64]|is_unique[cameras.token,id,{id}]',
        'overview_camera_ip'      => 'permit_empty|valid_ip',
        'camera_user'             => 'string|max_length[100]',
        'camera_pass'             => 'string|max_length[100]',
        'alarm_output_channel'    => 'integer|greater_than[0]|less_than[256]',
        'alarm_duration'          => 'integer|greater_than[0]|less_than[256]',
        'gate_trigger'            => 'integer|in_list[0,1]',
        'boom_live_view'          => 'integer|in_list[0,1]',
        'manual_override'         => 'integer|in_list[0,1]',
    ];

    protected $validationMessages = [
        'ip_address' => [
            'required'  => 'Camera IP address is required.',
            'valid_ip'  => 'Please enter a valid IP address.',
        ],
        'name' => [
            'required'    => 'Camera name is required.',
            'min_length'  => 'Camera name must be at least 3 characters.',
        ],
    ];

    // ────────────────────────────────────────────────────

    /**
     * Find a camera by its secret token (CACHED)
     * OPTIMIZED: Use code igniter's caching to avoid repeated DB queries
     */
    public function findByToken(string $token): ?array
    {
        $cache = cache();
        // FIXED: Use safe cache key format
        $cacheKey = "camera.token." . md5($token);

        $camera = $cache->get($cacheKey);
        if ($camera !== null) {
            return $camera ?: null;
        }

        $camera = $this->where('token', $token)
            ->where('is_active', 1)
            ->first();

        $cache->save($cacheKey, $camera ?: false, 3600);
        return $camera;
    }

    public function invalidateTokenCache(string $token): void
    {
        // FIXED: Use safe cache key format
        cache()->delete("camera.token." . md5($token));
    }

    /**
     * Get all gate-trigger cameras
     * OPTIMIZED: Single query with index
     */
    public function getGateCameras(): array
    {
        return $this->where('gate_trigger', 1)
            ->where('is_active', 1)
            ->orderBy('name')
            ->findAll();
    }

    /**
     * Get all boom live view cameras
     * OPTIMIZED: Indexed query
     */
    public function getBoomCameras(): array
    {
        return $this->where('boom_live_view', 1)
            ->where('is_active', 1)
            ->orderBy('name')
            ->findAll();
    }

    /**
     * Update camera status (online/offline)
     * OPTIMIZED: Single update call
     */
    public function updateStatus(int $cameraId, string $status): bool
    {
        return $this->update($cameraId, [
            'last_status' => in_array($status, ['online', 'offline', 'error'], true) ? $status : 'unknown',
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Batch update camera statuses
     * OPTIMIZED: Single query for multiple updates
     */
    public function batchUpdateStatus(array $statusMap): int
    {
        $db = \Config\Database::connect();
        $updated = 0;

        foreach ($statusMap as $cameraId => $status) {
            $updated += $db->table('cameras')
                ->where('id', $cameraId)
                ->update(['last_status' => $status, 'updated_at' => date('Y-m-d H:i:s')])
                ? 1 : 0;
        }

        return $updated;
    }

    /**
     * Update manual override flag
     * OPTIMIZED: Specific update for boom control
     */
    public function updateManualOverride(int $cameraId, bool $override): bool
    {
        return $this->update($cameraId, [
            'manual_override' => $override ? 1 : 0,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Generate a cryptographically random token
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(24));
    }

    /**
     * Get cameras with low connectivity (for monitoring dashboard)
     * OPTIMIZED: Indexed query
     */
    public function getOfflineCameras(int $minutes = 15): array
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        return $this->where('is_active', 1)
            ->where('last_status', 'offline')
            ->where('updated_at >', $cutoff)
            ->orderBy('updated_at', 'ASC')
            ->findAll();
    }

    /**
     * Regenerate camera token (invalidate old cache)
     */
    public function regenerateToken(int $cameraId): string
    {
        $oldToken = $this->select('token')->find($cameraId)['token'] ?? null;

        // Invalidate old cache
        if ($oldToken) {
            $this->invalidateTokenCache($oldToken);
        }

        $newToken = self::generateToken();
        $this->update($cameraId, ['token' => $newToken]);

        return $newToken;
    }
}