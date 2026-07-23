<?php
namespace App\Controllers;

use App\Models\RoleModel;
use App\Models\PermissionModel;
use CodeIgniter\Controller;

class Settings extends Controller
{
    public function permissions()
    {
        helper(['permission']);
        $session = session();

        if (!$session->get('isLoggedIn') || !hasPermission('settings')) {
            return redirect()->to('/dashboard');
        }

        $db = \Config\Database::connect();
        $roles = (new RoleModel())->where('id !=', 1)->findAll(); // exclude administrator
        $permissions = (new PermissionModel())->findAll();

        // Build a matrix: role_id => [permission_id => true/false]
        $matrix = [];
        foreach ($roles as $role) {
            $matrix[$role['id']] = [];
            foreach ($permissions as $perm) {
                $matrix[$role['id']][$perm['id']] = false;
            }
        }

        // Fill in granted permissions
        $granted = $db->table('role_permissions')->get()->getResultArray();
        foreach ($granted as $row) {
            if (isset($matrix[$row['role_id']][$row['permission_id']])) {
                $matrix[$row['role_id']][$row['permission_id']] = true;
            }
        }

        return view('admin/settings/permissions', [
            'roles'       => $roles,
            'permissions' => $permissions,
            'matrix'      => $matrix,
        ]);
    }

    public function togglePermission()
    {
        helper(['permission']);
        $session = session();

        if (!$session->get('isLoggedIn') || !hasPermission('settings')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access denied']);
        }

        $db     = \Config\Database::connect();
        $roleId = (int) $this->request->getPost('role_id');
        $permId = (int) $this->request->getPost('permission_id');
        $grant  = $this->request->getPost('grant') === 'true';

        // Prevent modifying administrator (role_id = 1)
        if ($roleId === 1) {
            return $this->response->setJSON(['success' => false, 'message' => 'Cannot modify administrator permissions']);
        }

        $exists = $db->table('role_permissions')
            ->where('role_id', $roleId)
            ->where('permission_id', $permId)
            ->get()->getRowArray();

        if ($grant && !$exists) {
            $db->table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permId]);
        } elseif (!$grant && $exists) {
            $db->table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permId)
                ->delete();
        }

        return $this->response->setJSON(['success' => true]);
    }

    public function managePermissions()
    {
        helper(['permission']);
        $session = session();

        if (!$session->get('isLoggedIn') || !hasPermission('settings')) {
            return redirect()->to('/dashboard');
        }

        $permissions = (new PermissionModel())->findAll();

        return view('admin/settings/manage_permissions', [
            'permissions' => $permissions,
        ]);
    }

    public function addPermission()
    {
        helper(['permission']);
        $session = session();

        if (!$session->get('isLoggedIn') || !hasPermission('settings')) {
            return redirect()->to('/dashboard');
        }

        $name = trim(strtolower($this->request->getPost('name')));
        // Convert spaces to underscores for consistency
        $name = preg_replace('/\s+/', '_', $name);

        if ($name) {
            $db = \Config\Database::connect();
            // Check for duplicates
            $exists = $db->table('permissions')->where('name', $name)->get()->getRowArray();
            if (!$exists) {
                $db->table('permissions')->insert(['name' => $name]);
                session()->setFlashdata('success', 'Permission "' . $name . '" added successfully.');
            } else {
                session()->setFlashdata('error', 'Permission "' . $name . '" already exists.');
            }
        } else {
            session()->setFlashdata('error', 'Permission name cannot be empty.');
        }

        return redirect()->to('/settings/managePermissions');
    }

    public function deletePermission($id)
    {
        helper(['permission']);
        $session = session();

        if (!$session->get('isLoggedIn') || !hasPermission('settings')) {
            return redirect()->to('/dashboard');
        }

        // Prevent deleting core permissions
        $db = \Config\Database::connect();
        $perm = $db->table('permissions')->where('id', $id)->get()->getRowArray();
        $corePermissions = ['user_management', 'role_management', 'settings'];

        if ($perm && in_array($perm['name'], $corePermissions)) {
            session()->setFlashdata('error', 'Cannot delete core permission "' . $perm['name'] . '".');
            return redirect()->to('/settings/managePermissions');
        }

        // Remove from role_permissions first
        $db->table('role_permissions')->where('permission_id', $id)->delete();
        $db->table('permissions')->where('id', $id)->delete();

        session()->setFlashdata('success', 'Permission deleted successfully.');
        return redirect()->to('/settings/managePermissions');
    }
}
