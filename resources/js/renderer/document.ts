import { editableSectionBackground } from './editableBackground';
/**
 * Document normalisation and node factories.
 *
 * `normaliseDocument` accepts whatever the API sends (`schema_version` or
 * `schemaVersion`, bare style scalars, missing collections) and produces the
 * strict `PageDocument` the renderer and the builder both rely on. The default
 * props mirror `App\Domain\Pages\Schema\BlockRegistry::build()`.
 */

import { toResponsive } from './style';
import {
    COLUMN_GRID,
    STYLE_PROPERTIES,
    isBlockType,
    type BlockData,
    type BlockType,
    type ColumnData,
    type ColumnSpanData,
    type PageDocument,
    type PageSettingsData,
    type RowData,
    type SectionData,
    type StyleData,
    type StyleProperty,
    type VisibilityData,
} from './types';

export const CURRENT_SCHEMA_VERSION = 1;

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function asArray(value: unknown): unknown[] {
    return Array.isArray(value) ? value : [];
}

function asString(value: unknown, fallback: string): string {
    return typeof value === 'string' && value !== '' ? value : fallback;
}

/** Crockford base32 — ULID's alphabet, with I, L, O and U left out. */
const ULID_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

/**
 * A real ULID: 10 characters of timestamp then 16 of randomness.
 *
 * This has to be a genuine ULID, not merely a unique string. Every node id in
 * the document is checked with `Str::isUlid()` by `DocumentValidator`, so an
 * id of the wrong length or alphabet makes the whole save fail with a 422 —
 * and because ids are generated for each new block, that turns into "nothing I
 * add to the page can be saved". Base36 and `Math.random().toString(36)` are
 * both wrong here: the alphabet includes i, l, o and u.
 */
export function newId(): string {
    let id = '';

    // Timestamp, most significant character first, so ids sort by creation.
    let time = Date.now();
    for (let index = 0; index < 10; index += 1) {
        id = ULID_ALPHABET[time % 32] + id;
        time = Math.floor(time / 32);
    }

    const random = new Uint8Array(16);
    if (typeof crypto !== 'undefined' && typeof crypto.getRandomValues === 'function') {
        crypto.getRandomValues(random);
    } else {
        for (let index = 0; index < random.length; index += 1) {
            random[index] = Math.floor(Math.random() * 256);
        }
    }

    for (const byte of random) {
        // One character per byte: 8 bits mapped into 5, which loses entropy
        // per character but keeps 16 characters of it overall.
        id += ULID_ALPHABET[byte % 32];
    }

    return id;
}

/* -------------------------------------------------------------------------- */
/* Block defaults — BlockRegistry::build()                                     */
/* -------------------------------------------------------------------------- */

