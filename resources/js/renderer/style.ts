/**
 * Responsive style resolution.
 *
 * Mirrors `ResponsiveValue::at()` and `StyleData::resolve()`: every style value
 * carries a desktop base plus optional tablet/mobile overrides that cascade
 * downwards. The renderer turns the three resolved tiers into one CSS rule plus
 * two `max-width` media queries, publishing each value as a custom property
 * (`--fnl-<prop>`) alongside the real declaration so block internals and the
 * builder can read the effective value back out of the cascade.
 */

import {
    BREAKPOINT_CASCADE,
    BREAKPOINT_MAX_WIDTH,
    BREAKPOINTS,
    COLUMN_GRID,
    STYLE_PROPERTIES,
    type BorderValue,
    type BoxValue,
    type Breakpoint,
    type ColumnSpanData,
    type RadiusValue,
    type ResponsiveValue,
    type StyleData,
    type StyleProperty,
    type VisibilityData,
} from './types';

/* -------------------------------------------------------------------------- */
/* Value resolution                                                            */
/* -------------------------------------------------------------------------- */

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

/**
 * Accepts either a bare scalar (treated as the desktop value) or the full
 * per-breakpoint map, so hand-written templates stay readable.
 */
export function toResponsive(value: unknown): ResponsiveValue {
    if (value === null || value === undefined) {
        return {};
    }
    if (isRecord(value)) {
        const keys = Object.keys(value);
        const looksLikeMap =
            keys.length > 0 && keys.every((key) => key === 'desktop' || key === 'tablet' || key === 'mobile');
        if (looksLikeMap) {
            return value as ResponsiveValue;
        }
    }
    return { desktop: value };
}

/** The effective value at a breakpoint, walking the inheritance cascade. */
export function valueAt(value: ResponsiveValue | null | undefined, breakpoint: Breakpoint): unknown {
    if (!value) {
        return null;
    }
    for (const candidate of BREAKPOINT_CASCADE[breakpoint]) {
        const found = value[candidate];
        if (found !== null && found !== undefined) {
            return found;
        }
    }
    return null;
}

/** True when the breakpoint carries its own value rather than an inherited one. */
export function isOverriddenAt(value: ResponsiveValue | null | undefined, breakpoint: Breakpoint): boolean {
    if (!value) {
        return false;
    }
    const own = value[breakpoint];
    return own !== null && own !== undefined;
}

export function isEmptyResponsive(value: ResponsiveValue | null | undefined): boolean {
    if (!value) {
        return true;
    }
    return BREAKPOINTS.every((breakpoint) => value[breakpoint] === null || value[breakpoint] === undefined);
}

/** Immutably set one breakpoint of a responsive value. */
export function withBreakpoint(
    value: ResponsiveValue | null | undefined,
    breakpoint: Breakpoint,
    next: unknown,
): ResponsiveValue {
    const base: ResponsiveValue = { ...(value ?? {}) };
    if (next === null || next === undefined || next === '') {
        delete base[breakpoint];
    } else {
        base[breakpoint] = next;
    }
    return base;
}

/* -------------------------------------------------------------------------- */
/* CSS serialisation                                                           */
/* -------------------------------------------------------------------------- */

const CSS_PROPERTY: Record<StyleProperty, string> = {
    fontFamily: 'font-family',
    fontSize: 'font-size',
    fontWeight: 'font-weight',
    fontStyle: 'font-style',
    lineHeight: 'line-height',
    letterSpacing: 'letter-spacing',
    textTransform: 'text-transform',
    textDecoration: 'text-decoration',
    color: 'color',
    backgroundColor: 'background-color',
    backgroundImage: 'background-image',
    backgroundSize: 'background-size',
    backgroundPosition: 'background-position',
    backgroundRepeat: 'background-repeat',
    backgroundAttachment: 'background-attachment',
    // The video is an element, not a paint: these carry the values to the
    // section component and never produce a CSS declaration of their own.
    backgroundVideo: '--fnl-background-video',
    backgroundVideoPoster: '--fnl-background-video-poster',
    backgroundImageOpacity: '--fnl-background-image-opacity',
    backgroundImageBrightness: '--fnl-background-image-brightness',
    backgroundImageContrast: '--fnl-background-image-contrast',
    backgroundImageSaturation: '--fnl-background-image-saturation',
    backgroundImageBlur: '--fnl-background-image-blur',
    overlayColor: '--fnl-overlay-color',
    overlayImage: '--fnl-overlay-image',
    overlayImageSize: '--fnl-overlay-image-size',
    overlayImagePosition: '--fnl-overlay-image-position',
    overlayImageOpacity: '--fnl-overlay-image-opacity',
    overlayBlendMode: '--fnl-overlay-blend-mode',
    border: 'border',
    borderRadius: 'border-radius',
    boxShadow: 'box-shadow',
    textShadow: 'text-shadow',
    padding: 'padding',
    margin: 'margin',
    width: 'width',
    minWidth: 'min-width',
    maxWidth: 'max-width',
    height: 'height',
    minHeight: 'min-height',
    maxHeight: 'max-height',
    display: 'display',
    flexDirection: 'flex-direction',
    alignItems: 'align-items',
    justifyContent: 'justify-content',
    textAlign: 'text-align',
    gap: 'gap',
    opacity: 'opacity',
    zIndex: 'z-index',
    transform: 'transform',
    transition: 'transition',
};

