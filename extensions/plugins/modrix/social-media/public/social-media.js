const { React, Inertia, ui, registerPlugin } = window.ModrixCMS;
const h = React.createElement;
const { useState } = React;
const { Link, router, useForm } = Inertia;

const PLATFORM_META = {
    x: { name: 'X', color: '#0f172a', path: 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z' },
    facebook: { name: 'Facebook', color: '#1877f2', path: 'M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.5 1.6-1.5h1.7V4.9c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3V11H7.5v3h2.6v8z' },
    instagram: { name: 'Instagram', color: '#e1306c', path: 'M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9a5.5 5.5 0 0 1-5.5 5.5h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2zm0 2A3.5 3.5 0 0 0 4 7.5v9A3.5 3.5 0 0 0 7.5 20h9a3.5 3.5 0 0 0 3.5-3.5v-9A3.5 3.5 0 0 0 16.5 4zm4.5 3.5a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9zm0 2a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zm5.25-3a1 1 0 1 1 0 2 1 1 0 0 1 0-2z' },
    linkedin: { name: 'LinkedIn', color: '#0a66c2', path: 'M6.94 8.5H3.56V19.5h3.38V8.5zM5.25 3.5a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM20.44 13.02c0-3.03-1.62-4.44-3.78-4.44-1.74 0-2.52.96-2.96 1.63V8.5h-3.37c.04.95 0 11 0 11h3.37v-6.14c0-.33.02-.66.12-.89.26-.66.86-1.34 1.86-1.34 1.31 0 1.84 1 1.84 2.47v5.9h3.37v-6.48z' },
    youtube: { name: 'YouTube', color: '#ff0000', path: 'M21.6 7.2a2.8 2.8 0 0 0-2-2C17.8 4.7 12 4.7 12 4.7s-5.8 0-7.6.5a2.8 2.8 0 0 0-2 2C2 9 2 12 2 12s0 3 .4 4.8a2.8 2.8 0 0 0 2 2c1.8.5 7.6.5 7.6.5s5.8 0 7.6-.5a2.8 2.8 0 0 0 2-2C22 15 22 12 22 12s0-3-.4-4.8zM10 15.2V8.8L15.5 12z' },
    tiktok: { name: 'TikTok', color: '#000000', path: 'M14 3v10.2a3.8 3.8 0 1 1-3-3.7V7a6.5 6.5 0 1 0 5.5 6.4V8.5a7.2 7.2 0 0 0 4 1.2V7a4.6 4.6 0 0 1-4-4z' },
    github: { name: 'GitHub', color: '#181717', path: 'M12 2a10 10 0 0 0-3.16 19.49c.5.09.68-.22.68-.48v-1.7C6.73 19.91 6.14 18 6.14 18c-.45-1.16-1.11-1.47-1.11-1.47-.91-.62.07-.61.07-.61 1 .07 1.53 1.03 1.53 1.03.89 1.53 2.34 1.09 2.91.83.09-.65.35-1.09.63-1.34-2.22-.25-4.56-1.11-4.56-4.95 0-1.09.39-1.99 1.03-2.69-.1-.25-.45-1.27.1-2.65 0 0 .84-.27 2.75 1.03a9.6 9.6 0 0 1 5 0c1.91-1.3 2.75-1.03 2.75-1.03.55 1.38.2 2.4.1 2.65.64.7 1.03 1.6 1.03 2.69 0 3.85-2.34 4.7-4.57 4.95.36.31.68.92.68 1.85v2.75c0 .27.18.58.69.48A10 10 0 0 0 12 2z' },
    discord: { name: 'Discord', color: '#5865f2', path: 'M19.3 5.4A16 16 0 0 0 15.3 4l-.5 1a14.5 14.5 0 0 0-5.6 0L8.7 4a16 16 0 0 0-4 1.4C2.2 9.1 1.5 12.7 1.8 16.3a16.2 16.2 0 0 0 4.9 2.5l1-1.6a10 10 0 0 1-1.6-.8l.4-.3a11.3 11.3 0 0 0 11 0l.4.3a10 10 0 0 1-1.6.8l1 1.6a16.2 16.2 0 0 0 4.9-2.5c.4-4.2-.6-7.8-2.9-10.9zM9.2 14.1c-.9 0-1.7-.9-1.7-1.9s.7-1.9 1.7-1.9c1 0 1.7.8 1.7 1.9 0 1-.8 1.9-1.7 1.9zm5.6 0c-.9 0-1.7-.9-1.7-1.9s.8-1.9 1.7-1.9c1 0 1.7.8 1.7 1.9 0 1-.7 1.9-1.7 1.9z' },
    whatsapp: { name: 'WhatsApp', color: '#25d366', path: 'M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.2 14.1c-.2.6-1.3 1.2-1.8 1.3-.5.1-1 .1-1.7-.1a14.4 14.4 0 0 1-6.1-5.4c-.7-1-.9-1.9-.9-2.5s.4-1.3.7-1.6c.3-.3.6-.4.8-.4h.6c.2 0 .5 0 .7.5.3.6.9 2.1 1 2.3.1.2.1.4 0 .6-.1.2-.2.4-.4.6l-.4.5c-.2.2-.3.4-.1.7.2.3.9 1.5 2 2.4 1.3 1.1 2.4 1.5 2.7 1.6.3.2.5.1.7-.1.2-.2.8-.9 1-1.2.2-.3.5-.3.8-.2.3.1 2 .9 2.3 1.1.3.2.5.3.6.4.1.2.1.8-.1 1.4z' },
    telegram: { name: 'Telegram', color: '#229ed9', path: 'M21.5 4.2 2.9 11.4c-1.3.5-1.3 1.3-.2 1.6l4.7 1.5 11-6.9c.5-.3 1-.1.6.2l-8.9 8-.3 4.9c.5 0 .7-.2 1-.5l2.4-2.3 4.9 3.6c.9.5 1.5.2 1.8-.8l3.2-15c.3-1.3-.5-1.9-1.6-1.5z' },
    reddit: { name: 'Reddit', color: '#ff4500', path: 'M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm5.6 11.3a2 2 0 0 1-.8 1.6c-.8 1.9-2.8 3.2-5.1 3.2s-4.3-1.3-5.1-3.2a2 2 0 0 1-.8-1.6 1.8 1.8 0 0 1 3-1.3 6.8 6.8 0 0 1 3.5-1l.7-3.2 2.2.5a1.3 1.3 0 1 1-.2.9l-1.5-.3-.5 2.4a6.8 6.8 0 0 1 3.4 1 1.8 1.8 0 0 1 1.2 1.9z' },
    pinterest: { name: 'Pinterest', color: '#e60023', path: 'M12 2a10 10 0 0 0-3.6 19.3c-.1-.8-.1-2 0-2.9l1.1-4.7s-.3-.6-.3-1.4c0-1.3.8-2.3 1.7-2.3.8 0 1.2.6 1.2 1.3 0 .8-.5 2-.8 3.1-.2.9.5 1.7 1.4 1.7 1.7 0 3-1.8 3-4.3 0-2.3-1.6-3.9-4-3.9-2.7 0-4.3 2-4.3 4.1 0 .8.3 1.7.7 2.2.1.1.1.2.1.3l-.3 1.1c0 .2-.2.2-.3.1-1.3-.6-2.1-2.5-2.1-4 0-3.2 2.3-6.2 6.8-6.2 3.6 0 6.3 2.5 6.3 5.9 0 3.5-2.2 6.4-5.3 6.4-1 0-2-.5-2.3-1.2l-.6 2.4c-.2.9-.8 2-1.2 2.7A10 10 0 1 0 12 2z' },
    threads: { name: 'Threads', color: '#111111', path: 'M12.2 2C7 2 3.5 5.4 3.5 11s3.5 9 8.7 9c4.2 0 7.1-2 8.1-5.2l-2.3-.7c-.7 2.2-2.7 3.5-5.7 3.5-3.6 0-6-2.5-6-6.6s2.4-6.6 6-6.6c3.7 0 6 2.3 6 5.9 0 2.1-.9 3.6-2.2 3.6-.8 0-1.3-.5-1.3-1.4V7.8h-2.3v1.1a3.2 3.2 0 0 0-2.5-1.2c-2 0-3.5 1.6-3.5 3.9s1.5 3.9 3.5 3.9c1.2 0 2.2-.6 2.7-1.5.5.9 1.4 1.4 2.6 1.4 2.5 0 4.3-2.2 4.3-5.6C20.5 5.2 17.2 2 12.2 2z' },
    email: { name: 'Email', color: '#475569', path: 'M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zm0 2 8 5 8-5' },
    website: { name: 'Website', color: '#5b3df5', path: 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm7.9 9h-3.1a15 15 0 0 0-1.4-5.2A8 8 0 0 1 19.9 11zM12 4.1c1 1.4 1.8 3.6 2 6.9h-4c.2-3.3 1-5.5 2-6.9zM4.1 13h3.1a15 15 0 0 0 1.4 5.2A8 8 0 0 1 4.1 13zm3.1-2H4.1a8 8 0 0 1 4.5-5.2A15 15 0 0 0 7.2 11zM12 19.9c-1-1.4-1.8-3.6-2-6.9h4c-.2 3.3-1 5.5-2 6.9zm3.4-1.7A15 15 0 0 0 16.8 13h3.1a8 8 0 0 1-4.5 5.2z' },
    copy: { name: 'Copy link', color: '#5b3df5', path: 'M8 8V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-3M5 8h9a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2z' },
};

function BrandIcon({ platform }) {
    const meta = PLATFORM_META[platform] || PLATFORM_META.website;
    return h('svg', { viewBox: '0 0 24 24', 'aria-hidden': 'true', style: { width: 18, height: 18, fill: 'currentColor' } },
        h('path', { d: meta.path }));
}

function shareUrlFor(platform, pageUrl, title) {
    const u = encodeURIComponent(pageUrl);
    const t = encodeURIComponent(title);
    const map = {
        x: `https://twitter.com/intent/tweet?url=${u}&text=${t}`,
        facebook: `https://www.facebook.com/sharer/sharer.php?u=${u}`,
        linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${u}`,
        whatsapp: `https://wa.me/?text=${t}%20${u}`,
        telegram: `https://t.me/share/url?url=${u}&text=${t}`,
        reddit: `https://www.reddit.com/submit?url=${u}&title=${t}`,
        email: `mailto:?subject=${t}&body=${u}`,
    };
    return map[platform] || null;
}

function sendShareBeacon(trackUrl, platform) {
    if (!trackUrl) return;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    fetch(trackUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...(token ? { 'X-CSRF-TOKEN': token } : {}) },
        body: JSON.stringify({
            event_type: 'share',
            platform,
            page_url: window.location.href,
            page_title: document.title,
        }),
        keepalive: true,
    }).catch(() => {});
}

