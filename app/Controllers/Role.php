<?php
namespace App\Controllers;

use App\Models\RoleModel;
use App\Models\PermissionModel;
use CodeIgniter\Controller;

class Role extends Controller
{
    public function index()
    {
        helper(['permission']);
        $session = session();
        if (!$session->get('isLoggedIn') || !hasPermission('role_management')) {
            return redirect()->to('/dashboard');
        }
        $roleModel = new RoleModel();
        $roles = $roleModel->findAll();
        return view('admin/roles/index', ['roles' => $roles]);
    }

    public function add()
    {
        helper(['permission']);
        $session = session();
        if (!$session->get('isLoggedIn') || !hasPermission('role_management')) {
            return redirect()->to('/dashboard');
        }
        helper(['form']);
        if ($this->request->getMethod() === 'post') {
            $roleModel = new RoleModel();
            $roleModel->insert(['name' => $this->request->getPost('name')]);
            return redirect()->to('/roles');
        }
        return view('admin/roles/add');
    }

    public function edit($id)
    {
        helper(['permission']);
        $session = session();
        if (!$session->get('isLoggedIn') || !hasPermission('role_management')) {
            return redirect()->to('/dashboard');
        }
        $roleModel = new RoleModel();
        $role = $roleModel->find($id);
        if (!$role) {
            return redirect()->to('/roles');
        }
        helper(['form']);
        if ($this->request->getMethod() === 'post') {
            $roleModel->update($id, ['name' => $this->request->getPost('name')]);
            return redirect()->to('/roles');
        }
        return view('admin/roles/edit', ['role' => $role]);
    }

    public function delete($id)
    {
        helper(['permission']);
        $session = session();
        if (!$session->get('isLoggedIn') || !hasPermission('role_management')) {
            return redirect()->to('/dashboard');
        }
        $roleModel = new RoleModel();
        $roleModel->delete($id);
        return redirect()->to('/roles');
    }
}
