import { WidthControls } from './WidthControls';
/**
 * Row settings.
 *
 * A row's job is the column grid, so the layout presets come first and are
 * drawn as proportional bars — reading "1-2" is guesswork, but seeing a narrow
 * bar next to a wide one is not. Choosing a preset rewrites the row's spans
 * through the store, and content from any column that disappears is folded into
 * the last remaining one rather than dropped.
 */

import { useId } from 'react';
import { BREAKPOINT_LABEL, COLUMN_GRID, type Breakpoint, type RowData, type StyleData, type VisibilityData } from '@renderer';
import { effectiveSpans, rowLayoutAt } from '../tree';
import { cn } from '@shared/lib/cn';
import { NodeHeader } from './NodeHeader';
import { BoxControl, ControlGroup, OptionGroup, PanelNote, SelectControl, TextControl } from './controls';
import {
    ALIGN_ITEMS,
    AdvancedStyles,
    BackgroundGroup,
    BorderGroup,
    EffectsGroup,
    JUSTIFY,
    LayoutGroup,
    SizingGroup,
    SpacingGroup,
    TypographyGroup,
    VisibilityGroup,
    makeStyleControl,
} from './styleGroups';

/**
 * The presets, as spans in twelfths. Everything here sums to 12 so a preset can
 * never leave the grid short — an unbalanced row is reachable by editing the
 * spans on the individual columns, which is where that intent belongs.
 */
export const COLUMN_PRESETS: ReadonlyArray<{ key: string; label: string; spans: readonly number[] }> = [
    { key: '12', label: 'One column', spans: [12] },
    { key: '6-6', label: 'Two equal', spans: [6, 6] },
    { key: '4-4-4', label: 'Three equal', spans: [4, 4, 4] },
    { key: '3-3-3-3', label: 'Four equal', spans: [3, 3, 3, 3] },
    { key: '4-8', label: 'Narrow, wide (1-2)', spans: [4, 8] },
    { key: '8-4', label: 'Wide, narrow (2-1)', spans: [8, 4] },
    { key: '3-9', label: 'Sidebar left (1-3)', spans: [3, 9] },
    { key: '9-3', label: 'Sidebar right (3-1)', spans: [9, 3] },
    { key: '3-6-3', label: 'Centred (1-2-1)', spans: [3, 6, 3] },
    { key: '2.4x5', label: 'Five equal', spans: [2, 2, 2, 2, 2] },
    { key: '2x6', label: 'Six equal', spans: [2, 2, 2, 2, 2, 2] },
];

/**
 * `'none'` rather than `null` is deliberate: a row that omits `stackBelow`
 * normalises back to `'mobile'` on both sides of the wire, so "never stack"
 * only survives a round trip as an explicit value. The renderer already treats
 * `'none'` as the no-stacking sentinel.
 */
type StackBelow = 'mobile' | 'tablet' | 'none';

const STACK_OPTIONS: ReadonlyArray<{ value: StackBelow; label: string }> = [
    { value: 'none', label: 'Never' },
    { value: 'mobile', label: 'On mobile' },
    { value: 'tablet', label: 'Tablet down' },
];

const STACK_HELP: Record<StackBelow, string> = {
    none: 'Columns keep their grid on every screen. Only right when each column is genuinely narrow.',
    mobile: 'Columns sit side by side on desktop and tablet, then stack to full width on a phone. This is the safe default.',
    tablet: 'Columns stack on tablet as well as mobile — use it when three or more columns get cramped before the phone breakpoint.',
};

const DEFAULT_VISIBILITY: VisibilityData = { desktop: true, tablet: true, mobile: true };

function presetKeyFor(row: RowData): string | null {
    return presetKeyForSpans(row.columns.map((column) => column.span.desktop));
}

export function presetKeyForSpans(spans: readonly number[]): string | null {
    return (
        COLUMN_PRESETS.find(
            (preset) =>
                preset.spans.length === spans.length &&
                preset.spans.every((span, index) => span === spans[index]),
        )?.key ?? null
    );
}

