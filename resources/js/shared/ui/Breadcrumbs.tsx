import { ChevronRight } from 'lucide-react';
import { Fragment, type ReactNode } from 'react';
import { cn } from '../lib/cn';

export interface Crumb {
    label: ReactNode;
    href?: string;
    onClick?: () => void;
}

export interface BreadcrumbsProps {
    items: readonly Crumb[];
    className?: string;
    /** Router-aware link component; falls back to a plain <a>. */
    linkComponent?: (props: { href: string; children: ReactNode; className: string }) => ReactNode;
}

export function Breadcrumbs({ items, className, linkComponent }: BreadcrumbsProps) {
    const linkClass =
        'rounded text-[var(--color-text-muted)] hover:text-[var(--color-text)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)]';

    return (
        <nav aria-label="Breadcrumb" className={cn('min-w-0', className)}>
            <ol className="flex flex-wrap items-center gap-1.5 text-[length:var(--text-sm)]">
                {items.map((item, index) => {
                    const isLast = index === items.length - 1;
                    return (
                        <Fragment key={index}>
                            <li className="flex min-w-0 items-center">
                                {isLast ? (
                                    <span
                                        aria-current="page"
                                        className="truncate font-medium text-[var(--color-text)]"
                                    >
                                        {item.label}
                                    </span>
                                ) : item.href ? (
                                    linkComponent ? (
                                        linkComponent({
                                            href: item.href,
                                            children: item.label,
                                            className: linkClass,
                                        })
                                    ) : (
                                        <a href={item.href} className={linkClass}>
                                            {item.label}
                                        </a>
                                    )
                                ) : (
                                    <button type="button" onClick={item.onClick} className={linkClass}>
                                        {item.label}
                                    </button>
                                )}
                            </li>
                            {!isLast && (
                                <li aria-hidden className="text-[var(--color-text-subtle)]">
                                    <ChevronRight className="h-3.5 w-3.5" />
                                </li>
                            )}
                        </Fragment>
                    );
                })}
            </ol>
        </nav>
    );
}
