import { cloneElement, useEffect, useId, useRef, useState, type ReactElement, type ReactNode } from 'react';
import { cn } from '../lib/cn';

export type TooltipSide = 'top' | 'bottom' | 'left' | 'right';

export interface TooltipProps {
    content: ReactNode;
    side?: TooltipSide;
    delay?: number;
    /** Single focusable child; it receives the aria wiring and event handlers. */
    children: ReactElement<Record<string, unknown>>;
    className?: string;
    disabled?: boolean;
}

const sideClasses: Record<TooltipSide, string> = {
    top: 'bottom-full left-1/2 -translate-x-1/2 mb-1.5',
    bottom: 'top-full left-1/2 -translate-x-1/2 mt-1.5',
    left: 'right-full top-1/2 -translate-y-1/2 mr-1.5',
    right: 'left-full top-1/2 -translate-y-1/2 ml-1.5',
};

/**
 * Hover *and* focus triggered, dismissible with Escape, and wired through
 * `aria-describedby` so screen readers announce it with the control.
 */
export function Tooltip({ content, side = 'top', delay = 250, children, className, disabled }: TooltipProps) {
    const id = useId();
    const [open, setOpen] = useState(false);
    const timer = useRef<number | undefined>(undefined);

    useEffect(() => () => window.clearTimeout(timer.current), []);

    useEffect(() => {
        if (!open) {
            return;
        }
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };
        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
    }, [open]);

    if (disabled) {
        return children;
    }

    const show = () => {
        window.clearTimeout(timer.current);
        timer.current = window.setTimeout(() => setOpen(true), delay);
    };
    const hide = () => {
        window.clearTimeout(timer.current);
        setOpen(false);
    };

    const trigger = cloneElement(children, {
        'aria-describedby': open ? id : undefined,
        onMouseEnter: show,
        onMouseLeave: hide,
        onFocus: show,
        onBlur: hide,
    });

    return (
        <span className="relative inline-flex">
            {trigger}
            <span
                id={id}
                role="tooltip"
                hidden={!open}
                className={cn(
                    'pointer-events-none absolute z-[var(--z-tooltip)] max-w-56 rounded-md px-2 py-1',
                    'bg-[var(--color-neutral-900)] text-[length:var(--text-xs)] text-white shadow-[var(--shadow-md)]',
                    'fnl-animate-in text-center whitespace-normal',
                    sideClasses[side],
                    className,
                )}
            >
                {content}
            </span>
        </span>
    );
}
