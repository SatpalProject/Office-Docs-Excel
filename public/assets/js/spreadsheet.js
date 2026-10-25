/**
 * OfficeSpace — Google Sheets Inspired Spreadsheet Engine & Client Logic
 * Pure Vanilla JavaScript implementation with recursive-descent formula parser.
 * Zero eval(), 100% secure, offline, robust.
 */

document.addEventListener('DOMContentLoaded', function () {
    const config = window.OfficeSpaceSheetConfig || {};
    const sheetId = config.sheetId;
    const saveUrl = config.saveUrl;
    let csrfTokenName = config.csrfTokenName || document.querySelector('meta[name="csrf-name"]')?.content || 'csrf_test_name';
    let csrfHash = config.csrfHash || document.querySelector('meta[name="csrf-token"]')?.content || '';

    // Initial Grid State
    let initialData = config.initialData || {};
    let rowCount = parseInt(initialData.rowCount, 10) || 50;
    let colCount = parseInt(initialData.colCount, 10) || 26;
    let cells = initialData.cells && typeof initialData.cells === 'object' ? initialData.cells : {};

    // UI Elements
    const titleInput = document.getElementById('sheetTitleInput');
    const saveStatusBadge = document.getElementById('saveStatusBadge');
    const saveStatusText = document.getElementById('saveStatusText');
    const formulaNameBox = document.getElementById('formulaNameBox');
    const formulaBarInput = document.getElementById('formulaBarInput');
    const gridTable = document.getElementById('sheetGridTable');
    const gridWorkspace = document.getElementById('sheetWorkspace');
    const inlineEditor = document.getElementById('inlineCellEditor');
    const exportDropdownBtn = document.getElementById('exportDropdownBtn');
    const exportMenuPopup = document.getElementById('exportMenuPopup');

    // Selection Stats
    const statSum = document.getElementById('statSum');
    const statAvg = document.getElementById('statAvg');
    const statCount = document.getElementById('statCount');

    // Active Selection State
    let activeRow = 1;
    let activeCol = 1;
    let isEditing = false;
    let isDirty = false;
    let isSaving = false;
    let saveTimeout = null;
    let pendingSave = false;

    // History for Undo / Redo
    const undoStack = [];
    const redoStack = [];
    const MAX_HISTORY = 50;

    function pushHistory() {
        undoStack.push(JSON.stringify(cells));
        if (undoStack.length > MAX_HISTORY) undoStack.shift();
        redoStack.length = 0; // Clear redo
        updateUndoRedoButtons();
    }

    function updateUndoRedoButtons() {
        const btnUndo = document.getElementById('btnUndo');
        const btnRedo = document.getElementById('btnRedo');
        if (btnUndo) btnUndo.disabled = undoStack.length === 0;
        if (btnRedo) btnRedo.disabled = redoStack.length === 0;
    }

    // =========================================================================
    // Coordinate & Reference Utilities
    // =========================================================================

    function indexToColLetter(index) {
        let letters = '';
        while (index > 0) {
            const mod = (index - 1) % 26;
            letters = String.fromCharCode(65 + mod) + letters;
            index = Math.floor((index - mod) / 26);
        }
        return letters;
    }

    function colLetterToIndex(letters) {
        letters = letters.toUpperCase();
        let index = 0;
        for (let i = 0; i < letters.length; i++) {
            index = index * 26 + (letters.charCodeAt(i) - 64);
        }
        return index;
    }

    function toCoord(r, c) {
        return indexToColLetter(c) + r;
    }

    function parseCoord(coord) {
        const match = coord.toUpperCase().match(/^([A-Z]+)(\d+)$/);
        if (!match) return null;
        return {
            col: colLetterToIndex(match[1]),
            row: parseInt(match[2], 10),
            colLetter: match[1]
        };
    }

    // Expand A1:B3 into array of coordinates ['A1', 'A2', 'A3', 'B1', 'B2', 'B3']
    function expandRange(rangeStr) {
        const parts = rangeStr.toUpperCase().split(':');
        if (parts.length === 1) return [parts[0]];
        const c1 = parseCoord(parts[0]);
        const c2 = parseCoord(parts[1]);
        if (!c1 || !c2) return [];

        const minR = Math.min(c1.row, c2.row);
        const maxR = Math.max(c1.row, c2.row);
        const minC = Math.min(c1.col, c2.col);
        const maxC = Math.max(c1.col, c2.col);

        const list = [];
        for (let r = minR; r <= maxR; r++) {
            for (let c = minC; c <= maxC; c++) {
                list.push(toCoord(r, c));
            }
        }
        return list;
    }

    // =========================================================================
    // Formula Engine (Recursive-Descent Parser — Zero eval())
    // =========================================================================

    class FormulaParser {
        constructor(context) {
            this.context = context; // Callback to get cell value
        }

        evaluate(formulaStr, visitingSet = new Set()) {
            if (!formulaStr.startsWith('=')) {
                return formulaStr;
            }

            const expr = formulaStr.substring(1).trim();
            if (expr === '') return '';

            try {
                const tokens = this.tokenize(expr);
                let pos = 0;

                const parseExpression = () => {
                    let left = parseTerm();
                    while (pos < tokens.length && (tokens[pos].type === 'OP_ADD' || tokens[pos].type === 'OP_SUB')) {
                        const op = tokens[pos++].value;
                        const right = parseTerm();
                        const lNum = (typeof left === 'number') ? left : (parseFloat(left) || 0);
                        const rNum = (typeof right === 'number') ? right : (parseFloat(right) || 0);
                        left = op === '+' ? (lNum + rNum) : (lNum - rNum);
                    }
                    return left;
                };

                const parseTerm = () => {
                    let left = parseFactor();
                    while (pos < tokens.length && (tokens[pos].type === 'OP_MUL' || tokens[pos].type === 'OP_DIV')) {
                        const op = tokens[pos++].value;
                        const right = parseFactor();
                        const lNum = (typeof left === 'number') ? left : (parseFloat(left) || 0);
                        const rNum = (typeof right === 'number') ? right : (parseFloat(right) || 0);
                        if (op === '/') {
                            if (rNum === 0) throw new Error('#DIV/0!');
                            left = lNum / rNum;
                        } else {
                            left = lNum * rNum;
                        }
                    }
                    return left;
                };

                const parseFactor = () => {
                    if (pos >= tokens.length) throw new Error('#ERROR!');
                    const token = tokens[pos++];

                    if (token.type === 'NUMBER') {
                        return token.value;
                    }

                    if (token.type === 'STRING') {
                        return token.value;
                    }

                    if (token.type === 'OP_SUB') {
                        return -parseFactor();
                    }

                    if (token.type === 'OP_ADD') {
                        return parseFactor();
                    }

                    if (token.type === 'LPAREN') {
                        const val = parseExpression();
                        if (pos >= tokens.length || tokens[pos++].type !== 'RPAREN') {
                            throw new Error('#ERROR!');
                        }
                        return val;
                    }

                    if (token.type === 'FUNC') {
                        const funcName = token.value.toUpperCase();
                        if (pos >= tokens.length || tokens[pos++].type !== 'LPAREN') {
                            throw new Error('#ERROR!');
                        }

                        // Collect argument token ranges
                        const args = [];
                        if (pos < tokens.length && tokens[pos].type !== 'RPAREN') {
                            while (pos < tokens.length) {
                                // Argument can be a range token like A1:A5 or an expression
                                if (pos < tokens.length && tokens[pos].type === 'RANGE') {
                                    args.push({ type: 'range', coords: expandRange(tokens[pos++].value) });
                                } else {
                                    args.push({ type: 'val', val: parseExpression() });
                                }

                                if (pos < tokens.length && tokens[pos].type === 'COMMA') {
                                    pos++; // skip comma
                                } else {
                                    break;
                                }
                            }
                        }

                        if (pos >= tokens.length || tokens[pos++].type !== 'RPAREN') {
                            throw new Error('#ERROR!');
                        }

                        return this.executeFunction(funcName, args, visitingSet);
                    }

                    if (token.type === 'CELL') {
                        const val = this.getCellValue(token.value, visitingSet);
                        return (val === '' || val === null || val === undefined) ? 0 : val;
                    }

                    throw new Error('#ERROR!');
                };

                const result = parseExpression();
                if (pos < tokens.length) {
                    throw new Error('#ERROR!');
                }
                return typeof result === 'number' && !isNaN(result) ? Math.round(result * 1000000) / 1000000 : result;
            } catch (err) {
                return err.message && err.message.startsWith('#') ? err.message : '#ERROR!';
            }
        }

        getCellValue(coord, visitingSet) {
            const upCoord = coord.toUpperCase();
            if (visitingSet.has(upCoord)) {
                throw new Error('#REF!'); // Circular dependency
            }

            const rawVal = this.context.getRaw(upCoord);
            if (rawVal === undefined || rawVal === null || String(rawVal).trim() === '') {
                return null; // Empty cell
            }

            if (typeof rawVal === 'string' && rawVal.startsWith('=')) {
                const nextVisiting = new Set(visitingSet);
                nextVisiting.add(upCoord);
                const calc = this.evaluate(rawVal, nextVisiting);
                if (typeof calc === 'string' && calc.startsWith('#')) throw new Error(calc);
                return typeof calc === 'number' ? calc : (parseFloat(calc) || calc);
            }

            const num = parseFloat(rawVal);
            return isNaN(num) ? rawVal : num;
        }

        executeFunction(funcName, args, visitingSet) {
            // Flatten values
            const values = [];
            for (const arg of args) {
                if (arg.type === 'range') {
                    for (const c of arg.coords) {
                        const v = this.getCellValue(c, visitingSet);
                        if (typeof v === 'number' && !isNaN(v)) {
                            values.push(v);
                        } else if (typeof v === 'string' && v.trim() !== '' && !isNaN(parseFloat(v))) {
                            values.push(parseFloat(v));
                        }
                    }
                } else {
                    const v = arg.val;
                    if (typeof v === 'number' && !isNaN(v)) {
                        values.push(v);
                    } else if (typeof v === 'string' && v.trim() !== '' && !isNaN(parseFloat(v))) {
                        values.push(parseFloat(v));
                    }
                }
            }

            switch (funcName) {
                case 'SUM':
                    return values.reduce((acc, curr) => acc + curr, 0);

                case 'AVERAGE':
                    if (values.length === 0) throw new Error('#DIV/0!');
                    return values.reduce((acc, curr) => acc + curr, 0) / values.length;

                case 'MIN':
                    if (values.length === 0) return 0;
                    return Math.min(...values);

                case 'MAX':
                    if (values.length === 0) return 0;
                    return Math.max(...values);

                case 'COUNT':
                    return values.length;

                default:
                    throw new Error('#NAME?');
            }
        }

        tokenize(str) {
            const tokens = [];
            let i = 0;
            const len = str.length;

            while (i < len) {
                const ch = str[i];

                if (/\s/.test(ch)) {
                    i++;
                    continue;
                }

                if (ch === '+') { tokens.push({ type: 'OP_ADD', value: '+' }); i++; continue; }
                if (ch === '-') { tokens.push({ type: 'OP_SUB', value: '-' }); i++; continue; }
                if (ch === '*') { tokens.push({ type: 'OP_MUL', value: '*' }); i++; continue; }
                if (ch === '/') { tokens.push({ type: 'OP_DIV', value: '/' }); i++; continue; }
                if (ch === '(') { tokens.push({ type: 'LPAREN', value: '(' }); i++; continue; }
                if (ch === ')') { tokens.push({ type: 'RPAREN', value: ')' }); i++; continue; }
                if (ch === ',') { tokens.push({ type: 'COMMA', value: ',' }); i++; continue; }

                // Quoted String
                if (ch === '"' || ch === "'") {
                    const quote = ch;
                    let strVal = '';
                    i++;
                    while (i < len && str[i] !== quote) {
                        strVal += str[i++];
                    }
                    i++; // skip quote
                    tokens.push({ type: 'STRING', value: strVal });
                    continue;
                }

                // Number
                if (/[0-9]/.test(ch) || (ch === '.' && i + 1 < len && /[0-9]/.test(str[i + 1]))) {
                    let numStr = '';
                    while (i < len && (/[0-9]/.test(str[i]) || str[i] === '.')) {
                        numStr += str[i++];
                    }
                    tokens.push({ type: 'NUMBER', value: parseFloat(numStr) });
                    continue;
                }

                // Identifier: Functions, Range (A1:B5), or Cell (A1)
                if (/[A-Za-z]/.test(ch)) {
                    let ident = '';
                    while (i < len && /[A-Za-z0-9_]/.test(str[i])) {
                        ident += str[i++];
                    }

                    // Check if followed by colon ':' for range
                    if (i < len && str[i] === ':') {
                        i++; // skip ':'
                        let secondIdent = '';
                        while (i < len && /[A-Za-z0-9_]/.test(str[i])) {
                            secondIdent += str[i++];
                        }
                        tokens.push({ type: 'RANGE', value: `${ident}:${secondIdent}` });
                        continue;
                    }

                    // Check if function (followed by '(')
                    let peek = i;
                    while (peek < len && /\s/.test(str[peek])) peek++;
                    if (peek < len && str[peek] === '(') {
                        tokens.push({ type: 'FUNC', value: ident });
                        continue;
                    }

                    // Otherwise, single Cell reference
                    tokens.push({ type: 'CELL', value: ident });
                    continue;
                }

                throw new Error('#ERROR!');
            }

            return tokens;
        }
    }

    const formulaEngine = new FormulaParser({
        getRaw: (coord) => {
            const c = cells[coord];
            return c ? c.raw : '';
        }
    });

    // Recalculate all formula cells
    function recalculateGrid() {
        for (const coord in cells) {
            const cell = cells[coord];
            if (cell && typeof cell.raw === 'string' && cell.raw.startsWith('=')) {
                cell.value = formulaEngine.evaluate(cell.raw, new Set([coord]));
                const td = document.querySelector(`td[data-coord="${coord}"]`);
                if (td) {
                    td.textContent = cell.value !== undefined ? cell.value : '';
                    applyCellFormatting(td, cell.format);
                }
            }
        }
        updateSelectionStats();
    }

    // =========================================================================
    // Grid Rendering & DOM Generation
    // =========================================================================

    function buildGrid() {
        gridTable.innerHTML = '';

        // 1. Column Headers Row (Corner + A, B, C...)
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');

        const cornerTh = document.createElement('th');
        cornerTh.className = 'corner-header';
        cornerTh.title = 'Select All';
        cornerTh.addEventListener('click', () => selectCell(1, 1));
        headerRow.appendChild(cornerTh);

        for (let c = 1; c <= colCount; c++) {
            const th = document.createElement('th');
            th.className = 'col-header';
            th.dataset.col = c;
            th.textContent = indexToColLetter(c);
            headerRow.appendChild(th);
        }
        thead.appendChild(headerRow);
        gridTable.appendChild(thead);

        // 2. Data Rows (1..rowCount)
        const tbody = document.createElement('tbody');
        for (let r = 1; r <= rowCount; r++) {
            const tr = document.createElement('tr');

            // Row header index
            const rowTh = document.createElement('th');
            rowTh.className = 'row-header';
            rowTh.dataset.row = r;
            rowTh.textContent = r;
            tr.appendChild(rowTh);

            // Cells in this row
            for (let c = 1; c <= colCount; c++) {
                const coord = toCoord(r, c);
                const td = document.createElement('td');
                td.className = 'sheet-cell';
                td.dataset.row = r;
                td.dataset.col = c;
                td.dataset.coord = coord;

                const cellData = cells[coord];
                if (cellData) {
                    td.textContent = cellData.value !== undefined ? cellData.value : cellData.raw;
                    applyCellFormatting(td, cellData.format);
                }

                // Interaction
                td.addEventListener('mousedown', (e) => {
                    selectCell(r, c);
                });

                td.addEventListener('dblclick', () => {
                    startInlineEdit();
                });

                tr.appendChild(td);
            }
            tbody.appendChild(tr);
        }
        gridTable.appendChild(tbody);

        // Initial selection
        selectCell(activeRow, activeCol);
        recalculateGrid();
    }

    function applyCellFormatting(td, format) {
        if (!td || !format) return;
        td.style.fontWeight = format.bold ? '700' : 'normal';
        td.style.fontStyle = format.italic ? 'italic' : 'normal';
        td.style.textAlign = format.align || 'left';
        td.style.backgroundColor = format.bg || '';
        td.style.color = format.color || '';
    }

    // =========================================================================
    // Cell Selection & Highlight Handling
    // =========================================================================

    function selectCell(r, c, scrollTo = false) {
        if (isEditing) {
            commitInlineEdit();
        }

        r = Math.max(1, Math.min(rowCount, r));
        c = Math.max(1, Math.min(colCount, c));

        activeRow = r;
        activeCol = c;
        const coord = toCoord(r, c);

        // Remove active class from old cells
        document.querySelectorAll('.sheet-cell.active-cell').forEach(el => el.classList.remove('active-cell'));
        document.querySelectorAll('.col-header.highlight').forEach(el => el.classList.remove('highlight'));
        document.querySelectorAll('.row-header.highlight').forEach(el => el.classList.remove('highlight'));

        // Add active class to new cell
        const targetTd = document.querySelector(`td[data-coord="${coord}"]`);
        if (targetTd) {
            targetTd.classList.add('active-cell');

            if (scrollTo) {
                targetTd.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            }
        }

        // Highlight Row and Column Headers
        const colHeader = document.querySelector(`.col-header[data-col="${c}"]`);
        if (colHeader) colHeader.classList.add('highlight');
        const rowHeader = document.querySelector(`.row-header[data-row="${r}"]`);
        if (rowHeader) rowHeader.classList.add('highlight');

        // Update Formula Bar
        if (formulaNameBox) formulaNameBox.textContent = coord;
        const cellData = cells[coord];
        const rawContent = cellData ? cellData.raw : '';
        if (formulaBarInput) formulaBarInput.value = rawContent;

        updateToolbarState(cellData ? cellData.format : null);
        updateSelectionStats();
    }

    function updateToolbarState(format) {
        format = format || {};
        const btnBold = document.getElementById('btnBold');
        const btnItalic = document.getElementById('btnItalic');
        const btnAlignLeft = document.getElementById('btnAlignLeft');
        const btnAlignCenter = document.getElementById('btnAlignCenter');
        const btnAlignRight = document.getElementById('btnAlignRight');

        if (btnBold) btnBold.classList.toggle('active', !!format.bold);
        if (btnItalic) btnItalic.classList.toggle('active', !!format.italic);
        if (btnAlignLeft) btnAlignLeft.classList.toggle('active', format.align === 'left' || !format.align);
        if (btnAlignCenter) btnAlignCenter.classList.toggle('active', format.align === 'center');
        if (btnAlignRight) btnAlignRight.classList.toggle('active', format.align === 'right');
    }

    function updateSelectionStats() {
        const coord = toCoord(activeRow, activeCol);
        const cell = cells[coord];
        const val = cell ? cell.value : null;

        if (val !== null && val !== '' && !isNaN(parseFloat(val))) {
            const num = parseFloat(val);
            if (statSum) statSum.textContent = num;
            if (statAvg) statAvg.textContent = num;
            if (statCount) statCount.textContent = '1';
        } else if (val !== null && val !== '') {
            if (statSum) statSum.textContent = '-';
            if (statAvg) statAvg.textContent = '-';
            if (statCount) statCount.textContent = '1';
        } else {
            if (statSum) statSum.textContent = '0';
            if (statAvg) statAvg.textContent = '0';
            if (statCount) statCount.textContent = '0';
        }
    }

    // =========================================================================
    // Inline & Formula Bar Editing
    // =========================================================================

    function startInlineEdit(initialChar = null) {
        const coord = toCoord(activeRow, activeCol);
        const td = document.querySelector(`td[data-coord="${coord}"]`);
        if (!td || !inlineEditor) return;

        isEditing = true;
        const rect = td.getBoundingClientRect();
        const workspaceRect = gridWorkspace.getBoundingClientRect();

        inlineEditor.style.left = (td.offsetLeft) + 'px';
        inlineEditor.style.top = (td.offsetTop) + 'px';
        inlineEditor.style.width = (td.offsetWidth) + 'px';
        inlineEditor.style.minHeight = (td.offsetHeight) + 'px';
        inlineEditor.style.display = 'block';

        const cellData = cells[coord];
        const initialVal = initialChar !== null ? initialChar : (cellData ? cellData.raw : '');
        inlineEditor.value = initialVal;
        inlineEditor.focus();
        inlineEditor.setSelectionRange(inlineEditor.value.length, inlineEditor.value.length);
    }

    function commitInlineEdit() {
        if (!isEditing || !inlineEditor) return;
        isEditing = false;
        const newVal = inlineEditor.value;
        inlineEditor.style.display = 'none';

        setCellRawValue(activeRow, activeCol, newVal);
    }

    function cancelInlineEdit() {
        if (!isEditing || !inlineEditor) return;
        isEditing = false;
        inlineEditor.style.display = 'none';
        selectCell(activeRow, activeCol);
    }

    function setCellRawValue(r, c, rawVal) {
        const coord = toCoord(r, c);
        const prev = cells[coord] ? cells[coord].raw : '';
        if (prev === rawVal) return;

        pushHistory();

        if (!cells[coord]) {
            cells[coord] = { raw: '', value: '', format: {} };
        }

        cells[coord].raw = rawVal;
        if (typeof rawVal === 'string' && rawVal.startsWith('=')) {
            cells[coord].value = formulaEngine.evaluate(rawVal, new Set([coord]));
        } else {
            cells[coord].value = rawVal;
        }

        const td = document.querySelector(`td[data-coord="${coord}"]`);
        if (td) {
            td.textContent = cells[coord].value !== undefined ? cells[coord].value : '';
        }

        if (formulaBarInput && activeRow === r && activeCol === c) {
            formulaBarInput.value = rawVal;
        }

        recalculateGrid();
        isDirty = true;
        scheduleAutosave();
    }

    function applyFormatToActiveCell(formatUpdate) {
        pushHistory();
        const coord = toCoord(activeRow, activeCol);
        if (!cells[coord]) {
            cells[coord] = { raw: '', value: '', format: {} };
        }
        cells[coord].format = Object.assign({}, cells[coord].format, formatUpdate);

        const td = document.querySelector(`td[data-coord="${coord}"]`);
        if (td) {
            applyCellFormatting(td, cells[coord].format);
        }

        updateToolbarState(cells[coord].format);
        isDirty = true;
        scheduleAutosave();
    }

    // Inline Editor Keyboard Events
    if (inlineEditor) {
        inlineEditor.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                commitInlineEdit();
                selectCell(activeRow + 1, activeCol, true);
            } else if (e.key === 'Tab') {
                e.preventDefault();
                commitInlineEdit();
                selectCell(activeRow, activeCol + 1, true);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                cancelInlineEdit();
            }
        });

        inlineEditor.addEventListener('input', () => {
            if (formulaBarInput) {
                formulaBarInput.value = inlineEditor.value;
            }
        });
    }

    // Formula Bar Input Events
    if (formulaBarInput) {
        formulaBarInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                setCellRawValue(activeRow, activeCol, formulaBarInput.value);
                formulaBarInput.blur();
                selectCell(activeRow + 1, activeCol, true);
            }
        });

        formulaBarInput.addEventListener('input', () => {
            const coord = toCoord(activeRow, activeCol);
            const td = document.querySelector(`td[data-coord="${coord}"]`);
            if (td) {
                td.textContent = formulaBarInput.value;
            }
        });
    }

    // Grid Keydown Navigation & Shortcuts
    window.addEventListener('keydown', (e) => {
        // Ignore if user is inside Title Input or Export Menu
        if (e.target === titleInput || e.target === inlineEditor || e.target === formulaBarInput) {
            return;
        }

        // Undo / Redo
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
            e.preventDefault();
            if (e.shiftKey) triggerRedo();
            else triggerUndo();
            return;
        }
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'y') {
            e.preventDefault();
            triggerRedo();
            return;
        }

        // Navigation
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectCell(activeRow - 1, activeCol, true);
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectCell(activeRow + 1, activeCol, true);
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            selectCell(activeRow, activeCol - 1, true);
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            selectCell(activeRow, activeCol + 1, true);
        } else if (e.key === 'Tab') {
            e.preventDefault();
            selectCell(activeRow, activeCol + 1, true);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            startInlineEdit();
        } else if (e.key === 'Backspace' || e.key === 'Delete') {
            e.preventDefault();
            setCellRawValue(activeRow, activeCol, '');
        } else if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
            // Typing directly starts edit mode
            e.preventDefault();
            startInlineEdit(e.key);
        }
    });

    // =========================================================================
    // Undo / Redo Actions
    // =========================================================================

    function triggerUndo() {
        if (undoStack.length === 0) return;
        redoStack.push(JSON.stringify(cells));
        const prev = JSON.parse(undoStack.pop());
        cells = prev;
        buildGrid();
        updateUndoRedoButtons();
        isDirty = true;
        scheduleAutosave();
    }

    function triggerRedo() {
        if (redoStack.length === 0) return;
        undoStack.push(JSON.stringify(cells));
        const next = JSON.parse(redoStack.pop());
        cells = next;
        buildGrid();
        updateUndoRedoButtons();
        isDirty = true;
        scheduleAutosave();
    }

    // =========================================================================
    // Toolbar Actions Wiring
    // =========================================================================

    const btnUndo = document.getElementById('btnUndo');
    const btnRedo = document.getElementById('btnRedo');
    const btnBold = document.getElementById('btnBold');
    const btnItalic = document.getElementById('btnItalic');
    const btnAlignLeft = document.getElementById('btnAlignLeft');
    const btnAlignCenter = document.getElementById('btnAlignCenter');
    const btnAlignRight = document.getElementById('btnAlignRight');
    const inputTextColor = document.getElementById('inputTextColor');
    const inputBgColor = document.getElementById('inputBgColor');
    const btnClearFormat = document.getElementById('btnClearFormat');
    const btnAddRow = document.getElementById('btnAddRow');
    const btnDeleteRow = document.getElementById('btnDeleteRow');
    const btnAddCol = document.getElementById('btnAddCol');
    const btnDeleteCol = document.getElementById('btnDeleteCol');
    const formulaHelperSelect = document.getElementById('formulaHelperSelect');

    if (btnUndo) btnUndo.addEventListener('click', triggerUndo);
    if (btnRedo) btnRedo.addEventListener('click', triggerRedo);

    if (btnBold) {
        btnBold.addEventListener('click', () => {
            const coord = toCoord(activeRow, activeCol);
            const current = cells[coord]?.format?.bold;
            applyFormatToActiveCell({ bold: !current });
        });
    }

    if (btnItalic) {
        btnItalic.addEventListener('click', () => {
            const coord = toCoord(activeRow, activeCol);
            const current = cells[coord]?.format?.italic;
            applyFormatToActiveCell({ italic: !current });
        });
    }

    if (btnAlignLeft) btnAlignLeft.addEventListener('click', () => applyFormatToActiveCell({ align: 'left' }));
    if (btnAlignCenter) btnAlignCenter.addEventListener('click', () => applyFormatToActiveCell({ align: 'center' }));
    if (btnAlignRight) btnAlignRight.addEventListener('click', () => applyFormatToActiveCell({ align: 'right' }));

    if (inputTextColor) {
        inputTextColor.addEventListener('change', (e) => {
            applyFormatToActiveCell({ color: e.target.value });
        });
    }

    if (inputBgColor) {
        inputBgColor.addEventListener('change', (e) => {
            applyFormatToActiveCell({ bg: e.target.value });
        });
    }

    if (btnClearFormat) {
        btnClearFormat.addEventListener('click', () => {
            applyFormatToActiveCell({ bold: false, italic: false, align: 'left', bg: '', color: '' });
        });
    }

    // Row / Column Add & Delete
    if (btnAddRow) {
        btnAddRow.addEventListener('click', () => {
            pushHistory();
            rowCount = Math.min(500, rowCount + 1);
            buildGrid();
            selectCell(activeRow + 1, activeCol, true);
            isDirty = true;
            scheduleAutosave();
        });
    }

    if (btnDeleteRow) {
        btnDeleteRow.addEventListener('click', () => {
            if (rowCount <= 5) return;
            pushHistory();
            // Remove cells in deleted row and shift up
            const targetRow = activeRow;
            const newCells = {};
            for (const coord in cells) {
                const p = parseCoord(coord);
                if (!p) continue;
                if (p.row < targetRow) {
                    newCells[coord] = cells[coord];
                } else if (p.row > targetRow) {
                    newCells[toCoord(p.row - 1, p.col)] = cells[coord];
                }
            }
            cells = newCells;
            rowCount--;
            buildGrid();
            selectCell(Math.min(activeRow, rowCount), activeCol, true);
            isDirty = true;
            scheduleAutosave();
        });
    }

    if (btnAddCol) {
        btnAddCol.addEventListener('click', () => {
            pushHistory();
            colCount = Math.min(100, colCount + 1);
            buildGrid();
            selectCell(activeRow, activeCol + 1, true);
            isDirty = true;
            scheduleAutosave();
        });
    }

    if (btnDeleteCol) {
        btnDeleteCol.addEventListener('click', () => {
            if (colCount <= 3) return;
            pushHistory();
            const targetCol = activeCol;
            const newCells = {};
            for (const coord in cells) {
                const p = parseCoord(coord);
                if (!p) continue;
                if (p.col < targetCol) {
                    newCells[coord] = cells[coord];
                } else if (p.col > targetCol) {
                    newCells[toCoord(p.row, p.col - 1)] = cells[coord];
                }
            }
            cells = newCells;
            colCount--;
            buildGrid();
            selectCell(activeRow, Math.min(activeCol, colCount), true);
            isDirty = true;
            scheduleAutosave();
        });
    }

    // Quick Formula Helper Select
    if (formulaHelperSelect) {
        formulaHelperSelect.addEventListener('change', () => {
            const func = formulaHelperSelect.value;
            if (!func) return;
            const colLetter = indexToColLetter(activeCol);
            const defaultRange = activeRow > 1 ? `${colLetter}1:${colLetter}${activeRow - 1}` : `${colLetter}1:${colLetter}5`;
            const templateFormula = `=${func}(${defaultRange})`;

            setCellRawValue(activeRow, activeCol, templateFormula);
            formulaHelperSelect.value = '';
        });
    }

    // Title Input Change
    if (titleInput) {
        titleInput.addEventListener('input', () => {
            isDirty = true;
            scheduleAutosave();
        });

        titleInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                titleInput.blur();
                selectCell(activeRow, activeCol);
            }
        });
    }

    // Export Dropdown
    if (exportDropdownBtn && exportMenuPopup) {
        exportDropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            exportMenuPopup.classList.toggle('open');
        });

        document.addEventListener('click', () => {
            exportMenuPopup.classList.remove('open');
        });
    }

    // =========================================================================
    // Cloud Autosave Logic (1000ms debounce, mutex, CSRF handling)
    // =========================================================================

    function setSaveStatus(state, message) {
        if (!saveStatusBadge || !saveStatusText) return;

        saveStatusBadge.className = 'save-status-badge ' + state;
        if (state === 'saving') {
            saveStatusBadge.innerHTML = `
                <svg class="save-spin-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                    <path d="M12 2a10 10 0 0 1 10 10"></path>
                </svg>
                <span id="saveStatusText">${message || 'Saving...'}</span>
            `;
        } else if (state === 'saved') {
            saveStatusBadge.innerHTML = `
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span id="saveStatusText">${message || 'Saved to cloud'}</span>
            `;
        } else if (state === 'error') {
            saveStatusBadge.innerHTML = `
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span id="saveStatusText">${message || 'Save failed — Retry'}</span>
            `;
            saveStatusBadge.onclick = triggerSaveNow;
        }
    }

    function scheduleAutosave() {
        if (saveTimeout) clearTimeout(saveTimeout);
        setSaveStatus('saving', 'Saving changes...');
        saveTimeout = setTimeout(triggerSaveNow, 1000);
    }

    async function triggerSaveNow() {
        if (isSaving) {
            pendingSave = true;
            return;
        }

        const currentTitle = titleInput ? titleInput.value.trim() : 'Untitled Spreadsheet';
        const payloadData = {
            version: 1,
            rowCount: rowCount,
            colCount: colCount,
            cells: cells
        };

        isSaving = true;
        setSaveStatus('saving', 'Saving to cloud...');

        try {
            const formData = new FormData();
            formData.append('title', currentTitle || 'Untitled Spreadsheet');
            formData.append('data', JSON.stringify(payloadData));
            formData.append(csrfTokenName, csrfHash);

            const response = await fetch(saveUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfHash,
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                isDirty = false;
                if (data.csrf_hash) {
                    csrfHash = data.csrf_hash;
                    const metaToken = document.querySelector('meta[name="csrf-token"]');
                    if (metaToken) metaToken.content = data.csrf_hash;
                }
                if (data.csrf_token) {
                    csrfTokenName = data.csrf_token;
                    const metaName = document.querySelector('meta[name="csrf-name"]');
                    if (metaName) metaName.content = data.csrf_token;
                }

                setSaveStatus('saved', `Saved at ${data.updated_at || 'just now'}`);
                document.title = `${currentTitle || 'Untitled Spreadsheet'} — OfficeSpace Sheets`;
            } else {
                throw new Error(data.message || 'Server reported save error');
            }
        } catch (err) {
            console.error('Spreadsheet save error:', err);
            setSaveStatus('error', 'Save failed — Retry');
        } finally {
            isSaving = false;
            if (pendingSave) {
                pendingSave = false;
                scheduleAutosave();
            }
        }
    }

    // Warn on leaving with unsaved changes
    window.addEventListener('beforeunload', function (e) {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes in this spreadsheet.';
            return e.returnValue;
        }
    });

    // Initialize the grid!
    buildGrid();
});
