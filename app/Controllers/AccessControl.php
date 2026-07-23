<?php

namespace App\Controllers;

use App\Models\BlacklistModel;
use App\Models\WhitelistModel;
use App\Models\MemberModel;
use App\Models\AuditModel;
use App\Libraries\TelegramAlert;
use App\Libraries\BoomGate;
use CodeIgniter\Controller;

class AccessControl extends Controller
{
    // ---------------------------------------------------------------
    // BLACKLIST
    // ---------------------------------------------------------------

    public function blacklist()
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('access_control')) {
            return redirect()->to('/dashboard');
        }
        $list = (new BlacklistModel())->getAllWithCreator();
        return view('access/blacklist/index', ['list' => $list]);
    }

    public function addBlacklist()
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('access_control')) {
            return redirect()->to('/dashboard');
        }

        if ($this->request->is('post')) {
            $model  = new BlacklistModel();
            $reg    = strtoupper(trim($this->request->getPost('registration')));
            $reason = $this->request->getPost('reason');
            $notes  = $this->request->getPost('notes');

            // ── Already on blacklist? ──────────────────────────────
            if ($model->isBlacklisted($reg)) {
                session()->setFlashdata('error', "Registration <strong>{$reg}</strong> is already on the blacklist.");
                return redirect()->back()->withInput();
            }

            // ── Is it currently on the whitelist? ─────────────────
            // OPTIMIZED: Single query with all JOINs
            $db              = \Config\Database::connect();
            $whitelistEntry  = $db->table('whitelist w')
                ->select('w.id AS wl_id, w.vehicle_id, mv.registration,
                          m.first_name, m.last_name, m.unit_number')
                ->join('member_vehicles mv', 'mv.id = w.vehicle_id', 'left')
                ->join('members m', 'm.id = mv.member_id', 'left')
                ->where('mv.registration', $reg)
                ->get()
                ->getRowArray();

            // If whitelisted and user has NOT yet confirmed removal, show confirmation
            if ($whitelistEntry && !$this->request->getPost('confirm_remove_whitelist')) {
                $owner = trim(($whitelistEntry['first_name'] ?? '') . ' ' . ($whitelistEntry['last_name'] ?? ''))
                       . ($whitelistEntry['unit_number'] ? ' (Unit ' . $whitelistEntry['unit_number'] . ')' : '');
                return view('access/blacklist/add', [
                    'whitelist_conflict' => true,
                    'conflict_reg'       => $reg,
                    'conflict_owner'     => $owner,
                    'conflict_wl_id'     => $whitelistEntry['wl_id'],
                    'prefill'            => [
                        'registration' => $reg,
                        'reason'       => $reason,
                        'notes'        => $notes,
                    ],
                ]);
            }

            // ── Save to blacklist ──────────────────────────────────
            $db->table('blacklist')->insert([
                'registration' => $reg,
                'reason'       => $reason,
                'notes'        => $notes,
                'created_by'   => session()->get('user_id'),
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
            $blId = $db->insertID();

            AuditModel::log('blacklist_add', 'blacklist', $blId,
                "Added {$reg} to blacklist. Reason: {$reason}");

            // ── Remove from whitelist if confirmed ─────────────────
            if ($whitelistEntry && $this->request->getPost('confirm_remove_whitelist') === 'yes') {
                $db->table('whitelist')->where('id', $whitelistEntry['wl_id'])->delete();
                AuditModel::log('whitelist_remove_for_blacklist', 'whitelist', $whitelistEntry['wl_id'],
                    "Removed {$reg} from whitelist when adding to blacklist.");
                session()->setFlashdata('success',
                    "<strong>{$reg}</strong> moved to blacklist (removed from whitelist).");
            } else {
                session()->setFlashdata('success',
                    "<strong>{$reg}</strong> added to blacklist.");
            }

            return redirect()->to('/access/blacklist');
        }

        return view('access/blacklist/add');
    }

    public function editBlacklist($id)
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('access_control')) {
            return redirect()->to('/dashboard');
        }
        $model = new BlacklistModel();
        $entry = $model->find($id);
        if (!$entry) return redirect()->to('/access/blacklist');

        if ($this->request->is('post')) {
            $reg = strtoupper(trim($this->request->getPost('registration')));
            $model->update($id, [
                'registration' => $reg,
                'reason'       => $this->request->getPost('reason'),
                'notes'        => $this->request->getPost('notes'),
            ]);
            AuditModel::log('blacklist_edit', 'blacklist', (int)$id,
                "Updated blacklist entry {$reg}.");
            session()->setFlashdata('success', 'Blacklist entry updated.');
            return redirect()->to('/access/blacklist');
        }
        return view('access/blacklist/edit', ['entry' => $entry]);
    }

    public function deleteBlacklist($id)
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('access_control')) {
            return redirect()->to('/dashboard');
        }
        $entry = (new BlacklistModel())->find($id);
        (new BlacklistModel())->delete($id);
        AuditModel::log('blacklist_delete', 'blacklist', (int)$id,
            'Deleted blacklist entry: ' . ($entry['registration'] ?? $id));
        session()->setFlashdata('success', 'Blacklist entry removed.');
        return redirect()->to('/access/blacklist');
    }

    // ---------------------------------------------------------------
    // WHITELIST (Members & Vehicles)
    // ---------------------------------------------------------------

    public function whitelist()
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('access_control')) {
            return redirect()->to('/dashboard');
        }
        $list = (new WhitelistModel())->getAllWithDetails();
        return view('access/whitelist/index', ['list' => $list]);
    }

    public function addWhitelist()
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('access_control')) {
            return redirect()->to('/dashboard');
        }
        $db = \Config\Database::connect();
        // Get all active member vehicles not already whitelisted
        $vehicles = $db->table('member_vehicles mv')
            ->select('mv.id, mv.registration, mv.make, mv.colour, m.first_name, m.last_name, m.unit_number')
            ->join('members m', 'm.id = mv.member_id')
            ->where('mv.is_active', 1)
            ->whereNotIn('mv.id', function ($builder) {
                $builder->select('vehicle_id')->from('whitelist')->where('vehicle_id IS NOT NULL', null, false);
            })
            ->orderBy('m.last_name')
            ->get()
            ->getResultArray();

        if ($this->request->is('post')) {
            $vehicleId  = (int) $this->request->getPost('vehicle_id');
            $validFrom  = $this->request->getPost('valid_from') ?: null;
            $validUntil = $this->request->getPost('valid_until') ?: null;

            // OPTIMIZED: Single query to get vehicle + check blacklist
            $vehicle = $db->table('member_vehicles mv')
                ->select('mv.id, mv.registration, b.id as blacklist_id, b.reason')
                ->join('blacklist b', 'b.registration = mv.registration', 'left')
                ->where('mv.id', $vehicleId)
                ->get()
                ->getRowArray();

            if (!$vehicle) {
                session()->setFlashdata('error', 'Vehicle not found.');
                return redirect()->back();
            }

            $reg = strtoupper($vehicle['registration']);
            $blacklistEntry = $vehicle['blacklist_id'] ? [
                'id' => $vehicle['blacklist_id'],
                'reason' => $vehicle['reason']
            ] : null;

            // If blacklisted and user has NOT yet confirmed, show confirmation
            if ($blacklistEntry && !$this->request->getPost('confirm_remove_blacklist')) {
                return view('access/whitelist/add', [
                    'vehicles'              => $vehicles,
                    'blacklist_conflict'    => true,
                    'conflict_reg'         => $reg,
                    'conflict_reason'      => $blacklistEntry['reason'] ?? '',
                    'conflict_bl_id'       => $blacklistEntry['id'],
                    'prefill' => [
                        'vehicle_id'  => $vehicleId,
                        'valid_from'  => $validFrom,
                        'valid_until' => $validUntil,
                    ],
                ]);
            }

            // ── Save to whitelist ──────────────────────────────────
            $db->table('whitelist')->insert([
                'vehicle_id'  => $vehicleId,
                'valid_from'  => $validFrom,
                'valid_until' => $validUntil,
                'created_by'  => session()->get('user_id'),
            ]);
            $wlId = $db->insertID();

            AuditModel::log('whitelist_add', 'whitelist', (int)$wlId,
                "Added {$reg} (vehicle_id {$vehicleId}) to whitelist.");

            // ── Remove from blacklist if confirmed ─────────────────
            if ($blacklistEntry && $this->request->getPost('confirm_remove_blacklist') === 'yes') {
                $db->table('blacklist')->where('id', $blacklistEntry['id'])->delete();
                AuditModel::log('blacklist_remove_for_whitelist', 'blacklist', (int)$blacklistEntry['id'],
                    "Removed {$reg} from blacklist when adding to whitelist.");
                session()->setFlashdata('success',
                    "<strong>{$reg}</strong> moved to whitelist (removed from blacklist).");
            } else {
                session()->setFlashdata('success', "<strong>{$reg}</strong> added to whitelist.");
            }

            return redirect()->to('/access/whitelist');
        }
        return view('access/whitelist/add', ['vehicles' => $vehicles]);
    }

    public function deleteWhitelist($id)
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('access_control')) {
            return redirect()->to('/dashboard');
        }
        $db    = \Config\Database::connect();
        $entry = $db->table('whitelist w')
            ->select('w.id, mv.registration')
            ->join('member_vehicles mv', 'mv.id = w.vehicle_id', 'left')
            ->where('w.id', $id)
            ->get()
            ->getRowArray();
        (new WhitelistModel())->delete($id);
        AuditModel::log('whitelist_remove', 'whitelist', (int)$id,
            'Removed from whitelist: ' . ($entry['registration'] ?? $id));
        session()->setFlashdata('success', 'Vehicle removed from whitelist.');
        return redirect()->to('/access/whitelist');
    }

    // ---------------------------------------------------------------
    // MEMBERS
    // ---------------------------------------------------------------

    public function members()
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('access_control')) {
            return redirect()->to('/dashboard');
        }
        $members = (new MemberModel())->getMembersWithVehicleCount();
        return view('access/members/index', ['members' => $members]);
    }

    public function addMember()
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('access_control')) {
            return redirect()->to('/dashboard');
        }
        if ($this->request->is('post')) {
            $model     = new MemberModel();
            $db        = \Config\Database::connect();
            $firstName = trim($this->request->getPost('first_name'));
            $lastName  = trim($this->request->getPost('last_name'));
            $email     = trim($this->request->getPost('email'));
            $unit      = trim($this->request->getPost('unit_number'));
            $errors    = [];
            $regs      = array_map('strtoupper', array_map('trim', (array) $this->request->getPost('registrations')));

            // ── Duplicate checks ──────────────────────────────────
            // Same name + unit already exists?
            if ($unit !== '') {
                $exists = $db->table('members')
                    ->where('first_name', $firstName)
                    ->where('last_name',  $lastName)
                    ->where('unit_number', $unit)
                    ->countAllResults();
                if ($exists) {
                    $errors[] = "A member named {$firstName} {$lastName} in unit {$unit} already exists.";
                }
            }
            
            // Same email already exists?
            if ($email !== '') {
                $emailExists = $db->table('members')
                    ->where('email', $email)
                    ->countAllResults();
                if ($emailExists) {
                    $errors[] = "A member with email {$email} already exists.";
                }
            }
            
            // OPTIMIZED: Check all registrations in ONE query instead of looping
            $regsToCheck = array_filter($regs);
            if (!empty($regsToCheck)) {
                $existingRegs = $db->table('member_vehicles')
                    ->select('registration')
                    ->whereIn('registration', $regsToCheck)
                    ->get()
                    ->getResultArray();
                
                foreach ($existingRegs as $existingReg) {
                    $errors[] = "Registration <strong>{$existingReg['registration']}</strong> is already registered to another member.";
                }
            }

            if (!empty($errors)) {
                session()->setFlashdata('error', implode('<br>', $errors));
                return redirect()->back()->withInput();
            }
            // ─────────────────────────────────────────────────────

            $id = $model->insert([
                'first_name'  => $firstName,
                'last_name'   => $lastName,
                'unit_number' => $unit ?: null,
                'phone'       => $this->request->getPost('phone'),
                'email'       => $email ?: null,
                'status'      => $this->request->getPost('status') ?: 'active',
                'notes'       => $this->request->getPost('notes'),
                'created_by'  => session()->get('user_id'),
            ]);

            // OPTIMIZED: Batch insert vehicles + whitelist entries
            $vehicleInserts = [];
            $whitelistInserts = [];
            
            foreach ($regs as $i => $reg) {
                if ($reg === '') continue;
                
                $vehicleInserts[] = [
                    'member_id'    => $id,
                    'registration' => $reg,
                    'make'         => $this->request->getPost('makes')[$i] ?? null,
                    'model'        => $this->request->getPost('models')[$i] ?? null,
                    'colour'       => $this->request->getPost('colours')[$i] ?? null,
                ];
            }

            if (!empty($vehicleInserts)) {
                $db->table('member_vehicles')->insertBatch($vehicleInserts);
                
                // Get all inserted vehicles and whitelist them in batch
                $vehicles = $db->table('member_vehicles')
                    ->where('member_id', $id)
                    ->get()
                    ->getResultArray();
                
                foreach ($vehicles as $vehicle) {
                    $whitelistInserts[] = [
                        'vehicle_id'  => $vehicle['id'],
                        'valid_from'  => null,
                        'valid_until' => null,
                        'created_by'  => session()->get('user_id'),
                    ];
                }
                
                if (!empty($whitelistInserts)) {
                    $db->table('whitelist')->insertBatch($whitelistInserts);
                }
            }
            
            session()->setFlashdata('success', 'Member added successfully.');
            return redirect()->to('/access/members');
        }
        return view('access/members/add');
    }

    public function editMember($id)
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('access_control')) {
            return redirect()->to('/dashboard');
        }
        $model   = new MemberModel();
        $member  = $model->find($id);
        if (!$member) return redirect()->to('/access/members');

        $db       = \Config\Database::connect();
        $vehicles = $db->table('member_vehicles')->where('member_id', $id)->get()->getResultArray();

        if ($this->request->is('post')) {
            $model->update($id, [
                'first_name'  => $this->request->getPost('first_name'),
                'last_name'   => $this->request->getPost('last_name'),
                'unit_number' => $this->request->getPost('unit_number'),
                'phone'       => $this->request->getPost('phone'),
                'email'       => $this->request->getPost('email'),
                'status'      => $this->request->getPost('status'),
                'notes'       => $this->request->getPost('notes'),
            ]);
            session()->setFlashdata('success', 'Member updated successfully.');
            return redirect()->to('/access/members');
        }
        return view('access/members/edit', ['member' => $member, 'vehicles' => $vehicles]);
    }

    public function deleteMember($id)
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('access_control')) {
            return redirect()->to('/dashboard');
        }
        (new MemberModel())->delete($id);
        session()->setFlashdata('success', 'Member deleted.');
        return redirect()->to('/access/members');
    }

    // ---------------------------------------------------------------
    // MEMBER VEHICLES (add / delete from edit page)
    // ---------------------------------------------------------------

    public function addVehicle($memberId)
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('access_control')) {
            return redirect()->to('/dashboard');
        }
        if ($this->request->is('post')) {
            $reg = strtoupper(trim($this->request->getPost('registration')));
            if ($reg !== '') {
                $db = \Config\Database::connect();

                // OPTIMIZED: Single query to check existing vehicle
                $existing = $db->table('member_vehicles mv')
                    ->select('mv.id, m.first_name, m.last_name, m.unit_number')
                    ->join('members m', 'm.id = mv.member_id')
                    ->where('mv.registration', $reg)
                    ->get()
                    ->getRowArray();

                if ($existing) {
                    $owner = trim($existing['first_name'] . ' ' . $existing['last_name'])
                           . ($existing['unit_number'] ? ' (Unit ' . $existing['unit_number'] . ')' : '');
                    session()->setFlashdata('error', "Registration <strong>{$reg}</strong> is already registered to {$owner}.");
                    return redirect()->back();
                }

                // Insert vehicle
                $db->table('member_vehicles')->insert([
                    'member_id'    => $memberId,
                    'registration' => $reg,
                    'make'         => $this->request->getPost('make'),
                    'model'        => $this->request->getPost('model'),
                    'colour'       => $this->request->getPost('colour'),
                    'is_active'    => 1,
                ]);
                $vehicleId = $db->insertID();

                // Check if already whitelisted and add if not
                $alreadyWhitelisted = $db->table('whitelist')
                    ->where('vehicle_id', $vehicleId)
                    ->countAllResults() > 0;
                    
                if (!$alreadyWhitelisted) {
                    $db->table('whitelist')->insert([
                        'vehicle_id'  => $vehicleId,
                        'valid_from'  => null,
                        'valid_until' => null,
                        'created_by'  => session()->get('user_id'),
                    ]);
                }
                session()->setFlashdata('success', "Vehicle {$reg} added and whitelisted.");
            }
        }
        return redirect()->to("/access/members/edit/{$memberId}");
    }

    public function deleteVehicle($vehicleId)
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('access_control')) {
            return redirect()->to('/dashboard');
        }
        $db      = \Config\Database::connect();
        $vehicle = $db->table('member_vehicles')->where('id', $vehicleId)->get()->getRowArray();
        $memberId = $vehicle['member_id'] ?? null;
        // Remove from whitelist first (FK-safe), then delete the vehicle
        $db->table('whitelist')->where('vehicle_id', $vehicleId)->delete();
        $db->table('member_vehicles')->where('id', $vehicleId)->delete();
        session()->setFlashdata('success', 'Vehicle removed and de-whitelisted.');
        return $memberId
            ? redirect()->to("/access/members/edit/{$memberId}")
            : redirect()->to('/access/members');
    }

    // ---------------------------------------------------------------
    // ACCESS LOG
    // ---------------------------------------------------------------

    public function accessLog()
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('access_control')) {
            return redirect()->to('/dashboard');
        }

        $db  = \Config\Database::connect();
        // OPTIMIZED: Use subquery with proper timestamp calculation
        $log = $db->table('access_log al')
            ->select('al.*, ae.snapshot_path, ae.overview_snapshot_path')
            ->join(
                'anpr_events ae',
                'ae.registration = al.registration 
                 AND ae.site_id   = al.site_id 
                 AND ABS(TIMESTAMPDIFF(SECOND, ae.created_at, al.created_at)) <= 5',
                'left'
            )
            ->orderBy('al.created_at', 'DESC')
            ->limit(150)
            ->get()
            ->getResultArray();

        return view('access/log/index', ['log' => $log]);
    }

    // ---------------------------------------------------------------
    // PLATE CHECK API  (POST /access/check)
    // Called by ANPR camera / external system.
    // Payload (JSON or form): registration, location (optional)
    // Returns JSON.
    // ---------------------------------------------------------------

    public function checkPlate()
    {
        $this->response->setContentType('application/json');
        
        // Get parameters from both POST and JSON
        $input = $this->request->isJSON() ? $this->request->getJSON(true) : $this->request->getPost();
        $cameraId = $input['camera_id'] ?? null;
        $registration = strtoupper(trim($input['registration'] ?? ''));
        $location = $input['location'] ?? '';

        // Simple token auth for external callers
        $token = $this->request->getHeaderLine('X-API-Token') ?: ($input['api_token'] ?? '');
        $configToken = env('access.api_token', '');
        if ($configToken && $token !== $configToken) {
            return $this->response->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Unauthorised']);
        }

        if ($registration === '') {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => 'error', 'message' => 'Registration required']);
        }

        $db       = \Config\Database::connect();
        $telegram = new TelegramAlert();
        $gate     = new BoomGate();

        // --- Check blacklist first ---
        $blacklistModel = new BlacklistModel();
        $blacklistEntry = $blacklistModel->where('registration', $registration)->first();
        
        if ($blacklistEntry) {
            $db->table('access_log')->insert([
                'registration' => $registration,
                'result'       => 'blacklisted',
                'location'     => $location,
                'notes'        => $blacklistEntry['reason'] ?? '',
                'created_at'   => date('Y-m-d H:i:s'),
            ]);

            $telegram->sendBlacklistAlert($registration, $blacklistEntry['reason'] ?? '', $location);

            return $this->response->setJSON([
                'status'   => 'blacklisted',
                'message'  => 'Vehicle is blacklisted. Alert sent.',
                'reason'   => $blacklistEntry['reason'] ?? '',
            ]);
        }

        // --- Check whitelist + camera in SINGLE query ---
        $whitelistModel = new WhitelistModel();
        $result = $db->table('whitelist w')
            ->select('w.id, w.valid_from, w.valid_until,
                      mv.registration, m.first_name, m.last_name, m.unit_number,
                      c.id as camera_id, c.manual_override')
            ->join('member_vehicles mv', 'mv.id = w.vehicle_id', 'left')
            ->join('members m', 'm.id = mv.member_id', 'left')
            ->join('cameras c', 'c.id = ?', 'left')
            ->where('mv.registration', $registration)
            ->bind(['c.id' => $cameraId])
            ->get()
            ->getRowArray();

        if ($result) {
            $memberName = trim(($result['first_name'] ?? '') . ' ' . ($result['last_name'] ?? ''));
            $unit = $result['unit_number'] ?? '';

            // Check manual override
            if (!empty($result['manual_override'])) {
                log_message('info', "[ANPR] Boom manual override active for camera $cameraId, skipping relay trigger.");
                
                $db->table('access_log')->insert([
                    'registration' => $registration,
                    'result'       => 'granted_override',
                    'location'     => $location,
                    'notes'        => "Member: {$memberName} (Manual Override Active)" . ($unit ? " (Unit {$unit})" : ''),
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
                
                return $this->response->setJSON([
                    'status'      => 'granted_override',
                    'message'     => 'Access granted (manual override active).',
                    'member'      => $memberName,
                    'unit'        => $unit,
                ]);
            }

            // Open the gate
            $gateResult = $gate->open("Whitelist: {$registration} – {$memberName}");

            // Log it
            $db->table('access_log')->insert([
                'registration' => $registration,
                'result'       => 'granted',
                'location'     => $location,
                'notes'        => "Member: {$memberName}" . ($unit ? " (Unit {$unit})" : ''),
                'created_at'   => date('Y-m-d H:i:s'),
            ]);

            return $this->response->setJSON([
                'status'      => 'granted',
                'message'     => 'Access granted. Gate triggered.',
                'member'      => $memberName,
                'unit'        => $unit,
                'gate_result' => $gateResult,
            ]);
        }

        // --- Unknown plate ---
        $db->table('access_log')->insert([
            'registration' => $registration,
            'result'       => 'unknown',
            'location'     => $location,
            'notes'        => '',
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status'  => 'unknown',
            'message' => 'Registration not found in whitelist or blacklist.',
        ]);
    }
}