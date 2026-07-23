<?php

namespace App\Controllers\Hardware;

use App\Libraries\Hardware\HardwareService;
use App\Controllers\BaseController;
use App\Models\Hardware\HardwareDeviceModel;

class Diagnostics extends BaseController
{
    public function index($deviceId)
    {
        $deviceModel = new HardwareDeviceModel();

        $device = $deviceModel->find($deviceId);

        if (!$device) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Device not found.'
            );
        }

        return view('hardware/diagnostics', [
            'device' => $device
        ]);
    }

    protected function hardware(int $deviceId): HardwareService
    {
        $hardware = new HardwareService();

        $hardware->connect($deviceId);

        return $hardware;
    }

    public function scanModules()
    {
        return $this->response->setJSON([
            'success' => false,
            'error' => 'Not yet refactored.'
        ]);
    }

    public function scanBaud()
    {
        return $this->response->setJSON([
            'success' => false,
            'error' => 'Not yet refactored.'
        ]);
    }

    public function readConfig()
    {
        try {

            $deviceId = (int)$this->request->getPost('deviceId');

            $hardware = $this->hardware($deviceId);

            $response = $hardware->configuration();

            $hardware->disconnect();

            return $this->response->setJSON([
                'success' => true,
                'response' => $response
            ]);

        } catch (\Throwable $e) {

            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);

        }
    }

    public function setRelay()
    {
        try {

            $deviceId = (int)$this->request->getPost('deviceId');

            $relay = (int)$this->request->getPost('relay');

            $state = (bool)$this->request->getPost('state');

            $hardware = $this->hardware($deviceId);

            $response = $hardware->relay($relay, $state);

            $hardware->disconnect();

            return $this->response->setJSON([
                'success' => true,
                'response' => $response
            ]);

        } catch (\Throwable $e) {

            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);

        }
    }

    public function readRelayStatus()
    {
        try {

            $deviceId = (int)$this->request->getPost('deviceId');

            $hardware = $this->hardware($deviceId);

            $response = $hardware->relayStatus();

            $hardware->disconnect();

            return $this->response->setJSON([
                'success' => true,
                'response' => $response
            ]);

        } catch (\Throwable $e) {

            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);

        }
    }

    public function sendRaw()
    {
        try {

            $deviceId = (int)$this->request->getPost('deviceId');

            $command = trim($this->request->getPost('command'));

            $hardware = $this->hardware($deviceId);

            $response = $hardware->raw($command);

            $hardware->disconnect();

            return $this->response->setJSON([
                'success' => true,
                'command' => $command,
                'response' => $response
            ]);

        } catch (\Throwable $e) {

            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);

        }
    }

    public function connectionStatus()
    {
        try {

            $deviceId = (int)$this->request->getPost('deviceId');

            $hardware = $this->hardware($deviceId);

            $start = microtime(true);

            $online = $hardware->ping();

            $time = round((microtime(true) - $start) * 1000);

            $model = '';

            if ($online) {
                $model = $hardware->moduleInfo();
            }

            $hardware->disconnect();

            return $this->response->setJSON([
                'success' => $online,
                'status'  => $online ? 'ONLINE' : 'OFFLINE',
                'model'   => $model,
                'time'    => $time
            ]);

        } catch (\Throwable $e) {

            return $this->response->setJSON([
                'success' => false,
                'error'   => $e->getMessage()
            ]);

        }
    }
}
