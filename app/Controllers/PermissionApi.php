<?php
// filepath: c:\Users\Administrator\Documents\GuardianControl\app\Controllers\PermissionApi.php

namespace App\Controllers;

class PermissionApi extends BaseController
{
    public function getUserPermissions()
    {
        header('Content-Type: application/json');
        
        $userId = (int) $this->request->getPost('user_id');
        
        if (!$userId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'user_id is required'
            ]);
        }

        $db = \Config\Database::connect();
        
        $user = $db->table('users')
            ->select('role_id')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        // Correct: permission_id (from your table structure)
        $permissions = $db->table('role_permissions rp')
            ->select('p.id, p.name')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('rp.role_id', $user['role_id'])
            ->get()
            ->getResultArray();

        log_message('info', 'User ' . $userId . ' role: ' . $user['role_id'] . ' permissions: ' . json_encode($permissions));

        return $this->response->setJSON([
            'success' => true,
            'permissions' => $permissions
        ]);
    }
}