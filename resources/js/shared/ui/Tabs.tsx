import { useId, useRef, type KeyboardEvent as ReactKeyboardEvent, type ReactNode } from 'react';
import { cn } from '../lib/cn';
import { Badge } from './Badge';

export interface TabItem {
    id: string;
    label: ReactNode;
    icon?: ReactNode;
    count?: number;
    disabled?: boolean;
}

export interface TabsProps {
    tabs: readonly TabItem[];
    value: string;
    onValueChange: (id: string) => void;
    variant?: 'underline' | 'pill';
    className?: string;
    /** Renders the panel wrapper with the right `aria-labelledby`. */
    children?: ReactNode;
}

/**
 * ARIA tabs with manual activation: arrows move focus and select, Home/End jump.
 * Panels are the caller's responsibility — pass them as `children`.
 */
export function Tabs({ tabs, value, onValueChange, variant = 'underline', className, children }: TabsProps) {
    const baseId = useId();
    const listRef = useRef<HTMLDivElement>(null);

    const move = (delta: number) => {
        const enabled = tabs.filter((tab) => !tab.disabled);
        const current = enabled.findIndex((tab) => tab.id === value);
        const next = enabled[(current + delta + enabled.length) % enabled.length];
        if (next) {
            onValueChange(next.id);
            listRef.current
                ?.querySelector<HTMLButtonElement>(`[data-tab-id="${CSS.escape(next.id)}"]`)
                ?.focus();
        }
    };

    const onKeyDown = (event: ReactKeyboardEvent<HTMLDivElement>) => {
        switch (event.key) {
            case 'ArrowRight':
                event.preventDefault();
                move(1);
                break;
            case 'ArrowLeft':
                event.preventDefault();
                move(-1);
                break;
            case 'Home': {
                event.preventDefault();
                const first = tabs.find((tab) => !tab.disabled);
                if (first) onValueChange(first.id);
                break;
            }
            case 'End': {
                event.preventDefault();
                const last = [...tabs].reverse().find((tab) => !tab.disabled);
                if (last) onValueChange(last.id);
                break;
            }
            default:
                break;
        }
    };

    return (
        <div className={className}>
            <div
                ref={listRef}
                role="tablist"
                onKeyDown={onKeyDown}
                className={cn(
                    'flex items-center gap-1 overflow-x-auto',
                    variant === 'underline' && 'border-b border-[var(--color-border)]',
                    variant === 'pill' && 'rounded-lg bg-[var(--color-surface-sunken)] p-1',
                )}
            >
                {tabs.map((tab) => {
                    const selected = tab.id === value;
                    return (
                        <button
                            key={tab.id}
                            type="button"
                            role="tab"
                            id={`${baseId}-tab-${tab.id}`}
                            data-tab-id={tab.id}
                            aria-selected={selected}
                            aria-controls={`${baseId}-panel-${tab.id}`}
                            tabIndex={selected ? 0 : -1}
                            disabled={tab.disabled}
                            onClick={() => onValueChange(tab.id)}
                            className={cn(
                                'inline-flex shrink-0 items-center gap-2 font-medium whitespace-nowrap',
                                'transition-colors duration-[var(--duration-fast)]',
                                'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)]',
                                'disabled:cursor-not-allowed disabled:opacity-50',
                                variant === 'underline' &&
                                    cn(
                                        '-mb-px border-b-2 px-3 py-2 text-[length:var(--text-base)]',
                                        selected
                                            ? 'border-[var(--color-primary)] text-[var(--color-text)]'
                                            : 'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text)]',
                                    ),
                                variant === 'pill' &&
                                    cn(
                                        'rounded-md px-3 py-1.5 text-[length:var(--text-sm)]',
                                        selected
                                            ? 'bg-[var(--color-surface)] text-[var(--color-text)] shadow-[var(--shadow-xs)]'
                                            : 'text-[var(--color-text-muted)] hover:text-[var(--color-text)]',
                                    ),
                            )}
                        >
                            {tab.icon && <span aria-hidden>{tab.icon}</span>}
                            {tab.label}
                            {tab.count != null && (
                                <Badge tone={selected ? 'brand' : 'neutral'}>{tab.count}</Badge>
                            )}
                        </button>
                    );
                })}
            </div>
            {children && (
                <div
                    role="tabpanel"
                    id={`${baseId}-panel-${value}`}
                    aria-labelledby={`${baseId}-tab-${value}`}
                    tabIndex={0}
                    className="pt-4 focus-visible:outline-none"
                >
                    {children}
                </div>
            )}
        </div>
    );
}
