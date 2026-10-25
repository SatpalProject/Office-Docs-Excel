<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class GuestFilter implements FilterInterface
{
    /**
     * Prevent authenticated users from accessing guest-only routes like login/register.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if ($session->get('isLoggedIn') && $session->get('user_id')) {
            return redirect()->to(site_url('dashboard'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed after request
    }
}
