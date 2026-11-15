const { React, Inertia, ui, registerPlugin } = window.ModrixCMS;
const { Head, Link, router, useForm, usePage } = Inertia;
const { AdminLayout, Badge, Check, DangerButton, ErrorFlash, Field, Pager } = ui;
const { createElement: h, useEffect, useRef, useState } = React;

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function sendHit(hitUrl) {
    if (!hitUrl || window.location.pathname.startsWith('/admin')) return;
    const payload = {
        path: window.location.pathname || '/',
        title: (document.title || '').slice(0, 200),
        referrer: (document.referrer || '').slice(0, 500),
    };
    fetch(hitUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
        keepalive: true,
    }).catch(() => {});
}

let trackerInstalled = false;
function initTracker() {
    if (trackerInstalled) return;
    trackerInstalled = true;
    let lastPath = null;

    const recordCurrent = () => {
        const current = window.location.pathname;
        if (current === lastPath) return;
        const pageProps = window.__MODRIX_INITIAL_PAGE_PROPS__ || null;
        lastPath = current;
    };

    router.on('navigate', (event) => {
        const hitUrl = event?.detail?.page?.props?.seoAnalytics?.hitUrl;
        if (hitUrl && lastPath !== window.location.pathname) {
            lastPath = window.location.pathname;
            sendHit(hitUrl);
        }
    });

    if (typeof document !== 'undefined') {
        const root = document.getElementById('app');
        if (root && root.dataset.page) {
            try {
                const initial = JSON.parse(root.dataset.page);
                const hitUrl = initial?.props?.seoAnalytics?.hitUrl;
                if (hitUrl) {
                    lastPath = window.location.pathname;
                    sendHit(hitUrl);
                }
            } catch {
                // Ignore malformed initial payload.
            }
        }
    }
    recordCurrent();
}

initTracker();

function SubNav({ active }) {
    const tab = (key, label, routeName) => h(Link, {
        href: route(routeName),
        className: active === key ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm',
    }, label);

    return h('div', { style: { display: 'flex', gap: '0.5rem', flexWrap: 'wrap', marginBottom: '1.25rem' } },
        tab('analytics', 'Analytics', 'plugin.modrix.seo-analytics.analytics.index'),
        tab('redirects', 'Redirects', 'plugin.modrix.seo-analytics.redirects.index'),
        tab('seo', 'SEO & Sitemap', 'plugin.modrix.seo-analytics.seo.index'),
    );
}

function DailyChart({ chart }) {
    const maxViews = Math.max(1, ...chart.map((d) => d.views));
    const width = 760;
    const height = 180;
    const padX = 16;
    const padY = 18;
    const innerW = width - padX * 2;
    const innerH = height - padY * 2;
    const barGap = 2;
    const barW = Math.max(4, Math.floor((innerW - barGap * (chart.length - 1)) / Math.max(1, chart.length)));

    return h('div', { className: 'card', style: { padding: '1.25rem', marginBottom: '1.25rem' } },
        h('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.75rem' } },
            h('h3', { style: { margin: 0, fontSize: '1rem' } }, 'Daily Page Views'),
            h('span', { className: 'hint' }, `Peak: ${maxViews} views/day`),
        ),
        h('svg', {
            viewBox: `0 0 ${width} ${height}`,
            style: { width: '100%', height: '180px', display: 'block', overflow: 'visible' },
            role: 'img',
            'aria-label': 'Daily page views chart',
        },
            h('line', { x1: padX, y1: height - padY, x2: width - padX, y2: height - padY, stroke: '#e5e7eb', strokeWidth: 1 }),
            chart.map((point, idx) => {
                const barH = point.views > 0 ? Math.max(4, Math.round((point.views / maxViews) * innerH)) : 2;
                const x = padX + idx * (barW + barGap);
                const y = height - padY - barH;
                return h('g', { key: point.date },
                    h('rect', {
                        x,
                        y,
                        width: barW,
                        height: barH,
                        rx: 3,
                        fill: point.views > 0 ? '#111827' : '#e5e7eb',
                    }, h('title', null, `${point.label}: ${point.views} views`)),
                );
            }),
        ),
        chart.length > 0 && h('div', {
            className: 'hint',
            style: { display: 'flex', justifyContent: 'space-between', marginTop: '0.35rem', fontSize: '0.75rem' },
        },
            h('span', null, chart[0].label),
            chart.length > 2 && h('span', null, chart[Math.floor(chart.length / 2)].label),
            h('span', null, chart[chart.length - 1].label),
        ),
    );
}

