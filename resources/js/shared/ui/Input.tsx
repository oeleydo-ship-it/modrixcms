import { forwardRef, useId, type InputHTMLAttributes, type ReactNode } from 'react';
import { cn } from '../lib/cn';
import { controlClasses, describedBy, Field } from './Field';

export interface InputProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'size'> {
    label?: ReactNode;
    hint?: ReactNode;
    error?: ReactNode;
    size?: 'sm' | 'md' | 'lg';
    leadingIcon?: ReactNode;
    trailingIcon?: ReactNode;
    /** Class list for the outer <Field> wrapper. */
    wrapperClassName?: string;
}

const sizes = {
    sm: 'h-8 text-[length:var(--text-sm)]',
    md: 'h-9 text-[length:var(--text-base)]',
    lg: 'h-11 text-[length:var(--text-md)]',
} as const;

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
    {
        label,
        hint,
        error,
        size = 'md',
        leadingIcon,
        trailingIcon,
        className,
        wrapperClassName,
        id,
        required,
        ...props
    },
    ref,
) {
    const reactId = useId();
    const inputId = id ?? reactId;

    return (
        <Field
            id={inputId}
            label={label}
            hint={hint}
            error={error}
            required={required}
            className={wrapperClassName}
        >
            <div className="relative flex items-center">
                {leadingIcon && (
                    <span
                        className="pointer-events-none absolute left-2.5 flex text-[var(--color-text-subtle)]"
                        aria-hidden
                    >
                        {leadingIcon}
                    </span>
                )}
                <input
                    ref={ref}
                    id={inputId}
                    required={required}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={describedBy(inputId, hint, error)}
                    className={cn(
                        controlClasses(Boolean(error)),
                        sizes[size],
                        'px-3',
                        leadingIcon && 'pl-9',
                        trailingIcon && 'pr-9',
                        className,
                    )}
                    {...props}
                />
                {trailingIcon && (
                    <span className="absolute right-2.5 flex text-[var(--color-text-subtle)]">
                        {trailingIcon}
                    </span>
                )}
            </div>
        </Field>
    );
});
