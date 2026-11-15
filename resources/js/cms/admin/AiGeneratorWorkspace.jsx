import { Link } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { PageRenderer } from '@renderer';

const STARTER_PROMPTS = [
    {
        label: 'SaaS platform',
        prompt: 'A modern B2B SaaS platform called PulseGrid that helps e-commerce teams automate inventory forecasting, supplier purchase orders, and multi-warehouse fulfillment.',
    },
    {
        label: 'Online storefront',
        prompt: 'A specialty organic coffee roastery & subscription brand called Ember & Bean based in Seattle, offering single-origin beans, brewing gear, and monthly tasting memberships.',
    },
    {
        label: 'Creative studio',
        prompt: 'An independent digital product & brand design studio called Northline Studio serving fintech and climate-tech startups with brand systems, websites, and product UI.',
    },
    {
        label: 'Local service',
        prompt: 'A boutique architectural remodeling & custom cabinetry firm called Oakcraft Homes specializing in kitchen renovations, ADUs, and historic home restorations.',
    },
];

const REDESIGN_GOALS = [
    {
        label: 'Modernize layout',
        prompt: 'Redesign this page with stronger visual hierarchy, balanced section spacing, sharper headlines, and prominent primary calls to action while preserving its core purpose.',
    },
    {
        label: 'Conversion copy',
        prompt: 'Rewrite and restructure this page around clear customer outcomes, key benefits, trust proof, and high-converting calls to action.',
    },
    {
        label: 'Add proof & FAQ',
        prompt: 'Keep the core story of this page, refresh the hero layout, and enrich it with proof metrics, customer testimonials, and a concise FAQ section.',
    },
    {
        label: 'Editorial refresh',
        prompt: 'Give this page a refined, minimal editorial layout with crisp concise copy, clean feature cards, and an inviting closing CTA.',
    },
];

const TONE_OPTIONS = [
    'Modern & clear',
    'Warm editorial',
    'Bold commerce',
    'Minimal studio',
    'Consulting & trust',
];

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function defaultOptionsForMode(targetMode) {
    return {
        replace_existing: true,
        publish_immediately: true,
        update_site_chrome: targetMode === 'multipage',
        update_site_settings: targetMode === 'multipage',
    };
}

