<?php

namespace App\Libraries\Hardware;

use App\Models\Hardware\HardwareEventModel;

class EventLogger
{
    protected HardwareEventModel $events;

    public function __construct()
    {
        $this->events = new HardwareEventModel();
    }

    public function log(
        int $siteId,
        int $deviceId,
        string $type,
        string $direction,
        string $command,
        string $response,
        string $description,
        string $status,
        float $time
    )
    {
        $this->events->insert([

            'FkSiteId'      => $siteId,

            'FkDeviceId'    => $deviceId,

            'EventType'     => $type,

            'Direction'     => $direction,

            'CommandSent'   => $command,

            'Response'      => $response,

            'Description'   => $description,

            'Status'        => $status,

            'ExecutionTime' => $time,

            'CreatedDate'   => date('Y-m-d H:i:s')

        ]);
    }
}