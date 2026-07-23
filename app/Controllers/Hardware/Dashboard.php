<?php

namespace App\Controllers\Hardware;

use App\Controllers\BaseController;
use App\Models\Hardware\HardwareDeviceModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $deviceModel = new HardwareDeviceModel();

        $devices = $deviceModel
            ->active()
            ->orderBy('DisplayOrder', 'ASC')
            ->orderBy('DeviceName', 'ASC')
            ->findAll();

        $data = [

            'pageTitle'       => 'Guardian Control Centre',
            'pageDescription' => 'Live status of all Guardian hardware controllers.',

            'devices' => $devices,

            'online' => count(array_filter($devices, fn($d) => $d['IsOnline'])),

            'offline' => count(array_filter($devices, fn($d) => !$d['IsOnline'])),

            'total' => count($devices)

        ];

        return view('hardware/dashboard', $data);
    }

    public function status()
    {
        $hardware = new \App\Libraries\Hardware\HardwareService();

        return $this->response->setJSON(
            $hardware->getDashboardStatus()
        );
    }
}