function triggerShare(platform, trackUrl, onCopied) {
    const url = window.location.href;
    const title = document.title;
    sendShareBeacon(trackUrl, platform);

    if (platform === 'copy') {
        if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(url).then(() => onCopied?.()).catch(() => {});
        }
        return;
    }

    const target = shareUrlFor(platform, url, title);
    if (!target) return;

    if (platform === 'email') {
        window.location.href = target;
    } else {
        window.open(target, '_blank', 'noopener,noreferrer,width=640,height=520');
    }
}

// Attach click handler for the site-wide floating share bar rendered in @hook('body.end').
if (typeof document !== 'undefined') {
    document.addEventListener('click', (event) => {
        const btn = event.target?.closest?.('[data-msm-share]');
        if (!btn) return;
        const bar = btn.closest('#msm-float-bar');
        const platform = btn.getAttribute('data-msm-share');
        triggerShare(platform, bar?.getAttribute('data-track-url'), () => {
            const orig = btn.textContent;
            btn.textContent = '✓';
            setTimeout(() => { btn.textContent = orig; }, 1400);
        });
    });
}

/** Block: Social Profiles */
function LinksBlock({ data, anchor }) {
    const profiles = data.profiles || [];
    const style = data.style || 'cards';

    return h('section', { className: 'msm-section', id: anchor },
        h('div', { className: 'wrap' },
            (data.eyebrow || data.title || data.subtitle) ? h('div', { className: 'section-head' },
                data.eyebrow ? h('p', { className: 'eyebrow' }, data.eyebrow) : null,
                data.title ? h('h2', null, data.title) : null,
                data.subtitle ? h('p', null, data.subtitle) : null) : null,
            profiles.length === 0
                ? h('p', { className: 'empty-note' }, 'No social profiles added yet. Add them in Admin → Social Links.')
                : style === 'cards'
                    ? h('div', { className: 'msm-cards' },
                        profiles.map((p) => h('a', {
                            key: p.id,
                            className: 'msm-card',
                            href: p.url,
                            target: p.open_new_tab ? '_blank' : undefined,
                            rel: p.open_new_tab ? 'noopener noreferrer' : undefined,
                        },
                        h('span', { className: 'msm-icon' }, h(BrandIcon, { platform: p.platform })),
                        h('div', { className: 'msm-card-meta' },
                            h('b', null, p.label),
                            p.handle ? h('span', null, p.handle) : null),
                        data.show_followers && p.followers ? h('span', { className: 'msm-followers' }, p.followers) : null)))
                    : h('div', { className: 'msm-pills' },
                        profiles.map((p) => h('a', {
                            key: p.id,
                            className: 'msm-pill',
                            href: p.url,
                            target: p.open_new_tab ? '_blank' : undefined,
                            rel: p.open_new_tab ? 'noopener noreferrer' : undefined,
                        },
                        h(BrandIcon, { platform: p.platform }),
                        style === 'minimal' ? null : p.label)))));
}

