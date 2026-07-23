<?php

namespace App\Libraries;

class TelegramAlert
{
    private string $token;
    private string $chatId;

    public function __construct()
    {
        $this->token  = env('telegram.bot_token', '');
        $this->chatId = env('telegram.chat_id', '');
    }

    public function sendBlacklistAlert(
        string  $registration,
        string  $reason,
        string  $location,
        ?int    $siteId           = null,
        ?string $snapshotPath     = null,
        ?string $overviewPath     = null
    ): bool {
        $siteName = $this->resolveSiteName($siteId);

        $text = implode("\n", [
            "\u{1F6A8} *BLACKLIST ALERT*",
            "\u{1F3E2} Site: {$siteName}",
            "\u{1F697} Plate: *{$registration}*",
            "\u{1F4CD} Location: {$location}",
            "\u{26A0}\u{FE0F} Reason: {$reason}",
            "\u{1F550} Time: " . date('Y-m-d H:i:s'),
        ]);

        $plateFile    = $snapshotPath && file_exists(WRITEPATH . $snapshotPath)
                        ? WRITEPATH . $snapshotPath : null;
        $overviewFile = $overviewPath && file_exists(WRITEPATH . $overviewPath)
                        ? WRITEPATH . $overviewPath : null;

        // Both images — send as media group
        if ($plateFile && $overviewFile) {
            return $this->sendMediaGroup($plateFile, $overviewFile, $text);
        }

        // Plate only
        if ($plateFile) {
            return $this->sendPhoto($plateFile, $text);
        }

        // Text only
        return $this->sendMessage($text);
    }
// ...existing code...

    private function sendMediaGroup(string $platePath, string $overviewPath, string $caption): bool
    {
        $media = json_encode([
            [
                'type'       => 'photo',
                'media'      => 'attach://plate',
                'caption'    => $caption,
                'parse_mode' => 'Markdown',
            ],
            [
                'type'  => 'photo',
                'media' => 'attach://overview',
            ],
        ]);

        $ch = curl_init("https://api.telegram.org/bot{$this->token}/sendMediaGroup");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [
                'chat_id' => $this->chatId,
                'media'   => $media,
                'plate'   => new \CURLFile($platePath,    'image/jpeg', basename($platePath)),
                'overview'=> new \CURLFile($overviewPath, 'image/jpeg', basename($overviewPath)),
            ],
            CURLOPT_TIMEOUT => 20,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return (json_decode($res, true)['ok'] ?? false);
    }

    public function sendMessage(string $text): bool
    {
        return $this->post('sendMessage', [
            'chat_id'    => $this->chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
        ]);
    }

    private function resolveSiteName(?int $siteId): string
    {
        if ($siteId === null) {
            return session('site_name') ?? env('app.siteName', 'Guardian Control');
        }
        $db  = \Config\Database::connect();
        $row = $db->table('sites')->select('name')->where('id', $siteId)->get()->getRowArray();
        return $row['name'] ?? 'Guardian Control';
    }

    private function sendPhoto(string $filePath, string $caption): bool
    {
        $ch = curl_init("https://api.telegram.org/bot{$this->token}/sendPhoto");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [
                'chat_id'    => $this->chatId,
                'caption'    => $caption,
                'parse_mode' => 'Markdown',
                'photo'      => new \CURLFile($filePath, 'image/jpeg', basename($filePath)),
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return (json_decode($res, true)['ok'] ?? false);
    }

    private function post(string $method, array $data): bool
    {
        $ch = curl_init("https://api.telegram.org/bot{$this->token}/{$method}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return (json_decode($res, true)['ok'] ?? false);
    }
}