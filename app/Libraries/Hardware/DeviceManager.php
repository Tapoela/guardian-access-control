<?php

namespace App\Libraries\Hardware;

use App\Models\Hardware\HardwareDeviceModel;

class DeviceManager
{
    protected HardwareDeviceModel $devices;

    public function __construct()
    {
        $this->devices = new HardwareDeviceModel();
    }

    public function get(int $deviceId): array
    {
        $device = $this->devices->find($deviceId);

        if (!$device)
        {
            throw new \Exception("Device not found.");
        }

        return $device;
    }

    public function online(int $deviceId)
    {
        $this->devices->update($deviceId, [

            'IsOnline' => 1,

            'LastSeen' => date('Y-m-d H:i:s')

        ]);
    }

    public function offline(int $deviceId)
    {
        $this->devices->update($deviceId, [

            'IsOnline' => 0

        ]);
    }
}