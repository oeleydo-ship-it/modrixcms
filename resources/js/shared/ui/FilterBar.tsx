import { Filter, X } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '../lib/cn';
import { Badge } from './Badge';
import { Button } from './Button';
import { DropdownItem, DropdownLabel, DropdownMenu, DropdownSeparator } from './DropdownMenu';
import { SearchInput } from './SearchInput';

export interface FilterDefinition {
    id: string;
    label: string;
    options: ReadonlyArray<{ value: string; label: string }>;
}

export interface FilterBarProps {
    search?: string;
    onSearchChange?: (value: string) => void;
    searchPlaceholder?: string;
    filters?: readonly FilterDefinition[];
    /** Active selection keyed by filter id. */
    values?: Readonly<Record<string, string | undefined>>;
    onValuesChange?: (values: Record<string, string | undefined>) => void;
    /** Extra controls (sort, date range, bulk actions) rendered on the right. */
    actions?: ReactNode;
    className?: string;
}

/**
 * Search + faceted filters + removable chips. Every list screen in the product
 * uses this so the filter interaction is identical everywhere.
 */
export function FilterBar({
    search,
    onSearchChange,
    searchPlaceholder = 'Search…',
    filters = [],
    values = {},
    onValuesChange,
    actions,
    className,
}: FilterBarProps) {
    const active = filters
        .map((filter) => {
            const value = values[filter.id];
            const option = filter.options.find((candidate) => candidate.value === value);
            return option ? { filter, option } : null;
        })
        .filter((entry): entry is { filter: FilterDefinition; option: { value: string; label: string } } =>
            Boolean(entry),
        );

    const setValue = (id: string, value: string | undefined) => {
        onValuesChange?.({ ...values, [id]: value });
    };

    const clearAll = () => {
        const cleared: Record<string, string | undefined> = { ...values };
        filters.forEach((filter) => delete cleared[filter.id]);
        onValuesChange?.(cleared);
    };

    return (
        <div className={cn('flex flex-col gap-3', className)}>
            <div className="flex flex-wrap items-center gap-2">
                {onSearchChange && (
                    <SearchInput
                        value={search ?? ''}
                        onValueChange={onSearchChange}
                        placeholder={searchPlaceholder}
                        className="w-full min-w-56 sm:w-72"
                    />
                )}

                {filters.map((filter) => (
                    <DropdownMenu
                        key={filter.id}
                        trigger={(triggerProps) => (
                            <Button
                                {...triggerProps}
                                variant="secondary"
                                size="sm"
                                leadingIcon={<Filter className="h-3.5 w-3.5" />}
                            >
                                {filter.label}
                                {values[filter.id] && (
                                    <Badge tone="brand" className="ml-1">
                                        1
                                    </Badge>
                                )}
                            </Button>
                        )}
                    >
                        <DropdownLabel>{filter.label}</DropdownLabel>
                        <DropdownSeparator />
                        {filter.options.map((option) => (
                            <DropdownItem
                                key={option.value}
                                onSelect={() =>
                                    setValue(
                                        filter.id,
                                        values[filter.id] === option.value ? undefined : option.value,
                                    )
                                }
                            >
                                {option.label}
                            </DropdownItem>
                        ))}
                    </DropdownMenu>
                ))}

                {actions && <div className="ml-auto flex items-center gap-2">{actions}</div>}
            </div>

            {active.length > 0 && (
                <ul className="flex flex-wrap items-center gap-2" aria-label="Active filters">
                    {active.map(({ filter, option }) => (
                        <li key={filter.id}>
                            <button
                                type="button"
                                onClick={() => setValue(filter.id, undefined)}
                                aria-label={`Remove filter ${filter.label}: ${option.label}`}
                                className="inline-flex items-center gap-1.5 rounded-full border border-[var(--color-border)] bg-[var(--color-surface-sunken)] py-1 pr-1.5 pl-2.5 text-[length:var(--text-xs)] text-[var(--color-text)] hover:bg-[var(--color-surface-hover)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)]"
                            >
                                <span className="text-[var(--color-text-muted)]">{filter.label}:</span>
                                {option.label}
                                <X className="h-3 w-3" aria-hidden />
                            </button>
                        </li>
                    ))}
                    <li>
                        <Button variant="link" size="xs" onClick={clearAll}>
                            Clear all
                        </Button>
                    </li>
                </ul>
            )}
        </div>
    );
}
