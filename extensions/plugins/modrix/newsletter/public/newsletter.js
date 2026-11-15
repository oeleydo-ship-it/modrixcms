const { React, Inertia, ui, registerPlugin } = window.ModrixCMS;
const h = React.createElement;
const { useState } = React;
const { Link, router, useForm, usePage } = Inertia;

/** Public Block: Newsletter Signup */
function SignupBlock({ data, anchor }) {
    const { newsletter } = usePage().props;
    const form = useForm({
        name: '',
        email: '',
        tag: data.tag || '',
        require_consent: Boolean(data.require_consent),
        consent: false,
        _nl_trap: '',
        _anchor: anchor || 'newsletter',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(newsletter.subscribeUrl, {
            preserveScroll: true,
            errorBag: 'newsletter',
            onSuccess: () => form.reset('name', 'email', 'consent'),
        });
    };

    const style = data.style || 'card';
    const cardClass = ['mnl-card', style === 'dark' && 'is-dark', style === 'banner' && 'is-banner'].filter(Boolean).join(' ');

    return h('section', { className: 'mnl-section', id: anchor },
        h('div', { className: 'wrap' },
            h('div', { className: cardClass },
                h('div', null,
                    data.eyebrow ? h('p', { className: 'eyebrow' }, data.eyebrow) : null,
                    data.show_count && data.subscriber_count > 0
                        ? h('div', { className: 'mnl-count' }, `Join ${data.subscriber_count} subscriber${data.subscriber_count === 1 ? '' : 's'}`)
                        : null,
                    data.title ? h('h2', null, data.title) : null,
                    data.description ? h('p', { className: 'mnl-desc' }, data.description) : null),
                newsletter?.status
                    ? h('div', { className: 'mnl-status', role: 'status' }, newsletter.message)
                    : h('form', { className: 'mnl-form', onSubmit: submit },
                        h('div', { className: 'mnl-row' },
                            data.show_name ? h('input', {
                                className: 'mnl-input',
                                type: 'text',
                                name: 'name',
                                placeholder: 'Your name',
                                value: form.data.name,
                                onChange: (e) => form.setData('name', e.target.value),
                            }) : null,
                            h('input', {
                                className: 'mnl-input',
                                type: 'email',
                                name: 'email',
                                required: true,
                                placeholder: 'you@example.com',
                                value: form.data.email,
                                onChange: (e) => form.setData('email', e.target.value),
                            }),
                            h('button', { className: `btn${style === 'dark' ? ' btn-light' : ''}`, type: 'submit', disabled: form.processing },
                                form.processing ? 'Joining…' : (data.button_label || 'Subscribe'))),
                        form.errors.email ? h('div', { className: 'mnl-error' }, form.errors.email) : null,
                        data.require_consent ? h('label', { className: 'mnl-consent' },
                            h('input', { type: 'checkbox', checked: form.data.consent, required: true, onChange: (e) => form.setData('consent', e.target.checked) }),
                            data.consent_label || 'I agree to receive email updates.') : null,
                        form.errors.consent ? h('div', { className: 'mnl-error' }, form.errors.consent) : null,
                        h('label', { className: 'mnl-trap', 'aria-hidden': 'true' }, 'Leave blank ',
                            h('input', { tabIndex: -1, autoComplete: 'off', value: form.data._nl_trap, onChange: (e) => form.setData('_nl_trap', e.target.value) }))))));
}

/** Public Page: Double opt-in confirm & Unsubscribe */
function StatusPage({ heading, message }) {
    const { links } = usePage().props;
    return h('section', { className: 'mnl-status-page' },
        h('div', { className: 'wrap' },
            h('div', { className: 'mnl-status-box' },
                h('h1', null, heading),
                h('p', null, message),
                h(Link, { className: 'btn', href: links.home }, 'Return home'))));
}
StatusPage.layout = (children) => h(ui.SiteLayout, null, children);

/** Admin: Subscribers */
function SubscribersIndex({ subscribers, counts, filters }) {
    const { errors } = usePage().props;
    const [q, setQ] = useState(filters?.q || '');
    const addForm = useForm({ email: '', name: '', tags: '', status: 'subscribed' });
    const importForm = useForm({ bulk: '', tag: '' });

    const applyFilter = (next = {}) => {
        const params = {
            status: next.status !== undefined ? next.status : filters.status,
            q: next.q !== undefined ? next.q : q,
        };
        Object.keys(params).forEach((k) => { if (!params[k] || params[k] === 'all') delete params[k]; });
        router.get(route('plugin.modrix.newsletter.subscribers.index'), params, { preserveState: true });
    };

    const submitAdd = (e) => {
        e.preventDefault();
        addForm.post(route('plugin.modrix.newsletter.subscribers.store'), { preserveScroll: true, onSuccess: () => addForm.reset() });
    };

    const submitImport = (e) => {
        e.preventDefault();
        importForm.post(route('plugin.modrix.newsletter.subscribers.import'), { preserveScroll: true, onSuccess: () => importForm.reset() });
    };

    const toneFor = (st) => ({ subscribed: 'green', pending: 'amber', unsubscribed: 'gray' }[st] || 'gray');

    return h(ui.AdminLayout, {
        title: 'Newsletter subscribers',
        subtitle: 'Grow your list, tag audiences, import/export CSV, and manage opt-in status.',
        actions: h(React.Fragment, null,
            h('a', { className: 'btn btn-light', href: route('plugin.modrix.newsletter.subscribers.export', filters.status !== 'all' ? { status: filters.status } : {}) }, 'Export CSV'),
            h(Link, { className: 'btn', href: route('plugin.modrix.newsletter.campaigns.index') }, 'Email campaigns')),
    },
    h(ui.ErrorFlash, { errors }),
    h('div', { className: 'stats' },
        h('div', { className: 'stat' }, h('small', null, 'Total'), h('b', null, counts.all)),
        h('div', { className: 'stat' }, h('small', null, 'Subscribed'), h('b', null, counts.subscribed)),
        h('div', { className: 'stat' }, h('small', null, 'Pending confirmation'), h('b', null, counts.pending)),
        h('div', { className: 'stat' }, h('small', null, 'Unsubscribed'), h('b', null, counts.unsubscribed))),
    h('div', { className: 'grid-2' },
        h('div', { className: 'panel' },
            h('div', { className: 'tabs' },
                ['all', 'subscribed', 'pending', 'unsubscribed'].map((st) => h('a', {
                    key: st,
                    href: `#${st}`,
                    className: filters.status === st ? 'on' : undefined,
                    onClick: (e) => { e.preventDefault(); applyFilter({ status: st }); },
                }, `${st[0].toUpperCase() + st.slice(1)} (${counts[st] ?? 0})`))),
            h('form', { className: 'toolbar', onSubmit: (e) => { e.preventDefault(); applyFilter({ q }); } },
                h('input', { type: 'search', placeholder: 'Search email or name…', value: q, onChange: (e) => setQ(e.target.value) }),
                h('button', { className: 'btn btn-light', type: 'submit' }, 'Search')),
            h('div', { className: 'table-wrap' },
                h('table', null,
                    h('thead', null, h('tr', null, h('th', null, 'Subscriber'), h('th', null, 'Tags'), h('th', null, 'Status'), h('th', null, 'Joined'), h('th'))),
                    h('tbody', null, subscribers.data.length
                        ? subscribers.data.map((s) => h('tr', { key: s.id },
                            h('td', { className: 'title' },
                                s.email,
                                s.name ? h('div', { className: 'sub' }, s.name) : null),
                            h('td', null, s.tags.length ? s.tags.map((t) => h('span', { key: t, style: { marginRight: 4 } }, h(ui.Badge, { tone: 'purple' }, t))) : '—'),
                            h('td', null, h(ui.Badge, { tone: toneFor(s.status) }, s.status)),
                            h('td', { className: 'sub' }, s.joined),
                            h('td', null,
                                h('div', { className: 'actions' },
                                    s.status !== 'subscribed'
                                        ? h('button', {
                                            className: 'btn btn-light btn-sm',
                                            type: 'button',
                                            onClick: () => router.put(route('plugin.modrix.newsletter.subscribers.update', s.id), { name: s.name, status: 'subscribed', tags: s.tags.join(',') }, { preserveScroll: true }),
                                        }, 'Subscribe')
                                        : h('button', {
                                            className: 'btn btn-light btn-sm',
                                            type: 'button',
                                            onClick: () => router.put(route('plugin.modrix.newsletter.subscribers.update', s.id), { name: s.name, status: 'unsubscribed', tags: s.tags.join(',') }, { preserveScroll: true }),
                                        }, 'Unsubscribe'),
                                    h(ui.DangerButton, { confirm: `Delete ${s.email}?`, url: route('plugin.modrix.newsletter.subscribers.destroy', s.id) }, 'Delete')))))
                        : h('tr', null, h('td', { colSpan: 5, className: 'empty' }, 'No subscribers yet.'))))),
            h(ui.Pager, { paginator: subscribers })),
        h('div', null,
            h('div', { className: 'panel' },
                h('h2', null, 'Add subscriber'),
                h('form', { onSubmit: submitAdd, style: { marginTop: 12 } },
                    h(ui.Field, { form: addForm, name: 'email', label: 'Email', type: 'email', required: true }),
                    h(ui.Field, { form: addForm, name: 'name', label: 'Name (optional)' }),
                    h(ui.Field, { form: addForm, name: 'tags', label: 'Tags (comma-separated)', placeholder: 'vip, launch' }),
                    h('button', { className: 'btn', type: 'submit', disabled: addForm.processing }, 'Add subscriber'))),
            h('div', { className: 'panel' },
                h('h2', null, 'Bulk import'),
                h('p', { className: 'hint' }, 'Paste one email per line, or "Name, email@example.com".'),
                h('form', { onSubmit: submitImport },
                    h(ui.Field, { form: importForm, name: 'bulk', label: 'Subscribers list', type: 'textarea', rows: 4, required: true }),
                    h(ui.Field, { form: importForm, name: 'tag', label: 'Apply tag (optional)', placeholder: 'imported-2026' }),
                    h('button', { className: 'btn btn-light', type: 'submit', disabled: importForm.processing }, 'Import list'))))));
}

/** Admin: Campaigns Index */
function CampaignsIndex({ campaigns, activeSubscribers }) {
    return h(ui.AdminLayout, {
        title: 'Email campaigns',
        subtitle: `Compose and broadcast Markdown newsletters to ${activeSubscribers} active subscriber(s).`,
        actions: h(React.Fragment, null,
            h(Link, { className: 'btn btn-light', href: route('plugin.modrix.newsletter.subscribers.index') }, 'Subscribers'),
            h(Link, { className: 'btn', href: route('plugin.modrix.newsletter.campaigns.create') }, '+ New campaign')),
    },
    h('div', { className: 'panel' },
        h('div', { className: 'table-wrap' },
            h('table', null,
                h('thead', null, h('tr', null, h('th', null, 'Subject'), h('th', null, 'Audience'), h('th', null, 'Status'), h('th', null, 'Delivered'), h('th'))),
                h('tbody', null, campaigns.data.length
                    ? campaigns.data.map((c) => h('tr', { key: c.id },
                        h('td', { className: 'title' }, h(Link, { href: route('plugin.modrix.newsletter.campaigns.edit', c.id) }, c.subject)),
                        h('td', null, c.target_tag),
                        h('td', null, h(ui.Badge, { tone: c.status === 'sent' ? 'green' : 'amber' }, c.status)),
                        h('td', { className: 'sub' }, c.status === 'sent' ? `${c.recipients_count} sent (${c.sent_at})` : `Draft · updated ${c.updated}`),
                        h('td', null,
                            h('div', { className: 'actions' },
                                h(Link, { className: 'btn btn-light btn-sm', href: route('plugin.modrix.newsletter.campaigns.edit', c.id) }, c.status === 'sent' ? 'View' : 'Edit'),
                                h(ui.DangerButton, { confirm: `Delete "${c.subject}"?`, url: route('plugin.modrix.newsletter.campaigns.destroy', c.id) }, 'Delete')))))
                    : h('tr', null, h('td', { colSpan: 5, className: 'empty' }, 'No campaigns yet.'))))),
        h(ui.Pager, { paginator: campaigns })));
}

/** Admin: Campaign Editor */
function CampaignEditor({ campaign, previewHtml, activeSubscribers }) {
    const { errors } = usePage().props;
    const exists = campaign.id !== null;
    const isSent = campaign.status === 'sent';
    const form = useForm({
        subject: campaign.subject || '',
        preview_text: campaign.preview_text || '',
        body_markdown: campaign.body_markdown || '',
        target_tag: campaign.target_tag || '',
    });
    const testForm = useForm({ test_email: '' });

    const submit = (e) => {
        e.preventDefault();
        exists
            ? form.put(route('plugin.modrix.newsletter.campaigns.update', campaign.id), { preserveScroll: true })
            : form.post(route('plugin.modrix.newsletter.campaigns.store'));
    };

    const sendTest = (e) => {
        e.preventDefault();
        testForm.post(route('plugin.modrix.newsletter.campaigns.test', campaign.id), { preserveScroll: true });
    };

    return h(ui.AdminLayout, {
        title: exists ? campaign.subject : 'New campaign',
        subtitle: isSent ? `Sent to ${campaign.recipients_count} subscriber(s) on ${campaign.sent_at}.` : `Ready to target ${activeSubscribers} active subscriber(s).`,
        actions: h(React.Fragment, null,
            h(Link, { className: 'btn btn-light', href: route('plugin.modrix.newsletter.campaigns.index') }, '← All campaigns'),
            exists && !isSent
                ? h('button', {
                    className: 'btn',
                    type: 'button',
                    onClick: () => ui.confirmVisit(`Send this campaign now to ${activeSubscribers} subscriber(s)?`, 'post', route('plugin.modrix.newsletter.campaigns.send', campaign.id)),
                }, 'Send campaign now')
                : null),
    },
    h(ui.ErrorFlash, { errors }),
    h('div', { className: 'grid-2' },
        h('div', { className: 'panel' },
            h('form', { onSubmit: submit },
                h(ui.Field, { form, name: 'subject', label: 'Email subject', required: true, disabled: isSent }),
                h('div', { className: 'row' },
                    h(ui.Field, { form, name: 'preview_text', label: 'Inbox preview text (optional)', disabled: isSent }),
                    h(ui.Field, { form, name: 'target_tag', label: 'Filter by tag (optional)', placeholder: 'Leave empty for all active', disabled: isSent })),
                h(ui.Field, { form, name: 'body_markdown', label: 'Email body (Markdown)', type: 'textarea', className: 'editor', rows: 14, required: true, disabled: isSent }),
                !isSent ? h('button', { className: 'btn', type: 'submit', disabled: form.processing }, exists ? 'Save draft' : 'Create draft') : null)),
        h('div', null,
            exists ? h('div', { className: 'panel' },
                h('h2', null, 'Rendered preview'),
                h('div', { style: { marginTop: 12, padding: 16, border: '1px solid var(--line)', borderRadius: 12 }, dangerouslySetInnerHTML: { __html: previewHtml } })) : null,
            exists && !isSent ? h('div', { className: 'panel' },
                h('h2', null, 'Send test email'),
                h('p', { className: 'hint' }, 'Verify formatting before broadcasting.'),
                h('form', { onSubmit: sendTest },
                    h(ui.Field, { form: testForm, name: 'test_email', label: 'Test recipient', type: 'email', required: true }),
                    h('button', { className: 'btn btn-light', type: 'submit', disabled: testForm.processing }, 'Send test'))) : null)));
}

registerPlugin('modrix/newsletter', {
    blocks: { 'modrix/newsletter:signup': SignupBlock },
    pages: {
        Status: StatusPage,
        SubscribersIndex,
        CampaignsIndex,
        CampaignEditor,
    },
});
