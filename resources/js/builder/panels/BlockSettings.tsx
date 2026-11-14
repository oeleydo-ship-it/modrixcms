import { WidthControls } from './WidthControls';
import { HEADING_PRESETS } from './headingPresets';
/**
 * Block settings.
 *
 * Content first, then only the style groups that mean something for this block
 * type — the map lives in `blockStyleGroups.ts` with the reasoning. Everything
 * that was left out stays reachable under "Advanced styles", so curation never
 * costs anyone the ability to edit what the document already holds.
 */

import { useBuilderStore } from '../store';
import { useId } from 'react';
import { BLOCK_LABEL, BREAKPOINT_LABEL, type BlockData, type Breakpoint, type StyleData, type VisibilityData } from '@renderer';
import { NodeHeader } from './NodeHeader';
import { ControlGroup, PanelNote } from './controls';
import { RESPONSIVE_FIELD_LABELS, overriddenFields, propsAt, resetTierFields, responsiveFieldsFor, splitPropsPatch } from './responsiveProps';
import { BlockContentFields } from './BlockContentFields';
import { AiAssist } from './AiAssist';
import { HeadingContent } from './HeadingSettings';
import { ContainerContent } from './ContainerContent';
import { remainingStyleGroups, styleGroupsFor, type StyleGroupId } from './blockStyleGroups';
import {
    AdvancedStyles,
    BackgroundGroup,
    BorderGroup,
    EffectsGroup,
    LayoutGroup,
    SizingGroup,
    SpacingGroup,
    TypographyGroup,
    VisibilityGroup,
    makeStyleControl,
    type StyleControlFactory,
} from './styleGroups';

const DEFAULT_VISIBILITY: VisibilityData = { desktop: true, tablet: true, mobile: true };

/** Renders one group by id, so the per-type list stays data rather than JSX. */
function StyleGroup({
    id,
    baseId,
    control,
    defaultOpen,
}: {
    id: StyleGroupId;
    baseId: string;
    control: StyleControlFactory;
    defaultOpen: boolean;
}) {
    const props = { baseId: `${baseId}-${id}`, control, defaultOpen };

    switch (id) {
        case 'typography':
            return <TypographyGroup {...props} />;
        case 'background':
            return <BackgroundGroup {...props} />;
        case 'border':
            return <BorderGroup {...props} />;
        case 'spacing':
            return <SpacingGroup {...props} />;
        case 'sizing':
            return <SizingGroup {...props} />;
        case 'layout':
            return <LayoutGroup {...props} />;
        case 'effects':
            return <EffectsGroup {...props} />;
    }
}

export interface BlockSettingsProps {
    block: BlockData;
    breakpoint: Breakpoint;
    /** How many headings on the page are H1 — the heading panel warns on more than one. */
    h1Count: number;
    onStyleChange: (style: StyleData) => void;
    onVisibilityChange: (visibility: VisibilityData) => void;
    onPropsChange: (blockId: string, patch: Record<string, unknown>) => void;
    onRename: (blockId: string, name: string) => void;
    onDuplicate: (blockId: string) => void;
    onDelete: (blockId: string) => void;
}

