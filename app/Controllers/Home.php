<?php

namespace App\Controllers;

use App\Commands\MonitorCameras;

class Home extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }

    public function testTelegram()
    {
        $telegram = new \App\Libraries\TelegramAlert();
        $result = $telegram->sendMessage('✅ Test message from Guardian Control at ' . date('Y-m-d H:i:s'));
        
        return $this->response->setJSON([
            'success' => $result,
            'message' => $result ? '✅ Message sent to group!' : '❌ Failed to send message',
            'chat_id' => env('telegram.chat_id'),
            'bot_token' => substr(env('telegram.bot_token'), 0, 20) . '...'
        ]);
    }


public function testCameraAlert()
{
    // Create a temporary instance using reflection to bypass constructor
    $reflectionClass = new \ReflectionClass(MonitorCameras::class);
    $monitor = $reflectionClass->newInstanceWithoutConstructor();
    
    $testCamera = [
        'id'         => 999,
        'name'       => 'Test Camera',
        'location'   => 'Test Location',
        'ip_address' => '192.168.1.100',
    ];

    $reflection = new \ReflectionMethod(MonitorCameras::class, 'sendTelegram');
    $reflection->setAccessible(true);
    $result = $reflection->invoke($monitor, $testCamera, 'offline');

    return $this->response->setJSON([
        'success' => $result,
        'message' => $result ? '✅ Camera alert sent!' : '❌ Failed to send alert',
        'camera' => $testCamera,
        'status' => 'offline'
    ]);
}
}