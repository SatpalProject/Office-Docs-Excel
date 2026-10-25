<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentModel extends Model
{
    protected $table            = 'documents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'folder_id', 'title', 'content'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'user_id' => 'required|is_natural_no_zero',
        'title'   => 'required|min_length[1]|max_length[255]',
    ];

    /**
     * Get documents strictly scoped to an authenticated user.
     */
    public function getUserDocuments(int $userId, ?string $search = null, string $sort = 'updated_at', string $order = 'DESC'): array
    {
        $allowedSorts = ['title', 'created_at', 'updated_at'];
        $sortColumn = in_array($sort, $allowedSorts, true) ? $sort : 'updated_at';
        $orderDir = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $builder = $this->where('user_id', $userId);

        if (!empty($search)) {
            $builder->like('title', $search);
        }

        return $builder->orderBy($sortColumn, $orderDir)->findAll();
    }

    /**
     * Get a specific document strictly scoped to the owner.
     */
    public function getUserDocument(int $userId, int $docId): ?array
    {
        return $this->where('user_id', $userId)
                    ->where('id', $docId)
                    ->first();
    }

    /**
     * Count total documents for a user.
     */
    public function countUserDocuments(int $userId): int
    {
        return $this->where('user_id', $userId)->countAllResults();
    }
}
