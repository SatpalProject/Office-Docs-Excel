<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    /**
     * Protect routes from unauthenticated access.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->get('isLoggedIn') || !$session->get('user_id')) {
            // Save intended target URL if it is a GET request
            if ($request->getMethod() === 'GET') {
                $session->set('redirect_url', (string) current_url());
            }

            return redirect()->to(site_url('login'))->with('error', 'Please log in to access your OfficeSpace workspace.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed after request
    }
}