/** Block: Social Share Bar */
function ShareBlock({ data, anchor }) {
    const { socialMedia } = Inertia.usePage().props;
    const [copied, setCopied] = useState(false);
    const platforms = socialMedia?.sharePlatforms || ['x', 'facebook', 'linkedin', 'copy'];
    const boxClass = ['msm-share-box', data.style === 'dark' && 'is-dark', data.style === 'compact' && 'is-compact'].filter(Boolean).join(' ');

    return h('section', { className: 'msm-section', id: anchor },
        h('div', { className: 'wrap' },
            h('div', { className: boxClass },
                h('div', null,
                    data.title ? h('h3', { style: { fontSize: '1.35rem', marginBottom: 4 } }, data.title) : null,
                    data.subtitle ? h('p', { style: { color: data.style === 'dark' ? '#b5bccd' : 'var(--muted)' } }, data.subtitle) : null),
                h('div', { className: 'msm-share-btns' },
                    platforms.map((platform) => {
                        const label = platform === 'copy' && copied ? 'Copied!' : (PLATFORM_META[platform]?.name || platform);
                        return h('button', {
                            key: platform,
                            type: 'button',
                            className: 'msm-share-btn',
                            onClick: () => triggerShare(platform, socialMedia?.trackUrl, () => {
                                setCopied(true);
                                setTimeout(() => setCopied(false), 1800);
                            }),
                        }, h(BrandIcon, { platform }), label);
                    })))));
}

