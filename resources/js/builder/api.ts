/**
 * The builder's server calls.
 *
 * `PUT /api/v1/pages/{id}` is optimistic-concurrency-checked: the request
 * carries the `version` integer the client loaded, and the API answers 409 with
 * the current version when someone else saved in between. Autosave surfaces
 * that as a conflict toast rather than clobbering the other person's work.
 *
 * These routes belong to the Pages module (phase 2) and are not registered yet;
 * see the report at the end of this change set.
 */

import { api } from '@shared/api/client';
import {
    normaliseForms,
    normaliseDocument,
    normaliseSection,
    serialiseDocument,
    type PageDocument,
    type PublicFormDefinition,
    type SectionData,
} from '@renderer';
import { toBuilderFunnel, type BuilderFunnel } from './funnelContext';

export type PageRole = 'header' | 'footer' | null;

export interface PageResponse {
    id: string;
    name: string;
    slug: string;
    role?: PageRole;
    status: 'draft' | 'published';
    version: number;
    document: unknown;
    updated_at: string;
    funnel?: unknown;
}

export interface LoadedPage {
    id: string;
    name: string;
    role: PageRole;
    status: 'draft' | 'published';
    version: number;
    document: PageDocument;
    /** Null for a page that is not attached to a funnel step. */
    funnel: BuilderFunnel | null;
}

export async function loadPage(pageId: string): Promise<LoadedPage> {
    const response = await api.get<{ data: PageResponse }>(`pages/${pageId}`);
    const page = response.data.data;

    return {
        id: page.id,
        name: page.name,
        role: page.role ?? null,
        status: page.status,
        version: page.version,
        document: normaliseDocument(page.document),
        funnel: toBuilderFunnel(page.funnel),
    };
}

export interface SaveResult {
    version: number;
}

export async function savePage(
    pageId: string,
    document: PageDocument,
    version: number,
): Promise<SaveResult> {
    const response = await api.put<{ data: PageResponse }>(`pages/${pageId}`, {
        version,
        document: serialiseDocument(document),
    });

    return { version: response.data.data.version };
}

export async function publishPage(pageId: string, version: number): Promise<SaveResult> {
    const response = await api.post<{ data: PageResponse }>(`pages/${pageId}/publish`, { version });
    return { version: response.data.data.version };
}

export interface PageRevision {
    id: string;
    page_id: string;
    version: number;
    schema_version: number;
    label: string | null;
    created_by_id: number | string | null;
    created_at: string | null;
    document: PageDocument;
}

/** Revision documents are requested here so selecting a row can preview immediately. */
export async function listPageRevisions(pageId: string): Promise<PageRevision[]> {
    const response = await api.get<{ data?: Array<Omit<PageRevision, 'document'> & { document?: unknown }> }>(
        `pages/${pageId}/versions`,
        { params: { per_page: 100, with_document: 1 } },
    );

    return (response.data.data ?? []).map((revision) => ({
        ...revision,
        document: normaliseDocument(revision.document),
    }));
}

export async function restorePageRevision(pageId: string, revisionId: string): Promise<SaveResult & { document: PageDocument }> {
    const response = await api.post<{ data: PageResponse }>(`pages/${pageId}/versions/${revisionId}/restore`);
    return {
        version: response.data.data.version,
        document: normaliseDocument(response.data.data.document),
    };
}

/* -------------------------------------------------------------------------- */
/* Design blocks                                                               */
/* -------------------------------------------------------------------------- */

/**
 * A pre-built arrangement of sections the editor can drop onto a page.
 *
 * `document` is a page-document fragment — `{ sections: [...] }` — normalised
 * on arrival so a hand-authored fragment with missing ids or bare scalar styles
 * lands in the same shape as anything else in the tree.
 */
export interface DesignBlock {
    key: string;
    name: string;
    category: string;
    description: string | null;
    previewHtml: string | null;
    previewSettings?: PageDocument['settings'];
    sections: SectionData[];
}

interface DesignBlockResponse {
    key?: unknown;
    name?: unknown;
    category?: unknown;
    description?: unknown;
    preview_html?: unknown;
    document?: unknown;
}

function asText(value: unknown): string | null {
    return typeof value === 'string' && value.trim() !== '' ? value : null;
}