const BLOCK_DEFAULTS: Record<BlockType, () => Record<string, unknown>> = {
    container: () => ({ layout: 'stack', gap: '16px' }),
    heading: () => ({ text: 'Your headline goes here', level: 'h2', link: null }),
    paragraph: () => ({ text: 'Write something that makes the next click obvious.' }),
    image: () => ({ src: null, alt: '', caption: null, link: null, loading: 'lazy', objectFit: 'cover' }),
    video: () => ({
        provider: 'youtube',
        url: null,
        posterSrc: null,
        autoplay: false,
        muted: true,
        loop: false,
        controls: true,
        aspectRatio: '16:9',
    }),
    button: () => ({
        label: 'Get started',
        action: 'url',
        url: null,
        targetStepId: null,
        targetBlockId: null,
        newTab: false,
        iconLeft: null,
        iconRight: null,
        fullWidth: false,
        size: 'md',
        variant: 'primary',
    }),
    icon: () => ({ name: 'star', library: 'lucide', size: '24px', link: null }),
    divider: () => ({ style: 'solid', thickness: '1px', width: '100%' }),
    spacer: () => ({ height: '48px' }),
    html: () => ({ html: '' }),
    custom_code: () => ({ html: '', css: '', js: '', placement: 'inline' }),
    site_form: () => ({ formSlug: '' }),
    form: () => ({
        formId: null,
        consentText: null,
        submitLabel: 'Submit',
        successAction: 'message',
        targetStepId: null,
        successMessage: 'Thanks — we got it.',
        redirectUrl: null,
        layout: 'stacked',
    }),
    email_optin: () => ({
        formId: null,
        label: 'Email address',
        showLabel: false,
        fieldName: 'email',
        required: true,
        successAction: 'message',
        targetStepId: null,
        successMessage: 'Thanks — we got it.',
        redirectUrl: null,
        placeholder: 'you@example.com',
        submitLabel: 'Send it to me',
        consentText: null,
        tagIds: [],
        layout: 'inline',
    }),
    phone_input: () => ({
        formId: null,
        label: 'Phone number',
        showLabel: true,
        fieldName: 'phone',
        required: true,
        successAction: 'message',
        targetStepId: null,
        successMessage: 'Thanks — we got it.',
        redirectUrl: null,
        placeholder: '+1 555 000 0000',
        defaultCountry: 'US',
        submitLabel: 'Text me',
        requireConsent: true,
        consentText: 'I agree to receive text messages. Reply STOP to opt out.',
        layout: 'inline',
    }),
    checkout: () => ({
        productIds: [],
        showOrderSummary: true,
        collectBillingAddress: false,
        collectPhone: false,
        saveCardForUpsells: true,
        submitLabel: 'Complete order',
        paymentMethods: ['card'],
    }),
    product: () => ({
        productId: null,
        showPrice: true,
        showImage: true,
        showDescription: true,
        buttonLabel: 'Buy now',
    }),
    pricing_table: () => ({
        plans: [
            {
                name: 'Starter',
                price: '$29',
                interval: 'month',
                features: ['Everything to launch', 'Email support'],
                buttonLabel: 'Choose Starter',
                buttonUrl: null,
                highlighted: false,
            },
        ],
        columns: 3,
    }),
    countdown: () => ({
        mode: 'fixed',
        endsAt: null,
        durationMinutes: 60,
        expiredAction: 'hide',
        expiredMessage: 'This offer has closed.',
        redirectUrl: null,
        showLabels: true,
    }),
    testimonial: () => ({
        quote: 'This changed how we launch.',
        authorName: 'Jordan Reyes',
        authorTitle: 'Founder, Northwind',
        avatarSrc: null,
        rating: 5,
        layout: 'card',
    }),
    faq: () => ({
        items: [{ question: 'How long does setup take?', answer: 'About ten minutes.' }],
        allowMultipleOpen: false,
        emitSchemaOrg: true,
    }),
    accordion: () => ({ items: [{ id: null, title: 'Section one' }], allowMultipleOpen: false }),
    tabs: () => ({ tabs: [{ id: null, label: 'Tab one' }], orientation: 'horizontal' }),
    progress_bar: () => ({ value: 40, max: 100, label: 'Step 2 of 5', showPercentage: false, source: 'manual' }),
    feature_cards: () => ({
        items: [
            { icon: 'zap', title: 'Fast', body: 'Ships in a day.' },
            { icon: 'shield', title: 'Safe', body: 'Encrypted at rest.' },
            { icon: 'heart', title: 'Loved', body: 'Rated 4.9 by users.' },
        ],
        columns: 3,
    }),
    carousel: () => ({
        autoplay: false,
        intervalMs: 5000,
        loop: true,
        showArrows: true,
        showDots: true,
        slidesPerView: 1,
    }),
    gallery: () => ({ images: [], columns: 3, gap: '12px', lightbox: true }),
    social_icons: () => ({
        networks: [
            { network: 'x', url: null },
            { network: 'instagram', url: null },
        ],
        size: '20px',
        shape: 'circle',
    }),
    navigation: () => ({
        items: [{ label: 'Home', url: '/' }],
        orientation: 'horizontal',
        mobileBehaviour: 'drawer',
    }),
    logo: () => ({ src: null, alt: null, url: '/' }),
    map: () => ({ provider: 'osm', address: null, latitude: null, longitude: null, zoom: 14, showMarker: true }),
    calendar: () => ({ serviceId: null, view: 'month', timezoneMode: 'visitor', confirmationAction: 'message', confirmationMessage: 'You are booked in.', targetStepId: null }),
    booking: () => ({
        serviceId: null,
        staffId: null,
        showStaffPicker: true,
        confirmationAction: 'message',
        targetStepId: null,
        confirmationMessage: 'You are booked in.',
    }),
    popup_trigger: () => ({ popupId: null, trigger: 'click', delaySeconds: 5, scrollPercent: 50, label: 'Open' }),
};

