/**
 * Column settings.
 *
 * The span is the interesting part. It is a `ColumnSpanData` rather than a
 * `ResponsiveValue`, and its cascade has one wrinkle worth being explicit
 * about: tablet inherits desktop, but mobile falls back to the full twelve
 * rather than to tablet, because a phone almost always wants a stacked column.
 * The control says so rather than leaving the user to discover it.
 */

import { useId } from 'react';
import {
    BREAKPOINTS,
    BREAKPOINT_LABEL,
    COLUMN_GRID,
    type Breakpoint,
    type ColumnData,
    type ColumnSpanData,
    type RowData,
    type StyleData,
    type VisibilityData,
} from '@renderer';
import { cn } from '@shared/lib/cn';
import { NodeHeader } from './NodeHeader';
import {
    BorderControl,
    BoxControl,
    ControlGroup,
    PanelNote,
    RadiusControl,
    ResponsiveHeader,
    SelectControl,
} from './controls';
import {
    ALIGN_ITEMS,
    AdvancedStyles,
    BackgroundGroup,
    EffectsGroup,
    LayoutGroup,
    MotionGroup,
    SizingGroup,
    SpacingGroup,
    TailwindUtilityGroup,
    TypographyGroup,
    VisibilityGroup,
    makeStyleControl,
} from './styleGroups';

const DEFAULT_VISIBILITY: VisibilityData = { desktop: true, tablet: true, mobile: true };

/** Mirrors `ColumnSpanData::at()` in PHP — keep the two in step. */
function spanAtTier(span: ColumnSpanData, breakpoint: Breakpoint): number {
    if (breakpoint === 'desktop') {
        return span.desktop;
    }
    if (breakpoint === 'tablet') {
        return span.tablet ?? span.desktop;
    }
    return span.mobile ?? COLUMN_GRID;
}

/** Where the shown value came from, for the inheritance note. */
function spanSource(span: ColumnSpanData, breakpoint: Breakpoint): Breakpoint | 'grid' | null {
    if (breakpoint === 'desktop') {
        return null;
    }
    if (breakpoint === 'tablet') {
        return span.tablet != null ? null : 'desktop';
    }
    return span.mobile != null ? null : 'grid';
}

export interface ColumnSettingsProps {
    column: ColumnData;
    /** The owning row, so the panel can say how the column sits among siblings. */
    row: RowData | null;
    breakpoint: Breakpoint;
    onStyleChange: (style: StyleData) => void;
    onVisibilityChange: (visibility: VisibilityData) => void;
    onPatch: (patch: Partial<ColumnData>) => void;
}