function toDesignBlock(raw: DesignBlockResponse, index: number): DesignBlock | null {
    const key = asText(raw.key);
    if (key === null) {
        return null;
    }

    const fragment = raw.document;
    const rawSections =
        typeof fragment === 'object' && fragment !== null && Array.isArray((fragment as { sections?: unknown }).sections)
            ? ((fragment as { sections: unknown[] }).sections)
            : [];

    return {
        key,
        name: asText(raw.name) ?? `Design block ${index + 1}`,
        category: asText(raw.category) ?? 'Other',
        description: asText(raw.description),
        previewHtml: asText(raw.preview_html),
        previewSettings: normaliseDocument(fragment).settings,
        sections: rawSections.map(normaliseSection),
    };
}

export interface DesignBlockLibrary {
    blocks: DesignBlock[];
    /** Category key to human label, as the API defines them. */
    categoryLabels: Record<string, string>;
}

/**
 * Rejects when the endpoint is missing; the panel turns that into a "not ready
 * yet" state rather than a crashed builder.
 */
// Platform-authored definitions only: no workspace content is stored here.
let designLibraryCache: { value: DesignBlockLibrary; expires: number } | null = null;
let designLibraryRequest: Promise<DesignBlockLibrary> | null = null;

export function loadDesignBlocks(): Promise<DesignBlockLibrary> {
    if (designLibraryCache && designLibraryCache.expires > Date.now()) {
        return Promise.resolve(designLibraryCache.value);
    }
    if (designLibraryRequest) return designLibraryRequest;
    designLibraryRequest = fetchDesignBlocks().then(value => {
        designLibraryCache = { value, expires: Date.now() + 60_000 };
        return value;
    }).finally(() => { designLibraryRequest = null; });
    return designLibraryRequest;
}

async function fetchDesignBlocks(): Promise<DesignBlockLibrary> {
    const response = await api.get<{ data?: unknown; categories?: unknown }>('design-blocks');
    const rows = Array.isArray(response.data.data) ? response.data.data : [];

    const categoryLabels: Record<string, string> = {};
    if (Array.isArray(response.data.categories)) {
        for (const entry of response.data.categories) {
            if (typeof entry !== 'object' || entry === null) {
                continue;
            }
            const key = asText((entry as { key?: unknown }).key);
            const label = asText((entry as { label?: unknown }).label);
            if (key !== null && label !== null) {
                categoryLabels[key] = label;
            }
        }
    }

    return {
        blocks: rows
            .map((row, index) => toDesignBlock((row ?? {}) as DesignBlockResponse, index))
            .filter((block): block is DesignBlock => block !== null),
        categoryLabels,
    };
}

/* -------------------------------------------------------------------------- */
/* AI assistant                                                                */
/* -------------------------------------------------------------------------- */

export interface GenerateBlockContentInput {
    blockType: string;
    brief: string;
    tone: string | null;
    props: Record<string, unknown>;
}

/**
 * Ask the server to write one block's copy. Resolves to a props patch; rejects
 * with an Error whose message is written for the author (the server's own
 * message where it sent one).
 */
export async function generateBlockContent(
    pageId: string,
    input: GenerateBlockContentInput,
): Promise<Record<string, unknown>> {
    try {
        const response = await api.post<{ data?: { patch?: unknown } }>(`pages/${pageId}/ai/block-content`, {
            block_type: input.blockType,
            brief: input.brief,
            tone: input.tone,
            props: input.props,
        });

        const patch = response.data.data?.patch;

        if (typeof patch !== 'object' || patch === null || Array.isArray(patch)) {
            throw new Error('The AI assistant returned something unusable. Please try again.');
        }

        return patch as Record<string, unknown>;
    } catch (error) {
        // The shared client rejects with a ProblemResponse ({ status, message }),
        // not an axios error, so status and message are read from the top level.
        const problem =
            typeof error === 'object' && error !== null ? (error as { status?: unknown; message?: unknown }) : {};

        if (problem.status === 402) {
            throw new Error('AI tools are not included in your plan.');
        }

        if (typeof problem.message === 'string' && problem.message !== '') {
            throw new Error(problem.message);
        }

        throw new Error('The AI assistant is unavailable right now.');
    }
}

/* -------------------------------------------------------------------------- */
/* Media library                                                               */
/* -------------------------------------------------------------------------- */

export interface MediaItem {
    id: string;
    name: string;
    url: string;
    mime_type: string;
    size: number;
    width: number | null;
    height: number | null;
    alt: string | null;
    created_at: string | null;
}

