<?php

namespace App\Controllers;

use App\Models\CameraModel;
use App\Libraries\TelegramAlert;
use App\Libraries\BoomGate;
use App\Libraries\CameraSnapshot;
use CodeIgniter\Controller;

class AnprReceiver extends Controller
{
    private const SNAPSHOT_DIR = 'uploads/anpr/';

    public function event(string $token = '')
    {
        // 1 — Authenticate camera
        $cameraModel = new CameraModel();
        $camera      = $cameraModel->findByToken($token);
		
		//log_message('debug', "[ANPR] Incoming token: $token, matched camera: " . json_encode($camera));

        if (!$camera) {
            log_message('warning', "[ANPR] Unknown or inactive camera token: {$token}");
            return $this->response->setStatusCode(401)->setBody('Unauthorised');
        }

        $siteId = (int)($camera['site_id'] ?? 1);

        // 2 — Read raw body
        $rawBody     = (string) file_get_contents('php://input');
        $contentType = $this->request->getHeaderLine('Content-Type');
        if (empty($rawBody)) {
            $rawBody = $this->reconstructBodyFromParsed();
        }

        //log_message('debug', '[ANPR] Raw body size: ' . strlen($rawBody) . ' bytes. CT: ' . $contentType);

        // 3 — Ignore non-ANPR events
        $eventType = $this->extractEventType($rawBody);
        if ($eventType && !in_array(strtoupper($eventType), [
            'ANPR', 'LICENSE_PLATE', 'LICENSEPLATE', 'TRAFFICVIOLATION', 'TPSREALTIME'
        ], true)) {
            //log_message('debug', "[ANPR] Camera [{$camera['name']}] – ignoring event type: {$eventType}");
            return $this->response->setStatusCode(200)->setBody('OK');
        }

        // 4 — Parse plate
        $plate      = $this->extractPlate($rawBody);
        $confidence = $this->extractConfidence($rawBody);
        $direction  = $this->extractDirection($rawBody);

		//log_message('debug', "[ANPR] Plate extraction: plate=[$plate], confidence=[$confidence], direction=[$direction]");

        if (!$plate) {
            $preview = substr(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\xFF]/', '.', $rawBody), 0, 500);
            log_message('info', "[ANPR] Camera [{$camera['name']}] – no plate found, saving image only. Body[0:500]: {$preview}");
            $rawXml       = $this->extractXmlPart($rawBody); // ← Fix 1: missing semicolon
            $snapshotPath = $this->saveSnapshot($rawBody, 'NOPLATE_' . time());
            $eventTime    = $this->extractDateTime($rawBody) ?? date('Y-m-d H:i:s');

            if ($snapshotPath) {
                $db = \Config\Database::connect();

                $xml = $rawXml ? simplexml_load_string($rawXml) : null; // ← Fix 2: parse XML for vehicle details if available

				$vehicleInfo = $xml->ANPR->vehicleInfo ?? null;
				
				log_message('debug', '[ANPR] Raw XML: ' . $rawXml);
				log_message('debug', '[ANPR] Extracted vehicle_make: ' . ($vehicleInfo->vehicleLogoRecogStrName ?? 'NULL'));
				//log_message('debug', '[ANPR] Extracted vehicle_color: ' . ($vehicleInfo->color ?? 'NULL'));
				log_message('debug', '[ANPR] Extracted vehicle_type: ' . ($xml->ANPR->vehicleType ?? 'NULL'));

                $db->table('anpr_events')->insert([
                    'site_id'       => $siteId,
                    'camera_id'     => $camera['id'],
                    'camera_name'   => $camera['name'] ?? 'Unknown',
                    'registration'  => 'NO PLATE',
                    'confidence'    => 0,
                    'direction'     => $direction,
                    'result'        => 'unknown',
                    'member_name'   => null,
                    'unit_number'   => null,
                    'snapshot_path' => $snapshotPath,
                    'raw_xml'       => $rawXml, // ← Fix 3: missing $ sign
                    'notes'         => 'No plate detected — image only',
                    'created_at'    => $eventTime,
                    'vehicle_make'  => (string)($vehicleInfo->vehicleLogoRecogStrName ?? ''),
					'vehicle_color' => (string)($vehicleInfo->color                   ?? ''),
					'vehicle_type'  => (string)($xml->ANPR->vehicleType               ?? ''),
                ]);

                $eventId     = (int) $db->insertID();
                $hasOverview = !empty($camera['overview_camera_ip']);
                $ovIp        = $camera['overview_camera_ip']   ?? null;
                $ovUser      = $camera['overview_camera_user'] ?? 'admin';
                $ovPass      = $camera['overview_camera_pass'] ?? 'admin';
                $delay       = $hasOverview ? (int)($camera['overview_snapshot_delay'] ?? 5) : 0;
                $snapshotDir = self::SNAPSHOT_DIR;

                if ($hasOverview && $ovIp) {
                    register_shutdown_function(function () use (
                        $eventId, $delay, $ovIp, $ovUser, $ovPass, $snapshotDir
                    ) {
                        if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
                        if ($delay > 0) sleep($delay);
                        try {
                            $snap         = new CameraSnapshot($ovIp, $ovUser, $ovPass, 10);
                            $overviewPath = $snap->captureAndSave('NOPLATE', $snapshotDir);
                            if ($overviewPath) {
                                $db = \Config\Database::connect();
                                $db->table('anpr_events')
                                   ->where('id', $eventId)
                                   ->update(['overview_snapshot_path' => $overviewPath]);
                                log_message('info', "[ANPR] No-plate overview saved: {$overviewPath}");
                            }
                        } catch (\Throwable $e) {
                            log_message('error', "[ANPR] No-plate overview exception: " . $e->getMessage());
                        }
                    });
                }

                log_message('info', "[ANPR] No-plate event saved with snapshot: {$snapshotPath}");
            }

            return $this->response->setStatusCode(200)->setBody('OK');
        }

        $plate     = strtoupper(trim($plate));
        $eventTime = $this->extractDateTime($rawBody) ?? date('Y-m-d H:i:s');
        $rawXml    = $this->extractXmlPart($rawBody);

        //log_message('info', "[ANPR] Camera [{$camera['name']}] plate: {$plate} conf:{$confidence}% dir:{$direction} site:{$siteId}");

        // 5 — Save plate snapshot (from camera push)
        $snapshotPath = $this->saveSnapshot($rawBody, $plate);

        // 6 — Check plate + store event; get back event ID + blacklist info
        $check          = $this->checkPlate($plate, $confidence, $direction, $siteId, $camera, $eventTime, $snapshotPath, $rawXml);
        $eventId        = $check['eventId'];
        $isBlacklisted  = $check['isBlacklisted'];
        $blacklistNotes = $check['blacklistNotes'];
        $location       = $camera['name'] ?? 'Unknown';

        // 7 — Single shutdown function: capture overview THEN send Telegram
        if ($eventId) {
            $hasOverview = !empty($camera['overview_camera_ip']);
            $delay       = $hasOverview ? (int)($camera['overview_snapshot_delay'] ?? 5) : 0;
            $ovIp        = $camera['overview_camera_ip']   ?? null;
            $ovUser      = $camera['overview_camera_user'] ?? 'admin';
            $ovPass      = $camera['overview_camera_pass'] ?? 'admin';
            $snapshotDir = self::SNAPSHOT_DIR;

            register_shutdown_function(function () use (
                $eventId, $delay, $ovIp, $ovUser, $ovPass, $plate,
                $snapshotDir, $snapshotPath, $location, $siteId,
                $isBlacklisted, $blacklistNotes, $hasOverview
            ) {
                // Flush response to camera immediately
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }

                $overviewPath = null;

                // Capture overview if camera is configured
                if ($hasOverview && $ovIp) {
                    if ($delay > 0) sleep($delay);

                    try {
                        $snap         = new CameraSnapshot($ovIp, $ovUser, $ovPass, 10);
                        $overviewPath = $snap->captureAndSave($plate, $snapshotDir);

                        if ($overviewPath) {
                            $db = \Config\Database::connect();
                            $db->table('anpr_events')
                               ->where('id', $eventId)
                               ->update(['overview_snapshot_path' => $overviewPath]);
                            log_message('info', "[ANPR] Overview snapshot saved: {$overviewPath} for event {$eventId}");
                        } else {
                            log_message('warning', "[ANPR] Overview snapshot failed for event {$eventId}");
                        }
                    } catch (\Throwable $e) {
                        log_message('error', "[ANPR] Overview snapshot exception: " . $e->getMessage());
                    }
                }

                // Send Telegram AFTER overview is ready (or timed out)
                if ($isBlacklisted) {
                    try {
                        $telegram = new TelegramAlert();
                        $telegram->sendBlacklistAlert(
                            $plate,
                            $blacklistNotes ?? '',
                            $location,
                            $siteId,
                            $snapshotPath,
                            $overviewPath   // null if no overview camera or capture failed
                        );
                    } catch (\Throwable $e) {
                        log_message('error', "[ANPR] Telegram alert exception: " . $e->getMessage());
                    }
                }
            });
        }

