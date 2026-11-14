import { forwardRef, useId, type InputHTMLAttributes, type ReactNode } from 'react';
import { cn } from '../lib/cn';

export interface RadioProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
    label?: ReactNode;
    description?: ReactNode;
    wrapperClassName?: string;
}

export const Radio = forwardRef<HTMLInputElement, RadioProps>(function Radio(
    { label, description, className, wrapperClassName, id, disabled, ...props },
    ref,
) {
    const reactId = useId();
    const inputId = id ?? reactId;

    return (
        <div className={cn('flex items-start gap-2.5', wrapperClassName)}>
            <span className="relative flex h-5 items-center">
                <input
                    ref={ref}
                    id={inputId}
                    type="radio"
                    disabled={disabled}
                    aria-describedby={description ? `${inputId}-desc` : undefined}
                    className={cn(
                        'peer h-4 w-4 shrink-0 cursor-pointer appearance-none rounded-full',
                        'border border-[var(--color-border-strong)] bg-[var(--color-surface)]',
                        'checked:border-[var(--color-primary)] checked:border-[5px]',
                        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)]',
                        'disabled:cursor-not-allowed disabled:opacity-55',
                        className,
                    )}
                    {...props}
                />
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
    );
});

export interface RadioGroupProps {
    /** Shared `name` for the underlying inputs. */
    name: string;
    legend?: ReactNode;
    hint?: ReactNode;
    error?: ReactNode;
    value?: string;
    onValueChange?: (value: string) => void;
    options: ReadonlyArray<{ value: string; label: ReactNode; description?: ReactNode; disabled?: boolean }>;
    orientation?: 'vertical' | 'horizontal';
    className?: string;
}

/** Fieldset-wrapped radio list — arrow-key roving comes from the native group. */
export function RadioGroup({
    name,
    legend,
    hint,
    error,
    value,
    onValueChange,
    options,
    orientation = 'vertical',
    className,
}: RadioGroupProps) {
    const groupId = useId();

    return (
        <fieldset
            className={cn('flex flex-col gap-2 border-0 p-0', className)}
            aria-describedby={hint ? `${groupId}-hint` : undefined}
            aria-invalid={error ? true : undefined}
        >
            {legend && (
                <legend className="mb-1 p-0 text-[length:var(--text-sm)] font-medium text-[var(--color-text)]">
                    {legend}
                </legend>
            )}
            <div className={cn('flex gap-3', orientation === 'vertical' ? 'flex-col' : 'flex-row flex-wrap')}>
                {options.map((option) => (
                    <Radio
                        key={option.value}
                        name={name}
                        value={option.value}
                        label={option.label}
                        description={option.description}
                        disabled={option.disabled}
                        checked={value === undefined ? undefined : value === option.value}
                        onChange={(event) => onValueChange?.(event.currentTarget.value)}
                    />
                ))}
            </div>
            {hint && !error && (
                <p id={`${groupId}-hint`} className="text-[length:var(--text-xs)] text-[var(--color-text-muted)]">
                    {hint}
                </p>
            )}
            {error && (
                <p role="alert" className="text-[length:var(--text-xs)] text-[var(--color-danger)]">
                    {error}
                </p>
            )}
        </fieldset>
    );
}
