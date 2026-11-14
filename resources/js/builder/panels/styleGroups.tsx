/**
 * The reusable style groups every settings panel composes from.
 *
 * `StyleData` carries about forty responsive properties. Showing all of them on
 * every node is what made the old panel useless, so the groups live here as
 * independent pieces and each panel decides which ones are worth surfacing for
 * the thing that is actually selected. Nothing is ever hidden permanently: a
 * panel that omits a group still offers it under "Advanced", so no property the
 * document can hold becomes uneditable.
 */

import { useState, type ReactNode } from 'react';
import { ChevronDown, SlidersHorizontal } from 'lucide-react';
import {
    BREAKPOINTS,
    BREAKPOINT_LABEL,
    valueAt,
    type Breakpoint,
    type StyleData,
    type StyleProperty,
    type VisibilityData,
} from '@renderer';
import { Switch } from '@shared/ui/Switch';
import {
    BorderControl,
    BoxControl,
    ColorControl,
    ControlGroup,
    PanelNote,
    OverlayControl,
    RangeControl,
    RadiusControl,
    SegmentedControl,
    SelectControl,
    TextControl,
    type ControlContext,
} from './controls';
import { FontFamilyControl } from './FontFamilyControl';
import { BackgroundImageControl, BackgroundVideoControl } from './MediaPicker';
import { AudienceEditor } from './AudienceEditor';

/* -------------------------------------------------------------------------- */
/* Vocabulary                                                                  */
/* -------------------------------------------------------------------------- */

export const FONT_WEIGHTS = [
    { value: '300', label: 'Light' },
    { value: '400', label: 'Regular' },
    { value: '500', label: 'Medium' },
    { value: '600', label: 'Semibold' },
    { value: '700', label: 'Bold' },
    { value: '800', label: 'Extrabold' },
] as const;

export const MEDIA_TABS = [
    { value: 'image', label: 'Image' },
    { value: 'video', label: 'Video' },
] as const satisfies readonly { value: 'image' | 'video'; label: string }[];

export const TEXT_ALIGN = [
    { value: 'left', label: 'Left' },
    { value: 'center', label: 'Center' },
    { value: 'right', label: 'Right' },
    { value: 'justify', label: 'Justify' },
] as const;

export const TEXT_TRANSFORM = [
    { value: 'none', label: 'None' },
    { value: 'uppercase', label: 'UPPERCASE' },
    { value: 'lowercase', label: 'lowercase' },
    { value: 'capitalize', label: 'Capitalize' },
] as const;

export const TEXT_DECORATION = [
    { value: 'none', label: 'None' },
    { value: 'underline', label: 'Underline' },
    { value: 'line-through', label: 'Strikethrough' },
] as const;

export const FONT_STYLES = [
    { value: 'normal', label: 'Normal' },
    { value: 'italic', label: 'Italic' },
] as const;

export const DISPLAY = [
    { value: 'block', label: 'Block' },
    { value: 'flex', label: 'Flex' },
    { value: 'grid', label: 'Grid' },
    { value: 'inline-flex', label: 'Inline flex' },
    { value: 'none', label: 'Hidden' },
] as const;

export const FLEX_DIRECTION = [
    { value: 'row', label: 'Row' },
    { value: 'column', label: 'Column' },
    { value: 'row-reverse', label: 'Row reversed' },
    { value: 'column-reverse', label: 'Column reversed' },
] as const;

export const ALIGN_ITEMS = [
    { value: 'flex-start', label: 'Top' },
    { value: 'center', label: 'Middle' },
    { value: 'flex-end', label: 'Bottom' },
    { value: 'stretch', label: 'Stretch' },
] as const;

export const JUSTIFY = [
    { value: 'flex-start', label: 'Start' },
    { value: 'center', label: 'Center' },
    { value: 'flex-end', label: 'End' },
    { value: 'space-between', label: 'Space between' },
    { value: 'space-around', label: 'Space around' },
] as const;

export const BACKGROUND_SIZE = [
    { value: 'cover', label: 'Cover' },
    { value: 'contain', label: 'Contain' },
    { value: 'auto', label: 'Original size' },
] as const;

export const BACKGROUND_POSITION = [
    { value: 'center', label: 'Center' },
    { value: 'top', label: 'Top' },
    { value: 'bottom', label: 'Bottom' },
    { value: 'left', label: 'Left' },
    { value: 'right', label: 'Right' },
] as const;

