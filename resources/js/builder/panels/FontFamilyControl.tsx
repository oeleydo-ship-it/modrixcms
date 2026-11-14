/**
 * Font family picker.
 *
 * A searchable list instead of a free-text field: typing a stack by hand was
 * error-prone (a missing quote silently fell back to the browser default) and
 * gave no way to see a font before choosing it. Each option is drawn in its own
 * face; the Google fonts for the previews are fetched once, the first time the
 * list opens, so the builder costs nothing extra until someone looks.
 *
 * The list expands inline rather than floating, because the properties panel
 * scrolls and a floating popover inside it gets clipped.
 */

import { useCallback, useEffect, useMemo, useRef, useState, type KeyboardEvent } from 'react';
import { Check, ChevronDown, Search } from 'lucide-react';
import { useOnClickOutside } from '@shared/hooks';
import { cn } from '@shared/lib/cn';
import {
    FONT_FAMILIES,
    fontOptionFor,
    valueAt,
    withBreakpoint,
    type FontFamilyOption,
} from '@renderer';
import { ControlHeader, type ControlContext } from './controls';

const PREVIEW_LINK_ID = 'fnl-font-previews';

/** One stylesheet with every Google font, regular weight only — enough to preview. */
function loadPreviews(): void {
    if (typeof document === 'undefined' || document.getElementById(PREVIEW_LINK_ID)) {
        return;
    }
    const families = FONT_FAMILIES.filter((font) => font.google)
        .map((font) => `family=${encodeURIComponent(font.google as string).replace(/%20/g, '+')}`)
        .join('&');
    const link = document.createElement('link');
    link.id = PREVIEW_LINK_ID;
    link.rel = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?${families}&display=swap`;
    document.head.appendChild(link);
}

export function FontFamilyControl({ id, label, context }: { id: string; label: string; context: ControlContext }) {
    const raw = valueAt(context.value, context.breakpoint);
    const current = typeof raw === 'string' ? raw : '';
    const selected = current === '' ? undefined : fontOptionFor(current);

    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [active, setActive] = useState(0);
    const rootRef = useRef<HTMLDivElement>(null);
    const searchRef = useRef<HTMLInputElement>(null);
    const listRef = useRef<HTMLUListElement>(null);

    const close = useCallback(() => {
        setOpen(false);
        setQuery('');
    }, []);
    useOnClickOutside([rootRef], close, open);

    // "Default" first (unless searching), then matching fonts in group order.
    const options = useMemo<(FontFamilyOption | null)[]>(() => {
        const needle = query.trim().toLowerCase();
        const matches = FONT_FAMILIES.filter((font) => needle === '' || font.label.toLowerCase().includes(needle));
        return [...(needle === '' ? [null] : []), ...matches];
    }, [query]);

    useEffect(() => {
        if (!open) {
            return;
        }
        loadPreviews();
        searchRef.current?.focus();
        const index = options.findIndex((option) => (option === null ? current === '' : option.value === selected?.value));
        setActive(Math.max(0, index));
        // Only when the list opens.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    useEffect(() => {
        if (open) {
            listRef.current?.querySelector<HTMLElement>(`[data-index="${active}"]`)?.scrollIntoView({ block: 'nearest' });
        }
    }, [active, open]);

    const choose = (option: FontFamilyOption | null): void => {
        context.onChange(withBreakpoint(context.value, context.breakpoint, option?.value ?? null));
        close();
    };

    const onKeyDown = (event: KeyboardEvent): void => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive((index) => Math.min(options.length - 1, index + 1));
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive((index) => Math.max(0, index - 1));
        } else if (event.key === 'Enter') {
            event.preventDefault();
            if (options.length > 0) {
                choose(options[active] ?? null);
            }
        } else if (event.key === 'Escape') {
            event.preventDefault();
            close();
        }
    };

    // A value typed by hand before this picker existed and not in the list is
    // shown as-is, so opening the panel never appears to have lost it.
    const buttonLabel = current === '' ? 'Default' : (selected?.label ?? current);

    return (
        <div ref={rootRef} className="flex flex-col gap-1">
            <ControlHeader id={id} label={label} context={context} />
            <button
                id={id}
                type="button"
                aria-haspopup="listbox"
                aria-expanded={open}
                onClick={() => (open ? close() : setOpen(true))}
                className={cn(
                    'flex h-8 w-full items-center justify-between gap-2 rounded-md border border-[var(--color-border-strong)]',
                    'bg-[var(--color-surface)] px-3 text-left text-[length:var(--text-sm)] text-[var(--color-text)]',
                    'focus-visible:outline-2 focus-visible:outline-[var(--color-ring)]',
                )}
            >
                <span className="truncate" style={{ fontFamily: current || undefined }}>
                    {buttonLabel}
                </span>
                <ChevronDown className="h-4 w-4 shrink-0 text-[var(--color-text-subtle)]" aria-hidden />
            </button>

            {open && (
                <div className="rounded-md border border-[var(--color-border)] bg-[var(--color-surface)] shadow-sm">
                    <div className="flex items-center gap-2 border-b border-[var(--color-border)] px-2">
                        <Search className="h-3.5 w-3.5 text-[var(--color-text-subtle)]" aria-hidden />
                        <input
                            ref={searchRef}
                            type="text"
                            value={query}
                            placeholder="Search fonts"
                            aria-label="Search fonts"
                            aria-controls={`${id}-list`}
                            onChange={(event) => {
                                setQuery(event.currentTarget.value);
                                setActive(0);
                            }}
                            onKeyDown={onKeyDown}
                            className="h-8 w-full bg-transparent text-[length:var(--text-sm)] outline-none"
                        />
                    </div>
                    <ul ref={listRef} id={`${id}-list`} role="listbox" aria-label={label} className="max-h-64 overflow-y-auto py-1">
                        {options.length === 0 && (
                            <li className="px-3 py-2 text-[length:var(--text-sm)] text-[var(--color-text-muted)]">No font matches that search.</li>
                        )}
                        {options.map((option, index) => {
                            const previous = index > 0 ? options[index - 1] : null;
                            const heading = option !== null && option.group !== previous?.group ? option.group : null;
                            const isSelected = option === null ? current === '' : option.value === selected?.value;

                            return (
                                <li key={option?.value ?? 'default'} role="presentation">
                                    {heading && (
                                        <div className="px-3 pt-2 pb-1 text-[10px] font-semibold tracking-wide text-[var(--color-text-subtle)] uppercase">
                                            {heading === 'System' ? 'Built in (no download)' : `${heading} · Google Fonts`}
                                        </div>
                                    )}
                                    <div
                                        role="option"
                                        data-index={index}
                                        aria-selected={isSelected}
                                        onPointerEnter={() => setActive(index)}
                                        onClick={() => choose(option)}
                                        className={cn(
                                            'flex cursor-pointer items-center justify-between gap-2 px-3 py-1.5 text-[length:var(--text-sm)]',
                                            index === active && 'bg-[var(--color-surface-sunken)]',
                                        )}
                                    >
                                        <span className="truncate" style={option ? { fontFamily: option.value, fontSize: '15px' } : undefined}>
                                            {option?.label ?? 'Default (inherit)'}
                                        </span>
                                        {isSelected && <Check className="h-3.5 w-3.5 shrink-0 text-[var(--color-primary)]" aria-hidden />}
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            )}
        </div>
    );
}
