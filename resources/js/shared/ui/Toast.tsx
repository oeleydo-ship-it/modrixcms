import { CircleCheck, CircleX, Info, TriangleAlert, X } from 'lucide-react';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
    type ReactNode,
} from 'react';
import { createPortal } from 'react-dom';
import { cn } from '../lib/cn';
import { uid } from '../lib/id';

export type ToastTone = 'success' | 'error' | 'warning' | 'info';

export interface ToastAction {
    label: string;
    onClick: () => void;
}

export interface ToastOptions {
    title: ReactNode;
    description?: ReactNode;
    tone?: ToastTone;
    /** ms before auto-dismiss. `0` keeps it until dismissed. */
    duration?: number;
    action?: ToastAction;
}

interface ToastRecord extends Required<Pick<ToastOptions, 'title' | 'tone' | 'duration'>> {
    id: string;
    description?: ReactNode;
    action?: ToastAction;
}

export interface ToastApi {
    toast: (options: ToastOptions) => string;
    success: (title: ReactNode, description?: ReactNode) => string;
    error: (title: ReactNode, description?: ReactNode) => string;
    warning: (title: ReactNode, description?: ReactNode) => string;
    info: (title: ReactNode, description?: ReactNode) => string;
    dismiss: (id: string) => void;
    dismissAll: () => void;
}

const ToastContext = createContext<ToastApi | null>(null);

export function useToast(): ToastApi {
    const context = useContext(ToastContext);
    if (!context) {
        throw new Error('useToast() must be used inside a <ToastProvider>.');
    }
    return context;
}

const icons: Record<ToastTone, typeof Info> = {
    success: CircleCheck,
    error: CircleX,
    warning: TriangleAlert,
    info: Info,
};

const toneClasses: Record<ToastTone, string> = {
    success: 'text-[var(--color-success)]',
    error: 'text-[var(--color-danger)]',
    warning: 'text-[var(--color-warning)]',
    info: 'text-[var(--color-info)]',
};

export interface ToastProviderProps {
    children: ReactNode;
    /** Oldest toasts are dropped once the stack exceeds this. */
    max?: number;
}

/**
 * Toast host. Errors go into an `assertive` live region, everything else into a
 * `polite` one, so screen readers interrupt only when something failed.
 */
export function ToastProvider({ children, max = 4 }: ToastProviderProps) {
    const [toasts, setToasts] = useState<ToastRecord[]>([]);
    const timers = useRef(new Map<string, number>());

    const dismiss = useCallback((id: string) => {
        const timer = timers.current.get(id);
        if (timer) {
            window.clearTimeout(timer);
            timers.current.delete(id);
        }
        setToasts((current) => current.filter((item) => item.id !== id));
    }, []);

    const dismissAll = useCallback(() => {
        timers.current.forEach((timer) => window.clearTimeout(timer));
        timers.current.clear();
        setToasts([]);
    }, []);

    const toast = useCallback(
        (options: ToastOptions) => {
            const record: ToastRecord = {
                id: uid('toast'),
                title: options.title,
                description: options.description,
                tone: options.tone ?? 'info',
                duration: options.duration ?? (options.tone === 'error' ? 8000 : 5000),
                action: options.action,
            };

            setToasts((current) => [...current, record].slice(-max));

            if (record.duration > 0) {
                timers.current.set(
                    record.id,
                    window.setTimeout(() => dismiss(record.id), record.duration),
                );
            }

            return record.id;
        },
        [dismiss, max],
    );

    useEffect(() => {
        const map = timers.current;
        return () => {
            map.forEach((timer) => window.clearTimeout(timer));
            map.clear();
        };
    }, []);

    const api = useMemo<ToastApi>(
        () => ({
            toast,
            success: (title, description) => toast({ title, description, tone: 'success' }),
            error: (title, description) => toast({ title, description, tone: 'error' }),
            warning: (title, description) => toast({ title, description, tone: 'warning' }),
            info: (title, description) => toast({ title, description, tone: 'info' }),
            dismiss,
            dismissAll,
        }),
        [toast, dismiss, dismissAll],
    );

    return (
        <ToastContext.Provider value={api}>
            {children}
            {typeof document !== 'undefined' &&
                createPortal(
                    <div className="pointer-events-none fixed inset-0 z-[var(--z-toast)] flex flex-col items-end justify-start gap-2 p-4 sm:p-6">
                        <div role="region" aria-label="Notifications" className="contents">
                            <ol aria-live="polite" aria-atomic="false" className="contents">
                                {toasts
                                    .filter((item) => item.tone !== 'error')
                                    .map((item) => (
                                        <ToastItem key={item.id} toast={item} onDismiss={dismiss} />
                                    ))}
                            </ol>
                            <ol aria-live="assertive" aria-atomic="false" className="contents">
                                {toasts
                                    .filter((item) => item.tone === 'error')
                                    .map((item) => (
                                        <ToastItem key={item.id} toast={item} onDismiss={dismiss} />
                                    ))}
                            </ol>
                        </div>
                    </div>,
                    document.body,
                )}
        </ToastContext.Provider>
    );
}

function ToastItem({ toast, onDismiss }: { toast: ToastRecord; onDismiss: (id: string) => void }) {
    const Icon = icons[toast.tone];

    return (
        <li
            className={cn(
                'fnl-animate-scale-in pointer-events-auto flex w-full max-w-sm items-start gap-3',
                'rounded-lg border border-[var(--color-border)] bg-[var(--color-surface-raised)] p-3.5',
                'shadow-[var(--shadow-lg)]',
            )}
        >
            <Icon className={cn('mt-0.5 h-4.5 w-4.5 shrink-0', toneClasses[toast.tone])} aria-hidden />
            <div className="min-w-0 flex-1">
                <p className="text-[length:var(--text-base)] font-medium text-[var(--color-text)]">
                    {toast.title}
                </p>
                {toast.description && (
                    <p className="mt-0.5 text-[length:var(--text-sm)] text-[var(--color-text-muted)]">
                        {toast.description}
                    </p>
                )}
                {toast.action && (
                    <button
                        type="button"
                        onClick={() => {
                            toast.action?.onClick();
                            onDismiss(toast.id);
                        }}
                        className="mt-2 text-[length:var(--text-sm)] font-medium text-[var(--color-text-brand)] underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)]"
                    >
                        {toast.action.label}
                    </button>
                )}
            </div>
            <button
                type="button"
                aria-label="Dismiss notification"
                onClick={() => onDismiss(toast.id)}
                className="-mt-0.5 -mr-0.5 rounded p-1 text-[var(--color-text-subtle)] hover:bg-[var(--color-surface-hover)] hover:text-[var(--color-text)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)]"
            >
                <X className="h-3.5 w-3.5" aria-hidden />
            </button>
        </li>
    );
}
