<?php

namespace App\Controllers;

use App\Models\SpreadsheetModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet as PhpSpreadsheetObj;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class Spreadsheets extends BaseController
{
    protected SpreadsheetModel $spreadsheetModel;

    public function __construct()
    {
        $this->spreadsheetModel = new SpreadsheetModel();
    }

    /**
     * Create a new spreadsheet (blank or from template).
     */
    public function create()
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) {
            return redirect()->to(site_url('login'))->with('error', 'Please log in to create spreadsheets.');
        }

        $template = (string) ($this->request->getGet('template') ?? $this->request->getPost('template') ?? 'blank_sheet');
        $title = 'Untitled Spreadsheet';
        $customTitle = trim((string) ($this->request->getPost('title') ?? $this->request->getGet('title') ?? ''));
        if ($customTitle !== '') {
            $title = mb_substr($customTitle, 0, 255);
        }

        $gridData = [
            'version'  => 1,
            'rowCount' => 50,
            'colCount' => 26,
            'cells'    => [],
        ];

        if ($template === 'budget_planner' || $template === 'Budget Planner') {
            if ($customTitle === '') $title = 'Monthly Budget Planner';
            $gridData['cells'] = [
                'A1' => ['raw' => 'Monthly Budget Planner', 'value' => 'Monthly Budget Planner', 'format' => ['bold' => true, 'fontSize' => '16px', 'color' => '#1e293b']],
                'A3' => ['raw' => 'Income Category', 'value' => 'Income Category', 'format' => ['bold' => true, 'bg' => '#f1f5f9']],
                'B3' => ['raw' => 'Projected', 'value' => 'Projected', 'format' => ['bold' => true, 'align' => 'right', 'bg' => '#f1f5f9']],
                'C3' => ['raw' => 'Actual', 'value' => 'Actual', 'format' => ['bold' => true, 'align' => 'right', 'bg' => '#f1f5f9']],
                'D3' => ['raw' => 'Difference', 'value' => 'Difference', 'format' => ['bold' => true, 'align' => 'right', 'bg' => '#f1f5f9']],
                'A4' => ['raw' => 'Salary', 'value' => 'Salary', 'format' => []],
                'B4' => ['raw' => '4500', 'value' => 4500, 'format' => ['align' => 'right']],
                'C4' => ['raw' => '4650', 'value' => 4650, 'format' => ['align' => 'right']],
                'D4' => ['raw' => '=C4-B4', 'value' => 150, 'format' => ['align' => 'right', 'color' => '#16a34a']],
                'A5' => ['raw' => 'Freelance / Consulting', 'value' => 'Freelance / Consulting', 'format' => []],
                'B5' => ['raw' => '1200', 'value' => 1200, 'format' => ['align' => 'right']],
                'C5' => ['raw' => '950', 'value' => 950, 'format' => ['align' => 'right']],
                'D5' => ['raw' => '=C5-B5', 'value' => -250, 'format' => ['align' => 'right', 'color' => '#dc2626']],
                'A6' => ['raw' => 'Investments', 'value' => 'Investments', 'format' => []],
                'B6' => ['raw' => '300', 'value' => 300, 'format' => ['align' => 'right']],
                'C6' => ['raw' => '350', 'value' => 350, 'format' => ['align' => 'right']],
                'D6' => ['raw' => '=C6-B6', 'value' => 50, 'format' => ['align' => 'right', 'color' => '#16a34a']],
                'A7' => ['raw' => 'Total Income', 'value' => 'Total Income', 'format' => ['bold' => true]],
                'B7' => ['raw' => '=SUM(B4:B6)', 'value' => 6000, 'format' => ['bold' => true, 'align' => 'right']],
                'C7' => ['raw' => '=SUM(C4:C6)', 'value' => 5950, 'format' => ['bold' => true, 'align' => 'right']],
                'D7' => ['raw' => '=SUM(D4:D6)', 'value' => -50, 'format' => ['bold' => true, 'align' => 'right']],
            ];
        } elseif ($template === 'project_tracker' || $template === 'Task Tracker') {
            if ($customTitle === '') $title = 'Sprint Project Tracker';
            $gridData['cells'] = [
                'A1' => ['raw' => 'Sprint Task Tracker', 'value' => 'Sprint Task Tracker', 'format' => ['bold' => true, 'fontSize' => '16px']],
                'A3' => ['raw' => 'Task ID', 'value' => 'Task ID', 'format' => ['bold' => true, 'bg' => '#e2e8f0']],
                'B3' => ['raw' => 'Task Description', 'value' => 'Task Description', 'format' => ['bold' => true, 'bg' => '#e2e8f0']],
                'C3' => ['raw' => 'Assignee', 'value' => 'Assignee', 'format' => ['bold' => true, 'bg' => '#e2e8f0']],
                'D3' => ['raw' => 'Status', 'value' => 'Status', 'format' => ['bold' => true, 'bg' => '#e2e8f0']],
                'E3' => ['raw' => 'Story Points', 'value' => 'Story Points', 'format' => ['bold' => true, 'align' => 'right', 'bg' => '#e2e8f0']],
                'A4' => ['raw' => 'TASK-101', 'value' => 'TASK-101', 'format' => []],
                'B4' => ['raw' => 'Design Sheet Editor Toolbar', 'value' => 'Design Sheet Editor Toolbar', 'format' => []],
                'C4' => ['raw' => 'Alex', 'value' => 'Alex', 'format' => []],
                'D4' => ['raw' => 'Completed', 'value' => 'Completed', 'format' => ['color' => '#16a34a', 'bold' => true]],
                'E4' => ['raw' => '5', 'value' => 5, 'format' => ['align' => 'right']],
                'A5' => ['raw' => 'TASK-102', 'value' => 'TASK-102', 'format' => []],
                'B5' => ['raw' => 'Formula Calculation Engine', 'value' => 'Formula Calculation Engine', 'format' => []],
                'C5' => ['raw' => 'Morgan', 'value' => 'Morgan', 'format' => []],
                'D5' => ['raw' => 'In Progress', 'value' => 'In Progress', 'format' => ['color' => '#2563eb', 'bold' => true]],
                'E5' => ['raw' => '8', 'value' => 8, 'format' => ['align' => 'right']],
                'A6' => ['raw' => 'TASK-103', 'value' => 'TASK-103', 'format' => []],
                'B6' => ['raw' => 'Excel & CSV Export Handler', 'value' => 'Excel & CSV Export Handler', 'format' => []],
                'C6' => ['raw' => 'Jordan', 'value' => 'Jordan', 'format' => []],
                'D6' => ['raw' => 'Testing', 'value' => 'Testing', 'format' => ['color' => '#d97706', 'bold' => true]],
                'E6' => ['raw' => '3', 'value' => 3, 'format' => ['align' => 'right']],
                'A7' => ['raw' => 'Total Points', 'value' => 'Total Points', 'format' => ['bold' => true]],
                'E7' => ['raw' => '=SUM(E4:E6)', 'value' => 16, 'format' => ['bold' => true, 'align' => 'right']],
            ];
        }

        $sheetId = $this->spreadsheetModel->insert([
            'user_id' => $userId,
            'title'   => $title,
            'content' => json_encode($gridData, JSON_UNESCAPED_UNICODE),
        ]);

        if (!$sheetId) {
            return redirect()->to(site_url('dashboard'))->with('error', 'Unable to create spreadsheet. Please try again.');
        }

        return redirect()->to(site_url("spreadsheets/edit/{$sheetId}"));
    }

    /**
     * Display the Spreadsheet Editor view.
     */
    public function edit(int $id): string
    {
        $userId = (int) session()->get('user_id');
        $sheet = $this->spreadsheetModel->getUserSpreadsheet($userId, $id);

        if (!$sheet) {
            throw PageNotFoundException::forPageNotFound('Spreadsheet not found or you do not have permission to view it.');
        }

        // Validate content structure
        $content = [];
        if (!empty($sheet['content'])) {
            $decoded = json_decode($sheet['content'], true);
            if (is_array($decoded)) {
                $content = $decoded;
            }
        }

        if (empty($content['cells'])) {
            $content['cells'] = (object)[];
        }
        if (empty($content['rowCount'])) {
            $content['rowCount'] = 50;
        }
        if (empty($content['colCount'])) {
            $content['colCount'] = 26;
        }

        $data = [
            'pageTitle' => esc($sheet['title']) . ' — OfficeSpace Sheets',
            'sheet'     => $sheet,
            'gridData'  => $content,
            'user'      => [
                'id'           => $userId,
                'name'         => session()->get('name') ?? 'Workspace Member',
                'email'        => session()->get('email') ?? '',
                'avatarLetter' => strtoupper(mb_substr(session()->get('name') ?? 'O', 0, 1)),
            ],
        ];

        return view('spreadsheets/editor', $data);
    }

    /**
     * Autosave endpoint for spreadsheet data via AJAX.
     */
    public function save(int $id): ResponseInterface
    {
        $userId = (int) session()->get('user_id');
        $sheet = $this->spreadsheetModel->getUserSpreadsheet($userId, $id);

        if (!$sheet) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Spreadsheet not found or permission denied.',
            ]);
        }

        // Safe body parsing
        $json = [];
        $rawInput = (string) $this->request->getBody();
        if ($rawInput !== '') {
            $decoded = json_decode($rawInput, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $json = $decoded;
            }
        }

        $title = trim((string) ($json['title'] ?? $this->request->getPost('title') ?? ''));
        if ($title === '') {
            $title = 'Untitled Spreadsheet';
        }
        if (mb_strlen($title) > 255) {
            $title = mb_substr($title, 0, 255);
        }

        // Validate grid payload
        $gridPayload = $json['data'] ?? null;
        if ($gridPayload === null && $this->request->getPost('data')) {
            $gridPayload = json_decode((string) $this->request->getPost('data'), true);
        }

        if (!is_array($gridPayload)) {
            $gridPayload = [
                'version'  => 1,
                'rowCount' => 50,
                'colCount' => 26,
                'cells'    => [],
            ];
        }

        // Enforce safe structural limits
        $rowCount = max(10, min(500, (int) ($gridPayload['rowCount'] ?? 50)));
        $colCount = max(5, min(100, (int) ($gridPayload['colCount'] ?? 26)));
        $rawCells = is_array($gridPayload['cells'] ?? null) ? $gridPayload['cells'] : [];

        // Sanitize cells: coordinate check & string safety
        $sanitizedCells = [];
        foreach ($rawCells as $coord => $cellInfo) {
            if (!is_string($coord) || !preg_match('/^[A-Z]{1,3}[1-9][0-9]{0,4}$/', $coord)) {
                continue;
            }
            if (!is_array($cellInfo)) {
                continue;
            }

            $rawVal = isset($cellInfo['raw']) ? mb_substr((string) $cellInfo['raw'], 0, 2000) : '';
            $calcVal = $cellInfo['value'] ?? $rawVal;
            if (is_string($calcVal)) {
                $calcVal = mb_substr($calcVal, 0, 2000);
            }

            $format = [];
            if (!empty($cellInfo['format']) && is_array($cellInfo['format'])) {
                if (!empty($cellInfo['format']['bold'])) $format['bold'] = true;
                if (!empty($cellInfo['format']['italic'])) $format['italic'] = true;
                if (!empty($cellInfo['format']['align']) && in_array($cellInfo['format']['align'], ['left', 'center', 'right'], true)) {
                    $format['align'] = $cellInfo['format']['align'];
                }
                if (!empty($cellInfo['format']['bg']) && preg_match('/^#[0-9a-fA-F]{3,8}$/', $cellInfo['format']['bg'])) {
                    $format['bg'] = $cellInfo['format']['bg'];
                }
                if (!empty($cellInfo['format']['color']) && preg_match('/^#[0-9a-fA-F]{3,8}$/', $cellInfo['format']['color'])) {
                    $format['color'] = $cellInfo['format']['color'];
                }
            }

            $sanitizedCells[$coord] = [
                'raw'    => $rawVal,
                'value'  => $calcVal,
                'format' => $format,
            ];
        }

        $validatedData = [
            'version'  => 1,
            'rowCount' => $rowCount,
            'colCount' => $colCount,
            'cells'    => $sanitizedCells,
        ];

        $encodedContent = json_encode($validatedData, JSON_UNESCAPED_UNICODE);

        $updateSuccess = $this->spreadsheetModel->update($sheet['id'], [
            'title'   => $title,
            'content' => $encodedContent,
        ]);

        if (!$updateSuccess) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to save spreadsheet to database.',
            ]);
        }

        $freshSheet = $this->spreadsheetModel->find($sheet['id']);

        return $this->response->setJSON([
            'success'    => true,
            'message'    => 'Saved',
            'title'      => $freshSheet['title'],
            'updated_at' => date('g:i A', strtotime($freshSheet['updated_at'] ?? 'now')),
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    /**
     * Rename a spreadsheet.
     */
    public function rename(int $id)
    {
        $userId = (int) session()->get('user_id');
        $sheet = $this->spreadsheetModel->getUserSpreadsheet($userId, $id);

        if (!$sheet) {
            return redirect()->to(site_url('dashboard'))->with('error', 'Spreadsheet not found or access denied.');
        }

        $title = trim((string) $this->request->getPost('title'));
        if ($title === '') {
            $title = 'Untitled Spreadsheet';
        }
        if (mb_strlen($title) > 255) {
            $title = mb_substr($title, 0, 255);
        }

        $this->spreadsheetModel->update($sheet['id'], ['title' => $title]);

        return redirect()->to(site_url('dashboard'))->with('success', 'Spreadsheet renamed successfully.');
    }

    /**
     * Delete a spreadsheet.
     */
    public function delete(int $id)
    {
        $userId = (int) session()->get('user_id');
        $sheet = $this->spreadsheetModel->getUserSpreadsheet($userId, $id);

        if (!$sheet) {
            return redirect()->to(site_url('dashboard'))->with('error', 'Spreadsheet not found or access denied.');
        }

        $this->spreadsheetModel->delete($sheet['id']);

        return redirect()->to(site_url('dashboard'))->with('success', 'Spreadsheet deleted successfully.');
    }

    /**
     * Export spreadsheet to CSV format with formula injection protection.
     */
    public function exportCsv(int $id): ResponseInterface
    {
        $userId = (int) session()->get('user_id');
        $sheet = $this->spreadsheetModel->getUserSpreadsheet($userId, $id);

        if (!$sheet) {
            throw PageNotFoundException::forPageNotFound('Spreadsheet not found or access denied.');
        }

        $gridData = !empty($sheet['content']) ? json_decode($sheet['content'], true) : [];
        $cells = is_array($gridData['cells'] ?? null) ? $gridData['cells'] : [];
        $rowCount = (int) ($gridData['rowCount'] ?? 50);
        $colCount = (int) ($gridData['colCount'] ?? 26);

        // Find max populated row and column to keep CSV clean
        $maxRow = 1;
        $maxCol = 1;
        foreach (array_keys($cells) as $coord) {
            if (preg_match('/^([A-Z]+)(\d+)$/', $coord, $m)) {
                $colIndex = $this->colLettersToIndex($m[1]);
                $rowIndex = (int) $m[2];
                if ($colIndex > $maxCol) $maxCol = $colIndex;
                if ($rowIndex > $maxRow) $maxRow = $rowIndex;
            }
        }
        $maxCol = min($maxCol, $colCount);
        $maxRow = min($maxRow, $rowCount);

        $fp = fopen('php://memory', 'r+');
        // UTF-8 Byte Order Mark for Excel compatibility
        fputs($fp, "\xEF\xBB\xBF");

        for ($r = 1; $r <= $maxRow; $r++) {
            $rowValues = [];
            for ($c = 1; $c <= $maxCol; $c++) {
                $coord = $this->indexToColLetters($c) . $r;
                $val = '';
                if (isset($cells[$coord])) {
                    $val = (string) ($cells[$coord]['value'] ?? $cells[$coord]['raw'] ?? '');
                }

                // Prevent CSV Formula Injection while preserving valid numbers (e.g. -50, +10)
                if ($val !== '' && !is_numeric($val) && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                    $val = "'" . $val;
                }

                $rowValues[] = $val;
            }
            fputcsv($fp, $rowValues);
        }

        rewind($fp);
        $csvContent = stream_get_contents($fp);
        fclose($fp);

        $safeFilename = url_title($sheet['title'], '-', true) ?: 'spreadsheet';
        $safeFilename .= '-' . date('Ymd') . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $safeFilename . '"')
            ->setBody($csvContent);
    }

    /**
     * Export spreadsheet to XLSX format using PhpSpreadsheet.
     */
    public function exportXlsx(int $id): ResponseInterface
    {
        $userId = (int) session()->get('user_id');
        $sheet = $this->spreadsheetModel->getUserSpreadsheet($userId, $id);

        if (!$sheet) {
            throw PageNotFoundException::forPageNotFound('Spreadsheet not found or access denied.');
        }

        $gridData = !empty($sheet['content']) ? json_decode($sheet['content'], true) : [];
        $cells = is_array($gridData['cells'] ?? null) ? $gridData['cells'] : [];

        $phpSpreadsheet = new PhpSpreadsheetObj();
        $activeSheet = $phpSpreadsheet->getActiveSheet();
        $activeSheet->setTitle(mb_substr($sheet['title'], 0, 30));

        foreach ($cells as $coord => $cellInfo) {
            $raw = (string) ($cellInfo['raw'] ?? '');
            $val = $cellInfo['value'] ?? $raw;
            $format = is_array($cellInfo['format'] ?? null) ? $cellInfo['format'] : [];

            // If raw is a formula (e.g. =SUM(A1:A5)), set formula, otherwise set calculated or string value
            if ($raw !== '' && $raw[0] === '=') {
                $activeSheet->setCellValue($coord, $raw);
            } else {
                if (is_numeric($val)) {
                    $activeSheet->setCellValue($coord, (float) $val);
                } else {
                    $activeSheet->setCellValue($coord, (string) $val);
                }
            }

            // Formatting
            $style = $activeSheet->getStyle($coord);
            if (!empty($format['bold'])) {
                $style->getFont()->setBold(true);
            }
            if (!empty($format['italic'])) {
                $style->getFont()->setItalic(true);
            }
            if (!empty($format['color'])) {
                $hexColor = ltrim($format['color'], '#');
                if (strlen($hexColor) === 6) {
                    $style->getFont()->getColor()->setRGB($hexColor);
                }
            }
            if (!empty($format['align'])) {
                if ($format['align'] === 'right') {
                    $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } elseif ($format['align'] === 'center') {
                    $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                } else {
                    $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
            }
            if (!empty($format['bg'])) {
                $hexBg = ltrim($format['bg'], '#');
                if (strlen($hexBg) === 6) {
                    $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($hexBg);
                }
            }
        }

        // Auto-size populated columns
        foreach (range('A', 'Z') as $colLetter) {
            $activeSheet->getColumnDimension($colLetter)->setWidth(14);
        }

        $writer = new Xlsx($phpSpreadsheet);
        ob_start();
        $writer->save('php://output');
        $xlsxContent = ob_get_clean();

        $safeFilename = url_title($sheet['title'], '-', true) ?: 'spreadsheet';
        $safeFilename .= '-' . date('Ymd') . '.xlsx';

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $safeFilename . '"')
            ->setBody($xlsxContent);
    }

    /**
     * Convert 1-based column index to letter string (1 -> A, 26 -> Z, 27 -> AA).
     */
    protected function indexToColLetters(int $index): string
    {
        $letters = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $index = (int) (($index - $mod) / 26);
        }
        return $letters;
    }

    /**
     * Convert letter string to 1-based column index (A -> 1, Z -> 26, AA -> 27).
     */
    protected function colLettersToIndex(string $letters): int
    {
        $index = 0;
        $letters = strtoupper($letters);
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return $index;
    }
}
