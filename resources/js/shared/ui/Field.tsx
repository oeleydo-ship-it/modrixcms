import type { ReactNode } from 'react';
import { cn } from '../lib/cn';

export interface FieldProps {
    id: string;
    label?: ReactNode;
    hint?: ReactNode;
    error?: ReactNode;
    required?: boolean;
    className?: string;
    children: ReactNode;
}

export const describedBy = (id: string, hint?: ReactNode, error?: ReactNode): string | undefined => {
    const ids = [hint ? `${id}-hint` : null, error ? `${id}-error` : null].filter(Boolean);
    return ids.length > 0 ? ids.join(' ') : undefined;
};

/**
 * Label + control + hint/error wrapper. Every input in the system uses it so
 * the `aria-describedby` wiring is written once and cannot drift.
 */
export function Field({ id, label, hint, error, required, className, children }: FieldProps) {
    return (
        <div className={cn('flex min-w-0 max-w-full flex-col gap-1.5', className)}>
            {label && (
                <label
                    htmlFor={id}
                    className="text-[length:var(--text-sm)] font-medium text-[var(--color-text)]"
                >
                    {label}
                    {required && (
                        <span className="ml-0.5 text-[var(--color-danger)]" aria-hidden>
                            *
                        </span>
                    )}
                </label>
            )}
            {children}
            {hint && !error && (
                <p id={`${id}-hint`} className="text-[length:var(--text-xs)] text-[var(--color-text-muted)]">
                    {hint}
                </p>
            )}
            {error && (
                <p
                    id={`${id}-error`}
                    role="alert"
                    className="text-[length:var(--text-xs)] text-[var(--color-danger)]"
                >
                    {error}
                </p>
            )}
        </div>
    );
}

/** Shared visual treatment for text-like controls. */
export const controlClasses = (invalid?: boolean, extra?: string) =>
    cn(
        'w-full rounded-md border bg-[var(--color-surface)] text-[var(--color-text)]',
        'placeholder:text-[var(--color-text-subtle)]',
        'transition-colors duration-[var(--duration-fast)]',
        'focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-[var(--color-ring)]',
        'disabled:cursor-not-allowed disabled:bg-[var(--color-surface-sunken)] disabled:opacity-70',
        invalid
            ? 'border-[var(--color-danger)] focus-visible:outline-[var(--color-danger)]'
            : 'border-[var(--color-border-strong)] hover:border-[var(--color-border-strong)]',
        extra,
    );
