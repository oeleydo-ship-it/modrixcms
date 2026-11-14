import { ArrowDown, ArrowUp, ChevronsUpDown, Settings2 } from 'lucide-react';
import { useMemo, type ReactNode } from 'react';
import { cn } from '../lib/cn';
import { Button } from './Button';
import { Checkbox } from './Checkbox';
import { DropdownLabel, DropdownMenu, DropdownSeparator } from './DropdownMenu';
import { Skeleton } from './Skeleton';

export type SortDirection = 'asc' | 'desc';

export interface SortState {
    column: string;
    direction: SortDirection;
}

export interface Column<Row> {
    /** Stable key — also the sort key sent to the API. */
    id: string;
    header: ReactNode;
    /** Cell renderer. Keep it pure; it runs for every row on every render. */
    cell: (row: Row) => ReactNode;
    sortable?: boolean;
    align?: 'left' | 'right' | 'center';
    width?: string;
    /** Excluded from the column-config menu; always visible. */
    locked?: boolean;
}

export interface TableProps<Row> {
    columns: ReadonlyArray<Column<Row>>;
    rows: readonly Row[];
    rowKey: (row: Row) => string;
    sort?: SortState | null;
    onSortChange?: (sort: SortState) => void;
    /** Ids of hidden columns. Omit both props to disable column configuration. */
    hiddenColumns?: readonly string[];
    onHiddenColumnsChange?: (hidden: string[]) => void;
    loading?: boolean;
    skeletonRows?: number;
    emptyState?: ReactNode;
    onRowClick?: (row: Row) => void;
    caption?: string;
    className?: string;
    /** Sticky header for long, scrolling lists. */
    stickyHeader?: boolean;
}

const alignClass = {
    left: 'text-left',
    right: 'text-right',
    center: 'text-center',
} as const;

/**
 * Data table with tri-state-free sorting (asc <-> desc), optional column
 * visibility config, loading skeletons and an empty state. Sorting is announced
 * through `aria-sort`, and the sort control is a real button inside the header.
 */
