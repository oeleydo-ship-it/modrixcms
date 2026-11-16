/**
 * The content half of a block's settings — its own `props`, not its styles.
 *
 * Props are breakpoint-independent by design: the text of a heading is the same
 * sentence on a phone as on a desktop. Anything a block's prop bag carries that
 * has no control here round-trips through the document untouched, so adding an
 * editor later is additive rather than a migration.
 */

import { Input } from '@shared/ui/Input';
import { Select } from '@shared/ui/Select';
import { Switch } from '@shared/ui/Switch';
import { Textarea } from '@shared/ui/Textarea';
import { ICON_NAMES, type BlockType } from '@renderer';
import { useBuilderFunnel } from '../funnelContext';
import { adjacentStep, funnelStepOptions } from '../funnelWidgets';
import { PanelNote } from './controls';
import { FileField, GalleryImagesField, ImageField } from './MediaPicker';
import { ProductMultiSelect, ProductSelect } from './ProductPicker';
import { FormPicker, SiteFormPicker } from './FormPicker';
import { PopupPicker } from './PopupPicker';
import { BookingServicePicker, BookingStaffPicker } from './BookingPickers';
import { ItemListEditor, type ItemListChange, type ItemRecord } from './ItemListEditor';

export type PropsPatch = (patch: Record<string, unknown>) => void;

function BookingSuccessFields({ props, set }: { props: Record<string, unknown>; set: PropsPatch }) {
    const funnel = useBuilderFunnel();
    const action = propChoice(props, 'confirmationAction', 'message');
    return <>
        <Select label="After booking" size="sm" options={[
            { value: 'message', label: 'Show a message' },
            { value: 'next_step', label: 'Go to a funnel step' },
            { value: 'redirect', label: 'Use the service redirect' },
        ]} value={action} onChange={event => set({ confirmationAction: event.currentTarget.value })} />
        {action === 'next_step' && funnel && <Select label="Destination step" size="sm"
            options={funnelStepOptions(funnel, propText(props, 'targetStepId'))} value={propText(props, 'targetStepId')}
            onChange={event => set({ targetStepId: event.currentTarget.value || null })} />}
        {action === 'next_step' && <PanelNote>Continues after the appointment is confirmed. If the destination is unavailable, the confirmation message is shown.</PanelNote>}
        <Textarea label="Confirmation message" rows={2} value={propText(props, 'confirmationMessage')}
            onChange={event => set({ confirmationMessage: event.currentTarget.value })} />
    </>;
}

/* -------------------------------------------------------------------------- */
/* Prop readers — a document is user data, so nothing here trusts its shape     */
/* -------------------------------------------------------------------------- */

export function propText(props: Record<string, unknown>, key: string): string {
    const value = props[key];
    if (typeof value === 'string') {
        return value;
    }
    if (typeof value === 'number') {
        return String(value);
    }
    return '';
}

export function propBool(props: Record<string, unknown>, key: string): boolean {
    return props[key] === true;
}

function propChoice(props: Record<string, unknown>, key: string, fallback: string): string {
    const value = propText(props, key);
    return value === '' ? fallback : value;
}

function propNumber(props: Record<string, unknown>, key: string, fallback: number): number {
    const value = props[key];
    if (typeof value === 'number' && Number.isFinite(value)) return value;
    if (typeof value === 'string') {
        const parsed = Number.parseFloat(value);
        if (Number.isFinite(parsed)) return parsed;
    }
    return fallback;
}

function ImageAdjustment({
    label,
    property,
    value,
    min,
    max,
    suffix,
    set,
}: {
    label: string;
    property: string;
    value: number;
    min: number;
    max: number;
    suffix: string;
    set: PropsPatch;
}) {
    return (
        <label className="fnl-overlay-range">
            <span><span>{label}</span><strong>{value}{suffix}</strong></span>
            <input
                type="range"
                min={min}
                max={max}
                value={value}
                aria-label={label}
                onChange={(event) => set({ [property]: Number(event.currentTarget.value) })}
            />
        </label>
    );
}

function options(values: readonly string[], label?: (value: string) => string) {
    return values.map((value) => ({ value, label: label ? label(value) : value }));
}

const TITLE_CASE = (value: string) =>
    value.charAt(0).toUpperCase() + value.slice(1).replace(/[-_]/g, ' ');

/** Where a form or opt-in sends the visitor once they have submitted. */
function SuccessActionFields({ props, set }: { props: Record<string, unknown>; set: PropsPatch }) {
    const funnel = useBuilderFunnel();
    const action = propChoice(props, 'successAction', 'message');

    return (
        <>
            <Select
                label="After submit"
                size="sm"
                options={[
                    { value: 'next_step', label: 'Go to a funnel step' },
                    { value: 'message', label: 'Show a thank-you message' },
                    { value: 'redirect', label: 'Redirect to a URL' },
                ]}
                value={action}
                onChange={(event) => set({ successAction: event.currentTarget.value })}
            />
            {action === 'next_step' && !funnel && (
                <PanelNote>This page is not part of a funnel, so a thank-you message is shown instead.</PanelNote>
            )}
            {action === 'next_step' && funnel && (
                <>
                    <Select label="Destination step" size="sm" options={funnelStepOptions(funnel, propText(props, 'targetStepId'))}
                        value={propText(props, 'targetStepId')}
                        onChange={(event) => set({ targetStepId: event.currentTarget.value || null })} />
                    <PanelNote>A redirect configured on the connected form takes priority. If the destination is not published, the fallback message is shown.</PanelNote>
                </>
            )}
            {action !== 'redirect' && (
                <Textarea
                    label={action === 'next_step' ? 'Fallback message' : 'Success message'}
                    rows={2}
                    value={propText(props, 'successMessage')}
                    onChange={(event) => set({ successMessage: event.currentTarget.value })}
                />
            )}
            {action === 'redirect' && (
                <Input
                    label="Redirect URL"
                    size="sm"
                    placeholder="https://…"
                    value={propText(props, 'redirectUrl')}
                    onChange={(event) => set({ redirectUrl: event.currentTarget.value })}
                />
            )}
        </>
    );
}

