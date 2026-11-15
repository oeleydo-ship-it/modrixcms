const { React, Inertia, ui, registerPlugin } = window.ModrixCMS;
const h = React.createElement;
const { useState } = React;
const { router, useForm, usePage } = Inertia;

function Stars({ rating }) {
    const filled = Math.max(1, Math.min(5, Number(rating) || 5));
    return h('span', { className: 'mtm-stars', 'aria-label': `${filled} out of 5 stars` },
        '★'.repeat(filled) + '☆'.repeat(5 - filled));
}

/** Public Block: Testimonials Showcase */
function ShowcaseBlock({ data, anchor }) {
    const items = data.items || [];
    const summary = data.summary || { average: 0, count: 0 };
    const isDark = data.layout === 'dark';

    return h('section', { className: `mtm-section${isDark ? ' is-dark' : ''}`, id: anchor },
        h('div', { className: 'wrap' },
            (data.eyebrow || data.title || data.subtitle) ? h('div', { className: 'section-head' },
                data.eyebrow ? h('p', { className: 'eyebrow' }, data.eyebrow) : null,
                data.title ? h('h2', null, data.title) : null,
                data.subtitle ? h('p', null, data.subtitle) : null) : null,
            data.show_rating_summary && summary.count > 0
                ? h('div', { className: 'mtm-summary' },
                    h(Stars, { rating: Math.round(summary.average) }),
                    h('span', null, `${summary.average} / 5 across ${summary.count} review${summary.count === 1 ? '' : 's'}`))
                : null,
            items.length === 0
                ? h('p', { className: 'empty-note' }, 'No approved testimonials yet.')
                : h('div', { className: 'mtm-grid' },
                    items.map((item) => h('article', { key: item.id, className: `mtm-card${item.is_featured ? ' is-featured' : ''}` },
                        h(Stars, { rating: item.rating }),
                        h('p', { className: 'mtm-quote' }, `“${item.quote}”`),
                        h('div', { className: 'mtm-author' },
                            h('div', null,
                                h('b', null, item.author_name),
                                (item.author_role || item.company)
                                    ? h('span', null, [item.author_role, item.company].filter(Boolean).join(' · '))
                                    : null),
                            item.verified ? h('span', { className: 'mtm-verified' }, 'Verified') : null))))));
}