function AnalyticsIndex({ days, summary, chart, topPages, topReferrers, devices }) {
    const statCard = (label, value) => h('div', { className: 'card', style: { padding: '1rem 1.25rem' } },
        h('div', { className: 'hint', style: { fontSize: '0.8rem', marginBottom: '0.25rem' } }, label),
        h('div', { style: { fontSize: '1.5rem', fontWeight: 700, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }, value),
    );

    const switchDays = (value) => {
        router.get(route('plugin.modrix.seo-analytics.analytics.index'), { days: value }, { preserveState: true });
    };

    const totalDeviceViews = Math.max(1, devices.reduce((acc, d) => acc + Number(d.total || 0), 0));

    return h('div', null,
        h(Head, { title: 'SEO & Analytics' }),
        h('div', { className: 'page-head' },
            h('div', null,
                h('h1', null, 'Analytics'),
                h('p', { className: 'hint' }, `Privacy-first page views over the last ${days} days.`),
            ),
            h('div', { style: { display: 'flex', gap: '0.4rem' } },
                [7, 30, 90].map((d) => h('button', {
                    key: d,
                    type: 'button',
                    className: days === d ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm',
                    onClick: () => switchDays(d),
                }, `${d}d`)),
            ),
        ),
        h(SubNav, { active: 'analytics' }),
        h('div', {
            style: {
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))',
                gap: '1rem',
                marginBottom: '1.25rem',
            },
        },
            statCard('Total views', summary.total_views),
            statCard('Views today', summary.today_views),
            statCard('Unique paths', summary.unique_pages),
            statCard('Top referrer', summary.top_referrer),
        ),
        h(DailyChart, { chart }),
        h('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: '1.25rem' } },
            h('div', { className: 'card', style: { padding: '1.25rem' } },
                h('h3', { style: { marginTop: 0, fontSize: '1rem' } }, 'Top Pages'),
                topPages.length === 0
                    ? h('p', { className: 'hint' }, 'No views recorded in this period yet.')
                    : h('table', { className: 'table' },
                        h('thead', null, h('tr', null, h('th', null, 'Page'), h('th', { className: 'num' }, 'Views'))),
                        h('tbody', null, topPages.map((row) => h('tr', { key: row.path },
                            h('td', null,
                                h('div', { style: { fontWeight: 500 } }, row.path),
                                row.page_title && h('div', { className: 'hint', style: { fontSize: '0.8rem' } }, row.page_title),
                            ),
                            h('td', { className: 'num' }, row.total),
                        ))),
                    ),
            ),
            h('div', { style: { display: 'flex', flexDirection: 'column', gap: '1.25rem' } },
                h('div', { className: 'card', style: { padding: '1.25rem' } },
                    h('h3', { style: { marginTop: 0, fontSize: '1rem' } }, 'Top Referrers'),
                    topReferrers.length === 0
                        ? h('p', { className: 'hint' }, 'Only direct visits recorded so far.')
                        : h('table', { className: 'table' },
                            h('thead', null, h('tr', null, h('th', null, 'Domain'), h('th', { className: 'num' }, 'Views'))),
                            h('tbody', null, topReferrers.map((row) => h('tr', { key: row.referrer_host },
                                h('td', null, row.referrer_host),
                                h('td', { className: 'num' }, row.total),
                            ))),
                        ),
                ),
                h('div', { className: 'card', style: { padding: '1.25rem' } },
                    h('h3', { style: { marginTop: 0, fontSize: '1rem' } }, 'Devices'),
                    devices.length === 0
                        ? h('p', { className: 'hint' }, 'No device data yet.')
                        : h('div', { style: { display: 'flex', flexDirection: 'column', gap: '0.6rem' } },
                            devices.map((d) => {
                                const pct = Math.round((Number(d.total || 0) / totalDeviceViews) * 100);
                                return h('div', { key: d.device },
                                    h('div', { style: { display: 'flex', justifyContent: 'space-between', fontSize: '0.875rem', marginBottom: '0.2rem' } },
                                        h('span', { style: { textTransform: 'capitalize', fontWeight: 500 } }, d.device),
                                        h('span', { className: 'hint' }, `${d.total} (${pct}%)`),
                                    ),
                                    h('div', { style: { height: '6px', background: '#e5e7eb', borderRadius: '999px', overflow: 'hidden' } },
                                        h('div', { style: { height: '100%', width: `${pct}%`, background: '#111827' } }),
                                    ),
                                );
                            }),
                        ),
                ),
            ),
        ),
    );
}
AnalyticsIndex.layout = (page) => h(AdminLayout, null, page);

