import type { SVGProps } from 'react';
import { cn } from '../lib/cn';

export interface SpinnerProps extends Omit<SVGProps<SVGSVGElement>, 'children'> {
    size?: 'xs' | 'sm' | 'md' | 'lg';
    /** Accessible label. Omit (and pass `aria-hidden`) when purely decorative. */
    label?: string;
}

const sizes = {
    xs: 'h-3 w-3',
    sm: 'h-4 w-4',
    md: 'h-5 w-5',
    lg: 'h-8 w-8',
} as const;

export function Spinner({ size = 'md', label, className, ...props }: SpinnerProps) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            role={label ? 'status' : undefined}
            aria-label={label}
            className={cn('animate-spin', sizes[size], className)}
            {...props}
        >
            <circle cx="12" cy="12" r="9" stroke="currentColor" strokeOpacity="0.2" strokeWidth="2.5" />
            <path
                d="M21 12a9 9 0 0 0-9-9"
                stroke="currentColor"
                strokeWidth="2.5"
                strokeLinecap="round"
            />
        </svg>
    );
}