export function BlockSettings({
    block,
    breakpoint,
    h1Count,
    onStyleChange,
    onVisibilityChange,
    onPropsChange,
    onRename,
    onDuplicate,
    onDelete,
}: BlockSettingsProps) {
    const baseId = useId();
    const style = block.style ?? {};
    const control = makeStyleControl(style, breakpoint, onStyleChange);
    // Layout props (columns, gap, size…) belong to the active screen size;
    // everything else is content and is shared by every screen.
    const tierProps = propsAt(block, breakpoint);
    const tierFields = responsiveFieldsFor(block.type);
    const tierOverrides = overriddenFields(block, breakpoint);
    const set = (patch: Record<string, unknown>) => {
        if (block.type === 'heading' && typeof patch.level === 'string' && HEADING_PRESETS[patch.level]) {
            useBuilderStore.getState().patchBlock(block.id, {
                props: { ...block.props, ...patch },
                style: { ...style, ...HEADING_PRESETS[patch.level] },
            });
            return;
        }
        const split = splitPropsPatch(block, breakpoint, patch);
        if (split.style === null) {
            onPropsChange(block.id, patch);
            return;
        }
        useBuilderStore.getState().patchBlock(block.id, { props: { ...block.props, ...split.props }, style: split.style });
    };

    const promoted = styleGroupsFor(block.type);
    const remaining = remainingStyleGroups(block.type);
    const label = BLOCK_LABEL[block.type] ?? block.type;

    return (
        <div className="flex h-full min-h-0 flex-col overflow-hidden">
            <NodeHeader
                kind="block"
                title={label}
                name={block.name ?? ''}
                namePlaceholder={label}
                onRename={(name) => onRename(block.id, name)}
                onDuplicate={() => onDuplicate(block.id)}
                onDelete={() => onDelete(block.id)}
            />

            <div className="min-h-0 flex-1 overflow-y-auto pr-1">
                <WidthControls id={baseId} control={control} title="Block width" />
                <ControlGroup title="Content">
                    {breakpoint !== 'desktop' && (
                        <PanelNote>
                            {tierFields.length > 0
                                ? `Editing ${BREAKPOINT_LABEL[breakpoint]}: ${tierFields.map((field) => RESPONSIVE_FIELD_LABELS[field] ?? field).join(', ')} apply to ${BREAKPOINT_LABEL[breakpoint].toLowerCase()} only. Text, images and links are shared by every screen.`
                                : `Editing ${BREAKPOINT_LABEL[breakpoint]}: content is shared by every screen. Styles below apply to ${BREAKPOINT_LABEL[breakpoint].toLowerCase()} only.`}
                            {tierOverrides.length > 0 && (
                                <>
                                    {' '}
                                    <button
                                        type="button"
                                        className="font-medium text-[var(--color-text-brand)] hover:underline"
                                        onClick={() => onStyleChange(resetTierFields(block, breakpoint))}
                                    >
                                        Reset {tierOverrides.map((field) => RESPONSIVE_FIELD_LABELS[field] ?? field).join(', ')} on {BREAKPOINT_LABEL[breakpoint].toLowerCase()}
                                    </button>
                                </>
                            )}
                        </PanelNote>
                    )}
                    {block.type === 'heading' ? (
                        <HeadingContent props={block.props} set={set} h1Count={h1Count} />
                    ) : block.type === 'container' ? (
                        <ContainerContent block={{ ...block, props: tierProps }} set={set} />
                    ) : (
                        <BlockContentFields
                            type={block.type}
                            props={tierProps}
                            set={set}
                            onItemsWithChildren={(patch, change) => {
                                // Tabs and accordion pair item n with child n, so a
                                // removed or moved item takes its panel with it.
                                const children = [...(block.children ?? [])];
                                if (change.kind === 'remove') {
                                    children.splice(change.index, 1);
                                }
                                if (change.kind === 'move') {
                                    const [moved] = children.splice(change.from, 1);
                                    if (moved) {
                                        children.splice(change.to, 0, moved);
                                    }
                                }
                                useBuilderStore.getState().patchBlock(block.id, {
                                    props: { ...block.props, ...patch },
                                    children,
                                });
                            }}
                        />
                    )}
                    <AiAssist block={block} onApply={set} />
                </ControlGroup>

                {promoted.map((group, index) => (
                    <StyleGroup
                        key={group}
                        id={group}
                        baseId={baseId}
                        control={control}
                        // The first two style groups are the ones people came for.
                        defaultOpen={index < 2}
                    />
                ))}

                <VisibilityGroup
                    visibility={block.visibility ?? DEFAULT_VISIBILITY}
                    onChange={onVisibilityChange}
                    noun="block"
                />

                {remaining.length > 0 && (
                    <AdvancedStyles>
                        {remaining.map((group) => (
                            <StyleGroup
                                key={group}
                                id={group}
                                baseId={`${baseId}-adv`}
                                control={control}
                                defaultOpen={false}
                            />
                        ))}
                    </AdvancedStyles>
                )}
            </div>
        </div>
    );
}
