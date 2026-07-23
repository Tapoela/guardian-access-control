<?php

namespace App\Libraries\Hardware\Drivers;

interface DriverInterface
{
    public function connect(): bool;

    public function disconnect(): void;

    public function moduleInfo(): string;

    public function firmware(): string;

    public function configuration(): string;

    public function relayStatus(): string;

    public function relay(int $relay, bool $state): string;

    public function raw(string $command): string;
}