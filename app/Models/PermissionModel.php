<?php
namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table = 'permissions';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name'];
    protected $returnType = 'array';
    public function getPermissionsForUser($userId)
    {
        return $this->db->table('user_roles')
            ->select('permissions.*')
            ->join('role_permissions', 'role_permissions.role_id = user_roles.role_id')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('user_roles.user_id', $userId)
            ->get()->getResultArray();
    }
}