export default function AiGeneratorWorkspace({
    aiConfigured,
    aiProviderLabel,
    aiModel,
    settingsUrl,
    siteName,
    defaultPages = [],
    siteCssVars = '',
    existingPages = [],
    initialTargetPageId = null,
    defaultMode = null,
    externalRequest = null,
    onApplied = null,
    onClose = null,
    themeKit = null,
}) {
    const initialMode = initialTargetPageId ? 'redesign' : (defaultMode || 'multipage');
    const [mode, setMode] = useState(initialMode);
    const [targetPageId, setTargetPageId] = useState(
        initialTargetPageId ? String(initialTargetPageId) : existingPages[0] ? String(existingPages[0].id) : '',
    );
    const [prompt, setPrompt] = useState('');
    // Blank unless typed: a pre-filled site name would override the business named in the prompt.
    const [brandName, setBrandName] = useState('');
    const [tone, setTone] = useState(TONE_OPTIONS[0]);
    const [pages, setPages] = useState(defaultPages.length ? defaultPages : ['Home', 'About', 'Services', 'Pricing', 'Contact']);
    const [customPageInput, setCustomPageInput] = useState('');
    const [singlePageTitle, setSinglePageTitle] = useState('Landing page');

    const [generating, setGenerating] = useState(false);
    const [applying, setApplying] = useState(false);
    const [error, setError] = useState('');
    const [plan, setPlan] = useState(null);
    const [selectedSlugs, setSelectedSlugs] = useState([]);
    const [previewSlug, setPreviewSlug] = useState(null);
    const [appliedResult, setAppliedResult] = useState(null);
    const [options, setOptions] = useState(() => defaultOptionsForMode(initialMode));
    // Full sites can be built from the active theme's own designs (its demo sections).
    const [useThemeDesigns, setUseThemeDesigns] = useState(Boolean(themeKit));
    const [progress, setProgress] = useState([]);

    useEffect(() => {
        if (!externalRequest?.nonce) {
            return;
        }
        const nextMode = externalRequest.mode || (externalRequest.pageId ? 'redesign' : 'single');
        setMode(nextMode);
        if (externalRequest.pageId) {
            setTargetPageId(String(externalRequest.pageId));
        } else if (nextMode === 'redesign' && !targetPageId && existingPages[0]) {
            setTargetPageId(String(existingPages[0].id));
        }
        setOptions(defaultOptionsForMode(nextMode));
        setPlan(null);
        setAppliedResult(null);
        setError('');
    }, [externalRequest?.mode, externalRequest?.pageId, externalRequest?.nonce]);

    const switchMode = (nextMode) => {
        setMode(nextMode);
        setError('');
        setOptions(defaultOptionsForMode(nextMode));
        if (nextMode === 'redesign' && !targetPageId && existingPages[0]) {
            setTargetPageId(String(existingPages[0].id));
        }
    };

    const selectedTargetPage = useMemo(
        () => existingPages.find((item) => String(item.id) === String(targetPageId)) ?? null,
        [existingPages, targetPageId],
    );

    const addCustomPage = () => {
        const clean = customPageInput.trim();
        if (!clean || pages.some((item) => item.toLowerCase() === clean.toLowerCase())) {
            return;
        }
        setPages((prev) => [...prev, clean]);
        setCustomPageInput('');
    };

    const removePage = (target) => {
        setPages((prev) => (prev.length > 1 ? prev.filter((item) => item !== target) : prev));
    };

    const postJson = async (routeName, body) => {
        const response = await fetch(route(routeName), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(body),
        });
        const json = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(json?.message || 'The AI request failed.');
        }
        return json.data;
    };

    /**
     * Full site in the active theme's designs: plan, then each page, then the header and
     * footer, one request each so progress shows and no request runs too long.
     */
    const generateWithTheme = async () => {
        const mark = (index, state) => setProgress((prev) => prev.map((step, i) => (i === index ? { ...step, state } : step)));
        setProgress([{ label: `Planning the site with ${themeKit.label} designs`, state: 'active' }]);

        let nextPlan;
        try {
            nextPlan = await postJson('admin.ai.site.plan', { prompt, tone, brand_name: brandName || undefined, pages });
        } catch (err) {
            // Show the step as failed (not as still running) and let the message explain why.
            setProgress([{ label: `Planning the site with ${themeKit.label} designs`, state: 'error' }]);
            throw err;
        }
        setProgress([
            { label: `Planning the site with ${themeKit.label} designs`, state: 'done' },
            ...nextPlan.pages.map((page) => ({ label: `Writing ${page.title}`, state: 'pending' })),
            { label: 'Writing the header & footer', state: 'pending' },
        ]);
        setPlan(nextPlan);
        setSelectedSlugs(nextPlan.pages.map((item) => item.slug));
        setPreviewSlug(nextPlan.pages[0]?.slug ?? null);

        const site = {
            brand: nextPlan.brand,
            theme: nextPlan.theme,
            prompt: nextPlan.prompt,
            tone: nextPlan.tone,
            cta_slug: nextPlan.cta_slug,
            pages: nextPlan.pages.map(({ title, slug, nav_label, is_home, show_in_menu }) => ({ title, slug, nav_label, is_home, show_in_menu })),
        };
        let failures = 0;

        for (let index = 0; index < nextPlan.pages.length; index++) {
            mark(index + 1, 'active');
            try {
                const written = await postJson('admin.ai.site.page', { site, page: nextPlan.pages[index] });
                nextPlan = { ...nextPlan, pages: nextPlan.pages.map((page, i) => (i === index ? written : page)) };
                setPlan(nextPlan);
                mark(index + 1, 'done');
            } catch (err) {
                failures++;
                mark(index + 1, 'error');
            }
        }

        const chromeStep = nextPlan.pages.length + 1;
        mark(chromeStep, 'active');
        try {
            const chrome = await postJson('admin.ai.site.chrome', {
                site,
                header_document: nextPlan.header_document,
                footer_document: nextPlan.footer_document,
            });
            nextPlan = { ...nextPlan, ...chrome, chrome_written: true };
            setPlan(nextPlan);
            mark(chromeStep, 'done');
        } catch (err) {
            failures++;
            mark(chromeStep, 'error');
        }

        if (failures > 0) {
            setError(`${failures} ${failures === 1 ? 'step' : 'steps'} could not be written and keep the theme's sample text. You can generate again or edit them in the builder.`);
        }
    };

    const handleGenerate = async (e) => {
        e.preventDefault();
        setError('');
        setAppliedResult(null);
        setGenerating(true);
        setProgress([]);

        if (mode === 'multipage' && useThemeDesigns && themeKit) {
            try {
                await generateWithTheme();
            } catch (err) {
                setError(err instanceof Error ? err.message : 'Unable to generate website.');
            } finally {
                setGenerating(false);
            }
            return;
        }

        try {
            const response = await fetch(route('admin.ai.generate'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    prompt,
                    mode,
                    tone,
                    brand_name: brandName || undefined,
                    target_page_id: mode === 'redesign' && targetPageId ? Number(targetPageId) : undefined,
                    page_title: mode === 'single' ? singlePageTitle : undefined,
                    pages: mode === 'multipage' ? pages : undefined,
                }),
            });

            const json = await response.json();

            if (!response.ok) {
                throw new Error(json?.message || 'Generation failed.');
            }

            const nextPlan = json.data;
            setPlan(nextPlan);
            setSelectedSlugs((nextPlan.pages || []).map((item) => item.slug));
            setPreviewSlug(nextPlan.pages?.[0]?.slug ?? null);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Unable to generate website.');
        } finally {
            setGenerating(false);
        }
    };

    const togglePageSelection = (slug) => {
        setSelectedSlugs((prev) => (prev.includes(slug) ? prev.filter((item) => item !== slug) : [...prev, slug]));
    };

    const updatePlanPageField = (index, field, value) => {
        setPlan((prev) => {
            if (!prev) {
                return prev;
            }
            const nextPages = prev.pages.map((page, i) => (i === index ? { ...page, [field]: value } : page));
            return { ...prev, pages: nextPages };
        });
    };

    const handleApply = async () => {
        if (!plan || selectedSlugs.length === 0) {
            return;
        }

        setError('');
        setApplying(true);

        try {
            const response = await fetch(route('admin.ai.apply'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    plan,
                    selected_slugs: selectedSlugs,
                    ...options,
                }),
            });

            const json = await response.json();

            if (!response.ok) {
                throw new Error(json?.message || 'Could not apply generated pages.');
            }

            setAppliedResult(json.data);
            if (typeof onApplied === 'function') {
                onApplied(json.data);
            }
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Could not save generated pages.');
        } finally {
            setApplying(false);
        }
    };

    const previewPage = useMemo(
        () => plan?.pages?.find((item) => item.slug === previewSlug) ?? plan?.pages?.[0] ?? null,
        [plan, previewSlug],
    );

    const compositePreviewDocument = useMemo(() => {
        if (!previewPage?.document) {
            return null;
        }

        const headerSections = options.update_site_chrome && previewPage.show_site_header !== false && plan?.header_document?.sections ? plan.header_document.sections : [];
        const footerSections = options.update_site_chrome && previewPage.show_site_footer !== false && plan?.footer_document?.sections ? plan.footer_document.sections : [];

        return {
            ...previewPage.document,
            sections: [
                ...headerSections,
                ...(previewPage.document.sections || []),
                ...footerSections,
            ],
        };
    }, [previewPage, plan, options.update_site_chrome]);

    const generateButtonLabel = generating
        ? mode === 'redesign'
            ? 'Redesigning with AI…'
            : 'Generating with AI…'
        : mode === 'multipage'
            ? (useThemeDesigns && themeKit ? `Generate full website in ${themeKit.label}` : 'Generate full website')
            : mode === 'redesign'
                ? 'Generate redesign'
                : 'Generate page';

    const starterExamples = mode === 'redesign' ? REDESIGN_GOALS : STARTER_PROMPTS;
    const isRedesignPlan = mode === 'redesign' || (plan?.pages || []).some((item) => Boolean(item.target_page_id));

    return (
        <>
            {siteCssVars ? <style>{siteCssVars}</style> : null}

            {!aiConfigured ? (
                <div className="panel" style={{ borderColor: '#f59e0b', background: '#fffbeb' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12, flexWrap: 'wrap' }}>
                        <div>
                            <strong style={{ display: 'block', marginBottom: 4 }}>AI API key is not configured yet</strong>
                            <span className="sub">Add your OpenAI, Anthropic, Gemini, DeepSeek, or OpenAI-compatible API key in Settings → AI API first.</span>
                        </div>
                        <div style={{ display: 'flex', gap: 8 }}>
                            <Link className="btn" href={settingsUrl}>Configure AI API</Link>
                            {onClose ? (
                                <button type="button" className="btn btn-light" onClick={onClose}>Close</button>
                            ) : null}
                        </div>
                    </div>
                </div>
            ) : (
                <div className="panel" style={{ paddingTop: 12, paddingBottom: 12 }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12, flexWrap: 'wrap' }}>
                        <span className="sub">
                            Connected provider: <strong>{aiProviderLabel}</strong> · Model: <code>{aiModel}</code>
                        </span>
                        <div style={{ display: 'flex', gap: 8 }}>
                            <Link className="btn btn-light btn-sm" href={settingsUrl}>Change provider / model</Link>
                            {onClose ? (
                                <button type="button" className="btn btn-light btn-sm" onClick={onClose}>Hide AI studio</button>
                            ) : null}
                        </div>
                    </div>
                </div>
            )}

            {error ? (
                <div className="panel" style={{ borderColor: '#ef4444', background: '#fef2f2', color: '#991b1b' }} role="alert">
                    <strong>Generation error:</strong> {error}
                </div>
            ) : null}

            <div className="grid-2" style={{ alignItems: 'start' }}>
                <form className="panel" onSubmit={handleGenerate}>
                    <h2>1. Describe what to build or redesign</h2>
                    <p className="hint">
                        Generate a full multi-page site, add a new landing page, or modify and redesign an existing page while keeping its URL and version history.
                    </p>

                    <div className="field">
                        <label>Generation mode</label>
                        <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                            <button
                                type="button"
                                className={mode === 'multipage' ? 'btn btn-sm' : 'btn btn-light btn-sm'}
                                onClick={() => switchMode('multipage')}
                            >
                                Full website (multi-page)
                            </button>
                            <button
                                type="button"
                                className={mode === 'single' ? 'btn btn-sm' : 'btn btn-light btn-sm'}
                                onClick={() => switchMode('single')}
                            >
                                Single new page
                            </button>
                            <button
                                type="button"
                                className={mode === 'redesign' ? 'btn btn-sm' : 'btn btn-light btn-sm'}
                                disabled={existingPages.length === 0}
                                onClick={() => switchMode('redesign')}
                            >
                                Modify &amp; redesign page
                            </button>
                        </div>
                    </div>

                    {mode === 'redesign' ? (
                        <div className="field">
                            <label htmlFor="ai-target-page">Existing page to modify &amp; redesign</label>
                            <select
                                id="ai-target-page"
                                value={targetPageId}
                                onChange={(e) => setTargetPageId(e.target.value)}
                            >
                                {existingPages.map((page) => (
                                    <option key={page.id} value={String(page.id)}>
                                        {page.title} ({page.is_home ? '/' : `/${page.slug}`}) · {page.editor === 'builder' ? 'Visual builder' : 'Blocks'}
                                    </option>
                                ))}
                            </select>
                            {selectedTargetPage?.editor !== 'builder' ? (
                                <p className="hint" style={{ marginTop: 6 }}>
                                    AI will read the existing content of <strong>{selectedTargetPage?.title}</strong> and upgrade it into rich visual-builder sections while preserving its URL.
                                </p>
                            ) : (
                                <p className="hint" style={{ marginTop: 6 }}>
                                    AI will inspect the current sections on <strong>{selectedTargetPage?.title}</strong> and redesign them per your instructions. A history snapshot is kept before applying.
                                </p>
                            )}
                        </div>
                    ) : null}

                    <div className="field">
                        <label>{mode === 'redesign' ? 'Quick redesign goals' : 'Quick examples'}</label>
                        <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                            {starterExamples.map((item) => (
                                <button
                                    key={item.label}
                                    type="button"
                                    className="btn btn-light btn-sm"
                                    onClick={() => setPrompt(item.prompt)}
                                >
                                    {item.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="field">
                        <label htmlFor="ai-prompt">
                            {mode === 'redesign' ? 'Redesign & modification instructions' : 'Website prompt'}
                        </label>
                        <textarea
                            id="ai-prompt"
                            rows={5}
                            required
                            minLength={10}
                            value={prompt}
                            onChange={(e) => setPrompt(e.target.value)}
                            placeholder={
                                mode === 'redesign'
                                    ? 'Tell the AI what to change, keep, rewrite, or redesign on this page (e.g. modernize the hero, sharpen headlines, add testimonials & FAQ)…'
                                    : 'Describe your business, products or services, target customers, unique value, and key calls to action…'
                            }
                        />
                    </div>

                    <div className="row">
                        <div className="field">
                            <label htmlFor="ai-brand">Brand name (optional)</label>
                            <input
                                id="ai-brand"
                                type="text"
                                value={brandName}
                                onChange={(e) => setBrandName(e.target.value)}
                                placeholder="Leave blank to use the name in your prompt"
                            />
                        </div>
                        <div className="field">
                            <label htmlFor="ai-tone">Style &amp; tone</label>
                            <select id="ai-tone" value={tone} onChange={(e) => setTone(e.target.value)}>
                                {TONE_OPTIONS.map((option) => (
                                    <option key={option} value={option}>{option}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {mode === 'multipage' ? (
                        <div className="field">
                            <label>Pages in this website ({pages.length})</label>
                            <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap', marginBottom: 8 }}>
                                {pages.map((pageLabel) => (
                                    <span
                                        key={pageLabel}
                                        className="badge purple"
                                        style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: '4px 10px' }}
                                    >
                                        {pageLabel}
                                        {pages.length > 1 ? (
                                            <button
                                                type="button"
                                                aria-label={`Remove ${pageLabel}`}
                                                onClick={() => removePage(pageLabel)}
                                                style={{ border: 0, background: 'transparent', cursor: 'pointer', color: 'inherit', fontWeight: 700, padding: 0 }}
                                            >
                                                ×
                                            </button>
                                        ) : null}
                                    </span>
                                ))}
                            </div>
                            <div style={{ display: 'flex', gap: 8 }}>
                                <input
                                    type="text"
                                    value={customPageInput}
                                    onChange={(e) => setCustomPageInput(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            e.preventDefault();
                                            addCustomPage();
                                        }
                                    }}
                                    placeholder="Add a custom page (e.g. Portfolio, Wholesale)…"
                                />
                                <button type="button" className="btn btn-light btn-sm" onClick={addCustomPage}>Add page</button>
                            </div>
                        </div>
                    ) : null}

                    {mode === 'multipage' ? (
                        <div className="field">
                            <label>Design</label>
                            {themeKit ? (
                                <>
                                    <label className="check">
                                        <input type="radio" name="ai-design" checked={useThemeDesigns} onChange={() => setUseThemeDesigns(true)} />
                                        {' '}Use the <strong>{themeKit.label}</strong> theme&apos;s designs (recommended)
                                    </label>
                                    <p className="hint" style={{ margin: '2px 0 8px 26px' }}>
                                        AI picks from the theme&apos;s {themeKit.section_count} designed sections for each page and rewrites every text, plus the theme&apos;s own header &amp; footer, for your business.
                                    </p>
                                    <label className="check">
                                        <input type="radio" name="ai-design" checked={!useThemeDesigns} onChange={() => setUseThemeDesigns(false)} />
                                        {' '}Generic sections that follow your theme settings
                                    </label>
                                </>
                            ) : (
                                <p className="hint" style={{ margin: 0 }}>
                                    Uses generic sections that follow your theme settings. Themes with a demo (Appearance → Themes) let the AI build the whole site in their own designs.
                                </p>
                            )}
                        </div>
                    ) : mode === 'single' ? (
                        <div className="field">
                            <label htmlFor="ai-single-title">Page title</label>
                            <input
                                id="ai-single-title"
                                type="text"
                                value={singlePageTitle}
                                onChange={(e) => setSinglePageTitle(e.target.value)}
                                placeholder="Landing page"
                            />
                        </div>
                    ) : null}

                    <button
                        className="btn"
                        type="submit"
                        disabled={generating || !aiConfigured || prompt.trim().length < 10 || (mode === 'redesign' && !targetPageId)}
                    >
                        {generateButtonLabel}
                    </button>

                    {progress.length ? (
                        <ol className="ai-progress" style={{ listStyle: 'none', padding: 0, margin: '14px 0 0', display: 'grid', gap: 6 }} aria-live="polite">
                            {progress.map((step, index) => (
                                <li key={index} style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 13, color: step.state === 'pending' ? 'var(--muted)' : 'inherit' }}>
                                    <span aria-hidden style={{ width: 18, textAlign: 'center' }}>
                                        {step.state === 'done' ? '✓' : step.state === 'error' ? '!' : step.state === 'active' ? '…' : '·'}
                                    </span>
                                    <span style={{ color: step.state === 'error' ? '#b91c1c' : undefined, fontWeight: step.state === 'active' ? 600 : 400 }}>
                                        {step.label}{step.state === 'error' ? (index === 0 ? ' (failed)' : ' (kept the sample text)') : ''}
                                    </span>
                                </li>
                            ))}
                        </ol>
                    ) : null}
                </form>

                <div className="panel">
                    <h2>2. {mode === 'redesign' ? 'Redesigned page blueprint' : 'Generated site blueprint'}</h2>
                    {!plan ? (
                        <p className="hint" style={{ marginBottom: 0 }}>
                            Enter your instructions and click <strong>{generateButtonLabel}</strong>. You can review the generated sections, preview the layout live, and choose what to save.
                        </p>
                    ) : (
                        <>
                            <div style={{ marginBottom: 14, paddingBottom: 12, borderBottom: '1px solid var(--line)' }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 8, flexWrap: 'wrap' }}>
                                    <div>
                                        <strong style={{ fontSize: 15 }}>{plan.brand?.site_name}</strong>
                                        <div className="sub">{plan.brand?.tagline}</div>
                                    </div>
                                    <span className="badge green">{plan.pages?.length ?? 0} {plan.pages?.length === 1 ? 'page' : 'pages'} ready</span>
                                </div>
                                {plan.theme?.label ? (
                                    <div className="sub" style={{ marginTop: 8 }}>
                                        Designed with the <strong>{plan.theme.label}</strong> theme · {(plan.pages || []).filter((page) => page.written).length} of {plan.pages?.length ?? 0} pages written{plan.chrome_written ? ' · header & footer written' : ''}
                                    </div>
                                ) : null}
                                {plan.header_links?.length && !isRedesignPlan ? (
                                    <div className="sub" style={{ marginTop: 8 }}>
                                        Header nav: {plan.header_links.map((link) => `${link.label} (${link.url})`).join(' · ')}
                                    </div>
                                ) : null}
                            </div>

                            <div style={{ display: 'flex', flexDirection: 'column', gap: 10, marginBottom: 14 }}>
                                {(plan.pages || []).map((page, index) => {
                                    const checked = selectedSlugs.includes(page.slug);
                                    const isActivePreview = previewPage?.slug === page.slug;
                                    const isExistingTarget = Boolean(page.target_page_id);
                                    return (
                                        <div
                                            key={`${index}-${page.slug}`}
                                            style={{
                                                border: `1px solid ${isActivePreview ? 'var(--brand)' : 'var(--line)'}`,
                                                borderRadius: 10,
                                                padding: 12,
                                                background: isActivePreview ? 'var(--admin-surface-muted)' : 'var(--white)',
                                            }}
                                        >
                                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 8, marginBottom: 8 }}>
                                                <label style={{ display: 'inline-flex', alignItems: 'center', gap: 8, fontWeight: 600 }}>
                                                    <input
                                                        type="checkbox"
                                                        checked={checked}
                                                        onChange={() => togglePageSelection(page.slug)}
                                                    />
                                                    <span>{page.title}</span>
                                                    {page.is_home ? <span className="badge purple">Home /</span> : null}
                                                    {isExistingTarget ? <span className="badge">Redesign target</span> : null}
                                                </label>
                                                <button
                                                    type="button"
                                                    className="btn btn-light btn-sm"
                                                    onClick={() => setPreviewSlug(page.slug)}
                                                >
                                                    {isActivePreview ? 'Previewing' : 'Preview'}
                                                </button>
                                            </div>
                                            <div className="row" style={{ gap: 8 }}>
                                                <input
                                                    type="text"
                                                    value={page.title}
                                                    onChange={(e) => updatePlanPageField(index, 'title', e.target.value)}
                                                    aria-label="Page title"
                                                />
                                                <input
                                                    type="text"
                                                    value={page.slug}
                                                    disabled={page.is_home || isExistingTarget}
                                                    onChange={(e) => {
                                                        const prevSlug = page.slug;
                                                        const nextSlug = e.target.value;
                                                        updatePlanPageField(index, 'slug', nextSlug);
                                                        setSelectedSlugs((curr) => curr.map((item) => (item === prevSlug ? nextSlug : item)));
                                                        if (previewSlug === prevSlug) {
                                                            setPreviewSlug(nextSlug);
                                                        }
                                                    }}
                                                    aria-label="Page slug"
                                                />
                                            </div>
                                            {page.outline?.length ? (
                                                <div className="sub" style={{ marginTop: 8 }}>
                                                    {page.outline.join(' → ')}
                                                </div>
                                            ) : null}
                                        </div>
                                    );
                                })}
                            </div>

                            <div style={{ display: 'flex', flexDirection: 'column', gap: 6, marginBottom: 14 }}>
                                {!isRedesignPlan ? (
                                    <label className="check">
                                        <input
                                            type="checkbox"
                                            checked={options.replace_existing}
                                            onChange={(e) => setOptions((prev) => ({ ...prev, replace_existing: e.target.checked }))}
                                        />
                                        Update existing pages with matching slugs (or Home)
                                    </label>
                                ) : null}
                                <label className="check">
                                    <input
                                        type="checkbox"
                                        checked={options.publish_immediately}
                                        onChange={(e) => setOptions((prev) => ({ ...prev, publish_immediately: e.target.checked }))}
                                    />
                                    Publish generated {isRedesignPlan ? 'changes' : 'pages'} immediately
                                </label>
                                <label className="check">
                                    <input
                                        type="checkbox"
                                        checked={options.update_site_chrome}
                                        onChange={(e) => setOptions((prev) => ({ ...prev, update_site_chrome: e.target.checked }))}
                                    />
                                    Update shared Site header &amp; Site footer navigation
                                </label>
                                <label className="check">
                                    <input
                                        type="checkbox"
                                        checked={options.update_site_settings}
                                        onChange={(e) => setOptions((prev) => ({ ...prev, update_site_settings: e.target.checked }))}
                                    />
                                    Update site name, tagline &amp; footer text in General settings
                                </label>
                            </div>

                            <button
                                type="button"
                                className="btn"
                                disabled={applying || selectedSlugs.length === 0}
                                onClick={handleApply}
                            >
                                {applying
                                    ? 'Saving…'
                                    : isRedesignPlan
                                        ? 'Save redesigned page'
                                        : `Create & save ${selectedSlugs.length} ${selectedSlugs.length === 1 ? 'page' : 'pages'}`}
                            </button>
                        </>
                    )}
                </div>
            </div>

            {appliedResult ? (
                <div className="panel" style={{ borderColor: '#22c55e', background: '#f0fdf4' }}>
                    <h2>{isRedesignPlan ? 'Page redesigned & saved' : 'Website saved'}</h2>
                    <p className="hint">Your generated pages are ready to edit in the visual builder or view live.</p>
                    <div className="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Page</th>
                                    <th>URL</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                {(appliedResult.pages || []).map((page) => (
                                    <tr key={page.id}>
                                        <td className="title">
                                            {page.title}
                                            {page.is_home ? <> <span className="badge purple">Home</span></> : null}
                                        </td>
                                        <td><code>{page.is_home ? '/' : `/${page.slug}`}</code></td>
                                        <td>
                                            <span className={`badge ${page.is_published ? 'green' : 'gray'}`}>
                                                {page.is_published ? 'Published' : 'Draft'}
                                            </span>
                                        </td>
                                        <td>
                                            <div className="actions">
                                                {page.is_published ? (
                                                    <a className="btn btn-light btn-sm" href={page.url} target="_blank" rel="noopener">View</a>
                                                ) : null}
                                                <a className="btn btn-sm" href={page.builder_url}>Open builder</a>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {(appliedResult.header_url || appliedResult.footer_url) ? (
                        <div className="actions" style={{ marginTop: 12, gap: 8 }}>
                            {appliedResult.header_url ? <a className="btn btn-light btn-sm" href={appliedResult.header_url}>Edit global header</a> : null}
                            {appliedResult.footer_url ? <a className="btn btn-light btn-sm" href={appliedResult.footer_url}>Edit global footer</a> : null}
                        </div>
                    ) : null}
                </div>
            ) : null}

            {compositePreviewDocument ? (
                <div className="panel">
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12, flexWrap: 'wrap', gap: 8 }}>
                        <div>
                            <h2>Live layout preview · {previewPage.title}</h2>
                            <span className="sub">/{previewPage.is_home ? '' : previewPage.slug} — {previewPage.meta_title}</span>
                        </div>
                        <span className="badge gray">{previewPage.section_count} sections</span>
                    </div>
                    <div
                        className="ai-site-preview"
                        style={{
                            border: '1px solid var(--line)',
                            borderRadius: 12,
                            overflow: 'hidden',
                            background: 'var(--site-background, #ffffff)',
                            maxHeight: 680,
                            overflowY: 'auto',
                        }}
                    >
                        <PageRenderer document={compositePreviewDocument} mode="static" />
                    </div>
                </div>
            ) : null}
        </>
    );
}
