<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'email', 'password_hash'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'name'          => 'required|min_length[2]|max_length[100]',
        'email'         => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password_hash' => 'required',
    ];

    protected $validationMessages = [
        'email' => [
            'is_unique'   => 'This email address is already registered. Please sign in or use another email.',
            'valid_email' => 'Please provide a valid email address.',
            'required'    => 'An email address is required.',
        ],
        'name' => [
            'required'   => 'Your full name is required.',
            'min_length' => 'Name must be at least 2 characters long.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Find a user by their unique email address.
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', strtolower(trim($email)))->first();
    }

    /**
     * Register a new user with secure password hashing.
     */
    public function registerUser(string $name, string $email, string $password): int|false
    {
        $data = [
            'name'          => trim($name),
            'email'         => strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ];

        return $this->insert($data);
    }

    /**
     * Verify a plain password against the stored bcrypt/argon2 hash.
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