export const BACKGROUND_REPEAT = [
    { value: 'no-repeat', label: 'No repeat' },
    { value: 'repeat', label: 'Tile' },
    { value: 'repeat-x', label: 'Tile across' },
    { value: 'repeat-y', label: 'Tile down' },
] as const;

export const BACKGROUND_ATTACHMENT = [
    { value: 'scroll', label: 'Scrolls with the page' },
    { value: 'fixed', label: 'Fixed (parallax)' },
] as const;

/* -------------------------------------------------------------------------- */
/* The control factory a panel hands to every group                            */
/* -------------------------------------------------------------------------- */

/** Builds a `ControlContext` for one style property, bound to the active tier. */
export type StyleControlFactory = (property: StyleProperty) => ControlContext;

export function makeStyleControl(
    style: StyleData,
    breakpoint: Breakpoint,
    onStyleChange: (style: StyleData) => void,
): StyleControlFactory {
    return (property) => ({
        breakpoint,
        value: style[property],
        onChange: (next) => onStyleChange({ ...style, [property]: next }),
        onChangeMany: (patch) => onStyleChange({ ...style, ...patch }),
    });
}

export interface GroupProps {
    /** Prefix for the generated control ids; must be stable per panel. */
    baseId: string;
    control: StyleControlFactory;
    defaultOpen?: boolean;
    title?: string;
    /** Filter and overlay controls require the section renderer's layered background. */
    layeredImageTools?: boolean;
    /** Sections, rows and columns paint a background video; blocks do not. */
    videoBackground?: boolean;
}

/* -------------------------------------------------------------------------- */
/* Groups                                                                      */
/* -------------------------------------------------------------------------- */

export function TypographyGroup({ baseId, control, defaultOpen = true, title = 'Typography' }: GroupProps) {
    return (
        <ControlGroup title={title} defaultOpen={defaultOpen}>
            <TextControl id={`${baseId}-font-size`} label="Font size" placeholder="16px" context={control('fontSize')} />
            <SelectControl
                id={`${baseId}-font-weight`}
                label="Font weight"
                options={FONT_WEIGHTS}
                context={control('fontWeight')}
            />
            <TextControl id={`${baseId}-line-height`} label="Line height" placeholder="1.5" context={control('lineHeight')} />
            <TextControl
                id={`${baseId}-letter-spacing`}
                label="Letter spacing"
                placeholder="0.01em"
                context={control('letterSpacing')}
            />
            <FontFamilyControl id={`${baseId}-font-family`} label="Font family" context={control('fontFamily')} />
            <SelectControl id={`${baseId}-font-style`} label="Font style" options={FONT_STYLES} context={control('fontStyle')} />
            <SelectControl
                id={`${baseId}-text-transform`}
                label="Text case"
                options={TEXT_TRANSFORM}
                context={control('textTransform')}
            />
            <SelectControl
                id={`${baseId}-text-decoration`}
                label="Decoration"
                options={TEXT_DECORATION}
                context={control('textDecoration')}
            />
            <SegmentedControl
                id={`${baseId}-text-align`}
                label="Text align"
                options={TEXT_ALIGN}
                context={control('textAlign')}
            />
            <ColorControl id={`${baseId}-color`} label="Text colour" context={control('color')} />
        </ControlGroup>
    );
}

