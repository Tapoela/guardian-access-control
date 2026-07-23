<?php

namespace App\Models\Hardware;

use CodeIgniter\Model;

class HardwareOutputModel extends Model
{
    protected $table      = 'tbl_hardware_outputs';

    protected $primaryKey = 'Id';

    protected $returnType = 'array';

    protected $allowedFields = [

        'FkDeviceId',

        'RelayNumber',

        'RelayName',

        'RelayType',

        'CurrentState',

        'LastChanged'
    ];

    protected $useTimestamps = false;
}