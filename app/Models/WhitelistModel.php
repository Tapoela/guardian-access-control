<?php
namespace App\Models;

use CodeIgniter\Model;

class WhitelistModel extends Model
{
    protected $table      = 'whitelist';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['vehicle_id', 'valid_from', 'valid_until', 'created_by'];
    protected $useTimestamps = false;

    public function getAllWithDetails()
    {
        return $this->db->table('whitelist')
            ->select('whitelist.id, whitelist.valid_from, whitelist.valid_until, whitelist.created_at,
                      member_vehicles.registration, member_vehicles.make, member_vehicles.colour, member_vehicles.model as vehicle_model,
                      member_vehicles.member_id,
                      members.first_name, members.last_name, members.unit_number,
                      users.username as created_by_name')
            ->join('member_vehicles', 'member_vehicles.id = whitelist.vehicle_id')
            ->join('members', 'members.id = member_vehicles.member_id')
            ->join('users', 'users.id = whitelist.created_by', 'left')
            ->orderBy('members.last_name', 'ASC')
            ->get()->getResultArray();
    }

    public function isWhitelisted(string $registration): array|false
    {
        $today = date('Y-m-d');
        return $this->db->table('whitelist')
            ->select('whitelist.*, member_vehicles.registration, members.first_name, members.last_name, members.unit_number')
            ->join('member_vehicles', 'member_vehicles.id = whitelist.vehicle_id')
            ->join('members', 'members.id = member_vehicles.member_id')
            ->where('member_vehicles.registration', strtoupper(trim($registration)))
            ->where('member_vehicles.is_active', 1)
            ->groupStart()
                ->where('whitelist.valid_until IS NULL')
                ->orWhere('whitelist.valid_until >=', $today)
            ->groupEnd()
            ->get()->getRowArray() ?: false;
    }
}
