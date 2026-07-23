<?php

namespace App\Controllers\Hardware;

use App\Controllers\BaseController;
use App\Models\Hardware\HardwareDeviceModel;

class Devices extends BaseController
{
    protected $devices;

    public function __construct()
    {
        $this->devices = new HardwareDeviceModel();
    }

    public function index()
    {
        $query = $this->devices;

        // Only Super Admin sees all sites
        if (!$this->isSuperAdmin) {
            $query->forSite($this->siteId);
        }

        $data = [
            'pageTitle'       => 'Hardware Devices',
            'pageDescription' => 'Manage hardware controllers installed at this site.',
            'devices'         => $query
                ->active()
                ->orderBy('DisplayOrder', 'ASC')
                ->orderBy('DeviceName', 'ASC')
                ->findAll(),
        ];

        return view('hardware/devices/index', $data);
    }

    public function create()
    {
        $data['title'] = 'Add Device';

        return view('hardware/devices/create', [
            'title'  => 'Add Device',
            'siteId' => $this->siteId,
        ]);
    }

    public function store()
    {
        $data = [

            'FkSiteId'      => $this->siteId,
            'DeviceName'    => $this->request->getPost('DeviceName'),
            'DeviceType'    => $this->request->getPost('DeviceType'),
            'IPAddress'     => $this->request->getPost('IPAddress'),
            'TcpPort'       => $this->request->getPost('TcpPort'),
            'ModuleAddress' => $this->request->getPost('ModuleAddress'),
            'BaudRate'      => $this->request->getPost('BaudRate'),
            'Protocol'      => $this->request->getPost('Protocol'),
            'Location'      => $this->request->getPost('Location'),
            'Description'   => $this->request->getPost('Description'),

            'IsOnline'      => 0,
            'IsActive'      => 1
        ];

        if (!$this->devices->insert($data)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->devices->errors());
        }

        $deviceId = $this->devices->getInsertID();

        $device = $this->devices->find($deviceId);

        $provision = new \App\Libraries\Hardware\DeviceProvisioningService();

        $provision->provision($device);

        return redirect()->to(base_url('hardware/devices'))
            ->with('success', 'Device added successfully.');
    }

    public function edit($id)
    {
        $data['title'] = 'Edit Device';

        $data['device'] = $this->devices->find($id);

        return view('hardware/devices/edit',$data);
    }

    public function update($id)
    {
        $data = [

            'FkSiteId'      => $this->siteId,

            'DeviceName'    => $this->request->getPost('DeviceName'),

            'DeviceType'    => $this->request->getPost('DeviceType'),

            'IPAddress'     => $this->request->getPost('IPAddress'),

            'TcpPort'       => $this->request->getPost('TcpPort'),

            'ModuleAddress' => $this->request->getPost('ModuleAddress'),

            'BaudRate'      => $this->request->getPost('BaudRate'),

            'Protocol'      => $this->request->getPost('Protocol'),

            'Location'      => $this->request->getPost('Location'),

            'Description'   => $this->request->getPost('Description')

        ];

        if (!$this->devices->update($id, $data)) {

            return redirect()->back()
                ->withInput()
                ->with('errors', $this->devices->errors());

        }

        return redirect()->to(base_url('hardware/devices'))
                        ->with('success', 'Device updated.');
    }

    public function delete($id)
    {
        $this->devices->update($id, [
            'IsActive' => 0
        ]);

        return redirect()->back()
                         ->with('success','Device removed.');
    }

    public function testConnection($id)
    {
        $hardware = new \App\Libraries\Hardware\HardwareService();

        $result = $hardware->testConnection((int)$id);

        return $this->response->setJSON($result);
    }

    public function refreshStatus()
    {
        $model = new \App\Models\Hardware\HardwareDeviceModel();
        $hardware = new \App\Libraries\Hardware\HardwareService();

        $devices = $model->findAll();

        $online = 0;
        $offline = 0;

        foreach ($devices as &$device) {

            $result = $hardware->testConnection($device['Id']);

            $device['IsOnline'] = $result['online'] ? 1 : 0;

            if ($result['online']) {
                $online++;
            } else {
                $offline++;
            }
        }

        return $this->response->setJSON([
            'devices' => $devices,
            'online'  => $online,
            'offline' => $offline
        ]);
        
    }

}