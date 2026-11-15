const { React, Inertia, ui, registerPlugin } = window.ModrixCMS;
const h = React.createElement;
const { useMemo, useState } = React;
const { router, useForm } = Inertia;

/** Public Block: FAQ Accordion */
function AccordionBlock({ data, anchor }) {
    const items = data.items || [];
    const [query, setQuery] = useState('');
    const [selectedCat, setSelectedCat] = useState('All');
    const [openIds, setOpenIds] = useState(() => new Set(items[0] ? [items[0].id] : []));
    const [voted, setVoted] = useState({});
    const [counts, setCounts] = useState({});

    const categories = useMemo(() => {
        const set = new Set(items.map((i) => i.category).filter(Boolean));
        return ['All', ...Array.from(set)];
    }, [items]);

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();
        return items.filter((item) => {
            if (selectedCat !== 'All' && item.category !== selectedCat) return false;
            if (!q) return true;
            return item.question.toLowerCase().includes(q) || item.category.toLowerCase().includes(q);
        });
    }, [items, query, selectedCat]);

    const toggle = (id) => {
        setOpenIds((prev) => {
            const next = new Set(data.allow_multiple_open ? prev : []);
            if (prev.has(id)) next.delete(id);
            else next.add(id);
            return next;
        });
    };

    const vote = async (item, choice) => {
        if (voted[item.id]) return;
        setVoted((prev) => ({ ...prev, [item.id]: choice }));
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        try {
            const res = await fetch(item.vote_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...(token ? { 'X-CSRF-TOKEN': token } : {}) },
                body: JSON.stringify({ vote: choice }),
            });
            if (res.ok) {
                const json = await res.json();
                setCounts((prev) => ({ ...prev, [item.id]: json }));
            }
        } catch {
            // Ignore transient network errors.
        }
    };

    const headEl = (data.eyebrow || data.title || data.subtitle)
        ? h('div', { className: 'section-head' },
            data.eyebrow ? h('p', { className: 'eyebrow' }, data.eyebrow) : null,
            data.title ? h('h2', null, data.title) : null,
            data.subtitle ? h('p', null, data.subtitle) : null)
        : null;

    const bodyEl = h('div', null,
        data.searchable && items.length > 0 ? h('div', { className: 'mfaq-controls' },
            h('input', {
                className: 'mfaq-search',
                type: 'search',
                placeholder: 'Search questions…',
                value: query,
                onChange: (e) => setQuery(e.target.value),
            }),
            categories.length > 2 ? h('div', { className: 'mfaq-cats' },
                categories.map((cat) => h('button', {
                    key: cat,
                    type: 'button',
                    className: `mfaq-cat${selectedCat === cat ? ' on' : ''}`,
                    onClick: () => setSelectedCat(cat),
                }, cat))) : null) : null,
        filtered.length === 0
            ? h('p', { className: 'empty-note' }, items.length ? 'No questions match your search.' : 'No FAQs published yet.')
            : h('div', { className: 'mfaq-list' },
                filtered.map((item) => {
                    const isOpen = openIds.has(item.id);
                    const yesCount = counts[item.id]?.helpful_yes ?? item.helpful_yes;
                    const noCount = counts[item.id]?.helpful_no ?? item.helpful_no;
                    return h('article', { key: item.id, className: `mfaq-item${isOpen ? ' open' : ''}` },
                        h('button', {
                            type: 'button',
                            className: 'mfaq-trigger',
                            'aria-expanded': isOpen,
                            onClick: () => toggle(item.id),
                        },
                        h('span', null, item.question),
                        h('span', { className: 'mfaq-chevron', 'aria-hidden': 'true' }, '+')),
                        isOpen ? h('div', { className: 'mfaq-panel' },
                            h(ui.Html, { className: 'mfaq-answer', html: item.answer_html }),
                            data.allow_voting ? h('div', { className: 'mfaq-vote' },
                                h('span', null, voted[item.id] ? 'Thanks for your feedback!' : 'Was this helpful?'),
                                h('button', { type: 'button', disabled: Boolean(voted[item.id]), onClick: () => vote(item, 'yes') }, `Yes (${yesCount})`),
                                h('button', { type: 'button', disabled: Boolean(voted[item.id]), onClick: () => vote(item, 'no') }, `No (${noCount})`)) : null) : null);
                })));

    return h('section', { className: 'mfaq-section', id: anchor },
        h('div', { className: data.layout === 'split' ? 'wrap mfaq-split' : 'wrap narrow' },
            data.layout === 'split' ? (headEl || h('div')) : headEl,
            bodyEl));
}

