/**
 * The page document contract.
 *
 * Mirrors `App\Domain\Pages\Data\*` and `App\Domain\Pages\Enums\*` one-for-one.
 * If a PHP Data object changes shape, change it here in the same commit — these
 * two files are the contract between the builder, the renderer and the public page.
 */

/* -------------------------------------------------------------------------- */
/* Breakpoints — App\Domain\Pages\Enums\Breakpoint                             */
/* -------------------------------------------------------------------------- */

export type Breakpoint = 'desktop' | 'tablet' | 'mobile';

export const BREAKPOINTS: readonly Breakpoint[] = ['desktop', 'tablet', 'mobile'] as const;

export const BREAKPOINT_LABEL: Record<Breakpoint, string> = {
    desktop: 'Desktop',
    tablet: 'Tablet',
    mobile: 'Mobile',
};

/** `null` means "no ceiling" — desktop is the base tier. */
export const BREAKPOINT_MAX_WIDTH: Record<Breakpoint, number | null> = {
    desktop: null,
    tablet: 1024,
    mobile: 640,
};

/** Resolution order at a breakpoint, most specific first. */
export const BREAKPOINT_CASCADE: Record<Breakpoint, readonly Breakpoint[]> = {
    desktop: ['desktop'],
    tablet: ['tablet', 'desktop'],
    mobile: ['mobile', 'tablet', 'desktop'],
};

/* -------------------------------------------------------------------------- */
/* Block vocabulary — App\Domain\Pages\Enums\BlockType                         */
/* -------------------------------------------------------------------------- */

export type BlockType =
    | 'container'
    | 'heading'
    | 'paragraph'
    | 'image'
    | 'video'
    | 'button'
    | 'icon'
    | 'divider'
    | 'spacer'
    | 'html'
    | 'custom_code'
    | 'form'
    | 'email_optin'
    | 'phone_input'
    | 'checkout'
    | 'product'
    | 'pricing_table'
    | 'countdown'
    | 'testimonial'
    | 'faq'
    | 'accordion'
    | 'tabs'
    | 'progress_bar'
    | 'feature_cards'
    | 'carousel'
    | 'gallery'
    | 'social_icons'
    | 'navigation'
    | 'logo'
    | 'map'
    | 'calendar'
    | 'booking'
    | 'popup_trigger';

export type BlockGroup =
    | 'layout'
    | 'content'
    | 'capture'
    | 'commerce'
    | 'persuasion'
    | 'media'
    | 'scheduling'
    | 'engagement';

export const BLOCK_TYPES: readonly BlockType[] = [
    'container',
    'heading',
    'paragraph',
    'image',
    'video',
    'button',
    'icon',
    'divider',
    'spacer',
    'html',
    'custom_code',
    'form',
    'email_optin',
    'phone_input',
    'checkout',
    'product',
    'pricing_table',
    'countdown',
    'testimonial',
    'faq',
    'accordion',
    'tabs',
    'progress_bar',
    'feature_cards',
    'carousel',
    'gallery',
    'social_icons',
    'navigation',
    'logo',
    'map',
    'calendar',
    'booking',
    'popup_trigger',
] as const;

/**
 * Blocks that need parts of a funnel platform Modrix CMS does not have (forms, commerce,
 * scheduling, popups). They still render if a document contains them, but the builder
 * does not offer them.
 */
export const UNAVAILABLE_BLOCK_TYPES: ReadonlySet<BlockType> = new Set<BlockType>([
    'form', 'email_optin', 'phone_input', 'checkout', 'product', 'calendar', 'booking', 'popup_trigger',
]);

/** The blocks the builder offers in its palettes. */
export const PALETTE_BLOCK_TYPES: readonly BlockType[] = BLOCK_TYPES.filter((type) => !UNAVAILABLE_BLOCK_TYPES.has(type));

export const BLOCK_LABEL: Record<BlockType, string> = {
    container: 'Container',
    heading: 'Heading',
    paragraph: 'Paragraph',
    image: 'Image',
    video: 'Video',
    button: 'Button',
    icon: 'Icon',
    divider: 'Divider',
    spacer: 'Spacer',
    html: 'HTML',
    custom_code: 'Custom code',
    form: 'Form',
    email_optin: 'Email opt-in',
    phone_input: 'Phone input',
    checkout: 'Checkout',
    product: 'Product',
    pricing_table: 'Pricing table',
    countdown: 'Countdown',
    testimonial: 'Testimonial',
    faq: 'FAQ',
    accordion: 'Accordion',
    tabs: 'Tabs',
    progress_bar: 'Progress bar',
    feature_cards: 'Feature cards',
    carousel: 'Carousel',
    gallery: 'Gallery',
    social_icons: 'Social icons',
    navigation: 'Navigation',
    logo: 'Logo',
    map: 'Map',
    calendar: 'Calendar',
    booking: 'Booking',
    popup_trigger: 'Popup trigger',
};

