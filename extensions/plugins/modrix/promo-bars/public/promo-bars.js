const { React, Inertia, ui, registerPlugin } = window.ModrixCMS;
const { Head, router, useForm } = Inertia;
const { AdminLayout, Badge, Check, DangerButton, Field, Html } = ui;
const { createElement: h, useEffect, useState } = React;

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function trackCampaign(trackUrl, eventName) {
    if (!trackUrl) return;
    fetch(trackUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ event: eventName }),
        keepalive: true,
    }).catch(() => {});
}

function formatRemaining(endsAt) {
    if (!endsAt) return '';
    const target = new Date(endsAt).getTime();
    if (Number.isNaN(target)) return '';
    const diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
    if (diff <= 0) return '';
    const days = Math.floor(diff / 86400);
    const hours = Math.floor((diff % 86400) / 3600);
    const mins = Math.floor((diff % 3600) / 60);
    const secs = diff % 60;
    const pad = (n) => String(n).padStart(2, '0');
    return days > 0
        ? `${days}d ${pad(hours)}h ${pad(mins)}m`
        : `${pad(hours)}:${pad(mins)}:${pad(secs)}`;
}

function useCountdown(endsAt) {
    const [text, setText] = useState(() => formatRemaining(endsAt));

    useEffect(() => {
        setText(formatRemaining(endsAt));
        if (!endsAt) return undefined;
        const timer = window.setInterval(() => setText(formatRemaining(endsAt)), 1000);
        return () => window.clearInterval(timer);
    }, [endsAt]);

    return text;
}

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function isDismissed(id) {
    try {
        const raw = window.localStorage.getItem(`modrix_promo_dismiss_${id}`);
        if (!raw) return false;
        return Number(raw) > Date.now();
    } catch {
        return false;
    }
}

function markDismissed(id, days) {
    try {
        const until = Date.now() + Math.max(1, Number(days || 7)) * 86400 * 1000;
        window.localStorage.setItem(`modrix_promo_dismiss_${id}`, String(until));
    } catch {
        // Ignore storage errors.
    }
}

function recordImpressionOnce(campaign) {
    try {
        const key = `modrix_promo_imp_${campaign.id}`;
        if (window.sessionStorage.getItem(key)) return;
        window.sessionStorage.setItem(key, '1');
    } catch {
        // Proceed anyway.
    }
    trackCampaign(campaign.track_url, 'impression');
}

function mountPromos(payload) {
    const host = document.getElementById('modrix-promo-root');
    if (!host) return;
    host.innerHTML = '';

    if (!payload || !Array.isArray(payload.campaigns) || payload.campaigns.length === 0) {
        return;
    }

    const dismissDays = payload.dismissDays || 7;
    const visibleBar = payload.campaigns.find((c) => c.type === 'bar' && !isDismissed(c.id));
    const visiblePopup = payload.campaigns.find((c) => c.type === 'popup' && !isDismissed(c.id));

    if (visibleBar) {
        renderGlobalBar(host, visibleBar, dismissDays);
    }

    if (visiblePopup) {
        scheduleGlobalPopup(host, visiblePopup, dismissDays);
    }
}

function renderGlobalBar(host, campaign, dismissDays) {
    const bar = document.createElement('div');
    bar.className = 'modrix-promo-bar';
    bar.style.background = campaign.bg_color || '#111827';
    bar.style.color = campaign.text_color || '#ffffff';

    const inner = document.createElement('div');
    inner.className = 'modrix-promo-bar-inner';

    if (campaign.badge_text) {
        const badge = document.createElement('span');
        badge.className = 'modrix-promo-badge';
        badge.style.background = campaign.accent_color || '#f59e0b';
        badge.textContent = campaign.badge_text;
        inner.appendChild(badge);
    }

    const title = document.createElement('strong');
    title.textContent = campaign.title;
    inner.appendChild(title);

    if (campaign.coupon_code) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'modrix-promo-coupon';
        btn.textContent = campaign.coupon_code;
        btn.addEventListener('click', () => {
            navigator.clipboard?.writeText(campaign.coupon_code).catch(() => {});
            btn.textContent = `Copied: ${campaign.coupon_code}`;
            window.setTimeout(() => { btn.textContent = campaign.coupon_code; }, 2000);
        });
        inner.appendChild(btn);
    }

    const remaining = formatRemaining(campaign.ends_at);
    if (remaining) {
        const timerEl = document.createElement('span');
        timerEl.className = 'modrix-promo-timer';
        timerEl.textContent = `Ends in ${remaining}`;
        const interval = window.setInterval(() => {
            if (!document.body.contains(timerEl)) {
                window.clearInterval(interval);
                return;
            }
            const next = formatRemaining(campaign.ends_at);
            timerEl.textContent = next ? `Ends in ${next}` : '';
        }, 1000);
        inner.appendChild(timerEl);
    }

    if (campaign.cta_label && campaign.cta_url) {
        const cta = document.createElement('a');
        cta.className = 'modrix-promo-cta';
        cta.style.background = campaign.accent_color || '#f59e0b';
        cta.href = campaign.cta_url;
        cta.textContent = campaign.cta_label;
        cta.addEventListener('click', () => trackCampaign(campaign.track_url, 'click'));
        inner.appendChild(cta);
    }

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'modrix-promo-close';
    close.setAttribute('aria-label', 'Dismiss promotion');
    close.innerHTML = '&times;';
    close.addEventListener('click', () => {
        markDismissed(campaign.id, dismissDays);
        bar.remove();
    });
    inner.appendChild(close);

    bar.appendChild(inner);
    document.body.prepend(bar);
    recordImpressionOnce(campaign);
}

