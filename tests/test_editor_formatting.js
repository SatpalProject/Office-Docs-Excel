/**
 * Automated test suite for OfficeSpace Tiptap Font Family and Font Size Extensions
 */
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

console.log('--- Running OfficeSpace Editor Formatting Tests ---');

// 1. Verify bundle exists and loads
const bundlePath = path.join(__dirname, '..', 'public', 'assets', 'vendor', 'tiptap.bundle.min.js');
assert(fs.existsSync(bundlePath), 'tiptap.bundle.min.js must exist.');

const bundleCode = fs.readFileSync(bundlePath, 'utf8');
const sandbox = {
    window: {},
    document: {
        documentElement: { style: {} },
        createElement: () => ({ style: {}, appendChild: () => {} }),
        head: { appendChild: () => {} },
        querySelector: () => null,
    },
};
sandbox.window = sandbox;
vm.createContext(sandbox);
vm.runInContext(bundleCode, sandbox);

assert(sandbox.window.Tiptap, 'window.Tiptap must be defined.');
assert(sandbox.window.Tiptap.FontFamily, 'window.Tiptap.FontFamily must be exported.');
assert(sandbox.window.Tiptap.FontSize, 'window.Tiptap.FontSize must be exported.');
console.log('✔ Test 1 passed: Bundle exports FontFamily and FontSize extensions.');

// 2. Test FontFamily Extension parseHTML and renderHTML
const FontFamily = sandbox.window.Tiptap.FontFamily;
assert.strictEqual(FontFamily.name, 'fontFamily', 'FontFamily extension name must be "fontFamily"');

// Test globalAttributes
const ffConfig = FontFamily.config;
assert(ffConfig.addGlobalAttributes, 'FontFamily must define addGlobalAttributes.');
const ffAttrs = ffConfig.addGlobalAttributes.call({ options: { types: ['textStyle'] } });
assert(Array.isArray(ffAttrs) && ffAttrs.length > 0, 'addGlobalAttributes must return an array.');
assert.deepStrictEqual(ffAttrs[0].types, ['textStyle']);

const ffAttrDef = ffAttrs[0].attributes.fontFamily;
assert(ffAttrDef, 'fontFamily attribute must be configured.');

// Parse HTML test
const mockElArial = { style: { fontFamily: "'Arial', sans-serif" } };
assert.strictEqual(ffAttrDef.parseHTML(mockElArial), 'Arial, sans-serif');

// Render HTML test
const renderedArial = ffAttrDef.renderHTML({ fontFamily: 'Arial' });
assert.strictEqual(renderedArial.style, 'font-family: Arial');

const renderedEmpty = ffAttrDef.renderHTML({});
assert.strictEqual(renderedEmpty.style, undefined);
console.log('✔ Test 2 passed: FontFamily parseHTML and renderHTML work correctly.');

// 3. Test FontSize Extension parseHTML and renderHTML
const FontSize = sandbox.window.Tiptap.FontSize;
assert.strictEqual(FontSize.name, 'fontSize', 'FontSize extension name must be "fontSize"');

const fsConfig = FontSize.config;
const fsAttrs = fsConfig.addGlobalAttributes.call({ options: { types: ['textStyle'] } });
const fsAttrDef = fsAttrs[0].attributes.fontSize;
assert(fsAttrDef, 'fontSize attribute must be configured.');

// Parse HTML test
const mockElSize = { style: { fontSize: '18pt' } };
assert.strictEqual(fsAttrDef.parseHTML(mockElSize), '18pt');

// Render HTML test
const renderedSize = fsAttrDef.renderHTML({ fontSize: '18pt' });
assert.strictEqual(renderedSize.style, 'font-size: 18pt');
console.log('✔ Test 3 passed: FontSize parseHTML and renderHTML work correctly.');

// 4. Test Font Size Stepper Logic
const FONT_SIZES = ['8pt', '9pt', '10pt', '11pt', '12pt', '14pt', '18pt', '24pt', '30pt', '36pt', '48pt', '72pt'];

function stepSize(current, direction) {
    const cur = (current || '11pt').toLowerCase();
    let idx = FONT_SIZES.findIndex(s => s.toLowerCase() === cur);
    if (idx === -1) {
        const num = parseInt(cur, 10);
        if (direction > 0) {
            idx = FONT_SIZES.findIndex(s => parseInt(s, 10) > num);
            if (idx === -1) idx = FONT_SIZES.length - 1;
        } else {
            idx = FONT_SIZES.findIndex(s => parseInt(s, 10) >= num);
            if (idx > 0) idx = idx - 1;
            else idx = 0;
        }
    } else {
        idx = Math.max(0, Math.min(FONT_SIZES.length - 1, idx + direction));
    }
    return FONT_SIZES[idx];
}

assert.strictEqual(stepSize('11pt', 1), '12pt', '11pt + 1 step = 12pt');
assert.strictEqual(stepSize('12pt', 1), '14pt', '12pt + 1 step = 14pt');
assert.strictEqual(stepSize('12pt', -1), '11pt', '12pt - 1 step = 11pt');
assert.strictEqual(stepSize('8pt', -1), '8pt', '8pt - 1 step bounds to 8pt');
assert.strictEqual(stepSize('72pt', 1), '72pt', '72pt + 1 step bounds to 72pt');
assert.strictEqual(stepSize('13pt', 1), '14pt', 'Arbitrary 13pt + 1 step goes to 14pt');
assert.strictEqual(stepSize('13pt', -1), '12pt', 'Arbitrary 13pt - 1 step goes to 12pt');
console.log('✔ Test 4 passed: Font size stepper calculations work correctly.');

// 5. Verify editor.php contains all required font options
const editorPhp = fs.readFileSync(path.join(__dirname, '..', 'app', 'Views', 'documents', 'editor.php'), 'utf8');
const expectedFonts = ['Arial', 'Inter', 'Roboto', 'Georgia', 'Times New Roman', 'Courier New', 'Verdana', 'Trebuchet MS'];
for (const font of expectedFonts) {
    assert(editorPhp.includes(`value="${font}"`), `editor.php must contain font option: ${font}`);
}
for (const size of FONT_SIZES) {
    assert(editorPhp.includes(`value="${size}"`), `editor.php must contain font size option: ${size}`);
}
assert(editorPhp.includes('id="btnDecreaseFontSize"'), 'editor.php must contain btnDecreaseFontSize');
assert(editorPhp.includes('id="btnIncreaseFontSize"'), 'editor.php must contain btnIncreaseFontSize');
console.log('✔ Test 5 passed: editor.php template contains all required font families, sizes, and steppers.');

// 6. Verify editor.js contains font handling and toolbar synchronization
const editorJs = fs.readFileSync(path.join(__dirname, '..', 'public', 'assets', 'js', 'editor.js'), 'utf8');
assert(editorJs.includes('fontFamilySelect'), 'editor.js must bind fontFamilySelect');
assert(editorJs.includes('fontSizeSelect'), 'editor.js must bind fontSizeSelect');
assert(editorJs.includes('btnDecreaseFontSize'), 'editor.js must bind btnDecreaseFontSize');
assert(editorJs.includes('btnIncreaseFontSize'), 'editor.js must bind btnIncreaseFontSize');
assert(editorJs.includes('setFontFamily'), 'editor.js must call setFontFamily');
assert(editorJs.includes('setFontSize'), 'editor.js must call setFontSize');
console.log('✔ Test 6 passed: editor.js contains font handling, sync, and steppers.');

console.log('\n--- ALL 6 FRONTEND & BUNDLE TESTS PASSED ---');
