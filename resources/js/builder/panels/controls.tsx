import { BufferedStyleInput } from './BufferedStyleInput';
/**
 * Breakpoint-aware style controls.
 *
 * Every control edits one `ResponsiveValue` at the *active* breakpoint. When the
 * value shown comes from a wider tier the control is marked "inherited", and a
 * reset button appears as soon as this tier has its own value — which is the
 * only honest way to show a cascade in a form.
 */

import { useRef, useState, type KeyboardEvent as ReactKeyboardEvent, type PointerEvent as ReactPointerEvent, type ReactNode } from 'react';
import { RotateCcw } from 'lucide-react';
import { Badge } from '@shared/ui/Badge';
import { Button } from '@shared/ui/Button';
import { Field } from '@shared/ui/Field';
import { Select } from '@shared/ui/Select';
import { cn } from '@shared/lib/cn';
import { siteDesignColors } from '../siteDesign';
import {
    BREAKPOINT_CASCADE,
    BREAKPOINT_LABEL,
    isOverriddenAt,
    valueAt,
    withBreakpoint,
    type Breakpoint,
    type ResponsiveValue,
} from '@renderer';

export interface ControlContext {
    breakpoint: Breakpoint;
    value: ResponsiveValue | null | undefined;
    onChange: (next: ResponsiveValue) => void;
    /**
     * Writes several properties in one patch.
     *
     * Each `onChange` spreads the style captured when the panel rendered, so
     * two calls in one handler make the second overwrite the first: "Remove
     * overlay" cleared the colour and then put it straight back. A control that
     * changes more than one property must go through here.
     */
    onChangeMany?: (patch: Record<string, ResponsiveValue>) => void;
}

function asString(value: unknown): string {
    if (typeof value === 'string') {
        return value;
    }
    if (typeof value === 'number') {
        return String(value);
    }
    return '';
}

function asRecord(value: unknown): Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
        ? (value as Record<string, unknown>)
        : {};
}

/**
 * The label row every breakpoint-aware field shares.
 *
 * Exported because a few things that cascade are not `ResponsiveValue` — a
 * column span is its own shape — and they still have to explain inheritance the
 * same way, or the panel teaches two different mental models for one idea.
 */
export function ResponsiveHeader({
    id,
    label,
    breakpoint,
    overridden,
    inherited,
    inheritedFrom,
    onReset,
}: {
    id?: string;
    label: string;
    breakpoint: Breakpoint;
    /** This breakpoint carries its own value. */
    overridden: boolean;
    /** A value is showing, but it came from a wider tier. */
    inherited: boolean;
    /** The tier the shown value actually came from, when known. */
    inheritedFrom?: Breakpoint;
    onReset: () => void;
}) {
    const inheritLabel = inheritedFrom ? `Inherited from ${BREAKPOINT_LABEL[inheritedFrom]}` : 'Inherited from a wider breakpoint';

    return (
        <div className="flex items-center justify-between gap-2">
            <label htmlFor={id} className="text-[length:var(--text-xs)] font-medium text-[var(--color-text)]">
                {label}
            </label>
            <span className="flex items-center gap-1">
                {inherited && breakpoint !== 'desktop' && (
                    <Badge tone="neutral" size="sm" title={inheritLabel}>
                        inherited
                    </Badge>
                )}
                {overridden && breakpoint !== 'desktop' && (
                    <>
                        <Badge tone="brand" size="sm" title={`Set on ${BREAKPOINT_LABEL[breakpoint]} only`}>
                            {BREAKPOINT_LABEL[breakpoint].toLowerCase()}
                        </Badge>
                        <Button
                            variant="ghost"
                            size="xs"
                            iconOnly
                            title={`Reset ${label} to the Desktop value`}
                            aria-label={`Reset ${label} on ${BREAKPOINT_LABEL[breakpoint]} back to the Desktop value`}
                            onClick={onReset}
                        >
                            <RotateCcw className="h-3 w-3" />
                        </Button>
                    </>
                )}
            </span>
        </div>
    );
}

