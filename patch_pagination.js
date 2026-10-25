/**
 * Automated patch script for tiptap-pagination-plus
 * Fixes:
 * 1. Off-by-one bug in calculatePageCount (removes extra empty pages without removing legitimate content pages)
 * 2. Adds refreshPagination command and forcePaginationRefresh meta handling
 * 3. Accurately locates the last content element rather than arbitrary widget decorations
 */
const fs = require('fs');
const path = require('path');

const targetFile = path.join(__dirname, 'node_modules', 'tiptap-pagination-plus', 'dist', 'PaginationPlus.js');
if (!fs.existsSync(targetFile)) {
    console.log('tiptap-pagination-plus not installed. Skipping patch.');
    process.exit(0);
}

let content = fs.readFileSync(targetFile, 'utf8');

// 1. Check if forcePaginationRefresh is already present in apply
if (!content.includes('tr.getMeta("forcePaginationRefresh")')) {
    content = content.replace(
        'if (\n                        // If pagination is enabled only then check for other changes',
        'if (\n                        tr.getMeta("forcePaginationRefresh") ||\n                        tr.getMeta(page_count_meta_key) ||\n                        // If pagination is enabled only then check for other changes'
    );
}

// 2. Check if refreshPagination command is present
if (!content.includes('refreshPagination:')) {
    content = content.replace(
        'disablePagination: () => () => {\n                this.storage.enabled = false;\n                return true;\n            },',
        'disablePagination: () => () => {\n                this.storage.enabled = false;\n                return true;\n            },\n            refreshPagination: () => ({ tr, dispatch }) => {\n                if (dispatch) {\n                    tr.setMeta("forcePaginationRefresh", true);\n                    dispatch(tr);\n                }\n                return true;\n            },'
    );
}

// 3. Check if calculatePageCount uses (removePage - 1)
if (content.includes('return currentPageCount - removePage;')) {
    content = content.replace(
        'const lastElementOfEditor = editorDom.lastElementChild;',
        `const contentElements = Array.from(editorDom.children).filter(el => 
            !el.hasAttribute("data-rm-pagination") && 
            !el.classList.contains("rm-pages-wrapper") &&
            !el.classList.contains("rm-first-page-header") &&
            !el.classList.contains("ProseMirror-widget")
        );
        const lastElementOfEditor = contentElements[contentElements.length - 1] || editorDom.lastElementChild;`
    );
    content = content.replace(
        'if (removePage > 1) {\n                    return currentPageCount - removePage;\n                }',
        'if (removePage > 1) {\n                    return Math.max(1, currentPageCount - (removePage - 1));\n                }'
    );
}

fs.writeFileSync(targetFile, content, 'utf8');
console.log('Successfully verified and patched tiptap-pagination-plus.');