export function defaultProps(type: BlockType): Record<string, unknown> {
    return BLOCK_DEFAULTS[type]();
}

/* -------------------------------------------------------------------------- */
/* Normalisation                                                               */
/* -------------------------------------------------------------------------- */

function normaliseStyle(value: unknown): StyleData {
    if (!isRecord(value)) {
        return {};
    }
    const style: StyleData = {};
    const custom: Record<string, ReturnType<typeof toResponsive>> = {};

    for (const [property, raw] of Object.entries(value)) {
        if (raw === null || raw === undefined) {
            continue;
        }
        if (property === 'custom') {
            if (isRecord(raw)) {
                for (const [cssProperty, cssValue] of Object.entries(raw)) {
                    custom[cssProperty] = toResponsive(cssValue);
                }
            }
            continue;
        }
        if ((STYLE_PROPERTIES as readonly string[]).includes(property)) {
            style[property as StyleProperty] = toResponsive(raw);
            continue;
        }
        custom[property] = toResponsive(raw);
    }

    if (Object.keys(custom).length > 0) {
        style.custom = custom;
    }
    return style;
}

type Audience = NonNullable<VisibilityData['audience']>;
type AudienceRuleField = Audience['rules'][number]['field'];

const AUDIENCE_FIELDS: readonly string[] = ['known_contact', 'tag', 'lifecycle_stage', 'purchased_product', 'country', 'utm_source'];

/** Audience rules are kept as written; the server enforces them on published pages. */
function normaliseAudience(value: unknown): Audience | null {
    if (!isRecord(value) || !Array.isArray(value['rules'])) {
        return null;
    }
    const rules = value['rules']
        .filter(isRecord)
        .filter((rule) => AUDIENCE_FIELDS.includes(String(rule['field'])))
        .slice(0, 10)
        .map((rule) => ({
            field: rule['field'] as AudienceRuleField,
            operator: rule['operator'] === 'is_not' ? ('is_not' as const) : ('is' as const),
            value: typeof rule['value'] === 'string' ? rule['value'] : '',
        }));
    return rules.length === 0 ? null : { match: value['match'] === 'any' ? 'any' : 'all', rules };
}

function normaliseVisibility(value: unknown): VisibilityData {
    const source = isRecord(value) ? value : {};
    const audience = normaliseAudience(source['audience']);
    return {
        desktop: source['desktop'] !== false,
        tablet: source['tablet'] !== false,
        mobile: source['mobile'] !== false,
        ...(audience ? { audience } : {}),
    };
}

function normaliseSpan(value: unknown): ColumnSpanData {
    const clamp = (input: number) => Math.max(1, Math.min(COLUMN_GRID, Math.round(input)));
    if (typeof value === 'number') {
        return { desktop: clamp(value) };
    }
    if (!isRecord(value)) {
        return { desktop: COLUMN_GRID };
    }
    const span: ColumnSpanData = { desktop: clamp(Number(value['desktop'] ?? COLUMN_GRID) || COLUMN_GRID) };
    if (typeof value['tablet'] === 'number') {
        span.tablet = clamp(value['tablet']);
    }
    if (typeof value['mobile'] === 'number') {
        span.mobile = clamp(value['mobile']);
    }
    return span;
}

export function normaliseBlock(value: unknown): BlockData {
    const source = isRecord(value) ? value : {};
    const type: BlockType = isBlockType(source['type']) ? source['type'] : 'paragraph';

    return {
        id: asString(source['id'], newId()),
        type,
        props: { ...defaultProps(type), ...(isRecord(source['props']) ? source['props'] : {}) },
        style: normaliseStyle(source['style']),
        visibility: normaliseVisibility(source['visibility']),
        children: asArray(source['children']).map(normaliseBlock),
        name: typeof source['name'] === 'string' ? source['name'] : null,
        globalComponentId:
            typeof source['globalComponentId'] === 'string' ? source['globalComponentId'] : null,
    };
}

export function normaliseColumn(value: unknown): ColumnData {
    const source = isRecord(value) ? value : {};
    return {
        id: asString(source['id'], newId()),
        span: normaliseSpan(source['span']),
        style: normaliseStyle(source['style']),
        visibility: normaliseVisibility(source['visibility']),
        blocks: asArray(source['blocks']).map(normaliseBlock),
        name: typeof source['name'] === 'string' ? source['name'] : null,
    };
}

