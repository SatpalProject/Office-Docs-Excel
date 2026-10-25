<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Show registration page.
     */
    public function register(): string
    {
        $data = [
            'pageTitle' => 'Create your OfficeSpace Account',
            'errors'    => session()->getFlashdata('errors') ?? [],
        ];

        return view('auth/register', $data);
    }

    /**
     * Handle user registration.
     */
    public function processRegister()
    {
        $rules = [
            'name' => [
                'rules'  => 'required|min_length[2]|max_length[100]',
                'errors' => [
                    'required'   => 'Please enter your full name.',
                    'min_length' => 'Name must be at least 2 characters long.',
                ],
            ],
            'email' => [
                'rules'  => 'required|valid_email|is_unique[users.email]',
                'errors' => [
                    'required'    => 'Please enter your email address.',
                    'valid_email' => 'Please provide a valid email format.',
                    'is_unique'   => 'This email is already registered. Please sign in instead.',
                ],
            ],
            'password' => [
                'rules'  => 'required|min_length[8]',
                'errors' => [
                    'required'   => 'Please enter a password.',
                    'min_length' => 'Password must be at least 8 characters long.',
                ],
            ],
            'password_confirm' => [
                'rules'  => 'required|matches[password]',
                'errors' => [
                    'required' => 'Please confirm your password.',
                    'matches'  => 'Passwords do not match.',
                ],
            ],
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $name     = (string) $this->request->getPost('name');
        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        $userId = $this->userModel->registerUser($name, $email, $password);

        if (!$userId) {
            return redirect()->back()->withInput()->with('error', 'Failed to create your account. Please try again.');
        }

        // Regenerate session ID upon successful registration & login
        session()->regenerate();
        session()->set([
            'user_id'    => (int) $userId,
            'name'       => $name,
            'email'      => $email,
            'isLoggedIn' => true,
        ]);

        return redirect()->to(site_url('dashboard'))->with('success', 'Account created successfully! Welcome to OfficeSpace, ' . esc($name) . '.');
    }

    /**
     * Show login page.
     */
    public function login(): string
    {
        $data = [
            'pageTitle' => 'Sign In to OfficeSpace',
            'errors'    => session()->getFlashdata('errors') ?? [],
        ];

        return view('auth/login', $data);
    }

    /**
     * Handle user login.
     */
    public function processLogin()
    {
        $rules = [
            'email' => [
                'rules'  => 'required|valid_email',
                'errors' => [
                    'required'    => 'Please enter your email address.',
                    'valid_email' => 'Please provide a valid email format.',
                ],
            ],
            'password' => [
                'rules'  => 'required',
                'errors' => [
                    'required' => 'Please enter your password.',
                ],
            ],
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        $user = $this->userModel->findByEmail($email);

        if (!$user || !$this->userModel->verifyPassword($password, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid email or password.');
        }

        // Regenerate session ID upon successful authentication
        session()->regenerate();
        session()->set([
            'user_id'    => (int) $user['id'],
            'name'       => $user['name'],
            'email'      => $user['email'],
            'isLoggedIn' => true,
        ]);

        $redirectUrl = session()->get('redirect_url') ?? site_url('dashboard');
        session()->remove('redirect_url');

        return redirect()->to($redirectUrl)->with('success', 'Welcome back, ' . esc($user['name']) . '!');
    }

    /**
     * Handle user logout.
     */
    public function logout()
    {
        session()->destroy();

        return redirect()->to(site_url('login'))->with('success', 'You have been safely signed out.');
    }
}