/** The button's click behaviour — its own component so it can read the funnel. */
function ButtonActionFields({ props, set }: { props: Record<string, unknown>; set: PropsPatch }) {
    const funnel = useBuilderFunnel();
    // Documents saved before the action values were fixed carry `scroll` and
    // `submit`; read them as what they meant.
    const raw = propChoice(props, 'action', 'url');
    const action = raw === 'scroll' ? 'scroll_to' : raw === 'submit' ? 'submit_form' : raw;

    return (
        <>
            <Select
                label="On click"
                size="sm"
                options={[
                    { value: 'next_step', label: 'Go to a funnel step' },
                    { value: 'upsell_purchase', label: 'One-click upsell (charge the saved card)' },
                    { value: 'url', label: 'Open a URL' },
                    { value: 'scroll_to', label: 'Scroll to a block' },
                    { value: 'submit_form', label: 'Submit the form on this section' },
                    { value: 'popup', label: 'Open a popup' },
                ]}
                value={action}
                onChange={(event) => set({ action: event.currentTarget.value })}
            />

            {action === 'upsell_purchase' && (
                <>
                    <ProductSelect
                        label="Product to charge"
                        value={propText(props, 'productId')}
                        onChange={(productId) => set({ productId })}
                    />
                    {funnel && (
                        <Select
                            label="After a successful charge, go to"
                            size="sm"
                            options={funnelStepOptions(funnel, propText(props, 'targetStepId'))}
                            value={propText(props, 'targetStepId')}
                            onChange={(event) => set({ targetStepId: event.currentTarget.value || null })}
                        />
                    )}
                    <PanelNote>
                        Charges the card the buyer used on this funnel&rsquo;s checkout step, in one click. Put this
                        button on a step after the checkout. If the bank asks for verification, the buyer confirms on
                        the page.
                    </PanelNote>
                </>
            )}

            {action === 'next_step' &&
                (funnel ? (
                    <Select
                        label="Step"
                        size="sm"
                        options={funnelStepOptions(funnel, propText(props, 'targetStepId'))}
                        value={propText(props, 'targetStepId')}
                        onChange={(event) => set({ targetStepId: event.currentTarget.value || null })}
                    />
                ) : (
                    <PanelNote>This page is not part of a funnel, so the button has nowhere to go yet.</PanelNote>
                ))}

            {action === 'next_step' && funnel && !propText(props, 'targetStepId') && !adjacentStep(funnel, 1) && (
                <PanelNote>This is the final step. Choose a specific destination or change the click action.</PanelNote>
            )}

            {action === 'url' && (
                <>
                    <Input
                        label="URL"
                        size="sm"
                        value={propText(props, 'url')}
                        placeholder="https://…"
                        onChange={(event) => set({ url: event.currentTarget.value })}
                    />
                    <Switch
                        size="sm"
                        label="Open in a new tab"
                        checked={propBool(props, 'newTab')}
                        onCheckedChange={(checked) => set({ newTab: checked })}
                    />
                </>
            )}

            {action === 'scroll_to' && (
                <Input
                    label="Block ID"
                    size="sm"
                    hint="Leave empty to scroll to the first form on the page."
                    value={propText(props, 'targetBlockId')}
                    onChange={(event) => set({ targetBlockId: event.currentTarget.value || null })}
                />
            )}

            {action === 'popup' && <PopupPicker value={propText(props, 'popupId')} onChange={(popupId) => set({ popupId })} />}
        </>
    );
}

/* -------------------------------------------------------------------------- */

function records(props: Record<string, unknown>, key: string): ItemRecord[] {
    const value = props[key];
    return Array.isArray(value)
        ? value.map((entry) => (typeof entry === 'object' && entry !== null && !Array.isArray(entry) ? (entry as ItemRecord) : {}))
        : [];
}

const SOCIAL_NETWORKS = [
    { value: 'x', label: 'X' },
    { value: 'instagram', label: 'Instagram' },
    { value: 'facebook', label: 'Facebook' },
    { value: 'linkedin', label: 'LinkedIn' },
    { value: 'tiktok', label: 'TikTok' },
    { value: 'youtube', label: 'YouTube' },
    { value: 'threads', label: 'Threads' },
] as const;

const ICON_OPTIONS = ICON_NAMES.map((name) => ({ value: name, label: TITLE_CASE(name) }));