let activePopupTriggerCleanup = null;

function scheduleGlobalPopup(host, campaign, dismissDays) {
    if (activePopupTriggerCleanup) {
        activePopupTriggerCleanup();
        activePopupTriggerCleanup = null;
    }

    const show = () => {
        if (isDismissed(campaign.id)) return;
        const backdrop = document.createElement('div');
        backdrop.className = 'modrix-promo-modal-backdrop';
        backdrop.innerHTML = `
            <div class="modrix-promo-modal" style="background:${escapeHtml(campaign.bg_color || '#111827')};color:${escapeHtml(campaign.text_color || '#ffffff')};">
                <button type="button" class="modrix-promo-close" aria-label="Close promotion">&times;</button>
                ${campaign.badge_text ? `<div style="margin-bottom:0.75rem"><span class="modrix-promo-badge" style="background:${escapeHtml(campaign.accent_color || '#f59e0b')}">${escapeHtml(campaign.badge_text)}</span></div>` : ''}
                <h2 style="margin:0 0 0.6rem;font-size:1.4rem;">${escapeHtml(campaign.title)}</h2>
                ${campaign.body ? `<p style="margin:0 0 1rem;opacity:0.9;">${escapeHtml(campaign.body)}</p>` : ''}
                <div class="modrix-promo-popup-actions" style="display:flex;flex-direction:column;gap:0.75rem;align-items:center;"></div>
            </div>
        `;
        const actions = backdrop.querySelector('.modrix-promo-popup-actions');
        if (campaign.coupon_code && actions) {
            const copyBtn = document.createElement('button');
            copyBtn.type = 'button';
            copyBtn.className = 'modrix-promo-coupon';
            copyBtn.textContent = `Copy code: ${campaign.coupon_code}`;
            copyBtn.addEventListener('click', () => {
                navigator.clipboard?.writeText(campaign.coupon_code).catch(() => {});
                copyBtn.textContent = `Copied: ${campaign.coupon_code}`;
            });
            actions.appendChild(copyBtn);
        }
        if (campaign.cta_label && campaign.cta_url && actions) {
            const cta = document.createElement('a');
            cta.className = 'modrix-promo-cta';
            cta.style.background = campaign.accent_color || '#f59e0b';
            cta.href = campaign.cta_url;
            cta.textContent = campaign.cta_label;
            cta.addEventListener('click', () => trackCampaign(campaign.track_url, 'click'));
            actions.appendChild(cta);
        }
        const dismiss = () => {
            markDismissed(campaign.id, dismissDays);
            backdrop.remove();
        };
        backdrop.querySelector('.modrix-promo-close')?.addEventListener('click', dismiss);
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) dismiss();
        });
        host.appendChild(backdrop);
        recordImpressionOnce(campaign);
    };

    if (campaign.trigger_type === 'delay') {
        const ms = Math.max(1, Number(campaign.trigger_value || 3)) * 1000;
        const id = window.setTimeout(show, ms);
        activePopupTriggerCleanup = () => window.clearTimeout(id);
    } else if (campaign.trigger_type === 'scroll') {
        const threshold = Math.min(95, Math.max(5, Number(campaign.trigger_value || 40)));
        const onScroll = () => {
            const doc = document.documentElement;
            const total = Math.max(1, doc.scrollHeight - window.innerHeight);
            const pct = (window.scrollY / total) * 100;
            if (pct >= threshold) {
                window.removeEventListener('scroll', onScroll);
                show();
            }
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        activePopupTriggerCleanup = () => window.removeEventListener('scroll', onScroll);
    } else {
        show();
    }
}

