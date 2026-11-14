import { useId, type ReactNode } from 'react';
import { cn } from '../lib/cn';

export interface SwitchProps {
    checked: boolean;
    onCheckedChange: (checked: boolean) => void;
    label?: ReactNode;
    description?: ReactNode;
    disabled?: boolean;
    size?: 'sm' | 'md';
    /** Use when the visible label lives elsewhere. */
    'aria-label'?: string;
    className?: string;
    id?: string;
}

const track = {
    sm: 'h-4 w-7',
    md: 'h-5 w-9',
} as const;

const thumb = {
    sm: 'h-3 w-3 data-[on=true]:translate-x-3',
    md: 'h-4 w-4 data-[on=true]:translate-x-4',
} as const;

/**
 * `role="switch"` button — space/enter toggle it, and `aria-checked` reports
 * state. A button (not a checkbox) because it applies immediately, not on submit.
 */
export function Switch({
    checked,
    onCheckedChange,
    label,
    description,
    disabled = false,
    size = 'md',
    className,
    id,
    ...aria
}: SwitchProps) {
    const reactId = useId();
    const switchId = id ?? reactId;

    const control = (
        <button
            type="button"
            role="switch"
            id={switchId}
            aria-checked={checked}
            aria-label={aria['aria-label']}
            aria-describedby={description ? `${switchId}-desc` : undefined}
            disabled={disabled}
            onClick={() => onCheckedChange(!checked)}
            className={cn(
                'relative inline-flex shrink-0 cursor-pointer items-center rounded-full border border-transparent p-0.5',
                'transition-colors duration-[var(--duration-fast)]',
                'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)]',
                'disabled:cursor-not-allowed disabled:opacity-55',
                track[size],
                checked ? 'bg-[var(--color-primary)]' : 'bg-[var(--color-border-strong)]',
                className,
            )}
        >
            <span
                data-on={checked}
                aria-hidden
                className={cn(
                    'pointer-events-none block rounded-full bg-white shadow-[var(--shadow-xs)]',
                    'transition-transform duration-[var(--duration-fast)] ease-[var(--ease-out)]',
                    thumb[size],
                )}
            />
        </button>
    );

    if (!label && !description) {
        return control;
    }

    return (
        <div className="flex items-start gap-3">
            {control}
            <span className="flex flex-col gap-0.5">
                {label && (
                    <label
                        htmlFor={switchId}
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
                        id={`${switchId}-desc`}
                        className="text-[length:var(--text-xs)] text-[var(--color-text-muted)]"
                    >
                        {description}
                    </span>
                )}
            </span>
        </div>
    );
}