export function BlockContentFields({
    type,
    props,
    set,
    onItemsWithChildren,
}: {
    type: BlockType;
    props: Record<string, unknown>;
    set: PropsPatch;
    /**
     * For blocks whose child blocks line up with an item list (tabs,
     * accordion): apply the new list and move or remove the matching child in
     * the same step. Falls back to a plain props write when absent.
     */
    onItemsWithChildren?: (patch: Record<string, unknown>, change: ItemListChange) => void;
}) {
    const withChildren = (patch: Record<string, unknown>, change: ItemListChange): void =>
        onItemsWithChildren ? onItemsWithChildren(patch, change) : set(patch);

    switch (type) {
        case 'paragraph':
            return (
                <Textarea
                    label="Text"
                    rows={5}
                    value={propText(props, 'text')}
                    onChange={(event) => set({ text: event.currentTarget.value })}
                />
            );

        case 'button':
            return (
                <>
                    <Input
                        label="Label"
                        size="sm"
                        value={propText(props, 'label')}
                        onChange={(event) => set({ label: event.currentTarget.value })}
                    />
                    <ButtonActionFields props={props} set={set} />
                    <Select
                        label="Variant"
                        size="sm"
                        options={[
                            { value: 'primary', label: 'Primary' },
                            { value: 'secondary', label: 'Secondary' },
                            { value: 'outline', label: 'Outline' },
                            { value: 'link', label: 'Link' },
                        ]}
                        value={propChoice(props, 'variant', 'primary')}
                        onChange={(event) => set({ variant: event.currentTarget.value })}
                    />
                    <Select
                        label="Size"
                        size="sm"
                        options={options(['sm', 'md', 'lg'], (value) => value.toUpperCase())}
                        value={propChoice(props, 'size', 'md')}
                        onChange={(event) => set({ size: event.currentTarget.value })}
                    />
                    <Switch
                        size="sm"
                        label="Full width"
                        checked={propBool(props, 'fullWidth')}
                        onCheckedChange={(checked) => set({ fullWidth: checked })}
                    />
                </>
            );

        case 'image':
            return (
                <>
                    <ImageField
                        label="Image"
                        value={propText(props, 'src')}
                        onChange={(src) => set({ src: src || null })}
                    />
                    <Input
                        label="Alt text"
                        size="sm"
                        hint="Describe the image, or leave blank if it is purely decorative."
                        value={propText(props, 'alt')}
                        onChange={(event) => set({ alt: event.currentTarget.value })}
                    />
                    <Input
                        label="Caption"
                        size="sm"
                        value={propText(props, 'caption')}
                        onChange={(event) => set({ caption: event.currentTarget.value })}
                    />
                    <Input
                        label="Links to"
                        size="sm"
                        placeholder="https://…"
                        value={propText(props, 'link')}
                        onChange={(event) => set({ link: event.currentTarget.value })}
                    />
                    <Select
                        label="Fit"
                        size="sm"
                        options={options(['cover', 'contain', 'fill', 'none'], TITLE_CASE)}
                        value={propChoice(props, 'objectFit', 'cover')}
                        onChange={(event) => set({ objectFit: event.currentTarget.value })}
                    />
                    <Select
                        label="Loading"
                        size="sm"
                        hint="Lazy is right for anything below the fold."
                        options={[
                            { value: 'lazy', label: 'Lazy' },
                            { value: 'eager', label: 'Eager' },
                        ]}
                        value={propChoice(props, 'loading', 'lazy')}
                        onChange={(event) => set({ loading: event.currentTarget.value })}
                    />
                    {propText(props, 'src') !== '' && (
                        <div className="fnl-image-adjustments">
                            <span className="fnl-image-adjustments-title">Image adjustments</span>
                            <ImageAdjustment label="Visibility" property="imageOpacity" value={propNumber(props, 'imageOpacity', 100)} min={0} max={100} suffix="%" set={set} />
                            <ImageAdjustment label="Brightness" property="imageBrightness" value={propNumber(props, 'imageBrightness', 100)} min={25} max={175} suffix="%" set={set} />
                            <ImageAdjustment label="Contrast" property="imageContrast" value={propNumber(props, 'imageContrast', 100)} min={25} max={175} suffix="%" set={set} />
                            <ImageAdjustment label="Saturation" property="imageSaturation" value={propNumber(props, 'imageSaturation', 100)} min={0} max={200} suffix="%" set={set} />
                            <ImageAdjustment label="Blur" property="imageBlur" value={propNumber(props, 'imageBlur', 0)} min={0} max={20} suffix="px" set={set} />
                            <div className="fnl-overlay-tones">
                                <button type="button" onClick={() => set({ imageBrightness: 65 })}>Darken image</button>
                                <button type="button" onClick={() => set({ imageBrightness: 135 })}>Lighten image</button>
                            </div>
                            <button
                                type="button"
                                className="text-left text-[length:var(--text-xs)] font-medium text-[var(--color-text-brand)] hover:underline"
                                onClick={() => set({ imageOpacity: 100, imageBrightness: 100, imageContrast: 100, imageSaturation: 100, imageBlur: 0 })}
                            >
                                Reset image adjustments
                            </button>
                        </div>
                    )}
                </>
            );

        case 'video': {
            const provider = propChoice(props, 'provider', 'youtube');
            return (
                <>
                    <Select label="Presentation" size="sm" options={[{ value: 'inline', label: 'Inline player' }, { value: 'modal', label: 'Play button and dialog' }]}
                        value={propChoice(props, 'presentation', 'inline')} onChange={(event) => set({ presentation: event.currentTarget.value })} />
                    <Select
                        label="Provider"
                        size="sm"
                        options={options(['youtube', 'vimeo', 'wistia', 'file'], TITLE_CASE)}
                        value={provider}
                        onChange={(event) => set({ provider: event.currentTarget.value })}
                    />
                    {provider === 'file' ? (
                        <FileField
                            label="Video file"
                            hint="Upload, drop, or choose an MP4/WebM file from your media library."
                            value={propText(props, 'url')}
                            onChange={(url) => set({ url: url || null })}
                        />
                    ) : (
                        <Input
                            label="Video URL"
                            size="sm"
                            value={propText(props, 'url')}
                            onChange={(event) => set({ url: event.currentTarget.value })}
                        />
                    )}
                    {provider === 'file' ? (
                        <PanelNote>The file starts when viewers click play; autoplay stays disabled in the builder.</PanelNote>
                    ) : (
                        <ImageField
                            label="Poster image"
                            hint="Shown before the video plays."
                            value={propText(props, 'posterSrc')}
                            onChange={(posterSrc) => set({ posterSrc: posterSrc || null })}
                        />
                    )}
                    <Select
                        label="Aspect ratio"
                        size="sm"
                        options={options(['16:9', '4:3', '1:1', '9:16'])}
                        value={propChoice(props, 'aspectRatio', '16:9')}
                        onChange={(event) => set({ aspectRatio: event.currentTarget.value })}
                    />
                    <Switch
                        size="sm"
                        label="Autoplay"
                        description="Browsers only honour autoplay while the video is muted."
                        checked={propBool(props, 'autoplay')}
                        onCheckedChange={(checked) => set({ autoplay: checked, muted: checked ? true : propBool(props, 'muted') })}
                    />
                    <Switch
                        size="sm"
                        label="Muted"
                        checked={propBool(props, 'muted')}
                        onCheckedChange={(checked) => set({ muted: checked })}
                    />
                    <Switch
                        size="sm"
                        label="Loop"
                        checked={propBool(props, 'loop')}
                        onCheckedChange={(checked) => set({ loop: checked })}
                    />
                    <Switch
                        size="sm"
                        label="Show controls"
                        description={propChoice(props, 'provider', 'youtube') === 'vimeo' ? 'Hiding Vimeo controls needs a paid Vimeo plan.' : undefined}
                        checked={propBool(props, 'controls')}
                        onCheckedChange={(checked) => set({ controls: checked })}
                    />
                </>
            );
        }

        case 'icon':
            return (
                <>
                    <Input
                        label="Icon name"
                        size="sm"
                        hint="A lucide icon name, e.g. star, zap, shield."
                        value={propText(props, 'name')}
                        onChange={(event) => set({ name: event.currentTarget.value })}
                    />
                    <Input
                        label="Size"
                        size="sm"
                        placeholder="24px"
                        value={propText(props, 'size')}
                        onChange={(event) => set({ size: event.currentTarget.value })}
                    />
                    <Input
                        label="Links to"
                        size="sm"
                        placeholder="https://…"
                        value={propText(props, 'link')}
                        onChange={(event) => set({ link: event.currentTarget.value })}
                    />
                </>
            );

        case 'divider':
            return (
                <>
                    <Select
                        label="Line style"
                        size="sm"
                        options={options(['solid', 'dashed', 'dotted'], TITLE_CASE)}
                        value={propChoice(props, 'style', 'solid')}
                        onChange={(event) => set({ style: event.currentTarget.value })}
                    />
                    <Input
                        label="Thickness"
                        size="sm"
                        placeholder="1px"
                        value={propText(props, 'thickness')}
                        onChange={(event) => set({ thickness: event.currentTarget.value })}
                    />
                    <Input
                        label="Width"
                        size="sm"
                        placeholder="100%"
                        value={propText(props, 'width')}
                        onChange={(event) => set({ width: event.currentTarget.value })}
                    />
                </>
            );

        case 'spacer':
            return (
                <>
                    <Input
                        label="Height"
                        size="sm"
                        placeholder="48px"
                        value={propText(props, 'height')}
                        onChange={(event) => set({ height: event.currentTarget.value })}
                    />
                    <PanelNote>
                        A spacer only holds space. Switch to tablet or mobile at the top of the builder to give
                        those screens a height of their own.
                    </PanelNote>
                </>
            );

        case 'container':
            return (
                <>
                    <Select
                        label="Layout"
                        size="sm"
                        options={[
                            { value: 'stack', label: 'Stack (vertical)' },
                            { value: 'row', label: 'Row (horizontal)' },
                            { value: 'grid', label: 'Grid' },
                        ]}
                        value={propChoice(props, 'layout', 'stack')}
                        onChange={(event) => set({ layout: event.currentTarget.value })}
                    />
                    <Input
                        label="Gap"
                        size="sm"
                        placeholder="16px"
                        value={propText(props, 'gap')}
                        onChange={(event) => set({ gap: event.currentTarget.value })}
                    />
                </>
            );

        case 'html':
            return (
                <Textarea
                    label="HTML"
                    rows={8}
                    hint="Scripts and inline event handlers are removed when the page renders. Use a custom code block for JavaScript."
                    value={propText(props, 'html')}
                    onChange={(event) => set({ html: event.currentTarget.value })}
                />
            );

        case 'custom_code':
            return (
                <>
                    <Textarea
                        label="HTML"
                        rows={6}
                        hint='HTML and script tags are supported, including external ones such as <script src="https://…"></script>. Scripts run only on published pages, not on the canvas.'
                        value={propText(props, 'html')}
                        onChange={(event) => set({ html: event.currentTarget.value })}
                    />
                    <Textarea
                        label="CSS"
                        rows={4}
                        value={propText(props, 'css')}
                        onChange={(event) => set({ css: event.currentTarget.value })}
                    />
                    <Textarea
                        label="JavaScript"
                        rows={4}
                        hint="Plain JavaScript, without script tags. Runs after HTML scripts on the published page. Only use trusted code."
                        value={propText(props, 'js')}
                        onChange={(event) => set({ js: event.currentTarget.value })}
                    />
                    <Select
                        label="Where the script runs"
                        size="sm"
                        hint="Tracking pixels and analytics belong in the page head; chat widgets and most other scripts at the end of the page."
                        options={[
                            { value: 'inline', label: 'Where the block is' },
                            { value: 'head', label: 'In the page head' },
                            { value: 'body_end', label: 'At the end of the page' },
                        ]}
                        value={propChoice(props, 'placement', 'inline')}
                        onChange={(event) => set({ placement: event.currentTarget.value })}
                    />
                </>
            );

        case 'site_form':
            return (
                <SiteFormPicker
                    value={propText(props, 'formSlug')}
                    onChange={(formSlug) => set({ formSlug })}
                />
            );

        case 'form':
            return (
                <>
                    <FormPicker
                        value={propText(props, 'formId')}
                        onChange={(formId) => set({ formId })}
                        hint="The block shows this form's own fields, as built under Forms."
                    />
                    <Input
                        label="Submit label"
                        size="sm"
                        value={propText(props, 'submitLabel')}
                        onChange={(event) => set({ submitLabel: event.currentTarget.value })}
                    />
                    <Select
                        label="Layout"
                        size="sm"
                        options={options(['stacked', 'inline'], TITLE_CASE)}
                        value={propChoice(props, 'layout', 'stacked')}
                        onChange={(event) => set({ layout: event.currentTarget.value })}
                    />
                    <SuccessActionFields props={props} set={set} />
                    <Textarea label="Consent text" rows={2} value={propText(props, 'consentText')} onChange={(event) => set({ consentText: event.currentTarget.value })} />
                </>
            );

        case 'email_optin':
            return (
                <>
                    <Input
                        label="Field label"
                        size="sm"
                        value={propText(props, 'label', 'Email address')}
                        onChange={(event) => set({ label: event.currentTarget.value })}
                    />
                    <Switch
                        size="sm"
                        label="Show field label"
                        checked={propBool(props, 'showLabel')}
                        onCheckedChange={(checked) => set({ showLabel: checked })}
                    />
                    <Input
                        label="Field name"
                        size="sm"
                        hint="Key used when storing or forwarding submissions."
                        value={propText(props, 'fieldName', 'email')}
                        onChange={(event) =>
                            set({ fieldName: event.currentTarget.value.replace(/[^a-z0-9_]/gi, '_').toLowerCase() })
                        }
                    />
                    <Input
                        label="Placeholder"
                        size="sm"
                        value={propText(props, 'placeholder')}
                        onChange={(event) => set({ placeholder: event.currentTarget.value })}
                    />
                    <Input
                        label="Submit label"
                        size="sm"
                        value={propText(props, 'submitLabel')}
                        onChange={(event) => set({ submitLabel: event.currentTarget.value })}
                    />
                    <Switch
                        size="sm"
                        label="Required field"
                        checked={props['required'] !== false}
                        onCheckedChange={(checked) => set({ required: checked })}
                    />
                    <Textarea
                        label="Consent text"
                        rows={2}
                        value={propText(props, 'consentText')}
                        onChange={(event) => set({ consentText: event.currentTarget.value })}
                    />
                    <Select
                        label="Layout"
                        size="sm"
                        options={options(['inline', 'stacked'], TITLE_CASE)}
                        value={propChoice(props, 'layout', 'inline')}
                        onChange={(event) => set({ layout: event.currentTarget.value })}
                    />
                    <FormPicker
                        value={propText(props, 'formId')}
                        onChange={(formId) => set({ formId })}
                        hint="The form whose contacts, tags and automations this opt-in feeds."
                    />
                    <SuccessActionFields props={props} set={set} />
                </>
            );

        case 'phone_input':
            return (
                <>
                    <Input
                        label="Field label"
                        size="sm"
                        value={propText(props, 'label', 'Phone number')}
                        onChange={(event) => set({ label: event.currentTarget.value })}
                    />
                    <Switch
                        size="sm"
                        label="Show field label"
                        checked={props['showLabel'] !== false}
                        onCheckedChange={(checked) => set({ showLabel: checked })}
                    />
                    <Input
                        label="Field name"
                        size="sm"
                        hint="Key used when storing or forwarding submissions."
                        value={propText(props, 'fieldName', 'phone')}
                        onChange={(event) =>
                            set({ fieldName: event.currentTarget.value.replace(/[^a-z0-9_]/gi, '_').toLowerCase() })
                        }
                    />
                    <Input
                        label="Placeholder"
                        size="sm"
                        value={propText(props, 'placeholder')}
                        onChange={(event) => set({ placeholder: event.currentTarget.value })}
                    />
                    <Input
                        label="Default country"
                        size="sm"
                        hint="Two-letter code, e.g. US or AE. Its dialling code is filled in for the visitor."
                        value={propText(props, 'defaultCountry')}
                        onChange={(event) => set({ defaultCountry: event.currentTarget.value.toUpperCase() })}
                    />
                    <Input
                        label="Submit label"
                        size="sm"
                        value={propText(props, 'submitLabel')}
                        onChange={(event) => set({ submitLabel: event.currentTarget.value })}
                    />
                    <Select
                        label="Layout"
                        size="sm"
                        options={options(['inline', 'stacked'], TITLE_CASE)}
                        value={propChoice(props, 'layout', 'inline')}
                        onChange={(event) => set({ layout: event.currentTarget.value })}
                    />
                    <Switch
                        size="sm"
                        label="Required field"
                        checked={props['required'] !== false}
                        onCheckedChange={(checked) => set({ required: checked })}
                    />
                    <Switch
                        size="sm"
                        label="Require consent"
                        checked={propBool(props, 'requireConsent', true)}
                        onCheckedChange={(checked) => set({ requireConsent: checked })}
                    />
                    {propBool(props, 'requireConsent', true) && (
                        <Textarea
                            label="Consent text"
                            rows={2}
                            value={propText(props, 'consentText', 'I agree to receive text messages. Reply STOP to opt out.')}
                            onChange={(event) => set({ consentText: event.currentTarget.value })}
                        />
                    )}
                    <FormPicker value={propText(props, 'formId')} onChange={(formId) => set({ formId })} />
                    <SuccessActionFields props={props} set={set} />
                </>
            );

        case 'checkout':
            return (
                <>
                    <Input
                        label="Submit label"
                        size="sm"
                        value={propText(props, 'submitLabel')}
                        onChange={(event) => set({ submitLabel: event.currentTarget.value })}
                    />
                    <Switch
                        size="sm"
                        label="Order summary"
                        checked={propBool(props, 'showOrderSummary')}
                        onCheckedChange={(checked) => set({ showOrderSummary: checked })}
                    />
                    <Switch
                        size="sm"
                        label="Collect billing address"
                        description="Stripe's address form, validated for each country."
                        checked={propBool(props, 'collectBillingAddress')}
                        onCheckedChange={(checked) => set({ collectBillingAddress: checked })}
                    />
                    <Switch
                        size="sm"
                        label="Collect phone number"
                        checked={propBool(props, 'collectPhone')}
                        onCheckedChange={(checked) => set({ collectPhone: checked })}
                    />
                    <ProductMultiSelect
                        value={Array.isArray(props['productIds']) ? (props['productIds'] as unknown[]).filter((id): id is string => typeof id === 'string') : []}
                        onChange={(productIds) => set({ productIds })}
                    />
                    <ProductSelect
                        label="Order bump (optional)"
                        value={propText(props, 'bumpProductId')}
                        onChange={(bumpProductId) => set({ bumpProductId })}
                    />
                    {propText(props, 'bumpProductId') !== '' && (
                        <Input
                            label="Order bump headline"
                            size="sm"
                            placeholder="Yes! Add this to my order"
                            value={propText(props, 'bumpHeadline')}
                            onChange={(event) => set({ bumpHeadline: event.currentTarget.value })}
                        />
                    )}
                    <Switch
                        size="sm"
                        label="Coupon code field"
                        description="Coupons are created in Products → Coupons."
                        checked={propBool(props, 'allowCoupon')}
                        onCheckedChange={(checked) => set({ allowCoupon: checked })}
                    />
                    <Switch
                        size="sm"
                        label="Save the card for one-click upsells"
                        description="Needed if a later step has a one-click upsell button."
                        checked={props['saveCardForUpsells'] !== false}
                        onCheckedChange={(checked) => set({ saveCardForUpsells: checked })}
                    />
                    <PanelNote>
                        The live Stripe card form appears on the published page. Payments go to the Stripe account
                        connected in Commerce, and a successful payment moves the buyer to the next funnel step.
                    </PanelNote>
                </>
            );

        case 'product':
            return (
                <>
                    <ProductSelect value={propText(props, 'productId')} onChange={(productId) => set({ productId })} />
                    <Input
                        label="Product ID"
                        size="sm"
                        value={propText(props, 'productId')}
                        onChange={(event) => set({ productId: event.currentTarget.value })}
                    />
                    <PanelNote>Select a saved product or paste its ID. The live block uses its saved image, description and price. Buy now opens the page checkout, the next funnel step, or this product’s checkout link.</PanelNote>
                    <Input
                        label="Button label"
                        size="sm"
                        value={propText(props, 'buttonLabel')}
                        onChange={(event) => set({ buttonLabel: event.currentTarget.value })}
                    />
                    <Switch
                        size="sm"
                        label="Show price"
                        checked={propBool(props, 'showPrice')}
                        onCheckedChange={(checked) => set({ showPrice: checked })}
                    />
                    <Switch
                        size="sm"
                        label="Show image"
                        checked={propBool(props, 'showImage')}
                        onCheckedChange={(checked) => set({ showImage: checked })}
                    />
                    <Switch
                        size="sm"
                        label="Show description"
                        checked={propBool(props, 'showDescription')}
                        onCheckedChange={(checked) => set({ showDescription: checked })}
                    />
                </>
            );

        case 'pricing_table':
            return (
                <>
                    <Select
                        label="Columns"
                        size="sm"
                        options={options(['1', '2', '3', '4'])}
                        value={propChoice(props, 'columns', '3')}
                        onChange={(event) => set({ columns: Number(event.currentTarget.value) })}
                    />
                    <ItemListEditor
                        noun="plans"
                        singular="plan"
                        items={records(props, 'plans')}
                        fields={[
                            { key: 'name', label: 'Name', kind: 'text' },
                            { key: 'price', label: 'Price', kind: 'text', placeholder: '$49' },
                            { key: 'interval', label: 'Billing interval', kind: 'text', placeholder: 'month' },
                            { key: 'features', label: 'Features', kind: 'lines' },
                            { key: 'buttonLabel', label: 'Button label', kind: 'text' },
                            {
                                key: 'action',
                                label: 'Button goes to',
                                kind: 'select',
                                options: [
                                    { value: 'url', label: 'A URL' },
                                    { value: 'next_step', label: 'The next funnel step' },
                                ],
                            },
                            { key: 'buttonUrl', label: 'Button URL', kind: 'url' },
                            { key: 'highlighted', label: 'Highlight as most popular', kind: 'switch' },
                        ]}
                        create={() => ({
                            name: 'New plan',
                            price: '$49',
                            interval: 'month',
                            features: ['A feature'],
                            buttonLabel: 'Choose plan',
                            buttonUrl: null,
                            highlighted: false,
                        })}
                        title={(item) => [item['name'], item['price']].filter(Boolean).join(' · ')}
                        onChange={(plans) => set({ plans })}
                    />
                </>
            );

        case 'countdown':
            return (
                <>
                    <Select
                        label="Mode"
                        size="sm"
                        options={[
                            { value: 'fixed', label: 'Ends at a fixed time' },
                            { value: 'evergreen', label: 'Evergreen per visitor' },
                        ]}
                        value={propChoice(props, 'mode', 'fixed')}
                        onChange={(event) => set({ mode: event.currentTarget.value })}
                    />
                    <Input
                        label="Ends at"
                        type="datetime-local"
                        size="sm"
                        value={propText(props, 'endsAt')}
                        onChange={(event) => set({ endsAt: event.currentTarget.value })}
                    />
                    <Input
                        label="Duration (minutes)"
                        type="number"
                        min={1}
                        size="sm"
                        value={propText(props, 'durationMinutes')}
                        onChange={(event) => set({ durationMinutes: Number(event.currentTarget.value) })}
                    />
                    <Select
                        label="When it expires"
                        size="sm"
                        options={[
                            { value: 'hide', label: 'Hide the block' },
                            { value: 'message', label: 'Show a message' },
                            { value: 'redirect', label: 'Redirect' },
                        ]}
                        value={propChoice(props, 'expiredAction', 'hide')}
                        onChange={(event) => set({ expiredAction: event.currentTarget.value })}
                    />
                    <Input
                        label="Expired message"
                        size="sm"
                        value={propText(props, 'expiredMessage')}
                        onChange={(event) => set({ expiredMessage: event.currentTarget.value })}
                    />
                    <Input
                        label="Redirect URL"
                        size="sm"
                        value={propText(props, 'redirectUrl')}
                        onChange={(event) => set({ redirectUrl: event.currentTarget.value })}
                    />
                    <Switch
                        size="sm"
                        label="Show unit labels"
                        checked={propBool(props, 'showLabels')}
                        onCheckedChange={(checked) => set({ showLabels: checked })}
                    />
                </>
            );

        case 'testimonial':
            return (
                <>
                    <Textarea
                        label="Quote"
                        rows={3}
                        value={propText(props, 'quote')}
                        onChange={(event) => set({ quote: event.currentTarget.value })}
                    />
                    <Input
                        label="Author"
                        size="sm"
                        value={propText(props, 'authorName')}
                        onChange={(event) => set({ authorName: event.currentTarget.value })}
                    />
                    <Input
                        label="Author title"
                        size="sm"
                        value={propText(props, 'authorTitle')}
                        onChange={(event) => set({ authorTitle: event.currentTarget.value })}
                    />
                    <ImageField
                        label="Author photo"
                        hint="Leave empty to show the author's initials."
                        value={propText(props, 'avatarSrc')}
                        onChange={(avatarSrc) => set({ avatarSrc: avatarSrc || null })}
                    />
                    <Select
                        label="Rating"
                        size="sm"
                        options={options(['0', '1', '2', '3', '4', '5'], (value) =>
                            value === '0' ? 'No stars' : `${value} stars`,
                        )}
                        value={propChoice(props, 'rating', '5')}
                        onChange={(event) => set({ rating: Number(event.currentTarget.value) })}
                    />
                    <Select
                        label="Layout"
                        size="sm"
                        options={[
                            { value: 'card', label: 'Card' },
                            { value: 'bare', label: 'Plain (no card)' },
                        ]}
                        value={propChoice(props, 'layout', 'card')}
                        onChange={(event) => set({ layout: event.currentTarget.value })}
                    />
                </>
            );

        case 'faq':
            return (
                <>
                    <ItemListEditor
                        noun="questions"
                        singular="question"
                        items={records(props, 'items')}
                        fields={[
                            { key: 'question', label: 'Question', kind: 'text' },
                            { key: 'answer', label: 'Answer', kind: 'textarea' },
                        ]}
                        create={() => ({ question: 'A new question', answer: 'Its answer.' })}
                        title={(item) => String(item['question'] ?? '')}
                        onChange={(items) => set({ items })}
                    />
                    <Switch
                        size="sm"
                        label="Allow several open at once"
                        checked={propBool(props, 'allowMultipleOpen')}
                        onCheckedChange={(checked) => set({ allowMultipleOpen: checked })}
                    />
                    <Switch
                        size="sm"
                        label="Emit FAQ schema.org markup"
                        description="Lets search engines show the questions directly in results."
                        checked={props['emitSchemaOrg'] !== false}
                        onCheckedChange={(checked) => set({ emitSchemaOrg: checked })}
                    />
                </>
            );

        case 'accordion':
            return (
                <>
                    <ItemListEditor
                        noun="panels"
                        singular="panel"
                        min={1}
                        items={records(props, 'items')}
                        fields={[{ key: 'title', label: 'Title', kind: 'text' }]}
                        create={(index) => ({ id: null, title: `Section ${index + 1}` })}
                        title={(item) => String(item['title'] ?? '')}
                        onChange={(items, change) => withChildren({ items }, change)}
                    />
                    <Switch
                        size="sm"
                        label="Allow several open at once"
                        checked={propBool(props, 'allowMultipleOpen')}
                        onCheckedChange={(checked) => set({ allowMultipleOpen: checked })}
                    />
                    <PanelNote>Each panel shows the block dropped into the accordion in the same position.</PanelNote>
                </>
            );

        case 'tabs':
            return (
                <>
                    <ItemListEditor
                        noun="tabs"
                        singular="tab"
                        min={1}
                        items={records(props, 'tabs')}
                        fields={[{ key: 'label', label: 'Label', kind: 'text' }]}
                        create={(index) => ({ id: null, label: `Tab ${index + 1}` })}
                        title={(item) => String(item['label'] ?? '')}
                        onChange={(tabs, change) => withChildren({ tabs }, change)}
                    />
                    <Select
                        label="Orientation"
                        size="sm"
                        options={options(['horizontal', 'vertical'], TITLE_CASE)}
                        value={propChoice(props, 'orientation', 'horizontal')}
                        onChange={(event) => set({ orientation: event.currentTarget.value })}
                    />
                    <PanelNote>Each tab shows the block dropped into the tabs block in the same position.</PanelNote>
                </>
            );

        case 'progress_bar':
            return (
                <>
                    <Select
                        label="Source"
                        size="sm"
                        options={[
                            { value: 'manual', label: 'Manual value' },
                            { value: 'funnel_step', label: 'Funnel step position' },
                        ]}
                        value={propChoice(props, 'source', 'manual')}
                        onChange={(event) => set({ source: event.currentTarget.value })}
                    />
                    {propChoice(props, 'source', 'manual') === 'funnel_step' && (
                        <PanelNote>
                            The builder previews this step&rsquo;s position. The live bar counts published steps only.
                            The values below are fallbacks for pages without funnel context. Write {'{current}'} and {'{total}'} in the label
                            to show the numbers.
                        </PanelNote>
                    )}
                    <Input
                        label="Value"
                        type="number"
                        size="sm"
                        value={propText(props, 'value')}
                        onChange={(event) => set({ value: Number(event.currentTarget.value) })}
                    />
                    <Input
                        label="Maximum"
                        type="number"
                        size="sm"
                        value={propText(props, 'max')}
                        onChange={(event) => set({ max: Number(event.currentTarget.value) })}
                    />
                    <Input
                        label="Label"
                        size="sm"
                        value={propText(props, 'label')}
                        onChange={(event) => set({ label: event.currentTarget.value })}
                    />
                    <Switch
                        size="sm"
                        label="Show percentage"
                        checked={propBool(props, 'showPercentage')}
                        onCheckedChange={(checked) => set({ showPercentage: checked })}
                    />
                </>
            );

        case 'feature_cards':
            return (
                <>
                    <Select
                        label="Columns"
                        size="sm"
                        options={options(['1', '2', '3', '4'])}
                        value={propChoice(props, 'columns', '3')}
                        onChange={(event) => set({ columns: Number(event.currentTarget.value) })}
                    />
                    <ItemListEditor
                        noun="cards"
                        singular="card"
                        items={records(props, 'items')}
                        fields={[
                            { key: 'icon', label: 'Icon', kind: 'select', options: ICON_OPTIONS },
                            { key: 'title', label: 'Title', kind: 'text' },
                            { key: 'body', label: 'Text', kind: 'textarea' },
                        ]}
                        create={() => ({ icon: 'star', title: 'New feature', body: 'What makes it worth having.' })}
                        title={(item) => String(item['title'] ?? '')}
                        onChange={(items) => set({ items })}
                    />
                </>
            );

        case 'carousel':
            return (
                <>
                    <Input
                        label="Slides per view"
                        type="number"
                        min={1}
                        size="sm"
                        value={propText(props, 'slidesPerView')}
                        onChange={(event) => set({ slidesPerView: Number(event.currentTarget.value) })}
                    />
                    <Switch
                        size="sm"
                        label="Autoplay"
                        checked={propBool(props, 'autoplay')}
                        onCheckedChange={(checked) => set({ autoplay: checked })}
                    />
                    <Input
                        label="Interval (ms)"
                        type="number"
                        min={500}
                        step={500}
                        size="sm"
                        value={propText(props, 'intervalMs')}
                        onChange={(event) => set({ intervalMs: Number(event.currentTarget.value) })}
                    />
                    <Switch
                        size="sm"
                        label="Loop"
                        checked={propBool(props, 'loop')}
                        onCheckedChange={(checked) => set({ loop: checked })}
                    />
                    <Switch
                        size="sm"
                        label="Show arrows"
                        checked={propBool(props, 'showArrows')}
                        onCheckedChange={(checked) => set({ showArrows: checked })}
                    />
                    <Switch
                        size="sm"
                        label="Show dots"
                        checked={propBool(props, 'showDots')}
                        onCheckedChange={(checked) => set({ showDots: checked })}
                    />
                </>
            );

        case 'gallery':
            return (
                <>
                    <Select
                        label="Columns"
                        size="sm"
                        options={options(['1', '2', '3', '4', '5', '6'])}
                        value={propChoice(props, 'columns', '3')}
                        onChange={(event) => set({ columns: Number(event.currentTarget.value) })}
                    />
                    <Input
                        label="Gap"
                        size="sm"
                        placeholder="12px"
                        value={propText(props, 'gap')}
                        onChange={(event) => set({ gap: event.currentTarget.value })}
                    />
                    <Switch
                        size="sm"
                        label="Open images in a lightbox"
                        checked={propBool(props, 'lightbox')}
                        onCheckedChange={(checked) => set({ lightbox: checked })}
                    />
                    <GalleryImagesField images={props['images']} onChange={(images) => set({ images })} />
                </>
            );

        case 'social_icons':
            return (
                <>
                    <ItemListEditor
                        noun="profiles"
                        singular="profile"
                        items={records(props, 'networks')}
                        fields={[
                            { key: 'network', label: 'Network', kind: 'select', options: SOCIAL_NETWORKS },
                            { key: 'url', label: 'Profile URL', kind: 'url' },
                        ]}
                        create={() => ({ network: 'x', url: null })}
                        title={(item) => SOCIAL_NETWORKS.find((entry) => entry.value === item['network'])?.label ?? 'Profile'}
                        onChange={(networks) => set({ networks })}
                    />
                    <Input
                        label="Icon size"
                        size="sm"
                        placeholder="20px"
                        value={propText(props, 'size')}
                        onChange={(event) => set({ size: event.currentTarget.value })}
                    />
                    <Select
                        label="Shape"
                        size="sm"
                        options={options(['circle', 'square', 'bare'], TITLE_CASE)}
                        value={propChoice(props, 'shape', 'circle')}
                        onChange={(event) => set({ shape: event.currentTarget.value })}
                    />
                </>
            );

        case 'navigation':
            return (
                <>
                    <ItemListEditor
                        noun="links"
                        singular="link"
                        items={records(props, 'items')}
                        fields={[
                            { key: 'label', label: 'Label', kind: 'text' },
                            { key: 'url', label: 'URL', kind: 'text', placeholder: '/pricing or https://…', hint: 'A path on this site, a full URL, or #section.' },
                        ]}
                        create={() => ({ label: 'New link', url: '#' })}
                        title={(item) => String(item['label'] ?? '')}
                        onChange={(items) => set({ items })}
                    />
                    {records(props, 'items').map((item, index) => (
                        <div key={index} className="rounded-md border border-[var(--color-border)] p-2">
                            <PanelNote>{String(item['label'] ?? 'Link')} submenu</PanelNote>
                            <ItemListEditor noun="submenu links" singular="submenu link"
                                items={Array.isArray(item['children']) ? item['children'] as ItemRecord[] : []}
                                fields={[
                                    { key: 'label', label: 'Label', kind: 'text' },
                                    { key: 'url', label: 'URL', kind: 'text', placeholder: '/products' },
                                    { key: 'description', label: 'Subtitle', kind: 'text', placeholder: 'Optional short summary' },
                                    { key: 'icon', label: 'Icon', kind: 'select', options: [{ value: '', label: 'None' }, ...ICON_OPTIONS] },
                                ]}
                                create={() => ({ label: 'New submenu link', url: '#' })}
                                title={(child) => String(child['label'] ?? '')}
                                onChange={(children) => set({ items: records(props, 'items').map((link, i) => i === index ? { ...link, children } : link) })} />
                        </div>
                    ))}
                    <Select
                        label="Orientation"
                        size="sm"
                        options={options(['horizontal', 'vertical'], TITLE_CASE)}
                        value={propChoice(props, 'orientation', 'horizontal')}
                        onChange={(event) => set({ orientation: event.currentTarget.value })}
                    />
                    <Select
                        label="On mobile"
                        size="sm"
                        options={[
                            { value: 'drawer', label: 'Slide-out drawer' },
                            { value: 'stack', label: 'Stack the links' },
                            { value: 'hide', label: 'Hide' },
                        ]}
                        value={propChoice(props, 'mobileBehaviour', 'drawer')}
                        onChange={(event) => set({ mobileBehaviour: event.currentTarget.value })}
                    />
                </>
            );

        case 'logo':
            return (
                <>
                    <ImageField
                        label="Logo image"
                        hint="A transparent PNG looks best on coloured headers."
                        value={propText(props, 'src')}
                        onChange={(src) => set({ src: src || null })}
                    />
                    <Input
                        label="Alt text"
                        size="sm"
                        hint="Also shown as the logo text when no image is set."
                        value={propText(props, 'alt')}
                        onChange={(event) => set({ alt: event.currentTarget.value })}
                    />
                    <Input
                        label="Logo height (px)"
                        type="number"
                        size="sm"
                        hint="Between 16 and 240. The width follows the picture's shape."
                        value={String(propNumber(props, 'height', 36))}
                        onChange={(event) => set({ height: Math.min(240, Math.max(16, Number(event.currentTarget.value) || 36)) })}
                    />
                    <Input
                        label="Links to"
                        size="sm"
                        value={propText(props, 'url')}
                        onChange={(event) => set({ url: event.currentTarget.value })}
                    />
                </>
            );

        case 'map':
            return (
                <>
                    <Select
                        label="Provider"
                        size="sm"
                        options={options(['osm', 'google'], (value) =>
                            value === 'osm' ? 'OpenStreetMap' : 'Google Maps',
                        )}
                        value={propChoice(props, 'provider', 'osm')}
                        onChange={(event) => set({ provider: event.currentTarget.value })}
                    />
                    <Input
                        label="Address"
                        size="sm"
                        hint="Enough on its own: the map finds the place from the address."
                        value={propText(props, 'address')}
                        onChange={(event) => set({ address: event.currentTarget.value })}
                    />
                    <div className="grid grid-cols-2 gap-2">
                        <Input
                            label="Latitude"
                            size="sm"
                            inputMode="decimal"
                            placeholder="25.1972"
                            value={propText(props, 'latitude')}
                            onChange={(event) => {
                                const raw = event.currentTarget.value.trim();
                                set({ latitude: raw === '' || Number.isNaN(Number(raw)) ? null : Number(raw) });
                            }}
                        />
                        <Input
                            label="Longitude"
                            size="sm"
                            inputMode="decimal"
                            placeholder="55.2744"
                            value={propText(props, 'longitude')}
                            onChange={(event) => {
                                const raw = event.currentTarget.value.trim();
                                set({ longitude: raw === '' || Number.isNaN(Number(raw)) ? null : Number(raw) });
                            }}
                        />
                    </div>
                    <PanelNote>OpenStreetMap needs latitude and longitude; without them the map uses Google.</PanelNote>
                    <Input
                        label="Zoom"
                        type="number"
                        min={1}
                        max={20}
                        size="sm"
                        value={propText(props, 'zoom')}
                        onChange={(event) => set({ zoom: Number(event.currentTarget.value) })}
                    />
                    <Switch
                        size="sm"
                        label="Show a marker"
                        checked={propBool(props, 'showMarker')}
                        onCheckedChange={(checked) => set({ showMarker: checked })}
                    />
                </>
            );

        case 'calendar':
            return (
                <>
                    <BookingServicePicker value={propText(props, 'serviceId')} onChange={(serviceId) => set({ serviceId })} />
                    <BookingSuccessFields props={props} set={set} />
                    <Select
                        label="View"
                        size="sm"
                        options={options(['month', 'week', 'list'], TITLE_CASE)}
                        value={propChoice(props, 'view', 'month')}
                        onChange={(event) => set({ view: event.currentTarget.value })}
                    />
                    <Select
                        label="Timezone"
                        size="sm"
                        options={[
                            { value: 'visitor', label: "The visitor's own" },
                            { value: 'workspace', label: 'The workspace timezone' },
                        ]}
                        value={propChoice(props, 'timezoneMode', 'visitor')}
                        onChange={(event) => set({ timezoneMode: event.currentTarget.value })}
                    />
                </>
            );

        case 'booking':
            return (
                <>
                    <BookingServicePicker value={propText(props, 'serviceId')} onChange={(serviceId) => set({ serviceId })} />
                    <BookingStaffPicker serviceId={propText(props, 'serviceId')} value={propText(props, 'staffId')} onChange={(staffId) => set({ staffId })} />
                    <Switch
                        size="sm"
                        label="Let the visitor pick staff"
                        checked={propBool(props, 'showStaffPicker')}
                        onCheckedChange={(checked) => set({ showStaffPicker: checked })}
                    />
                    <BookingSuccessFields props={props} set={set} />
                </>
            );

        case 'popup_trigger':
            return (
                <>
                    <Input
                        label="Label"
                        size="sm"
                        value={propText(props, 'label')}
                        onChange={(event) => set({ label: event.currentTarget.value })}
                    />
                    <PopupPicker value={propText(props, 'popupId')} onChange={(popupId) => set({ popupId })} />
                    <Select
                        label="Trigger"
                        size="sm"
                        options={[
                            { value: 'click', label: 'On click' },
                            { value: 'delay', label: 'After a delay' },
                            { value: 'scroll', label: 'On scroll depth' },
                            { value: 'exit_intent', label: 'On exit intent' },
                        ]}
                        value={propChoice(props, 'trigger', 'click')}
                        onChange={(event) => set({ trigger: event.currentTarget.value })}
                    />
                    <Input
                        label="Delay (seconds)"
                        type="number"
                        min={0}
                        size="sm"
                        value={propText(props, 'delaySeconds')}
                        onChange={(event) => set({ delaySeconds: Number(event.currentTarget.value) })}
                    />
                    <Input
                        label="Scroll depth (%)"
                        type="number"
                        min={0}
                        max={100}
                        size="sm"
                        value={propText(props, 'scrollPercent')}
                        onChange={(event) => set({ scrollPercent: Number(event.currentTarget.value) })}
                    />
                </>
            );

        // `heading` is handled by its own panel; anything else has no editor yet.
        default:
            return (
                <PanelNote>
                    This block has no content fields yet — style it here, and its own editor arrives with the
                    module that owns it. Nothing in its saved props is lost in the meantime.
                </PanelNote>
            );
    }
}
