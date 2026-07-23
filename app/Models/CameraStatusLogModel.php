<?php

namespace App\Models;

use CodeIgniter\Model;

class CameraStatusLogModel extends Model
{
    protected $table         = 'camera_status_log';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['camera_id', 'status', 'notified_telegram'];
    protected $useTimestamps = false;

    public function getLatestForCamera(int $cameraId): ?array
    {
        return $this->where('camera_id', $cameraId)
                    ->orderBy('id', 'DESC')
                    ->first();
    }

    public function getRecentEvents(int $limit = 50): array
    {
        return $this->db->table('camera_status_log l')
            ->select('l.*, c.name as camera_name, c.ip_address, c.location')
            ->join('cameras c', 'c.id = l.camera_id')
            ->orderBy('l.id', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    public function getDowntimeReport(): array
    {
        // Get all offline events paired with the next online event for same camera
        $sql = "
            SELECT 
                c.name AS camera_name,
                c.ip_address,
                c.location,
                off_log.created_at AS went_offline,
                on_log.created_at  AS came_online,
                CASE 
                    WHEN on_log.created_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, off_log.created_at, on_log.created_at)
                    ELSE TIMESTAMPDIFF(SECOND, off_log.created_at, NOW())
                END AS duration_seconds,
                CASE 
                    WHEN on_log.created_at IS NULL THEN 1 
                    ELSE 0 
                END AS still_offline
            FROM camera_status_log off_log
            JOIN cameras c ON c.id = off_log.camera_id
            LEFT JOIN camera_status_log on_log 
                ON  on_log.camera_id = off_log.camera_id
                AND on_log.status    = 'online'
                AND on_log.id = (
                    SELECT MIN(id) FROM camera_status_log
                    WHERE camera_id = off_log.camera_id
                    AND   status    = 'online'
                    AND   id > off_log.id
                )
            WHERE off_log.status = 'offline'
            ORDER BY off_log.created_at DESC
            LIMIT 50
        ";

        return $this->db->query($sql)->getResultArray();
    }
}