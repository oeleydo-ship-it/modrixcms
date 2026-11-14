import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '../lib/cn';
import { Select } from './Select';

export interface PaginationProps {
    page: number;
    pageSize: number;
    total: number;
    onPageChange: (page: number) => void;
    onPageSizeChange?: (pageSize: number) => void;
    pageSizeOptions?: readonly number[];
    className?: string;
}

const DOTS = 'dots' as const;

/** Windowed page list: 1 … 4 5 [6] 7 8 … 20 */
export function pageItems(page: number, pageCount: number, siblings = 1): Array<number | typeof DOTS> {
    const total = siblings * 2 + 5;
    if (pageCount <= total) {
        return Array.from({ length: pageCount }, (_, i) => i + 1);
    }

    const left = Math.max(page - siblings, 1);
    const right = Math.min(page + siblings, pageCount);
    const showLeftDots = left > 2;
    const showRightDots = right < pageCount - 1;

    const items: Array<number | typeof DOTS> = [1];
    if (showLeftDots) {
        items.push(DOTS);
    }
    for (let i = Math.max(left, 2); i <= Math.min(right, pageCount - 1); i += 1) {
        items.push(i);
    }
    if (showRightDots) {
        items.push(DOTS);
    }
    items.push(pageCount);
    return items;
}

export function Pagination({
    page,
    pageSize,
    total,
    onPageChange,
    onPageSizeChange,
    pageSizeOptions = [10, 25, 50, 100],
    className,
}: PaginationProps) {
    const pageCount = Math.max(Math.ceil(total / pageSize), 1);
    const from = total === 0 ? 0 : (page - 1) * pageSize + 1;
    const to = Math.min(page * pageSize, total);

    return (
        <nav
            aria-label="Pagination"
            className={cn('flex flex-wrap items-center justify-between gap-3 py-3', className)}
        >
            <p className="text-[length:var(--text-sm)] text-[var(--color-text-muted)]" aria-live="polite">
                Showing <strong className="text-[var(--color-text)]">{from}</strong>–
                <strong className="text-[var(--color-text)]">{to}</strong> of{' '}
                <strong className="text-[var(--color-text)]">{total.toLocaleString()}</strong>
            </p>

            <div className="flex items-center gap-3">
                {onPageSizeChange && (
                    <Select
                        aria-label="Rows per page"
                        size="sm"
                        value={String(pageSize)}
                        onChange={(event) => onPageSizeChange(Number(event.currentTarget.value))}
                        options={pageSizeOptions.map((option) => ({
                            value: String(option),
                            label: `${option} / page`,
                        }))}
                        wrapperClassName="w-32"
                    />
                )}

                <ul className="flex items-center gap-1">
                    <li>
                        <PageButton
                            label="Previous page"
                            disabled={page <= 1}
                            onClick={() => onPageChange(page - 1)}
                        >
                            <ChevronLeft className="h-4 w-4" aria-hidden />
                        </PageButton>
                    </li>
                    {pageItems(page, pageCount).map((item, index) =>
                        item === DOTS ? (
                            <li
                                key={`dots-${index}`}
                                aria-hidden
                                className="px-1 text-[var(--color-text-subtle)]"
                            >
                                …
                            </li>
                        ) : (
                            <li key={item}>
                                <PageButton
                                    label={`Page ${item}`}
                                    current={item === page}
                                    onClick={() => onPageChange(item)}
                                >
                                    {item}
                                </PageButton>
                            </li>
                        ),
                    )}
                    <li>
                        <PageButton
                            label="Next page"
                            disabled={page >= pageCount}
                            onClick={() => onPageChange(page + 1)}
                        >
                            <ChevronRight className="h-4 w-4" aria-hidden />
                        </PageButton>
                    </li>
                </ul>
            </div>
        </nav>
    );
}

function PageButton({
    children,
    label,
    onClick,
    disabled,
    current,
}: {
    children: ReactNode;
    label: string;
    onClick: () => void;
    disabled?: boolean;
    current?: boolean;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            aria-label={label}
            aria-current={current ? 'page' : undefined}
            className={cn(
                'inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2',
                'text-[length:var(--text-sm)] transition-colors duration-[var(--duration-fast)]',
                'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)]',
                'disabled:pointer-events-none disabled:opacity-45',
                current
                    ? 'border-transparent bg-[var(--color-primary)] font-medium text-[var(--color-primary-fg)]'
                    : 'border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text-muted)] hover:bg-[var(--color-surface-hover)] hover:text-[var(--color-text)]',
            )}
        >
            {children}
        </button>
    );
}
