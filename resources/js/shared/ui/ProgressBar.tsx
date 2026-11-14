import type { ReactNode } from 'react';
import { cn } from '../lib/cn';

export type ProgressTone = 'brand' | 'success' | 'warning' | 'danger';

export interface ProgressBarProps {
    value: number;
    max?: number;
    tone?: ProgressTone;
    size?: 'sm' | 'md';
    label?: ReactNode;
    /** Text shown at the right of the label row. */
    valueLabel?: ReactNode;
    className?: string;
    'aria-label'?: string;
}

const tones: Record<ProgressTone, string> = {
    brand: 'bg-[var(--color-primary)]',
    success: 'bg-[var(--color-success)]',
    warning: 'bg-[var(--color-warning)]',
    danger: 'bg-[var(--color-danger)]',
};

export function ProgressBar({
    value,
    max = 100,
    tone = 'brand',
    size = 'md',
    label,
    valueLabel,
    className,
    ...aria
}: ProgressBarProps) {
    const clamped = Math.min(Math.max(value, 0), max);
    const percent = max === 0 ? 0 : (clamped / max) * 100;

    return (
        <div className={cn('w-full', className)}>
            {(label || valueLabel) && (
                <div className="mb-1.5 flex items-baseline justify-between gap-3 text-[length:var(--text-sm)]">
                    {label && <span className="text-[var(--color-text)]">{label}</span>}
                    {valueLabel && <span className="text-[var(--color-text-muted)]">{valueLabel}</span>}
                </div>
            )}
            <div
                role="progressbar"
                aria-valuenow={Math.round(clamped)}
                aria-valuemin={0}
                aria-valuemax={max}
                aria-label={aria['aria-label'] ?? (typeof label === 'string' ? label : undefined)}
                className={cn(
                    'w-full overflow-hidden rounded-full bg-[var(--color-surface-sunken)]',
                    size === 'sm' ? 'h-1.5' : 'h-2',
                )}
            >
                <div
                    className={cn('h-full rounded-full transition-[width] duration-[var(--duration-slow)]', tones[tone])}
                    style={{ width: `${percent}%` }}
                />
            </div>
        </div>
    );
}

export interface UsageBarProps {
    label: ReactNode;
    used: number;
    limit: number | null;
    /** e.g. "contacts", "emails / month". */
    unit?: string;
    /** Rendered when the workspace is at or over the limit — an upgrade CTA. */
    upgradeSlot?: ReactNode;
    className?: string;
}

/**
 * Plan quota meter (spec §46). Turns amber at 80% and red at 100% so the
 * upgrade prompt appears before the workspace actually hits the wall.
 */
export function UsageBar({ label, used, limit, unit, upgradeSlot, className }: UsageBarProps) {
    const unlimited = limit === null;
    const ratio = unlimited || limit === 0 ? 0 : used / limit;
    const tone: ProgressTone = ratio >= 1 ? 'danger' : ratio >= 0.8 ? 'warning' : 'brand';

    return (
        <div className={cn('flex flex-col gap-1.5', className)}>
            <ProgressBar
                value={unlimited ? 0 : used}
                max={unlimited ? 100 : Math.max(limit, 1)}
                tone={tone}
                label={label}
                valueLabel={
                    unlimited
                        ? 'Unlimited'
                        : `${used.toLocaleString()} / ${limit.toLocaleString()}${unit ? ` ${unit}` : ''}`
                }
            />
            {ratio >= 0.8 && upgradeSlot}
        </div>
    );
}
