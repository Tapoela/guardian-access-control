<?php

namespace App\Controllers;

use App\Models\CameraModel;
use CodeIgniter\Controller;

class Cameras extends Controller
{
    private function guard(): bool
    {
        helper(['permission']);
        return session()->get('isLoggedIn') && hasPermission('access_control');
    }

    public function index()
    {
        if (!$this->guard()) return redirect()->to('/dashboard');
        
        $db = \Config\Database::connect();
        $cameras = $db->table('cameras')
            ->select('id, name, location, ip_address, channel, is_active, gate_trigger, boom_live_view, last_status')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->getResultArray();
        
        return view('access/cameras/index', ['cameras' => $cameras]);
    }

    public function add()
    {
        if (!$this->guard()) return redirect()->to('/dashboard');

        if ($this->request->is('post')) {
            $model = new CameraModel();
            $model->insert([
                'name'           => trim($this->request->getPost('name')),
                'location'       => trim($this->request->getPost('location') ?? ''),
                'ip_address'     => trim($this->request->getPost('ip_address')),
                'channel'        => (int) ($this->request->getPost('channel') ?: 1),
                'is_active'      => $this->request->getPost('is_active') ? 1 : 0,
                'gate_trigger'   => $this->request->getPost('gate_trigger') ? 1 : 0,
                'boom_live_view' => $this->request->getPost('boom_live_view') ? 1 : 0,
                'token'          => CameraModel::generateToken(),
                'notes'          => trim($this->request->getPost('notes') ?? ''),
            ]);
            
            session()->setFlashdata('success', 'Camera added. Copy its token now.');
            return redirect()->to('/access/cameras');
        }
        
        return view('access/cameras/add');
    }

    // ── REMOVED DUPLICATE delete() from here ──

    public function edit($id)
    {
        if (!$this->guard()) return redirect()->to('/dashboard');
        
        $model  = new CameraModel();
        $camera = $model->find((int)$id);
        
        if (!$camera) {
            return redirect()->to('/access/cameras')->with('error', 'Camera not found.');
        }

        if ($this->request->is('post')) {
            $data = [
                'name'                    => trim($this->request->getPost('name')),
                'location'                => trim($this->request->getPost('location') ?? ''),
                'ip_address'              => trim($this->request->getPost('ip_address') ?? ''),
                'channel'                 => (int) ($this->request->getPost('channel') ?: 1),
                'is_active'               => $this->request->getPost('is_active') ? 1 : 0,
                'notes'                   => trim($this->request->getPost('notes') ?? ''),
                'overview_camera_ip'      => trim($this->request->getPost('overview_camera_ip') ?? '') ?: null,
                'overview_camera_user'    => trim($this->request->getPost('overview_camera_user')) ?: $camera['overview_camera_user'],
                'overview_camera_pass'    => trim($this->request->getPost('overview_camera_pass')) ?: $camera['overview_camera_pass'],
                'overview_snapshot_delay' => (int) ($this->request->getPost('overview_snapshot_delay') ?: 5),
                'camera_user'             => trim($this->request->getPost('camera_user')) ?: $camera['camera_user'],
                'camera_pass'             => trim($this->request->getPost('camera_pass')) ?: $camera['camera_pass'],
                'alarm_output_channel'    => (int) ($this->request->getPost('alarm_output_channel') ?: 1),
                'alarm_duration'          => (int) ($this->request->getPost('alarm_duration') ?: 5),
                'gate_trigger'            => $this->request->getPost('gate_trigger') ? 1 : 0,
                'boom_live_view'          => $this->request->getPost('boom_live_view') ? 1 : 0,
            ];

            if ($this->request->getPost('regen_token')) {
                $newToken = $model->regenerateToken($id);
                session()->setFlashdata('success', "Camera updated and token regenerated. Copy the new token now: {$newToken}");
            } else {
                session()->setFlashdata('success', 'Camera updated.');
            }

            if (!$model->update($id, $data)) {
                session()->setFlashdata('error', implode(', ', $model->errors() ?: ['Database update failed.']));
                return redirect()->back()->withInput();
            }

            return redirect()->to('/access/cameras/edit/' . $id);
        }

        return view('access/cameras/edit', ['camera' => $camera]);
    }

    public function delete($id)
    {
        if (!$this->guard()) return redirect()->to('/dashboard');
        
        $model = new CameraModel();
        
        // Get old token to invalidate cache
        $camera = $model->find($id);
        if ($camera) {
            $model->invalidateTokenCache($camera['token']);
        }
        
        $model->delete((int)$id);
        
        session()->setFlashdata('success', 'Camera deleted.');
        return redirect()->to('/access/cameras');
    }

    // ── ANPR Events viewer ──────────────────────────────────────