function RedirectsIndex({ redirects }) {
    const [editing, setEditing] = useState(null);
    const form = useForm({
        from_path: '',
        to_url: '',
        status_code: '301',
        is_active: true,
    });

    const startEdit = (item) => {
        setEditing(item);
        form.setData({
            from_path: item.from_path,
            to_url: item.to_url,
            status_code: String(item.status_code),
            is_active: Boolean(item.is_active),
        });
        form.clearErrors();
    };

    const resetForm = () => {
        setEditing(null);
        form.setData({ from_path: '', to_url: '', status_code: '301', is_active: true });
        form.clearErrors();
    };

    const submit = (e) => {
        e.preventDefault();
        if (editing) {
            form.put(route('plugin.modrix.seo-analytics.redirects.update', editing.id), {
                onSuccess: () => resetForm(),
            });
        } else {
            form.post(route('plugin.modrix.seo-analytics.redirects.store'), {
                onSuccess: () => resetForm(),
            });
        }
    };

    const toggleActive = (item) => {
        router.put(route('plugin.modrix.seo-analytics.redirects.update', item.id), {
            from_path: item.from_path,
            to_url: item.to_url,
            status_code: String(item.status_code),
            is_active: !item.is_active,
        });
    };

    return h('div', null,
        h(Head, { title: 'URL Redirects' }),
        h('div', { className: 'page-head' },
            h('div', null,
                h('h1', null, 'URL Redirects'),
                h('p', { className: 'hint' }, 'Route old slugs to new destinations with 301 or 302 redirects and hit counters.'),
            ),
        ),
        h(SubNav, { active: 'redirects' }),
        h('div', { style: { display: 'grid', gridTemplateColumns: 'minmax(280px, 340px) 1fr', gap: '1.25rem', alignItems: 'start' } },
            h('form', { className: 'card', style: { padding: '1.25rem' }, onSubmit: submit },
                h('h3', { style: { marginTop: 0, fontSize: '1rem' } }, editing ? 'Edit Redirect' : 'Add Redirect'),
                h(Field, { label: 'From path', error: form.errors.from_path, hint: 'Relative path e.g. /old-pricing' },
                    h('input', {
                        type: 'text',
                        value: form.data.from_path,
                        onChange: (e) => form.setData('from_path', e.target.value),
                        placeholder: '/old-slug',
                        required: true,
                    }),
                ),
                h(Field, { label: 'Destination URL', error: form.errors.to_url, hint: 'Path (/new) or full URL (https://...)' },
                    h('input', {
                        type: 'text',
                        value: form.data.to_url,
                        onChange: (e) => form.setData('to_url', e.target.value),
                        placeholder: '/new-slug',
                        required: true,
                    }),
                ),
                h(Field, { label: 'HTTP status', error: form.errors.status_code },
                    h('select', {
                        value: form.data.status_code,
                        onChange: (e) => form.setData('status_code', e.target.value),
                    },
                        h('option', { value: '301' }, '301 — Permanent'),
                        h('option', { value: '302' }, '302 — Temporary'),
                    ),
                ),
                h(Check, {
                    label: 'Redirect active',
                    checked: form.data.is_active,
                    onChange: (val) => form.setData('is_active', val),
                }),
                h('div', { style: { display: 'flex', gap: '0.5rem', marginTop: '1rem' } },
                    h('button', { type: 'submit', className: 'btn btn-primary', disabled: form.processing },
                        editing ? 'Update redirect' : 'Create redirect',
                    ),
                    editing && h('button', { type: 'button', className: 'btn btn-ghost', onClick: resetForm }, 'Cancel'),
                ),
            ),
            h('div', { className: 'card' },
                redirects.data.length === 0
                    ? h('p', { className: 'empty' }, 'No redirects configured yet.')
                    : h('div', null,
                        h('table', { className: 'table' },
                            h('thead', null, h('tr', null,
                                h('th', null, 'From'),
                                h('th', null, 'Destination'),
                                h('th', null, 'Code'),
                                h('th', null, 'Status'),
                                h('th', { className: 'num' }, 'Hits'),
                                h('th'),
                            )),
                            h('tbody', null, redirects.data.map((r) => h('tr', { key: r.id },
                                h('td', null, h('code', null, r.from_path)),
                                h('td', null, r.to_url),
                                h('td', null, h(Badge, { tone: r.status_code === 301 ? 'ink' : 'accent' }, String(r.status_code))),
                                h('td', null, h('button', {
                                    type: 'button',
                                    className: 'btn btn-ghost btn-sm',
                                    onClick: () => toggleActive(r),
                                }, r.is_active ? h(Badge, { tone: 'success' }, 'Active') : h(Badge, null, 'Paused'))),
                                h('td', { className: 'num' }, r.hits),
                                h('td', { className: 'actions' },
                                    h('button', { type: 'button', className: 'btn btn-ghost btn-sm', onClick: () => startEdit(r) }, 'Edit'),
                                    h(DangerButton, {
                                        className: 'btn btn-ghost btn-sm',
                                        confirm: `Delete redirect ${r.from_path}?`,
                                        href: route('plugin.modrix.seo-analytics.redirects.destroy', r.id),
                                        method: 'delete',
                                    }, 'Delete'),
                                ),
                            ))),
                        ),
                        h(Pager, { links: redirects.links }),
                    ),
            ),
        ),
    );
}
RedirectsIndex.layout = (page) => h(AdminLayout, null, page);

