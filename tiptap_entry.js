import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';
import Link from '@tiptap/extension-link';
import Highlight from '@tiptap/extension-highlight';
import { Color } from '@tiptap/extension-color';
import TextStyle from '@tiptap/extension-text-style';
import CharacterCount from '@tiptap/extension-character-count';
import { PaginationPlus, PAGE_SIZES } from 'tiptap-pagination-plus';

window.Tiptap = {
    Editor,
    StarterKit,
    Underline,
    TextAlign,
    Link,
    Highlight,
    Color,
    TextStyle,
    CharacterCount,
    PaginationPlus,
    PAGE_SIZES,
};