/** Block: Curated Social Feed */
function FeedBlock({ data, anchor }) {
    const items = data.items || [];

    return h('section', { className: 'msm-section', id: anchor },
        h('div', { className: 'wrap' },
            (data.eyebrow || data.title || data.subtitle) ? h('div', { className: 'section-head' },
                data.eyebrow ? h('p', { className: 'eyebrow' }, data.eyebrow) : null,
                data.title ? h('h2', null, data.title) : null,
                data.subtitle ? h('p', null, data.subtitle) : null) : null,
            h('div', { className: 'msm-feed-grid' },
                items.map((item, i) => h('article', { key: i, className: 'msm-post' },
                    h('div', { className: 'msm-post-head' },
                        h('div', { className: 'msm-post-author' },
                            h('span', { className: 'msm-icon' }, h(BrandIcon, { platform: item.platform })),
                            h('div', null,
                                h('b', null, item.author || 'Author'),
                                item.handle ? h('span', null, item.handle) : null)),
                        item.date ? h('span', { style: { fontSize: '.8rem', color: 'var(--muted)' } }, item.date) : null),
                    item.content ? h('p', { className: 'msm-post-body' }, item.content) : null,
                    item.image_url ? h('img', { className: 'msm-post-img', src: item.image_url, alt: '', loading: 'lazy' }) : null,
                    h('div', { className: 'msm-post-foot' },
                        h('span', null, item.likes ? `♥ ${item.likes}` : ''),
                        item.post_url && item.post_url !== '#'
                            ? h('a', { href: item.post_url, target: '_blank', rel: 'noopener noreferrer', style: { fontWeight: 600, color: 'var(--brand)' } }, 'View post →')
                            : null))))));
}