function SeoAuditIndex({ sitemapUrl, indexedCount, pageChecks, postChecks }) {
    const scorePill = (ok, okLabel, badLabel) => h(Badge, { tone: ok ? 'success' : 'warning' }, ok ? okLabel : badLabel);

    return h('div', null,
        h(Head, { title: 'SEO & Sitemap Audit' }),
        h('div', { className: 'page-head' },
            h('div', null,
                h('h1', null, 'SEO & Sitemap'),
                h('p', { className: 'hint' }, 'Audit published metadata and inspect your automatically generated XML sitemap.'),
            ),
            h('a', { href: sitemapUrl, target: '_blank', rel: 'noopener', className: 'btn btn-primary btn-sm' }, 'Open sitemap.xml ↗'),
        ),
        h(SubNav, { active: 'seo' }),
        h('div', { className: 'card', style: { padding: '1.25rem', marginBottom: '1.25rem', display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '1rem' } },
            h('div', null,
                h('div', { style: { fontWeight: 600 } }, 'XML Sitemap Endpoint'),
                h('code', { className: 'hint' }, sitemapUrl),
            ),
            h(Badge, { tone: 'success' }, `${indexedCount} URLs indexed`),
        ),
        h('div', { className: 'card', style: { marginBottom: '1.25rem' } },
            h('div', { style: { padding: '1rem 1.25rem', borderBottom: '1px solid #e5e7eb', fontWeight: 600 } }, `Published Pages (${pageChecks.length})`),
            pageChecks.length === 0
                ? h('p', { className: 'empty' }, 'No published pages found.')
                : h('table', { className: 'table' },
                    h('thead', null, h('tr', null,
                        h('th', null, 'Title'),
                        h('th', null, 'Meta Title'),
                        h('th', null, 'Meta Description'),
                        h('th', { className: 'num' }, 'Length'),
                        h('th'),
                    )),
                    h('tbody', null, pageChecks.map((p) => h('tr', { key: p.id },
                        h('td', null,
                            h('div', { style: { fontWeight: 500 } }, p.title),
                            h('a', { href: p.url, target: '_blank', rel: 'noopener', className: 'hint', style: { fontSize: '0.8rem' } }, p.url),
                        ),
                        h('td', null, scorePill(p.has_meta_title, 'Set', 'Missing')),
                        h('td', null, scorePill(p.has_meta_description, 'Set', 'Missing')),
                        h('td', { className: 'num' }, `${p.meta_description_length} chars`),
                        h('td', { className: 'actions' }, h(Link, { href: p.edit_url, className: 'btn btn-ghost btn-sm' }, 'Edit')),
                    ))),
                ),
        ),
        h('div', { className: 'card' },
            h('div', { style: { padding: '1rem 1.25rem', borderBottom: '1px solid #e5e7eb', fontWeight: 600 } }, `Published Posts (${postChecks.length})`),
            postChecks.length === 0
                ? h('p', { className: 'empty' }, 'No published posts found.')
                : h('table', { className: 'table' },
                    h('thead', null, h('tr', null,
                        h('th', null, 'Title'),
                        h('th', null, 'Meta / Excerpt'),
                        h('th', { className: 'num' }, 'Length'),
                        h('th'),
                    )),
                    h('tbody', null, postChecks.map((p) => h('tr', { key: p.id },
                        h('td', null,
                            h('div', { style: { fontWeight: 500 } }, p.title),
                            h('a', { href: p.url, target: '_blank', rel: 'noopener', className: 'hint', style: { fontSize: '0.8rem' } }, p.url),
                        ),
                        h('td', null, scorePill(p.has_meta_description, 'Set', 'Missing')),
                        h('td', { className: 'num' }, `${p.meta_description_length} chars`),
                        h('td', { className: 'actions' }, h(Link, { href: p.edit_url, className: 'btn btn-ghost btn-sm' }, 'Edit')),
                    ))),
                ),
        ),
    );
}
SeoAuditIndex.layout = (page) => h(AdminLayout, null, page);

registerPlugin('modrix/seo-analytics', {
    pages: {
        AnalyticsIndex,
        RedirectsIndex,
        SeoAuditIndex,
    },
});
