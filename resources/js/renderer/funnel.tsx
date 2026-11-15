/**
 * The funnel a published page belongs to, as far as its blocks need to know.
 *
 * A step's page is authored in isolation — the builder edits one document — but
 * on the live site a "Next step" button has to lead somewhere, and a form that
 * says "continue to the next step" on success has to know the address. That
 * address is only known server-side, once the funnel is published and its steps
 * have slugs, so the public page embeds it and the renderer reads it from here.
 *
 * In the builder there is no runtime: buttons stay inert (a click selects the
 * block instead of navigating away) and forms never submit.
 *
 * This module imports nothing from the builder, like the rest of the renderer.
 */

import { createContext, useContext } from 'react';
import { normaliseCommerce, type CommerceRuntime } from './commerce';
import { optionalStr, safeHref } from './props';

import { normaliseConditions, type FieldConditions } from './formLogic';

export interface FunnelRuntime {
    /** Local template rendering only; never accepted from public runtime data. */
    templatePreview?: boolean;
    /** The next published step's URL, or null when this is the last step. */
    nextStepUrl: string | null;
    /** Every published step's URL by step id, in funnel order, so a button can target any step. */
    stepUrls: Record<string, string>;
    /** The step this page belongs to — what a progress bar counts from. */
    currentStepId: string | null;
    /** Products and Stripe details, when the page has checkout or upsell blocks. */
    commerce: CommerceRuntime | null;
    /** The fields of every form a capture block on the page is connected to, by form id. */
    forms: Record<string, PublicFormDefinition>;
}

export interface PublicFormField {
    name: string;
    label: string;
    /** A FormFieldType value: first_name, email, dropdown, checkbox, textarea, … */
    type: string;
    placeholder: string | null;
    helpText: string | null;
    options: string[];
    required: boolean;
    hidden: boolean;
    defaultValue: string | null;
    /** Out of 12. Null means the full width. */
    columnSpan: number | null;
    /** Show or hide the field from earlier answers (§24). */
    conditions?: FieldConditions | null;
}

export interface PublicFormDefinition {
    id: string;
    /** The off-screen input a human leaves empty. */
    honeypot: string;
    /** The hidden input carrying when the form was rendered. */
    timing: string;
    fields: PublicFormField[];
}

/** Narrow a forms payload — from the published page, or from the builder's API calls. */
export function normaliseForms(raw: unknown): Record<string, PublicFormDefinition> {
    const forms: Record<string, PublicFormDefinition> = {};

    if (typeof raw !== 'object' || raw === null) {
        return forms;
    }

    for (const [id, value] of Object.entries(raw as Record<string, unknown>)) {
        if (typeof value !== 'object' || value === null) {
            continue;
        }
        const source = value as Record<string, unknown>;
        const fields = Array.isArray(source['fields']) ? source['fields'] : [];

        forms[id] = {
            id,
            honeypot: typeof source['honeypot'] === 'string' && source['honeypot'] !== '' ? source['honeypot'] : '_hp',
            timing: typeof source['timing'] === 'string' && source['timing'] !== '' ? source['timing'] : '_ts',
            fields: fields
                .filter((field): field is Record<string, unknown> => typeof field === 'object' && field !== null)
                .filter((field) => typeof field['name'] === 'string' && field['name'] !== '')
                .map((field) => ({
                    name: field['name'] as string,
                    label: typeof field['label'] === 'string' ? field['label'] : (field['name'] as string),
                    type: typeof field['type'] === 'string' ? field['type'] : 'text',
                    placeholder: typeof field['placeholder'] === 'string' ? field['placeholder'] : null,
                    helpText:
                        typeof field['help_text'] === 'string'
                            ? field['help_text']
                            : typeof field['helpText'] === 'string'
                              ? field['helpText']
                              : null,
                    options: Array.isArray(field['options'])
                        ? field['options']
                              .map((option) =>
                                  typeof option === 'string' || typeof option === 'number'
                                      ? String(option)
                                      : typeof option === 'object' && option !== null
                                        ? String((option as Record<string, unknown>)['value'] ?? (option as Record<string, unknown>)['label'] ?? '')
                                        : '',
                              )
                              .filter((option) => option !== '')
                        : [],
                    required: field['required'] === true || field['is_required'] === true,
                    hidden: field['hidden'] === true || field['is_hidden'] === true,
                    conditions: normaliseConditions(
                        field['conditions'] ??
                            (typeof field['condition'] === 'object' && field['condition'] !== null && typeof (field['condition'] as Record<string, unknown>)['field'] === 'string' && (field['condition'] as Record<string, unknown>)['field'] !== ''
                                ? {
                                      action: 'show',
                                      match: 'all',
                                      rules: [
                                          {
                                              field: (field['condition'] as Record<string, unknown>)['field'],
                                              operator:
                                                  (field['condition'] as Record<string, unknown>)['operator'] === 'not_empty'
                                                      ? 'is_not_empty'
                                                      : (field['condition'] as Record<string, unknown>)['operator'] === 'is_empty'
                                                        ? 'is_empty'
                                                        : (field['condition'] as Record<string, unknown>)['operator'],
                                              value: (field['condition'] as Record<string, unknown>)['value'] ?? null,
                                          },
                                      ],
                                  }
                                : null),
                    ),
                    defaultValue:
                        typeof field['default_value'] === 'string'
                            ? field['default_value']
                            : typeof field['defaultValue'] === 'string'
                              ? field['defaultValue']
                              : null,
                    columnSpan:
                        typeof field['column_span'] === 'number'
                            ? field['column_span']
                            : typeof field['columnSpan'] === 'number'
                              ? field['columnSpan']
                              : null,
                })),
        };
    }

    return forms;
}