/** Admin: Social Profiles Manager */
function ProfilesIndex({ profiles, platforms }) {
    const [editingId, setEditingId] = useState(null);
    const form = useForm({
        platform: 'x',
        label: '',
        handle: '',
        url: '',
        followers: '',
        sort_order: 0,
        is_active: true,
        open_new_tab: true,
    });

    const startEdit = (p) => {
        setEditingId(p.id);
        form.setData({
            platform: p.platform,
            label: p.label,
            handle: p.handle || '',
            url: p.url,
            followers: p.followers || '',
            sort_order: p.sort_order,
            is_active: Boolean(p.is_active),
            open_new_tab: Boolean(p.open_new_tab),
        });
    };

    const resetForm = () => {
        setEditingId(null);
        form.reset();
    };

    const submit = (e) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => resetForm() };
        editingId
            ? form.put(route('plugin.modrix.social-media.profiles.update', editingId), opts)
            : form.post(route('plugin.modrix.social-media.profiles.store'), opts);
    };

    return h(ui.AdminLayout, {
        title: 'Social links & profiles',
        subtitle: 'Add your social accounts once and display them with the “Social Profiles” block.',
        actions: h(Link, { className: 'btn btn-light', href: route('plugin.modrix.social-media.analytics.index') }, 'Social analytics'),
    },
    h('div', { className: 'grid-2' },
        h('div', { className: 'panel' },
            h('div', { className: 'table-wrap' },
                h('table', null,
                    h('thead', null, h('tr', null, h('th', null, 'Profile'), h('th', null, 'Followers'), h('th', null, 'Clicks'), h('th', null, 'Status'), h('th'))),
                    h('tbody', null, profiles.length
                        ? profiles.map((p) => h('tr', { key: p.id },
                            h('td', { className: 'title' },
                                h('span', { style: { display: 'inline-flex', alignItems: 'center', gap: 8 } },
                                    h(BrandIcon, { platform: p.platform }),
                                    p.label),
                                h('div', { className: 'sub' }, `${p.handle ? `${p.handle} · ` : ''}${p.url}`)),
                            h('td', null, p.followers || '—'),
                            h('td', null, p.clicks_count),
                            h('td', null, h(ui.Badge, { tone: p.is_active ? 'green' : 'gray' }, p.is_active ? 'Active' : 'Hidden')),
                            h('td', null,
                                h('div', { className: 'actions' },
                                    h('button', { className: 'btn btn-light btn-sm', type: 'button', onClick: () => startEdit(p) }, 'Edit'),
                                    h(ui.DangerButton, { confirm: `Remove ${p.label}?`, url: route('plugin.modrix.social-media.profiles.destroy', p.id) }, 'Delete')))))
                        : h('tr', null, h('td', { colSpan: 5, className: 'empty' }, 'No social profiles yet. Add one on the right.')))))),
        h('div', { className: 'panel' },
            h('h2', null, editingId ? 'Edit profile' : 'Add social profile'),
            h('form', { onSubmit: submit, style: { marginTop: 14 } },
                h(ui.Field, { form, name: 'platform', label: 'Platform', type: 'select', options: platforms }),
                h(ui.Field, { form, name: 'label', label: 'Display label', required: true, placeholder: 'Follow us on X' }),
                h(ui.Field, { form, name: 'handle', label: 'Handle (optional)', placeholder: '@modrix' }),
                h(ui.Field, { form, name: 'url', label: 'Profile URL', type: 'url', required: true, placeholder: 'https://x.com/modrix' }),
                h(ui.Field, { form, name: 'followers', label: 'Follower badge (optional)', placeholder: '12.4k' }),
                editingId ? h(ui.Field, { form, name: 'sort_order', label: 'Sort order', type: 'number', min: 0 }) : null,
                h(ui.Check, { form, name: 'is_active', label: 'Active' }),
                h(ui.Check, { form, name: 'open_new_tab', label: 'Open in new tab' }),
                h('div', { className: 'actions', style: { marginTop: 14 } },
                    h('button', { className: 'btn', type: 'submit', disabled: form.processing }, editingId ? 'Save profile' : 'Add profile'),
                    editingId ? h('button', { className: 'btn btn-light', type: 'button', onClick: resetForm }, 'Cancel') : null)))));
}

