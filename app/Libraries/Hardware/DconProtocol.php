<?php

namespace App\Libraries\Hardware;

class DconProtocol
{
    /**
     * Format module address as 2 digits.
     */
    protected function address(string|int $address): string
    {
        return str_pad((string)$address, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Read Module Information
     * Example: $10M
     */
    public function moduleInfo(string|int $address): string
    {
        return '$' . $this->address($address) . 'M';
    }

    /**
     * Read Firmware Version
     * Example: $10F
     */
    public function firmware(string|int $address): string
    {
        return '$' . $this->address($address) . 'F';
    }

    /**
     * Read Module Configuration
     * Example: $102
     */
    public function configuration(string|int $address): string
    {
        return '$' . $this->address($address) . '2';
    }

    /**
     * Read Relay Status
     * Example: $106
     */
    public function relayStatus(string|int $address): string
    {
        return '$' . $this->address($address) . '6';
    }

    /**
     * Turn Relay ON
     * Example: #101001
     */
    public function relayOn(string|int $address, int $relay): string
    {
        return sprintf(
            '#%s1%d01',
            $this->address($address),
            $relay
        );
    }

    /**
     * Turn Relay OFF
     * Example: #101000
     */
    public function relayOff(string|int $address, int $relay): string
    {
        return sprintf(
            '#%s1%d00',
            $this->address($address),
            $relay
        );
    }

    /**
     * Pass-through raw command.
     */
    public function raw(string $command): string
    {
        return trim($command);
    }
}