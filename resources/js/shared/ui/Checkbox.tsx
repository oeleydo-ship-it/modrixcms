import { Check, Minus } from 'lucide-react';
import { forwardRef, useEffect, useId, useRef, type InputHTMLAttributes, type ReactNode } from 'react';
import { cn } from '../lib/cn';

export interface CheckboxProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type' | 'size'> {
    label?: ReactNode;
    description?: ReactNode;
    error?: ReactNode;
    /** Tri-state: renders the dash glyph and sets `indeterminate` on the node. */
    indeterminate?: boolean;
    wrapperClassName?: string;
}

/**
 * A real `<input type="checkbox">` kept visually hidden but hit-testable, with a
 * styled proxy. Keyboard, form submission and AT support come from the input.
 */
export const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>(function Checkbox(
    { label, description, error, indeterminate = false, className, wrapperClassName, id, disabled, ...props },
    ref,
) {
    const reactId = useId();
    const inputId = id ?? reactId;
    const innerRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (innerRef.current) {
            innerRef.current.indeterminate = indeterminate;
        }
    }, [indeterminate]);

    return (
        <div className={cn('flex flex-col gap-1', wrapperClassName)}>
            <div className="flex items-start gap-2.5">
                <span className="relative flex h-5 items-center">
                    <input
                        ref={(node) => {
                            innerRef.current = node;
                            if (typeof ref === 'function') {
                                ref(node);
                            } else if (ref) {
                                ref.current = node;
                            }
                        }}
                        id={inputId}
                        type="checkbox"
                        disabled={disabled}
                        aria-invalid={error ? true : undefined}
                        aria-describedby={
                            description ? `${inputId}-desc` : error ? `${inputId}-error` : undefined
                        }
                        className={cn('peer h-4 w-4 shrink-0 cursor-pointer appearance-none rounded-[4px]', 'border border-[var(--color-border-strong)] bg-[var(--color-surface)]', 'checked:border-[var(--color-primary)] checked:bg-[var(--color-primary)]', 'indeterminate:border-[var(--color-primary)] indeterminate:bg-[var(--color-primary)]', 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)]', 'disabled:cursor-not-allowed disabled:opacity-55', error && 'border-[var(--color-danger)]', className)}
                        {...props}
                    />
                    <span
                        className="pointer-events-none absolute left-0 flex h-4 w-4 items-center justify-center text-white opacity-0 peer-checked:opacity-100 peer-indeterminate:opacity-100"
                        aria-hidden
                    >
                        {indeterminate ? <Minus className="h-3 w-3" /> : <Check className="h-3 w-3" />}
                    </span>
                </span>

                {(label || description) && (
                    <span className="flex flex-col gap-0.5">
                        {label && (
                            <label
                                htmlFor={inputId}
                                className={cn(
                                    'cursor-pointer text-[length:var(--text-base)] leading-5 text-[var(--color-text)]',
                                    disabled && 'cursor-not-allowed opacity-60',
                                )}
                            >
                                {label}
                            </label>
                        )}
                        {description && (
                            <span
                                id={`${inputId}-desc`}
                                className="text-[length:var(--text-xs)] text-[var(--color-text-muted)]"
                            >
                                {description}
                            </span>
                        )}
                    </span>
                )}
            </div>
            {error && (
                <p
                    id={`${inputId}-error`}
                    role="alert"
                    className="text-[length:var(--text-xs)] text-[var(--color-danger)]"
                >
                    {error}
                </p>
            )}
        </div>
    );
});
