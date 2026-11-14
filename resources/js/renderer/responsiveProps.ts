/**
 * Layout props that can differ per breakpoint.
 *
 * A block's props are its content and are shared by every screen size: a
 * heading says the same thing on a phone. A handful of props are layout
 * rather than content, though (how many gallery columns, a spacer's height,
 * a container's gap), and those need a phone value of their own.
 *
 * The prop keeps the desktop value, so documents without overrides render
 * exactly as before. A tablet or mobile value lives in the block's style as a
 * `--fnl-p-<key>` custom property, which the normal responsive style emitter
 * already writes into the right media query. The block reads the variable with
 * the prop as its fallback.
 */

import { BREAKPOINTS, type BlockType, type Breakpoint, type StyleData } from './types';
import { resolveStyle } from './style';

/** The CSS variable keys each block type reads. */
export const RESPONSIVE_PROP_VARS: Partial<Record<BlockType, readonly string[]>> = {
    container: ['gap'],
    gallery: ['grid', 'gap'],
    feature_cards: ['grid'],
    pricing_table: ['grid'],
    spacer: ['height'],
    divider: ['width', 'thickness'],
    icon: ['size'],
    social_icons: ['size'],
};

export function propVar(key: string): string {
    return `--fnl-p-${key}`;
}

/** `var(--fnl-p-key, fallback)` for use in a block's inline style. */
export function responsiveProp(key: string, fallback: string | number): string {
    return `var(${propVar(key)}, ${fallback})`;
}

/** The grid track list a "columns" setting stands for. */
export function gridForColumns(columns: number): string {
    return `repeat(${Math.max(1, Math.round(columns))}, minmax(0, 1fr))`;
}

export function columnsFromGrid(value: unknown): number | null {
    const match = typeof value === 'string' ? /^repeat\((\d+)/.exec(value) : null;
    return match ? Number(match[1]) : null;
}

/**
 * Custom properties inherit, so a gallery inside a container would pick up the
 * container's mobile gap. Every tier where a block has no value of its own
 * resets the variable, which makes the block fall back to its own prop.
 */
export function responsivePropReset(type: BlockType, style: StyleData | null | undefined): Partial<Record<Breakpoint, Record<string, string>>> | undefined {
    const keys = RESPONSIVE_PROP_VARS[type];
    if (!keys) {
        return undefined;
    }

    const extra: Partial<Record<Breakpoint, Record<string, string>>> = {};
    for (const breakpoint of BREAKPOINTS) {
        const resolved = resolveStyle(style, breakpoint);
        const reset: Record<string, string> = {};
        for (const key of keys) {
            if (!(propVar(key) in resolved)) {
                reset[propVar(key)] = 'initial';
            }
        }
        extra[breakpoint] = reset;
    }
    return extra;
}
