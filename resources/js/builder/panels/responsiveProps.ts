/**
 * Routes a block's layout props to the active breakpoint.
 *
 * On desktop every prop is written to the block as before. On tablet and
 * mobile, the layout props a block type supports (see RESPONSIVE_PROP_VARS)
 * are written to that tier of a `--fnl-p-*` style variable instead, so the
 * phone gets its own gallery columns or spacer height while the text, images
 * and links stay shared by every screen.
 */

import {
    columnsFromGrid,
    gridForColumns,
    propVar,
    valueAt,
    withBreakpoint,
    type BlockData,
    type BlockType,
    type Breakpoint,
    type StyleData,
} from '@renderer';

/** Prop key -> the variable key the renderer reads, per block type. */
const FIELDS: Partial<Record<BlockType, Record<string, string>>> = {
    container: { gap: 'gap' },
    gallery: { columns: 'grid', gap: 'gap' },
    feature_cards: { columns: 'grid' },
    pricing_table: { columns: 'grid' },
    spacer: { height: 'height' },
    divider: { width: 'width', thickness: 'thickness' },
    icon: { size: 'size' },
    social_icons: { size: 'size' },
};

export const RESPONSIVE_FIELD_LABELS: Record<string, string> = {
    columns: 'columns',
    gap: 'gap',
    height: 'height',
    width: 'width',
    thickness: 'thickness',
    size: 'size',
};

export function responsiveFieldsFor(type: BlockType): string[] {
    return Object.keys(FIELDS[type] ?? {});
}

/** The props as the active tier sees them: tier overrides laid over the shared props. */
export function propsAt(block: BlockData, breakpoint: Breakpoint): Record<string, unknown> {
    const fields = FIELDS[block.type];
    if (!fields || breakpoint === 'desktop') {
        return block.props;
    }
    const props = { ...block.props };
    for (const [prop, key] of Object.entries(fields)) {
        const value = valueAt(block.style?.custom?.[propVar(key)], breakpoint);
        if (value === null || value === undefined || value === '') {
            continue;
        }
        props[prop] = key === 'grid' ? (columnsFromGrid(value) ?? props[prop]) : value;
    }
    return props;
}

/** Which layout props carry their own value on this tier. */
export function overriddenFields(block: BlockData, breakpoint: Breakpoint): string[] {
    const fields = FIELDS[block.type];
    if (!fields || breakpoint === 'desktop') {
        return [];
    }
    return Object.entries(fields)
        .filter(([, key]) => {
            const own = block.style?.custom?.[propVar(key)]?.[breakpoint];
            return own !== null && own !== undefined && own !== '';
        })
        .map(([prop]) => prop);
}

/**
 * Splits a props patch: layout props go to the tier's style variable, the rest
 * stay props. Returns the style to write, or null when nothing tier-specific changed.
 */
export function splitPropsPatch(
    block: BlockData,
    breakpoint: Breakpoint,
    patch: Record<string, unknown>,
): { props: Record<string, unknown>; style: StyleData | null } {
    const fields = FIELDS[block.type];
    if (!fields || breakpoint === 'desktop') {
        return { props: patch, style: null };
    }

    const props: Record<string, unknown> = {};
    let custom = { ...(block.style?.custom ?? {}) };
    let touched = false;

    for (const [prop, value] of Object.entries(patch)) {
        const key = fields[prop];
        if (!key) {
            props[prop] = value;
            continue;
        }
        const empty = value === null || value === undefined || value === '' || (typeof value === 'number' && !Number.isFinite(value));
        const css = empty ? null : key === 'grid' ? gridForColumns(Number(value)) : String(value);
        custom = { ...custom, [propVar(key)]: withBreakpoint(custom[propVar(key)], breakpoint, css) };
        if (Object.keys(custom[propVar(key)] ?? {}).length === 0) {
            delete custom[propVar(key)];
        }
        touched = true;
    }

    return { props, style: touched ? { ...(block.style ?? {}), custom } : null };
}

/** Removes this tier's layout overrides so it follows the wider screen again. */
export function resetTierFields(block: BlockData, breakpoint: Breakpoint): StyleData {
    const patch = Object.fromEntries(overriddenFields(block, breakpoint).map((prop) => [prop, null]));
    return splitPropsPatch(block, breakpoint, patch).style ?? block.style ?? {};
}
