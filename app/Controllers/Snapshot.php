<?php

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * Snapshot
 * Securely serves ANPR snapshot JPEGs stored in WRITEPATH (outside public/).
 * Only authenticated users can access images.
 *
 * Route: GET /anpr/snapshot?path=uploads/anpr/20260416/ABC123_1713093600.jpg
 */
class Snapshot extends Controller
{
    public function serve(): void
    {
        // Must be logged in
        if (!session()->get('isLoggedIn')) {
            $this->response->setStatusCode(403)->setBody('Forbidden')->send();
            exit;
        }

        $rel = $this->request->getGet('path');

        // Sanitise: allow only safe characters, prevent directory traversal
        if (empty($rel) || !preg_match('#^uploads/anpr/[a-zA-Z0-9/_\-]+\.jpe?g$#i', $rel)) {
            $this->response->setStatusCode(400)->setBody('Invalid path')->send();
            exit;
        }

        $full = rtrim(WRITEPATH, '/') . '/' . $rel;

        if (!is_file($full)) {
            $this->response->setStatusCode(404)->setBody('Not found')->send();
            exit;
        }

        header('Content-Type: image/jpeg');
        header('Content-Length: ' . filesize($full));
        header('Cache-Control: private, max-age=86400');
        readfile($full);
        exit;
    }
}
