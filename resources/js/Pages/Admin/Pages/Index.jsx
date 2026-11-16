import { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AdminLayout, { ReferenceEmptyArt } from '@/cms/admin/AdminLayout';
import AiGeneratorWorkspace from '@/cms/admin/AiGeneratorWorkspace';
import { DangerButton, ErrorFlash, Pager } from '@/cms/admin/forms';

/** The shared header and footer of visual-builder pages, edited in the builder like a page. */
function SiteChromePanel({ chrome }) {
    if (!chrome) return null;

    const status = (live) => <span className={`badge ${live ? 'green' : 'gray'}`}>{live ? 'Published' : 'Not published yet'}</span>;

    return (
        <div className="panel">
            <h2>Site header &amp; footer</h2>
            <p className="hint">Built in the visual builder and shown on every visual-builder page. Each theme keeps its own, so these belong to <b>{chrome.theme}</b>; switching themes switches them too. Each page can switch them off in its settings.</p>
            <div className="actions" style={{ flexWrap: 'wrap', gap: 12 }}>
                <span className="actions">{status(chrome.headerLive)}<a className="btn btn-light btn-sm" href={chrome.headerUrl}>Edit site header</a></span>
                <span className="actions">{status(chrome.footerLive)}<a className="btn btn-light btn-sm" href={chrome.footerUrl}>Edit site footer</a></span>
            </div>
        </div>
    );
}

function HomePagePanel({ options, onSelectHome, saving }) {
    const currentHome = options.find((option) => option.is_home) ?? null;
    const [selectedId, setSelectedId] = useState(currentHome ? String(currentHome.id) : (options[0] ? String(options[0].id) : ''));

    useEffect(() => {
        if (currentHome) {
            setSelectedId(String(currentHome.id));
        } else if (options.length > 0) {
            setSelectedId((prev) => (options.some((option) => String(option.id) === prev) ? prev : String(options[0].id)));
        }
    }, [currentHome?.id, options]);

    if (!options.length) {
        return null;
    }

    const unchanged = currentHome !== null && String(currentHome.id) === selectedId;

    return (
        <div className="panel">
            <h2>Home page</h2>
            {currentHome ? (
                <p className="hint">
                    Currently showing <b>{currentHome.title}</b> at <code>/</code>{' '}
                    <span className={`badge ${currentHome.is_published ? 'green' : 'amber'}`}>
                        {currentHome.is_published ? 'Published' : 'Draft · publish to show on /'}
                    </span>
                </p>
            ) : (
                <p className="hint">
                    <span className="badge amber">No home page selected</span> Pick which page visitors see at <code>/</code>.
                </p>
            )}
            <div className="actions" style={{ flexWrap: 'wrap', gap: 12, justifyContent: 'space-between', alignItems: 'center' }}>
                <form
                    className="toolbar"
                    style={{ marginBottom: 0, alignItems: 'center' }}
                    onSubmit={(e) => {
                        e.preventDefault();
                        if (selectedId && !unchanged) {
                            onSelectHome(selectedId);
                        }
                    }}
                >
                    <select
                        aria-label="Home page"
                        value={selectedId}
                        onChange={(e) => setSelectedId(e.target.value)}
                    >
                        {options.map((option) => (
                            <option key={option.id} value={String(option.id)}>
                                {option.title} ({option.is_home ? '/' : `/${option.slug}`})
                                {option.is_published ? '' : ' · Draft'}
                                {option.is_home ? ' · Current' : ''}
                            </option>
                        ))}
                    </select>
                    <button className="btn btn-sm" type="submit" disabled={saving || !selectedId || unchanged}>
                        {saving ? 'Updating…' : 'Set home page'}
                    </button>
                </form>
                {currentHome ? (
                    <div className="actions">
                        {currentHome.is_published ? (
                            <a className="btn btn-light btn-sm" href={currentHome.url} target="_blank" rel="noopener">View /</a>
                        ) : null}
                        {currentHome.editor === 'builder' ? (
                            <a className="btn btn-sm" href={currentHome.builder_url}>Open builder</a>
                        ) : null}
                        <Link className="btn btn-light btn-sm" href={currentHome.edit_url}>
                            {currentHome.editor === 'builder' ? 'Settings' : 'Edit'}
                        </Link>
                    </div>
                ) : null}
            </div>
        </div>
    );
}

export default function PagesIndex({ pages, homeOptions = [], filters, siteChrome, ai }) {
    const { errors } = usePage().props;
    const [q, setQ] = useState(filters.q ?? '');
    const [settingHome, setSettingHome] = useState(false);
    const [aiOpen, setAiOpen] = useState(Boolean(ai?.initialTargetPageId));
    const [aiRequest, setAiRequest] = useState(
        ai?.initialTargetPageId
            ? { mode: 'redesign', pageId: ai.initialTargetPageId, nonce: 1 }
            : null,
    );

    useEffect(() => {
        if (!aiOpen) return undefined;
        const onKeyDown = (e) => {
            if (e.key === 'Escape') {
                setAiOpen(false);
            }
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [aiOpen]);

    const openAiModal = (mode, pageId = null) => {
        setAiRequest((prev) => ({
            mode,
            pageId,
            nonce: (prev?.nonce ?? 0) + 1,
        }));
        setAiOpen(true);
    };

    const setAsHome = (pageId) => {
        setSettingHome(true);
        router.post(
            route('admin.pages.home', pageId),
            {},
            {
                preserveScroll: true,
                onFinish: () => setSettingHome(false),
            },
        );
    };

    return (
        <AdminLayout
            title="Pages"
            subtitle="Every page, including the home page, is built from blocks."
            actions={<>
                <button className="btn btn-light" type="button" onClick={() => openAiModal('single')}>
                    AI generator
                </button>
                <Link className="btn btn-light" href={route('admin.pages.create')}>New block page</Link>
                <button className="btn" type="button" onClick={() => router.post(route('admin.pages.store-builder'))}>New page in visual builder</button>
            </>}
        >
            <ErrorFlash errors={errors} />
            <SiteChromePanel chrome={siteChrome} />
            <HomePagePanel options={homeOptions} onSelectHome={setAsHome} saving={settingHome} />
            <div className="panel">
                <form className="toolbar" onSubmit={(e) => { e.preventDefault(); router.get(route('admin.pages.index'), { q: q || undefined }, { preserveState: true }); }}>
                    <input type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search pages…" />
                    <button className="btn btn-light" type="submit">Search</button>
                    {filters.q ? (
                        <button className="btn btn-light" type="button" onClick={() => { setQ(''); router.get(route('admin.pages.index'), {}, { preserveState: true }); }}>Clear</button>
                    ) : null}
                </form>

                <div className="table-wrap">
                    <table>
                        <thead><tr><th>Title</th><th>Status</th><th>Menu</th><th>Updated</th><th></th></tr></thead>
                        <tbody>
                            {pages.data.length ? pages.data.map((page) => (
                                <tr key={page.id}>
                                    <td className="title">
                                        <Link href={route('admin.pages.edit', page.id)}>{page.title}</Link>
                                        {page.is_home ? <> <span className="badge purple">Home page</span></> : null}
                                        <div className="sub">{page.is_home ? '/' : `/${page.slug}`} · {page.editor === 'builder' ? 'Visual builder' : `${page.block_count} blocks`}{page.unpublished_changes ? ' · unpublished changes' : ''}</div>
                                    </td>
                                    <td><span className={`badge ${page.is_published ? 'green' : 'gray'}`}>{page.is_published ? 'Published' : 'Draft'}</span></td>
                                    <td>{page.show_in_menu ? `Yes (${page.menu_order})` : '—'}</td>
                                    <td className="sub">{page.updated}</td>
                                    <td>
                                        <div className="actions">
                                            {page.is_published ? <a className="btn btn-light btn-sm" href={page.url} target="_blank" rel="noopener">View</a> : null}
                                            {!page.is_home ? (
                                                <button className="btn btn-light btn-sm" type="button" disabled={settingHome} onClick={() => setAsHome(page.id)}>
                                                    Set as home
                                                </button>
                                            ) : null}
                                            <button className="btn btn-light btn-sm" type="button" onClick={() => openAiModal('redesign', page.id)}>AI redesign</button>
                                            {page.editor === 'builder' ? <a className="btn btn-sm" href={page.builder_url}>Open builder</a> : null}
                                            <Link className="btn btn-light btn-sm" href={route('admin.pages.edit', page.id)}>{page.editor === 'builder' ? 'Settings' : 'Edit'}</Link>
                                            {(page.can_delete ?? !page.is_home) ? (
                                                <DangerButton
                                                    url={route('admin.pages.destroy', page.id)}
                                                    confirm={page.is_home ? `Delete the last page “${page.title}”? This can’t be undone.` : `Delete “${page.title}”? This can’t be undone.`}
                                                >
                                                    Delete
                                                </DangerButton>
                                            ) : null}
                                        </div>
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan={5} className="empty">
                                        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', padding: '24px 0' }}>
                                            <ReferenceEmptyArt />
                                            <strong style={{ color: 'var(--ink)', fontSize: 15, marginBottom: 4 }}>Your pages will show here</strong>
                                            <span style={{ maxWidth: 320, marginBottom: 14 }}>No pages found. Build custom storefront pages using blocks or the visual builder.</span>
                                            <Link className="btn" href={route('admin.pages.create')}>Create page</Link>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <Pager paginator={pages} />
            </div>

            {ai && aiOpen ? (
                <div
                    className="ai-modal-backdrop"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="ai-modal-title"
                    onClick={() => setAiOpen(false)}
                >
                    <div className="ai-modal" onClick={(e) => e.stopPropagation()}>
                        <div className="ai-modal-head">
                            <div>
                                <h2 id="ai-modal-title">AI Page &amp; Website Generator</h2>
                                <div className="sub">Generate a new page, build a full multi-page site, or redesign an existing page.</div>
                            </div>
                            <div className="actions" style={{ gap: 8 }}>
                                <Link className="btn btn-light btn-sm" href={route('admin.ai.index')}>Full screen</Link>
                                <button
                                    type="button"
                                    className="btn btn-light btn-sm"
                                    aria-label="Close AI generator modal"
                                    onClick={() => setAiOpen(false)}
                                >
                                    Close ×
                                </button>
                            </div>
                        </div>
                        <div className="ai-modal-body">
                            <AiGeneratorWorkspace
                                {...ai}
                                defaultMode="single"
                                externalRequest={aiRequest}
                                onApplied={() => router.reload({ only: ['pages', 'siteChrome', 'ai'] })}
                            />
                        </div>
                    </div>
                </div>
            ) : null}
        </AdminLayout>
    );
}
