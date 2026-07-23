<?php

namespace App\Controllers;

use App\Models\ReportModel;
use CodeIgniter\Controller;

class ReportController extends Controller
{
    /**
     * Guard: Check if user is logged in and has reporting permission
     */
    private function guard(): bool
    {
        helper(['permission']);
        return session()->get('isLoggedIn') && hasPermission('reports');
    }

    /**
     * Overview Report - Summary dashboard
     */
    public function overview()
    {
        if (!$this->guard()) return redirect()->to('/dashboard');

        $model = new ReportModel();

        $filters = [
            'start'     => $this->request->getGet('start') ?? date('Y-m-d 00:00:00'),
            'end'       => $this->request->getGet('end') ?? date('Y-m-d 23:59:59'),
            'site_id'   => $this->request->getGet('site_id'),
            'camera_id' => $this->request->getGet('camera_id'),
        ];

        $data = [
            'title'      => 'Overview Report',
            'summary'    => $model->getSummary($filters),
            'hourly'     => $model->getHourlyData($filters),
            'breakdown'  => $model->getCameraBreakdown($filters),
            'cameras'    => $model->getCameras(),
            'sites'      => $model->getSites(),
            'filters'    => $filters,
            'downtime'   => $model->getCameraDowntime($filters),
        ];

        return view('reports/overview', $data);
    }

    /**
     * Detailed Events Report
     */
    public function events()
    {
        if (!$this->guard()) return redirect()->to('/dashboard');

        $model = new ReportModel();
        $db = \Config\Database::connect();

        $filters = [
            'start'       => $this->request->getGet('start') ?? date('Y-m-d 00:00:00'),
            'end'         => $this->request->getGet('end') ?? date('Y-m-d 23:59:59'),
            'camera_id'   => $this->request->getGet('camera_id'),
            'result'      => $this->request->getGet('result'), // 'granted', 'blacklisted', 'unknown'
            'registration' => $this->request->getGet('registration'), // 'NO PLATE', etc
            'page'        => (int)($this->request->getGet('page') ?? 1),
        ];

        $perPage = 50;
        $offset = ($filters['page'] - 1) * $perPage;

        // Build query
        $builder = $db->table('anpr_events e')
            ->select('e.id, e.plate, e.registration, e.result, e.confidence, e.confidence_human, e.created_at, c.name as camera_name, c.ip_address')
            ->join('cameras c', 'c.id = e.camera_id', 'left')
            ->where('e.created_at >=', $filters['start'])
            ->where('e.created_at <=', $filters['end']);

        if (!empty($filters['camera_id'])) {
            $builder->where('e.camera_id', $filters['camera_id']);
        }
        if (!empty($filters['result'])) {
            $builder->where('e.result', $filters['result']);
        }
        if (!empty($filters['registration'])) {
            $builder->where('e.registration', $filters['registration']);
        }

        $total = $builder->countAllResults(false);
        $events = $builder->orderBy('e.created_at', 'DESC')
            ->limit($perPage)
            ->offset($offset)
            ->get()
            ->getResultArray();

        $data = [
            'title'     => 'Events Report',
            'events'    => $events,
            'cameras'   => $model->getCameras(),
            'filters'   => $filters,
            'total'     => $total,
            'perPage'   => $perPage,
            'page'      => $filters['page'],
            'totalPages' => ceil($total / $perPage),
        ];

        return view('reports/events', $data);
    }