export function BackgroundGroup({ baseId, control, defaultOpen = true, title = 'Background', layeredImageTools = true, videoBackground = false }: GroupProps) {
    // An image and a video are two ways to fill the same layer, so they share
    // one switch rather than stacking two long lists on top of each other.
    const [media, setMedia] = useState<'image' | 'video'>('image');
    const backgroundImage = control('backgroundImage');
    const backgroundVideo = control('backgroundVideo');
    const hasVideo = typeof valueAt(backgroundVideo.value, backgroundVideo.breakpoint) === 'string'
        && String(valueAt(backgroundVideo.value, backgroundVideo.breakpoint)).trim() !== '';
    const overlayColor = control('overlayColor');
    const overlayImage = control('overlayImage');
    const hasBackgroundImage = typeof valueAt(backgroundImage.value, backgroundImage.breakpoint) === 'string'
        && String(valueAt(backgroundImage.value, backgroundImage.breakpoint)).trim() !== '';
    const hasOverlayColor = typeof valueAt(overlayColor.value, overlayColor.breakpoint) === 'string'
        && String(valueAt(overlayColor.value, overlayColor.breakpoint)).trim() !== '';
    const hasOverlayImage = typeof valueAt(overlayImage.value, overlayImage.breakpoint) === 'string'
        && String(valueAt(overlayImage.value, overlayImage.breakpoint)).trim() !== '';
    const hasLayeredBackground = hasBackgroundImage || hasVideo || hasOverlayColor || hasOverlayImage;
    // A video already in place wins the opening tab, so what is on the canvas is
    // what the panel shows — unless this node also carries an image, in which
    // case the image stays on top and the video is one click away.
    const tab = media === 'image' && hasVideo && !hasBackgroundImage ? 'video' : media;

    const imageFill = (
        <>
            <BackgroundImageControl
                id={`${baseId}-background-image`}
                label="Image"
                context={backgroundImage}
            />
            <SelectControl
                id={`${baseId}-background-size`}
                label="Image size"
                options={BACKGROUND_SIZE}
                context={control('backgroundSize')}
            />
            <SelectControl
                id={`${baseId}-background-position`}
                label="Image position"
                options={BACKGROUND_POSITION}
                context={control('backgroundPosition')}
            />
            <SelectControl
                id={`${baseId}-background-repeat`}
                label="Repeat"
                options={BACKGROUND_REPEAT}
                context={control('backgroundRepeat')}
            />
            <SelectControl
                id={`${baseId}-background-attachment`}
                label="Attachment"
                options={BACKGROUND_ATTACHMENT}
                context={control('backgroundAttachment')}
            />
        </>
    );

    const videoFill = (
        <>
            <BackgroundVideoControl id={`${baseId}-background-video`} label="Video file" context={backgroundVideo} />
            <BackgroundImageControl id={`${baseId}-background-video-poster`} label="Poster frame" context={control('backgroundVideoPoster')} />
            <PanelNote>
                The video plays muted and on a loop behind the content, and the colour, image and overlay still apply — use the overlay to keep text readable. The poster frame shows while it loads, when it cannot play, and for visitors who prefer reduced motion. The canvas shows the poster so editing is not a moving target.
            </PanelNote>
        </>
    );

    return (
        <ControlGroup title={title} defaultOpen={defaultOpen}>
            <ColorControl id={`${baseId}-background-color`} label="Colour" context={control('backgroundColor')} allowTransparent />
            {videoBackground ? (
                <>
                    <div
                        role="tablist"
                        aria-label="Background media"
                        className="flex gap-1 rounded-md bg-[var(--color-surface-sunken)] p-0.5"
                    >
                        {MEDIA_TABS.map((option) => {
                            const selected = option.value === tab;
                            const filled = option.value === 'image' ? hasBackgroundImage : hasVideo;

                            return (
                                <button
                                    key={option.value}
                                    type="button"
                                    role="tab"
                                    aria-selected={selected}
                                    onClick={() => setMedia(option.value)}
                                    className={[
                                        'flex flex-1 items-center justify-center gap-1 rounded px-2 py-1',
                                        'text-[length:var(--text-xs)] whitespace-nowrap',
                                        'focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[var(--color-ring)]',
                                        selected
                                            ? 'bg-[var(--color-surface)] font-medium text-[var(--color-text)] shadow-[var(--shadow-xs)]'
                                            : 'text-[var(--color-text-muted)] hover:text-[var(--color-text)]',
                                    ].join(' ')}
                                >
                                    {option.label}
                                    {filled && <span aria-label="in use" className="size-1.5 rounded-full bg-[var(--color-primary)]" />}
                                </button>
                            );
                        })}
                    </div>
                    {tab === 'image' ? imageFill : videoFill}
                </>
            ) : imageFill}
            {layeredImageTools && (tab === 'image' ? hasBackgroundImage : hasVideo) && <div className="fnl-image-adjustments">
                <span className="fnl-image-adjustments-title">{tab === 'image' ? 'Image adjustments' : 'Video adjustments'}</span>
                <RangeControl id={`${baseId}-image-opacity`} label="Visibility" min={0} max={100} defaultValue={1} scale={100} suffix="%" context={control('backgroundImageOpacity')} />
                <RangeControl id={`${baseId}-image-brightness`} label="Brightness" min={25} max={175} defaultValue={1} scale={100} suffix="%" context={control('backgroundImageBrightness')} />
                <RangeControl id={`${baseId}-image-contrast`} label="Contrast" min={25} max={175} defaultValue={1} scale={100} suffix="%" context={control('backgroundImageContrast')} />
                <RangeControl id={`${baseId}-image-saturation`} label="Saturation" min={0} max={200} defaultValue={1} scale={100} suffix="%" context={control('backgroundImageSaturation')} />
                <RangeControl id={`${baseId}-image-blur`} label="Blur" min={0} max={20} defaultValue={0} suffix="px" unit="px" context={control('backgroundImageBlur')} />
            </div>}
            {layeredImageTools && (hasBackgroundImage || hasVideo || hasOverlayColor) && <OverlayControl
                id={`${baseId}-overlay`}
                context={overlayColor}
                blendContext={control('overlayBlendMode')}
            />}
            {layeredImageTools && (hasBackgroundImage || hasVideo || hasOverlayImage) && <div className="fnl-image-adjustments">
                <span className="fnl-image-adjustments-title">Overlay image</span>
                <BackgroundImageControl
                    id={`${baseId}-overlay-image`}
                    label="Image"
                    context={overlayImage}
                />
                <SelectControl id={`${baseId}-overlay-image-size`} label="Image size" options={BACKGROUND_SIZE} context={control('overlayImageSize')} />
                <SelectControl id={`${baseId}-overlay-image-position`} label="Image position" options={BACKGROUND_POSITION} context={control('overlayImagePosition')} />
                <RangeControl id={`${baseId}-overlay-image-opacity`} label="Visibility" min={0} max={100} defaultValue={1} scale={100} suffix="%" context={control('overlayImageOpacity')} />
            </div>}
            {layeredImageTools && hasLayeredBackground && <PanelNote>
                Background adjustments affect only the image or video, never the text or buttons. The overlay sits above the fill and behind your content, so it is what tints a video. Every value can be adjusted independently for desktop, tablet and mobile.
            </PanelNote>}
        </ControlGroup>
    );
}

