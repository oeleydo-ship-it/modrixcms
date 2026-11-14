import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useId,
    useMemo,
    useRef,
    useState,
    type KeyboardEvent as ReactKeyboardEvent,
    type ReactNode,
} from 'react';
import { cn } from '../lib/cn';
import { useOnClickOutside } from '../hooks/useOnClickOutside';

type Align = 'start' | 'end';

interface DropdownContextValue {
    open: boolean;
    setOpen: (open: boolean) => void;
    menuId: string;
    triggerId: string;
    registerItem: (node: HTMLElement | null) => void;
}

const DropdownContext = createContext<DropdownContextValue | null>(null);

function useDropdown(component: string): DropdownContextValue {
    const context = useContext(DropdownContext);
    if (!context) {
        throw new Error(`<${component}> must be rendered inside <DropdownMenu>.`);
    }
    return context;
}

export interface DropdownMenuProps {
    /** Render prop so the trigger keeps its own component identity. */
    trigger: (props: {
        ref: (node: HTMLButtonElement | null) => void;
        onClick: () => void;
        onKeyDown: (event: ReactKeyboardEvent) => void;
        'aria-haspopup': 'menu';
        'aria-expanded': boolean;
        'aria-controls': string;
        id: string;
    }) => ReactNode;
    children: ReactNode;
    align?: Align;
    /** Menu width; defaults to intrinsic content width with a sane minimum. */
    className?: string;
    onOpenChange?: (open: boolean) => void;
}

/**
 * WAI-ARIA menu button. Enter/Space/ArrowDown open it, arrows move between
 * items, Home/End jump, Escape closes and returns focus to the trigger, and a
 * click outside dismisses.
 */
export function DropdownMenu({ trigger, children, align = 'start', className, onOpenChange }: DropdownMenuProps) {
    const menuId = useId();
    const triggerId = useId();
    const [open, setOpenState] = useState(false);
    const triggerRef = useRef<HTMLButtonElement | null>(null);
    const menuRef = useRef<HTMLDivElement | null>(null);
    const itemsRef = useRef<HTMLElement[]>([]);

    const setOpen = useCallback(
        (next: boolean) => {
            setOpenState(next);
            onOpenChange?.(next);
            if (!next) {
                triggerRef.current?.focus();
            }
        },
        [onOpenChange],
    );

    useOnClickOutside([menuRef, triggerRef], () => setOpenState(false), open);

    const registerItem = useCallback((node: HTMLElement | null) => {
        if (node && !itemsRef.current.includes(node)) {
            itemsRef.current.push(node);
        }
    }, []);

    // Reset the item registry whenever the menu closes so stale nodes go away.
    useEffect(() => {
        if (!open) {
            itemsRef.current = [];
        }
    }, [open]);

    useEffect(() => {
        if (!open) {
            return;
        }
        const frame = window.requestAnimationFrame(() => {
            const items = menuRef.current ? enabledItems(menuRef.current) : [];
            items[0]?.focus();
        });
        return () => window.cancelAnimationFrame(frame);
    }, [open]);

    const onMenuKeyDown = (event: ReactKeyboardEvent<HTMLDivElement>) => {
        const items = menuRef.current ? enabledItems(menuRef.current) : [];
        const index = items.indexOf(document.activeElement as HTMLElement);

        switch (event.key) {
            case 'Escape':
                event.preventDefault();
                setOpen(false);
                break;
            case 'ArrowDown':
                event.preventDefault();
                items[(index + 1) % Math.max(items.length, 1)]?.focus();
                break;
            case 'ArrowUp':
                event.preventDefault();
                items[(index - 1 + items.length) % Math.max(items.length, 1)]?.focus();
                break;
            case 'Home':
                event.preventDefault();
                items[0]?.focus();
                break;
            case 'End':
                event.preventDefault();
                items[items.length - 1]?.focus();
                break;
            case 'Tab':
                setOpenState(false);
                break;
            default:
                break;
        }
    };

    const onTriggerKeyDown = (event: ReactKeyboardEvent) => {
        if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            setOpenState(true);
        }
    };

    const context = useMemo<DropdownContextValue>(
        () => ({ open, setOpen, menuId, triggerId, registerItem }),
        [open, setOpen, menuId, triggerId, registerItem],
    );

    return (
        <DropdownContext.Provider value={context}>
            <div className="relative inline-flex">
                {trigger({
                    ref: (node) => {
                        triggerRef.current = node;
                    },
                    onClick: () => setOpenState(!open),
                    onKeyDown: onTriggerKeyDown,
                    'aria-haspopup': 'menu',
                    'aria-expanded': open,
                    'aria-controls': menuId,
                    id: triggerId,
                })}
                {open && (
                    <div
                        ref={menuRef}
                        id={menuId}
                        role="menu"
                        aria-labelledby={triggerId}
                        onKeyDown={onMenuKeyDown}
                        className={cn(
                            'fnl-animate-scale-in absolute top-full z-[var(--z-dropdown)] mt-1.5 min-w-52',
                            'rounded-lg border border-[var(--color-border)] bg-[var(--color-surface-raised)] p-1',
                            'shadow-[var(--shadow-lg)]',
                            align === 'end' ? 'right-0' : 'left-0',
                            className,
                        )}
                    >
                        {children}
                    </div>
                )}
            </div>
        </DropdownContext.Provider>
    );
}

function enabledItems(menu: HTMLElement): HTMLElement[] {
    return Array.from(menu.querySelectorAll<HTMLElement>('[role="menuitem"]:not([aria-disabled="true"])'));
}

export interface DropdownItemProps {
    children: ReactNode;
    onSelect?: () => void;
    icon?: ReactNode;
    /** Right-aligned hint, typically a keyboard shortcut. */
    shortcut?: string;
    disabled?: boolean;
    tone?: 'default' | 'danger';
    className?: string;
}

export function DropdownItem({
    children,
    onSelect,
    icon,
    shortcut,
    disabled = false,
    tone = 'default',
    className,
}: DropdownItemProps) {
    const { setOpen, registerItem } = useDropdown('DropdownItem');

    return (
        <button
            type="button"
            role="menuitem"
            ref={registerItem}
            tabIndex={-1}
            aria-disabled={disabled || undefined}
            disabled={disabled}
            onClick={() => {
                if (disabled) {
                    return;
                }
                onSelect?.();
                setOpen(false);
            }}
            className={cn(
                'flex w-full items-center gap-2.5 rounded-md px-2.5 py-1.5 text-left',
                'text-[length:var(--text-base)] text-[var(--color-text)]',
                'hover:bg-[var(--color-surface-hover)] focus-visible:bg-[var(--color-surface-hover)]',
                'focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                tone === 'danger' && 'text-[var(--color-danger)]',
                className,
            )}
        >
            {icon && (
                <span className="shrink-0 text-[var(--color-text-subtle)]" aria-hidden>
                    {icon}
                </span>
            )}
            <span className="flex-1 truncate">{children}</span>
            {shortcut && (
                <kbd className="font-mono text-[length:var(--text-2xs)] text-[var(--color-text-subtle)]">
                    {shortcut}
                </kbd>
            )}
        </button>
    );
}

export function DropdownSeparator() {
    return <div role="separator" className="my-1 h-px bg-[var(--color-border)]" />;
}

export function DropdownLabel({ children }: { children: ReactNode }) {
    return (
        <div className="px-2.5 py-1.5 text-[length:var(--text-2xs)] font-semibold tracking-wide text-[var(--color-text-subtle)] uppercase">
            {children}
        </div>
    );
}
