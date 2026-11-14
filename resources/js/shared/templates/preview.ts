export interface PreviewPage { name: string; slug: string; document: unknown }
const record = (value: unknown): Record<string, unknown> => value !== null && typeof value === 'object' && !Array.isArray(value) ? value as Record<string, unknown> : {};

export function previewNextHref(props: Record<string, unknown>, pages: PreviewPage[], active: number): string {
    const target = typeof props.targetStepId === 'string' ? props.targetStepId.replace(/^@step:/, '') : null;
    const index = target ? pages.findIndex(page => page.slug === target) : active + 1;
    return index >= 0 && index < pages.length ? `#preview-step-${index}` : '#preview-complete';
}

export function previewStepRuntime(pages: PreviewPage[], active: number) {
    return { nextStepUrl: active + 1 < pages.length ? `#preview-step-${active + 1}` : null,
        stepUrls: Object.fromEntries(pages.map((page, index) => [page.slug, `#preview-step-${index}`])), currentStepId: pages[active]?.slug ?? null };
}

export function previewPages(payload: unknown, type: string): PreviewPage[] {
    const source = record(payload);
    if (type === 'funnel') return (Array.isArray(source.steps) ? source.steps : []).map((value, index) => {
        const step = record(value);
        return { name: String(step.name ?? `Step ${index + 1}`), slug: String(step.slug ?? index), document: step.document ?? record(step.page).document ?? {} };
    });
    let document = source.document ?? source;
    if (type === 'section') document = { sections: [source.section] };
    if (type === 'block') document = { sections: [{ rows: [{ columns: [{ span: 12, blocks: [source.block] }] }] }] };
    return [{ name: String(record(source.page).name ?? 'Template preview'), slug: 'page', document }];
}

/** Replace runtime-only actions with local preview actions; never modify the template. */
export function previewDocument(value: unknown, pages: PreviewPage[], active: number): unknown {
    if (Array.isArray(value)) return value.map(item => previewDocument(item, pages, active));
    if (!value || typeof value !== 'object') return value;
    const result = Object.fromEntries(Object.entries(value).map(([key, item]) => [key, previewDocument(item, pages, active)]));
    // The public renderer hides unconnected forms. A preview-only identifier
    // renders their fallback fields; the sandbox intercepts every submission.
    if (['form', 'email_optin', 'phone_input'].includes(String(result.type))) {
        const props = record(result.props);
        result.props = { ...props, formId: `template-preview-${String(result.id ?? 'demo')}`,
            previewNextHref: props.successAction === 'next_step' ? previewNextHref(props, pages, active) : null };
    }
    if (['booking', 'calendar', 'checkout'].includes(String(result.type))) {
        const props = record(result.props);
        result.props = { ...props, previewNextHref: result.type === 'checkout' || props.confirmationAction === 'next_step' ? previewNextHref(props, pages, active) : null };
    }
    if (result.action === 'next_step') {
        const href = previewNextHref(result, pages, active);
        result.action = 'url';
        result.url = href;
        result.buttonUrl = result.url;
    } else if (result.action === 'submit_form') {
        result.action = 'url'; result.url = '#preview-submit';
    } else if (result.action === 'upsell_purchase') {
        result.action = 'url'; result.url = previewNextHref(result, pages, active).replace('#preview-step-', '#preview-payment-'); result.buttonUrl = result.url;
    }
    return result;
}

/** Only presentation data is exposed to the sandbox; never a workspace form ID. */
export function previewForms(value: unknown): Record<string, { fields: unknown[] }> {
    const forms: Record<string, { fields: unknown[] }> = {};
    const walk = (node: unknown): void => {
        if (Array.isArray(node)) { node.forEach(walk); return; }
        const source = record(node);
        if (['form', 'email_optin', 'phone_input'].includes(String(source.type))) {
            const props = record(source.props);
            forms[String(props.formId)] = { fields: Array.isArray(props.templateFields) ? props.templateFields : [] };
        }
        Object.values(source).forEach(child => { if (child && typeof child === 'object') walk(child); });
    };
    walk(value);
    return forms;
}