    /**
     * Blacklist Events Report
     */
    public function blacklisted()
    {
        if (!$this->guard()) return redirect()->to('/dashboard');

        $model = new ReportModel();
        $db = \Config\Database::connect();

        $filters = [
            'start'     => $this->request->getGet('start') ?? date('Y-m-d 00:00:00'),
            'end'       => $this->request->getGet('end') ?? date('Y-m-d 23:59:59'),
            'camera_id' => $this->request->getGet('camera_id'),
            'page'      => (int)($this->request->getGet('page') ?? 1),
        ];

        $perPage = 50;
        $offset = ($filters['page'] - 1) * $perPage;

        $builder = $db->table('anpr_events e')
            ->select('e.id, e.plate, e.registration, e.confidence, e.created_at, c.name as camera_name, bl.reason, bl.added_by')
            ->join('cameras c', 'c.id = e.camera_id', 'left')
            ->join('blacklist_entries bl', 'bl.plate = e.plate', 'left')
            ->where('e.result', 'blacklisted')
            ->where('e.created_at >=', $filters['start'])
            ->where('e.created_at <=', $filters['end']);

        if (!empty($filters['camera_id'])) {
            $builder->where('e.camera_id', $filters['camera_id']);
        }

        $total = $builder->countAllResults(false);
        $events = $builder->orderBy('e.created_at', 'DESC')
            ->limit($perPage)
            ->offset($offset)
            ->get()
            ->getResultArray();

        $data = [
            'title'      => 'Blacklisted Events Report',
            'events'     => $events,
            'cameras'    => $model->getCameras(),
            'filters'    => $filters,
            'total'      => $total,
            'perPage'    => $perPage,
            'page'       => $filters['page'],
            'totalPages' => ceil($total / $perPage),
        ];

        return view('reports/blacklisted', $data);
    }

    /**
     * Camera Uptime Report
     */
    public function uptime()
    {
        if (!$this->guard()) return redirect()->to('/dashboard');

        $model = new ReportModel();
        $db = \Config\Database::connect();

        $filters = [
            'start'  => $this->request->getGet('start') ?? date('Y-m-d 00:00:00', strtotime('-30 days')),
            'end'    => $this->request->getGet('end') ?? date('Y-m-d 23:59:59'),
        ];

        $cameras = $db->table('cameras')
            ->select('id, name, location')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->getResultArray();

        $uptimeData = [];
        foreach ($cameras as $cam) {
            $total = $db->table('camera_status_log')
                ->where('camera_id', $cam['id'])
                ->where('timestamp >=', $filters['start'])
                ->where('timestamp <=', $filters['end'])
                ->countAllResults();

            $online = $db->table('camera_status_log')
                ->where('camera_id', $cam['id'])
                ->where('status', 'online')
                ->where('timestamp >=', $filters['start'])
                ->where('timestamp <=', $filters['end'])
                ->countAllResults();

            $uptime = $total > 0 ? round(($online / $total) * 100, 2) : 0;

            $uptimeData[] = [
                'id'       => $cam['id'],
                'name'     => $cam['name'],
                'location' => $cam['location'],
                'uptime'   => $uptime,
                'online'   => $online,
                'total'    => $total,
            ];
        }

        $data = [
            'title'   => 'Camera Uptime Report',
            'cameras' => $uptimeData,
            'filters' => $filters,
        ];

        return view('reports/uptime', $data);
    }

    /**
     * Peak Hours Analysis
     */
    public function peakHours()
    {
        if (!$this->guard()) return redirect()->to('/dashboard');

        $model = new ReportModel();
        $db = \Config\Database::connect();

        $filters = [
            'start'     => $this->request->getGet('start') ?? date('Y-m-d 00:00:00'),
            'end'       => $this->request->getGet('end') ?? date('Y-m-d 23:59:59'),
            'camera_id' => $this->request->getGet('camera_id'),
        ];

        $builder = $db->table('anpr_events')
            ->select('HOUR(created_at) as hour, COUNT(*) as count')
            ->where('created_at >=', $filters['start'])
            ->where('created_at <=', $filters['end'])
            ->groupBy('HOUR(created_at)')
            ->orderBy('hour', 'ASC');

        if (!empty($filters['camera_id'])) {
            $builder->where('camera_id', $filters['camera_id']);
        }

        $hourlyData = $builder->get()->getResultArray();

        $data = [
            'title'      => 'Peak Hours Analysis',
            'hourlyData' => $hourlyData,
            'cameras'    => $model->getCameras(),
            'filters'    => $filters,
        ];

        return view('reports/peak-hours', $data);
    }

