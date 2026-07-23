<?php

namespace App\Models;

use CodeIgniter\Model;

class SiteModel extends Model
{
    protected $table         = 'sites';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name', 'address', 'contact', 'is_active'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function getActiveSites(): array
    {
        return $this->where('is_active', 1)->orderBy('name')->findAll();
    }

    public function getDropdown(): array
    {
        $rows = $this->select('id, name')->where('is_active', 1)->orderBy('name')->findAll();
        return array_column($rows, 'name', 'id');
    }
}