/** The preset picker, drawn as proportional bars. Shared with nested columns. */
export function ColumnPresetGrid({
    activeKey,
    onChange,
    presets = COLUMN_PRESETS,
}: {
    activeKey: string | null;
    onChange: (spans: readonly number[]) => void;
    presets?: ReadonlyArray<{ key: string; label: string; spans: readonly number[] }>;
}) {
    return (
        <div role="radiogroup" aria-label="Column layout" className="grid grid-cols-2 gap-1.5">
            {presets.map((preset) => {
                const selected = preset.key === activeKey;
                return (
                    <button
                        key={preset.key}
                        type="button"
                        role="radio"
                        aria-checked={selected}
                        aria-label={preset.label}
                        title={preset.label}
                        tabIndex={selected || (activeKey === null && preset.key === presets[0]?.key) ? 0 : -1}
                        onClick={() => onChange(preset.spans)}
                        className={cn(
                            'flex flex-col gap-1 rounded-md border p-1.5',
                            'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)]',
                            selected
                                ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]'
                                : 'border-[var(--color-border)] bg-[var(--color-surface)] hover:border-[var(--color-border-strong)]',
                        )}
                    >
                        <span aria-hidden className="flex h-4 w-full gap-0.5">
                            {preset.spans.map((span, index) => (
                                <span
                                    key={`${preset.key}-${index}`}
                                    style={{ flexGrow: span }}
                                    className={cn(
                                        'rounded-[2px]',
                                        selected ? 'bg-[var(--color-primary)]' : 'bg-[var(--color-border-strong)]',
                                    )}
                                />
                            ))}
                        </span>
                        <span className="truncate text-[length:var(--text-2xs)] text-[var(--color-text-muted)]">
                            {preset.label}
                        </span>
                    </button>
                );
            })}
        </div>
    );
}

export interface RowSettingsProps {
    row: RowData;
    breakpoint: Breakpoint;
    onStyleChange: (style: StyleData) => void;
    onVisibilityChange: (visibility: VisibilityData) => void;
    onPatch: (patch: Partial<RowData>) => void;
    onLayoutChange: (spans: readonly number[]) => void;
    onDelete: () => void;
}

export function RowSettings({
    row,
    breakpoint,
    onStyleChange,
    onVisibilityChange,
    onPatch,
    onLayoutChange,
    onDelete,
}: RowSettingsProps) {
    const baseId = useId();
    const style = row.style ?? {};
    const control = makeStyleControl(style, breakpoint, onStyleChange);
    const activePreset = presetKeyFor(row);
    const stack: StackBelow =
        row.stackBelow === 'tablet'
            ? 'tablet'
            : row.stackBelow === 'none' || row.stackBelow == null
              ? 'none'
              : 'mobile';

    return (
        <div className="flex h-full flex-col">
            <NodeHeader
                kind="row"
                title={row.name ?? 'Row'}
                name={row.name ?? ''}
                namePlaceholder="Row"
                onRename={(name) => onPatch({ name: name === '' ? null : name })}
                onDelete={onDelete}
            />

            <div className="flex-1 overflow-y-auto pr-1">
                <ControlGroup title={breakpoint === 'desktop' ? 'Columns' : `Columns on ${BREAKPOINT_LABEL[breakpoint]}`}>
                    {breakpoint !== 'desktop' ? (
                        <TierColumnLayout row={row} breakpoint={breakpoint} onPatch={onPatch} />
                    ) : (
                        <>
                            <ColumnPresetGrid activeKey={activePreset} onChange={onLayoutChange} />
                            <button type="button" disabled={row.columns.length >= 6}
                                className="mt-2 w-full rounded-md border border-[var(--color-border)] px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                                onClick={() => onLayoutChange(Array.from({ length: row.columns.length + 1 }, () => Math.floor(COLUMN_GRID / (row.columns.length + 1))))}>
                                {row.columns.length >= 6 ? 'Maximum 6 columns' : '+ Add column'}
                            </button>
                            <PanelNote>Adds an empty column and distributes the row evenly. Existing content is kept.</PanelNote>
                            {activePreset === null && (
                                <PanelNote>
                                    This row uses a custom split ({row.columns.map((column) => column.span.desktop).join(' + ')} of{' '}
                                    {COLUMN_GRID}). Pick a preset to replace it, or set each column's span from the column
                                    itself.
                                </PanelNote>
                            )}
                        </>
                    )}

                    <TextControl
                        id={`${baseId}-gutter`}
                        label="Gutter"
                        placeholder="24px"
                        context={control('gap')}
                    />
                    <PanelNote>The gap between columns. It is responsive, so a tighter mobile gutter is one edit away.</PanelNote>

                    <SelectControl
                        id={`${baseId}-align`}
                        label="Vertical alignment"
                        options={ALIGN_ITEMS}
                        context={control('alignItems')}
                    />
                    <SelectControl
                        id={`${baseId}-justify`}
                        label="Horizontal distribution"
                        options={JUSTIFY}
                        context={control('justifyContent')}
                    />

                    {breakpoint === 'desktop' && (
                        <>
                            <OptionGroup
                                label="Stack columns"
                                value={stack}
                                options={STACK_OPTIONS}
                                onChange={(next) => onPatch({ stackBelow: next })}
                                description={STACK_HELP[stack]}
                            />
                            <PanelNote>Switch to tablet or mobile at the top of the builder to give those screens a column layout of their own.</PanelNote>
                        </>
                    )}
                </ControlGroup>

                <WidthControls id={baseId} control={control} title="Row width" />
                <BackgroundGroup baseId={`${baseId}-bg`} control={control} videoBackground />

                <ControlGroup title="Spacing">
                    <BoxControl id={`${baseId}-padding`} label="Padding" context={control('padding')} />
                    <BoxControl id={`${baseId}-margin`} label="Margin" context={control('margin')} />
                </ControlGroup>

                <VisibilityGroup visibility={row.visibility ?? DEFAULT_VISIBILITY} onChange={onVisibilityChange} noun="row" />

                <AdvancedStyles>
                    <TypographyGroup baseId={`${baseId}-adv-type`} control={control} defaultOpen={false} />
                    <BorderGroup baseId={`${baseId}-adv-border`} control={control} defaultOpen={false} />
                    <SpacingGroup baseId={`${baseId}-adv-space`} control={control} defaultOpen={false} title="Padding and margin" />
                    <SizingGroup baseId={`${baseId}-adv-size`} control={control} defaultOpen={false} />
                    <LayoutGroup baseId={`${baseId}-adv-layout`} control={control} defaultOpen={false} />
                    <EffectsGroup baseId={`${baseId}-adv-effects`} control={control} defaultOpen={false} />
                </AdvancedStyles>
            </div>
        </div>
    );
}

