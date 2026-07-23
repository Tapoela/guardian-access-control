<?php

namespace App\Libraries;

class IcpGateway
{
    protected string $ip;
    protected int $port;
    protected $socket = null;

    public function __construct(
        string $ip = '192.168.1.2',
        int $port = 10002
    ) {
        $this->ip   = $ip;
        $this->port = $port;
    }

    public function connect()
    {
        $errno = 0;
        $errstr = '';

        $this->socket = @fsockopen(
            $this->ip,
            $this->port,
            $errno,
            $errstr,
            5
        );

        if (!$this->socket) {
            throw new \Exception($errstr . " ($errno)");
        }

        stream_set_timeout(
            $this->socket,
            1
        );

        return true;
    }

    public function disconnect()
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }

        $this->socket = null;
    }

    public function send($command)
    {
        fwrite(
            $this->socket,
            $command . "\r"
        );
    }

    public function receive()
    {
        $response = '';

        $start = microtime(true);

        while ((microtime(true) - $start) < 1)
        {
            $data = fread($this->socket, 1024);

            if ($data !== false && strlen($data))
            {
                $response .= $data;

                // We got a response—no need to wait longer.
                break;
            }

            usleep(10000);
        }

        return [
            'ascii'  => trim($response),
            'hex'    => strtoupper(bin2hex($response)),
            'length' => strlen($response)
        ];
    }
}