/**
 * Properties whose value is a length. A bare number typed into one of these
 * ("32") is invalid CSS and the browser silently drops the whole declaration,
 * which reads to the author as "the setting does nothing". Pixels are what
 * every placeholder suggests, so a unitless number is read as pixels. Line
 * height, weight, opacity and z-index are deliberately absent: a bare number is
 * the correct, meaningful value for those.
 */
const LENGTH_PROPERTIES: ReadonlySet<string> = new Set([
    'fontSize',
    'letterSpacing',
    'width',
    'minWidth',
    'maxWidth',
    'height',
    'minHeight',
    'maxHeight',
    'gap',
    'borderRadius',
    'padding',
    'margin',
    'border',
]);

const BARE_NUMBER = /^-?\d*\.?\d+$/;

/** "32" becomes "32px"; "0", "2rem", "50%" and "auto" pass through untouched. */
export function withUnit(value: string): string {
    const trimmed = value.trim();
    return BARE_NUMBER.test(trimmed) && Number(trimmed) !== 0 ? `${trimmed}px` : trimmed;
}

/** A CSS value may never terminate the declaration it lives in. */
function sanitiseCssValue(value: string): string {
    return value.replace(/[;{}<>]/g, '').trim();
}

function boxToCss(value: Record<string, unknown>, keys: readonly string[], fallback: string): string | null {
    const parts = keys.map((key) => {
        const part = value[key];
        if (typeof part === 'number') {
            return withUnit(String(part));
        }
        return typeof part === 'string' && part.trim() !== '' ? withUnit(sanitiseCssValue(part)) : fallback;
    });
    const declared = keys.some(key => typeof value[key] === 'number' || (typeof value[key] === 'string' && value[key].trim() !== ''));
    return declared ? parts.join(' ') : null;
}

function borderToCss(value: Record<string, unknown>): string | null {
    const sides = Object.values(borderLonghands(value));
    // A single border shorthand can only represent four equal sides.
    return sides.every((side) => side === sides[0]) ? sides[0] ?? null : null;
}

function borderLonghands(value: Record<string, unknown>): Record<string, string> {
    const border = value as BorderValue;
    const style = sanitiseCssValue(typeof border.style === 'string' && border.style ? border.style : 'solid');
    const color = sanitiseCssValue(typeof border.color === 'string' && border.color ? border.color : 'currentColor');
    return Object.fromEntries(['top', 'right', 'bottom', 'left'].map((side) => {
        const raw = value[side] ?? value.width;
        const width = typeof raw === 'number' ? withUnit(String(raw))
            : typeof raw === 'string' && raw.trim() ? withUnit(sanitiseCssValue(raw)) : '0';
        return [`border-${side}`, `${width} ${style} ${color}`];
    }));
}

/**
 * Turn one resolved style value into a CSS declaration value.
 * Returns `null` when the value cannot contribute anything.
 */