/** The tier a value at `breakpoint` actually resolved from, if any. */
function sourceOf(
    value: ResponsiveValue | null | undefined,
    breakpoint: Breakpoint,
): Breakpoint | undefined {
    if (!value) {
        return undefined;
    }
    for (const candidate of BREAKPOINT_CASCADE[breakpoint]) {
        const found = value[candidate];
        if (found !== null && found !== undefined) {
            return candidate;
        }
    }
    return undefined;
}

/** Label row shared by every style control: name, inheritance state, reset. */
export function ControlHeader({
    id,
    label,
    context,
}: {
    id: string;
    label: string;
    context: ControlContext;
}) {
    const overridden = isOverriddenAt(context.value, context.breakpoint);
    const inherited = !overridden && valueAt(context.value, context.breakpoint) !== null;

    return (
        <ResponsiveHeader
            id={id}
            label={label}
            breakpoint={context.breakpoint}
            overridden={overridden}
            inherited={inherited}
            inheritedFrom={sourceOf(context.value, context.breakpoint)}
            onReset={() => context.onChange(withBreakpoint(context.value, context.breakpoint, null))}
        />
    );
}

export const inputClasses = cn(
    'w-full rounded-md border border-[var(--color-border-strong)] bg-[var(--color-surface)]',
    'px-2 py-1 text-[length:var(--text-sm)] text-[var(--color-text)]',
    'focus-visible:outline-2 focus-visible:outline-[var(--color-ring)]',
);

export function TextControl({
    id,
    label,
    placeholder,
    context,
}: {
    id: string;
    label: string;
    placeholder?: string;
    context: ControlContext;
}) {
    const current = asString(valueAt(context.value, context.breakpoint));

    return (
        <div className="flex flex-col gap-1">
            <ControlHeader id={id} label={label} context={context} />
            <input
                id={id}
                className={inputClasses}
                placeholder={placeholder}
                value={current}
                onChange={(event) =>
                    context.onChange(withBreakpoint(context.value, context.breakpoint, event.currentTarget.value))
                }
            />
        </div>
    );
}

export function SelectControl({
    id,
    label,
    options,
    context,
}: {
    id: string;
    label: string;
    options: readonly { value: string; label: string }[];
    context: ControlContext;
}) {
    const current = asString(valueAt(context.value, context.breakpoint));

    return (
        <div className="flex flex-col gap-1">
            <ControlHeader id={id} label={label} context={context} />
            <Select
                id={id}
                size="sm"
                options={[{ value: '', label: 'Default' }, ...options]}
                value={current}
                onChange={(event) =>
                    context.onChange(
                        withBreakpoint(context.value, context.breakpoint, event.currentTarget.value || null),
                    )
                }
            />
        </div>
    );
}

export function RangeControl({
    id,
    label,
    context,
    min,
    max,
    step = 1,
    defaultValue,
    scale = 1,
    suffix = '',
    unit = '',
}: {
    id: string;
    label: string;
    context: ControlContext;
    min: number;
    max: number;
    step?: number;
    defaultValue: number;
    /** Display multiplier: CSS 1 becomes 100 when scale is 100. */
    scale?: number;
    suffix?: string;
    unit?: string;
}) {
    const parsed = Number.parseFloat(asString(valueAt(context.value, context.breakpoint)));
    const stored = Number.isFinite(parsed) ? parsed : defaultValue;
    const displayed = Math.round(stored * scale * 100) / 100;

    return (
        <div className="flex flex-col gap-1">
            <ControlHeader id={id} label={label} context={context} />
            <label className="fnl-overlay-range" htmlFor={id}>
                <span><span>{min}{suffix}</span><strong>{displayed}{suffix}</strong><span>{max}{suffix}</span></span>
                <BufferedStyleInput
                    id={id}
                    type="range"
                    min={min}
                    max={max}
                    step={step}
                    value={displayed}
                    onValueChange={(value) => {
                        const next = Number(value) / scale;
                        context.onChange(withBreakpoint(context.value, context.breakpoint, `${next}${unit}`));
                    }}
                />
            </label>
        </div>
    );
}

