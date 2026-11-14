import { CircleCheck, CircleX, Info, TriangleAlert, X } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '../lib/cn';

export type AlertTone = 'info' | 'success' | 'warning' | 'danger';

export interface AlertProps {
    tone?: AlertTone;
    title?: ReactNode;
    children?: ReactNode;
    /** Renders a dismiss button; the caller controls visibility. */
    onDismiss?: () => void;
    action?: ReactNode;
    className?: string;
}

const icons = {
    info: Info,
    success: CircleCheck,
    warning: TriangleAlert,
    danger: CircleX,
} as const;

const tones: Record<AlertTone, string> = {
    info: 'bg-[var(--color-info-soft)] text-[var(--color-info)]',
    success: 'bg-[var(--color-success-soft)] text-[var(--color-success)]',
    warning: 'bg-[var(--color-warning-soft)] text-[var(--color-warning)]',
    danger: 'bg-[var(--color-danger-soft)] text-[var(--color-danger)]',
};

/**
 * Inline, persistent message. `danger` and `warning` use `role="alert"` so
 * assistive tech announces them; the calmer tones use `role="status"`.
 */
export function Alert({ tone = 'info', title, children, onDismiss, action, className }: AlertProps) {
    const Icon = icons[tone];

    return (
        <div
            role={tone === 'danger' || tone === 'warning' ? 'alert' : 'status'}
            className={cn(
                'flex items-start gap-3 rounded-lg border border-transparent p-3.5',
                tones[tone],
                className,
            )}
        >
            <Icon className="mt-0.5 h-4.5 w-4.5 shrink-0" aria-hidden />
            <div className="min-w-0 flex-1 text-[var(--color-text)]">
                {title && (
                    <p className="text-[length:var(--text-base)] font-semibold text-current">{title}</p>
                )}
                {children && (
                    <div
                        className={cn(
                            'text-[length:var(--text-sm)] text-[var(--color-text-muted)]',
                            title && 'mt-0.5',
                        )}
                    >
                        {children}
                    </div>
                )}
                {action && <div className="mt-2.5 flex items-center gap-2">{action}</div>}
            </div>
            {onDismiss && (
                <button
                    type="button"
                    aria-label="Dismiss message"
                    onClick={onDismiss}
                    className="-mt-0.5 -mr-0.5 rounded p-1 text-current opacity-70 hover:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)]"
                >
                    <X className="h-3.5 w-3.5" aria-hidden />
                </button>
            )}
        </div>
    );
}
