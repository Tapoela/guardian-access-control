<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $table = 'anpr_events';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    /**
     * Get summary statistics
     */
    public function getSummary(array $filters): array
    {
        $db = \Config\Database::connect();

        $builder = $db->table('anpr_events')
            ->select("
                COUNT(*) as total,
                SUM(CASE WHEN result = 'granted' THEN 1 ELSE 0 END) as granted,
                SUM(CASE WHEN result = 'blacklisted' THEN 1 ELSE 0 END) as blacklisted,
                SUM(CASE WHEN result = 'unknown' THEN 1 ELSE 0 END) as unknown,
                SUM(CASE WHEN registration = 'NO PLATE' THEN 1 ELSE 0 END) as no_plate,
                SUM(CASE WHEN direction = 'entry' THEN 1 ELSE 0 END) as entries,
                SUM(CASE WHEN direction = 'exit' THEN 1 ELSE 0 END) as exits
            ")
            ->where('created_at >=', $filters['start'])
            ->where('created_at <=', $filters['end']);

        if (!empty($filters['camera_id'])) {
            $builder->where('camera_id', $filters['camera_id']);
        }

        return (array)$builder->get()->getRow();
    }

    /**
     * Get hourly breakdown
     */
    public function getHourlyData(array $filters): array
    {
        $db = \Config\Database::connect();

        $builder = $db->table('anpr_events')
            ->select("
                HOUR(created_at) as hour,
                COUNT(*) as total,
                SUM(CASE WHEN result = 'granted' THEN 1 ELSE 0 END) as granted,
                SUM(CASE WHEN result = 'blacklisted' THEN 1 ELSE 0 END) as blacklisted
            ")
            ->where('created_at >=', $filters['start'])
            ->where('created_at <=', $filters['end'])
            ->groupBy('HOUR(created_at)')
            ->orderBy('hour', 'ASC');

        if (!empty($filters['camera_id'])) {
            $builder->where('camera_id', $filters['camera_id']);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Get camera breakdown
     */
   public function getCameraBreakdown(array $filters): array
    {
        $db = \Config\Database::connect();

        $builder = $db->table('anpr_events e')
            ->select("
                c.id,
                c.name as camera_name,
                e.direction,
                e.result,
                COUNT(*) as total
            ")
            ->join('cameras c', 'c.id = e.camera_id', 'left')
            ->where('e.created_at >=', $filters['start'])
            ->where('e.created_at <=', $filters['end'])
            ->groupBy('c.id, e.direction, e.result')
            ->orderBy('c.name', 'ASC')
            ->orderBy('e.direction', 'ASC')
            ->orderBy('e.result', 'ASC');

        if (!empty($filters['camera_id'])) {
            $builder->where('e.camera_id', $filters['camera_id']);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Get camera downtime
     */
    public function getCameraDowntime(array $filters): array
    {
        $db = \Config\Database::connect();

        return $db->table('camera_status_log csl')
            ->select("
                c.id,
                c.name,
                SUM(CASE WHEN csl.status = 'offline' THEN 1 ELSE 0 END) as offline_count,
                FLOOR(SUM(CASE WHEN csl.status = 'offline' THEN 1 ELSE 0 END) / 60) as downtime_minutes
            ")
            ->join('cameras c', 'c.id = csl.camera_id', 'left')
            ->where('csl.created_at >=', $filters['start'])
            ->where('csl.created_at <=', $filters['end'])
            ->where('csl.status', 'offline')
            ->groupBy('csl.camera_id')
            ->orderBy('downtime_minutes', 'DESC')
            ->get() 
            ->getResultArray();
    }

    /**
     * Get all cameras
     */
    public function getCameras(): array
    {
        return \Config\Database::connect()
            ->table('cameras')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->getResultArray();
    }

    /**
     * Get all sites
     */
    public function getSites(): array
    {
        return \Config\Database::connect()
            ->table('sites')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->getResultArray();
    }
}