export function ColorControl({
    id,
    label,
    context,
    allowTransparent = false,
}: {
    id: string;
    label: string;
    context: ControlContext;
    allowTransparent?: boolean;
}) {
    const current = asString(valueAt(context.value, context.breakpoint));
    const themeColors = siteDesignColors();
    const linked = themeColors.find((color) => color.value === current);
    const swatch = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(current) ? current : linked?.color ?? '#000000';

    return (
        <div className="flex flex-col gap-1">
            <ControlHeader id={id} label={label} context={context} />
            {themeColors.length > 0 && (
                // Theme colours stay linked: the block follows later changes in Theme settings.
                <div className="flex flex-wrap items-center gap-1" role="group" aria-label={`${label}: theme colours`}>
                    {themeColors.map((color) => (
                        <button
                            key={color.name}
                            type="button"
                            title={`${color.label} (theme colour, stays linked)`}
                            aria-label={`Use theme colour ${color.label}`}
                            aria-pressed={linked?.name === color.name}
                            onClick={() => context.onChange(withBreakpoint(context.value, context.breakpoint, color.value))}
                            className={cn(
                                'h-5 w-5 rounded-full border border-[var(--color-border-strong)]',
                                linked?.name === color.name && 'ring-2 ring-[var(--color-ring)] ring-offset-1',
                            )}
                            style={{ background: color.color }}
                        />
                    ))}
                </div>
            )}
            {linked && <span className="text-[length:var(--text-2xs)] text-[var(--color-text-muted)]">Theme colour: {linked.label}</span>}
            <div className="flex items-center gap-2">
                <BufferedStyleInput
                    type="color"
                    aria-label={`${label} colour picker`}
                    value={swatch}
                    className="h-8 w-9 shrink-0 cursor-pointer rounded-md border border-[var(--color-border-strong)] bg-[var(--color-surface)]"
                    onValueChange={(value) =>
                        context.onChange(
                            withBreakpoint(context.value, context.breakpoint, value),
                        )
                    }
                />
                <input
                    id={id}
                    className={inputClasses}
                    placeholder="#111827 or var(--brand)"
                    value={current}
                    onChange={(event) =>
                        context.onChange(
                            withBreakpoint(context.value, context.breakpoint, event.currentTarget.value),
                        )
                    }
                />
            </div>
            {allowTransparent && (
                <Button
                    type="button"
                    variant="outline"
                    size="xs"
                    onClick={() => context.onChange(
                        withBreakpoint(context.value, context.breakpoint, 'transparent'),
                    )}
                >
                    Transparent
                </Button>
            )}
        </div>
    );
}

type OverlayMode = 'solid' | 'gradient';

interface OverlaySettings {
    mode: OverlayMode;
    first: string;
    second: string;
    opacity: number;
    angle: number;
}