export function ColourGroup({ baseId, control, defaultOpen = true, title = 'Colour' }: GroupProps) {
    return (
        <ControlGroup title={title} defaultOpen={defaultOpen}>
            <ColorControl id={`${baseId}-color`} label="Text colour" context={control('color')} />
            <ColorControl id={`${baseId}-background-color`} label="Background" context={control('backgroundColor')} allowTransparent />
        </ControlGroup>
    );
}

export function BorderGroup({ baseId, control, defaultOpen = true, title = 'Border and corners' }: GroupProps) {
    return (
        <ControlGroup title={title} defaultOpen={defaultOpen}>
            <BorderControl id={`${baseId}-border`} context={control('border')} />
            <RadiusControl id={`${baseId}-radius`} context={control('borderRadius')} />
        </ControlGroup>
    );
}

export function SpacingGroup({ baseId, control, defaultOpen = true, title = 'Spacing' }: GroupProps) {
    return (
        <ControlGroup title={title} defaultOpen={defaultOpen}>
            <BoxControl id={`${baseId}-padding`} label="Padding" context={control('padding')} />
            <BoxControl id={`${baseId}-margin`} label="Margin" context={control('margin')} />
        </ControlGroup>
    );
}

export function SizingGroup({ baseId, control, defaultOpen = false, title = 'Size' }: GroupProps) {
    return (
        <ControlGroup title={title} defaultOpen={defaultOpen}>
            <TextControl id={`${baseId}-width`} label="Width" placeholder="100%" context={control('width')} />
            <TextControl id={`${baseId}-min-width`} label="Min width" placeholder="0" context={control('minWidth')} />
            <TextControl id={`${baseId}-max-width`} label="Max width" placeholder="1140px" context={control('maxWidth')} />
            <TextControl id={`${baseId}-height`} label="Height" placeholder="auto" context={control('height')} />
            <TextControl id={`${baseId}-min-height`} label="Min height" placeholder="320px" context={control('minHeight')} />
            <TextControl id={`${baseId}-max-height`} label="Max height" placeholder="none" context={control('maxHeight')} />
        </ControlGroup>
    );
}