/** The shared client rejects with a ProblemResponse; surface its message. */
function problemMessage(error: unknown, fallback: string): Error {
    const message =
        typeof error === 'object' && error !== null ? (error as { message?: unknown }).message : undefined;
    return new Error(typeof message === 'string' && message !== '' ? message : fallback);
}

export async function listMedia(): Promise<MediaItem[]> {
    try {
        const response = await api.get<{ data?: MediaItem[] }>('media', { params: { per_page: 100 } });
        return Array.isArray(response.data.data) ? response.data.data : [];
    } catch (error) {
        throw problemMessage(error, 'Could not load your media library.');
    }
}

export async function uploadMedia(file: File, onProgress?: (fraction: number) => void): Promise<MediaItem> {
    const form = new FormData();
    form.append('file', file);

    try {
        const response = await api.post<{ data: MediaItem }>('media', form, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: (event) => {
                if (onProgress && event.total) {
                    onProgress(event.loaded / event.total);
                }
            },
        });
        return response.data.data;
    } catch (error) {
        throw problemMessage(error, 'The file could not be uploaded.');
    }
}

export async function deleteMedia(id: string): Promise<void> {
    try {
        await api.delete(`media/${id}`);
    } catch (error) {
        throw problemMessage(error, 'The file could not be deleted.');
    }
}

/* -------------------------------------------------------------------------- */
/* Commerce catalogue (for checkout and upsell blocks)                          */
/* -------------------------------------------------------------------------- */

export interface CatalogProduct {
    id: string;
    description?: string | null;
    image?: string | null;
    imageAlt?: string | null;
    checkoutUrl?: string | null;
    name: string;
    /** Minor units. */
    price: number;
    salePrice: number | null;
    currency: string;
    status: string;
    type: string;
}

export async function listProducts(): Promise<CatalogProduct[]> {
    try {
        const response = await api.get<{ data?: Record<string, unknown>[] }>('products', { params: { per_page: 100 } });
        const rows = Array.isArray(response.data.data) ? response.data.data : [];

        return rows
            .filter((row) => typeof row['id'] === 'string')
            .map((row) => ({
                id: row['id'] as string,
                name: typeof row['name'] === 'string' ? row['name'] : 'Untitled product',
                price: typeof row['price'] === 'number' ? row['price'] : 0,
                salePrice: typeof row['sale_price'] === 'number' ? row['sale_price'] : null,
                currency: typeof row['currency'] === 'string' ? row['currency'] : 'USD',
                status: typeof row['status'] === 'string' ? row['status'] : 'draft',
                type: typeof row['type'] === 'string' ? row['type'] : 'digital',
                  description: typeof row['description'] === 'string' ? row['description'] : null,
                  image: Array.isArray(row['images']) && typeof row['images'][0]?.url === 'string' ? row['images'][0].url : null,
                  imageAlt: Array.isArray(row['images']) && typeof row['images'][0]?.alt === 'string' ? row['images'][0].alt : null,
                  checkoutUrl: typeof row['checkout_url'] === 'string' ? row['checkout_url'] : null,
            }));
    } catch (error) {
        throw problemMessage(error, 'Could not load your products.');
    }
}

/* -------------------------------------------------------------------------- */
/* Global components (§64)                                                     */
/* -------------------------------------------------------------------------- */

export type GlobalComponentType = 'header' | 'navigation' | 'footer' | 'cta' | 'custom';

export const GLOBAL_COMPONENT_TYPES: ReadonlyArray<{ value: GlobalComponentType; label: string }> = [
    { value: 'header', label: 'Header' },
    { value: 'navigation', label: 'Navigation' },
    { value: 'footer', label: 'Footer' },
    { value: 'cta', label: 'Call to action' },
    { value: 'custom', label: 'Custom' },
];

export interface GlobalComponentSummary {
    id: string;
    name: string;
    type: GlobalComponentType;
    typeLabel: string;
    version: number;
    updatedAt: string | null;
}

export interface LoadedGlobalComponent extends GlobalComponentSummary {
    section: SectionData;
}

export interface GlobalComponentUsage {
    page_id: string;
    page_name: string;
    funnel_id: string | null;
    funnel_name: string | null;
    in_draft: boolean;
    is_live: boolean;
}

