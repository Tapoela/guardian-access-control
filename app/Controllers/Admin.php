<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\SiteModel;
use App\Models\AuditModel;
use CodeIgniter\Controller;

class Admin extends Controller
{
    public function users()
    {
        helper(['permission']);
        $session = session();
        if (!$session->get('isLoggedIn') || !hasPermission('user_management')) {
            return redirect()->to('/dashboard');
        }
        
        $db = \Config\Database::connect();
        $query = $db->table('users u')
            ->select('u.id, u.username, u.email, u.site_id, u.created_at,
                      r.id as role_id, r.name as role_name,
                      s.name as site_name')
            ->join('user_roles ur', 'ur.user_id = u.id', 'left')
            ->join('roles r', 'r.id = ur.role_id', 'left')
            ->join('sites s', 's.id = u.site_id', 'left');

        // Non-super-admins only see users from their own site
        if ($session->get('site_id') !== null) {
            $query->where('u.site_id', $session->get('site_id'));
        }

        $users = $query->orderBy('u.username')->get()->getResultArray();
        return view('admin/users', ['users' => $users]);
    }

    public function addUser()
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('user_management')) {
            return redirect()->to('/dashboard');
        }

        $db = \Config\Database::connect();
        
        // OPTIMIZED: Fetch sites and roles once, cache in local vars
        $sites = $db->table('sites')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->getResultArray();
        
        $roles = $db->table('roles')
            ->select('id, name')
            ->orderBy('name')
            ->get()
            ->getResultArray();

        if (strtolower($this->request->getMethod()) === 'post') {
            $username = trim($this->request->getPost('username'));
            $email    = trim($this->request->getPost('email'));
            $password = $this->request->getPost('password');
            $roleId   = (int)$this->request->getPost('role_id');
            $siteId   = $this->request->getPost('site_id') ? (int)$this->request->getPost('site_id') : null;

            // OPTIMIZED: Single indexed query for duplicate check
            $emailExists = $db->table('users')
                ->where('email', $email)
                ->countAllResults() > 0;
            
            $usernameExists = $db->table('users')
                ->where('username', $username)
                ->countAllResults() > 0;

            if ($emailExists) {
                return redirect()->back()->withInput()
                    ->with('error', 'A user with that email already exists.');
            }
            if ($usernameExists) {
                return redirect()->back()->withInput()
                    ->with('error', 'A user with that username already exists.');
            }

            // OPTIMIZED: Use transaction for data integrity
            $db->transBegin();
            try {
                $db->table('users')->insert([
                    'username'      => $username,
                    'email'         => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'site_id'       => $siteId,
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
                $userId = $db->insertID();

                $db->table('user_roles')->insert([
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'site_id' => $siteId,
                ]);

                $db->transCommit();

                AuditModel::log('user_add', 'user', $userId, 
                    "Added user {$email} (username: {$username}) to site_id={$siteId}, role_id={$roleId}");
                
                return redirect()->to('/admin/users')
                    ->with('success', "User '{$username}' added successfully.");
            } catch (\Exception $e) {
                $db->transRollback();
                log_message('error', 'User creation failed: ' . $e->getMessage());
                return redirect()->back()->withInput()
                    ->with('error', 'Failed to create user. Please try again.');
            }
        }

        return view('admin/add_user', ['sites' => $sites, 'roles' => $roles]);
    }

    public function editUser(int $id)
    {
        helper(['permission']);
        if (!session()->get('isLoggedIn') || !hasPermission('user_management')) {
            return redirect()->to('/dashboard');
        }

        $db = \Config\Database::connect();
        
        // OPTIMIZED: Single query to get user + current role assignment
        $user = $db->table('users u')
            ->select('u.id, u.username, u.email, u.site_id, u.created_at,
                      ur.role_id, ur.site_id as role_site_id')
            ->join('user_roles ur', 'ur.user_id = u.id', 'left')
            ->where('u.id', $id)
            ->get()
            ->getRowArray();

        if (!$user) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }

        // Fetch sites and roles
        $sites = $db->table('sites')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->getResultArray();
        
        $roles = $db->table('roles')
            ->select('id, name')
            ->orderBy('name')
            ->get()
            ->getResultArray();

        if (strtolower($this->request->getMethod()) === 'post') {
            $username = trim($this->request->getPost('username'));
            $email    = trim($this->request->getPost('email'));
            $roleId   = (int)$this->request->getPost('role_id');
            $siteId   = $this->request->getPost('site_id') ? (int)$this->request->getPost('site_id') : null;

            // OPTIMIZED: Check for duplicate email/username excluding current user
            $emailExists = $db->table('users')
                ->where('email', $email)
                ->where('id !=', $id)
                ->countAllResults() > 0;
            
            $usernameExists = $db->table('users')
                ->where('username', $username)
                ->where('id !=', $id)
                ->countAllResults() > 0;

            if ($emailExists) {
                return redirect()->back()->withInput()
                    ->with('error', 'A different user with that email already exists.');
            }
            if ($usernameExists) {
                return redirect()->back()->withInput()
                    ->with('error', 'A different user with that username already exists.');
            }

            // OPTIMIZED: Use transaction
            $db->transBegin();
            try {
                $db->table('users')
                    ->where('id', $id)
                    ->update([
                        'username' => $username,
                        'email'    => $email,
                        'site_id'  => $siteId,
                    ]);

                // Delete old role assignment and create new one
                $db->table('user_roles')->where('user_id', $id)->delete();
                $db->table('user_roles')->insert([
                    'user_id' => $id,
                    'role_id' => $roleId,
                    'site_id' => $siteId,
                ]);

                $db->transCommit();

                AuditModel::log('user_edit', 'user', $id, 
                    "Updated user {$email} (username: {$username}) site_id={$siteId}, role_id={$roleId}");
                
                return redirect()->to('/admin/users')
                    ->with('success', 'User updated successfully.');
            } catch (\Exception $e) {
                $db->transRollback();
                log_message('error', 'User update failed: ' . $e->getMessage());
                return redirect()->back()->withInput()
                    ->with('error', 'Failed to update user. Please try again.');
            }
        }

        return view('admin/edit_user', [
            'user'  => $user,
            'sites' => $sites,
            'roles' => $roles,
        ]);
    }
    
    public function deleteUser($id)
    {
        helper(['permission']);
        $session = session();
        if (!$session->get('isLoggedIn') || !hasPermission('user_management')) {
            return redirect()->to('/dashboard');
        }

        $db = \Config\Database::connect();
        
        // Get user info for audit log
        $user = $db->table('users')->select('username, email')->where('id', $id)->get()->getRowArray();
        
        // OPTIMIZED: Use transaction for data integrity
        $db->transBegin();
        try {
            $db->table('user_roles')->where('user_id', $id)->delete();
            (new UserModel())->delete($id);
            $db->transCommit();

            AuditModel::log('user_delete', 'user', $id, 
                "Deleted user {$user['email']} (username: {$user['username']})");
            
            return redirect()->to('/admin/users')
                ->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'User deletion failed: ' . $e->getMessage());
            return redirect()->to('/admin/users')
                ->with('error', 'Failed to delete user. Please try again.');
        }
    }
}