function overlayColourToken(value: string): string[] {
    return value.match(/#[0-9a-f]{3,8}|rgba?\([^)]*\)/gi) ?? [];
}

function colourToHex(value: string, fallback: string): string {
    const hex = value.trim().match(/^#([0-9a-f]{3}|[0-9a-f]{6})/i)?.[1];
    if (hex) {
        return hex.length === 3
            ? `#${hex.split('').map((part) => part + part).join('')}`
            : `#${hex.slice(0, 6)}`;
    }
    const rgb = value.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
    if (!rgb) return fallback;
    return `#${[rgb[1], rgb[2], rgb[3]]
        .map((part) => Math.max(0, Math.min(255, Number(part))).toString(16).padStart(2, '0'))
        .join('')}`;
}

function colourAlpha(value: string): number | null {
    const alpha = value.match(/rgba\([^)]*,\s*(0|1|0?\.\d+)\s*\)/i)?.[1];
    return alpha === undefined ? null : Math.round(Number(alpha) * 100);
}

function parseOverlay(value: string): OverlaySettings {
    const colours = overlayColourToken(value);
    const first = colourToHex(colours[0] ?? value, '#111827');
    return {
        mode: /^\s*(?:repeating-)?linear-gradient\(/i.test(value) ? 'gradient' : 'solid',
        first,
        second: colourToHex(colours[1] ?? '', '#4f46e5'),
        opacity: colourAlpha(colours[0] ?? value) ?? 45,
        angle: Number(value.match(/linear-gradient\(\s*(-?\d+(?:\.\d+)?)deg/i)?.[1] ?? 135),
    };
}

function hexToRgba(hex: string, opacity: number): string {
    const clean = hex.replace('#', '');
    const value = Number.parseInt(clean, 16);
    const red = (value >> 16) & 255;
    const green = (value >> 8) & 255;
    const blue = value & 255;
    return `rgba(${red}, ${green}, ${blue}, ${(opacity / 100).toFixed(2)})`;
}

function overlayCss(settings: OverlaySettings): string {
    const first = hexToRgba(settings.first, settings.opacity);
    if (settings.mode === 'solid') return first;
    return `linear-gradient(${settings.angle}deg, ${first}, ${hexToRgba(settings.second, settings.opacity)})`;
}

const OVERLAY_BLEND_MODES = [
    { value: 'normal', label: 'Normal' },
    { value: 'multiply', label: 'Multiply' },
    { value: 'screen', label: 'Screen' },
    { value: 'overlay', label: 'Overlay' },
    { value: 'soft-light', label: 'Soft light' },
    { value: 'color', label: 'Colour' },
] as const;

export function OverlayControl({
    id,
    context,
    blendContext,
}: {
    id: string;
    context: ControlContext;
    blendContext: ControlContext;
}) {
    const raw = asString(valueAt(context.value, context.breakpoint));
    const settings = parseOverlay(raw);
    const blend = asString(valueAt(blendContext.value, blendContext.breakpoint)) || 'normal';
    const update = (patch: Partial<OverlaySettings>) => {
        context.onChange(withBreakpoint(context.value, context.breakpoint, overlayCss({ ...settings, ...patch })));
    };

    return (
        <div className="fnl-overlay-control">
            <ControlHeader id={id} label="Overlay" context={context} />

            <div className="fnl-overlay-preview" style={{ background: raw || 'transparent' }}>
                <span>{raw ? `${settings.opacity}% overlay` : 'No overlay'}</span>
            </div>

            <div className="fnl-overlay-modes" role="group" aria-label="Overlay type">
                {(['solid', 'gradient'] as const).map((mode) => (
                    <button
                        key={mode}
                        type="button"
                        className={cn(settings.mode === mode && 'is-active')}
                        aria-pressed={settings.mode === mode}
                        onClick={() => update({ mode })}
                    >
                        {mode === 'solid' ? 'Solid' : 'Gradient'}
                    </button>
                ))}
            </div>

            <div className={cn('grid gap-2', settings.mode === 'gradient' ? 'grid-cols-2' : 'grid-cols-1')}>
                <label className="fnl-overlay-colour">
                    <span>{settings.mode === 'gradient' ? 'Start colour' : 'Colour'}</span>
                    <BufferedStyleInput type="color" value={settings.first} onValueChange={(value) => update({ first: value })} />
                    <code>{settings.first}</code>
                </label>
                {settings.mode === 'gradient' && (
                    <label className="fnl-overlay-colour">
                        <span>End colour</span>
                        <BufferedStyleInput type="color" value={settings.second} onValueChange={(value) => update({ second: value })} />
                        <code>{settings.second}</code>
                    </label>
                )}
            </div>

            <label className="fnl-overlay-range" htmlFor={`${id}-opacity`}>
                <span><span>Opacity</span><strong>{settings.opacity}%</strong></span>
                <BufferedStyleInput
                    id={`${id}-opacity`}
                    type="range"
                    min="0"
                    max="100"
                    value={settings.opacity}
                    onValueChange={(value) => update({ opacity: Number(value) })}
                />
            </label>

            <div className="fnl-overlay-presets" aria-label="Overlay strength presets">
                {[20, 45, 70].map((opacity) => (
                    <button key={opacity} type="button" onClick={() => update({ opacity })}>
                        {opacity === 20 ? 'Soft' : opacity === 45 ? 'Balanced' : 'Strong'}
                    </button>
                ))}
            </div>

            <div className="fnl-overlay-tones" aria-label="Image lighting presets">
                <button type="button" onClick={() => update({ mode: 'solid', first: '#000000', opacity: 45 })}>
                    Darken image
                </button>
                <button type="button" onClick={() => update({ mode: 'solid', first: '#ffffff', opacity: 35 })}>
                    Lighten image
                </button>
            </div>

            <Button
                type="button"
                variant="outline"
                size="sm"
                fullWidth
                onClick={() => {
                    const cleared = {
                        overlayColor: withBreakpoint(context.value, context.breakpoint, null),
                        overlayBlendMode: withBreakpoint(blendContext.value, blendContext.breakpoint, null),
                    };
                    if (context.onChangeMany) {
                        context.onChangeMany(cleared);
                        return;
                    }
                    context.onChange(cleared.overlayColor);
                    blendContext.onChange(cleared.overlayBlendMode);
                }}
            >
                Remove overlay
            </Button>

            {settings.mode === 'gradient' && (
                <label className="fnl-overlay-range" htmlFor={`${id}-angle`}>
                    <span><span>Angle</span><strong>{settings.angle}deg</strong></span>
                    <BufferedStyleInput
                        id={`${id}-angle`}
                        type="range"
                        min="0"
                        max="360"
                        value={settings.angle}
                        onValueChange={(value) => update({ angle: Number(value) })}
                    />
                </label>
            )}

            <Select
                id={`${id}-blend`}
                label="Blend mode"
                size="sm"
                options={OVERLAY_BLEND_MODES}
                value={blend}
                onChange={(event) => blendContext.onChange(
                    withBreakpoint(blendContext.value, blendContext.breakpoint, event.currentTarget.value),
                )}
            />

            <details className="fnl-overlay-css">
                <summary>Custom CSS value</summary>
                <input
                    id={id}
                    className={inputClasses}
                    placeholder="rgba(0, 0, 0, .45) or linear-gradient(...)"
                    value={raw}
                    onChange={(event) => context.onChange(
                        withBreakpoint(context.value, context.breakpoint, event.currentTarget.value),
                    )}
                />
            </details>
        </div>
    );
}

const BOX_SIDES = ['top', 'right', 'bottom', 'left'] as const;
type BoxSide = (typeof BOX_SIDES)[number];

function numericCssValue(value: unknown): number {
    const parsed = Number.parseFloat(asString(value));
    return Number.isFinite(parsed) ? parsed : 0;
}

function cssUnit(value: unknown): string {
    return asString(value).trim().match(/[a-z%]+$/i)?.[0] ?? 'px';
}

export function BoxControl({
    id,
    label,
    context,
}: {
    id: string;
    label: string;
    context: ControlContext;
}) {
    const current = asRecord(valueAt(context.value, context.breakpoint));
    const drag = useRef<{ side: BoxSide; x: number; y: number; value: number; unit: string } | null>(null);
    const [dragging, setDragging] = useState<BoxSide | null>(null);

    const setSide = (side: BoxSide, next: string) =>
        context.onChange(
            withBreakpoint(context.value, context.breakpoint, { ...current, [side]: next || null }),
        );

    const beginDrag = (side: BoxSide, event: ReactPointerEvent<HTMLButtonElement>): void => {
        if (event.button !== 0) return;
        event.preventDefault();
        event.currentTarget.setPointerCapture(event.pointerId);
        drag.current = {
            side,
            x: event.clientX,
            y: event.clientY,
            value: numericCssValue(current[side]),
            unit: cssUnit(current[side]),
        };
        setDragging(side);
    };

    const moveDrag = (side: BoxSide, event: ReactPointerEvent<HTMLButtonElement>): void => {
        const active = drag.current;
        if (!active || active.side !== side) return;
        const delta = side === 'left'
            ? active.x - event.clientX
            : side === 'right'
              ? event.clientX - active.x
              : side === 'top'
                ? active.y - event.clientY
                : event.clientY - active.y;
        const minimum = label.toLowerCase() === 'margin' ? -200 : 0;
        const next = Math.max(minimum, Math.min(400, Math.round(active.value + delta)));
        setSide(side, `${next}${active.unit}`);
    };

    const endDrag = (): void => {
        drag.current = null;
        setDragging(null);
    };

    return (
        <div className="flex flex-col gap-2">
            <ControlHeader id={id} label={label} context={context} />
            <div className="fnl-box-model" aria-label={`${label} visual control`}>
                <span className="fnl-box-model-core">{label}</span>
                {BOX_SIDES.map((side) => (
                    <span
                        key={side}
                        className={cn('fnl-box-model-edge', `is-${side}`, dragging === side && 'is-dragging')}
                    >
                        <input
                            id={side === 'top' ? id : undefined}
                            aria-label={`${label} ${side}`}
                            placeholder="0"
                            value={asString(current[side])}
                            onChange={(event) => setSide(side, event.currentTarget.value)}
                            onFocus={(event) => event.currentTarget.select()}
                        />
                        <button
                            type="button"
                            className="fnl-box-model-grip"
                            aria-label={`Drag to change ${label.toLowerCase()} ${side}`}
                            title={`Drag ${side} edge to adjust`}
                            onPointerDown={(event) => beginDrag(side, event)}
                            onPointerMove={(event) => moveDrag(side, event)}
                            onPointerUp={endDrag}
                            onPointerCancel={endDrag}
                        />
                    </span>
                ))}
            </div>
            <span className="text-[length:var(--text-2xs)] text-[var(--color-text-subtle)]">
                Click a value to type it, or drag its dotted grip for quick changes.
            </span>
        </div>
    );
}

const BORDER_STYLES = [
    { value: 'solid', label: 'Solid' },
    { value: 'dashed', label: 'Dashed' },
    { value: 'dotted', label: 'Dotted' },
    { value: 'none', label: 'None' },
];

export function BorderControl({ id, context }: { id: string; context: ControlContext }) {
    const current = asRecord(valueAt(context.value, context.breakpoint));

    const patch = (next: Record<string, unknown>) =>
        context.onChange(withBreakpoint(context.value, context.breakpoint, { ...current, ...next }));

    return (
        <div className="flex flex-col gap-2">
            <ControlHeader id={id} label="Border" context={context} />
            <div className="grid grid-cols-4 gap-1.5">
                {BOX_SIDES.map((side) => (
                    <input
                        key={side}
                        id={side === 'top' ? id : undefined}
                        className={inputClasses}
                        aria-label={`Border ${side} width`}
                        placeholder={side.charAt(0).toUpperCase()}
                        value={asString(current[side])}
                        onChange={(event) => patch({ [side]: event.currentTarget.value || null })}
                    />
                ))}
            </div>
            <div className="grid grid-cols-2 gap-1.5">
                <Select
                    aria-label="Border style"
                    size="sm"
                    options={BORDER_STYLES}
                    value={asString(current['style']) || 'solid'}
                    onChange={(event) => patch({ style: event.currentTarget.value })}
                />
                <input
                    className={inputClasses}
                    aria-label="Border colour"
                    placeholder="#e5e7eb"
                    value={asString(current['color'])}
                    onChange={(event) => patch({ color: event.currentTarget.value || null })}
                />
            </div>
        </div>
    );
}

const CORNERS = ['topLeft', 'topRight', 'bottomRight', 'bottomLeft'] as const;
const CORNER_LABEL: Record<(typeof CORNERS)[number], string> = {
    topLeft: 'Top left',
    topRight: 'Top right',
    bottomRight: 'Bottom right',
    bottomLeft: 'Bottom left',
};

export function RadiusControl({ id, context }: { id: string; context: ControlContext }) {
    const raw = valueAt(context.value, context.breakpoint);
    const uniform = typeof raw === 'string' ? raw : '';
    const corners = asRecord(raw);

    return (
        <div className="flex flex-col gap-2">
            <ControlHeader id={id} label="Corner radius" context={context} />
            <input
                id={id}
                className={inputClasses}
                placeholder="8px — applies to every corner"
                value={uniform}
                onChange={(event) =>
                    context.onChange(
                        withBreakpoint(context.value, context.breakpoint, event.currentTarget.value || null),
                    )
                }
            />
            <div className="grid grid-cols-4 gap-1.5">
                {CORNERS.map((corner) => (
                    <input
                        key={corner}
                        className={inputClasses}
                        aria-label={CORNER_LABEL[corner]}
                        placeholder={CORNER_LABEL[corner].split(' ').map((word) => word.charAt(0).toUpperCase()).join('')}
                        value={asString(corners[corner])}
                        onChange={(event) =>
                            context.onChange(
                                withBreakpoint(context.value, context.breakpoint, {
                                    ...corners,
                                    [corner]: event.currentTarget.value || null,
                                }),
                            )
                        }
                    />
                ))}
            </div>
        </div>
    );
}

/**
 * A responsive choice rendered as a button group rather than a select.
 *
 * Used where the options are few and the choice is spatial (alignment), because
 * a row of buttons shows the whole vocabulary at once. It is a real radiogroup:
 * arrows move between options, and the selected one is the only tab stop.
 */
export function SegmentedControl({
    id,
    label,
    options,
    context,
    allowClear = true,
}: {
    id: string;
    label: string;
    options: readonly { value: string; label: string; icon?: ReactNode }[];
    context: ControlContext;
    allowClear?: boolean;
}) {
    const current = asString(valueAt(context.value, context.breakpoint));
    const activeIndex = options.findIndex((option) => option.value === current);

    const choose = (value: string) =>
        context.onChange(
            withBreakpoint(
                context.value,
                context.breakpoint,
                allowClear && value === current ? null : value,
            ),
        );

    const onKeyDown = (event: ReactKeyboardEvent<HTMLDivElement>) => {
        const delta = event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : 0;
        if (delta === 0) {
            return;
        }
        event.preventDefault();
        const from = activeIndex < 0 ? 0 : activeIndex;
        const next = options[(from + delta + options.length) % options.length];
        if (next) {
            context.onChange(withBreakpoint(context.value, context.breakpoint, next.value));
        }
    };

    return (
        <div className="flex flex-col gap-1">
            <ControlHeader id={id} label={label} context={context} />
            <div
                id={id}
                role="radiogroup"
                aria-label={label}
                onKeyDown={onKeyDown}
                className="flex gap-1 rounded-md bg-[var(--color-surface-sunken)] p-0.5"
            >
                {options.map((option, index) => {
                    const selected = option.value === current;
                    return (
                        <button
                            key={option.value}
                            type="button"
                            role="radio"
                            aria-checked={selected}
                            aria-label={option.label}
                            title={option.label}
                            tabIndex={selected || (activeIndex < 0 && index === 0) ? 0 : -1}
                            onClick={() => choose(option.value)}
                            className={cn(
                                'flex flex-1 items-center justify-center gap-1 rounded px-2 py-1',
                                'text-[length:var(--text-xs)] whitespace-nowrap',
                                'focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[var(--color-ring)]',
                                selected
                                    ? 'bg-[var(--color-surface)] font-medium text-[var(--color-text)] shadow-[var(--shadow-xs)]'
                                    : 'text-[var(--color-text-muted)] hover:text-[var(--color-text)]',
                            )}
                        >
                            {option.icon ? <span aria-hidden>{option.icon}</span> : option.label}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

/**
 * A non-responsive choice — a plain document field like a section's width or a
 * row's stacking breakpoint, which has one value rather than one per tier.
 *
 * Same radiogroup semantics as `SegmentedControl`; kept separate because
 * conflating "a style at this breakpoint" with "a setting on the node" is
 * exactly the confusion the old panel created.
 */
export function OptionGroup<T extends string>({
    label,
    value,
    options,
    onChange,
    description,
}: {
    label: string;
    value: T;
    options: readonly { value: T; label: string }[];
    onChange: (value: T) => void;
    description?: ReactNode;
}) {
    const index = options.findIndex((option) => option.value === value);

    const onKeyDown = (event: ReactKeyboardEvent<HTMLDivElement>) => {
        const delta = event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : 0;
        if (delta === 0) {
            return;
        }
        event.preventDefault();
        const next = options[((index < 0 ? 0 : index) + delta + options.length) % options.length];
        if (next) {
            onChange(next.value);
        }
    };

    return (
        <fieldset className="flex flex-col gap-1.5">
            <legend className="text-[length:var(--text-xs)] font-medium text-[var(--color-text)]">
                {label}
            </legend>
            <div
                role="radiogroup"
                aria-label={label}
                onKeyDown={onKeyDown}
                className="flex gap-1 rounded-md bg-[var(--color-surface-sunken)] p-0.5"
            >
                {options.map((option, position) => {
                    const selected = option.value === value;
                    return (
                        <button
                            key={option.value}
                            type="button"
                            role="radio"
                            aria-checked={selected}
                            tabIndex={selected || (index < 0 && position === 0) ? 0 : -1}
                            onClick={() => onChange(option.value)}
                            className={cn(
                                'flex-1 rounded px-2 py-1 text-[length:var(--text-xs)] whitespace-nowrap',
                                'focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[var(--color-ring)]',
                                selected
                                    ? 'bg-[var(--color-surface)] font-medium text-[var(--color-text)] shadow-[var(--shadow-xs)]'
                                    : 'text-[var(--color-text-muted)] hover:text-[var(--color-text)]',
                            )}
                        >
                            {option.label}
                        </button>
                    );
                })}
            </div>
            {description && <PanelNote>{description}</PanelNote>}
        </fieldset>
    );
}

/** One line of explanation under a control, for settings that need a "why". */
export function PanelNote({ children }: { children: ReactNode }) {
    return (
        <p className="text-[length:var(--text-xs)] leading-relaxed text-[var(--color-text-muted)]">
            {children}
        </p>
    );
}

/** A titled group of controls, collapsed by default for the rarer sections. */
export function ControlGroup({
    title,
    children,
    defaultOpen = true,
}: {
    title: string;
    children: ReactNode;
    defaultOpen?: boolean;
}) {
    return (
        <details open={defaultOpen} className="border-b border-[var(--color-border)] pb-3">
            <summary className="cursor-pointer list-none py-2 text-[length:var(--text-xs)] font-semibold tracking-wide text-[var(--color-text-subtle)] uppercase">
                {title}
            </summary>
            <div className="flex flex-col gap-3 pt-1">{children}</div>
        </details>
    );
}

/** Re-exported so the panel can build plain fields without importing twice. */
export { Field };
