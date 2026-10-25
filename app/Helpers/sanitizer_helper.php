<?php

if (!function_exists('sanitize_rich_text')) {
    /**
     * Sanitize rich-text HTML content to prevent XSS attacks while preserving
     * valid document tags (headings, paragraphs, lists, bold, italic, links, colors).
     */
    function sanitize_rich_text(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        // Strip dangerous tags completely
        $disallowedTags = ['script', 'iframe', 'object', 'embed', 'applet', 'form', 'input', 'button', 'select', 'textarea', 'style', 'link', 'meta', 'base'];
        foreach ($disallowedTags as $tag) {
            $html = preg_replace("#<{$tag}[^>]*?>.*?</{$tag}>#is", '', $html);
            $html = preg_replace("#<{$tag}[^>]*?>#is", '', $html);
        }

        // Strip inline JavaScript event handlers (e.g., onload, onclick, onerror)
        $html = preg_replace('/\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html);

        // Strip javascript: pseudoprotocol in href, src, or styles
        $html = preg_replace('/(href|src)\s*=\s*("javascript:[^"]*"|\'javascript:[^\']*\'|javascript:[^\s>]+)/i', '$1="#"', $html);

        // Strip expression() or url(javascript:...) in style attributes
        $html = preg_replace('/style\s*=\s*(".*?expression\s*\(.*?\).*?"|\'.*?expression\s*\(.*?\).*?\')/i', '', $html);
        $html = preg_replace('/style\s*=\s*(".*?javascript:.*?"|\'.*?javascript:.*?\')/i', '', $html);

        return $html;
    }
}