export function LayoutGroup({ baseId, control, defaultOpen = false, title = 'Layout' }: GroupProps) {
    return (
        <ControlGroup title={title} defaultOpen={defaultOpen}>
            <SelectControl id={`${baseId}-display`} label="Display" options={DISPLAY} context={control('display')} />
            <SelectControl
                id={`${baseId}-flex-direction`}
                label="Direction"
                options={FLEX_DIRECTION}
                context={control('flexDirection')}
            />
            <SelectControl id={`${baseId}-align-items`} label="Align items" options={ALIGN_ITEMS} context={control('alignItems')} />
            <SelectControl
                id={`${baseId}-justify`}
                label="Justify content"
                options={JUSTIFY}
                context={control('justifyContent')}
            />
            <TextControl id={`${baseId}-gap`} label="Gap" placeholder="16px" context={control('gap')} />
        </ControlGroup>
    );
}

export function EffectsGroup({ baseId, control, defaultOpen = false, title = 'Effects' }: GroupProps) {
    return (
        <ControlGroup title={title} defaultOpen={defaultOpen}>
            <TextControl
                id={`${baseId}-shadow`}
                label="Box shadow"
                placeholder="0 10px 30px rgba(0,0,0,.12)"
                context={control('boxShadow')}
            />
            <TextControl
                id={`${baseId}-text-shadow`}
                label="Text shadow"
                placeholder="0 1px 2px rgba(0,0,0,.4)"
                context={control('textShadow')}
            />
            <TextControl id={`${baseId}-opacity`} label="Opacity" placeholder="1" context={control('opacity')} />
            <TextControl id={`${baseId}-z-index`} label="Z index" placeholder="auto" context={control('zIndex')} />
            <TextControl id={`${baseId}-transform`} label="Transform" placeholder="translateY(-4px)" context={control('transform')} />
            <TextControl
                id={`${baseId}-transition`}
                label="Transition"
                placeholder="all .2s ease"
                context={control('transition')}
            />
        </ControlGroup>
    );
}

/* -------------------------------------------------------------------------- */
/* Visibility — the one group every node shares                                */
/* -------------------------------------------------------------------------- */

export function VisibilityGroup({
    visibility,
    onChange,
    noun,
}: {
    visibility: VisibilityData;
    onChange: (visibility: VisibilityData) => void;
    /** "section", "row", "column", "block" — used in the copy. */
    noun: string;
}) {
    return (
        <ControlGroup title="Visibility" defaultOpen={false}>
            <PanelNote>
                Hide this {noun} on individual breakpoints. It stays in the document either way, so
                nothing is lost by turning a tier off.
            </PanelNote>
            {BREAKPOINTS.map((tier) => (
                <Switch
                    key={tier}
                    size="sm"
                    label={`Visible on ${BREAKPOINT_LABEL[tier]}`}
                    checked={visibility[tier]}
                    onCheckedChange={(checked) => onChange({ ...visibility, [tier]: checked })}
                />
            ))}
            <AudienceEditor audience={visibility.audience} onChange={(audience) => onChange({ ...visibility, audience })} />
        </ControlGroup>
    );
}

/* -------------------------------------------------------------------------- */
/* Advanced fallback                                                           */
/* -------------------------------------------------------------------------- */

/**
 * Everything a panel chose not to promote, kept one click away.
 *
 * This is what makes the per-type curation safe: a spacer's panel does not show
 * typography, but a spacer whose document already carries a font size can still
 * be fixed here rather than becoming uneditable.
 */
export function AdvancedStyles({ children }: { children: ReactNode }) {
    return (
        <details className="fnl-advanced-styles group">
            <summary className="fnl-advanced-styles-summary">
                <span className="fnl-advanced-styles-icon"><SlidersHorizontal aria-hidden size={15} /></span>
                <span className="min-w-0 flex-1">
                    <span className="block text-[length:var(--text-xs)] font-semibold tracking-wide text-[var(--color-text)] uppercase">
                        Advanced styles
                    </span>
                    <span className="block truncate text-[length:var(--text-2xs)] font-normal text-[var(--color-text-subtle)]">
                        Fine control for every remaining property
                    </span>
                </span>
                <ChevronDown aria-hidden className="fnl-advanced-styles-chevron" size={16} />
            </summary>
            <div className="fnl-advanced-styles-body">
                <PanelNote>
                    These controls inherit across desktop, tablet and mobile. A breakpoint badge means that value only applies at the current size.
                </PanelNote>
                {children}
            </div>
        </details>
    );
}
