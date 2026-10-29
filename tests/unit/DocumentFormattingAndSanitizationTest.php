<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class DocumentFormattingAndSanitizationTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('sanitizer');
    }

    public function testSanitizerPreservesFontFamilyAndFontSize(): void
    {
        $input = '<p><span style="font-family: Arial; font-size: 14pt; color: #2563eb;">Styled text</span></p>';
        $output = sanitize_rich_text($input);

        $this->assertStringContainsString('font-family: Arial', $output);
        $this->assertStringContainsString('font-size: 14pt', $output);
        $this->assertStringContainsString('color: #2563eb', $output);
        $this->assertStringContainsString('Styled text', $output);
    }

    public function testSanitizerPreservesAllSupportedFonts(): void
    {
        $fonts = [
            'Arial',
            'Inter',
            'Roboto',
            'Georgia',
            'Times New Roman',
            'Courier New',
            'Verdana',
            'Trebuchet MS',
        ];

        foreach ($fonts as $font) {
            $input = '<p><span style="font-family: ' . $font . ';">Sample in ' . $font . '</span></p>';
            $output = sanitize_rich_text($input);
            $this->assertStringContainsString('font-family: ' . $font, $output, "Font {$font} must be preserved by sanitizer.");
        }
    }

    public function testSanitizerPreservesAllSupportedFontSizes(): void
    {
        $sizes = ['8pt', '9pt', '10pt', '11pt', '12pt', '14pt', '18pt', '24pt', '30pt', '36pt', '48pt', '72pt'];

        foreach ($sizes as $size) {
            $input = '<p><span style="font-size: ' . $size . ';">Size ' . $size . '</span></p>';
            $output = sanitize_rich_text($input);
            $this->assertStringContainsString('font-size: ' . $size, $output, "Font size {$size} must be preserved by sanitizer.");
        }
    }

    public function testSanitizerStripsDangerousProtocolsAndTagsWithFontStyles(): void
    {
        $malicious = '<p><span style="font-family: Arial; font-size: 12pt;" onmouseover="alert(\'xss\')">Normal looking text</span><script>alert(1)</script></p>';
        $output = sanitize_rich_text($malicious);

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringNotContainsString('onmouseover', $output);
        $this->assertStringContainsString('font-family: Arial', $output);
        $this->assertStringContainsString('font-size: 12pt', $output);
        $this->assertStringContainsString('Normal looking text', $output);
    }

    public function testSanitizerStripsJavascriptInStyleAttribute(): void
    {
        $malicious = '<p><span style="font-family: Arial; javascript:alert(1)">Text</span></p>';
        $output = sanitize_rich_text($malicious);

        $this->assertStringNotContainsString('javascript:', $output);
    }
}
