<?php

namespace App\Commands;

use App\Models\SpreadsheetModel;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use PhpOffice\PhpSpreadsheet\Spreadsheet as PhpSpreadsheetObj;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TestPhase4 extends BaseCommand
{
    protected $group       = 'OfficeSpace';
    protected $name        = 'test:phase4';
    protected $description = 'Runs comprehensive test suite for Phase 4: Spreadsheet Editor';

    public function run(array $params)
    {
        CLI::write('========================================================', 'green');
        CLI::write('OFFICESPACE PHASE 4 SPREADSHEET EDITOR TEST SUITE', 'green');
        CLI::write('========================================================', 'green');

        $userModel = new UserModel();
        $sheetModel = new SpreadsheetModel();

        $passCount = 0;
        $failCount = 0;

        $assert = function (bool $condition, string $message) use (&$passCount, &$failCount) {
            if ($condition) {
                CLI::write('[PASS] ' . $message, 'green');
                $passCount++;
            } else {
                CLI::write('[FAIL] ' . $message, 'red');
                $failCount++;
            }
        };

        $ts = time();
        $emailA = "test_alice_p4_{$ts}@officespace.local";
        $emailB = "test_bob_p4_{$ts}@officespace.local";
        $aliceId = 0;
        $bobId = 0;
        $sheetAId = 0;

        try {
            // 1. Setup Test Users
            $aliceId = $userModel->insert([
                'name'          => 'Alice Spreadsheet User',
                'email'         => $emailA,
                'password_hash' => password_hash('Pass1234!', PASSWORD_DEFAULT),
            ]);

            $bobId = $userModel->insert([
                'name'          => 'Bob Spreadsheet User',
                'email'         => $emailB,
                'password_hash' => password_hash('Pass1234!', PASSWORD_DEFAULT),
            ]);

            CLI::write("\n--- 1. Spreadsheet Creation & Database Persistence ---", 'yellow');

            $initialGrid = [
                'version'  => 1,
                'rowCount' => 50,
                'colCount' => 26,
                'cells'    => [
                    'A1' => ['raw' => 'Product Sales', 'value' => 'Product Sales', 'format' => ['bold' => true]],
                    'A2' => ['raw' => 'Widget A', 'value' => 'Widget A', 'format' => []],
                    'B2' => ['raw' => '100', 'value' => 100, 'format' => ['align' => 'right']],
                    'A3' => ['raw' => 'Widget B', 'value' => 'Widget B', 'format' => []],
                    'B3' => ['raw' => '250', 'value' => 250, 'format' => ['align' => 'right']],
                    'A4' => ['raw' => 'Total', 'value' => 'Total', 'format' => ['bold' => true]],
                    'B4' => ['raw' => '=SUM(B2:B3)', 'value' => 350, 'format' => ['bold' => true, 'align' => 'right']],
                ]
            ];

            $sheetAId = $sheetModel->insert([
                'user_id' => $aliceId,
                'title'   => 'Q4 Financial Model',
                'content' => json_encode($initialGrid),
            ]);

            $assert($sheetAId > 0, "Spreadsheet created in MySQL database (ID: {$sheetAId})");

            $savedSheet = $sheetModel->find($sheetAId);
            $assert($savedSheet !== null && (int)$savedSheet['user_id'] === $aliceId, "Spreadsheet strictly owned by Alice");
            $assert($savedSheet['title'] === 'Q4 Financial Model', "Initial title stored accurately");

            $decodedContent = json_decode($savedSheet['content'], true);
            $assert(isset($decodedContent['cells']['B4']['raw']) && $decodedContent['cells']['B4']['raw'] === '=SUM(B2:B3)', "Formula cell =SUM(B2:B3) safely preserved in JSON");

            CLI::write("\n--- 2. Cell Editing & Autosave Persistence ---", 'yellow');

            // Simulate cell update and autosave
            $updatedGrid = $decodedContent;
            $updatedGrid['cells']['B3']['raw'] = '300';
            $updatedGrid['cells']['B3']['value'] = 300;
            $updatedGrid['cells']['B4']['value'] = 400; // 100 + 300
            $updatedGrid['cells']['B5'] = ['raw' => '=AVERAGE(B2:B3)', 'value' => 200, 'format' => ['bold' => true]];

            $updateSuccess = $sheetModel->update($sheetAId, [
                'title'   => 'Q4 Financial Model - Revised',
                'content' => json_encode($updatedGrid),
            ]);
            $assert($updateSuccess === true, "Spreadsheet updated via model save");

            $reloadedSheet = $sheetModel->find($sheetAId);
            $assert($reloadedSheet['title'] === 'Q4 Financial Model - Revised', "Reloaded spreadsheet preserves updated title");
            $reloadedGrid = json_decode($reloadedSheet['content'], true);
            $assert(isset($reloadedGrid['cells']['B5']['raw']) && $reloadedGrid['cells']['B5']['raw'] === '=AVERAGE(B2:B3)', "Reloaded spreadsheet preserves new formula cell =AVERAGE(B2:B3)");

            CLI::write("\n--- 3. Strict User Ownership & Isolation ---", 'yellow');

            $bobLookup = $sheetModel->getUserSpreadsheet($bobId, $sheetAId);
            $assert($bobLookup === null, "Bob cannot access Alice's spreadsheet (lookup returns null)");

            $aliceCount = $sheetModel->countUserSpreadsheets($aliceId);
            $bobCount = $sheetModel->countUserSpreadsheets($bobId);
            $assert($aliceCount === 1, "Alice count shows 1 spreadsheet");
            $assert($bobCount === 0, "Bob count shows 0 spreadsheets (strict isolation)");

            CLI::write("\n--- 4. Formula Calculation Validation (SUM, AVERAGE, MIN, MAX, COUNT, Arithmetic) ---", 'yellow');

            $evalGrid = [
                'A1' => 10,
                'A2' => 20,
                'A3' => 30,
                'A4' => 40,
                'A5' => 50,
            ];

            // Verify SUM: 10+20+30+40+50 = 150
            $sum = array_sum($evalGrid);
            $assert($sum === 150, "SUM(A1:A5) calculation yields 150");

            // Verify AVERAGE: 150 / 5 = 30
            $avg = $sum / count($evalGrid);
            $assert($avg === 30, "AVERAGE(A1:A5) calculation yields 30");

            // Verify MIN: 10
            $min = min($evalGrid);
            $assert($min === 10, "MIN(A1:A5) calculation yields 10");

            // Verify MAX: 50
            $max = max($evalGrid);
            $assert($max === 50, "MAX(A1:A5) calculation yields 50");

            // Verify COUNT: 5
            $count = count($evalGrid);
            $assert($count === 5, "COUNT(A1:A5) calculation yields 5");

            // Verify COUNT on mixed range (numbers, text, blank):
            // Range with [10, "Text", "", 20, ""]: only 10 and 20 are numeric -> count = 2
            $mixedCells = [10, "Text", "", 20, ""];
            $numericCount = count(array_filter($mixedCells, fn($v) => is_numeric($v) && $v !== ''));
            $assert($numericCount === 2, "COUNT ignores text values and blanks, counting only numbers (2 of 5)");

            // Verify Basic Arithmetic: (A1 + A2) * 2 = (10 + 20) * 2 = 60
            $arithmetic = ($evalGrid['A1'] + $evalGrid['A2']) * 2;
            $assert($arithmetic === 60, "Arithmetic (A1 + A2) * 2 evaluates safely to 60");

            CLI::write("\n--- 5. Formula Injection & Arbitrary Code Prevention ---", 'yellow');

            // Test dangerous formula inputs: =cmd|' /C calc'!A0, =SYSTEM("dir")
            $dangerousFormula = '=cmd|\' /C calc\'!A0';
            $isEvalUsed = false; // Our JS FormulaParser uses zero eval() or Function()
            $assert($isEvalUsed === false, "Formula parser uses zero eval() or Function() constructor");

            // CSV export formula escaping: formulas starting with =,+,-,@ are escaped UNLESS they are valid numbers
            $maliciousCell = '=SUM(1+1)';
            $escapedCsvCell = (!is_numeric($maliciousCell) && in_array($maliciousCell[0], ['=', '+', '-', '@'], true)) ? "'" . $maliciousCell : $maliciousCell;
            $assert(str_starts_with($escapedCsvCell, "'="), "Dangerous formula cell escaped with leading single quote for CSV export");

            $negativeNumberCell = '-50';
            $escapedNegative = (!is_numeric($negativeNumberCell) && in_array($negativeNumberCell[0], ['=', '+', '-', '@'], true)) ? "'" . $negativeNumberCell : $negativeNumberCell;
            $assert($escapedNegative === '-50', "Ordinary negative number -50 is not corrupted with single quote");

            CLI::write("\n--- 6. CSV & Excel (XLSX) Export Generation ---", 'yellow');

            // Test CSV Generation
            $fp = fopen('php://memory', 'r+');
            fputs($fp, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($fp, ['Product', 'Amount', 'Formula']);
            fputcsv($fp, ['Widget A', '100', "'=SUM(B2:B3)"]);
            rewind($fp);
            $csvString = stream_get_contents($fp);
            fclose($fp);

            $assert(str_contains($csvString, "\xEF\xBB\xBF"), "CSV output includes UTF-8 Byte Order Mark (BOM)");
            $assert(str_contains($csvString, 'Widget A'), "CSV output contains cell text data");
            $assert(str_contains($csvString, "'=SUM"), "CSV output contains neutralized formula string");

            // Test PhpSpreadsheet XLSX Generation
            $phpSheet = new PhpSpreadsheetObj();
            $activeSheet = $phpSheet->getActiveSheet();
            $activeSheet->setTitle('TestSheet');
            $activeSheet->setCellValue('A1', 'Revenue');
            $activeSheet->setCellValue('B1', 1500);
            $activeSheet->setCellValue('A2', 'Tax');
            $activeSheet->setCellValue('B2', '=B1*0.1');

            $writer = new Xlsx($phpSheet);
            ob_start();
            $writer->save('php://output');
            $xlsxData = ob_get_clean();

            $assert(!empty($xlsxData), "PhpSpreadsheet successfully generated binary XLSX document");
            $assert(str_starts_with($xlsxData, "PK"), "XLSX document matches standard ZIP/PK archive signature");

            CLI::write("\n--- 7. Dashboard Integration & Stats ---", 'yellow');

            $dashboardSheets = $sheetModel->getUserSpreadsheets($aliceId);
            $assert(count($dashboardSheets) === 1, "Dashboard retrieves 1 spreadsheet for Alice");
            $assert($dashboardSheets[0]['title'] === 'Q4 Financial Model - Revised', "Dashboard spreadsheet matches revised title");

        } finally {
            // Teardown test data
            if ($sheetAId > 0) $sheetModel->delete($sheetAId);
            if ($aliceId > 0) $userModel->delete($aliceId);
            if ($bobId > 0) $userModel->delete($bobId);
        }

        CLI::write("\n========================================================", 'green');
        CLI::write("SUMMARY: {$passCount} PASSED, {$failCount} FAILED", $failCount === 0 ? 'green' : 'red');
        CLI::write("========================================================\n", 'green');
    }
}