export function ColumnSettings({
    column,
    row,
    breakpoint,
    onStyleChange,
    onVisibilityChange,
    onPatch,
}: ColumnSettingsProps) {
    const baseId = useId();
    const style = column.style ?? {};
    const control = makeStyleControl(style, breakpoint, onStyleChange);

    const span = column.span;
    const current = spanAtTier(span, breakpoint);
    const source = spanSource(span, breakpoint);
    const overridden = source === null && breakpoint !== 'desktop';

    const setSpan = (value: number | null) => {
        const next: ColumnSpanData = { ...span };
        if (breakpoint === 'desktop') {
            next.desktop = value ?? COLUMN_GRID;
        } else if (value === null) {
            delete next[breakpoint];
        } else {
            next[breakpoint] = value;
        }
        onPatch({ span: next });
    };

    const position = row ? row.columns.findIndex((candidate) => candidate.id === column.id) : -1;
    const total = row?.columns.length ?? 0;

    return (
        <div className="flex h-full flex-col">
            <NodeHeader
                kind="column"
                title={column.name ?? (position >= 0 ? `Column ${position + 1}` : 'Column')}
                name={column.name ?? ''}
                namePlaceholder={position >= 0 ? `Column ${position + 1}` : 'Column'}
                onRename={(name) => onPatch({ name: name === '' ? null : name })}
            />

            <div className="flex-1 overflow-y-auto pr-1">
                <ControlGroup title="Width">
                    <div className="flex flex-col gap-1.5">
                        <ResponsiveHeader
                            id={`${baseId}-span`}
                            label={`Span on ${BREAKPOINT_LABEL[breakpoint]}`}
                            breakpoint={breakpoint}
                            overridden={overridden}
                            inherited={source !== null}
                            inheritedFrom={source === 'desktop' ? 'desktop' : undefined}
                            onReset={() => setSpan(null)}
                        />
                        <div
                            id={`${baseId}-span`}
                            role="radiogroup"
                            aria-label={`Column span on ${BREAKPOINT_LABEL[breakpoint]}, in twelfths`}
                            className="grid grid-cols-12 gap-0.5"
                        >
                            {Array.from({ length: COLUMN_GRID }, (_, index) => index + 1).map((value) => {
                                const selected = value === current;
                                const filled = value <= current;
                                return (
                                    <button
                                        key={value}
                                        type="button"
                                        role="radio"
                                        aria-checked={selected}
                                        aria-label={`${value} of ${COLUMN_GRID}`}
                                        title={`${value} / ${COLUMN_GRID}`}
                                        tabIndex={selected ? 0 : -1}
                                        onClick={() => setSpan(value)}
                                        onKeyDown={(event) => {
                                            const delta =
                                                event.key === 'ArrowRight'
                                                    ? 1
                                                    : event.key === 'ArrowLeft'
                                                      ? -1
                                                      : 0;
                                            if (delta === 0) {
                                                return;
                                            }
                                            event.preventDefault();
                                            setSpan(Math.max(1, Math.min(COLUMN_GRID, current + delta)));
                                        }}
                                        className={cn(
                                            'h-7 rounded-[3px] text-[length:var(--text-2xs)] leading-none',
                                            'focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[var(--color-ring)]',
                                            filled
                                                ? 'bg-[var(--color-primary)] text-[var(--color-primary-fg)]'
                                                : 'bg-[var(--color-surface-sunken)] text-[var(--color-text-subtle)] hover:bg-[var(--color-surface-hover)]',
                                        )}
                                    >
                                        {selected ? value : ''}
                                    </button>
                                );
                            })}
                        </div>
                        <PanelNote>
                            {breakpoint === 'desktop' && (
                                <>
                                    {current} of {COLUMN_GRID} columns
                                    {total > 1 && position >= 0
                                        ? ` — column ${position + 1} of ${total} in this row.`
                                        : '.'}{' '}
                                    Tablet inherits this value unless you override it there.
                                </>
                            )}
                            {breakpoint === 'tablet' &&
                                (source === 'desktop'
                                    ? `Inheriting ${current} of ${COLUMN_GRID} from Desktop. Pick a width to override it on tablet only.`
                                    : `Set to ${current} of ${COLUMN_GRID} on tablet. Reset to fall back to the Desktop value.`)}
                            {breakpoint === 'mobile' &&
                                (source === 'grid'
                                    ? `Full width on mobile. Mobile does not inherit from tablet or desktop — a column stacks to all ${COLUMN_GRID} unless you set a width here.`
                                    : `Set to ${current} of ${COLUMN_GRID} on mobile. Reset to go back to full width.`)}
                        </PanelNote>
                        {(row?.stackBelow === 'mobile' || row?.stackBelow === 'tablet') && (
                            <PanelNote>
                                This row stacks its columns
                                {row.stackBelow === 'tablet' ? ' on tablet and below' : ' on mobile'}, so the span
                                there is ignored while stacking is on.
                            </PanelNote>
                        )}
                    </div>

                    <SpanSummary span={span} />
                </ControlGroup>

                <ControlGroup title="Alignment">
                    <SelectControl
                        id={`${baseId}-align`}
                        label="Vertical alignment"
                        options={ALIGN_ITEMS}
                        context={control('alignItems')}
                    />
                    <PanelNote>How this column's content sits against the tallest column in the row.</PanelNote>
                </ControlGroup>

                <BackgroundGroup baseId={`${baseId}-bg`} control={control} videoBackground />

                <ControlGroup title="Spacing">
                    <BoxControl id={`${baseId}-padding`} label="Padding" context={control('padding')} />
                </ControlGroup>

                <ControlGroup title="Border and corners">
                    <BorderControl id={`${baseId}-border`} context={control('border')} />
                    <RadiusControl id={`${baseId}-radius`} context={control('borderRadius')} />
                </ControlGroup>

                <MotionGroup baseId={`${baseId}-motion`} control={control} defaultOpen={false} />
                <TailwindUtilityGroup baseId={`${baseId}-tw`} control={control} defaultOpen={false} />

                <VisibilityGroup
                    visibility={column.visibility ?? DEFAULT_VISIBILITY}
                    onChange={onVisibilityChange}
                    noun="column"
                />

                <AdvancedStyles>
                    <TypographyGroup baseId={`${baseId}-adv-type`} control={control} defaultOpen={false} />
                    <SpacingGroup baseId={`${baseId}-adv-space`} control={control} defaultOpen={false} title="Padding and margin" />
                    <SizingGroup baseId={`${baseId}-adv-size`} control={control} defaultOpen={false} />
                    <LayoutGroup baseId={`${baseId}-adv-layout`} control={control} defaultOpen={false} />
                    <EffectsGroup baseId={`${baseId}-adv-effects`} control={control} defaultOpen={false} />
                </AdvancedStyles>
            </div>
        </div>
    );
}

/** All three tiers at a glance, so the cascade is visible without switching. */
function SpanSummary({ span }: { span: ColumnSpanData }) {
    return (
        <dl className="grid grid-cols-3 gap-1.5">
            {BREAKPOINTS.map((tier) => {
                const own = tier === 'desktop' ? span.desktop : span[tier];
                return (
                    <div
                        key={tier}
                        className="rounded-md border border-[var(--color-border)] px-2 py-1 text-center"
                    >
                        <dt className="text-[length:var(--text-2xs)] text-[var(--color-text-subtle)]">
                            {BREAKPOINT_LABEL[tier]}
                        </dt>
                        <dd
                            className={cn(
                                'text-[length:var(--text-xs)] font-medium',
                                own == null ? 'text-[var(--color-text-muted)] italic' : 'text-[var(--color-text)]',
                            )}
                        >
                            {spanAtTier(span, tier)}/{COLUMN_GRID}
                        </dd>
                    </div>
                );
            })}
        </dl>
    );
}
