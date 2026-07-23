<?php
namespace App\Models;

use CodeIgniter\Model;

class MemberModel extends Model
{
    protected $table      = 'members';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'first_name', 'last_name', 'unit_number',
        'phone', 'email', 'status', 'notes', 'created_by'
    ];
    protected $useTimestamps = false;

    public function getMembersWithVehicleCount()
    {
        return $this->db->table('members')
            ->select('members.*, COUNT(member_vehicles.id) as vehicle_count, users.username as created_by_name')
            ->join('member_vehicles', 'member_vehicles.member_id = members.id', 'left')
            ->join('users', 'users.id = members.created_by', 'left')
            ->groupBy('members.id')
            ->get()->getResultArray();
    }
}
