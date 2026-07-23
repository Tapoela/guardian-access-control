<?php

namespace App\Libraries\Hardware;

use App\Models\Hardware\HardwareInputModel;
use App\Models\Hardware\HardwareOutputModel;

class DeviceProvisioningService
{
    protected HardwareInputModel $inputs;
    protected HardwareOutputModel $outputs;

    public function __construct()
    {
        $this->inputs  = new HardwareInputModel();
        $this->outputs = new HardwareOutputModel();
    }

    public function provision(array $device)
    {
        switch ($device['DeviceType']) {

            case 'ICP_DAS_7065':
                $this->create7065Inputs($device['Id']);
                $this->create7065Outputs($device['Id']);
                break;
        }
    }

    protected function create7065Inputs(int $deviceId)
    {
        
        if ($this->inputs->where('FkDeviceId', $deviceId)->countAllResults() > 0) {
            return;
        }

        for ($i = 1; $i <= 4; $i++) {

            $this->inputs->insert([

                'FkDeviceId'   => $deviceId,

                'InputNumber'  => $i,

                'InputName'    => "Input {$i}",

                'InputType'    => 'GENERAL_INPUT',

                'CurrentState' => 0

            ]);

        }
    }

    protected function create7065Outputs(int $deviceId)
    {

        if ($this->outputs->where('FkDeviceId', $deviceId)->countAllResults() > 0) {
            return;
        }

        for ($i = 1; $i <= 5; $i++) {

            $this->outputs->insert([

                'FkDeviceId'   => $deviceId,

                'RelayNumber'  => $i,

                'RelayName'    => "Relay {$i}",

                'RelayType'    => 'GENERAL_OUTPUT',

                'CurrentState' => 0

            ]);

        }
    }
}