let promoBooted = false;
function initGlobalPromos() {
    if (promoBooted || typeof document === 'undefined') return;
    promoBooted = true;

    const existingBars = document.querySelectorAll('.modrix-promo-bar');
    existingBars.forEach((el) => el.remove());

    router.on('navigate', (event) => {
        document.querySelectorAll('.modrix-promo-bar').forEach((el) => el.remove());
        mountPromos(event?.detail?.page?.props?.promoBars);
    });

    const root = document.getElementById('app');
    if (root && root.dataset.page) {
        try {
            const initial = JSON.parse(root.dataset.page);
            mountPromos(initial?.props?.promoBars);
        } catch {
            // Ignore malformed payload.
        }
    }
}

initGlobalPromos();

function BannerBlock({ data }) {
    const [copied, setCopied] = useState(false);
    const countdown = useCountdown(data.ends_at);
    const themeClass = `modrix-promo-banner-block modrix-promo-banner-${data.theme || 'dark'}`;

    const copyCode = () => {
        if (!data.coupon_code) return;
        navigator.clipboard?.writeText(data.coupon_code).catch(() => {});
        setCopied(true);
        window.setTimeout(() => setCopied(false), 2000);
    };

    return h('div', { className: themeClass },
        h('div', { style: { flex: '1 1 320px' } },
            data.badge_text && h('span', {
                className: 'modrix-promo-badge',
                style: { background: '#fde68a', marginBottom: '0.6rem' },
            }, data.badge_text),
            h('h3', { style: { margin: '0.25rem 0 0.5rem', fontSize: '1.35rem' } }, data.heading),
            data.body_html
                ? h(Html, { html: data.body_html, style: { opacity: 0.9 } })
                : (data.body && h('p', { style: { margin: 0, opacity: 0.9 } }, data.body)),
            countdown && h('div', { className: 'modrix-promo-timer', style: { marginTop: '0.6rem' } }, `Ends in ${countdown}`),
        ),
        h('div', { style: { display: 'flex', gap: '0.75rem', alignItems: 'center', flexWrap: 'wrap' } },
            data.coupon_code && h('button', {
                type: 'button',
                className: 'modrix-promo-coupon',
                onClick: copyCode,
            }, copied ? `Copied: ${data.coupon_code}` : `Code: ${data.coupon_code}`),
            data.cta_label && data.cta_url && h('a', {
                href: data.cta_url,
                className: 'modrix-promo-cta',
                style: { background: '#fbbf24' },
            }, data.cta_label),
        ),
    );
}

const emptyForm = {
    name: '',
    type: 'bar',
    title: '',
    body: '',
    badge_text: 'Sale',
    cta_label: 'Shop now',
    cta_url: '/',
    coupon_code: '',
    bg_color: '#111827',
    text_color: '#ffffff',
    accent_color: '#f59e0b',
    trigger_type: 'immediate',
    trigger_value: 0,
    ends_at: '',
    is_active: true,
};

