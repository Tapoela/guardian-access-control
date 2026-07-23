<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Dashboard extends Controller
{
    public function index()
    {
        helper(['permission']);
        
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $db = \Config\Database::connect();
        $cache = cache();

        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd   = date('Y-m-d 23:59:59');

        // ── TODAY'S STATS ────────────────────────────────────────────
        $cacheKey = 'dash.today.' . date('Y-m-d-H');
        $todayStats = $cache->get($cacheKey);

        if ($todayStats === null) {
            // FIXED: Chain get() before getRow()
            $todayStats = $db->table('anpr_events')
                ->select("
                    SUM(CASE WHEN result = 'granted' THEN 1 ELSE 0 END) AS granted,
                    SUM(CASE WHEN result = 'blacklisted' THEN 1 ELSE 0 END) AS blacklisted,
                    SUM(CASE WHEN result = 'unknown' THEN 1 ELSE 0 END) AS unknown,
                    SUM(CASE WHEN registration = 'NO PLATE' THEN 1 ELSE 0 END) AS no_plate,
                    COUNT(*) AS total
                ")
                ->where('created_at >=', $todayStart)
                ->where('created_at <=', $todayEnd)
                ->get()  // ← ADD THIS
                ->getRow();

            $cache->save($cacheKey, $todayStats, 300);
        }

        // ── LAST 7 DAYS TRAFFIC ─────────────────────────────────────
        $cacheKey7d = 'dash.7days.' . date('Y-m-d-H');
        $trafficDays = $cache->get($cacheKey7d);

        if ($trafficDays === null) {
            $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-6 days 00:00:00'));

            $trafficDays = $db->table('anpr_events')
                ->select("
                    DATE(created_at) AS day,
                    SUM(CASE WHEN result = 'granted' THEN 1 ELSE 0 END) AS granted,
                    SUM(CASE WHEN result = 'blacklisted' THEN 1 ELSE 0 END) AS blacklisted,
                    SUM(CASE WHEN result = 'unknown' THEN 1 ELSE 0 END) AS unknown,
                    SUM(CASE WHEN registration = 'NO PLATE' THEN 1 ELSE 0 END) AS no_plate
                ")
                ->where('created_at >=', $sevenDaysAgo)
                ->groupBy('DATE(created_at)')
                ->orderBy('day', 'ASC')
                ->get()  // Already has this ✅
                ->getResultArray();

            $cache->save($cacheKey7d, $trafficDays, 3600);
        }

        // ── HOURLY TRAFFIC TODAY ─────────────────────────────────────
        $cacheKeyHourly = 'dash.hourly.' . date('Y-m-d-H');
        $hourlyToday = $cache->get($cacheKeyHourly);

        if ($hourlyToday === null) {
            $hourlyToday = $db->table('anpr_events')
                ->select("
                    HOUR(created_at) AS hour,
                    COUNT(*) AS total
                ")
                ->where('created_at >=', $todayStart)
                ->where('created_at <=', $todayEnd)
                ->groupBy('HOUR(created_at)')
                ->orderBy('hour', 'ASC')
                ->get()  // Already has this ✅
                ->getResultArray();

            $cache->save($cacheKeyHourly, $hourlyToday, 1800);
        }

        // ── TOP 5 CAMERAS TODAY ──────────────────────────────────────
        $cacheKeyTopCams = 'dash.topcam.' . date('Y-m-d-H');
        $topCameras = $cache->get($cacheKeyTopCams);

        if ($topCameras === null) {
            $topCameras = $db->table('anpr_events e')
                ->select('c.name, COUNT(*) AS total')
                ->join('cameras c', 'c.id = e.camera_id', 'left')
                ->where('e.created_at >=', $todayStart)
                ->where('e.created_at <=', $todayEnd)
                ->groupBy('e.camera_id')
                ->orderBy('total', 'DESC')
                ->limit(5)
                ->get()  // Already has this ✅
                ->getResultArray();

            $cache->save($cacheKeyTopCams, $topCameras, 1800);
        }

        // ── YESTERDAY COMPARISON ─────────────────────────────────────
        $yesterdayKey = 'dash.yesterday.' . date('Y-m-d', strtotime('-1 day'));
        $yesterdayTotal = $cache->get($yesterdayKey);

        if ($yesterdayTotal === null) {
            $yesterdayStart = date('Y-m-d 00:00:00', strtotime('-1 day'));
            $yesterdayEnd   = date('Y-m-d 23:59:59', strtotime('-1 day'));

            // FIXED: Chain get() before getRow()
            $yesterday = $db->table('anpr_events')
                ->selectCount('id', 'total')
                ->where('created_at >=', $yesterdayStart)
                ->where('created_at <=', $yesterdayEnd)
                ->get()  // ← ADD THIS
                ->getRow();

            $yesterdayTotal = $yesterday->total ?? 0;
            $cache->save($yesterdayKey, $yesterdayTotal, 86400);
        }

        // ── CAMERA STATUS SUMMARY ────────────────────────────────────
        $cacheKeyCamStatus = 'dash.camstat.' . date('Y-m-d-H-i', time() - (time() % 300));
        $cameraStatus = $cache->get($cacheKeyCamStatus);

        if ($cameraStatus === null) {
            // FIXED: Chain get() before getRow()
            $cameraStatus = $db->table('cameras')
                ->select("
                    SUM(CASE WHEN last_status = 'online' THEN 1 ELSE 0 END) AS online,
                    SUM(CASE WHEN last_status = 'offline' THEN 1 ELSE 0 END) AS offline,
                    SUM(CASE WHEN last_status = 'error' THEN 1 ELSE 0 END) AS error,
                    COUNT(*) AS total
                ")
                ->where('is_active', 1)
                ->get()  // ← ADD THIS
                ->getRow();

            $cache->save($cacheKeyCamStatus, $cameraStatus, 300);
        }

        // Extract scalar values for view
        $granted = $todayStats->granted ?? 0;
        $blacklisted = $todayStats->blacklisted ?? 0;
        $unknown = $todayStats->unknown ?? 0;
        $noPlate = $todayStats->no_plate ?? 0;
        $total = $todayStats->total ?? 0;

        // ── RENDER DASHBOARD ─────────────────────────────────────────
        return view('dashboard', [
            'todayStats'     => $todayStats,
            'granted'        => $granted,
            'blacklisted'    => $blacklisted,
            'unknown'        => $unknown,
            'noPlate'        => $noPlate,
            'trafficDays'    => $trafficDays,
            'hourlyToday'    => $hourlyToday,
            'topCameras'     => $topCameras,
            'yesterdayTotal' => $yesterdayTotal,
            'cameraStatus'   => $cameraStatus,
            'generatedAt'    => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Refresh dashboard cache
     */
    public function refresh()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(403)
                ->setJSON(['error' => 'Unauthorized']);
        }

        $cache = cache();

        try {
            $cache->delete('dash.today.' . date('Y-m-d-H'));
            $cache->delete('dash.7days.' . date('Y-m-d-H'));
            $cache->delete('dash.hourly.' . date('Y-m-d-H'));
            $cache->delete('dash.topcam.' . date('Y-m-d-H'));
        } catch (\Exception $e) {
            log_message('error', 'Cache delete failed: ' . $e->getMessage());
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Dashboard cache cleared',
            'refreshedAt' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get real-time stats (AJAX polling)
     */
    public function realtimeStats()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(403)
                ->setJSON(['error' => 'Unauthorized']);
        }

        $db = \Config\Database::connect();
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd   = date('Y-m-d 23:59:59');
        $hourStart  = date('Y-m-d H:00:00');
        $hourEnd    = date('Y-m-d H:59:59');

        // FIXED: Chain get() before getRow()
        $currentHour = $db->table('anpr_events')
            ->selectCount('id', 'count')
            ->where('created_at >=', $hourStart)
            ->where('created_at <=', $hourEnd)
            ->get()  // ← ADD THIS
            ->getRow();

        // FIXED: Chain get() before getRow()
        $todayTotal = $db->table('anpr_events')
            ->selectCount('id', 'count')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->get()  // ← ADD THIS
            ->getRow();

        // FIXED: Chain get() before getRow()
        $offlineCameras = $db->table('cameras')
            ->selectCount('id', 'count')
            ->where('last_status', 'offline')
            ->where('is_active', 1)
            ->get()  // ← ADD THIS
            ->getRow();

        return $this->response->setJSON([
            'hourly'  => $currentHour->count ?? 0,
            'today'   => $todayTotal->count ?? 0,
            'offline' => $offlineCameras->count ?? 0,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get downtime data for dashboard table
     */
    public function downtime()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $db = \Config\Database::connect();

        // Get offline cameras in last 24 hours
        $rows = $db->table('cameras')
            ->select('id, name, ip_address, location, last_status, updated_at')
            ->where('is_active', 1)
            ->where('updated_at >', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->orderBy('last_status', 'ASC')
            ->orderBy('updated_at', 'DESC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON($rows);
    }
}