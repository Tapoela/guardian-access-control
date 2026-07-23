<?php

namespace App\Models\Hardware;

use CodeIgniter\Model;

class HardwareCommandTemplateModel extends Model
{
    protected $table      = 'tbl_hardware_command_templates';

    protected $primaryKey = 'Id';

    protected $returnType = 'array';

    protected $allowedFields = [

        'DeviceType',

        'CommandName',

        'Command',

        'Description'
    ];

    protected $useTimestamps = false;
}