export const BLOCK_GROUP: Record<BlockType, BlockGroup> = {
    container: 'layout',
    heading: 'content',
    paragraph: 'content',
    image: 'content',
    video: 'content',
    button: 'content',
    icon: 'content',
    divider: 'content',
    spacer: 'content',
    html: 'content',
    custom_code: 'content',
    form: 'capture',
    email_optin: 'capture',
    phone_input: 'capture',
    checkout: 'commerce',
    product: 'commerce',
    pricing_table: 'commerce',
    countdown: 'persuasion',
    testimonial: 'persuasion',
    faq: 'persuasion',
    accordion: 'persuasion',
    tabs: 'persuasion',
    progress_bar: 'persuasion',
    feature_cards: 'persuasion',
    carousel: 'media',
    gallery: 'media',
    social_icons: 'media',
    navigation: 'media',
    logo: 'media',
    map: 'media',
    calendar: 'scheduling',
    booking: 'scheduling',
    popup_trigger: 'engagement',
};

export const BLOCK_GROUP_LABEL: Record<BlockGroup, string> = {
    layout: 'Layout',
    content: 'Content',
    capture: 'Capture',
    commerce: 'Commerce',
    persuasion: 'Persuasion',
    media: 'Media and navigation',
    scheduling: 'Scheduling',
    engagement: 'Engagement',
};

/** Only these four hold child blocks — mirrors BlockRegistry::isContainer(). */
export const CONTAINER_BLOCKS: ReadonlySet<BlockType> = new Set<BlockType>([
    'container',
    'accordion',
    'tabs',
    'carousel',
]);

export function isContainerBlock(type: BlockType): boolean {
    return CONTAINER_BLOCKS.has(type);
}

export function isBlockType(value: unknown): value is BlockType {
    return typeof value === 'string' && (BLOCK_TYPES as readonly string[]).includes(value);
}

/* -------------------------------------------------------------------------- */
/* Style model — StyleData / ResponsiveValue / VisibilityData                  */
/* -------------------------------------------------------------------------- */

/** Box values used by padding and margin. */
export interface BoxValue {
    top?: string | null;
    right?: string | null;
    bottom?: string | null;
    left?: string | null;
}

export interface BorderValue extends BoxValue {
    style?: string | null;
    color?: string | null;
}

export interface RadiusValue {
    topLeft?: string | null;
    topRight?: string | null;
    bottomRight?: string | null;
    bottomLeft?: string | null;
}

/**
 * A base (desktop) value plus optional per-breakpoint overrides. Serialised as
 * `{"desktop": "32px", "mobile": "24px"}`.
 */
export interface ResponsiveValue<T = unknown> {
    desktop?: T | null;
    tablet?: T | null;
    mobile?: T | null;
}

export type StyleProperty =
    | 'fontFamily'
    | 'fontSize'
    | 'fontWeight'
    | 'fontStyle'
    | 'lineHeight'
    | 'letterSpacing'
    | 'textTransform'
    | 'textDecoration'
    | 'color'
    | 'backgroundColor'
    | 'backgroundImage'
    | 'backgroundVideo'
    | 'backgroundVideoPoster'
    | 'backgroundSize'
    | 'backgroundPosition'
    | 'backgroundRepeat'
    | 'backgroundAttachment'
    | 'backgroundImageOpacity'
    | 'backgroundImageBrightness'
    | 'backgroundImageContrast'
    | 'backgroundImageSaturation'
    | 'backgroundImageBlur'
    | 'overlayColor'
    | 'overlayImage'
    | 'overlayImageSize'
    | 'overlayImagePosition'
    | 'overlayImageOpacity'
    | 'overlayBlendMode'
    | 'border'
    | 'borderRadius'
    | 'boxShadow'
    | 'textShadow'
    | 'padding'
    | 'margin'
    | 'width'
    | 'minWidth'
    | 'maxWidth'
    | 'height'
    | 'minHeight'
    | 'maxHeight'
    | 'display'
    | 'flexDirection'
    | 'alignItems'
    | 'justifyContent'
    | 'textAlign'
    | 'gap'
    | 'opacity'
    | 'zIndex'
    | 'transform'
    | 'transition';

