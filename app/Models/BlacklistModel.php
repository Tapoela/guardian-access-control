<?php
namespace App\Models;

use CodeIgniter\Model;

class BlacklistModel extends Model
{
    protected $table      = 'blacklist';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['registration', 'reason', 'notes', 'created_by'];
    protected $useTimestamps = false;

    public function getAllWithCreator()
    {
        return $this->db->table('blacklist')
            ->select('blacklist.*, users.username as created_by_name')
            ->join('users', 'users.id = blacklist.created_by', 'left')
            ->orderBy('blacklist.created_at', 'DESC')
            ->get()->getResultArray();
    }

    public function isBlacklisted(string $registration): bool
    {
        return !empty($this->where('registration', strtoupper(trim($registration)))->first());
    }
}
