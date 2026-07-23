<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditModel extends Model
{
    protected $table      = 'audit_logs';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['event', 'module', 'user_id', 'description', 'ip_address', 'created_at'];

    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Static method to log audit events
     */
    public static function log(string $event, string $module, int $userId, string $description): bool
    {
        try {
            $db = \Config\Database::connect();
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
            }

            return $db->table('audit_logs')->insert([
                'event'       => $event,
                'module'      => $module,
                'user_id'     => $userId,
                'description' => $description,
                'ip_address'  => $ip,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Audit log error: ' . $e->getMessage());
            return false;
        }
    }
}