/**
 * The contract every block component implements, plus the small presentational
 * primitives they share.
 *
 * Block components style themselves with inline styles rather than utility
 * classes: the renderer also runs on public pages, which ship only the CSS the
 * document itself generates.
 */

import type { CSSProperties, ReactNode } from 'react';
import type { BlockData, RenderMode } from '../types';

export interface BlockComponentProps {
    block: BlockData;
    mode: RenderMode;
    /** Already-rendered child blocks — containers place them, leaves ignore them. */
    children?: ReactNode;
    /** Editable mode only: the builder's "add block" affordance for a container. */
    append?: ReactNode;
    /** Inline text editing writes back through this in editable mode. */
    onPropsChange?: (patch: Record<string, unknown>) => void;
}

export type BlockComponent = (props: BlockComponentProps) => ReactNode;

/**
 * A block the builder can drop but that only becomes real once its backing
 * module ships. Rendered as an obvious, labelled stand-in rather than silently
 * producing nothing.
 */
export function Placeholder({
    label,
    detail,
    children,
}: {
    label: string;
    detail?: string;
    children?: ReactNode;
}) {
    return (
        <div
            role="note"
            style={{
                border: '1px dashed var(--color-border-strong, #cbd5e1)',
                borderRadius: '8px',
                padding: '20px',
                background: 'var(--color-surface-sunken, #f8fafc)',
                color: 'var(--color-text-muted, #64748b)',
                fontSize: '14px',
                lineHeight: 1.5,
            }}
        >
            <strong style={{ display: 'block', color: 'var(--color-text, #0f172a)', marginBottom: '4px' }}>
                {label} — preview placeholder
            </strong>
            {detail && <span>{detail}</span>}
            {children}
        </div>
    );
}

export const stack = (gap: string): CSSProperties => ({
    display: 'flex',
    flexDirection: 'column',
    gap,
});

/** Editable text: contenteditable in the builder, a plain node on the page. */
export function EditableText({
    value,
    mode,
    onChange,
    as: Tag = 'span',
    style,
    ariaLabel,
    placeholder,
    multiline = false,
}: {
    value: string;
    mode: RenderMode;
    onChange?: (next: string) => void;
    as?: 'span' | 'div' | 'p' | 'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6' | 'strong' | 'blockquote' | 'li';
    style?: CSSProperties;
    ariaLabel?: string;
    /** Shown faintly on the canvas while the text is empty, so it stays clickable. */
    placeholder?: string;
    /** Enter inserts a line break instead of finishing the edit. */
    multiline?: boolean;
}) {
    if (mode !== 'editable' || !onChange) {
        return <Tag style={style}>{value}</Tag>;
    }

    return (
        <Tag
            style={{ ...style, outline: 'none', minWidth: '1ch' }}
            contentEditable
            suppressContentEditableWarning
            role="textbox"
            aria-label={ariaLabel ?? 'Edit text'}
            aria-multiline={multiline || undefined}
            data-fnl-inline
            data-fnl-placeholder={placeholder ?? 'Type here'}
            tabIndex={0}
            // A click inside text must place the caret, not bubble up and
            // re-render the canvas mid-edit; selection still reaches the block
            // through focus.
            onMouseDown={(event) => event.stopPropagation()}
            onClick={(event) => {
                // Prevent ancestor labels, links, or summaries from stealing focus or
                // navigating while still letting the click bubble to select the block.
                if (event.currentTarget.closest('a, label, summary')) {
                    event.preventDefault();
                }
            }}
            onBlur={(event) => {
                const raw = (multiline ? event.currentTarget.innerText : event.currentTarget.textContent) ?? '';
                const normalized = (multiline ? raw.replace(/\r?\n$/, '') : raw).replace(/\u00a0/g, ' ');
                if (normalized !== value) {
                    onChange(normalized);
                }
            }}
            onKeyDown={(event) => {
                if (event.key === 'Escape' || (event.key === 'Enter' && !multiline && !event.shiftKey)) {
                    event.preventDefault();
                    event.stopPropagation();
                    event.currentTarget.blur();
                    return;
                }
                if (event.key === ' ' && event.currentTarget.closest('button, summary')) {
                    // Ancestor <button> / <summary> elements intercept Spacebar for
                    // native activation; insert the space explicitly instead.
                    event.preventDefault();
                    event.stopPropagation();
                    document.execCommand('insertText', false, ' ');
                    return;
                }
                if (event.key === 'Enter' && !multiline && event.currentTarget.closest('button, summary')) {
                    event.preventDefault();
                }
                if (!event.ctrlKey && !event.metaKey) {
                    event.stopPropagation();
                }
            }}
            onKeyUp={(event) => {
                if (event.key === ' ' && event.currentTarget.closest('button, summary')) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            }}
            onPaste={(event) => {
                // Pasted rich text would smuggle markup into a plain-text prop.
                event.preventDefault();
                const text = event.clipboardData.getData('text/plain');
                document.execCommand('insertText', false, multiline ? text : text.replace(/\s*\n\s*/g, ' '));
            }}
        >
            {value}
        </Tag>
    );
}

/**
 * A copy of a list prop with one item patched — the shape every inline edit of
 * a repeated item (an FAQ question, a pricing plan, a nav link) writes back.
 */
export function withItem(
    items: Record<string, unknown>[],
    index: number,
    patch: Record<string, unknown>,
): Record<string, unknown>[] {
    return items.map((item, position) => (position === index ? { ...item, ...patch } : item));
}

/**
 * The "add another" affordance under a repeated list on the canvas. It exists
 * only in the builder; structure (reorder, remove) lives in the settings panel.
 */
export function CanvasAddButton({ mode, label, onAdd }: { mode: RenderMode; label: string; onAdd?: () => void }) {
    if (mode !== 'editable' || !onAdd) {
        return null;
    }

    return (
        <button
            type="button"
            data-fnl-canvas-control
            onClick={(event) => {
                event.stopPropagation();
                onAdd();
            }}
            style={{
                marginTop: '10px',
                padding: '6px 12px',
                borderRadius: '8px',
                border: '1px dashed currentColor',
                background: 'transparent',
                color: 'inherit',
                opacity: 0.6,
                font: 'inherit',
                fontSize: '13px',
                cursor: 'pointer',
            }}
        >
            + {label}
        </button>
    );
}
