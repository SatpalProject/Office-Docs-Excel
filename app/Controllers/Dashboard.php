<?php

namespace App\Controllers;

use App\Models\DocumentModel;
use App\Models\SpreadsheetModel;
use App\Models\UserModel;
use Config\Database;

class Dashboard extends BaseController
{
    protected UserModel $userModel;
    protected DocumentModel $documentModel;
    protected SpreadsheetModel $spreadsheetModel;

    public function __construct()
    {
        $this->userModel        = new UserModel();
        $this->documentModel    = new DocumentModel();
        $this->spreadsheetModel = new SpreadsheetModel();
    }

    /**
     * Display the main OfficeSpace dashboard for the authenticated user.
     */
    public function index(): string
    {
        $session = session();
        $userId  = (int) $session->get('user_id');

        // Retrieve current authenticated user profile
        $userRecord = $userId > 0 ? $this->userModel->find($userId) : null;
        $userName   = $userRecord['name'] ?? $session->get('name') ?? 'OfficeSpace Member';
        $userEmail  = $userRecord['email'] ?? $session->get('email') ?? 'member@officespace.local';
        $initial    = strtoupper(mb_substr($userName, 0, 1));

        // Safe database check to determine connectivity status for dashboard badge
        $dbConnected = false;
        $dbName      = '';
        try {
            $db = Database::connect();
            $db->initialize();
            $dbConnected = true;
            $dbName      = $db->getDatabase();
        } catch (\Throwable $e) {
            $dbConnected = false;
        }

        // Fetch counts and files strictly scoped to this authenticated user
        $totalDocs   = $userId > 0 ? $this->documentModel->countUserDocuments($userId) : 0;
        $totalSheets = $userId > 0 ? $this->spreadsheetModel->countUserSpreadsheets($userId) : 0;
        $totalFiles  = $totalDocs + $totalSheets;

        // Retrieve user files (if any exist)
        $userDocs   = $userId > 0 ? $this->documentModel->getUserDocuments($userId) : [];
        $userSheets = $userId > 0 ? $this->spreadsheetModel->getUserSpreadsheets($userId) : [];

        $allFiles = [];
        foreach ($userDocs as $doc) {
            $allFiles[] = [
                'id'         => (int) $doc['id'],
                'type'       => 'document',
                'title'      => $doc['title'],
                'updated_at' => $doc['updated_at'] ?? $doc['created_at'],
            ];
        }
        foreach ($userSheets as $sheet) {
            $allFiles[] = [
                'id'         => (int) $sheet['id'],
                'type'       => 'spreadsheet',
                'title'      => $sheet['title'],
                'updated_at' => $sheet['updated_at'] ?? $sheet['created_at'],
            ];
        }

        // Sort combined files by updated_at descending
        usort($allFiles, function ($a, $b) {
            return strcmp((string)$b['updated_at'], (string)$a['updated_at']);
        });

        $data = [
            'pageTitle' => 'OfficeSpace — Cloud Documents & Spreadsheets',
            'activeNav' => 'dashboard',
            'user'      => [
                'id'           => $userId,
                'name'         => $userName,
                'email'        => $userEmail,
                'avatarLetter' => $initial ?: 'O',
                'isLoggedIn'   => (bool) $session->get('isLoggedIn'),
            ],
            'dbStatus'  => [
                'connected' => $dbConnected,
                'name'      => $dbName ?: 'officespace',
            ],
            'stats'     => [
                'totalDocuments'    => $totalDocs,
                'totalSpreadsheets' => $totalSheets,
                'totalFiles'        => $totalFiles,
                'storageUsage'      => $totalFiles > 0 ? ($totalFiles * 12) . ' KB' : '0 KB',
            ],
            'files'     => $allFiles,
            'templates' => [
                'documents' => [
                    ['id' => 'blank_doc', 'title' => 'Blank Document', 'badge' => 'New', 'desc' => 'Start writing from a clean slate'],
                    ['id' => 'meeting_notes', 'title' => 'Meeting Notes', 'badge' => 'Template', 'desc' => 'Action items, attendees, agenda'],
                    ['id' => 'project_brief', 'title' => 'Project Brief', 'badge' => 'Template', 'desc' => 'Objectives, timeline & deliverables'],
                ],
                'spreadsheets' => [
                    ['id' => 'blank_sheet', 'title' => 'Blank Sheet', 'badge' => 'New', 'desc' => 'Grid tables, calculations & data'],
                    ['id' => 'budget_planner', 'title' => 'Budget Planner', 'badge' => 'Template', 'desc' => 'Income, expenses & net savings'],
                    ['id' => 'project_tracker', 'title' => 'Task Tracker', 'badge' => 'Template', 'desc' => 'Statuses, assignees & deadlines'],
                ],
            ],
        ];

        return view('dashboard/index', $data);
    }
}
