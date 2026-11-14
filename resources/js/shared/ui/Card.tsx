import type { HTMLAttributes, ReactNode } from 'react';
import { cn } from '../lib/cn';

export interface CardProps extends HTMLAttributes<HTMLDivElement> {
    padding?: 'none' | 'sm' | 'md' | 'lg';
    /** `raised` lifts off the canvas; `flat` sits inside another surface. */
    elevation?: 'flat' | 'raised';
    interactive?: boolean;
}

const paddings = {
    none: '',
    sm: 'p-3',
    md: 'p-4',
    lg: 'p-6',
} as const;

export function Card({
    padding = 'md',
    elevation = 'raised',
    interactive = false,
    className,
    children,
    ...props
}: CardProps) {
    return (
        <div
            className={cn(
                'min-w-0 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)]',
                elevation === 'raised' && 'shadow-[var(--shadow-sm)]',
                interactive &&
                    'transition-shadow duration-[var(--duration-fast)] hover:shadow-[var(--shadow-md)] focus-within:shadow-[var(--shadow-md)]',
                paddings[padding],
                className,
            )}
            {...props}
        >
            {children}
        </div>
    );
}

// `title` is a ReactNode here, not the native string tooltip attribute.
export interface CardHeaderProps extends Omit<HTMLAttributes<HTMLDivElement>, 'title'> {
    title: ReactNode;
    description?: ReactNode;
    actions?: ReactNode;
}

export function CardHeader({ title, description, actions, className, ...props }: CardHeaderProps) {
    return (
        <div className={cn('flex flex-wrap items-start justify-between gap-4', className)} {...props}>
            <div className="min-w-0">
                <h3 className="truncate text-[length:var(--text-md)] font-semibold text-[var(--color-text)]">
                    {title}
                </h3>
                {description && (
                    <p className="mt-0.5 text-[length:var(--text-sm)] text-[var(--color-text-muted)]">
                        {description}
                    </p>
                )}
            </div>
            {actions && <div className="flex min-w-0 max-w-full flex-wrap items-center gap-2">{actions}</div>}
        </div>
    );
}

export function CardBody({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
    return <div className={cn('mt-4', className)} {...props} />;
}

export function CardFooter({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
    return (
        <div
            className={cn(
                'mt-4 flex items-center justify-end gap-2 border-t border-[var(--color-border)] pt-3',
                className,
            )}
            {...props}
        />
    );
}
