/**
 * Section settings.
 *
 * A section is the full-bleed band that owns a background. Its two settings
 * that are not styles — `width` and `sticky` — are the ones people get wrong
 * most often, so both are explained inline rather than left as jargon.
 */

import { useId } from 'react';
import { Switch } from '@shared/ui/Switch';
import type { Breakpoint, SectionData, StyleData, VisibilityData } from '@renderer';
import { NodeHeader } from './NodeHeader';
import { SectionReuse } from './SectionReuse';
import { BoxControl, ControlGroup, OptionGroup, PanelNote, TextControl } from './controls';
import {
    AdvancedStyles,
    BackgroundGroup,
    BorderGroup,
    EffectsGroup,
    LayoutGroup,
    SpacingGroup,
    TypographyGroup,
    VisibilityGroup,
    makeStyleControl,
} from './styleGroups';

type SectionWidth = 'boxed' | 'full';

const WIDTH_OPTIONS: ReadonlyArray<{ value: SectionWidth; label: string }> = [
    { value: 'boxed', label: 'Boxed' },
    { value: 'full', label: 'Full width' },
];

const WIDTH_HELP: Record<SectionWidth, string> = {
    boxed: 'The background stops at the content width, so the section reads as a contained panel sitting on the page.',
    full: 'The background fills the page. Content stays centred at the content maximum width below.',
};

const DEFAULT_VISIBILITY: VisibilityData = { desktop: true, tablet: true, mobile: true };

export interface SectionSettingsProps {
    section: SectionData;
    breakpoint: Breakpoint;
    onStyleChange: (style: StyleData) => void;
    onVisibilityChange: (visibility: VisibilityData) => void;
    onPatch: (patch: Partial<SectionData>) => void;
    onDelete: () => void;
}

export function SectionSettings({
    section,
    breakpoint,
    onStyleChange,
    onVisibilityChange,
    onPatch,
    onDelete,
}: SectionSettingsProps) {
    const baseId = useId();
    const style = section.style ?? {};
    const control = makeStyleControl(style, breakpoint, onStyleChange);
    const width: SectionWidth = section.width === 'full' ? 'full' : 'boxed';

    return (
        <div className="flex h-full flex-col">
            <NodeHeader
                kind="section"
                title={section.name ?? 'Section'}
                name={section.name ?? ''}
                namePlaceholder="Section"
                onRename={(name) => onPatch({ name: name === '' ? null : name })}
                onDelete={onDelete}
            />

            <div className="flex-1 overflow-y-auto pr-1">
                <SectionReuse key={section.id} section={section} />
                <ControlGroup title="Section">
                    <OptionGroup
                        label="Background width"
                        value={width}
                        options={WIDTH_OPTIONS}
                        onChange={(next) => onPatch({
                            width: next,
                            ...(section.globalComponentId ? { settings: { ...section.settings, instanceWidth: next } } : {}),
                        })}
                        description={WIDTH_HELP[width]}
                    />

                    <Switch
                        id={`${baseId}-sticky`}
                        size="sm"
                        label="Stick to the top"
                        description="Pins to the top of the viewport once the visitor scrolls past it. Use it for a nav band — one per page."
                        checked={section.sticky}
                        onCheckedChange={(checked) => onPatch({ sticky: checked })}
                    />
                </ControlGroup>

                <BackgroundGroup baseId={`${baseId}-bg`} control={control} layeredImageTools videoBackground />

                <ControlGroup title="Content layout">
                    <TextControl id={`${baseId}-content-width`} label="Content maximum width" placeholder="1200px or 100%" context={control('maxWidth')} />
                    <PanelNote>Limits the content, not the full-width background. Leave empty to use the page default.</PanelNote>
                </ControlGroup>
                <ControlGroup title="Spacing and height">
                    <BoxControl id={`${baseId}-padding`} label="Padding" context={control('padding')} />
                    <TextControl
                        id={`${baseId}-min-height`}
                        label="Minimum height"
                        placeholder="480px or 100vh"
                        context={control('minHeight')}
                    />
                    <PanelNote>
                        A minimum height gives a hero its shape before the content fills it. Pair it with
                        an “Align items: middle” under Advanced to centre the rows inside.
                    </PanelNote>
                </ControlGroup>

                <VisibilityGroup visibility={section.visibility ?? DEFAULT_VISIBILITY} onChange={onVisibilityChange} noun="section" />

                <AdvancedStyles>
                    <TypographyGroup baseId={`${baseId}-adv-type`} control={control} defaultOpen={false} />
                    <BorderGroup baseId={`${baseId}-adv-border`} control={control} defaultOpen={false} />
                    <SpacingGroup baseId={`${baseId}-adv-space`} control={control} defaultOpen={false} title="Padding and margin" />
                    <LayoutGroup baseId={`${baseId}-adv-layout`} control={control} defaultOpen={false} />
                    <EffectsGroup baseId={`${baseId}-adv-effects`} control={control} defaultOpen={false} />
                </AdvancedStyles>
            </div>
        </div>
    );
}