        return $this->response->setStatusCode(200)->setBody('OK');
    }

    // ----------------------------------------------------------------
    // Returns event ID + blacklist status so event() can use them
    // ----------------------------------------------------------------
    private function checkPlate(
        string  $plate,
        int     $confidence,
        string  $direction,
        int     $siteId,
        array   $camera,
        string  $eventTime,
        ?string $snapshotPath,
        ?string $rawXml
    ): array {
        $db       = \Config\Database::connect();
        $location = $camera['name'] ?? 'Unknown';

        $result         = 'unknown';
        $memberName     = null;
        $unitNumber     = null;
        $notes          = null;
        $isBlacklisted  = false;
        $blacklistNotes = null;
        /*
        // 1 — Duplicate guard FIRST: same plate + camera within 10 seconds
        $existingEvent = $db->table('anpr_events')
            ->where('camera_id',    $camera['id'])
            ->where('registration', $plate)
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-10 seconds')))
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();

        if ($existingEvent) {
            log_message('info', "[ANPR] Duplicate suppressed: {$plate} on camera {$camera['id']}");
            return [
                'eventId'        => (int)$existingEvent['id'],
                'isBlacklisted'  => false,
                'blacklistNotes' => null,
            ];
        }
        */

        // 2 — Blacklist check (site-scoped)
        $blackEntry = $db->table('blacklist')
            ->where('registration', $plate)
            ->where('site_id', $siteId)
            ->get()->getRowArray();

        if ($blackEntry) {
            $result         = 'blacklisted';
            $notes          = $blackEntry['reason'] ?? '';
            $isBlacklisted  = true;
            $blacklistNotes = $notes;
            log_message('warning', "[ANPR] BLACKLISTED {$plate} at {$location} (site {$siteId})");

        } else {
            // 3 — Whitelist check (site-scoped)

            $white = $db->table('whitelist w')
                ->select('w.id, m.first_name, m.last_name, m.unit_number')
                ->join('member_vehicles mv', 'mv.id = w.vehicle_id', 'inner')  // ← FIXED: Use INNER JOIN
                ->join('members m', 'm.id = mv.member_id', 'inner')            // ← FIXED: Use INNER JOIN
                ->where('mv.registration', strtoupper(trim($plate)))
                ->where('w.site_id', $siteId)
                ->where('(w.valid_until IS NULL OR w.valid_until >= NOW())', null, false)
                ->get()
                ->getRowArray();

            //log_message('debug', '[ANPR] Whitelist lookup for plate: ' . $plate . ' result: ' . json_encode($white));

            if ($white) {
                $result     = 'granted';
                $memberName = trim($white['first_name'] . ' ' . $white['last_name']);
                $unitNumber = $white['unit_number'];
                $notes      = "Member: {$memberName}" . ($unitNumber ? " (Unit {$unitNumber})" : '');

                log_message('debug', '[ANPR] Triggering camera alarm with: ip=' . ($camera['ip_address'] ?? 'NULL') .
                    ', user=' . ($camera['camera_user'] ?? 'NULL') .
                    ', pass=' . ($camera['camera_pass'] ?? 'NULL') .
                    ', channel=' . ($camera['alarm_output_channel'] ?? 'NULL') .
                    ', duration=' . ($camera['alarm_duration'] ?? 'NULL'));

                $this->triggerCameraAlarm(
                    $camera['ip_address'] ?? null,
                    $camera['camera_user'] ?? 'admin',
                    $camera['camera_pass'] ?? 'JpfOrs@159',
                    (int)($camera['alarm_output_channel'] ?? 1),
                    (int)($camera['alarm_duration']       ?? 5)
                );

                log_message('info', "[ANPR] GRANTED {$plate} ({$memberName}) at {$location} (site {$siteId}) alarm {$camera['alarm_duration']}s");
            } else {
                log_message('info', "[ANPR] UNKNOWN {$plate} at {$location} (site {$siteId})");
            }
        }

        $xml = $rawXml ? simplexml_load_string($rawXml) : null;
		$vehicleInfo = $xml && isset($xml->ANPR->vehicleInfo) ? $xml->ANPR->vehicleInfo : null;

		log_message('debug', '[ANPR] Raw XML: ' . $rawXml);
		log_message('debug', '[ANPR] Extracted vehicle_make: ' . ($vehicleInfo->vehicleLogoRecogStrName ?? 'NULL'));
		log_message('debug', '[ANPR] Extracted vehicle_color: ' . ($vehicleInfo->color ?? 'NULL'));
		log_message('debug', '[ANPR] Extracted vehicle_type: ' . ($xml->ANPR->vehicleType ?? 'NULL'));

        // 4 — Store ANPR event
        $db->table('anpr_events')->insert([
            'site_id'       => $siteId,
            'camera_id'     => $camera['id'],
            'camera_name'   => $location,
            'registration'  => $plate,
            'confidence'    => $confidence,
            'direction'     => $direction,
            'result'        => $result,
            'member_name'   => $memberName,
            'unit_number'   => $unitNumber,
            'snapshot_path' => $snapshotPath,
            'raw_xml'       => $rawXml,
            'notes'         => $notes,
            'created_at'    => $eventTime,
            'vehicle_make'  => (string)($vehicleInfo->vehicleLogoRecogStrName ?? ''),
			'vehicle_color' => (string)($vehicleInfo->color                   ?? ''),
			'vehicle_type'  => (string)($xml->ANPR->vehicleType               ?? ''),
        ]);

        $eventId = (int) $db->insertID();

        // 5 — Access log
        $db->table('access_log')->insert([
            'registration' => $plate,
            'result'       => $result,
            'location'     => $location,
            'notes'        => $notes,
            'site_id'      => $siteId,
            'created_at'   => $eventTime,
        ]);

        return [
            'eventId'        => $eventId,
            'isBlacklisted'  => $isBlacklisted,
            'blacklistNotes' => $blacklistNotes,
        ];
    }
    
    private function triggerCameraAlarm(
        ?string $ip,
        string  $user,
        string  $pass,
        int     $channel  = 1,
        int     $duration = 5
    ): void {
        if (!$ip) return;

        try {
            $url = "http://{$ip}/ISAPI/System/IO/outputs/{$channel}/trigger";
            $xml = "<IOPortData><outputState>high</outputState></IOPortData>";

            $maxRetries = 3;
            $attempt    = 0;
            $success    = false;

            while ($attempt < $maxRetries && !$success) {
                $attempt++;
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST  => 'PUT',
                    CURLOPT_POSTFIELDS     => $xml,
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/xml'],
                    CURLOPT_USERPWD        => "{$user}:{$pass}",
                    CURLOPT_HTTPAUTH       => CURLAUTH_DIGEST,
                    CURLOPT_TIMEOUT        => 15,
                    CURLOPT_CONNECTTIMEOUT => 10,
                ]);
                $res  = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($code === 200 || $code === 201) {
                    $success = true;
                    log_message('info', "[ANPR] Camera relay triggered on {$ip} ch{$channel} (attempt {$attempt}/{$maxRetries}) — HTTP {$code}");
                } elseif ($attempt < $maxRetries) {
                    log_message('warning', "[ANPR] Camera relay attempt {$attempt}/{$maxRetries} failed (HTTP {$code}), retrying...");
                    sleep(2); // Wait 2 seconds before retry
                } else {
                    log_message('error', "[ANPR] Camera relay failed after {$maxRetries} attempts (HTTP {$code})");
                }
            }

            // Deactivate after $duration seconds (non-blocking via shutdown)
            register_shutdown_function(function () use ($ip, $user, $pass, $channel, $duration) {
                sleep($duration);

                // Check manual_override before closing the relay
                $db = \Config\Database::connect();
                $cam = $db->table('cameras')->where('ip_address', $ip)->get()->getRowArray();
                if (!empty($cam['manual_override'])) {
                    log_message('info', "[ANPR] Manual override active for camera {$cam['id']} ({$ip}), not closing relay.");
                    return;
                }

                $url = "http://{$ip}/ISAPI/System/IO/outputs/{$channel}/trigger";
                $xml = "<IOPortData><outputState>low</outputState></IOPortData>";
                $ch  = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST  => 'PUT',
                    CURLOPT_POSTFIELDS     => $xml,
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/xml'],
                    CURLOPT_USERPWD        => "{$user}:{$pass}",
                    CURLOPT_HTTPAUTH       => CURLAUTH_DIGEST,
                    CURLOPT_TIMEOUT        => 15,
                ]);
                curl_exec($ch);
                curl_close($ch);
                log_message('info', "[ANPR] Camera relay deactivated on {$ip} ch{$channel}");
            });

        } catch (\Throwable $e) {
            log_message('error', "[ANPR] Camera relay trigger failed: " . $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------
    private function reconstructBodyFromParsed(): string
    {
        $parts = [];
        foreach ($_POST as $k => $v) {
            if (is_string($v)) $parts[] = "$k=$v";
        }
        foreach ($_FILES as $fileInfo) {
            $tmp = is_array($fileInfo['tmp_name']) ? $fileInfo['tmp_name'] : [$fileInfo['tmp_name']];
            foreach ($tmp as $t) {
                if ($t && file_exists($t)) $parts[] = file_get_contents($t);
            }
        }
        $ciBody = $this->request->getBody();
        if (!empty($ciBody)) $parts[] = $ciBody;
        return implode("\n", $parts);
    }

    private function extractEventType(string $body): ?string
    {
        // XML
        if (preg_match('/<eventType>(.*?)<\/eventType>/i', $body, $m)) {
            return trim($m[1]);
        }
        // JSON — "eventType": "TPSRealTime"
        if (preg_match('/"eventType"\s*:\s*"([^"]+)"/i', $body, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function extractDateTime(string $body): ?string
    {
        // Try XML first
        if (preg_match('/<dateTime>(.*?)<\/dateTime>/i', $body, $m)) {
            $raw = trim($m[1]);
        }
        // Try JSON dateTime field (camera sends TrafficFlow={...})
        elseif (preg_match('/"dateTime"\s*:\s*"([^"]+)"/i', $body, $m)) {
            $raw = trim($m[1]);
        }
        else {
            return null;
        }

        try {
            // DateTime handles ISO8601 with offset e.g. 2026-04-21T10:17:32+02:00
            $dt = new \DateTime($raw);
            $dt->setTimezone(new \DateTimeZone('Africa/Johannesburg'));
            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            log_message('error', "[ANPR] DateTime parse failed for: {$raw}");
            return null;
        }
    }

    private function extractXmlPart(string $body): ?string
    {
        foreach ([
            '/<\?xml[\s\S]*?<\/EventNotificationAlert>/i',
            '/<EventNotificationAlert[\s\S]*?<\/EventNotificationAlert>/i',
            '/<ANPRInfo[\s\S]*?<\/ANPRInfo>/i',
        ] as $pattern) {
            if (preg_match($pattern, $body, $m)) return $m[0];
        }
        return null;
    }

    private function extractPlate(string $body): ?string
    {
        // ── XML tags (Hikvision EventNotificationAlert) ──────────
        foreach (['originalLicensePlate', 'licensePlate', 'plateNumber', 'licenseNum'] as $tag) {
            if (preg_match("/<{$tag}>(.*?)<\/{$tag}>/i", $body, $m)) {
                $v = trim($m[1]);
                if ($v !== '' && strtolower($v) !== 'unknown') return $v;
            }
        }

        // ── JSON format (TrafficFlow camera payload) ─────────────
        // Strip "TrafficFlow=" prefix if present
        $json = preg_replace('/^[^{]*/s', '', $body);
        $json = substr($json, 0, strrpos($json, '}') + 1);

        $data = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($data['Target'])) {
            foreach ((array)$data['Target'] as $target) {
                // Look in PlateInfo
                $plate = $target['PlateInfo']['plateNo']
                      ?? $target['PlateInfo']['licensePlateNumber']
                      ?? $target['TargetInfo']['plateNo']
                      ?? $target['TargetInfo']['licensePlateNumber']
                      ?? null;

                if ($plate && strtolower($plate) !== 'unknown' && trim($plate) !== '') {
                    return trim($plate);
                }
            }
        }

        return null;
    }

    private function extractConfidence(string $body): int
    {
        foreach (['confidenceLevel', 'confidence'] as $tag) {
            if (preg_match("/<{$tag}>(\d+)<\/{$tag}>/i", $body, $m)) return (int)$m[1];
        }
        return 0;
    }

    private function extractDirection(string $body): string
    {
        // XML
        if (preg_match('/<direction>(.*?)<\/direction>/i', $body, $m)) {
            $raw = strtolower(trim($m[1]));
            if (in_array($raw, ['forward', 'entry', 'approach', 'positive'])) return 'entry';
            if (in_array($raw, ['reverse', 'exit', 'away', 'negative']))      return 'exit';
        }

        // JSON — "Moving Direction": "Reverse" / "Forward"
        if (preg_match('/"movingDirection"\s*:\s*"([^"]+)"/i', $body, $m) ||
            preg_match('/"direction"\s*:\s*"([^"]+)"/i', $body, $m)) {
            $raw = strtolower(trim($m[1]));
            if (in_array($raw, ['forward', 'entry', 'approach'])) return 'entry';
            if (in_array($raw, ['reverse', 'exit', 'away']))      return 'exit';
        }

        return 'unknown';
    }

    private function saveSnapshot(string $body, string $plate): ?string
    {
        $start = strpos($body, "\xFF\xD8\xFF");
        if ($start === false) return null;
        $end = strpos($body, "\xFF\xD9", $start);
        if ($end === false) return null;

        $jpeg    = substr($body, $start, $end - $start + 2);
        $dateDir = date('Ymd');
        $dir     = WRITEPATH . self::SNAPSHOT_DIR . $dateDir . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = preg_replace('/[^A-Z0-9]/', '', strtoupper($plate)) . '_' . time() . '.jpg';
        if (file_put_contents($dir . $filename, $jpeg) !== false) {
            return self::SNAPSHOT_DIR . $dateDir . '/' . $filename;
        }
        return null;
    }
}