/**
 * Where this page sits in its funnel: 1-based position and step count. Null
 * when the page is not part of a published funnel.
 */
export function stepPosition(runtime: FunnelRuntime | null): { current: number; total: number } | null {
    if (!runtime?.currentStepId) {
        return null;
    }
    const ids = Object.keys(runtime.stepUrls);
    const index = ids.indexOf(runtime.currentStepId);

    return index === -1 ? null : { current: index + 1, total: ids.length };
}

const FunnelContext = createContext<FunnelRuntime | null>(null);

export const FunnelProvider = FunnelContext.Provider;

export function useFunnel(): FunnelRuntime | null {
    return useContext(FunnelContext);
}

/**
 * Narrow whatever the page embedded. Every URL goes through `safeHref`, because
 * the payload sits in the page's HTML and a runtime is no reason to trust it.
 */
export function normaliseFunnelRuntime(raw: unknown): FunnelRuntime | null {
    if (typeof raw !== 'object' || raw === null) {
        return null;
    }

    const source = raw as Record<string, unknown>;
    const stepUrls: Record<string, string> = {};

    const rawUrls = source['step_urls'] ?? source['stepUrls'];
    if (typeof rawUrls === 'object' && rawUrls !== null) {
        for (const [id, url] of Object.entries(rawUrls as Record<string, unknown>)) {
            const href = safeHref(url);
            if (href) {
                stepUrls[id] = href;
            }
        }
    }

    const currentStepId = source['current_step_id'] ?? source['currentStepId'];

    return {
        nextStepUrl: safeHref(source['next_step_url'] ?? source['nextStepUrl']) ?? null,
        stepUrls,
        currentStepId: typeof currentStepId === 'string' && currentStepId !== '' ? currentStepId : null,
        commerce: normaliseCommerce(source['commerce']),
        forms: normaliseForms(source['forms']),
    };
}

/* -------------------------------------------------------------------------- */
/* Button actions                                                              */
/* -------------------------------------------------------------------------- */

export type ResolvedAction =
    | { kind: 'link'; href: string }
    | { kind: 'scroll'; targetId: string }
    | { kind: 'submit' }
    | { kind: 'popup'; popupId: string }
    /** Charge the card saved at checkout, then go to `thenHref`. */
    | { kind: 'upsell'; productId: string; thenHref: string | null }
    | { kind: 'none' };

/**
 * What a button with these props does on the published page.
 *
 * `next_step` with a `targetStepId` goes to that step; without one it goes to
 * whichever step follows this one. A target that is not published yet resolves
 * to nothing rather than to a 404.
 */
export function resolveButtonAction(props: Record<string, unknown>, runtime: FunnelRuntime | null): ResolvedAction {
    const action = optionalStr(props, 'action') ?? 'url';

    if (action === 'next_step') {
        const target = optionalStr(props, 'targetStepId');
        const href = safeHref(target ? runtime?.stepUrls[target] : runtime?.nextStepUrl);
        return href ? { kind: 'link', href } : { kind: 'none' };
    }

    if (action === 'upsell_purchase') {
        const productId = optionalStr(props, 'productId');
        const target = optionalStr(props, 'targetStepId');
        const thenHref = safeHref(target ? runtime?.stepUrls[target] : runtime?.nextStepUrl) ?? null;

        // Without a product or payments on the page there is nothing to charge;
        // the button still moves the visitor on rather than doing nothing.
        if (productId && runtime?.commerce?.ready) {
            return { kind: 'upsell', productId, thenHref };
        }
        return thenHref ? { kind: 'link', href: thenHref } : { kind: 'none' };
    }

    if (action === 'scroll_to') {
        const target = optionalStr(props, 'targetBlockId');
        return target ? { kind: 'scroll', targetId: target } : { kind: 'scroll', targetId: '' };
    }

    if (action === 'submit_form') {
        return { kind: 'submit' };
    }

    if (action === 'popup') {
        const popupId = optionalStr(props, 'popupId');
        return popupId ? { kind: 'popup', popupId } : { kind: 'none' };
    }

    const href = safeHref(props['url']);
    return href ? { kind: 'link', href } : { kind: 'none' };
}

