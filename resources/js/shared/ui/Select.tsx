import { ChevronDown } from 'lucide-react';
import { forwardRef, useId, type ReactNode, type SelectHTMLAttributes } from 'react';
import { cn } from '../lib/cn';
import { controlClasses, describedBy, Field } from './Field';

export interface SelectOption {
    value: string;
    label: string;
    disabled?: boolean;
}

export interface SelectProps extends Omit<SelectHTMLAttributes<HTMLSelectElement>, 'size' | 'children'> {
    label?: ReactNode;
    hint?: ReactNode;
    error?: ReactNode;
    size?: 'sm' | 'md' | 'lg';
    options: readonly SelectOption[];
    /** Renders a disabled first option; pairs with an empty `value`. */
    placeholder?: string;
    wrapperClassName?: string;
}

const sizes = {
    sm: 'h-8 text-[length:var(--text-sm)]',
    md: 'h-9 text-[length:var(--text-base)]',
    lg: 'h-11 text-[length:var(--text-md)]',
} as const;

/**
 * A native <select>. Deliberately native: it is keyboard- and screen-reader
 * correct for free, and mobile gets the platform picker. Use `DropdownMenu`
 * when you need arbitrary content instead of a value list.
 */
export const Select = forwardRef<HTMLSelectElement, SelectProps>(function Select(
    { label, hint, error, size = 'md', options, placeholder, className, wrapperClassName, id, required, ...props },
    ref,
) {
    const reactId = useId();
    const selectId = id ?? reactId;

    return (
        <Field
            id={selectId}
            label={label}
            hint={hint}
            error={error}
            required={required}
            className={wrapperClassName}
        >
            <div className="relative flex items-center">
                <select
                    ref={ref}
                    id={selectId}
                    required={required}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={describedBy(selectId, hint, error)}
                    className={cn(
                        controlClasses(Boolean(error)),
                        sizes[size],
                        'appearance-none pr-9 pl-3',
                        className,
                    )}
                    {...props}
                >
                    {placeholder && (
                        <option value="" disabled>
                            {placeholder}
                        </option>
                    )}
                    {options.map((option) => (
                        <option key={option.value} value={option.value} disabled={option.disabled}>
                            {option.label}
                        </option>
                    ))}
                </select>
                <ChevronDown
                    className="pointer-events-none absolute right-2.5 h-4 w-4 text-[var(--color-text-subtle)]"
                    aria-hidden
                />
            </div>
        </Field>
    );
});
