<?php

namespace App\Controllers;

use App\Libraries\IcpGateway;

class HardwareController extends BaseController
{
    private $deviceIp = '192.168.1.2';
    private $devicePort = 10002;

    public function index()
    {
        $data = [
            'title' => 'ICP DAS Hardware Test'
        ];

        return view('hardware/index', $data);
    }


    public function connectTest()
    {
        $socket = fsockopen(
            $this->deviceIp,
            $this->devicePort,
            $errno,
            $errstr,
            5
        );

        if (!$socket) {

            return $this->response->setJSON([
                'status' => false,
                'message' => "Connection failed: $errstr ($errno)"
            ]);

        }


        fclose($socket);

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Connected successfully to 7188E4'
        ]);
    }

    public function testRelay1()
    {
        $socket = fsockopen(
            $this->deviceIp,
            $this->devicePort,
            $errno,
            $errstr,
            5
        );

        if (!$socket) {

            return $this->response->setJSON([
                'status'=>false,
                'message'=>$errstr
            ]);
        }


        /*
            DCON command will go here
        */


        fclose($socket);


        return $this->response->setJSON([
            'status'=>true,
            'message'=>'Command sent'
        ]);
    }

    public function sendRaw()
    {
        $command = $this->request->getPost('command');

        try {

            $gateway = new IcpGateway();

            $gateway->connect();

            $tx = $gateway->send($command);

            $rx = $gateway->receive();

            return $this->response->setJSON([
                'success' => true,
                'tx'      => trim($tx),
                'rx'      => trim($rx)
            ]);

            $gateway->disconnect();

            return $this->response->setJSON([
                'success' => true,
                'response' => trim($response)
            ]);

        } catch (\Exception $e) {

            return $this->response->setJSON([
                'success'=>true,
                'tx'=>trim($tx),
                'rx'=>$rx,
                'length'=>strlen($rx)
            ]);

        }
    }
}