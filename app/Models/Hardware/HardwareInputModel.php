<?php

namespace App\Models\Hardware;

use CodeIgniter\Model;

class HardwareInputModel extends Model
{
    protected $table      = 'tbl_hardware_inputs';

    protected $primaryKey = 'Id';

    protected $returnType = 'array';

    protected $allowedFields = [

        'FkDeviceId',

        'InputNumber',

        'InputName',

        'InputType',

        'CurrentState',

        'LastChanged'
    ];

    protected $useTimestamps = false;
}