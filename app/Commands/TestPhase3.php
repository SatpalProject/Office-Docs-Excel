<?php

namespace App\Commands;

use App\Models\DocumentModel;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestPhase3 extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:phase3';
    protected $description = 'Runs comprehensive test suite for Phase 3 Document Editor';

    public function run(array $params)
    {
        CLI::write("========================================================", 'yellow');
        CLI::write("OFFICESPACE PHASE 3 DOCUMENT EDITOR TEST SUITE", 'green');
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

        $userModel = new UserModel();
        $docModel  = new DocumentModel();

        // Setup Test Users
        $emailA = 'alice_doc_' . time() . '@test.com';
        $emailB = 'bob_doc_' . time() . '@test.com';

        $userIdA = $userModel->registerUser('Alice Editor', $emailA, 'TestPass123!');
        $userIdB = $userModel->registerUser('Bob Spectator', $emailB, 'TestPass456!');

        CLI::newLine();
        CLI::write("--- 1. Document Creation & Lifecycle ---", 'cyan');

        // Test 1: User A creates a document
        $docIdA = $docModel->insert([
            'user_id' => $userIdA,
            'title'   => 'Quarterly Strategy Proposal',
            'content' => '<h1>Strategic Goals</h1><p>Expand collaborative features and autosave.</p>',
        ]);
        $assert("Document created in database", $docIdA > 0, "Document ID: $docIdA");

        $fetchedDoc = $docModel->find($docIdA);
        $assert("Document belongs to Alice", (int) $fetchedDoc['user_id'] === $userIdA);
        $assert("Initial title and rich-text content saved accurately",
            $fetchedDoc['title'] === 'Quarterly Strategy Proposal' &&
            str_contains($fetchedDoc['content'], 'Strategic Goals')
        );

        CLI::newLine();
        CLI::write("--- 2. Editing & Autosave Persistence ---", 'cyan');

        // Test 2: User A edits title and rich content
        $updatedTitle = 'Quarterly Strategy Proposal (Revised V2)';
        $updatedContent = '<h1>Strategic Goals V2</h1><p>Updated content with <strong>bold formatting</strong> and <a href="https://example.com">hyperlink</a>.</p>';

        $updateSuccess = $docModel->update($docIdA, [
            'title'   => $updatedTitle,
            'content' => $updatedContent,
        ]);
        $assert("Database record updated successfully", $updateSuccess === true);

        // Test 3: Reload document and verify persistence
        $reloadedDoc = $docModel->find($docIdA);
        $assert("Reloaded document preserves updated title", $reloadedDoc['title'] === $updatedTitle);
        $assert("Reloaded document preserves formatted HTML content",
            str_contains($reloadedDoc['content'], '<strong>bold formatting</strong>') &&
            str_contains($reloadedDoc['content'], '<a href="https://example.com">hyperlink</a>')
        );

        CLI::newLine();
        CLI::write("--- 3. Strict User Ownership & Isolation ---", 'cyan');

        // Test 4: Bob attempts to access Alice's document via model scoping
        $bobLookup = $docModel->getUserDocument($userIdB, $docIdA);
        $assert("Bob cannot access Alice's document (returns null)", $bobLookup === null);

        // Test 5: Verify Alice's count vs Bob's count
        $aliceCount = $docModel->countUserDocuments($userIdA);
        $bobCount   = $docModel->countUserDocuments($userIdB);
        $assert("Alice has 1 document, Bob has 0 documents", $aliceCount === 1 && $bobCount === 0);

        CLI::newLine();
        CLI::write("--- 4. HTML Sanitization Verification ---", 'cyan');
        helper('sanitizer');
        $maliciousPayload = '<p>Normal text</p><script>alert("XSS")</script><img src="x" onerror="stealCookies()"><a href="javascript:attack()">Click</a>';
        $cleanedHtml = sanitize_rich_text($maliciousPayload);

        $assert("Script tags stripped completely", !str_contains($cleanedHtml, '<script>'));
        $assert("Inline onerror handlers stripped", !str_contains($cleanedHtml, 'onerror'));
        $assert("javascript: pseudo-protocol neutralized", !str_contains($cleanedHtml, 'javascript:attack'));
        $assert("Legitimate paragraph preserved", str_contains($cleanedHtml, '<p>Normal text</p>'));

        CLI::newLine();
        CLI::write("--- 5. HTML & Plain Text Export Generation ---", 'cyan');
        $documentsController = new \App\Controllers\Documents();

        // Simulate Alice session
        $session = session();
        $session->set([
            'user_id'    => $userIdA,
            'name'       => 'Alice Editor',
            'email'      => $emailA,
            'isLoggedIn' => true,
        ]);

        $siteURI = new \CodeIgniter\HTTP\SiteURI(config(\Config\App::class), "documents/export/html/{$docIdA}");
        $requestMock = new \CodeIgniter\HTTP\IncomingRequest(
            config(\Config\App::class),
            $siteURI,
            null,
            new \CodeIgniter\HTTP\UserAgent()
        );
        \Config\Services::injectMock('request', $requestMock);

        $documentsController->initController(
            $requestMock,
            \Config\Services::response(),
            \Config\Services::logger()
        );

        $htmlExportResponse = $documentsController->exportHtml($docIdA);
        $htmlExportBody = $htmlExportResponse->getBody();

        $assert("HTML export generated with valid DOCTYPE", str_contains($htmlExportBody, '<!DOCTYPE html>'));
        $assert("HTML export contains document title", str_contains($htmlExportBody, $updatedTitle));
        $assert("HTML export contains formatted content", str_contains($htmlExportBody, 'bold formatting'));

        $textExportResponse = $documentsController->exportText($docIdA);
        $textExportBody = $textExportResponse->getBody();
        $assert("Plain text export strips all HTML tags", !str_contains($textExportBody, '<p>') && !str_contains($textExportBody, '<h1>'));
        $assert("Plain text export preserves title and text", str_contains($textExportBody, 'Strategic Goals V2'));

        CLI::newLine();
        CLI::write("--- 6. Dashboard Integration with Live Documents ---", 'cyan');
        $dashboardController = new \App\Controllers\Dashboard();
        $dashboardSiteURI = new \CodeIgniter\HTTP\SiteURI(config(\Config\App::class), 'dashboard');
        $dashboardRequestMock = new \CodeIgniter\HTTP\IncomingRequest(
            config(\Config\App::class),
            $dashboardSiteURI,
            null,
            new \CodeIgniter\HTTP\UserAgent()
        );
        \Config\Services::injectMock('request', $dashboardRequestMock);

        $dashboardController->initController(
            $dashboardRequestMock,
            \Config\Services::response(),
            \Config\Services::logger()
        );

        $dashboardHtml = $dashboardController->index();
        $assert("Dashboard renders document title in My Files section", str_contains($dashboardHtml, $updatedTitle));
        $assert("Dashboard links to document editor URL", str_contains($dashboardHtml, "documents/edit/{$docIdA}"));
        $assert("Dashboard displays active document count '1'", str_contains($dashboardHtml, 'docs') && str_contains($dashboardHtml, '1'));
        $assert("Dashboard contains Rename and Delete action buttons",
            str_contains($dashboardHtml, 'btn-open-rename') &&
            str_contains($dashboardHtml, 'btn-open-delete')
        );

        // Clean up test records safely
        $docModel->delete($docIdA);
        $userModel->delete($userIdA);
        $userModel->delete($userIdB);

        CLI::newLine();
        CLI::write("========================================================", 'yellow');
        CLI::write("SUMMARY: $passCount PASSED, $failCount FAILED", $failCount === 0 ? 'green' : 'red');
        CLI::write("========================================================", 'yellow');
    }
}