function toSummary(row: Record<string, unknown>): GlobalComponentSummary {
    return {
        id: String(row['id']),
        name: asText(row['name']) ?? 'Untitled component',
        type: (asText(row['type']) ?? 'custom') as GlobalComponentType,
        typeLabel: asText(row['type_label']) ?? 'Custom',
        version: typeof row['version'] === 'number' ? row['version'] : 1,
        updatedAt: asText(row['updated_at']),
    };
}

export async function listGlobalComponents(): Promise<GlobalComponentSummary[]> {
    try {
        const response = await api.get<{ data?: Record<string, unknown>[] }>('global-components');
        return (Array.isArray(response.data.data) ? response.data.data : []).map(toSummary);
    } catch (error) {
        throw problemMessage(error, 'Could not load your global components.');
    }
}

export async function loadGlobalComponent(id: string): Promise<LoadedGlobalComponent> {
    const response = await api.get<{ data: Record<string, unknown> }>(`global-components/${id}`);
    const row = response.data.data;

    return { ...toSummary(row), section: normaliseSection(row['section']) };
}

export async function createGlobalComponent(
    name: string,
    type: GlobalComponentType,
    section: SectionData,
): Promise<GlobalComponentSummary> {
    try {
        const response = await api.post<{ data: Record<string, unknown> }>('global-components', {
            name,
            type,
            // A section lifted off the page may still carry a reference if it
            // was detached in this session; a component can never contain one.
            section: { ...section, globalComponentId: null },
        });
        return toSummary(response.data.data);
    } catch (error) {
        throw problemMessage(error, 'The section could not be saved as a global component.');
    }
}

/**
 * Save the component builder's section. Rejects with the raw problem so the
 * caller can tell a 409 conflict from any other failure, exactly as a page
 * save does.
 */
export async function saveGlobalComponent(id: string, section: SectionData, version: number): Promise<SaveResult> {
    const response = await api.put<{ data: Record<string, unknown> }>(`global-components/${id}`, {
        version,
        section: { ...section, globalComponentId: null },
    });

    return { version: toSummary(response.data.data).version };
}

export async function deleteGlobalComponent(id: string): Promise<void> {
    try {
        await api.delete(`global-components/${id}`);
    } catch (error) {
        throw problemMessage(error, 'The component could not be deleted.');
    }
}

/**
 * A ready-to-insert instance. The server mints it so the ids inside match the
 * ones every later save derives — the canvas and the stored page agree.
 */
export async function mintGlobalComponentInstance(id: string): Promise<SectionData> {
    try {
        const response = await api.post<{ data: unknown }>(`global-components/${id}/instances`);
        return normaliseSection(response.data.data);
    } catch (error) {
        throw problemMessage(error, 'The component could not be added to the page.');
    }
}

export async function globalComponentUsage(id: string): Promise<GlobalComponentUsage[]> {
    try {
        const response = await api.get<{ data?: GlobalComponentUsage[] }>(`global-components/${id}/usage`);
        return Array.isArray(response.data.data) ? response.data.data : [];
    } catch (error) {
        throw problemMessage(error, 'Could not load where this component is used.');
    }
}

/* -------------------------------------------------------------------------- */
/* Saved sections (workspace section templates)                                */
/* -------------------------------------------------------------------------- */

export interface SavedSection {
    id: string;
    name: string;
    updatedAt: string | null;
}

/**
 * Sections this workspace saved for reuse. They are section-type templates, so
 * inserting one copies it — unlike a global component, a saved section has no
 * link back to where it came from.
 */
export async function listSavedSections(): Promise<SavedSection[]> {
    try {
        const response = await api.get<{ data?: Record<string, unknown>[] }>('templates', {
            params: { type: 'section', scope: 'mine', per_page: 100 },
        });
        return (Array.isArray(response.data.data) ? response.data.data : []).map((row) => ({
            id: String(row['id']),
            name: asText(row['name']) ?? 'Saved section',
            updatedAt: asText(row['updated_at']),
        }));
    } catch (error) {
        throw problemMessage(error, 'Could not load your saved sections.');
    }
}

export async function loadSavedSection(id: string): Promise<SectionData> {
    try {
        const response = await api.get<{ data?: { payload?: { section?: unknown } } }>(`templates/${id}`);
        return normaliseSection(response.data.data?.payload?.section);
    } catch (error) {
        throw problemMessage(error, 'That saved section could not be loaded.');
    }
}

