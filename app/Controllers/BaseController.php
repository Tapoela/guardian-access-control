<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    /**
     * @var array<int,string>
     */
    protected $helpers = ['url', 'form'];

    protected $session;

    protected ?int $userId = null;
    protected ?int $siteId = null;
    protected ?int $roleId = null;

    protected ?string $username = null;
    protected ?string $roleName = null;
    protected ?string $siteName = null;

    protected bool $isLoggedIn = false;
    protected bool $isSuperAdmin = false;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->session = service('session');

        $this->isLoggedIn = (bool) $this->session->get('isLoggedIn');

        if ($this->isLoggedIn) {

            $this->userId = $this->session->get('user_id');
            $this->siteId = $this->session->get('site_id');
            $this->roleId = $this->session->get('role_id');

            $this->username = $this->session->get('username');
            $this->roleName = $this->session->get('role_name');
            $this->siteName = $this->session->get('site_name');

            // Temporary
            $this->isSuperAdmin = ($this->roleName === 'Super Administrator');
        }
    }
}