    /**
     * Plate Statistics
     */
    public function plateStats()
    {
        if (!$this->guard()) return redirect()->to('/dashboard');

        $model = new ReportModel();
        $db = \Config\Database::connect();

        $filters = [
            'start'     => $this->request->getGet('start') ?? date('Y-m-d 00:00:00'),
            'end'       => $this->request->getGet('end') ?? date('Y-m-d 23:59:59'),
            'camera_id' => $this->request->getGet('camera_id'),
            'min_count' => (int)($this->request->getGet('min_count') ?? 2),
        ];

        $builder = $db->table('anpr_events')
            ->select('plate, COUNT(*) as count, MIN(created_at) as first_seen, MAX(created_at) as last_seen')
            ->where('created_at >=', $filters['start'])
            ->where('created_at <=', $filters['end'])
            ->groupBy('plate')
            ->having('COUNT(*) >=', $filters['min_count'])
            ->orderBy('count', 'DESC')
            ->limit(1000);

        if (!empty($filters['camera_id'])) {
            $builder->where('camera_id', $filters['camera_id']);
        }

        $plates = $builder->get()->getResultArray();

        $data = [
            'title'   => 'Plate Statistics',
            'plates'  => $plates,
            'cameras' => $model->getCameras(),
            'filters' => $filters,
        ];

        return view('reports/plate-stats', $data);
    }

    /**
     * Export Report as CSV
     */
    public function exportCsv()
    {
        if (!$this->guard()) return redirect()->to('/dashboard');

        $type = $this->request->getGet('type') ?? 'events'; // events, blacklisted, uptime
        $filters = [
            'start'     => $this->request->getGet('start') ?? date('Y-m-d 00:00:00'),
            'end'       => $this->request->getGet('end') ?? date('Y-m-d 23:59:59'),
            'camera_id' => $this->request->getGet('camera_id'),
        ];

        $db = \Config\Database::connect();
        $filename = 'report_' . $type . '_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

        if ($type === 'events') {
            fputcsv($output, ['ID', 'Plate', 'Registration', 'Result', 'Confidence', 'Camera', 'Timestamp']);

            $builder = $db->table('anpr_events e')
                ->select('e.id, e.plate, e.registration, e.result, e.confidence, c.name as camera_name, e.created_at')
                ->join('cameras c', 'c.id = e.camera_id', 'left')
                ->where('e.created_at >=', $filters['start'])
                ->where('e.created_at <=', $filters['end']);

            if (!empty($filters['camera_id'])) {
                $builder->where('e.camera_id', $filters['camera_id']);
            }

            $rows = $builder->orderBy('e.created_at', 'DESC')->get()->getResultArray();

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['id'],
                    $row['plate'],
                    $row['registration'],
                    $row['result'],
                    $row['confidence'],
                    $row['camera_name'],
                    $row['created_at'],
                ]);
            }
        } elseif ($type === 'blacklisted') {
            fputcsv($output, ['ID', 'Plate', 'Result', 'Camera', 'Timestamp', 'Reason']);

            $builder = $db->table('anpr_events e')
                ->select('e.id, e.plate, e.result, c.name as camera_name, e.created_at, bl.reason')
                ->join('cameras c', 'c.id = e.camera_id', 'left')
                ->join('blacklist_entries bl', 'bl.plate = e.plate', 'left')
                ->where('e.result', 'blacklisted')
                ->where('e.created_at >=', $filters['start'])
                ->where('e.created_at <=', $filters['end']);

            if (!empty($filters['camera_id'])) {
                $builder->where('e.camera_id', $filters['camera_id']);
            }

            $rows = $builder->orderBy('e.created_at', 'DESC')->get()->getResultArray();

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['id'],
                    $row['plate'],
                    $row['result'],
                    $row['camera_name'],
                    $row['created_at'],
                    $row['reason'] ?? 'N/A',
                ]);
            }
        }

        fclose($output);
        exit;
    }
}