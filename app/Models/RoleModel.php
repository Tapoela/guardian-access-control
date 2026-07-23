<?php
namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name'];
    protected $returnType = 'array';
    public function getRolesForUser($userId)
    {
        return $this->db->table('user_roles')
            ->select('roles.*')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->get()->getResultArray();
    }
}
