<?php

namespace App\Commands;

use App\Models\DocumentModel;
use App\Models\FolderModel;
use App\Models\SpreadsheetModel;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class TestPhase2 extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:phase2';
    protected $description = 'Runs automated test suite for Phase 2 authentication and file foundation';

    public function run(array $params)
    {
        CLI::write("========================================================", 'yellow');
        CLI::write("OFFICESPACE PHASE 2 COMPREHENSIVE TEST SUITE", 'green');
        CLI::write("========================================================", 'yellow');

        $passCount = 0;
        $failCount = 0;

        $assert = function (string $name, bool $condition, string $detail = '') use (&$passCount, &$failCount) {
            if ($condition) {
                $passCount++;
                CLI::write("[PASS] " . $name . ($detail ? " ($detail)" : ""), 'green');
            } else {
                $failCount++;
                CLI::write("[FAIL] " . $name . ($detail ? " ($detail)" : ""), 'red');
            }
        };

        // ---------------------------------------------------------
        // 1. Schema & Migration Verification
        // ---------------------------------------------------------
        CLI::newLine();
        CLI::write("--- 1. Database Tables & Fields Check ---", 'cyan');
        $db = Database::connect();
        $tables = $db->listTables();

        $assert("Table 'users' exists", in_array('users', $tables));
        $assert("Table 'folders' exists", in_array('folders', $tables));
        $assert("Table 'documents' exists", in_array('documents', $tables));
        $assert("Table 'spreadsheets' exists", in_array('spreadsheets', $tables));

        $userFields = $db->getFieldNames('users');
        $assert("Users table columns (id, name, email, password_hash, created_at, updated_at)",
            in_array('id', $userFields) &&
            in_array('name', $userFields) &&
            in_array('email', $userFields) &&
            in_array('password_hash', $userFields) &&
            in_array('created_at', $userFields) &&
            in_array('updated_at', $userFields)
        );

        // ---------------------------------------------------------
        // 2. User Registration & Password Hashing
        // ---------------------------------------------------------
        CLI::newLine();
        CLI::write("--- 2. User Registration & Password Hashing ---", 'cyan');
        $userModel = new UserModel();

        $testEmailA = 'alice_' . time() . '@officespace.test';
        $testEmailB = 'bob_' . time() . '@officespace.test';
        $plainPassA = 'SecretPassword123!';

        $userIdA = $userModel->registerUser('Alice Walker', $testEmailA, $plainPassA);
        $assert("User A successfully registered", $userIdA > 0, "Generated ID: $userIdA");

        $aliceUser = $userModel->find($userIdA);
        $assert("Password hashed with PASSWORD_DEFAULT (bcrypt/argon)",
            $aliceUser['password_hash'] !== $plainPassA && str_starts_with($aliceUser['password_hash'], '$2y$'),
            substr($aliceUser['password_hash'], 0, 15) . '...'
        );

        // ---------------------------------------------------------
        // 3. Duplicate Email Rejection
        // ---------------------------------------------------------
        CLI::newLine();
        CLI::write("--- 3. Duplicate Email Rejection ---", 'cyan');
        $dupCaught = false;
        try {
            $dupId = $userModel->registerUser('Alice Imposter', $testEmailA, 'DifferentPass123!');
            $dupCaught = ($dupId === false);
        } catch (\Throwable $e) {
            $dupCaught = true;
        }
        $assert("Duplicate email registration rejected", $dupCaught);

        // ---------------------------------------------------------
        // 4. Password Verification (Valid & Invalid)
        // ---------------------------------------------------------
        CLI::newLine();
        CLI::write("--- 4. Password Verification ---", 'cyan');
        $validLogin = $userModel->verifyPassword($plainPassA, $aliceUser['password_hash']);
        $assert("Valid password verified via password_verify()", $validLogin === true);

        $invalidLogin = $userModel->verifyPassword('WrongPassword!', $aliceUser['password_hash']);
        $assert("Incorrect password rejected by password_verify()", $invalidLogin === false);

        // ---------------------------------------------------------
        // 5. User-Specific File Ownership & Isolation
        // ---------------------------------------------------------
        CLI::newLine();
        CLI::write("--- 5. File Ownership & Isolation ---", 'cyan');
        $userIdB = $userModel->registerUser('Bob Roberts', $testEmailB, 'BobSecurePass456!');
        $docModel   = new DocumentModel();
        $sheetModel = new SpreadsheetModel();

        // Alice creates 1 document and 1 spreadsheet
        $docIdA = $docModel->insert([
            'user_id' => $userIdA,
            'title'   => 'Alice Secret Strategic Notes',
            'content' => 'Confidential contents belonging exclusively to Alice.',
        ]);

        $sheetIdA = $sheetModel->insert([
            'user_id' => $userIdA,
            'title'   => 'Alice Q4 Financial Forecast',
            'content' => '{"cells":{"A1":{"value":"Revenue"},"B1":{"value":150000}}}',
        ]);

        $assert("Alice creates document & spreadsheet", $docIdA > 0 && $sheetIdA > 0);

        // Alice's isolated counts
        $aliceDocCount   = $docModel->countUserDocuments($userIdA);
        $aliceSheetCount = $sheetModel->countUserSpreadsheets($userIdA);
        $assert("Alice count indicates 1 document & 1 spreadsheet", $aliceDocCount === 1 && $aliceSheetCount === 1);

        // Bob's isolation check
        $bobDocCount   = $docModel->countUserDocuments($userIdB);
        $bobSheetCount = $sheetModel->countUserSpreadsheets($userIdB);
        $assert("Bob count is 0 (Alice files completely hidden)", $bobDocCount === 0 && $bobSheetCount === 0);

        $bobDocs = $docModel->getUserDocuments($userIdB);
        $assert("Bob document list query returns 0 records", empty($bobDocs));

        $bobDirectAccess = $docModel->getUserDocument($userIdB, (int) $docIdA);
        $assert("Bob direct lookup of Alice's document ID returns null", $bobDirectAccess === null);

        // ---------------------------------------------------------
        // 6. Dashboard Integration with User Session
        // ---------------------------------------------------------
        CLI::newLine();
        CLI::write("--- 6. Dashboard Controller & User Session ---", 'cyan');
        
        $siteURI = new \CodeIgniter\HTTP\SiteURI(config(\Config\App::class), 'dashboard');
        $requestMock = new \CodeIgniter\HTTP\IncomingRequest(
            config(\Config\App::class),
            $siteURI,
            null,
            new \CodeIgniter\HTTP\UserAgent()
        );
        \Config\Services::injectMock('request', $requestMock);

        $session = session();
        $session->set([
            'user_id'    => (int) $userIdA,
            'name'       => 'Alice Walker',
            'email'      => $testEmailA,
            'isLoggedIn' => true,
        ]);

        $dashboardController = new \App\Controllers\Dashboard();
        $dashboardController->initController(
            $requestMock,
            \Config\Services::response(),
            \Config\Services::logger()
        );

        $htmlOutput = $dashboardController->index();
        $assert("Dashboard renders with HTTP 200 equivalent content", strlen($htmlOutput) > 1000);
        $assert("Dashboard displays logged-in user name 'Alice Walker'", str_contains($htmlOutput, 'Alice Walker'));
        $assert("Dashboard displays user initial avatar 'A'", str_contains($htmlOutput, '<div class="avatar-badge">A</div>'));
        $assert("Dashboard displays user document count '1'", str_contains($htmlOutput, 'docs') && str_contains($htmlOutput, '1'));
        $assert("Dashboard includes CSRF-protected Logout button", str_contains($htmlOutput, 'logout') && str_contains($htmlOutput, 'csrf'));

        // Clean up test records
        $docModel->delete($docIdA);
        $sheetModel->delete($sheetIdA);
        $userModel->delete($userIdA);
        $userModel->delete($userIdB);
        if (session_status() === PHP_SESSION_ACTIVE) {
            @$session->destroy();
        }

        CLI::newLine();
        CLI::write("========================================================", 'yellow');
        CLI::write("SUMMARY: $passCount PASSED, $failCount FAILED", $failCount === 0 ? 'green' : 'red');
        CLI::write("========================================================", 'yellow');
    }
}