export async function saveSectionToLibrary(name: string, section: SectionData): Promise<void> {
    try {
        await api.post('templates', {
            name,
            type: 'section',
            // A saved section is a plain copy, never a live reference.
            node: { ...section, globalComponentId: null },
        });
    } catch (error) {
        throw problemMessage(error, 'The section could not be saved to your library.');
    }
}

export async function deleteSavedSection(id: string): Promise<void> {
    try {
        await api.delete(`templates/${id}`);
    } catch (error) {
        throw problemMessage(error, 'The saved section could not be deleted.');
    }
}


/* -------------------------------------------------------------------------- */
/* Forms (for capture blocks)                                                  */
/* -------------------------------------------------------------------------- */

export interface FormOption {
    id: string;
    slug: string;
    name: string;
    isActive: boolean;
}

/** The workspace's forms, for the picker on form, opt-in and phone blocks. */
export async function listForms(): Promise<FormOption[]> {
    try {
        const response = await api.get<{ data?: Record<string, unknown>[] }>('forms', { params: { per_page: 100 } });
        const rows = Array.isArray(response.data.data) ? response.data.data : [];

        return rows
            .filter((row) => typeof row['id'] === 'string')
            .map((row) => ({
                id: row['id'] as string,
                slug: typeof row['slug'] === 'string' ? row['slug'] : '',
                name: typeof row['name'] === 'string' && row['name'] !== '' ? row['name'] : 'Untitled form',
                isActive: row['is_active'] !== false,
            }));
    } catch (error) {
        throw problemMessage(error, 'Could not load your forms.');
    }
}

/**
 * The field definitions of the given forms, in the same shape the published
 * page embeds, so the canvas shows a connected form exactly as visitors will
 * see it. A form that fails to load is simply left out.
 */
export async function loadFormDefinitions(ids: readonly string[]): Promise<Record<string, PublicFormDefinition>> {
    const rows = await Promise.all(
        [...new Set(ids)].map(async (id) => {
            try {
                const response = await api.get<{ data?: Record<string, unknown> }>(`forms/${id}`);
                return response.data.data ? ([id, response.data.data] as const) : null;
            } catch {
                return null;
            }
        }),
    );

    return normaliseForms(Object.fromEntries(rows.filter((row): row is readonly [string, Record<string, unknown>] => row !== null)));
}

export interface PopupOption {
    id: string;
    name: string;
    status: string;
}

/** The workspace's popups, for the picker on button and popup-trigger blocks. */
export async function listPopups(): Promise<PopupOption[]> {
    try {
        const response = await api.get<{ data?: Record<string, unknown>[] }>('popups');
        const rows = Array.isArray(response.data.data) ? response.data.data : [];

        return rows
            .filter((row) => typeof row['id'] === 'string')
            .map((row) => ({
                id: row['id'] as string,
                name: typeof row['name'] === 'string' && row['name'] !== '' ? row['name'] : 'Untitled popup',
                status: typeof row['status'] === 'string' ? row['status'] : 'draft',
            }));
    } catch (error) {
        throw problemMessage(error, 'Could not load your popups.');
    }
}

export interface BookingOption {
    id: string;
    name: string;
    active: boolean;
    serviceIds: string[];
}

function bookingOptions(rows: unknown, fallback: string): BookingOption[] {
    return (Array.isArray(rows) ? (rows as Record<string, unknown>[]) : [])
        .filter((row) => typeof row['id'] === 'string')
        .map((row) => ({
            id: row['id'] as string,
            name: typeof row['name'] === 'string' && row['name'] !== '' ? row['name'] : fallback,
            active: row['is_active'] !== false,
            serviceIds: Array.isArray(row['service_ids']) ? (row['service_ids'] as unknown[]).filter((id): id is string => typeof id === 'string') : [],
        }));
}

/** The workspace's booking services, for calendar and booking blocks. */
export async function listBookingServices(): Promise<BookingOption[]> {
    try {
        const response = await api.get<{ data?: unknown }>('booking/services');
        return bookingOptions(response.data.data, 'Untitled service');
    } catch (error) {
        throw problemMessage(error, 'Could not load your booking services.');
    }
}

/** The people appointments can be booked with. */
export async function listBookingStaff(): Promise<BookingOption[]> {
    try {
        const response = await api.get<{ data?: unknown }>('booking/staff');
        return bookingOptions(response.data.data, 'Unnamed');
    } catch (error) {
        throw problemMessage(error, 'Could not load your staff.');
    }
}