export function normaliseRow(value: unknown): RowData {
    const source = isRecord(value) ? value : {};
    const columns = asArray(source['columns']).map(normaliseColumn);
    return {
        id: asString(source['id'], newId()),
        style: normaliseStyle(source['style']),
        visibility: normaliseVisibility(source['visibility']),
        columns: columns.length > 0 ? columns : [createColumn(COLUMN_GRID)],
        name: typeof source['name'] === 'string' ? source['name'] : null,
        stackBelow: asString(source['stackBelow'], 'mobile'),
    };
}

export function normaliseSection(value: unknown): SectionData {
    const source = isRecord(value) ? value : {};
    const rows = asArray(source['rows']).map(normaliseRow);
    return {
        id: asString(source['id'], newId()),
        style: editableSectionBackground(normaliseStyle(source['style'])),
        visibility: normaliseVisibility(source['visibility']),
        rows: rows.length > 0 ? rows : [createRow()],
        name: typeof source['name'] === 'string' ? source['name'] : null,
        width: asString(source['width'], 'boxed'),
        sticky: source['sticky'] === true,
        settings: isRecord(source['settings']) ? source['settings'] : {},
        globalComponentId:
            typeof source['globalComponentId'] === 'string' ? source['globalComponentId'] : null,
    };
}

export function normaliseSettings(value: unknown): PageSettingsData {
    const source = isRecord(value) ? value : {};
    return {
        // A page without its own width follows Theme settings → Content width (1140px when none is set).
        contentWidth: asString(source['contentWidth'], 'var(--site-content-width, 1140px)'),
        body: normaliseStyle(source['body']),
        colors: isRecord(source['colors']) ? source['colors'] : {},
        fonts: isRecord(source['fonts']) ? source['fonts'] : {},
        customFonts: asArray(source['customFonts']),
        customCss: typeof source['customCss'] === 'string' ? source['customCss'] : null,
        headScripts: typeof source['headScripts'] === 'string' ? source['headScripts'] : null,
        bodyScripts: typeof source['bodyScripts'] === 'string' ? source['bodyScripts'] : null,
        showCookieBanner: source['showCookieBanner'] === true,
    };
}

export function normaliseDocument(value: unknown): PageDocument {
    const source = isRecord(value) ? value : {};
    const version = source['schema_version'] ?? source['schemaVersion'];

    return {
        schemaVersion: typeof version === 'number' ? version : CURRENT_SCHEMA_VERSION,
        settings: normaliseSettings(source['settings']),
        sections: asArray(source['sections']).map(normaliseSection),
    };
}

/** The wire shape the API expects back — `schema_version`, not `schemaVersion`. */
export function serialiseDocument(document: PageDocument): Record<string, unknown> {
    return {
        schema_version: document.schemaVersion,
        settings: document.settings,
        sections: document.sections,
    };
}

/* -------------------------------------------------------------------------- */
/* Factories                                                                   */
/* -------------------------------------------------------------------------- */

export function createBlock(type: BlockType): BlockData {
    return {
        id: newId(),
        type,
        props: defaultProps(type),
        style: {},
        visibility: { desktop: true, tablet: true, mobile: true },
        children: [],
        name: null,
        globalComponentId: null,
    };
}

export function createColumn(span: number = COLUMN_GRID): ColumnData {
    return {
        id: newId(),
        span: { desktop: span },
        style: {},
        visibility: { desktop: true, tablet: true, mobile: true },
        blocks: [],
        name: null,
    };
}

export function createRow(spans: readonly number[] = [COLUMN_GRID]): RowData {
    return {
        id: newId(),
        style: {},
        visibility: { desktop: true, tablet: true, mobile: true },
        columns: spans.map((span) => createColumn(span)),
        name: null,
        stackBelow: 'mobile',
    };
}

export function createSection(name?: string): SectionData {
    return {
        id: newId(),
        style: {},
        visibility: { desktop: true, tablet: true, mobile: true },
        rows: [createRow()],
        name: name ?? null,
        width: 'boxed',
        sticky: false,
        settings: {},
        globalComponentId: null,
    };
}

export function blankDocument(): PageDocument {
    return {
        schemaVersion: CURRENT_SCHEMA_VERSION,
        settings: normaliseSettings(null),
        sections: [createSection('Section 1')],
    };
}
