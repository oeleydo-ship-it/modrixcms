import { X } from 'lucide-react';
import { useEffect, useId, useRef, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { cn } from '../lib/cn';
import { useFocusTrap } from '../hooks/useFocusTrap';
import { useLockBodyScroll } from '../hooks/useLockBodyScroll';
import { Button } from './Button';

export type ModalSize = 'sm' | 'md' | 'lg' | 'xl' | 'full';

export interface ModalProps {
    open: boolean;
    onClose: () => void;
    title: ReactNode;
    description?: ReactNode;
    children?: ReactNode;
    footer?: ReactNode;
    size?: ModalSize;
    /** Set false for destructive flows that must be dismissed deliberately. */
    dismissOnOverlayClick?: boolean;
    className?: string;
}

const sizes: Record<ModalSize, string> = {
    sm: 'max-w-sm',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
    full: 'max-w-[calc(100vw-2rem)] h-[calc(100vh-2rem)]',
};

/**
 * Modal dialog: focus trapped, Escape-dismissible, body scroll locked, labelled
 * by its title and described by its optional description.
 */
export function Modal({
    open,
    onClose,
    title,
    description,
    children,
    footer,
    size = 'md',
    dismissOnOverlayClick = true,
    className,
}: ModalProps) {
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
                event.stopPropagation();
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
        <div
            className="fixed inset-0 z-[var(--z-modal)] flex items-center justify-center p-4"
            onMouseDown={(event) => {
                if (dismissOnOverlayClick && event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <div className="fnl-animate-in absolute inset-0 bg-[var(--color-overlay)]" aria-hidden />
            <div
                ref={panelRef}
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                aria-describedby={description ? descriptionId : undefined}
                tabIndex={-1}
                className={cn(
                    'fnl-animate-scale-in relative flex max-h-[calc(100dvh-2rem)] w-full flex-col overflow-hidden',
                    'rounded-xl border border-[var(--color-border)] bg-[var(--color-surface-raised)] shadow-[var(--shadow-xl)]',
                    sizes[size],
                    className,
                )}
            >
                <header className="flex items-start justify-between gap-4 border-b border-[var(--color-border)] px-5 py-4">
                    <div className="min-w-0">
                        <h2
                            id={titleId}
                            className="text-[length:var(--text-lg)] font-semibold text-[var(--color-text)]"
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
                    <Button variant="ghost" size="sm" iconOnly aria-label="Close dialog" onClick={onClose}>
                        <X className="h-4 w-4" />
                    </Button>
                </header>

                {children && <div className="min-h-0 flex-1 overflow-y-auto px-5 py-4">{children}</div>}

                {footer && (
                    <footer className="flex items-center justify-end gap-2 border-t border-[var(--color-border)] bg-[var(--color-surface-sunken)] px-5 py-3">
                        {footer}
                    </footer>
                )}
            </div>
        </div>,
        document.body,
    );
}

export interface ConfirmModalProps {
    open: boolean;
    onCancel: () => void;
    onConfirm: () => void;
    title: ReactNode;
    description?: ReactNode;
    confirmLabel?: string;
    cancelLabel?: string;
    tone?: 'primary' | 'danger';
    loading?: boolean;
}

/** The one-question confirm used by delete/publish flows. */
export function ConfirmModal({
    open,
    onCancel,
    onConfirm,
    title,
    description,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    tone = 'primary',
    loading = false,
}: ConfirmModalProps) {
    return (
        <Modal
            open={open}
            onClose={onCancel}
            title={title}
            description={description}
            size="sm"
            dismissOnOverlayClick={!loading}
            footer={
                <>
                    <Button variant="secondary" onClick={onCancel} disabled={loading}>
                        {cancelLabel}
                    </Button>
                    <Button variant={tone} onClick={onConfirm} loading={loading}>
                        {confirmLabel}
                    </Button>
                </>
            }
        />
    );
}
