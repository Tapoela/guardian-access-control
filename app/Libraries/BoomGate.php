<?php

namespace App\Libraries;

/**
 * BoomGate
 * Triggers the Hikvision access control gate/barrier via its HTTP API.
 *
 * Configuration (set in .env):
 *   hikvision.host       = 192.168.1.100
 *   hikvision.port       = 80
 *   hikvision.username   = admin
 *   hikvision.password   = yourpassword
 *   hikvision.channel    = 1          (door/gate channel number)
 *   hikvision.open_time  = 5          (seconds the relay stays open)
 */
class BoomGate
{
    protected string $host;
    protected int    $port;
    protected string $username;
    protected string $password;
    protected int    $channel;
    protected int    $openTime;

    /**
     * @param int|null    $channel  Override the channel (door number) from .env
     * @param string|null $host     Override the host IP from .env (per-camera)
     */
    public function __construct(?int $channel = null, ?string $host = null)
    {
        $this->host     = $host     ?? env('hikvision.host', '');
        $this->port     = (int)       env('hikvision.port', 80);
        $this->username =             env('hikvision.username', 'admin');
        $this->password =             env('hikvision.password', '');
        $this->channel  = $channel  ?? (int) env('hikvision.channel', 1);
        $this->openTime = (int)       env('hikvision.open_time', 5);
    }

    /**
     * Open the boom gate / door relay.
     * Uses the Hikvision ISAPI remote control door endpoint.
     *
     * PUT /ISAPI/AccessControl/RemoteControl/door/<channel>
     * Body: <RemoteControlDoor><cmd>open</cmd></RemoteControlDoor>
     *
     * @return array{success: bool, message: string}
     */
    public function open(string $reason = 'Vehicle access'): array
    {
        if (empty($this->host) || empty($this->password)) {
            log_message('warning', '[BoomGate] Hikvision host or password not configured.');
            return ['success' => false, 'message' => 'Gate not configured.'];
        }

        $url  = "http://{$this->host}:{$this->port}/ISAPI/AccessControl/RemoteControl/door/{$this->channel}";
        $body = '<RemoteControlDoor><cmd>open</cmd></RemoteControlDoor>';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/xml'],
            CURLOPT_USERPWD        => "{$this->username}:{$this->password}",
            CURLOPT_HTTPAUTH       => CURLAUTH_DIGEST,
        ]);

        $response   = curl_exec($ch);
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            log_message('error', "[BoomGate] cURL error: {$curlError}");
            return ['success' => false, 'message' => "Connection error: {$curlError}"];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            log_message('info', "[BoomGate] Gate opened. Reason: {$reason}. HTTP {$httpCode}");
            return ['success' => true, 'message' => 'Gate opened successfully.'];
        }

        log_message('error', "[BoomGate] Failed to open gate. HTTP {$httpCode}. Response: {$response}");
        return ['success' => false, 'message' => "Gate returned HTTP {$httpCode}."];
    }

    /**
     * Check if the gate is configured (host & password set).
     */
    public function isConfigured(): bool
    {
        return !empty($this->host) && !empty($this->password);
    }
}
