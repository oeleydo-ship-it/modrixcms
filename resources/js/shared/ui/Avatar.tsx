import { useState } from 'react';
import { cn } from '../lib/cn';

export interface AvatarProps {
    name: string;
    src?: string | null;
    size?: 'xs' | 'sm' | 'md' | 'lg';
    className?: string;
    /** Presence ring, e.g. for team members currently editing a funnel. */
    status?: 'online' | 'offline' | null;
}

const sizes = {
    xs: 'h-6 w-6 text-[length:var(--text-2xs)]',
    sm: 'h-8 w-8 text-[length:var(--text-xs)]',
    md: 'h-10 w-10 text-[length:var(--text-sm)]',
    lg: 'h-14 w-14 text-[length:var(--text-md)]',
} as const;

export function initialsFrom(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) {
        return '?';
    }
    if (parts.length === 1) {
        return parts[0]!.slice(0, 2).toUpperCase();
    }
    return `${parts[0]![0] ?? ''}${parts[parts.length - 1]![0] ?? ''}`.toUpperCase();
}

/** Deterministic tint so the same person always gets the same colour. */
function hueFrom(name: string): number {
    let hash = 0;
    for (let i = 0; i < name.length; i += 1) {
        hash = (hash * 31 + name.charCodeAt(i)) % 360;
    }
    return hash;
}

export function Avatar({ name, src, size = 'md', className, status = null }: AvatarProps) {
    const [failed, setFailed] = useState(false);
    const showImage = Boolean(src) && !failed;

    return (
        <span className={cn('relative inline-flex shrink-0', className)}>
            {showImage ? (
                <img
                    src={src ?? undefined}
                    alt={name}
                    onError={() => setFailed(true)}
                    className={cn('rounded-full object-cover', sizes[size])}
                />
            ) : (
                <span
                    role="img"
                    aria-label={name}
                    style={{
                        backgroundColor: `hsl(${hueFrom(name)} 62% 92%)`,
                        color: `hsl(${hueFrom(name)} 52% 30%)`,
                    }}
                    className={cn(
                        'flex items-center justify-center rounded-full font-semibold select-none',
                        sizes[size],
                    )}
                >
                    {initialsFrom(name)}
                </span>
            )}
            {status && (
                <span
                    aria-hidden
                    className={cn(
                        'absolute right-0 bottom-0 h-2.5 w-2.5 rounded-full ring-2 ring-[var(--color-surface)]',
                        status === 'online' ? 'bg-[var(--color-success)]' : 'bg-[var(--color-border-strong)]',
                    )}
                />
            )}
        </span>
    );
}

export interface AvatarGroupProps {
    people: ReadonlyArray<{ name: string; src?: string | null }>;
    max?: number;
    size?: AvatarProps['size'];
    className?: string;
}

export function AvatarGroup({ people, max = 4, size = 'sm', className }: AvatarGroupProps) {
    const shown = people.slice(0, max);
    const overflow = people.length - shown.length;

    return (
        <div className={cn('flex items-center -space-x-2', className)}>
            {shown.map((person) => (
                <Avatar
                    key={person.name}
                    name={person.name}
                    src={person.src}
                    size={size}
                    className="ring-2 ring-[var(--color-surface)]"
                />
            ))}
            {overflow > 0 && (
                <span
                    className={cn(
                        'flex items-center justify-center rounded-full bg-[var(--color-surface-sunken)] font-medium text-[var(--color-text-muted)] ring-2 ring-[var(--color-surface)]',
                        sizes[size],
                    )}
                >
                    +{overflow}
                </span>
            )}
        </div>
    );
}