/**
 * The column layout for tablet or mobile. The number of columns is a desktop
 * decision; here each screen chooses how those columns share the width, or
 * stacks them. Nothing on the other screens changes.
 */
function TierColumnLayout({
    row,
    breakpoint,
    onPatch,
}: {
    row: RowData;
    breakpoint: Exclude<Breakpoint, 'desktop'>;
    onPatch: (patch: Partial<RowData>) => void;
}) {
    const count = row.columns.length;
    const current = effectiveSpans(row, breakpoint);
    const desktop = effectiveSpans(row, 'desktop');
    const stacked = current.every((span) => span === COLUMN_GRID);
    const followsDesktop = current.every((span, index) => span === desktop[index]);
    const label = BREAKPOINT_LABEL[breakpoint];

    const presets = [
        { key: 'stack', label: 'Stacked', spans: [COLUMN_GRID] },
        ...COLUMN_PRESETS.filter((preset) => preset.spans.length === count && count > 1),
    ];
    const activeKey = stacked ? 'stack' : presetKeyForSpans(current);

    return (
        <>
            {count > 1 ? (
                <ColumnPresetGrid
                    presets={presets}
                    activeKey={activeKey}
                    onChange={(spans) => onPatch(rowLayoutAt(row, breakpoint, spans.length === 1 && spans[0] === COLUMN_GRID ? 'stack' : spans))}
                />
            ) : (
                <PanelNote>This row has one column, so it already fills the width on {label.toLowerCase()}.</PanelNote>
            )}
            {count > 1 && (
                <PanelNote>
                    {stacked
                        ? `Columns stack to full width on ${label.toLowerCase()}.`
                        : activeKey === null
                          ? `Custom split on ${label.toLowerCase()}: ${current.join(' + ')} of ${COLUMN_GRID}.`
                          : `Side by side on ${label.toLowerCase()}.`}{' '}
                    Only {label.toLowerCase()} changes; desktop{breakpoint === 'mobile' ? ' and tablet keep' : ' and mobile keep'} their own layout. To add or remove columns, switch to desktop.
                </PanelNote>
            )}
            {breakpoint === 'tablet' && count > 1 && !followsDesktop && (
                <button type="button" className="self-start text-[length:var(--text-xs)] font-medium text-[var(--color-text-brand)] hover:underline" onClick={() => onPatch(rowLayoutAt(row, 'tablet', 'desktop'))}>
                    Use the desktop layout on tablet
                </button>
            )}
        </>
    );
}
