import { X } from 'lucide-react';
import { useEffect, useId, useRef, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { cn } from '../lib/cn';
import { useFocusTrap } from '../hooks/useFocusTrap';
import { useLockBodyScroll } from '../hooks/useLockBodyScroll';
import { Button } from './Button';

export type DrawerSide = 'right' | 'left' | 'bottom';

export interface DrawerProps {
    open: boolean;
    onClose: () => void;
    title: ReactNode;
    description?: ReactNode;
    children?: ReactNode;
    footer?: ReactNode;
    side?: DrawerSide;
    width?: 'sm' | 'md' | 'lg';
    className?: string;
}

const widths = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-xl',
} as const;

const sides: Record<DrawerSide, string> = {
    right: 'inset-y-0 right-0 h-full w-full border-l',
    left: 'inset-y-0 left-0 h-full w-full border-r',
    bottom: 'inset-x-0 bottom-0 max-h-[85vh] w-full rounded-t-xl border-t',
};

/**
 * Side sheet used for record detail panes (contact drawer, block settings).
 * Same dialog semantics as Modal — trapped, escapable, scroll-locked.
 */
export function Drawer({
    open,
    onClose,
    title,
    description,
    children,
    footer,
    side = 'right',
    width = 'md',
    className,
}: DrawerProps) {
    const titleId = useId();
    const descriptionId = useId();
    const panelRef = useRef<HTMLDivElement>(null);

    useLockBodyScroll(open);
    useFocusTrap(panelRef, open);

    useEffect(() => {
        if (!open) {
            return;
        }
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };
        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
    }, [open, onClose]);

    if (!open || typeof document === 'undefined') {
        return null;
    }

    return createPortal(
        <div className="fixed inset-0 z-[var(--z-drawer)]">
            <button
                type="button"
                aria-label="Close panel"
                onClick={onClose}
                className="fnl-animate-in absolute inset-0 h-full w-full cursor-default bg-[var(--color-overlay)]"
            />
            <div
                ref={panelRef}
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                aria-describedby={description ? descriptionId : undefined}
                tabIndex={-1}
                className={cn(
                    'fnl-animate-in absolute flex flex-col border-[var(--color-border)]',
                    'bg-[var(--color-surface-raised)] shadow-[var(--shadow-xl)]',
                    sides[side],
                    side !== 'bottom' && widths[width],
                    className,
                )}
            >
                <header className="flex items-start justify-between gap-4 border-b border-[var(--color-border)] px-5 py-4">
                    <div className="min-w-0">
                        <h2
                            id={titleId}
                            className="truncate text-[length:var(--text-lg)] font-semibold text-[var(--color-text)]"
                        >
                            {title}
                        </h2>
                        {description && (
                            <p
                                id={descriptionId}
                                className="mt-1 text-[length:var(--text-sm)] text-[var(--color-text-muted)]"
                            >
                                {description}
                            </p>
                        )}
                    </div>
                    <Button variant="ghost" size="sm" iconOnly aria-label="Close panel" onClick={onClose}>
                        <X className="h-4 w-4" />
                    </Button>
                </header>

                <div className="min-h-0 flex-1 overflow-y-auto px-5 py-4">{children}</div>

                {footer && (
                    <footer className="flex items-center justify-end gap-2 border-t border-[var(--color-border)] px-5 py-3">
                        {footer}
                    </footer>
                )}
            </div>
        </div>,
        document.body,
    );
}
