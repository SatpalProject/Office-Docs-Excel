<?php

namespace App\Controllers;

use App\Models\DocumentModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

class Documents extends BaseController
{
    protected DocumentModel $documentModel;

    public function __construct()
    {
        $this->documentModel = new DocumentModel();
        helper(['sanitizer', 'text']);
    }

    /**
     * Create a new document for the authenticated user and redirect to editor.
     */
    public function create()
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) {
            return redirect()->to(site_url('login'))->with('error', 'Please log in to create documents.');
        }

        $template = (string) ($this->request->getGet('template') ?? $this->request->getPost('template') ?? 'blank');

        $title = 'Untitled Document';
        $customTitle = trim((string) ($this->request->getPost('title') ?? $this->request->getGet('title') ?? ''));
        if ($customTitle !== '') {
            $title = mb_substr($customTitle, 0, 255);
        }
        $content = '';

        if ($template === 'meeting_notes' || $template === 'Meeting Notes') {
            $title = 'Meeting Notes';
            $content = '<h2>Meeting Agenda & Action Items</h2>' .
                '<p><strong>Date:</strong> ' . date('F j, Y') . ' &bull; <strong>Attendees:</strong> Team</p>' .
                '<h3>1. Objectives</h3>' .
                '<ul><li>Review weekly progress and sprint deliverables.</li><li>Identify blockers and resource dependencies.</li></ul>' .
                '<h3>2. Key Decisions</h3>' .
                '<p>Document critical consensus and strategic directions agreed upon during the discussion.</p>' .
                '<h3>3. Next Actions</h3>' .
                '<ul><li>Prepare Phase 3 deployment package.</li><li>Conduct quality assurance verification.</li></ul>';
        } elseif ($template === 'project_brief' || $template === 'Project Brief') {
            $title = 'Project Brief';
            $content = '<h1>Project Specification Brief</h1>' .
                '<p class="lead">An executive overview outlining goals, requirements, and deliverables.</p>' .
                '<h2>1. Problem Statement</h2>' .
                '<p>Describe the business challenge and opportunity space being addressed.</p>' .
                '<h2>2. Key Deliverables</h2>' .
                '<ul><li>Core document editing and formatting suite.</li><li>Real-time cloud synchronization and autosave.</li><li>Secure document isolation and exports.</li></ul>' .
                '<h2>3. Success Metrics</h2>' .
                '<p>Define target key performance indicators (KPIs) to measure completion.</p>';
        }

        $docId = $this->documentModel->insert([
            'user_id' => $userId,
            'title'   => $title,
            'content' => $content,
        ]);

        if (!$docId) {
            return redirect()->to(site_url('dashboard'))->with('error', 'Unable to create a new document. Please try again.');
        }

        return redirect()->to(site_url("documents/edit/{$docId}"))->with('success', 'New document created.');
    }

    /**
     * Open the full rich-text editor for an existing document.
     */
    public function edit(int $id): string
    {
        $userId = (int) session()->get('user_id');
        $doc = $this->documentModel->getUserDocument($userId, $id);

        if (!$doc) {
            throw PageNotFoundException::forPageNotFound('Document not found or you do not have permission to view it.');
        }

        $data = [
            'pageTitle' => esc($doc['title']) . ' — OfficeSpace Docs',
            'doc'       => $doc,
            'user'      => [
                'id'           => $userId,
                'name'         => session()->get('name') ?? 'Workspace Member',
                'email'        => session()->get('email') ?? '',
                'avatarLetter' => strtoupper(mb_substr(session()->get('name') ?? 'O', 0, 1)),
            ],
        ];

        return view('documents/editor', $data);
    }

    /**
     * Autosave endpoint for title and rich-text content via AJAX.
     */
    public function save(int $id): ResponseInterface
    {
        $userId = (int) session()->get('user_id');
        $doc = $this->documentModel->getUserDocument($userId, $id);

        if (!$doc) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Document not found or permission denied.',
            ]);
        }

        // Support both JSON body and form-urlencoded/FormData POST safely
        $json = [];
        $rawInput = (string) $this->request->getBody();
        if ($rawInput !== '') {
            $decoded = json_decode($rawInput, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $json = $decoded;
            }
        }

        $title = trim((string) ($json['title'] ?? $this->request->getPost('title') ?? ''));
        $rawContent = (string) ($json['content'] ?? $this->request->getPost('content') ?? '');

        if ($title === '') {
            $title = 'Untitled Document';
        }

        // Truncate title to 255 chars if longer
        if (mb_strlen($title) > 255) {
            $title = mb_substr($title, 0, 255);
        }

        // Sanitize rich-text content
        $sanitizedContent = sanitize_rich_text($rawContent);

        $updateSuccess = $this->documentModel->update($doc['id'], [
            'title'   => $title,
            'content' => $sanitizedContent,
        ]);

        if (!$updateSuccess) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to save changes to database.',
            ]);
        }

        $freshDoc = $this->documentModel->find($doc['id']);

        return $this->response->setJSON([
            'success'     => true,
            'message'     => 'Saved',
            'title'       => $freshDoc['title'],
            'updated_at'  => date('g:i A', strtotime($freshDoc['updated_at'] ?? 'now')),
            'csrf_token'  => csrf_token(),
            'csrf_hash'   => csrf_hash(),
        ]);
    }

    /**
     * Rename a document.
     */
    public function rename(int $id)
    {
        $userId = (int) session()->get('user_id');
        $doc = $this->documentModel->getUserDocument($userId, $id);

        if (!$doc) {
            return redirect()->to(site_url('dashboard'))->with('error', 'Document not found or access denied.');
        }

        $title = trim((string) $this->request->getPost('title'));
        if ($title === '') {
            $title = 'Untitled Document';
        }

        $this->documentModel->update($doc['id'], [
            'title' => mb_substr($title, 0, 255),
        ]);

        return redirect()->to(site_url('dashboard'))->with('success', 'Document renamed successfully.');
    }

    /**
     * Delete a document with user ownership verification.
     */
    public function delete(int $id)
    {
        $userId = (int) session()->get('user_id');
        $doc = $this->documentModel->getUserDocument($userId, $id);

        if (!$doc) {
            return redirect()->to(site_url('dashboard'))->with('error', 'Document not found or access denied.');
        }

        $this->documentModel->delete($doc['id']);

        return redirect()->to(site_url('dashboard'))->with('success', 'Document deleted successfully.');
    }

    /**
     * Export document as a clean, styled, standalone HTML file.
     */
    public function exportHtml(int $id): ResponseInterface
    {
        $userId = (int) session()->get('user_id');
        $doc = $this->documentModel->getUserDocument($userId, $id);

        if (!$doc) {
            throw PageNotFoundException::forPageNotFound('Document not found or access denied.');
        }

        $title = esc($doc['title']);
        $content = sanitize_rich_text($doc['content'] ?? '');
        $date = date('F j, Y', strtotime($doc['updated_at'] ?? 'now'));

        $safeFilename = url_title($doc['title'], '-', true) ?: 'document';
        $safeFilename .= '-' . date('Ymd') . '.html';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} — OfficeSpace Export</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.65;
            color: #1e293b;
            background-color: #f8fafc;
            margin: 0;
            padding: 40px 20px;
        }
        .paper-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 56px 64px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .doc-header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .doc-title {
            font-size: 2.25rem;
            font-weight: 800;
            margin: 0 0 8px 0;
            color: #0f172a;
        }
        .doc-meta {
            font-size: 0.875rem;
            color: #64748b;
        }
        .doc-content h1, .doc-content h2, .doc-content h3 {
            color: #0f172a;
            margin-top: 1.5em;
            margin-bottom: 0.5em;
        }
        .doc-content p {
            margin: 0.75em 0;
        }
        .doc-content a {
            color: #2563eb;
            text-decoration: underline;
        }
        .doc-content blockquote {
            border-left: 4px solid #3b82f6;
            margin: 1.5em 0;
            padding: 0.5em 1em;
            color: #475569;
            background-color: #f1f5f9;
            border-radius: 0 4px 4px 0;
        }
        .doc-content ul, .doc-content ol {
            padding-left: 1.5em;
            margin: 0.75em 0;
        }
        .doc-footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 0.8rem;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="paper-container">
        <header class="doc-header">
            <h1 class="doc-title">{$title}</h1>
            <div class="doc-meta">OfficeSpace Document &bull; Last updated: {$date}</div>
        </header>
        <article class="doc-content">
            {$content}
        </article>
        <footer class="doc-footer">
            Generated by OfficeSpace Cloud Workspace
        </footer>
    </div>
</body>
</html>
HTML;

        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $safeFilename . '"')
            ->setBody($html);
    }

    /**
     * Export document as plain text.
     */
    public function exportText(int $id): ResponseInterface
    {
        $userId = (int) session()->get('user_id');
        $doc = $this->documentModel->getUserDocument($userId, $id);

        if (!$doc) {
            throw PageNotFoundException::forPageNotFound('Document not found or access denied.');
        }

        $title = $doc['title'];
        $plainText = strip_tags(str_replace(['</p>', '</h1>', '</h2>', '</h3>', '</li>', '<br>', '<br/>'], "\n", $doc['content'] ?? ''));
        $plainText = html_entity_decode($plainText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plainText = preg_replace("/\n{3,}/", "\n\n", trim($plainText));

        $output = $title . "\n" . str_repeat('=', mb_strlen($title)) . "\n\n" . $plainText;
        $safeFilename = url_title($doc['title'], '-', true) ?: 'document';
        $safeFilename .= '-' . date('Ymd') . '.txt';

        return $this->response
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $safeFilename . '"')
            ->setBody($output);
    }
}
