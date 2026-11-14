import { forwardRef, type ButtonHTMLAttributes, type ReactNode } from 'react';
import { cn } from '../lib/cn';
import { Spinner } from './Spinner';

export type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger' | 'outline' | 'link';
export type ButtonSize = 'xs' | 'sm' | 'md' | 'lg';

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: ButtonVariant;
    size?: ButtonSize;
    /** Shows a spinner, disables the button and marks it `aria-busy`. */
    loading?: boolean;
    /** Rendered before the label. Decorative — give the button a real label too. */
    leadingIcon?: ReactNode;
    trailingIcon?: ReactNode;
    fullWidth?: boolean;
    /** Square icon-only button. `aria-label` becomes required in practice. */
    iconOnly?: boolean;
}

const base =
    'relative inline-flex items-center justify-center gap-2 font-medium whitespace-nowrap ' +
    'rounded-md border transition-colors duration-[var(--duration-fast)] ' +
    'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)] ' +
    'disabled:pointer-events-none disabled:opacity-55 select-none';

const variants: Record<ButtonVariant, string> = {
    primary:
        'border-transparent bg-[var(--color-primary)] text-[var(--color-primary-fg)] shadow-[var(--shadow-xs)] ' +
        'hover:bg-[var(--color-primary-hover)] active:bg-[var(--color-primary-hover)]',
    secondary:
        'border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text)] shadow-[var(--shadow-xs)] ' +
        'hover:bg-[var(--color-surface-hover)] active:bg-[var(--color-surface-active)]',
    outline:
        'border-[var(--color-border-strong)] bg-transparent text-[var(--color-text)] ' +
        'hover:bg-[var(--color-surface-hover)] active:bg-[var(--color-surface-active)]',
    ghost:
        'border-transparent bg-transparent text-[var(--color-text-muted)] ' +
        'hover:bg-[var(--color-surface-hover)] hover:text-[var(--color-text)] active:bg-[var(--color-surface-active)]',
    danger:
        'border-transparent bg-[var(--color-danger)] text-white shadow-[var(--shadow-xs)] ' +
        'hover:brightness-95 active:brightness-90',
    link: 'border-transparent bg-transparent text-[var(--color-text-brand)] underline-offset-4 hover:underline px-0',
};

const sizes: Record<ButtonSize, string> = {
    xs: 'h-7 px-2.5 text-[length:var(--text-xs)]',
    sm: 'h-8 px-3 text-[length:var(--text-sm)]',
    md: 'h-9 px-3.5 text-[length:var(--text-base)]',
    lg: 'h-11 px-5 text-[length:var(--text-md)]',
};

const iconSizes: Record<ButtonSize, string> = {
    xs: 'h-7 w-7 p-0',
    sm: 'h-8 w-8 p-0',
    md: 'h-9 w-9 p-0',
    lg: 'h-11 w-11 p-0',
};

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
    {
        variant = 'secondary',
        size = 'md',
        loading = false,
        leadingIcon,
        trailingIcon,
        fullWidth = false,
        iconOnly = false,
        className,
        children,
        disabled,
        type = 'button',
        ...props
    },
    ref,
) {
    return (
        <button
            ref={ref}
            type={type}
            disabled={disabled || loading}
            aria-busy={loading || undefined}
            className={cn(
                base,
                variants[variant],
                iconOnly ? iconSizes[size] : sizes[size],
                fullWidth && 'w-full',
                className,
            )}
            {...props}
        >
            {loading ? (
                <Spinner size={size === 'lg' ? 'md' : 'sm'} className="shrink-0" aria-hidden />
            ) : (
                leadingIcon && (
                    <span className="shrink-0" aria-hidden>
                        {leadingIcon}
                    </span>
                )
            )}
            {iconOnly ? !loading && children : children}
            {!loading && trailingIcon && (
                <span className="shrink-0" aria-hidden>
                    {trailingIcon}
                </span>
            )}
        </button>
    );
});
