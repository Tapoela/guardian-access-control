<?php
namespace App\Controllers;

use App\Models\SiteModel;
use App\Models\AuditModel;
use CodeIgniter\Controller;

/**
 * Admin → Sites
 * Only super-admin (site_id IS NULL in session) can manage sites.
 */
class Sites extends Controller
{
    private function requireSuperAdmin(): bool
    {
        if (!session()->get('isLoggedIn') || session()->get('site_id') !== null) {
            session()->setFlashdata('error', 'Only super-admins can manage sites.');
            return false;
        }
        return true;
    }

    public function index()
    {
        if (!$this->requireSuperAdmin()) return redirect()->to('/dashboard');
        $sites = (new SiteModel())->orderBy('name')->findAll();
        return view('admin/sites/index', ['sites' => $sites]);
    }

    public function add()
    {
        if (!$this->requireSuperAdmin()) return redirect()->to('/dashboard');

        if ($this->request->is('post')) {
            $model = new SiteModel();
            $id = $model->insert([
                'name'      => trim($this->request->getPost('name')),
                'address'   => $this->request->getPost('address') ?: null,
                'contact'   => $this->request->getPost('contact') ?: null,
                'is_active' => 1,
            ]);
            AuditModel::log('site_add', 'site', (int)$id,
                'Added site: ' . $this->request->getPost('name'));
            session()->setFlashdata('success', 'Site added.');
            return redirect()->to('/admin/sites');
        }
        return view('admin/sites/add');
    }

    public function edit($id)
    {
        if (!$this->requireSuperAdmin()) return redirect()->to('/dashboard');
        $model = new SiteModel();
        $site  = $model->find($id);
        if (!$site) return redirect()->to('/admin/sites');

        if ($this->request->is('post')) {
            $model->update($id, [
                'name'      => trim($this->request->getPost('name')),
                'address'   => $this->request->getPost('address') ?: null,
                'contact'   => $this->request->getPost('contact') ?: null,
                'is_active' => (int) $this->request->getPost('is_active'),
            ]);
            AuditModel::log('site_edit', 'site', (int)$id,
                'Updated site: ' . $this->request->getPost('name'));
            session()->setFlashdata('success', 'Site updated.');
            return redirect()->to('/admin/sites');
        }
        return view('admin/sites/edit', ['site' => $site]);
    }

    public function delete($id)
    {
        if (!$this->requireSuperAdmin()) return redirect()->to('/dashboard');
        $site = (new SiteModel())->find($id);
        (new SiteModel())->update($id, ['is_active' => 0]); // soft-disable, never hard-delete
        AuditModel::log('site_disable', 'site', (int)$id,
            'Disabled site: ' . ($site['name'] ?? $id));
        session()->setFlashdata('success', 'Site disabled.');
        return redirect()->to('/admin/sites');
    }
}
