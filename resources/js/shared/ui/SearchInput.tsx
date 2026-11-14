import { Search, X } from 'lucide-react';
import { useEffect, useRef, useState, type KeyboardEvent as ReactKeyboardEvent } from 'react';
import { cn } from '../lib/cn';
import { useDebouncedValue } from '../hooks/useDebouncedValue';

export interface SearchInputProps {
    value?: string;
    onValueChange: (value: string) => void;
    /** Fires on the debounced value — wire this to your query. */
    onDebouncedChange?: (value: string) => void;
    debounceMs?: number;
    placeholder?: string;
    'aria-label'?: string;
    size?: 'sm' | 'md';
    autoFocus?: boolean;
    /** Renders a ⌘K hint on the right when idle. */
    shortcutHint?: string;
    className?: string;
}

const sizes = {
    sm: 'h-8 text-[length:var(--text-sm)]',
    md: 'h-9 text-[length:var(--text-base)]',
} as const;

/**
 * Debounced search box with a clear button and Escape-to-clear. Controlled:
 * the caller owns `value`, this only adds the debounce and affordances.
 */
export function SearchInput({
    value,
    onValueChange,
    onDebouncedChange,
    debounceMs = 300,
    placeholder = 'Search…',
    size = 'md',
    autoFocus = false,
    shortcutHint,
    className,
    ...aria
}: SearchInputProps) {
    const [internal, setInternal] = useState(value ?? '');
    const current = value ?? internal;
    const debounced = useDebouncedValue(current, debounceMs);
    const inputRef = useRef<HTMLInputElement>(null);
    const lastEmitted = useRef(debounced);

    useEffect(() => {
        if (onDebouncedChange && lastEmitted.current !== debounced) {
            lastEmitted.current = debounced;
            onDebouncedChange(debounced);
        }
    }, [debounced, onDebouncedChange]);

    const update = (next: string) => {
        setInternal(next);
        onValueChange(next);
    };

    const onKeyDown = (event: ReactKeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Escape' && current !== '') {
            event.preventDefault();
            update('');
        }
    };

    return (
        <div className={cn('relative flex items-center', className)}>
            <Search
                className="pointer-events-none absolute left-2.5 h-4 w-4 text-[var(--color-text-subtle)]"
                aria-hidden
            />
            <input
                ref={inputRef}
                type="search"
                role="searchbox"
                value={current}
                autoFocus={autoFocus}
                aria-label={aria['aria-label'] ?? placeholder}
                placeholder={placeholder}
                onChange={(event) => update(event.currentTarget.value)}
                onKeyDown={onKeyDown}
                className={cn(
                    'w-full rounded-md border border-[var(--color-border-strong)] bg-[var(--color-surface)] pr-9 pl-8.5',
                    'text-[var(--color-text)] placeholder:text-[var(--color-text-subtle)]',
                    'focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-[var(--color-ring)]',
                    '[&::-webkit-search-cancel-button]:appearance-none',
                    sizes[size],
                )}
            />
            {current !== '' ? (
                <button
                    type="button"
                    aria-label="Clear search"
                    onClick={() => {
                        update('');
                        inputRef.current?.focus();
                    }}
                    className="absolute right-2 rounded p-0.5 text-[var(--color-text-subtle)] hover:text-[var(--color-text)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)]"
                >
                    <X className="h-3.5 w-3.5" aria-hidden />
                </button>
            ) : (
                shortcutHint && (
                    <kbd
                        aria-hidden
                        className="absolute right-2 rounded border border-[var(--color-border)] bg-[var(--color-surface-sunken)] px-1.5 py-0.5 font-mono text-[length:var(--text-2xs)] text-[var(--color-text-subtle)]"
                    >
                        {shortcutHint}
                    </kbd>
                )
            )}
        </div>
    );
}