function CampaignsIndex({ campaigns, totals }) {
    const [editing, setEditing] = useState(null);
    const form = useForm({ ...emptyForm });

    const editCampaign = (item) => {
        setEditing(item);
        form.setData({
            name: item.name,
            type: item.type,
            title: item.title,
            body: item.body || '',
            badge_text: item.badge_text || '',
            cta_label: item.cta_label || '',
            cta_url: item.cta_url || '',
            coupon_code: item.coupon_code || '',
            bg_color: item.bg_color || '#111827',
            text_color: item.text_color || '#ffffff',
            accent_color: item.accent_color || '#f59e0b',
            trigger_type: item.trigger_type || 'immediate',
            trigger_value: item.trigger_value ?? 0,
            ends_at: item.ends_at || '',
            is_active: Boolean(item.is_active),
        });
        form.clearErrors();
    };

    const resetForm = () => {
        setEditing(null);
        form.setData({ ...emptyForm });
        form.clearErrors();
    };

    const submit = (e) => {
        e.preventDefault();
        if (editing) {
            form.put(route('plugin.modrix.promo-bars.campaigns.update', editing.id), {
                onSuccess: () => resetForm(),
            });
        } else {
            form.post(route('plugin.modrix.promo-bars.campaigns.store'), {
                onSuccess: () => resetForm(),
            });
        }
    };

    const statCard = (label, value) => h('div', { className: 'card', style: { padding: '1rem 1.25rem' } },
        h('div', { className: 'hint', style: { fontSize: '0.8rem', marginBottom: '0.25rem' } }, label),
        h('div', { style: { fontSize: '1.45rem', fontWeight: 700 } }, value),
    );

    return h('div', null,
        h(Head, { title: 'Promotions' }),
        h('div', { className: 'page-head' },
            h('div', null,
                h('h1', null, 'Promo Bars & Popups'),
                h('p', { className: 'hint' }, 'Run site-wide announcement bars, timed or scroll-triggered popups, and track clicks.'),
            ),
        ),
        h('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '1rem', marginBottom: '1.25rem' } },
            statCard('Active campaigns', totals.active),
            statCard('Total impressions', totals.impressions),
            statCard('Total CTA clicks', totals.clicks),
        ),
        h('div', {
            className: 'modrix-promo-bar',
            style: {
                position: 'static',
                borderRadius: '10px',
                marginBottom: '1.25rem',
                background: form.data.bg_color,
                color: form.data.text_color,
            },
        },
            h('div', { className: 'modrix-promo-bar-inner' },
                form.data.badge_text && h('span', { className: 'modrix-promo-badge', style: { background: form.data.accent_color } }, form.data.badge_text),
                h('strong', null, form.data.title || 'Live preview headline'),
                form.data.coupon_code && h('span', { className: 'modrix-promo-coupon' }, form.data.coupon_code),
                form.data.cta_label && h('span', { className: 'modrix-promo-cta', style: { background: form.data.accent_color } }, form.data.cta_label),
            ),
        ),
        h('div', { style: { display: 'grid', gridTemplateColumns: 'minmax(300px, 380px) 1fr', gap: '1.25rem', alignItems: 'start' } },
            h('form', { className: 'card', style: { padding: '1.25rem' }, onSubmit: submit },
                h('h3', { style: { marginTop: 0, fontSize: '1rem' } }, editing ? 'Edit Promotion' : 'New Promotion'),
                h(Field, { label: 'Campaign name (internal)', error: form.errors.name },
                    h('input', { type: 'text', value: form.data.name, onChange: (e) => form.setData('name', e.target.value), required: true, placeholder: 'Spring Flash Sale' }),
                ),
                h(Field, { label: 'Placement type', error: form.errors.type },
                    h('select', { value: form.data.type, onChange: (e) => form.setData('type', e.target.value) },
                        h('option', { value: 'bar' }, 'Top Announcement Bar'),
                        h('option', { value: 'popup' }, 'Modal Popup'),
                    ),
                ),
                h(Field, { label: 'Headline', error: form.errors.title },
                    h('input', { type: 'text', value: form.data.title, onChange: (e) => form.setData('title', e.target.value), required: true, placeholder: 'Get 20% off today only' }),
                ),
                form.data.type === 'popup' && h(Field, { label: 'Popup message', error: form.errors.body },
                    h('textarea', { rows: 2, value: form.data.body, onChange: (e) => form.setData('body', e.target.value), placeholder: 'Apply the code below at checkout.' }),
                ),
                h('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem' } },
                    h(Field, { label: 'Badge', error: form.errors.badge_text },
                        h('input', { type: 'text', value: form.data.badge_text, onChange: (e) => form.setData('badge_text', e.target.value) }),
                    ),
                    h(Field, { label: 'Coupon code', error: form.errors.coupon_code },
                        h('input', { type: 'text', value: form.data.coupon_code, onChange: (e) => form.setData('coupon_code', e.target.value), placeholder: 'SAVE20' }),
                    ),
                ),
                h('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem' } },
                    h(Field, { label: 'CTA button text', error: form.errors.cta_label },
                        h('input', { type: 'text', value: form.data.cta_label, onChange: (e) => form.setData('cta_label', e.target.value) }),
                    ),
                    h(Field, { label: 'CTA URL', error: form.errors.cta_url },
                        h('input', { type: 'text', value: form.data.cta_url, onChange: (e) => form.setData('cta_url', e.target.value) }),
                    ),
                ),
                h('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '0.5rem' } },
                    h(Field, { label: 'Background', error: form.errors.bg_color },
                        h('input', { type: 'color', value: form.data.bg_color, onChange: (e) => form.setData('bg_color', e.target.value) }),
                    ),
                    h(Field, { label: 'Text', error: form.errors.text_color },
                        h('input', { type: 'color', value: form.data.text_color, onChange: (e) => form.setData('text_color', e.target.value) }),
                    ),
                    h(Field, { label: 'Accent', error: form.errors.accent_color },
                        h('input', { type: 'color', value: form.data.accent_color, onChange: (e) => form.setData('accent_color', e.target.value) }),
                    ),
                ),
                form.data.type === 'popup' && h('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem' } },
                    h(Field, { label: 'Trigger', error: form.errors.trigger_type },
                        h('select', { value: form.data.trigger_type, onChange: (e) => form.setData('trigger_type', e.target.value) },
                            h('option', { value: 'immediate' }, 'Immediate'),
                            h('option', { value: 'delay' }, 'Delay (seconds)'),
                            h('option', { value: 'scroll' }, 'Scroll (%)'),
                        ),
                    ),
                    form.data.trigger_type !== 'immediate' && h(Field, { label: form.data.trigger_type === 'delay' ? 'Seconds' : 'Scroll %', error: form.errors.trigger_value },
                        h('input', { type: 'number', min: 1, max: 3600, value: form.data.trigger_value, onChange: (e) => form.setData('trigger_value', Number(e.target.value || 0)) }),
                    ),
                ),
                h(Field, { label: 'Countdown ends at (optional)', error: form.errors.ends_at },
                    h('input', { type: 'datetime-local', value: form.data.ends_at, onChange: (e) => form.setData('ends_at', e.target.value) }),
                ),
                h(Check, {
                    label: 'Promotion active',
                    checked: form.data.is_active,
                    onChange: (val) => form.setData('is_active', val),
                }),
                h('div', { style: { display: 'flex', gap: '0.5rem', marginTop: '1rem' } },
                    h('button', { type: 'submit', className: 'btn btn-primary', disabled: form.processing },
                        editing ? 'Update promotion' : 'Create promotion',
                    ),
                    editing && h('button', { type: 'button', className: 'btn btn-ghost', onClick: resetForm }, 'Cancel'),
                ),
            ),
            h('div', { className: 'card' },
                campaigns.length === 0
                    ? h('p', { className: 'empty' }, 'No promotions created yet.')
                    : h('table', { className: 'table' },
                        h('thead', null, h('tr', null,
                            h('th', null, 'Campaign'),
                            h('th', null, 'Type'),
                            h('th', null, 'Status'),
                            h('th', { className: 'num' }, 'Views'),
                            h('th', { className: 'num' }, 'Clicks'),
                            h('th', { className: 'num' }, 'CTR'),
                            h('th'),
                        )),
                        h('tbody', null, campaigns.map((item) => h('tr', { key: item.id },
                            h('td', null,
                                h('div', { style: { fontWeight: 600 } }, item.name),
                                h('div', { className: 'hint', style: { fontSize: '0.8rem' } }, item.title),
                            ),
                            h('td', null, h(Badge, { tone: item.type === 'bar' ? 'ink' : 'accent' }, item.type === 'bar' ? 'Bar' : 'Popup')),
                            h('td', null, h('button', {
                                type: 'button',
                                className: 'btn btn-ghost btn-sm',
                                onClick: () => router.post(route('plugin.modrix.promo-bars.campaigns.toggle', item.id)),
                            }, item.is_active ? h(Badge, { tone: 'success' }, 'Active') : h(Badge, null, 'Paused'))),
                            h('td', { className: 'num' }, item.impressions),
                            h('td', { className: 'num' }, item.clicks),
                            h('td', { className: 'num' }, `${item.ctr}%`),
                            h('td', { className: 'actions' },
                                h('button', { type: 'button', className: 'btn btn-ghost btn-sm', onClick: () => editCampaign(item) }, 'Edit'),
                                h(DangerButton, {
                                    className: 'btn btn-ghost btn-sm',
                                    confirm: `Delete "${item.name}"?`,
                                    href: route('plugin.modrix.promo-bars.campaigns.destroy', item.id),
                                    method: 'delete',
                                }, 'Delete'),
                            ),
                        ))),
                    ),
            ),
        ),
    );
}
CampaignsIndex.layout = (page) => h(AdminLayout, null, page);

registerPlugin('modrix/promo-bars', {
    blocks: {
        'modrix/promo-bars:banner': BannerBlock,
    },
    pages: {
        CampaignsIndex,
    },
});
