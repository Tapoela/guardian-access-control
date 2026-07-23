<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RoleModel;
use CodeIgniter\Database\BaseBuilder;

class AppSettings extends BaseController
{
    // Hardcoded notification types (avoid database lookups)
    private const NOTIFICATION_TYPES = ['blacklist', 'visitor_arrival', 'whitelist'];

    public function index()
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('settings_management')) {
            return redirect()->to('/dashboard');
        }

        $db = \Config\Database::connect();

        // OPTIMIZED: Fetch roles + all settings in TWO queries (not 1+N)
        $roles = $db->table('roles')
            ->select('id, name')
            ->orderBy('name')
            ->get()
            ->getResultArray();

        // OPTIMIZED: Single query to get ALL settings at once
        $allSettings = $db->table('role_notification_settings')
            ->select('role_id, notification_type, enabled')
            ->get()
            ->getResultArray();

        // Build nested array: $settings[role_id][notification_type] = enabled
        $settings = [];
        foreach ($allSettings as $row) {
            $settings[$row['role_id']][$row['notification_type']] = (bool)$row['enabled'];
        }

        // Ensure all roles/types have entries (default to false if missing)
        foreach ($roles as $role) {
            if (!isset($settings[$role['id']])) {
                $settings[$role['id']] = [];
            }
            foreach (self::NOTIFICATION_TYPES as $type) {
                if (!isset($settings[$role['id']][$type])) {
                    $settings[$role['id']][$type] = false;
                }
            }
        }

        return view('admin/settings/app', [
            'roles'               => $roles,
            'notificationTypes'   => self::NOTIFICATION_TYPES,
            'settings'            => $settings,
        ]);
    }

    public function save()
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('settings_management')) {
            return redirect()->to('/dashboard');
        }

        $db = \Config\Database::connect();

        // OPTIMIZED: Fetch roles and settings once
        $roles = $db->table('roles')
            ->select('id')
            ->get()
            ->getResultArray();

        $roleIds = array_column($roles, 'id');

        // Get existing settings in ONE query
        $existingSettings = $db->table('role_notification_settings')
            ->select('id, role_id, notification_type')
            ->whereIn('role_id', $roleIds)
            ->get()
            ->getResultArray();

        // Build map of existing: role_id|notification_type => id
        $existingMap = [];
        foreach ($existingSettings as $setting) {
            $key = $setting['role_id'] . '|' . $setting['notification_type'];
            $existingMap[$key] = $setting['id'];
        }

        // OPTIMIZED: Batch prepare insert/update arrays
        $toInsert = [];
        $toUpdate = [];

        $db->transBegin();
        try {
            foreach ($roleIds as $roleId) {
                foreach (self::NOTIFICATION_TYPES as $type) {
                    $enabled = (int)($this->request->getPost("role_{$roleId}_{$type}") ? 1 : 0);
                    $key = $roleId . '|' . $type;

                    if (isset($existingMap[$key])) {
                        // Existing setting - prepare for batch update
                        $toUpdate[] = [
                            'id'      => $existingMap[$key],
                            'enabled' => $enabled,
                        ];
                    } else {
                        // New setting - prepare for batch insert
                        $toInsert[] = [
                            'role_id'            => $roleId,
                            'notification_type' => $type,
                            'enabled'           => $enabled,
                        ];
                    }
                }
            }

            // OPTIMIZED: Batch insert new settings
            if (!empty($toInsert)) {
                $db->table('role_notification_settings')->insertBatch($toInsert);
            }

            // OPTIMIZED: Batch update existing settings
            if (!empty($toUpdate)) {
                foreach ($toUpdate as $update) {
                    $db->table('role_notification_settings')
                        ->where('id', $update['id'])
                        ->update(['enabled' => $update['enabled']]);
                }
            }

            $db->transCommit();

            return redirect()->to('/admin/appsettings')
                ->with('success', 'Notification settings updated successfully!');

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'AppSettings save failed: ' . $e->getMessage());
            return redirect()->to('/admin/appsettings')
                ->with('error', 'Failed to update settings. Please try again.');
        }
    }
}