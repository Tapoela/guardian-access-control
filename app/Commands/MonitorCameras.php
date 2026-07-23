<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\CameraModel;
use App\Models\CameraStatusLogModel;

class MonitorCameras extends BaseCommand
{
    protected $group       = 'Guardian';
    protected $name        = 'cameras:monitor';
    protected $description = 'Continuously monitor cameras and alert on state change.';

    protected bool $running = true;

    public function run(array $params)
    {
        // Allow --interval=30 override, default 30 seconds
        $interval = (int) CLI::getOption('interval') ?: 30;

        CLI::write('🟢 Camera monitor started. Checking every ' . $interval . 's. Press Ctrl+C to stop.', 'green');

        // Graceful shutdown on Ctrl+C (Linux/Mac only — Windows ignores this)
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT,  function () { $this->running = false; });
            pcntl_signal(SIGTERM, function () { $this->running = false; });
        }

        while ($this->running) {
            $this->checkAll();

            // Tick down visually so operator knows it's alive
            for ($i = $interval; $i > 0 && $this->running; $i--) {
                CLI::print("\r⏱  Next check in {$i}s...   ");
                sleep(1);
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
            }
            CLI::newLine();
        }

        CLI::write('🔴 Monitor stopped.', 'red');
    }

    private function checkAll(): void
    {
        $cameraModel = new CameraModel();
        $logModel    = new CameraStatusLogModel();

        // Retry any unsent Telegram notifications from last 15 minutes
        $this->retryUnsent($cameraModel, $logModel);

        $cameras = $cameraModel->where('is_monitored', 1)->where('is_active', 1)->findAll();

        if (empty($cameras)) {
            CLI::write('[' . date('H:i:s') . '] No monitored cameras found.', 'yellow');
            return;
        }

        foreach ($cameras as $camera) {
            $online    = $this->ping($camera['ip_address']);
            $newStatus = $online ? 'online' : 'offline';

            if ($camera['last_status'] === $newStatus) {
                CLI::write('[' . date('H:i:s') . '] ' . $camera['name'] . ' → ' . strtoupper($newStatus) . ' (no change)');
                continue;
            }

            // If coming back ONLINE — check if the offline event was never notified and send it first
            if ($newStatus === 'online') {
                $missedOffline = $logModel
                    ->where('camera_id', $camera['id'])
                    ->where('status', 'offline')
                    ->where('notified_telegram', 0)
                    ->orderBy('id', 'DESC')
                    ->first();

                if ($missedOffline) {
                    $sent = $this->sendTelegram($camera, 'offline');
                    if ($sent) {
                        $logModel->update($missedOffline['id'], ['notified_telegram' => 1]);
                        CLI::write('[' . date('H:i:s') . '] 📨 Sent missed OFFLINE alert: ' . $camera['name'], 'yellow');
                    }
                }
            }

            // Log new event
            $logModel->insert([
                'camera_id'         => $camera['id'],
                'status'            => $newStatus,
                'notified_telegram' => 0,
            ]);
            $logId = $logModel->getInsertID();

            // Update last_status
            $cameraModel->update($camera['id'], ['last_status' => $newStatus]);

            // Telegram — retry 3x on offline
            $sent     = false;
            $maxTries = $newStatus === 'offline' ? 3 : 1;
            for ($try = 1; $try <= $maxTries; $try++) {
                if ($try > 1) {
                    CLI::write('[' . date('H:i:s') . '] Telegram retry ' . $try . '/' . $maxTries . '...', 'yellow');
                    sleep(5);
                }
                $sent = $this->sendTelegram($camera, $newStatus);
                if ($sent) break;
            }

            $logModel->update($logId, ['notified_telegram' => $sent ? 1 : 0]);

            $icon  = $online ? '✅' : '🔴';
            $color = $online ? 'green' : 'red';
            $tg    = $sent ? '(Telegram ✓)' : '(Telegram ✗ — will retry next cycle)';
            CLI::write('[' . date('H:i:s') . '] ' . $icon . ' ' . $camera['name'] . ' is now ' . strtoupper($newStatus) . ' ' . $tg, $color);
        }
    }

    private function retryUnsent(CameraModel $cameraModel, CameraStatusLogModel $logModel): void
    {
        // Extended to 2 hours to catch long outages
        $unsent = $logModel
            ->where('notified_telegram', 0)
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-2 hours')))
            ->findAll();

        foreach ($unsent as $log) {
            $camera = $cameraModel->find($log['camera_id']);
            if (!$camera) continue;

            $sent = $this->sendTelegram($camera, $log['status']);
            if ($sent) {
                $logModel->update($log['id'], ['notified_telegram' => 1]);
                CLI::write('[' . date('H:i:s') . '] 📨 Retry sent: ' . $camera['name'] . ' → ' . strtoupper($log['status']), 'cyan');
            }
        }
    }

    private function ping(string $ip): bool
    {
        foreach ([80, 8000, 554] as $port) {
            $sock = @fsockopen($ip, $port, $e, $s, 2);
            if ($sock) {
                fclose($sock);
                return true;
            }
        }
        return false;
    }

private function sendTelegram(array $camera, string $status): bool
{
    $token  = env('telegram.bot_token');
    $chatId = env('telegram.chat_id');
    
    // DEBUG LOGGING
    log_message('debug', '[TELEGRAM] Token: ' . (substr($token, 0, 20) . '...'));
    log_message('debug', '[TELEGRAM] Chat ID: ' . $chatId);
    log_message('debug', '[TELEGRAM] Status: ' . $status);
    log_message('debug', '[TELEGRAM] Camera: ' . $camera['name']);
    
    if (!$token || !$chatId) {
        log_message('error', '[TELEGRAM] Missing token or chatId!');
        return false;
    }

    $icon = $status === 'online' ? '✅' : '🔴';
    $msg  = "{$icon} <b>Camera " . strtoupper($status) . "</b>\n"
          . "📷 <b>" . htmlspecialchars($camera['name']) . "</b>\n"
          . "📍 " . htmlspecialchars($camera['location'] ?? 'Unknown') . "\n"
          . "🌐 <code>" . htmlspecialchars($camera['ip_address']) . "</code>\n"
          . "🕐 " . date('Y-m-d H:i:s');

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_POSTFIELDS     => [
            'chat_id'    => $chatId,
            'text'       => $msg,
            'parse_mode' => 'HTML',
        ],
    ]);
    $result   = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($result, true);
    
    // LOG RESPONSE
    log_message('debug', '[TELEGRAM] Response: ' . json_encode($response));

    if (empty($response['ok'])) {
        log_message('error', '[TELEGRAM] Failed [' . $status . '] ' . $camera['name'] . ': ' . ($response['description'] ?? 'unknown'));
    } else {
        log_message('info', '[TELEGRAM] ✅ Sent [' . $status . '] ' . $camera['name']);
    }

    return !empty($response['ok']);
}

}