/** Admin: FAQ Manager */
function ItemsIndex({ items, categories, filters }) {
    const [editingId, setEditingId] = useState(null);
    const [q, setQ] = useState(filters.q || '');
    const form = useForm({
        category: 'General',
        question: '',
        answer_markdown: '',
        sort_order: 0,
        is_published: true,
    });

    const startEdit = (item) => {
        setEditingId(item.id);
        form.setData({
            category: item.category,
            question: item.question,
            answer_markdown: item.answer_markdown,
            sort_order: item.sort_order,
            is_published: Boolean(item.is_published),
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
            ? form.put(route('plugin.modrix.faq.items.update', editingId), opts)
            : form.post(route('plugin.modrix.faq.items.store'), opts);
    };

    const filter = (next = {}) => {
        const params = {
            category: next.category !== undefined ? next.category : filters.category,
            q: next.q !== undefined ? next.q : q,
        };
        Object.keys(params).forEach((k) => { if (!params[k]) delete params[k]; });
        router.get(route('plugin.modrix.faq.items.index'), params, { preserveState: true });
    };

    return h(ui.AdminLayout, {
        title: 'FAQs & Help Center',
        subtitle: 'Organize questions by category with Markdown answers and helpfulness tracking.',
    },
    h('div', { className: 'grid-2' },
        h('div', { className: 'panel' },
            h('form', { className: 'toolbar', onSubmit: (e) => { e.preventDefault(); filter({ q }); } },
                h('select', {
                    value: filters.category || '',
                    onChange: (e) => filter({ category: e.target.value }),
                },
                h('option', { value: '' }, 'All categories'),
                categories.map((c) => h('option', { key: c, value: c }, c))),
                h('input', { type: 'search', placeholder: 'Search FAQs…', value: q, onChange: (e) => setQ(e.target.value) }),
                h('button', { className: 'btn btn-light', type: 'submit' }, 'Filter')),
            h('div', { className: 'table-wrap' },
                h('table', null,
                    h('thead', null, h('tr', null, h('th', null, 'Question'), h('th', null, 'Category'), h('th', null, 'Votes'), h('th', null, 'Status'), h('th'))),
                    h('tbody', null, items.data.length
                        ? items.data.map((item) => h('tr', { key: item.id },
                            h('td', { className: 'title' }, item.question),
                            h('td', null, h(ui.Badge, { tone: 'purple' }, item.category)),
                            h('td', { className: 'sub' }, `👍 ${item.helpful_yes} · 👎 ${item.helpful_no}`),
                            h('td', null, h(ui.Badge, { tone: item.is_published ? 'green' : 'gray' }, item.is_published ? 'Published' : 'Draft')),
                            h('td', null,
                                h('div', { className: 'actions' },
                                    h('button', { className: 'btn btn-light btn-sm', type: 'button', onClick: () => startEdit(item) }, 'Edit'),
                                    h(ui.DangerButton, { confirm: 'Delete this FAQ item?', url: route('plugin.modrix.faq.items.destroy', item.id) }, 'Delete')))))
                        : h('tr', null, h('td', { colSpan: 5, className: 'empty' }, 'No FAQs yet. Add one on the right.'))))),
            h(ui.Pager, { paginator: items })),
        h('div', { className: 'panel' },
            h('h2', null, editingId ? 'Edit FAQ' : 'Add FAQ'),
            h('form', { onSubmit: submit, style: { marginTop: 14 } },
                h(ui.Field, { form, name: 'category', label: 'Category', required: true, placeholder: 'General, Billing, Shipping…' }),
                h(ui.Field, { form, name: 'question', label: 'Question', required: true }),
                h(ui.Field, { form, name: 'answer_markdown', label: 'Answer (Markdown)', type: 'textarea', rows: 6, required: true }),
                h(ui.Field, { form, name: 'sort_order', label: 'Sort order', type: 'number', min: 0 }),
                h(ui.Check, { form, name: 'is_published', label: 'Published' }),
                h('div', { className: 'actions', style: { marginTop: 14 } },
                    h('button', { className: 'btn', type: 'submit', disabled: form.processing }, editingId ? 'Save FAQ' : 'Add FAQ'),
                    editingId ? h('button', { className: 'btn btn-light', type: 'button', onClick: resetForm }, 'Cancel') : null)))));
}

registerPlugin('modrix/faq', {
    blocks: { 'modrix/faq:accordion': AccordionBlock },
    pages: { ItemsIndex },
});
