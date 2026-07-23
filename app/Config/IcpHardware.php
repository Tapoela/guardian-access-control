<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class IcpHardware extends BaseConfig
{
    public array $devices = [

        'WesselEntranceController' => [

            'ip' => '192.168.1.2',

            'port' => 10002,

            'address' => 1

        ]

    ];
}