/** Admin: Social Analytics */
function AnalyticsIndex({ summary, byPlatform, topPages, recent }) {
    return h(ui.AdminLayout, {
        title: 'Social analytics',
        subtitle: 'Shares and profile clicks recorded over the last 30 days.',
        actions: h(Link, { className: 'btn btn-light', href: route('plugin.modrix.social-media.profiles.index') }, '← Social links'),
    },
    h('div', { className: 'stats' },
        h('div', { className: 'stat' }, h('small', null, 'Shares (30d)'), h('b', null, summary.shares_30d)),
        h('div', { className: 'stat' }, h('small', null, 'Profile clicks (30d)'), h('b', null, summary.clicks_30d)),
        h('div', { className: 'stat' }, h('small', null, 'Active profiles'), h('b', null, summary.active_profiles)),
        h('div', { className: 'stat' }, h('small', null, 'All-time link clicks'), h('b', null, summary.total_profile_clicks))),
    h('div', { className: 'grid-2' },
        h('div', { className: 'panel' },
            h('h2', null, 'Top shared pages (30d)'),
            h('div', { className: 'table-wrap', style: { marginTop: 12 } },
                h('table', null,
                    h('thead', null, h('tr', null, h('th', null, 'Page'), h('th', null, 'Shares'))),
                    h('tbody', null, topPages.length
                        ? topPages.map((row, idx) => h('tr', { key: idx },
                            h('td', null, h('strong', null, row.page_title || 'Page'), h('div', { className: 'sub' }, row.page_url)),
                            h('td', null, row.total)))
                        : h('tr', null, h('td', { colSpan: 2, className: 'empty' }, 'No share events recorded yet.')))))),
        h('div', { className: 'panel' },
            h('h2', null, 'By platform (30d)'),
            h('div', { className: 'table-wrap', style: { marginTop: 12 } },
                h('table', null,
                    h('thead', null, h('tr', null, h('th', null, 'Platform'), h('th', null, 'Type'), h('th', null, 'Count'))),
                    h('tbody', null, byPlatform.length
                        ? byPlatform.map((row, idx) => h('tr', { key: idx },
                            h('td', null, PLATFORM_META[row.platform]?.name || row.platform),
                            h('td', null, h(ui.Badge, { tone: row.event_type === 'share' ? 'purple' : 'green' }, row.event_type)),
                            h('td', null, row.total)))
                        : h('tr', null, h('td', { colSpan: 3, className: 'empty' }, 'No events yet.'))))))),
    h('div', { className: 'panel' },
        h('h2', null, 'Recent activity'),
        h('div', { className: 'table-wrap', style: { marginTop: 12 } },
            h('table', null,
                h('thead', null, h('tr', null, h('th', null, 'Event'), h('th', null, 'Platform'), h('th', null, 'URL'), h('th', null, 'Time'))),
                h('tbody', null, recent.length
                    ? recent.map((ev) => h('tr', { key: ev.id },
                        h('td', null, h(ui.Badge, { tone: ev.event_type === 'share' ? 'purple' : 'green' }, ev.event_type)),
                        h('td', null, PLATFORM_META[ev.platform]?.name || ev.platform),
                        h('td', { className: 'sub' }, ev.page_url),
                        h('td', { className: 'sub' }, ev.created_at)))
                    : h('tr', null, h('td', { colSpan: 4, className: 'empty' }, 'No recent social events.')))))));
}

registerPlugin('modrix/social-media', {
    blocks: {
        'modrix/social-media:links': LinksBlock,
        'modrix/social-media:share': ShareBlock,
        'modrix/social-media:feed': FeedBlock,
    },
    pages: {
        ProfilesIndex,
        AnalyticsIndex,
    },
});
