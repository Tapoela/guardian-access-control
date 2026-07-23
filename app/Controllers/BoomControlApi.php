<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class BoomControlApi extends Controller
{
    public function getCameras()
    {
        $gateTrigger = $this->request->getGet('gate_trigger') ?? 1;
        
        log_message('info', "getCameras: gate_trigger parameter = {$gateTrigger}");
        
        $db = \Config\Database::connect();
        
        $cameras = $db->table('cameras')
            ->select('id, name, gate_trigger')
            ->where('gate_trigger', (int)$gateTrigger)
            ->get()
            ->getResultArray();

        log_message('info', "Query result: " . json_encode($cameras));

        return $this->response->setJSON(['success' => true, 'cameras' => $cameras]);
    }

    public function trigger()
    {
        header('Content-Type: application/json');
        
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method not allowed']);
        }

        $camera_id = $this->request->getPost('camera_id');
        $action = $this->request->getPost('action');

        // TODO: Add your boom control logic here
        // For now, just return success
        
        return $this->response->setJSON([
            'success' => true, 
            'message' => "Boom " . ucfirst($action) . " triggered",
            'state' => $action
        ]);
    }
}