export function styleValueToCss(property: StyleProperty | string, value: unknown): string | null {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (typeof value === 'number') {
        return LENGTH_PROPERTIES.has(property) ? withUnit(String(value)) : String(value);
    }

    if (typeof value === 'string') {
        const sanitised = sanitiseCssValue(value);
        if (sanitised === '') {
            return null;
        }
        const clean = LENGTH_PROPERTIES.has(property) ? withUnit(sanitised) : sanitised;
        // Gradients are deliberately not part of the design system, so a
        // background image is a url() or nothing. Anything else is wrapped as a
        // url rather than passed through as a raw CSS function.
        if ((property === 'backgroundImage' || property === 'overlayImage') && !/^(url\(|none$)/i.test(clean)) {
            return `url("${clean.replace(/"/g, '')}")`;
        }
        return clean;
    }

    if (isRecord(value)) {
        if (property === 'border') {
            return borderToCss(value);
        }
        if (property === 'borderRadius') {
            const radius = value as RadiusValue;
            return boxToCss(
                { top: radius.topLeft, right: radius.topRight, bottom: radius.bottomRight, left: radius.bottomLeft },
                ['top', 'right', 'bottom', 'left'],
                '0',
            );
        }
        const box = value as BoxValue;
        if ('top' in box || 'right' in box || 'bottom' in box || 'left' in box) {
            return boxToCss(value, ['top', 'right', 'bottom', 'left'], '0');
        }
    }

    return null;
}

/** Every declared property, including `custom` keys. */
function styleProperties(style: StyleData | null | undefined): string[] {
    if (!style) {
        return [];
    }
    const declared = STYLE_PROPERTIES.filter((property) => !isEmptyResponsive(style[property]));
    const custom = Object.keys(style.custom ?? {}).filter((key) => !isEmptyResponsive(style.custom?.[key]));
    // Custom declarations can contain kebab-case aliases left by older
    // documents (for example `background-color`). Resolve them first so the
    // dedicated, editable style property wins when both map to the same CSS
    // declaration. Otherwise the inspector accepts a new row background while
    // the legacy custom value silently paints over it.
    return [...custom, ...declared];
}

function responsiveFor(style: StyleData, property: string): ResponsiveValue | null | undefined {
    if ((STYLE_PROPERTIES as readonly string[]).includes(property)) {
        return style[property as StyleProperty];
    }
    return style.custom?.[property];
}

/**
 * A margin box names only the sides it sets.
 *
 * The four-value shorthand would fill the rest with zero, and that zero beats
 * the stylesheet: a row that asks for "64px on top" would lose the
 * `margin-inline: auto` that centres it inside a narrower max-width, and sit
 * against the left edge instead. Sides left blank stay unset, which is what
 * clearing a side in the inspector means.
 */
function marginLonghands(value: Record<string, unknown>): Record<string, string> {
    const sides = ['top', 'right', 'bottom', 'left'] as const;
    const declarations: Record<string, string> = {};

    for (const side of sides) {
        const raw = value[side];
        if (typeof raw === 'number') {
            declarations['margin-'+side] = withUnit(String(raw));
            continue;
        }
        if (typeof raw === 'string' && raw.trim() !== '') {
            declarations['margin-'+side] = withUnit(sanitiseCssValue(raw));
        }
    }

    return declarations;
}

/** The declarations that apply at one breakpoint, `css property -> value`. */
export function resolveStyle(
    style: StyleData | null | undefined,
    breakpoint: Breakpoint,
): Record<string, string> {
    if (!style) {
        return {};
    }
    const resolved: Record<string, string> = {};

    for (const property of styleProperties(style)) {
        const raw = valueAt(responsiveFor(style, property), breakpoint);

        if (property === 'margin' && isRecord(raw)) {
            Object.assign(resolved, marginLonghands(raw));
            continue;
        }

        if (property === 'border') {
            if (isRecord(raw)) {
                Object.assign(resolved, borderLonghands(raw));
            } else {
                const border = styleValueToCss(property, raw);
                if (border !== null) {
                    // Use the same declarations at every tier so switching
                    // between a uniform border and per-side widths resets cleanly.
                    for (const side of ['top', 'right', 'bottom', 'left']) resolved[`border-${side}`] = border;
                }
            }
            continue;
        }

        const css = styleValueToCss(property, raw);
        if (css === null) {
            continue;
        }
        const cssProperty = (CSS_PROPERTY as Record<string, string | undefined>)[property] ?? kebab(property);
        resolved[cssProperty] = css;
    }

    return resolved;
}

export function kebab(property: string): string {
    return property.startsWith('--') ? property : property.replace(/[A-Z]/g, (c) => `-${c.toLowerCase()}`);
}

/* -------------------------------------------------------------------------- */
/* Rule emission                                                               */
/* -------------------------------------------------------------------------- */

export interface NodeStyleInput {
    id: string;
    style?: StyleData | null;
    visibility?: VisibilityData | null;
    /** Extra declarations the node itself computes, e.g. a column width. */
    extra?: Partial<Record<Breakpoint, Record<string, string>>>;
    /**
     * CSS properties published only as `--fnl-*` custom properties, without the
     * real declaration on the wrapper. Used for blocks whose visible surface is
     * an inner element: a button's background belongs on the button, and
     * painting it on the block's box as well draws a coloured band behind it.
     */
    varsOnly?: ReadonlySet<string>;
}

/** `[data-fnl-id="..."]` — one stable, collision-free selector per node. */
export function nodeSelector(id: string): string {
    return `[data-fnl-id="${id.replace(/["\\]/g, '')}"]`;
}

function declarationBlock(declarations: Record<string, string>, varsOnly?: ReadonlySet<string>): string {
    return Object.entries(declarations)
        .map(([property, value]) => {
            if (property.startsWith('--')) {
                return `${property}:${value}`;
            }
            if (varsOnly?.has(property)) {
                return `--fnl-${property}:${value}`;
            }
            return `--fnl-${property}:${value};${property}:var(--fnl-${property})`;
        })
        .join(';');
}

function visibilityDeclaration(
    visibility: VisibilityData | null | undefined,
    resolvedDisplay: string | undefined,
    breakpoint: Breakpoint,
): Record<string, string> {
    if (!visibility) {
        return {};
    }
    if (!visibility[breakpoint]) {
        return { display: 'none' };
    }
    // Coming back from a hidden tier needs an explicit display again.
    return resolvedDisplay ? { display: resolvedDisplay } : {};
}

/**
 * Build the CSS for one node across all three tiers.
 *
 * When `only` is given (the builder canvas, which sizes itself rather than the
 * viewport) a single flattened rule for that breakpoint is emitted instead.
 */
export function nodeCss(node: NodeStyleInput, only?: Breakpoint): string {
    const selector = nodeSelector(node.id);
    const perTier = new Map<Breakpoint, Record<string, string>>();

    for (const breakpoint of BREAKPOINTS) {
        const resolved = { ...resolveStyle(node.style, breakpoint), ...(node.extra?.[breakpoint] ?? {}) };
        const display = resolved['display'];
        Object.assign(resolved, visibilityDeclaration(node.visibility, display, breakpoint));
        perTier.set(breakpoint, resolved);
    }

    if (only) {
        const declarations = perTier.get(only) ?? {};
        return Object.keys(declarations).length === 0 ? '' : `${selector}{${declarationBlock(declarations, node.varsOnly)}}`;
    }

    const rules: string[] = [];
    const desktop = perTier.get('desktop') ?? {};
    if (Object.keys(desktop).length > 0) {
        rules.push(`${selector}{${declarationBlock(desktop, node.varsOnly)}}`);
    }

    let previous = desktop;
    for (const breakpoint of ['tablet', 'mobile'] as const) {
        const current = perTier.get(breakpoint) ?? {};
        const changed: Record<string, string> = {};

        for (const [property, value] of Object.entries(current)) {
            if (previous[property] !== value) {
                changed[property] = value;
            }
        }
        // A property that disappears at this tier must be actively reset.
        for (const property of Object.keys(previous)) {
            if (!(property in current)) {
                changed[property] = 'revert';
            }
        }

        if (Object.keys(changed).length > 0) {
            const maxWidth = BREAKPOINT_MAX_WIDTH[breakpoint];
            rules.push(`@media (max-width:${maxWidth}px){${selector}{${declarationBlock(changed, node.varsOnly)}}}`);
        }
        previous = current;
    }

    return rules.join('');
}

/* -------------------------------------------------------------------------- */
/* Column spans                                                                */
/* -------------------------------------------------------------------------- */

export function spanAt(span: ColumnSpanData | null | undefined, breakpoint: Breakpoint): number {
    if (!span) {
        return COLUMN_GRID;
    }
    const clamp = (value: number) => Math.max(1, Math.min(COLUMN_GRID, Math.round(value)));
    switch (breakpoint) {
        case 'desktop':
            return clamp(span.desktop ?? COLUMN_GRID);
        case 'tablet':
            return clamp(span.tablet ?? span.desktop ?? COLUMN_GRID);
        case 'mobile':
            return clamp(span.mobile ?? COLUMN_GRID);
    }
}

/** Breakpoints at which a row with this `stackBelow` collapses to one column. */
function stackedAt(stackBelow: string | null | undefined): ReadonlySet<Breakpoint> {
    switch (stackBelow) {
        case 'tablet':
            return new Set<Breakpoint>(['tablet', 'mobile']);
        case 'none':
        case null:
        case undefined:
            return new Set<Breakpoint>();
        default:
            return new Set<Breakpoint>(['mobile']);
    }
}

/**
 * Column width as a grid-column span, per tier. Stacking is folded in here
 * rather than left to a structural rule so it survives the builder canvas,
 * which forces one breakpoint and emits no media queries at all.
 */
export function columnExtra(
    span: ColumnSpanData | null | undefined,
    stackBelow?: string | null,
): Partial<Record<Breakpoint, Record<string, string>>> {
    const stacked = stackedAt(stackBelow);
    const extra: Partial<Record<Breakpoint, Record<string, string>>> = {};

    for (const breakpoint of BREAKPOINTS) {
        const columns = stacked.has(breakpoint) ? COLUMN_GRID : spanAt(span, breakpoint);
        extra[breakpoint] = { 'grid-column': `span ${columns} / span ${columns}` };
    }
    return extra;
}
