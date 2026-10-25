<?php

namespace App\Models;

use CodeIgniter\Model;

class FolderModel extends Model
{
    protected $table            = 'folders';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'name'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'user_id' => 'required|is_natural_no_zero',
        'name'    => 'required|min_length[1]|max_length[150]',
    ];

    /**
     * Get all folders belonging to a specific user.
     */
    public function getUserFolders(int $userId): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }

    /**
     * Get a specific folder belonging to a specific user.
     */
    public function getUserFolder(int $userId, int $folderId): ?array
    {
        return $this->where('user_id', $userId)
                    ->where('id', $folderId)
                    ->first();
    }
}