/** Public Block: Review Submission Form */
function SubmitBlock({ data, anchor }) {
    const { testimonials } = usePage().props;
    const form = useForm({
        author_name: '',
        author_email: '',
        author_role: '',
        company: '',
        rating: 5,
        quote: '',
        _tm_trap: '',
        _anchor: anchor || 'leave-review',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(testimonials.submitUrl, {
            preserveScroll: true,
            errorBag: 'testimonials',
            onSuccess: () => form.reset(),
        });
    };

    return h('section', { className: 'mtm-section', id: anchor },
        h('div', { className: 'wrap' },
            h('div', { className: 'mtm-form-card' },
                data.title ? h('h2', { style: { fontSize: '1.8rem', marginBottom: 6 } }, data.title) : null,
                data.subtitle ? h('p', { style: { color: 'var(--muted)' } }, data.subtitle) : null,
                testimonials?.submitted
                    ? h('div', { className: 'mtm-sent', role: 'status' }, testimonials.successMessage)
                    : h('form', { className: 'mtm-form', onSubmit: submit },
                        h('div', null,
                            h('label', { style: { marginBottom: 6 } }, 'Your rating'),
                            h('div', { className: 'mtm-star-picker' },
                                [1, 2, 3, 4, 5].map((star) => h('button', {
                                    key: star,
                                    type: 'button',
                                    className: `mtm-star-btn${form.data.rating >= star ? ' on' : ''}`,
                                    onClick: () => form.setData('rating', star),
                                }, '★')))),
                        h('div', { className: 'mtm-row' },
                            h('label', null, 'Your name',
                                h('input', { type: 'text', required: true, value: form.data.author_name, onChange: (e) => form.setData('author_name', e.target.value) }),
                                form.errors.author_name ? h('span', { className: 'mtm-error' }, form.errors.author_name) : null),
                            h('label', null, 'Email (not published)',
                                h('input', { type: 'email', value: form.data.author_email, onChange: (e) => form.setData('author_email', e.target.value) }))),
                        data.show_company ? h('div', { className: 'mtm-row' },
                            h('label', null, 'Role (optional)',
                                h('input', { type: 'text', placeholder: 'Founder', value: form.data.author_role, onChange: (e) => form.setData('author_role', e.target.value) })),
                            h('label', null, 'Company (optional)',
                                h('input', { type: 'text', placeholder: 'Acme Co.', value: form.data.company, onChange: (e) => form.setData('company', e.target.value) }))) : null,
                        h('label', null, 'Your review',
                            h('textarea', { rows: 4, required: true, minLength: 10, value: form.data.quote, onChange: (e) => form.setData('quote', e.target.value) }),
                            form.errors.quote ? h('span', { className: 'mtm-error' }, form.errors.quote) : null),
                        h('label', { className: 'mtm-trap', 'aria-hidden': 'true' }, 'Leave blank ',
                            h('input', { tabIndex: -1, autoComplete: 'off', value: form.data._tm_trap, onChange: (e) => form.setData('_tm_trap', e.target.value) })),
                        h('button', { className: 'btn', type: 'submit', disabled: form.processing, style: { justifySelf: 'start' } },
                            form.processing ? 'Submitting…' : (data.button_label || 'Submit review'))))));
}

/** Admin: Testimonials Manager & Moderation Queue */
function ItemsIndex({ items, stats, filters }) {
    const [editingId, setEditingId] = useState(null);
    const form = useForm({
        author_name: '',
        author_role: '',
        company: '',
        author_email: '',
        rating: 5,
        quote: '',
        status: 'approved',
        is_featured: false,
        verified: true,
        sort_order: 0,
    });

    const editItem = (item) => {
        setEditingId(item.id);
        form.setData({
            author_name: item.author_name,
            author_role: item.author_role || '',
            company: item.company || '',
            author_email: item.author_email || '',
            rating: item.rating,
            quote: item.quote,
            status: item.status,
            is_featured: Boolean(item.is_featured),
            verified: Boolean(item.verified),
            sort_order: item.sort_order,
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
            ? form.put(route('plugin.modrix.testimonials.items.update', editingId), opts)
            : form.post(route('plugin.modrix.testimonials.items.store'), opts);
    };

    const quickUpdate = (item, patch) => {
        router.put(route('plugin.modrix.testimonials.items.update', item.id), { ...item, ...patch }, { preserveScroll: true });
    };

    const toneFor = (st) => ({ approved: 'green', pending: 'amber', rejected: 'gray' }[st] || 'gray');

    return h(ui.AdminLayout, {
        title: 'Testimonials & reviews',
        subtitle: 'Moderate customer submissions, verify reviewers, and highlight featured quotes.',
    },
    h('div', { className: 'stats' },
        h('div', { className: 'stat' }, h('small', null, 'Total reviews'), h('b', null, stats.all)),
        h('div', { className: 'stat' }, h('small', null, 'Approved'), h('b', null, stats.approved)),
        h('div', { className: 'stat' }, h('small', null, 'Pending moderation'), h('b', null, stats.pending)),
        h('div', { className: 'stat' }, h('small', null, 'Average rating'), h('b', null, `${stats.average_rating} ★`))),
    h('div', { className: 'grid-2' },
        h('div', { className: 'panel' },
            h('div', { className: 'tabs' },
                ['all', 'approved', 'pending', 'rejected'].map((st) => h('a', {
                    key: st,
                    href: `#${st}`,
                    className: filters.status === st ? 'on' : undefined,
                    onClick: (e) => {
                        e.preventDefault();
                        router.get(route('plugin.modrix.testimonials.items.index'), st === 'all' ? {} : { status: st }, { preserveState: true });
                    },
                }, `${st[0].toUpperCase() + st.slice(1)} (${stats[st] ?? 0})`))),
            h('div', { className: 'table-wrap' },
                h('table', null,
                    h('thead', null, h('tr', null, h('th', null, 'Reviewer'), h('th', null, 'Review'), h('th', null, 'Status'), h('th'))),
                    h('tbody', null, items.data.length
                        ? items.data.map((item) => h('tr', { key: item.id },
                            h('td', { className: 'title' },
                                item.author_name,
                                h('div', { className: 'sub' }, [item.author_role, item.company].filter(Boolean).join(' · ') || 'Customer'),
                                h('div', { className: 'sub' }, '★'.repeat(item.rating))),
                            h('td', { style: { maxWidth: 340 } },
                                item.quote,
                                h('div', { className: 'actions', style: { marginTop: 6 } },
                                    item.is_featured ? h(ui.Badge, { tone: 'purple' }, 'Featured') : null,
                                    item.verified ? h(ui.Badge, { tone: 'green' }, 'Verified') : null)),
                            h('td', null, h(ui.Badge, { tone: toneFor(item.status) }, item.status)),
                            h('td', null,
                                h('div', { className: 'actions' },
                                    item.status !== 'approved'
                                        ? h('button', { className: 'btn btn-light btn-sm', type: 'button', onClick: () => quickUpdate(item, { status: 'approved' }) }, 'Approve')
                                        : null,
                                    h('button', { className: 'btn btn-light btn-sm', type: 'button', onClick: () => editItem(item) }, 'Edit'),
                                    h(ui.DangerButton, { confirm: `Delete review by ${item.author_name}?`, url: route('plugin.modrix.testimonials.items.destroy', item.id) }, 'Delete')))))
                        : h('tr', null, h('td', { colSpan: 4, className: 'empty' }, 'No testimonials yet.'))))),
            h(ui.Pager, { paginator: items })),
        h('div', { className: 'panel' },
            h('h2', null, editingId ? 'Edit testimonial' : 'Add testimonial'),
            h('form', { onSubmit: submit, style: { marginTop: 14 } },
                h(ui.Field, { form, name: 'author_name', label: 'Author name', required: true }),
                h('div', { className: 'row' },
                    h(ui.Field, { form, name: 'author_role', label: 'Role' }),
                    h(ui.Field, { form, name: 'company', label: 'Company' })),
                h('div', { className: 'row' },
                    h(ui.Field, { form, name: 'rating', label: 'Rating (1–5)', type: 'number', min: 1, max: 5, required: true }),
                    h(ui.Field, { form, name: 'status', label: 'Status', type: 'select', options: { approved: 'Approved', pending: 'Pending', rejected: 'Rejected' } })),
                h(ui.Field, { form, name: 'quote', label: 'Quote', type: 'textarea', rows: 4, required: true }),
                h(ui.Check, { form, name: 'verified', label: 'Verified customer badge' }),
                h(ui.Check, { form, name: 'is_featured', label: 'Featured' }),
                h('div', { className: 'actions', style: { marginTop: 14 } },
                    h('button', { className: 'btn', type: 'submit', disabled: form.processing }, editingId ? 'Save changes' : 'Add testimonial'),
                    editingId ? h('button', { className: 'btn btn-light', type: 'button', onClick: resetForm }, 'Cancel') : null)))));
}

registerPlugin('modrix/testimonials', {
    blocks: {
        'modrix/testimonials:showcase': ShowcaseBlock,
        'modrix/testimonials:submit': SubmitBlock,
    },
    pages: { ItemsIndex },
});
