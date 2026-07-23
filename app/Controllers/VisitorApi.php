<?php
// filepath: c:\Users\Administrator\Documents\GuardianControl\app\Controllers\VisitorApi.php

namespace App\Controllers;

class VisitorApi extends BaseController
{
    public function addVisitor()
    {
        header('Content-Type: application/json');
        
        $userId = (int) $this->request->getPost('user_id');
        $name = $this->request->getPost('name');
        $email = $this->request->getPost('email');
        $phone = $this->request->getPost('phone');
        $unit = $this->request->getPost('unit_number');
        $checkIn = $this->request->getPost('check_in'); // ISO format: 2026-06-03T14:30:00
        $durationHours = (int) $this->request->getPost('duration_hours');

        if (!$userId || !$name || !$checkIn || !$durationHours) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Missing required fields: user_id, name, check_in, duration_hours'
            ]);
        }

        try {
            // Calculate check-out time
            $checkInDt = new \DateTime($checkIn);
            $checkOutDt = clone $checkInDt;
            $checkOutDt->modify("+$durationHours hours");

            $db = \Config\Database::connect();
            
            // Get user's site_id
            $user = $db->table('users')
                ->select('site_id')
                ->where('id', $userId)
                ->get()
                ->getRowArray();

            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }

            $visitorData = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'unit_number' => $unit,
                'check_in' => $checkInDt->format('Y-m-d H:i:s'),
                'check_out' => $checkOutDt->format('Y-m-d H:i:s'),
                'duration_hours' => $durationHours,
                'added_by' => $userId,
                'site_id' => $user['site_id'],
                'status' => 'active'
            ];

            $db->table('visitors')->insert($visitorData);
            $visitorId = $db->insertID();

            log_message('info', 'Visitor added: ' . json_encode($visitorData));

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Visitor added successfully',
                'visitor_id' => $visitorId
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Visitor add error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error adding visitor: ' . $e->getMessage()
            ]);
        }
    }

    public function getVisitors()
    {
        header('Content-Type: application/json');
        
        $userId = (int) $this->request->getPost('user_id');

        if (!$userId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'user_id is required'
            ]);
        }

        $db = \Config\Database::connect();
        
        $user = $db->table('users')
            ->select('site_id')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        // Get active visitors for user's site
        $visitors = $db->table('visitors')
            ->select('id, name, email, phone, unit_number, check_in, check_out, duration_hours, status, created_at')
            ->where('site_id', $user['site_id'])
            ->where('status', 'active')
            ->orderBy('check_in', 'DESC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'success' => true,
            'visitors' => $visitors
        ]);
    }
}