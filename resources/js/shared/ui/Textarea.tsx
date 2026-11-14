import { forwardRef, useId, type ReactNode, type TextareaHTMLAttributes } from 'react';
import { cn } from '../lib/cn';
import { controlClasses, describedBy, Field } from './Field';

export interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
    label?: ReactNode;
    hint?: ReactNode;
    error?: ReactNode;
    wrapperClassName?: string;
    /** Show a live "n / max" counter. Requires `maxLength`. */
    showCount?: boolean;
}

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(function Textarea(
    { label, hint, error, className, wrapperClassName, id, required, showCount, maxLength, value, ...props },
    ref,
) {
    const reactId = useId();
    const textareaId = id ?? reactId;
    const length = typeof value === 'string' ? value.length : 0;

    return (
        <Field
            id={textareaId}
            label={label}
            hint={hint}
            error={error}
            required={required}
            className={wrapperClassName}
        >
            <div className="relative">
                <textarea
                    ref={ref}
                    id={textareaId}
                    required={required}
                    maxLength={maxLength}
                    value={value}
                    rows={props.rows ?? 4}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={describedBy(textareaId, hint, error)}
                    className={cn(
                        controlClasses(Boolean(error)),
                        'resize-y px-3 py-2 text-[length:var(--text-base)] leading-[var(--leading-normal)]',
                        className,
                    )}
                    {...props}
                />
                {showCount && maxLength != null && (
                    <span
                        aria-live="polite"
                        className="pointer-events-none absolute right-2 bottom-2 rounded bg-[var(--color-surface)] px-1 text-[length:var(--text-2xs)] text-[var(--color-text-subtle)]"
                    >
                        {length}/{maxLength}
                    </span>
                )}
            </div>
        </Field>
    );
});
