<?php
// filepath: c:\Users\Administrator\Documents\GuardianControl\app\Libraries\CameraSnapshot.php
namespace App\Libraries;

class CameraSnapshot
{
    private string $ip;
    private string $user;
    private string $pass;
    private int    $timeout;

    public function __construct(string $ip, string $user = 'admin', string $pass = 'admin', int $timeout = 10)
    {
        $this->ip      = $ip;
        $this->user    = $user;
        $this->pass    = $pass;
        $this->timeout = $timeout;
    }

    /**
     * Capture a JPEG snapshot from the camera.
     * Tries Hikvision ISAPI first, falls back to CGI endpoint.
     * Returns raw JPEG bytes or null on failure.
     */
    public function capture(): ?string
    {
        $urls = [
            "http://{$this->ip}/ISAPI/Streaming/channels/101/picture",
            "http://{$this->ip}/ISAPI/Streaming/channels/1/picture",
            "http://{$this->ip}/cgi-bin/snapshot.cgi",
            "http://{$this->ip}/onvif-http/snapshot?Profile_1",
        ];

        foreach ($urls as $url) {
            $jpeg = $this->fetch($url);
            if ($jpeg && $this->isJpeg($jpeg)) {
                log_message('debug', "[Snapshot] Got JPEG from {$url} (" . strlen($jpeg) . " bytes)");
                return $jpeg;
            }
        }

        log_message('warning', "[Snapshot] Failed to capture from {$this->ip}");
        return null;
    }

    /**
     * Capture and save to disk. Returns relative path or null.
     */
    public function captureAndSave(string $plate, string $subDir = 'uploads/anpr/'): ?string
    {
        $jpeg = $this->capture();
        if (!$jpeg) return null;

        $dateDir = date('Ymd');
        $dir     = WRITEPATH . $subDir . $dateDir . '/overview/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $safePlate = preg_replace('/[^A-Z0-9]/', '', strtoupper($plate));
        $filename  = $safePlate . '_OV_' . time() . '.jpg';

        if (file_put_contents($dir . $filename, $jpeg) !== false) {
            return $subDir . $dateDir . '/overview/' . $filename;
        }

        return null;
    }

    private function fetch(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => "{$this->user}:{$this->pass}",
            CURLOPT_HTTPAUTH       => CURLAUTH_DIGEST | CURLAUTH_BASIC,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $result = curl_exec($ch);
        $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($result && $code === 200) ? $result : null;
    }

    private function isJpeg(string $data): bool
    {
        return str_starts_with($data, "\xFF\xD8\xFF");
    }
}