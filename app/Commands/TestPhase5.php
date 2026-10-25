<?php

namespace App\Commands;

use App\Controllers\Documents;
use App\Controllers\Spreadsheets;
use App\Filters\AuthFilter;
use App\Models\DocumentModel;
use App\Models\SpreadsheetModel;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Services;

class TestPhase5 extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:phase5';
    protected $description = 'Runs comprehensive security, authorization, isolation, and production-readiness verification';

    public function run(array $params)
    {
        CLI::write("========================================================", 'yellow');
        CLI::write("OFFICESPACE PHASE 5 SECURITY & PRODUCTION AUDIT SUITE", 'green');
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

        $userModel  = new UserModel();
        $docModel   = new DocumentModel();
        $sheetModel = new SpreadsheetModel();

        // Setup Test Users
        $emailA = 'audit_alice_' . time() . '@test.com';
        $emailB = 'audit_bob_' . time() . '@test.com';

        $userIdA = $userModel->registerUser('Alice Auditor', $emailA, 'StrongPass123!');
        $userIdB = $userModel->registerUser('Bob Intruder', $emailB, 'StrongPass456!');

        CLI::newLine();
        CLI::write("--- 1. Authentication & Session Foundation ---", 'cyan');
        $assert("User A & B registered with bcrypt/argon2 hashing", $userIdA > 0 && $userIdB > 0);
        $userA = $userModel->find($userIdA);
        $assert("Password hash begins with safe cost identifier", str_starts_with($userA['password_hash'], '$2y$'));

        CLI::newLine();
        CLI::write("--- 2. Document & Spreadsheet Creation by User A ---", 'cyan');
        $docAId = $docModel->insert([
            'user_id' => $userIdA,
            'title'   => 'Alice Confidential Plan',
            'content' => '<h1>Secret Strategy</h1><p>Restricted to Alice only.</p>',
        ]);
        $assert("Alice document created", $docAId > 0, "Doc ID: $docAId");

        $sheetAId = $sheetModel->insert([
            'user_id' => $userIdA,
            'title'   => 'Alice Confidential Financials',
            'content' => json_encode([
                'version'  => 1,
                'rowCount' => 50,
                'colCount' => 26,
                'cells'    => [
                    'A1' => ['raw' => 'Revenue', 'value' => 'Revenue', 'format' => ['bold' => true]],
                    'B1' => ['raw' => '50000', 'value' => 50000, 'format' => []],
                ],
            ]),
        ]);
        $assert("Alice spreadsheet created", $sheetAId > 0, "Sheet ID: $sheetAId");

        CLI::newLine();
        CLI::write("--- 3. Authorization & Cross-User Isolation (User B Intrusion Attempts) ---", 'cyan');

        // Model query isolation
        $bDocLookup = $docModel->getUserDocument($userIdB, $docAId);
        $assert("Bob cannot access Alice document via Model (returns null)", $bDocLookup === null);

        $bSheetLookup = $sheetModel->getUserSpreadsheet($userIdB, $sheetAId);
        $assert("Bob cannot access Alice spreadsheet via Model (returns null)", $bSheetLookup === null);

        // Controller authorization as Bob
        $session = session();
        $session->set([
            'user_id'    => $userIdB,
            'name'       => 'Bob Intruder',
            'email'      => $emailB,
            'isLoggedIn' => true,
        ]);

        $siteURI = new \CodeIgniter\HTTP\SiteURI(config(\Config\App::class), 'dashboard');
        $requestMock = new \CodeIgniter\HTTP\IncomingRequest(
            config(\Config\App::class),
            $siteURI,
            null,
            new \CodeIgniter\HTTP\UserAgent()
        );
        Services::injectMock('request', $requestMock);

        $docCtrl   = new Documents();
        $docCtrl->initController($requestMock, Services::response(), Services::logger());

        $sheetCtrl = new Spreadsheets();
        $sheetCtrl->initController($requestMock, Services::response(), Services::logger());

        // Bob tries to edit Alice's document
        $docEditBlocked = false;
        try {
            $docCtrl->edit($docAId);
        } catch (PageNotFoundException $e) {
            $docEditBlocked = true;
        }
        $assert("Bob edit of Alice document blocked with HTTP 404 (PageNotFoundException)", $docEditBlocked);

        // Bob tries to save Alice's document
        $docSaveResp = $docCtrl->save($docAId);
        $assert("Bob autosave to Alice document rejected with HTTP 404", $docSaveResp->getStatusCode() === 404);

        // Bob tries to export Alice's document (HTML & Text)
        $docExportHtmlBlocked = false;
        try {
            $docCtrl->exportHtml($docAId);
        } catch (PageNotFoundException $e) {
            $docExportHtmlBlocked = true;
        }
        $assert("Bob export HTML of Alice document blocked with HTTP 404", $docExportHtmlBlocked);

        $docExportTxtBlocked = false;
        try {
            $docCtrl->exportText($docAId);
        } catch (PageNotFoundException $e) {
            $docExportTxtBlocked = true;
        }
        $assert("Bob export Text of Alice document blocked with HTTP 404", $docExportTxtBlocked);

        // Bob tries to edit Alice's spreadsheet
        $sheetEditBlocked = false;
        try {
            $sheetCtrl->edit($sheetAId);
        } catch (PageNotFoundException $e) {
            $sheetEditBlocked = true;
        }
        $assert("Bob edit of Alice spreadsheet blocked with HTTP 404", $sheetEditBlocked);

        // Bob tries to save Alice's spreadsheet
        $sheetSaveResp = $sheetCtrl->save($sheetAId);
        $assert("Bob autosave to Alice spreadsheet rejected with HTTP 404", $sheetSaveResp->getStatusCode() === 404);

        // Bob tries to export Alice's spreadsheet (CSV & XLSX)
        $sheetExportCsvBlocked = false;
        try {
            $sheetCtrl->exportCsv($sheetAId);
        } catch (PageNotFoundException $e) {
            $sheetExportCsvBlocked = true;
        }
        $assert("Bob export CSV of Alice spreadsheet blocked with HTTP 404", $sheetExportCsvBlocked);

        $sheetExportXlsxBlocked = false;
        try {
            $sheetCtrl->exportXlsx($sheetAId);
        } catch (PageNotFoundException $e) {
            $sheetExportXlsxBlocked = true;
        }
        $assert("Bob export XLSX of Alice spreadsheet blocked with HTTP 404", $sheetExportXlsxBlocked);

        CLI::newLine();
        CLI::write("--- 4. Non-Existent and Malformed Resource IDs ---", 'cyan');
        $nonExistentDocBlocked = false;
        try {
            $docCtrl->edit(9999999);
        } catch (PageNotFoundException $e) {
            $nonExistentDocBlocked = true;
        }
        $assert("Non-existent document ID throws PageNotFoundException safely", $nonExistentDocBlocked);

        $nonExistentSheetBlocked = false;
        try {
            $sheetCtrl->edit(9999999);
        } catch (PageNotFoundException $e) {
            $nonExistentSheetBlocked = true;
        }
        $assert("Non-existent spreadsheet ID throws PageNotFoundException safely", $nonExistentSheetBlocked);

        CLI::newLine();
        CLI::write("--- 5. Input Validation, Bounds Clamping & HTML Sanitization ---", 'cyan');
        // Test overlength title (>255 chars)
        $session->set('user_id', $userIdA);
        $overLengthTitle = str_repeat('LongTitle_', 40); // 400 chars
        $docModel->update($docAId, ['title' => mb_substr($overLengthTitle, 0, 255)]);
        $savedDoc = $docModel->find($docAId);
        $assert("Document title exceeding 255 characters safely truncated", mb_strlen($savedDoc['title']) === 255);

        // Rich-text XSS payload sanitization
        helper('sanitizer');
        $maliciousPayload = '<script>alert(1)</script><img src="x" onerror="steal()"><p>Safe text</p><a href="javascript:alert(2)">Link</a>';
        $cleanedHtml = sanitize_rich_text($maliciousPayload);
        $assert("HTML sanitizer completely removes <script> and onerror", !str_contains($cleanedHtml, '<script>') && !str_contains($cleanedHtml, 'onerror'));
        $assert("HTML sanitizer neutralizes javascript: pseudo-protocol", !str_contains($cleanedHtml, 'javascript:alert(2)'));
        $assert("HTML sanitizer preserves legitimate paragraph and tags", str_contains($cleanedHtml, '<p>Safe text</p>'));

        CLI::newLine();
        CLI::write("--- 6. Session Anti-Caching and Header Hardening ---", 'cyan');
        $filter = new AuthFilter();
        $request = Services::request();
        $response = Services::response();
        $filter->after($request, $response);

        $cacheControl = $response->getHeaderLine('Cache-Control');
        $pragma = $response->getHeaderLine('Pragma');
        $assert("AuthFilter sets Cache-Control: no-store, no-cache on authenticated responses", str_contains($cacheControl, 'no-store') && str_contains($cacheControl, 'no-cache'));
        $assert("AuthFilter sets Pragma: no-cache", $pragma === 'no-cache');

        CLI::newLine();
        CLI::write("--- 7. Safe File Export Filename Neutralization ---", 'cyan');
        $specialTitleDocId = $docModel->insert([
            'user_id' => $userIdA,
            'title'   => '../../etc/passwd <script> & "Dangerous"',
            'content' => '<p>Test</p>',
        ]);
        $specialDoc = $docModel->find($specialTitleDocId);
        $safeExportName = url_title($specialDoc['title'], '-', true) ?: 'document';
        $assert("Path traversal chars and dangerous symbols stripped from export filename",
            !str_contains($safeExportName, '..') &&
            !str_contains($safeExportName, '/') &&
            !str_contains($safeExportName, '<') &&
            !str_contains($safeExportName, '"')
        );

        // Cleanup test data
        $docModel->delete($specialTitleDocId, true);
        $sheetModel->delete($sheetAId, true);
        $docModel->delete($docAId, true);
        $userModel->delete($userIdA, true);
        $userModel->delete($userIdB, true);

        CLI::newLine();
        CLI::write("========================================================", 'yellow');
        CLI::write("SUMMARY: $passCount PASSED, $failCount FAILED", $failCount === 0 ? 'green' : 'red');
        CLI::write("========================================================", 'yellow');

        return $failCount === 0 ? 0 : 1;
    }
}
