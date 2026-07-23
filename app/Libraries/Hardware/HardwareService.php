<?php

namespace App\Libraries\Hardware;

use App\Libraries\IcpGateway;
use App\Libraries\Hardware\Drivers\DriverInterface;
use App\Libraries\Hardware\Drivers\ICP7065Driver;
use App\Models\Hardware\HardwareDeviceModel;

class HardwareService
{
    protected DeviceManager $deviceManager;
    protected EventLogger $logger;
    protected DconProtocol $protocol;

    protected DriverInterface $driver;

    protected ?IcpGateway $gateway = null;

    protected int $deviceId = 0;

    protected bool $connected = false;

    protected array $device = [];

    public function __construct()
    {
        $this->deviceManager = new DeviceManager();

        $this->logger = new EventLogger();

        $this->protocol = new DconProtocol();
    }

    public function connect(int $deviceId): bool
    {
        $this->deviceId = $deviceId;

        $this->device = $this->deviceManager->get($deviceId);

        if (!$this->device) {
            throw new \Exception("Hardware device not found.");
        }

        $this->gateway = new IcpGateway(
            $this->device['IPAddress'],
            $this->device['TcpPort']
        );

        log_message('debug',
            'Connecting to ' .
            $this->device['IPAddress'] .
            ':' .
            $this->device['TcpPort']
        );

        $this->connected = $this->gateway->connect();

        log_message('debug', 'Connect returned: ' . var_export($this->connected, true));

        log_message(
            'debug',
            $this->connected
                ? 'Socket connected'
                : 'Socket connection failed'
        );

        return $this->connected;
    }

    public function disconnect()
    {
        if($this->gateway)
        {
            $this->gateway->disconnect();
        }
    }

    public function send(string $command): string
    {
        if (!$this->gateway) {
            throw new \Exception('No hardware device connected.');
        }

        log_message('debug', 'TX ASCII : ' . $command);
        log_message('debug', 'TX HEX   : ' . strtoupper(bin2hex($command)));

        $this->gateway->send($command);

        $response = $this->gateway->receive();

        log_message('debug', 'RX ASCII : ' . $response['ascii']);
        log_message('debug', 'RX HEX   : ' . $response['hex']);
        log_message('debug', 'RX LEN   : ' . $response['length']);

        return trim($response['ascii']);
    }

    public function ping(): bool
    {
        $response = $this->moduleInfo();

        log_message('debug', 'PING RESPONSE = [' . $response . ']');

        $online = ($response !== '');

        if ($online) {
            $this->deviceManager->online($this->deviceId);
        } else {
            $this->deviceManager->offline($this->deviceId);
        }

        return $online;
    }

    public function moduleInfo(): string
    {
        return $this->send(
            $this->protocol->moduleInfo(
                $this->address()
            )
        );
    }

    public function firmware(): string
    {
        return $this->send(
            $this->protocol->firmware(
                $this->address()
            )
        );
    }

    public function relay(int $relay, bool $state): string
    {
        $command = $state
            ? $this->protocol->relayOn(
                $this->address(),
                $relay
            )
            : $this->protocol->relayOff(
                $this->address(),
                $relay
            );

        return $this->send($command);
    }

    public function configuration(): string
    {
        return $this->send(
            $this->protocol->configuration(
                $this->address()
            )
        );
    }

    public function relayStatus(): string
    {
        return $this->send(
            $this->protocol->relayStatus(
                $this->address()
            )
        );
    }

    public function raw(string $command): string
    {
        return $this->send($command);
    }

    private function address(): string
    {
        return str_pad(
            (string)$this->device['ModuleAddress'],
            2,
            '0',
            STR_PAD_LEFT
        );
    }

    public function boomOpen(): bool
    {
        return $this->relay(0, true) === '>';
    }

    public function boomClose(): bool
    {
        return $this->relay(0, false) === '>';
    }

    public function greenOn(): bool
    {
        return $this->relay(1, true) === '>';
    }

    public function greenOff(): bool
    {
        return $this->relay(1, false) === '>';
    }

    public function redOn(): bool
    {
        return $this->relay(2, true) === '>';
    }

    public function redOff(): bool
    {
        return $this->relay(2, false) === '>';
    }

    public function testConnection(int $deviceId): array
    {
        try {

            // Connect to the device
            if (!$this->connect($deviceId)) {
                return [
                    'success' => false,
                    'online'  => false,
                    'message' => 'Unable to connect to device.'
                ];
            }

            // Measure response time
            $start = microtime(true);

            $online = $this->ping();

            log_message('debug', 'ONLINE VALUE = ' . ($online ? 'TRUE' : 'FALSE'));

            $responseTime = round((microtime(true) - $start) * 1000);

            $this->disconnect();

            $result = [
                'success'      => true,
                'online'       => $online,
                'responseTime' => $responseTime,
                'message'      => $online
                    ? 'Device is online.'
                    : 'Device did not respond.'
            ];

            log_message('debug', json_encode($result));

            return $result;

        } catch (\Throwable $e) {

            $this->disconnect();

            log_message('error', $e->getMessage());

            return [
                'success' => false,
                'online'  => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getDashboardStatus(): array
    {
        $deviceModel = new HardwareDeviceModel();

        $devices = $deviceModel
            ->active()
            ->orderBy('DisplayOrder', 'ASC')
            ->findAll();

        $online = 0;
        $offline = 0;

        foreach ($devices as &$device) {

            try {

                if ($this->connect((int)$device['Id'])) {

                    $start = microtime(true);

                    $status = $this->ping();

                    $responseTime = round((microtime(true) - $start) * 1000);

                    $device['ResponseTime'] = $responseTime;

                    $device['IsOnline'] = $status;

                    if ($status) {
                        $online++;
                    } else {
                        $offline++;
                    }

                    $this->disconnect();

                } else {

                    $device['IsOnline'] = false;
                    $device['ResponseTime'] = null;

                    $offline++;
                }

            } catch (\Throwable $e) {

                log_message('error', $e->getMessage());

                $device['IsOnline'] = false;
                $device['ResponseTime'] = null;

                $offline++;

                $this->disconnect();
            }
        }

        return [

            'success' => true,

            'online' => $online,

            'offline' => $offline,

            'total' => count($devices),

            'devices' => $devices

        ];
    }

}