export function Table<Row>({
    columns,
    rows,
    rowKey,
    sort = null,
    onSortChange,
    hiddenColumns,
    onHiddenColumnsChange,
    loading = false,
    skeletonRows = 5,
    emptyState,
    onRowClick,
    caption,
    className,
    stickyHeader = false,
}: TableProps<Row>) {
    const hidden = useMemo(() => new Set(hiddenColumns ?? []), [hiddenColumns]);
    const visibleColumns = useMemo(
        () => columns.filter((column) => column.locked || !hidden.has(column.id)),
        [columns, hidden],
    );
    const configurable = Boolean(hiddenColumns && onHiddenColumnsChange);

    const toggleSort = (columnId: string) => {
        if (!onSortChange) {
            return;
        }
        const direction: SortDirection = sort?.column === columnId && sort.direction === 'asc' ? 'desc' : 'asc';
        onSortChange({ column: columnId, direction });
    };

    return (
        <div
            className={cn(
                'relative min-w-0 max-w-full overflow-hidden rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)]',
                className,
            )}
        >
            {configurable && (
                <div className="flex justify-end border-b border-[var(--color-border)] px-3 py-2">
                    <DropdownMenu
                        align="end"
                        trigger={(triggerProps) => (
                            <Button
                                {...triggerProps}
                                variant="ghost"
                                size="sm"
                                leadingIcon={<Settings2 className="h-4 w-4" />}
                            >
                                Columns
                            </Button>
                        )}
                    >
                        <DropdownLabel>Visible columns</DropdownLabel>
                        <DropdownSeparator />
                        <div className="flex flex-col gap-1 p-1.5">
                            {columns.map((column) => (
                                <Checkbox
                                    key={column.id}
                                    label={typeof column.header === 'string' ? column.header : column.id}
                                    checked={column.locked || !hidden.has(column.id)}
                                    disabled={column.locked}
                                    onChange={(event) => {
                                        const next = new Set(hidden);
                                        if (event.currentTarget.checked) {
                                            next.delete(column.id);
                                        } else {
                                            next.add(column.id);
                                        }
                                        onHiddenColumnsChange?.([...next]);
                                    }}
                                />
                            ))}
                        </div>
                    </DropdownMenu>
                </div>
            )}

            <div className="overflow-x-auto">
                <table className="w-full border-collapse text-[length:var(--text-base)]">
                    {caption && <caption className="sr-only">{caption}</caption>}
                    <thead
                        className={cn(
                            'bg-[var(--color-surface-sunken)]',
                            stickyHeader && 'sticky top-0 z-10',
                        )}
                    >
                        <tr>
                            {visibleColumns.map((column) => {
                                const isSorted = sort?.column === column.id;
                                return (
                                    <th
                                        key={column.id}
                                        scope="col"
                                        style={column.width ? { width: column.width } : undefined}
                                        aria-sort={
                                            column.sortable
                                                ? isSorted
                                                    ? sort!.direction === 'asc'
                                                        ? 'ascending'
                                                        : 'descending'
                                                    : 'none'
                                                : undefined
                                        }
                                        className={cn(
                                            'border-b border-[var(--color-border)] px-3 py-2.5',
                                            'text-[length:var(--text-xs)] font-semibold tracking-wide text-[var(--color-text-muted)] uppercase',
                                            alignClass[column.align ?? 'left'],
                                        )}
                                    >
                                        {column.sortable && onSortChange ? (
                                            <button
                                                type="button"
                                                onClick={() => toggleSort(column.id)}
                                                className={cn(
                                                    'inline-flex items-center gap-1.5 rounded hover:text-[var(--color-text)]',
                                                    'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)]',
                                                    isSorted && 'text-[var(--color-text)]',
                                                )}
                                            >
                                                {column.header}
                                                {isSorted ? (
                                                    sort!.direction === 'asc' ? (
                                                        <ArrowUp className="h-3.5 w-3.5" aria-hidden />
                                                    ) : (
                                                        <ArrowDown className="h-3.5 w-3.5" aria-hidden />
                                                    )
                                                ) : (
                                                    <ChevronsUpDown
                                                        className="h-3.5 w-3.5 opacity-50"
                                                        aria-hidden
                                                    />
                                                )}
                                            </button>
                                        ) : (
                                            column.header
                                        )}
                                    </th>
                                );
                            })}
                        </tr>
                    </thead>

                    <tbody>
                        {loading &&
                            Array.from({ length: skeletonRows }).map((_, rowIndex) => (
                                <tr key={`skeleton-${rowIndex}`}>
                                    {visibleColumns.map((column) => (
                                        <td
                                            key={column.id}
                                            className="border-b border-[var(--color-border)] px-3 py-3"
                                        >
                                            <Skeleton className="h-4 w-full max-w-40" />
                                        </td>
                                    ))}
                                </tr>
                            ))}

                        {!loading &&
                            rows.map((row) => (
                                <tr
                                    key={rowKey(row)}
                                    onClick={onRowClick ? () => onRowClick(row) : undefined}
                                    tabIndex={onRowClick ? 0 : undefined}
                                    onKeyDown={
                                        onRowClick
                                            ? (event) => {
                                                  if (event.key === 'Enter' || event.key === ' ') {
                                                      event.preventDefault();
                                                      onRowClick(row);
                                                  }
                                              }
                                            : undefined
                                    }
                                    className={cn(
                                        'border-b border-[var(--color-border)] last:border-b-0',
                                        onRowClick &&
                                            'cursor-pointer hover:bg-[var(--color-surface-hover)] focus-visible:bg-[var(--color-surface-hover)] focus-visible:outline-none',
                                    )}
                                >
                                    {visibleColumns.map((column) => (
                                        <td
                                            key={column.id}
                                            className={cn(
                                                'px-3 py-2.5 text-[var(--color-text)]',
                                                alignClass[column.align ?? 'left'],
                                            )}
                                        >
                                            {column.cell(row)}
                                        </td>
                                    ))}
                                </tr>
                            ))}

                        {!loading && rows.length === 0 && (
                            <tr>
                                <td colSpan={visibleColumns.length} className="px-3 py-10">
                                    {emptyState ?? (
                                        <p className="text-center text-[var(--color-text-muted)]">
                                            Nothing to show yet.
                                        </p>
                                    )}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