/**
 * Scroll to a block by id, or — with no target — to the first form on the page,
 * which is what a "Get started" button above an opt-in almost always means.
 */
export function scrollToTarget(targetId: string): void {
    if (typeof document === 'undefined') {
        return;
    }

    const element = targetId
        ? document.querySelector(`[data-fnl-id="${CSS.escape(targetId)}"]`)
        : document.querySelector('.fnl-page form');

    element?.scrollIntoView({ behavior: 'smooth', block: 'start' });

    const field = element?.querySelector<HTMLElement>('input, textarea, select');
    field?.focus({ preventScroll: true });
}

/* -------------------------------------------------------------------------- */
/* Form submission                                                             */
/* -------------------------------------------------------------------------- */

export type SubmitOutcome =
    | { kind: 'redirect'; href: string }
    | { kind: 'message'; message: string }
    | { kind: 'error'; message: string };

/** Advance only after a successful booking; an unavailable step keeps the receipt visible. */
export function bookingSuccessOutcome(props: Record<string, unknown>, runtime: FunnelRuntime | null, data: { redirect_url?: unknown; message?: unknown }): SubmitOutcome {
    const action = optionalStr(props, 'confirmationAction') ?? 'message';
    if (action === 'redirect' || action === 'next_step') {
        const server = safeHref(data.redirect_url);
        if (server) return { kind: 'redirect', href: server };
        if (action === 'next_step') {
            const target = optionalStr(props, 'targetStepId');
            const href = safeHref(target ? runtime?.stepUrls[target] : runtime?.nextStepUrl);
            if (href) return { kind: 'redirect', href };
        }
    }
    return { kind: 'message', message: optionalStr(props, 'confirmationMessage') ?? (typeof data.message === 'string' && data.message ? data.message : 'You are booked in.') };
}

/**
 * Post a capture form to the public endpoint and decide what happens next.
 *
 * The endpoint answers JSON, so a native form post would strand the visitor on
 * a raw JSON document. Submitting with fetch keeps them on the page, and the
 * success action decides between the next step, a redirect and a message.
 *
 * The form's own server-side redirect wins over the block's setting: it is the
 * workspace's configured behaviour for that form wherever it is embedded.
 */
export async function submitCaptureForm(
    form: HTMLFormElement,
    endpoint: string,
    props: Record<string, unknown>,
    runtime: FunnelRuntime | null,
): Promise<SubmitOutcome> {
    let response: Response;

    try {
        response = await fetch(endpoint, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: new FormData(form),
        });
    } catch {
        return { kind: 'error', message: 'We could not reach the server. Check your connection and try again.' };
    }

    if (response.status === 422) {
        const body = (await response.json().catch(() => null)) as { message?: unknown } | null;
        const message = typeof body?.message === 'string' ? body.message : 'Please check the highlighted fields.';
        return { kind: 'error', message };
    }

    if (!response.ok) {
        return { kind: 'error', message: 'Something went wrong. Please try again in a moment.' };
    }

    const body = (await response.json().catch(() => ({}))) as { message?: unknown; redirect_url?: unknown };
    const serverRedirect = safeHref(body.redirect_url);

    if (serverRedirect) {
        return { kind: 'redirect', href: serverRedirect };
    }

    const successAction = optionalStr(props, 'successAction') ?? 'message';

    if (successAction === 'next_step') {
        const target = optionalStr(props, 'targetStepId');
        const href = safeHref(target ? runtime?.stepUrls[target] : runtime?.nextStepUrl);
        if (href) return { kind: 'redirect', href };
    }

    if (successAction === 'redirect') {
        const href = safeHref(props['redirectUrl']);
        if (href) {
            return { kind: 'redirect', href };
        }
    }

    const message =
        optionalStr(props, 'successMessage') ??
        (typeof body.message === 'string' && body.message !== '' ? body.message : 'Thanks — we got it.');

    return { kind: 'message', message };
}
