<?php

namespace App\Models\Hardware;

use App\Models\BaseModel;

class HardwareDeviceModel extends BaseModel
{
    protected $table            = 'tbl_hardware_devices';
    protected $primaryKey       = 'Id';

    protected $returnType       = 'array';

    protected $useAutoIncrement = true;

    protected $useTimestamps = true;
    protected $createdField  = 'CreatedAt';
    protected $updatedField  = 'UpdatedAt';

    protected $allowedFields = [

        'FkSiteId',

        'DeviceName',
        'DeviceType',

        'IPAddress',
        'TcpPort',

        'ModuleAddress',

        'BaudRate',

        'Protocol',

        'Location',

        'Description',

        'Timeout',

        'DisplayOrder',

        'IsOnline',

        'LastSeen',

        'IsActive'
    ];

    protected $validationRules = [

        'FkSiteId'      => 'required|integer',

        'DeviceName'    => 'required|min_length[3]|max_length[100]',

        'DeviceType'    => 'required|max_length[50]',

        'IPAddress'     => 'required|valid_ip',

        'TcpPort'       => 'required|integer|greater_than[0]|less_than_equal_to[65535]',

        'ModuleAddress' => 'required|max_length[2]',

        'Location'      => 'permit_empty|max_length[100]',

        'Description'   => 'permit_empty|max_length[255]'
    ];
}