    public function events()
    {
        if (!$this->guard()) return redirect()->to('/dashboard');

        $db = \Config\Database::connect();

        $searched  = $this->request->getGet('searched') === '1';
        $cameraId  = $this->request->getGet('camera_id');
        $dateFrom  = $this->request->getGet('date_from');
        $dateTo    = $this->request->getGet('date_to');
        $timeFrom  = $this->request->getGet('time_from') ?: '00:00';
        $timeTo    = $this->request->getGet('time_to')   ?: '23:59';
        $result    = $this->request->getGet('result');

        $builder = $db->table('anpr_events ae')
            ->select('ae.id, ae.camera_id, ae.registration, ae.result, ae.confidence, 
                      ae.member_name, ae.snapshot_path, ae.created_at, c.name as camera_name');

        $builder->join('cameras c', 'c.id = ae.camera_id', 'left');

        if (!$searched) {
            $today = date('Y-m-d');
            $builder->where('ae.created_at >=', "{$today} 00:00:00")
                    ->where('ae.created_at <=', "{$today} 23:59:59")
                    ->orderBy('ae.created_at', 'DESC')
                    ->limit(100);
        } else {
            if ($dateFrom && $timeFrom) {
                $from = "{$dateFrom} {$timeFrom}:00";
                $builder->where('ae.created_at >=', $from);
            }

            if ($dateTo && $timeTo) {
                $to = "{$dateTo} {$timeTo}:59";
                $builder->where('ae.created_at <=', $to);
            }

            if ($cameraId) {
                $builder->where('ae.camera_id', (int) $cameraId);
            }

            if ($result) {
                $builder->where('ae.result', $result);
            }

            $builder->orderBy('ae.created_at', 'DESC')
                    ->limit(500);
        }

        $events = $builder->get()->getResultArray();

        $cameras = $db->table('cameras')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->getResultArray();

        return view('access/cameras/events', [
            'events'    => $events,
            'cameras'   => $cameras,
            'cameraId'  => $cameraId,
            'dateFrom'  => $dateFrom ?: date('Y-m-d'),
            'dateTo'    => $dateTo   ?: date('Y-m-d'),
            'timeFrom'  => $timeFrom,
            'timeTo'    => $timeTo,
            'result'    => $result,
            'searched'  => $searched,
        ]);
    }

    public function eventsPoll()
    {
        if (!$this->guard()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }

        $db       = \Config\Database::connect();
        $afterId  = (int) $this->request->getGet('after') ?? 0;
        $cameraId = $this->request->getGet('camera_id');
        $result   = $this->request->getGet('result');

        $today = date('Y-m-d');

        $builder = $db->table('anpr_events')
            ->select('id, camera_id, registration, result, confidence, member_name, snapshot_path, created_at')
            ->where('id >', $afterId)
            ->where('created_at >=', "{$today} 00:00:00")
            ->where('created_at <=', "{$today} 23:59:59")
            ->orderBy('id', 'ASC')
            ->limit(50);

        if ($cameraId) {
            $builder->where('camera_id', (int) $cameraId);
        }

        if ($result) {
            $builder->where('result', $result);
        }

        $rows = $builder->get()->getResultArray();
        return $this->response->setJSON($rows);
    }

    public function pingCamera($id)
    {
        if (!$this->guard()) {
            return $this->response->setJSON(['online' => false]);
        }

        $camera = (new CameraModel())->find((int)$id);
        
        if (!$camera || empty($camera['ip_address'])) {
            return $this->response->setJSON(['online' => false, 'reason' => 'No IP configured']);
        }

        $ip     = trim($camera['ip_address']);
        $online = $this->checkCameraOnlineWithRetry($ip, 3);

        return $this->response->setJSON(['online' => $online, 'ip' => $ip]);
    }

    private function checkCameraOnlineWithRetry(string $ip, int $maxAttempts = 3): bool
    {
        $timeouts = [500, 800, 1000];
        
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $timeout = isset($timeouts[$attempt - 1]) 
                ? $timeouts[$attempt - 1] / 1000 
                : 1;

            if ($this->checkCameraOnline($ip, $timeout)) {
                log_message('debug', "[PING] Camera {$ip} online on attempt {$attempt}");
                return true;
            }

            if ($attempt < $maxAttempts) {
                usleep(100 * $attempt);
            }
        }

        log_message('warning', "[PING] Camera {$ip} offline after {$maxAttempts} attempts");
        return false;
    }

    private function checkCameraOnline(string $ip, float $timeout = 1): bool
    {
        $ports = [80, 8000, 554];

        foreach ($ports as $port) {
            $sock = @fsockopen($ip, $port, $errno, $errstr, $timeout);
            if ($sock) {
                fclose($sock);
                return true;
            }
        }

        return false;
    }

    public function downtimeData()
    {
        if (!$this->guard()) {
            return $this->response->setJSON([]);
        }

        $rows = (new \App\Models\CameraStatusLogModel())->getDowntimeReport();
        return $this->response->setJSON($rows);
    }
}