export const STYLE_PROPERTIES: readonly StyleProperty[] = [
    'fontFamily',
    'fontSize',
    'fontWeight',
    'fontStyle',
    'lineHeight',
    'letterSpacing',
    'textTransform',
    'textDecoration',
    'color',
    'backgroundColor',
    'backgroundImage',
    'backgroundVideo',
    'backgroundVideoPoster',
    'backgroundSize',
    'backgroundPosition',
    'backgroundRepeat',
    'backgroundAttachment',
    'backgroundImageOpacity',
    'backgroundImageBrightness',
    'backgroundImageContrast',
    'backgroundImageSaturation',
    'backgroundImageBlur',
    'overlayColor',
    'overlayImage',
    'overlayImageSize',
    'overlayImagePosition',
    'overlayImageOpacity',
    'overlayBlendMode',
    'border',
    'borderRadius',
    'boxShadow',
    'textShadow',
    'padding',
    'margin',
    'width',
    'minWidth',
    'maxWidth',
    'height',
    'minHeight',
    'maxHeight',
    'display',
    'flexDirection',
    'alignItems',
    'justifyContent',
    'textAlign',
    'gap',
    'opacity',
    'zIndex',
    'transform',
    'transition',
] as const;

export type StyleData = Partial<Record<StyleProperty, ResponsiveValue | null>> & {
    /** Escape hatch keyed by raw CSS property name. */
    custom?: Record<string, ResponsiveValue> | null;
};

export type AudienceField = 'known_contact' | 'tag' | 'lifecycle_stage' | 'purchased_product' | 'country' | 'utm_source';

export interface AudienceRule {
    field: AudienceField;
    operator: 'is' | 'is_not';
    value: string;
}

/** Who sees a node on the published page; evaluated on the server (§63). */
export interface AudienceData {
    match: 'all' | 'any';
    rules: AudienceRule[];
}

export interface VisibilityData {
    desktop: boolean;
    tablet: boolean;
    mobile: boolean;
    audience?: AudienceData | null;
}

/* -------------------------------------------------------------------------- */
/* Document tree — Page -> Section[] -> Row[] -> Column[] -> Block[]           */
/* -------------------------------------------------------------------------- */

export interface BlockData {
    id: string;
    type: BlockType;
    props: Record<string, unknown>;
    style?: StyleData | null;
    visibility?: VisibilityData | null;
    children?: BlockData[];
    name?: string | null;
    globalComponentId?: string | null;
}

export interface ColumnSpanData {
    desktop: number;
    tablet?: number | null;
    mobile?: number | null;
}

export const COLUMN_GRID = 12;

export interface ColumnData {
    id: string;
    span: ColumnSpanData;
    style?: StyleData | null;
    visibility?: VisibilityData | null;
    blocks: BlockData[];
    name?: string | null;
}

export interface RowData {
    id: string;
    style?: StyleData | null;
    visibility?: VisibilityData | null;
    columns: ColumnData[];
    name?: string | null;
    /** Breakpoint at and below which the columns stack. */
    stackBelow?: string | null;
}

export interface SectionData {
    id: string;
    style?: StyleData | null;
    visibility?: VisibilityData | null;
    rows: RowData[];
    name?: string | null;
    width: string;
    sticky: boolean;
    settings: Record<string, unknown>;
    globalComponentId?: string | null;
}

export interface PageSettingsData {
    contentWidth: string;
    body?: StyleData | null;
    colors: Record<string, unknown>;
    fonts: Record<string, unknown>;
    customFonts: unknown[];
    customCss?: string | null;
    headScripts?: string | null;
    bodyScripts?: string | null;
    showCookieBanner: boolean;
}

export interface PageDocument {
    /** PHP serialises this as `schema_version`; `normaliseDocument` accepts both. */
    schemaVersion: number;
    settings: PageSettingsData;
    sections: SectionData[];
}

export type RenderMode = 'static' | 'editable';

/** Every node in the tree, discriminated for the builder selection model. */
export type NodeKind = 'section' | 'row' | 'column' | 'block';

export interface NodeRef {
    kind: NodeKind;
    id: string;
}
