<?php

namespace App\Libraries\Hardware\Drivers;

use App\Libraries\Hardware\DconProtocol;
use App\Libraries\IcpGateway;

class ICP7065Driver implements DriverInterface
{
    protected array $device;

    protected DconProtocol $protocol;

    protected IcpGateway $gateway;

    public function __construct(array $device)
    {
        $this->device = $device;

        $this->protocol = new DconProtocol();

        $this->gateway = new IcpGateway(
            $device['IPAddress'],
            $device['TcpPort']
        );
    }

    public function connect(): bool
    {
        return $this->gateway->connect();
    }

    public function disconnect(): void
    {
        $this->gateway->disconnect();
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

    public function relay(int $relay, bool $state): string
    {
        $command = $state
            ? $this->protocol->relayOn($this->address(), $relay)
            : $this->protocol->relayOff($this->address(), $relay);

        return $this->send($command);
    }

    public function raw(string $command): string
    {
        return $this->send($command);
    }

    private function send(string $command): string
    {
        log_message('debug', 'TX: ' . $command);

        $this->gateway->send($command);

        $response = $this->gateway->receive();

        log_message('debug', 'RX: ' . ($response['ascii'] ?? ''));

        return trim($response['ascii'] ?? '');
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
}