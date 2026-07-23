<?php
namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['username', 'password_hash', 'created_at', 'email'];
    protected $returnType = 'array';
    protected $useTimestamps = false;

    public function saveUser($data, $id = null)
    {
        if ($id) {
            return $this->update($id, $data);
        } else {
            return $this->insert($data);
        }
    }
}
