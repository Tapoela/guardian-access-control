<?php

namespace App\Models\Hardware;

use CodeIgniter\Model;

class HardwareEventModel extends Model
{
    protected $table      = 'tbl_hardware_events';

    protected $primaryKey = 'Id';

    protected $returnType = 'array';

    protected $allowedFields = [

        'FkSiteId',

        'FkDeviceId',

        'EventType',

        'Direction',

        'CommandSent',

        'Response',

        'Description',

        'Status',

        'ExecutionTime',

        'CreatedDate'
    ];

    protected $useTimestamps = false;
}