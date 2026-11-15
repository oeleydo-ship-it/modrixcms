/**
 * Modrix Form Builder — Admin Drag & Drop Visual Studio
 */

const ICON_PATHS = {
    text: '<path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/>',
    email: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    phone: '<path d="M22 16.9v3a2 2 0 0 1-2.2 2A19.8 19.8 0 0 1 11.2 19a19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7 12.8 12.8 0 0 0 .7 2.8 2 2 0 0 1-.4 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4 12.8 12.8 0 0 0 2.8.7A2 2 0 0 1 22 16.9z"/>',
    textarea: '<path d="M17 6H3"/><path d="M21 12H3"/><path d="M15 18H3"/>',
    number: '<path d="M4 9h16"/><path d="M4 15h16"/><path d="M10 3 8 21"/><path d="M16 3l-2 18"/>',
    url: '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7.1-7.1l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7.1 7.1l1.7-1.7"/>',
    select: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m8 11 4 4 4-4"/>',
    radio: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3" fill="currentColor"/>',
    checkbox: '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="m9 12 2 2 4-4"/>',
    checkbox_group: '<rect x="3" y="4" width="7" height="7" rx="1.5"/><path d="m5 7.5 1.5 1.5L9 6"/><path d="M14 7.5h7"/><rect x="3" y="13" width="7" height="7" rx="1.5"/><path d="M14 16.5h7"/>',
    consent: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
    rating: '<polygon points="12 2 15.1 8.3 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 8.9 8.3 12 2"/>',
    date: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
    time: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    hidden: '<path d="M17.9 17.9A10.1 10.1 0 0 1 12 20c-7 0-11-8-11-8a18.5 18.5 0 0 1 5.1-5.9"/><path d="M9.9 4.2A9.1 9.1 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.2 3.2"/><path d="M1 1l22 22"/>',
    heading: '<path d="M6 12h12"/><path d="M6 4v16"/><path d="M18 4v16"/>',
    paragraph: '<path d="M13 4v16"/><path d="M17 4v16"/><path d="M19 4H9.5a4.5 4.5 0 0 0 0 9H13"/>',
    divider: '<path d="M3 12h18"/>',
    grip: '<circle cx="9" cy="6" r="1.1" fill="currentColor"/><circle cx="15" cy="6" r="1.1" fill="currentColor"/><circle cx="9" cy="12" r="1.1" fill="currentColor"/><circle cx="15" cy="12" r="1.1" fill="currentColor"/><circle cx="9" cy="18" r="1.1" fill="currentColor"/><circle cx="15" cy="18" r="1.1" fill="currentColor"/>',
    plus: '<path d="M12 5v14"/><path d="M5 12h14"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
    x: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    trash: '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>',
    copy: '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
    undo: '<path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/>',
    redo: '<path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/>',
    eye: '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
    edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
    sliders: '<path d="M4 21v-7"/><path d="M4 10V3"/><path d="M12 21v-9"/><path d="M12 8V3"/><path d="M20 21v-5"/><path d="M20 12V3"/><path d="M2 14h4"/><path d="M10 8h4"/><path d="M18 16h4"/>',
    palette: '<circle cx="13.5" cy="6.5" r=".8" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".8" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".8" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".8" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.9 0 1.6-.7 1.6-1.6 0-.4-.1-.8-.4-1.1-.3-.3-.4-.7-.4-1.1 0-.9.7-1.6 1.6-1.6H16c3.3 0 6-2.7 6-6 0-4.9-4.5-8.6-10-8.6z"/>',
    settings: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 0 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 0 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 0 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 0 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 0 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 0 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 0 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8v.1a1.7 1.7 0 0 0 1.5 1H21a2 2 0 0 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
    lock: '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    unlock: '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/>',
    desktop: '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/>',
    tablet: '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M12 18h.01"/>',
    mobile: '<rect x="6" y="2" width="12" height="20" rx="2"/><path d="M12 18h.01"/>',
    branch: '<path d="M6 3v12"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/>',
    up: '<path d="m18 15-6-6-6 6"/>',
    down: '<path d="m6 9 6 6 6-6"/>',
    sparkles: '<path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3L12 3Z"/>',
    check: '<path d="M20 6 9 17l-5-5"/>',
    template: '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>',
    inbox: '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.5 5.1 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.8 4H7.2a2 2 0 0 0-1.7 1.1z"/>',
    forms: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/>',
    code: '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
    share: '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4"/><path d="m15.4 6.5-6.8 4"/>',
    layers: '<path d="m12 2 9 4.5-9 4.5L3 6.5 12 2Z"/><path d="m3 12 9 4.5 9-4.5"/><path d="m3 17.5 9 4.5 9-4.5"/>',
    save: '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>',
};

const TYPE_CATEGORY = {
    text: 'basic',
    email: 'basic',
    phone: 'basic',
    textarea: 'basic',
    number: 'basic',
    url: 'basic',
    select: 'choice',
    radio: 'choice',
    checkbox: 'choice',
    checkbox_group: 'choice',
    consent: 'choice',
    rating: 'choice',
    date: 'advanced',
    time: 'advanced',
    hidden: 'advanced',
    heading: 'layout',
    paragraph: 'layout',
    divider: 'layout',
};

const CONDITION_OPERATORS = [
    { value: 'equals', label: 'equals' },
    { value: 'not_equals', label: 'does not equal' },
    { value: 'contains', label: 'contains' },
    { value: 'not_empty', label: 'is not empty' },
    { value: 'is_empty', label: 'is empty' },
    { value: 'greater_than', label: 'is greater than' },
    { value: 'less_than', label: 'is less than' },
];

function createIconHelper(h) {
    return function FbIcon({ name, size = 15, className = '' }) {
        const path = ICON_PATHS[name] || ICON_PATHS.text;
        return h('svg', {
            width: size,
            height: size,
            viewBox: '0 0 24 24',
            fill: 'none',
            stroke: 'currentColor',
            strokeWidth: 1.8,
            strokeLinecap: 'round',
            strokeLinejoin: 'round',
            className,
            'aria-hidden': 'true',
            dangerouslySetInnerHTML: { __html: path },
        });
    };
}

function defaultPlaceholderFor(type) {
    return {
        text: 'Enter text…',
        email: 'you@example.com',
        phone: '+1 555 0100',
        textarea: 'Write detailed notes…',
        number: '0',
        url: 'https://example.com',
        select: 'Choose an option',
        date: 'YYYY-MM-DD',
        time: '10:00 AM',
    }[type] || 'Type here…';
}

export function createFormBuilderAdminPages(shared) {
    const {
        React,
        h,
        Link,
        router,
        useForm,
        ui,
        FIELD_GROUPS,
        FIELD_CATALOG,
        NON_INPUT_TYPES,
        ACCENT_SWATCHES,
        PHONE_PRESETS,
        STARTER_TEMPLATES,
        uid,
        makeFieldKey,
        buildNewField,
        initialValuesFor,
        validatePreviewValues,
        shellClassesAndStyle,
        DynamicFormRenderer,
    } = shared;

    const { useState, useMemo, useRef, useEffect, useCallback } = React;
    const FbIcon = createIconHelper(h);

    /** Copy slug helper badge */
    function SlugCopyPill({ slug }) {
        const [copied, setCopied] = useState(false);

        if (!slug) {
            return h('span', { className: 'mfb-slug-chip' }, 'unsaved slug');
        }

        const copy = (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (navigator?.clipboard?.writeText) {
                navigator.clipboard.writeText(slug).catch(() => {});
            }
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        };

        return h('button', {
            type: 'button',
            className: `mfb-slug-chip${copied ? ' is-copied' : ''}`,
            onClick: copy,
            title: 'Copy form slug',
        },
        h(FbIcon, { name: copied ? 'check' : 'copy', size: 11 }),
        copied ? 'Copied!' : slug);
    }

    /** Switch Toggle Control */
    function SwitchRow({ label, hint, checked, onChange }) {
        return h('label', { className: 'mfb-switch-row' },
            h('span', { className: 'mfb-switch-text' },
                label,
                hint ? h('span', { className: 'mfb-switch-sub' }, hint) : null),
            h('button', {
                type: 'button',
                role: 'switch',
                'aria-checked': Boolean(checked),
                className: `mfb-switch${checked ? ' on' : ''}`,
                onClick: () => onChange(!checked),
            }));
    }

    /** Visual inert mockup of a field on the WYSIWYG Canvas */
    function CanvasFieldBody({
        field,
        isEditing,
        editingText,
        onChangeEditingText,
        onStartInlineEdit,
        onCommitInlineEdit,
        onCancelInlineEdit,
    }) {
        const isLayout = NON_INPUT_TYPES.includes(field.type);
        const inputRef = useRef(null);

        useEffect(() => {
            if (isEditing && inputRef.current) {
                inputRef.current.focus();
                inputRef.current.select();
            }
        }, [isEditing]);

        const labelNode = isEditing
            ? h('input', {
                ref: inputRef,
                type: 'text',
                className: 'mfb-inline-edit-input',
                value: editingText,
                onClick: (e) => e.stopPropagation(),
                onChange: (e) => onChangeEditingText(e.target.value),
                onBlur: onCommitInlineEdit,
                onKeyDown: (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        onCommitInlineEdit();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        onCancelInlineEdit();
                    }
                },
            })
            : h('span', {
                className: 'mfb-canvas-label',
                title: 'Double-click to rename inline',
                onDoubleClick: (e) => {
                    e.stopPropagation();
                    onStartInlineEdit();
                },
            },
            field.label || (field.type === 'divider' ? 'Divider' : 'Untitled field'),
            !isLayout && field.required ? h('span', { className: 'mfb-req' }, '*') : null);

        if (field.type === 'divider') {
            return h('div', { className: 'mfb-canvas-field-body' },
                h('hr', { style: { border: 0, borderTop: '1px solid #cbd5e1', width: '100%', margin: '8px 0' } }));
        }

        if (field.type === 'heading') {
            return h('div', { className: 'mfb-canvas-field-body' },
                h('div', { style: { fontSize: 17, fontWeight: 700, color: 'var(--ink, #111827)' } }, labelNode),
                field.help ? h('div', { style: { fontSize: 12.5, color: 'var(--muted, #6b7280)' } }, field.help) : null);
        }

        if (field.type === 'paragraph') {
            return h('div', { className: 'mfb-canvas-field-body' },
                h('div', { style: { fontWeight: 600, fontSize: 13 } }, labelNode),
                h('div', { style: { fontSize: 12.5, color: 'var(--muted, #6b7280)', lineHeight: 1.5 } },
                    field.help || 'Helper instructions appear here for visitors.'));
        }

        if (field.type === 'hidden') {
            return h('div', { className: 'mfb-canvas-field-body' },
                h('div', { className: 'mfb-canvas-label-row' }, labelNode),
                h('div', { className: 'mfb-mock-hidden' },
                    `hidden[${field.key}] = "${field.default_value || ''}"`));
        }

        const renderMockControl = () => {
            if (field.type === 'textarea') {
                return h('div', { className: 'mfb-mock-control is-textarea' },
                    h('span', null, field.placeholder || defaultPlaceholderFor('textarea')),
                    h('span', { style: { fontSize: 11 } }, `${field.rows || 4} rows`));
            }
            if (field.type === 'select') {
                const count = (field.options || []).length;
                return h('div', { className: 'mfb-mock-control' },
                    h('span', null, field.placeholder || `— Select (${count} options) —`),
                    h(FbIcon, { name: 'down', size: 14 }));
            }
            if (field.type === 'radio' || field.type === 'checkbox_group') {
                const isMulti = field.type === 'checkbox_group';
                const opts = (field.options || ['Option 1', 'Option 2']).slice(0, 5);
                const extra = Math.max(0, (field.options || []).length - opts.length);
                return h('div', { className: 'mfb-mock-choices' },
                    opts.map((opt, idx) => h('span', { key: `${idx}-${opt}`, className: 'mfb-mock-choice' },
                        h('span', { className: isMulti ? 'mfb-mock-check-box' : 'mfb-mock-radio-dot' }),
                        opt)),
                    extra > 0 ? h('span', { className: 'mfb-mock-choice' }, `+${extra} more`) : null);
            }
            if (field.type === 'checkbox' || field.type === 'consent') {
                return h('div', {
                    className: 'mfb-mock-choices',
                    style: field.type === 'consent'
                        ? { padding: '8px 10px', borderRadius: 8, background: '#f8fafc', border: '1px solid #e2e8f0' }
                        : undefined,
                },
                h('span', { className: 'mfb-mock-choice', style: { background: 'transparent', border: 0, padding: 0 } },
                    h('span', { className: 'mfb-mock-check-box' }),
                    field.placeholder || field.label || 'Check to confirm'));
            }
            if (field.type === 'rating') {
                const total = Math.max(3, Math.min(10, Number(field.max_stars || 5)));
                const active = Number(field.default_value || 0);
                return h('div', { style: { display: 'inline-flex', gap: 4 } },
                    Array.from({ length: total }, (_, idx) => h('span', {
                        key: idx,
                        style: {
                            fontSize: 18,
                            lineHeight: 1,
                            color: idx + 1 <= active ? '#f59e0b' : '#d1d5db',
                        },
                    }, '★')));
            }

            const trailingIcon = field.type === 'date' ? 'date' : field.type === 'time' ? 'time' : null;
            return h('div', { className: 'mfb-mock-control' },
                h('span', null, field.placeholder || (field.default_value ? `Default: ${field.default_value}` : defaultPlaceholderFor(field.type))),
                trailingIcon ? h(FbIcon, { name: trailingIcon, size: 14 }) : null);
        };

        return h('div', { className: 'mfb-canvas-field-body' },
            field.type !== 'checkbox' && field.type !== 'consent'
                ? h('div', { className: 'mfb-canvas-label-row' }, labelNode)
                : null,
            renderMockControl(),
            field.help ? h('div', { style: { fontSize: 11.5, color: 'var(--muted, #6b7280)' } }, field.help) : null);
    }

    /** Admin: Draggable Options Editor with Bulk Paste */
    function OptionsEditor({ options, onChange }) {
        const list = options?.length ? options : ['Option 1', 'Option 2'];
        const [bulkMode, setBulkMode] = useState(false);
        const [bulkText, setBulkText] = useState(list.join('\n'));
        const [dragOptIdx, setDragOptIdx] = useState(null);
        const [overOptIdx, setOverOptIdx] = useState(null);

        const update = (idx, value) => onChange(list.map((o, i) => (i === idx ? value : o)));
        const addOption = () => onChange([...list, `Option ${list.length + 1}`]);
        const remove = (idx) => {
            if (list.length <= 1) return;
            onChange(list.filter((_, i) => i !== idx));
        };
        const moveOption = (fromIdx, toIdx) => {
            if (fromIdx === toIdx || toIdx < 0 || toIdx >= list.length) return;
            const copy = [...list];
            const [item] = copy.splice(fromIdx, 1);
            copy.splice(toIdx, 0, item);
            onChange(copy);
        };

        return h('div', { className: 'mfb-options-box' },
            h('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 } },
                h('span', { style: { fontSize: 11.5, fontWeight: 700, color: 'var(--muted, #6b7280)' } },
                    `Choices (${list.length})`),
                h('button', {
                    type: 'button',
                    className: 'btn btn-light btn-sm',
                    style: { padding: '2px 8px', fontSize: 11 },
                    onClick: () => {
                        if (!bulkMode) setBulkText(list.join('\n'));
                        setBulkMode(!bulkMode);
                    },
                }, bulkMode ? 'Row editor' : 'Bulk paste')),
            bulkMode
                ? h('div', null,
                    h('textarea', {
                        rows: 5,
                        value: bulkText,
                        placeholder: 'One option per line…',
                        onChange: (e) => setBulkText(e.target.value),
                    }),
                    h('div', { style: { display: 'flex', gap: 6, marginTop: 8 } },
                        h('button', {
                            type: 'button',
                            className: 'btn btn-sm',
                            onClick: () => {
                                const parsed = bulkText.split(/\r?\n/).map((s) => s.trim()).filter(Boolean);
                                onChange(parsed.length ? parsed : ['Option 1']);
                                setBulkMode(false);
                            },
                        }, 'Apply choices'),
                        h('button', {
                            type: 'button',
                            className: 'btn btn-light btn-sm',
                            onClick: () => setBulkMode(false),
                        }, 'Cancel')))
                : h(React.Fragment, null,
                    list.map((opt, idx) => h('div', {
                        key: idx,
                        className: `mfb-option-row${overOptIdx === idx && dragOptIdx !== idx ? ' is-over' : ''}`,
                        draggable: true,
                        onDragStart: (e) => {
                            setDragOptIdx(idx);
                            e.dataTransfer.effectAllowed = 'move';
                            e.dataTransfer.setData('text/plain', String(idx));
                        },
                        onDragOver: (e) => {
                            if (dragOptIdx === null) return;
                            e.preventDefault();
                            setOverOptIdx(idx);
                        },
                        onDrop: (e) => {
                            e.preventDefault();
                            if (dragOptIdx !== null && dragOptIdx !== idx) {
                                moveOption(dragOptIdx, idx);
                            }
                            setDragOptIdx(null);
                            setOverOptIdx(null);
                        },
                        onDragEnd: () => {
                            setDragOptIdx(null);
                            setOverOptIdx(null);
                        },
                    },
                    h('span', { className: 'mfb-drag-grip', title: 'Drag option to reorder', style: { width: 20, height: 26 } },
                        h(FbIcon, { name: 'grip', size: 12 })),
                    h('input', {
                        type: 'text',
                        value: opt,
                        placeholder: `Option ${idx + 1}`,
                        onChange: (e) => update(idx, e.target.value),
                        onKeyDown: (e) => {
                            if (e.key === 'Enter' && idx === list.length - 1) {
                                e.preventDefault();
                                addOption();
                            }
                        },
                    }),
                    h('button', {
                        type: 'button',
                        className: 'mfb-mini-act',
                        disabled: idx === 0,
                        title: 'Move up',
                        onClick: () => moveOption(idx, idx - 1),
                    }, h(FbIcon, { name: 'up', size: 12 })),
                    h('button', {
                        type: 'button',
                        className: 'mfb-mini-act is-del',
                        disabled: list.length <= 1,
                        title: 'Remove choice',
                        onClick: () => remove(idx),
                    }, h(FbIcon, { name: 'x', size: 12 })))),
                    h('button', {
                        type: 'button',
                        className: 'btn btn-light btn-sm',
                        style: { width: '100%', marginTop: 4 },
                        onClick: addOption,
                    },
                    h(FbIcon, { name: 'plus', size: 13 }),
                    'Add choice')));
    }

    /** Starter Templates Modal */
    /** A read-only value with a Copy button (a single line, or a code box for embed snippets). */
    function CopyField({ label, value, multiline = false, hint, disabled = false }) {
        const [copied, setCopied] = useState(false);

        const copy = () => {
            const done = () => {
                setCopied(true);
                setTimeout(() => setCopied(false), 1800);
            };
            if (navigator?.clipboard?.writeText) {
                navigator.clipboard.writeText(value).then(done).catch(done);
            } else {
                done();
            }
        };

        return h('div', { className: `mfb-copy-field${disabled ? ' is-disabled' : ''}` },
            h('div', { className: 'mfb-copy-head' },
                h('label', null, label),
                h('button', { type: 'button', className: 'mfb-copy-btn', onClick: copy, disabled },
                    h(FbIcon, { name: copied ? 'check' : 'copy', size: 12 }),
                    copied ? 'Copied' : 'Copy')),
            multiline
                ? h('textarea', { className: 'mfb-copy-code', readOnly: true, rows: 4, value, onFocus: (e) => e.target.select(), spellCheck: false })
                : h('input', { className: 'mfb-copy-input', type: 'text', readOnly: true, value, onFocus: (e) => e.target.select() }),
            hint ? h('div', { className: 'mfb-copy-hint' }, hint) : null);
    }

    /**
     * The form's public link and the code to put it on any page: a link, an iframe, and how to add it
     * in the page builder. Links use the saved form, so a new or changed form says so.
     */
    function ShareModal({ open, onClose, form, saved, unsaved }) {
        if (!open) return null;

        const origin = typeof window !== 'undefined' ? window.location.origin : '';
        const slug = form.slug || '';
        const url = slug ? `${origin}/forms/${slug}` : '';
        const embedUrl = url ? `${url}?embed=1` : '';
        const title = (form.name || 'Form').replace(/"/g, '&quot;');
        const iframe = url
            ? `<iframe src="${embedUrl}" title="${title}" width="100%" height="640" style="border:0;max-width:720px" loading="lazy"></iframe>`
            : '';
        const link = url ? `<a href="${url}">${title}</a>` : '';
        const ready = saved && Boolean(slug);
        const paused = form.status !== 'active';

        let notice = null;
        if (!saved) {
            notice = 'This form has not been created yet. Create it first; the link and code work as soon as it is saved.';
        } else if (unsaved) {
            notice = 'You have unsaved changes. The link and embed show the last saved version of the form, so save first.';
        } else if (paused) {
            notice = 'This form is paused. Visitors get a "not found" page until you set it to Active.';
        }

        return h('div', {
            className: 'mfb-modal-backdrop',
            role: 'dialog',
            'aria-modal': 'true',
            'aria-label': 'Share and embed this form',
            onClick: onClose,
        },
        h('div', { className: 'mfb-modal mfb-share-modal', onClick: (e) => e.stopPropagation() },
            h('div', { className: 'mfb-modal-head' },
                h('div', null,
                    h('h3', null, 'Share & embed'),
                    h('div', { style: { fontSize: 12, color: 'var(--muted, #6b7280)', marginTop: 2 } },
                        'Send people to this form, or put it on any page.')),
                h('button', { type: 'button', className: 'mfb-icon-btn', onClick: onClose, 'aria-label': 'Close' },
                    h(FbIcon, { name: 'x', size: 15 }))),
            h('div', { className: 'mfb-modal-body' },
                notice ? h('div', { className: 'mfb-share-notice' }, notice) : null,

                h(CopyField, { label: 'Form link', value: url || 'Add a form title to get a link', disabled: !url, hint: 'A page of its own with just this form. Use it in emails, social posts or buttons.' }),
                ready && !paused
                    ? h('a', { className: 'mfb-open-link', href: url, target: '_blank', rel: 'noopener' }, 'Open the form in a new tab ↗')
                    : null,

                h(CopyField, {
                    label: 'Embed code (iframe)',
                    value: iframe || 'Add a form title to get the code',
                    multiline: true,
                    disabled: !iframe,
                    hint: 'Paste this into any website or page. It shows the form without the site header and footer.',
                }),

                h(CopyField, { label: 'Link code (HTML)', value: link || 'Add a form title to get the code', disabled: !link }),

                h('div', { className: 'mfb-share-tip' },
                    h('strong', null, 'In the page builder: '),
                    'add a ',
                    h('em', null, 'Dynamic Form'),
                    ' block and choose this form, or add an ',
                    h('em', null, 'HTML embed'),
                    ' (Code & scripts) block and paste the embed code above.'))));
    }

    function TemplatesModal({ open, onClose, onSelect }) {
        if (!open) return null;

        return h('div', {
            className: 'mfb-modal-backdrop',
            role: 'dialog',
            'aria-modal': 'true',
            'aria-label': 'Starter form templates',
            onClick: onClose,
        },
        h('div', { className: 'mfb-modal', onClick: (e) => e.stopPropagation() },
            h('div', { className: 'mfb-modal-head' },
                h('div', null,
                    h('h3', null, 'Choose a starter blueprint'),
                    h('div', { style: { fontSize: 12, color: 'var(--muted, #6b7280)', marginTop: 2 } },
                        'Loads pre-built fields and conditional logic onto the canvas (supports Undo).')),
                h('button', { type: 'button', className: 'mfb-icon-btn', onClick: onClose, 'aria-label': 'Close modal' },
                    h(FbIcon, { name: 'x', size: 15 }))),
            h('div', { className: 'mfb-modal-body' },
                h('div', { className: 'mfb-template-grid' },
                    Object.entries(STARTER_TEMPLATES).map(([tplKey, tpl]) => h('button', {
                        key: tplKey,
                        type: 'button',
                        className: 'mfb-template-card',
                        onClick: () => onSelect(tplKey, tpl),
                    },
                    h('div', null,
                        h('h4', null, tpl.name),
                        h('p', null, tpl.description)),
                    h('div', { className: 'mfb-template-chips' },
                        tpl.fields.length
                            ? tpl.fields.slice(0, 5).map((f) => h('span', { key: f.key, className: 'mfb-template-chip' }, f.label))
                            : h('span', { className: 'mfb-template-chip' }, '0 fields · start fresh'),
                        tpl.fields.length > 5 ? h('span', { className: 'mfb-template-chip' }, `+${tpl.fields.length - 5}`) : null)))))));
    }

    /** Right Inspector: Field Properties Tab */
    function FieldInspector({
        field,
        index,
        totalCount,
        allFields,
        updateField,
        renameFieldKey,
        duplicateField,
        moveField,
        removeField,
    }) {
        if (!field) {
            return h('div', { style: { textAlign: 'center', padding: '28px 12px', color: 'var(--muted, #6b7280)' } },
                h('div', { style: { marginBottom: 10, opacity: 0.65 } }, h(FbIcon, { name: 'sliders', size: 28 })),
                h('div', { style: { fontWeight: 600, fontSize: 13.5, color: 'var(--ink, #111827)', marginBottom: 4 } },
                    'No field selected'),
                h('div', { style: { fontSize: 12.5, lineHeight: 1.5 } },
                    'Click any field on the canvas to configure its label, key, column width, validation, and conditional visibility.'));
        }

        const isLayoutOnly = NON_INPUT_TYPES.includes(field.type);
        const hasOptions = ['select', 'radio', 'checkbox_group'].includes(field.type);
        const hasPlaceholder = !isLayoutOnly && !['radio', 'checkbox_group', 'date', 'time', 'hidden', 'rating'].includes(field.type);
        const hasLengthBounds = ['text', 'textarea', 'email', 'phone', 'url'].includes(field.type);
        const catalogMeta = FIELD_CATALOG.find((c) => c.type === field.type);
        const otherConditionFields = allFields.filter((other) => other.id !== field.id && !NON_INPUT_TYPES.includes(other.type));
        const syncLocked = field._syncKey !== false;

        const labelForField = field.type === 'heading'
            ? 'Heading title'
            : field.type === 'paragraph'
                ? 'Paragraph heading (optional)'
                : field.type === 'divider'
                    ? 'Outline label'
                    : 'Field label';

        return h('div', null,
            h('div', { className: 'mfb-insp-header' },
                h('div', { className: 'mfb-insp-header-left' },
                    h('span', { className: `mfb-palette-ic cat-${TYPE_CATEGORY[field.type] || 'basic'}` },
                        h(FbIcon, { name: field.type, size: 14 })),
                    h('div', { style: { minWidth: 0 } },
                        h('div', { style: { fontSize: 12.5, fontWeight: 700, color: 'var(--ink, #111827)' } },
                            catalogMeta?.label || field.type),
                        h('div', { style: { fontSize: 11, color: 'var(--muted, #6b7280)' } },
                            `Position #${index + 1} of ${totalCount}`))),
                h('div', { style: { display: 'inline-flex', gap: 4 } },
                    h('button', {
                        type: 'button',
                        className: 'mfb-mini-act',
                        title: 'Move earlier',
                        disabled: index === 0,
                        onClick: () => moveField(index, index - 1),
                    }, h(FbIcon, { name: 'up', size: 12 })),
                    h('button', {
                        type: 'button',
                        className: 'mfb-mini-act',
                        title: 'Move later',
                        disabled: index >= totalCount - 1,
                        onClick: () => moveField(index, index + 2),
                    }, h(FbIcon, { name: 'down', size: 12 })),
                    h('button', {
                        type: 'button',
                        className: 'mfb-mini-act',
                        title: 'Duplicate field',
                        onClick: () => duplicateField(index),
                    }, h(FbIcon, { name: 'copy', size: 12 })),
                    h('button', {
                        type: 'button',
                        className: 'mfb-mini-act is-del',
                        title: 'Delete field',
                        onClick: () => removeField(index),
                    }, h(FbIcon, { name: 'trash', size: 12 })))),

            /* Content Section */
            h('div', { className: 'mfb-insp-section' },
                h('h4', { className: 'mfb-insp-title' }, 'Content'),
                h('div', { className: 'field' },
                    h('label', null, labelForField),
                    h('input', {
                        type: 'text',
                        value: field.label || '',
                        onChange: (e) => {
                            const label = e.target.value;
                            if (syncLocked && !isLayoutOnly) {
                                renameFieldKey(index, makeFieldKey(label, allFields, index), { label });
                            } else {
                                updateField(index, { label }, { coalesceKey: `label-${field.id}` });
                            }
                        },
                    })),
                !isLayoutOnly
                    ? h('div', { className: 'field' },
                        h('label', null, 'Field key (payload name)'),
                        h('div', { className: 'mfb-key-row' },
                            h('input', {
                                type: 'text',
                                value: field.key || '',
                                onChange: (e) => renameFieldKey(index, e.target.value, { _syncKey: false }),
                            }),
                            h('button', {
                                type: 'button',
                                className: `mfb-sync-btn${syncLocked ? ' is-locked' : ''}`,
                                title: syncLocked ? 'Auto-syncs key with label (click to unlock manual key)' : 'Manual key (click to re-sync from label)',
                                onClick: () => {
                                    if (!syncLocked) {
                                        renameFieldKey(index, makeFieldKey(field.label, allFields, index), { _syncKey: true });
                                    } else {
                                        updateField(index, { _syncKey: false });
                                    }
                                },
                            },
                            h(FbIcon, { name: syncLocked ? 'lock' : 'unlock', size: 11 }),
                            syncLocked ? 'Auto' : 'Custom')))
                    : null,
                hasPlaceholder
                    ? h('div', { className: 'field' },
                        h('label', null, field.type === 'checkbox' || field.type === 'consent' ? 'Checkbox text' : 'Placeholder'),
                        field.type === 'consent'
                            ? h('textarea', {
                                rows: 2,
                                value: field.placeholder || '',
                                onChange: (e) => updateField(index, { placeholder: e.target.value }, { coalesceKey: `ph-${field.id}` }),
                            })
                            : h('input', {
                                type: 'text',
                                value: field.placeholder || '',
                                onChange: (e) => updateField(index, { placeholder: e.target.value }, { coalesceKey: `ph-${field.id}` }),
                            }))
                    : null,
                field.type !== 'divider' && field.type !== 'hidden'
                    ? h('div', { className: 'field' },
                        h('label', null, field.type === 'paragraph' ? 'Body copy' : 'Helper text'),
                        field.type === 'paragraph'
                            ? h('textarea', {
                                rows: 3,
                                value: field.help || '',
                                onChange: (e) => updateField(index, { help: e.target.value }, { coalesceKey: `help-${field.id}` }),
                            })
                            : h('input', {
                                type: 'text',
                                value: field.help || '',
                                placeholder: 'Optional hint below the input',
                                onChange: (e) => updateField(index, { help: e.target.value }, { coalesceKey: `help-${field.id}` }),
                            }))
                    : null),

            /* Layout & Column Span */
            !isLayoutOnly && field.type !== 'hidden'
                ? h('div', { className: 'mfb-insp-section' },
                    h('h4', { className: 'mfb-insp-title' }, 'Column width'),
                    h('div', { className: 'mfb-seg-grid' },
                        [
                            { value: 'full', label: 'Full 100%', bars: [1] },
                            { value: 'half', label: 'Half 50%', bars: [1, 0] },
                            { value: 'third', label: 'Third 33%', bars: [1, 0, 0] },
                        ].map((opt) => h('button', {
                            key: opt.value,
                            type: 'button',
                            className: `mfb-seg-btn${(field.width || 'full') === opt.value ? ' on' : ''}`,
                            onClick: () => updateField(index, { width: opt.value }),
                        },
                        h('div', { className: 'mfb-width-bars' },
                            opt.bars.map((fill, bi) => h('span', { key: bi, className: fill ? 'fill' : '' }))),
                        opt.label))))
                : null,

            /* Input-specific Rules & Options */
            !isLayoutOnly
                ? h('div', { className: 'mfb-insp-section' },
                    h('h4', { className: 'mfb-insp-title' }, 'Input rules & options'),
                    field.type === 'phone'
                        ? h('div', { className: 'field' },
                            h('label', null, 'Country format preset'),
                            h('select', {
                                value: '',
                                onChange: (e) => {
                                    const preset = PHONE_PRESETS.find((p) => p.label === e.target.value);
                                    if (preset) updateField(index, { placeholder: preset.placeholder, default_value: preset.defaultValue });
                                },
                            },
                            h('option', { value: '' }, 'Apply preset…'),
                            PHONE_PRESETS.map((p) => h('option', { key: p.label, value: p.label }, p.label))))
                        : null,
                    !['checkbox_group'].includes(field.type)
                        ? h('div', { className: 'field' },
                            h('label', null, field.type === 'hidden' ? 'Hidden payload value' : 'Default value'),
                            field.type === 'checkbox' || field.type === 'consent'
                                ? h('select', {
                                    value: field.default_value === '1' || field.default_value === 'true' ? '1' : '',
                                    onChange: (e) => updateField(index, { default_value: e.target.value }),
                                },
                                h('option', { value: '' }, 'Unchecked'),
                                h('option', { value: '1' }, 'Pre-checked'))
                                : h('input', {
                                    type: 'text',
                                    value: field.default_value || '',
                                    onChange: (e) => updateField(index, { default_value: e.target.value }, { coalesceKey: `def-${field.id}` }),
                                }))
                        : null,
                    field.type === 'textarea'
                        ? h('div', { className: 'field' },
                            h('label', null, 'Textarea rows'),
                            h('input', {
                                type: 'number',
                                min: 2,
                                max: 14,
                                value: field.rows || 4,
                                onChange: (e) => updateField(index, { rows: Number(e.target.value) || 4 }),
                            }))
                        : null,
                    field.type === 'rating'
                        ? h('div', { className: 'field' },
                            h('label', null, 'Star scale (3–10)'),
                            h('input', {
                                type: 'number',
                                min: 3,
                                max: 10,
                                value: field.max_stars || 5,
                                onChange: (e) => updateField(index, { max_stars: Math.max(3, Math.min(10, Number(e.target.value) || 5)) }),
                            }))
                        : null,
                    field.type === 'number'
                        ? h('div', { className: 'row' },
                            h('div', { className: 'field' },
                                h('label', null, 'Min value'),
                                h('input', {
                                    type: 'number',
                                    value: field.min ?? '',
                                    onChange: (e) => updateField(index, { min: e.target.value === '' ? null : Number(e.target.value) }),
                                })),
                            h('div', { className: 'field' },
                                h('label', null, 'Max value'),
                                h('input', {
                                    type: 'number',
                                    value: field.max ?? '',
                                    onChange: (e) => updateField(index, { max: e.target.value === '' ? null : Number(e.target.value) }),
                                })))
                        : null,
                    hasLengthBounds
                        ? h('div', { className: 'row' },
                            h('div', { className: 'field' },
                                h('label', null, 'Min chars'),
                                h('input', {
                                    type: 'number',
                                    min: 0,
                                    value: field.min_length ?? '',
                                    onChange: (e) => updateField(index, { min_length: e.target.value === '' ? null : Number(e.target.value) }),
                                })),
                            h('div', { className: 'field' },
                                h('label', null, 'Max chars'),
                                h('input', {
                                    type: 'number',
                                    min: 1,
                                    value: field.max_length ?? '',
                                    onChange: (e) => updateField(index, { max_length: e.target.value === '' ? null : Number(e.target.value) }),
                                })))
                        : null,
                    field.type === 'date'
                        ? h('div', { className: 'row' },
                            h('div', { className: 'field' },
                                h('label', null, 'Earliest date'),
                                h('input', {
                                    type: 'date',
                                    value: field.min_date || '',
                                    onChange: (e) => updateField(index, { min_date: e.target.value }),
                                })),
                            h('div', { className: 'field' },
                                h('label', null, 'Latest date'),
                                h('input', {
                                    type: 'date',
                                    value: field.max_date || '',
                                    onChange: (e) => updateField(index, { max_date: e.target.value }),
                                })))
                        : null,
                    hasOptions
                        ? h(OptionsEditor, {
                            options: field.options || [],
                            onChange: (opts) => updateField(index, { options: opts }),
                        })
                        : null,
                    field.type !== 'hidden'
                        ? h('div', { style: { marginTop: 8 } },
                            h(SwitchRow, {
                                label: 'Required field',
                                hint: 'Prevent submission when empty.',
                                checked: Boolean(field.required),
                                onChange: (val) => updateField(index, { required: val }),
                            }))
                        : null)
                : null,

            /* Conditional Visibility Logic */
            field.type !== 'hidden'
                ? h('div', { className: 'mfb-insp-section' },
                    h('h4', { className: 'mfb-insp-title' }, 'Conditional logic'),
                    h('div', { className: 'mfb-cond-card' },
                        h(SwitchRow, {
                            label: 'Show conditionally',
                            hint: otherConditionFields.length
                                ? 'Only show this field when a rule passes.'
                                : 'Add another input field first to target it.',
                            checked: Boolean(field.condition?.field),
                            onChange: (enabled) => {
                                if (!enabled) {
                                    updateField(index, { condition: null });
                                } else if (otherConditionFields.length > 0) {
                                    updateField(index, {
                                        condition: { field: otherConditionFields[0].key, operator: 'equals', value: '' },
                                    });
                                }
                            },
                        }),
                        field.condition?.field
                            ? h('div', { className: 'mfb-cond-grid' },
                                h('div', { className: 'field', style: { marginBottom: 0 } },
                                    h('label', null, 'Target field'),
                                    h('select', {
                                        value: field.condition?.field || '',
                                        onChange: (e) => updateField(index, {
                                            condition: e.target.value
                                                ? { ...(field.condition || { operator: 'equals', value: '' }), field: e.target.value }
                                                : null,
                                        }),
                                    },
                                    otherConditionFields.map((other) => h('option', { key: other.id, value: other.key },
                                        `${other.label} (${other.key})`)))),
                                h('div', { className: 'field', style: { marginBottom: 0 } },
                                    h('label', null, 'Condition'),
                                    h('select', {
                                        value: field.condition?.operator || 'equals',
                                        onChange: (e) => updateField(index, {
                                            condition: { ...(field.condition || {}), operator: e.target.value },
                                        }),
                                    },
                                    CONDITION_OPERATORS.map((op) => h('option', { key: op.value, value: op.value }, op.label)))),
                                !['not_empty', 'is_empty'].includes(field.condition?.operator)
                                    ? h('div', { className: 'field', style: { marginBottom: 0 } },
                                        h('label', null, 'Expected value'),
                                        h('input', {
                                            type: 'text',
                                            placeholder: 'e.g. Enterprise',
                                            value: field.condition?.value || '',
                                            onChange: (e) => updateField(index, {
                                                condition: { ...(field.condition || {}), value: e.target.value },
                                            }, { coalesceKey: `cond-val-${field.id}` }),
                                        }))
                                    : null,
                                h('div', { className: 'mfb-cond-summary' },
                                    `Visible when "${field.condition.field}" ${field.condition.operator || 'equals'}${['not_empty', 'is_empty'].includes(field.condition.operator) ? '' : ` "${field.condition.value || ''}"`}`))
                            : null))
                : null);
    }

    /** Right Inspector: Design Tab */
    function DesignInspector({ settings, onUpdate }) {
        // Follows the active theme when no colour is set (the old default purple was saved on every form).
        const followsTheme = !settings.accent_color || String(settings.accent_color).toLowerCase() === '#5b3df5';

        return h('div', null,
            h('div', { className: 'mfb-insp-section' },
                h('h4', { className: 'mfb-insp-title' }, 'Accent & brand color'),
                h('div', { className: 'mfb-swatches', style: { marginBottom: 10 } },
                    h('button', {
                        type: 'button',
                        className: `mfb-swatch-theme${followsTheme ? ' on' : ''}`,
                        title: 'Use the colours of the active site theme',
                        onClick: () => onUpdate('accent_color', ''),
                    }, 'Site theme'),
                    ACCENT_SWATCHES.map((hex) => h('button', {
                        key: hex,
                        type: 'button',
                        className: `mfb-swatch${!followsTheme && settings.accent_color === hex ? ' on' : ''}`,
                        style: { backgroundColor: hex },
                        title: hex,
                        onClick: () => onUpdate('accent_color', hex),
                    })),
                    h('input', {
                        type: 'color',
                        value: followsTheme ? '#5b3df5' : settings.accent_color,
                        onChange: (e) => onUpdate('accent_color', e.target.value),
                        style: { width: 32, height: 26, padding: 1, borderRadius: 6, cursor: 'pointer' },
                    }))),

            h('div', { className: 'mfb-insp-section' },
                h('h4', { className: 'mfb-insp-title' }, 'Container style'),
                h('div', { className: 'mfb-seg-grid' },
                    [
                        { value: 'card', label: 'Card' },
                        { value: 'bordered', label: 'Bordered' },
                        { value: 'flat', label: 'Flat' },
                    ].map((opt) => h('button', {
                        key: opt.value,
                        type: 'button',
                        className: `mfb-seg-btn${(settings.card_style || 'card') === opt.value ? ' on' : ''}`,
                        onClick: () => onUpdate('card_style', opt.value),
                    }, opt.label)))),

            h('div', { className: 'mfb-insp-section' },
                h('h4', { className: 'mfb-insp-title' }, 'Corner radius'),
                h('div', { className: 'mfb-seg-grid' },
                    [
                        { value: 'sharp', label: 'Sharp' },
                        { value: 'rounded', label: 'Rounded' },
                        { value: 'pill', label: 'Pill' },
                    ].map((opt) => h('button', {
                        key: opt.value,
                        type: 'button',
                        className: `mfb-seg-btn${(settings.border_radius || 'rounded') === opt.value ? ' on' : ''}`,
                        onClick: () => onUpdate('border_radius', opt.value),
                    }, opt.label)))),

            h('div', { className: 'mfb-insp-section' },
                h('h4', { className: 'mfb-insp-title' }, 'Field rhythm'),
                h('div', { className: 'mfb-seg-grid' },
                    [
                        { value: 'compact', label: 'Compact' },
                        { value: 'comfortable', label: 'Balanced' },
                        { value: 'spacious', label: 'Spacious' },
                    ].map((opt) => h('button', {
                        key: opt.value,
                        type: 'button',
                        className: `mfb-seg-btn${(settings.spacing || 'comfortable') === opt.value ? ' on' : ''}`,
                        onClick: () => onUpdate('spacing', opt.value),
                    }, opt.label)))),

            h('div', { className: 'mfb-insp-section' },
                h('h4', { className: 'mfb-insp-title' }, 'Submit CTA & legend'),
                h('div', { className: 'mfb-seg-grid', style: { marginBottom: 12 } },
                    [
                        { value: 'left', label: 'Align left' },
                        { value: 'full', label: 'Full width' },
                        { value: 'right', label: 'Align right' },
                    ].map((opt) => h('button', {
                        key: opt.value,
                        type: 'button',
                        className: `mfb-seg-btn${(settings.button_align || 'left') === opt.value ? ' on' : ''}`,
                        onClick: () => onUpdate('button_align', opt.value),
                    }, opt.label))),
                h(SwitchRow, {
                    label: 'Show "* required" legend',
                    hint: 'Helpful when a form mixes required and optional fields.',
                    checked: settings.show_required_note !== false,
                    onChange: (val) => onUpdate('show_required_note', val),
                })));
    }

    /** Right Inspector: Form Settings & Delivery Tab */
    function SettingsInspector({
        data,
        slugSynced,
        nameError = '',
        onNameChange,
        onSlugChange,
        onToggleSlugSync,
        onDescriptionChange,
        onStatusChange,
        onUpdateSetting,
    }) {
        const s = data.settings || {};
        return h('div', null,
            h('div', { className: 'mfb-insp-section' },
                h('h4', { className: 'mfb-insp-title' }, 'Form identity'),
                h('div', { className: 'field' },
                    h('label', null, 'Form name *'),
                    h('input', {
                        type: 'text',
                        required: true,
                        className: nameError ? 'is-error' : '',
                        value: data.name || '',
                        placeholder: 'e.g. Contact Us',
                        onChange: (e) => onNameChange(e.target.value),
                    }),
                    nameError ? h('div', { className: 'mfb-field-error' }, nameError) : null),
                h('div', { className: 'field' },
                    h('label', null, 'URL slug'),
                    h('div', { className: 'mfb-key-row' },
                        h('input', {
                            type: 'text',
                            value: data.slug || '',
                            placeholder: 'auto-generated-slug',
                            onChange: (e) => onSlugChange(e.target.value),
                        }),
                        h('button', {
                            type: 'button',
                            className: `mfb-sync-btn${slugSynced ? ' is-locked' : ''}`,
                            onClick: onToggleSlugSync,
                        },
                        h(FbIcon, { name: slugSynced ? 'lock' : 'unlock', size: 11 }),
                        slugSynced ? 'Auto' : 'Manual'))),
                h('div', { className: 'field' },
                    h('label', null, 'Status'),
                    h('select', {
                        value: data.status || 'active',
                        onChange: (e) => onStatusChange(e.target.value),
                    },
                    h('option', { value: 'active' }, 'Active (accepting submissions)'),
                    h('option', { value: 'paused' }, 'Paused (closed)'))),
                h('div', { className: 'field' },
                    h('label', null, 'Form intro / description'),
                    h('textarea', {
                        rows: 2,
                        value: data.description || '',
                        placeholder: 'Displayed above the form when embedded.',
                        onChange: (e) => onDescriptionChange(e.target.value),
                    }))),

            h('div', { className: 'mfb-insp-section' },
                h('h4', { className: 'mfb-insp-title' }, 'Submission behavior'),
                h('div', { className: 'field' },
                    h('label', null, 'Submit button text'),
                    h('input', {
                        type: 'text',
                        value: s.submit_label || '',
                        placeholder: 'Submit',
                        onChange: (e) => onUpdateSetting('submit_label', e.target.value),
                    })),
                h('div', { className: 'field' },
                    h('label', null, 'Confirmation message'),
                    h('textarea', {
                        rows: 2,
                        value: s.success_message || '',
                        onChange: (e) => onUpdateSetting('success_message', e.target.value),
                    })),
                h('div', { className: 'field' },
                    h('label', null, 'Redirect URL (optional)'),
                    h('input', {
                        type: 'url',
                        placeholder: 'https://… or /thank-you',
                        value: s.redirect_url || '',
                        onChange: (e) => onUpdateSetting('redirect_url', e.target.value),
                    }))),

            h('div', { className: 'mfb-insp-section' },
                h('h4', { className: 'mfb-insp-title' }, 'Notifications & spam guard'),
                h('div', { className: 'field' },
                    h('label', null, 'Notify email address'),
                    h('input', {
                        type: 'email',
                        placeholder: 'Leave empty for plugin default',
                        value: s.notify_email || '',
                        onChange: (e) => onUpdateSetting('notify_email', e.target.value),
                    })),
                h(SwitchRow, {
                    label: 'Store entries in inbox',
                    hint: 'Save submissions for browsing & CSV export.',
                    checked: s.store_submissions !== false,
                    onChange: (val) => onUpdateSetting('store_submissions', val),
                }),
                h(SwitchRow, {
                    label: 'Honeypot spam shield',
                    hint: 'Invisible anti-bot trap and timing check.',
                    checked: s.honeypot !== false,
                    onChange: (val) => onUpdateSetting('honeypot', val),
                })));
    }

    /** Admin: Forms Index */
    function FormsIndex({ forms, filters }) {
        const [q, setQ] = useState(filters?.q || '');

        const search = (e) => {
            e.preventDefault();
            router.get(route('plugin.modrix.form-builder.forms.index'), q ? { q } : {}, { preserveState: true });
        };

        const rows = forms.data || [];
        const totalForms = forms.total ?? rows.length;
        const activeOnPage = rows.filter((f) => f.status === 'active').length;
        const totalSubs = rows.reduce((sum, f) => sum + Number(f.submissions_count || 0), 0);
        const unreadSubs = rows.reduce((sum, f) => sum + Number(f.unread_count || 0), 0);

        const quickCards = [
            { key: 'blank', icon: 'plus', title: 'Blank Canvas', sub: 'Start dragging fields' },
            { key: 'contact', icon: 'email', title: 'Contact Us', sub: '2-column classic' },
            { key: 'lead', icon: 'branch', title: 'Lead Capture', sub: 'Conditional inquiry' },
            { key: 'callback', icon: 'phone', title: 'Callback Request', sub: 'Phone + SMS consent' },
            { key: 'feedback', icon: 'rating', title: 'CSAT Feedback', sub: 'Stars + branching' },
            { key: 'event', icon: 'date', title: 'Event RSVP', sub: 'Multi-track signup' },
        ];

        return h(ui.AdminLayout, {
            title: 'Forms',
            subtitle: 'Visual drag-and-drop form studio with conditional logic, styling, and submissions inbox.',
            actions: h(React.Fragment, null,
                h(Link, { className: 'btn btn-light', href: route('plugin.modrix.form-builder.submissions.index') },
                    h(FbIcon, { name: 'inbox', size: 14 }),
                    'Submissions inbox'),
                h(Link, { className: 'btn', href: route('plugin.modrix.form-builder.forms.create'), preserveState: false },
                    h(FbIcon, { name: 'plus', size: 14 }),
                    'Create new form')),
        },
        h('div', { className: 'mfb-kpi-row' },
            h('div', { className: 'mfb-kpi-card' },
                h('div', null,
                    h('div', { className: 'mfb-kpi-label' }, 'Total forms'),
                    h('div', { className: 'mfb-kpi-value' }, totalForms)),
                h('div', { className: 'mfb-kpi-icon is-purple' }, h(FbIcon, { name: 'forms', size: 18 }))),
            h('div', { className: 'mfb-kpi-card' },
                h('div', null,
                    h('div', { className: 'mfb-kpi-label' }, 'Active on page'),
                    h('div', { className: 'mfb-kpi-value' }, activeOnPage)),
                h('div', { className: 'mfb-kpi-icon is-green' }, h(FbIcon, { name: 'check', size: 18 }))),
            h('div', { className: 'mfb-kpi-card' },
                h('div', null,
                    h('div', { className: 'mfb-kpi-label' }, 'Captured entries'),
                    h('div', { className: 'mfb-kpi-value' }, totalSubs)),
                h('div', { className: 'mfb-kpi-icon' }, h(FbIcon, { name: 'inbox', size: 18 }))),
            h('div', { className: 'mfb-kpi-card' },
                h('div', null,
                    h('div', { className: 'mfb-kpi-label' }, 'Unread entries'),
                    h('div', { className: 'mfb-kpi-value' }, unreadSubs)),
                h('div', { className: 'mfb-kpi-icon is-amber' }, h(FbIcon, { name: 'sparkles', size: 18 })))),

        h('div', { className: 'mfb-quick-templates' },
            h('div', { className: 'mfb-quick-head' },
                h('h3', null, h(FbIcon, { name: 'template', size: 15 }), 'Start from a drag-and-drop template'),
                h('span', null, 'Opens directly in the Visual Studio')),
            h('div', { className: 'mfb-quick-cards' },
                quickCards.map((c) => h(Link, {
                    key: c.key,
                    className: 'mfb-quick-card',
                    href: `${route('plugin.modrix.form-builder.forms.create')}?template=${c.key}`,
                },
                h('span', { className: 'mfb-quick-card-ic' }, h(FbIcon, { name: c.icon, size: 14 })),
                h('strong', null, c.title),
                h('small', null, c.sub))))),

        h('div', { className: 'panel' },
            h('form', { className: 'toolbar', onSubmit: search },
                h('input', {
                    type: 'search',
                    placeholder: 'Search forms by name or slug…',
                    value: q,
                    onChange: (e) => setQ(e.target.value),
                }),
                h('button', { className: 'btn btn-light', type: 'submit' }, 'Search'),
                filters?.q ? h(Link, { className: 'btn btn-light', href: route('plugin.modrix.form-builder.forms.index') }, 'Reset') : null),
            h('div', { className: 'table-wrap' },
                h('table', null,
                    h('thead', null,
                        h('tr', null,
                            h('th', null, 'Form'),
                            h('th', null, 'Slug'),
                            h('th', null, 'Fields'),
                            h('th', null, 'Submissions'),
                            h('th', null, 'Status'),
                            h('th', null, ''))),
                    h('tbody', null, rows.length
                        ? rows.map((f) => h('tr', { key: f.id },
                            h('td', { className: 'title' },
                                h(Link, { href: route('plugin.modrix.form-builder.forms.edit', f.id) }, f.name),
                                f.description ? h('div', { className: 'sub' }, f.description) : null),
                            h('td', null, h(SlugCopyPill, { slug: f.slug })),
                            h('td', null, `${f.fields_count} fields`),
                            h('td', null,
                                h(Link, { href: route('plugin.modrix.form-builder.submissions.index', { form: f.id }) },
                                    `${f.submissions_count} total`),
                                f.unread_count > 0 ? h('span', { style: { marginLeft: 8 } }, h(ui.Badge, { tone: 'purple' }, `${f.unread_count} new`)) : null),
                            h('td', null, h(ui.Badge, { tone: f.status === 'active' ? 'green' : 'amber' }, f.status)),
                            h('td', { className: 'actions' },
                                h(Link, { className: 'btn btn-light btn-sm', href: route('plugin.modrix.form-builder.forms.edit', f.id) }, 'Open Studio'),
                                h('button', {
                                    type: 'button',
                                    className: 'btn btn-light btn-sm',
                                    onClick: () => router.post(route('plugin.modrix.form-builder.forms.toggle-status', f.id), {}, { preserveScroll: true }),
                                }, f.status === 'active' ? 'Pause' : 'Activate'),
                                h('button', {
                                    type: 'button',
                                    className: 'btn btn-light btn-sm',
                                    onClick: () => router.post(route('plugin.modrix.form-builder.forms.duplicate', f.id)),
                                }, 'Duplicate'),
                                h(ui.DangerButton, {
                                    confirm: `Delete "${f.name}" and its submissions?`,
                                    url: route('plugin.modrix.form-builder.forms.destroy', f.id),
                                }, 'Delete'))))
                        : h('tr', null, h('td', { colSpan: 6, className: 'empty' }, 'No forms yet. Pick a template above or create your first form.'))))),
            h(ui.Pager, { paginator: forms })));
    }

    /** Admin: 3-Column Drag & Drop Form Studio */
    function FormEditor({ formRecord }) {
        const exists = Boolean(formRecord?.id);
        const cloneTemplateFields = (fields) => (fields || []).map((f) => ({
            ...f,
            id: uid(),
            options: Array.isArray(f.options) ? [...f.options] : [],
            condition: f.condition ? { ...f.condition } : null,
            _syncKey: true,
        }));

        const requestedTplKey = useMemo(() => {
            if (exists || typeof window === 'undefined') return null;
            const param = new URLSearchParams(window.location.search).get('template');
            return param && STARTER_TEMPLATES[param] ? param : null;
        }, [exists]);

        const starter = requestedTplKey ? STARTER_TEMPLATES[requestedTplKey] : null;
        const initialName = formRecord?.name || (requestedTplKey && requestedTplKey !== 'blank' ? starter.name : '');
        const initialSlug = formRecord?.slug || (initialName ? ui.slugify(initialName) : '');
        const initialDescription = formRecord?.description || (starter ? starter.description : '');
        const initialFields = useMemo(() => {
            const raw = starter ? cloneTemplateFields(starter.fields) : (formRecord?.fields || []);
            return raw.map((f) => ({
                ...f,
                id: f.id || uid(),
                _syncKey: exists ? false : (f._syncKey ?? true),
            }));
        }, []);

        const form = useForm({
            name: initialName,
            slug: initialSlug,
            description: initialDescription,
            status: formRecord?.status || 'active',
            fields: initialFields,
            settings: formRecord?.settings || {
                submit_label: 'Submit',
                success_message: 'Thank you! Your submission has been received.',
                redirect_url: '',
                notify_email: '',
                store_submissions: true,
                honeypot: true,
                accent_color: '',
                card_style: 'card',
                border_radius: 'rounded',
                spacing: 'comfortable',
                button_align: 'left',
                show_required_note: true,
            },
        });

        // What counts as "saved": the form as it was when it loaded, and again after every successful save.
        // (Inertia's own isDirty also fires for forms that were only loaded, so the studio tracks it itself.)
        const snapshotOf = (d) => JSON.stringify({
            name: d.name,
            slug: d.slug,
            description: d.description,
            status: d.status,
            fields: d.fields.map(({ _syncKey, ...rest }) => rest),
            settings: d.settings,
        });
        const savedSnapshotRef = useRef(null);
        if (savedSnapshotRef.current === null) {
            savedSnapshotRef.current = snapshotOf(form.data);
        }
        const currentSnapshot = useMemo(() => snapshotOf(form.data), [form.data]);
        const hasUnsavedChanges = currentSnapshot !== savedSnapshotRef.current;
        const currentSnapshotRef = useRef(currentSnapshot);
        currentSnapshotRef.current = currentSnapshot;
        const [justSaved, setJustSaved] = useState(false);
        const [shareOpen, setShareOpen] = useState(false);

        // Leaving with unsaved changes asks first.
        useEffect(() => {
            if (!hasUnsavedChanges) return undefined;
            const warn = (e) => { e.preventDefault(); e.returnValue = ''; };
            window.addEventListener('beforeunload', warn);
            return () => window.removeEventListener('beforeunload', warn);
        }, [hasUnsavedChanges]);

        const [slugSynced, setSlugSynced] = useState(!exists && initialSlug === ui.slugify(initialName));
        const [leftTab, setLeftTab] = useState('palette'); // 'palette' | 'outline'
        const [paletteQuery, setPaletteQuery] = useState('');
        const [rightTab, setRightTab] = useState('field'); // 'field' | 'design' | 'settings'
        const [viewMode, setViewMode] = useState('canvas'); // 'canvas' | 'test'
        const [testViewport, setTestViewport] = useState('desktop'); // 'desktop' | 'tablet' | 'mobile'
        const [selectedId, setSelectedId] = useState(initialFields[0]?.id || null);
        const [templatesOpen, setTemplatesOpen] = useState(false);

        // Inline canvas label edit state
        const [editingLabelId, setEditingLabelId] = useState(null);
        const [editingLabelText, setEditingLabelText] = useState('');

        // Interactive Test Mode state
        const [previewValues, setPreviewValues] = useState(() => initialValuesFor(initialFields));
        const [previewErrors, setPreviewErrors] = useState({});
        const [previewStatus, setPreviewStatus] = useState('');

        // Drag & Drop state (ref guarantees immediate payload read + minimal re-render)
        const dragPayloadRef = useRef(null);
        const dragBlockedRef = useRef(false);
        const [dragUi, setDragUi] = useState(null); // { kind, id, index, label } | null
        const [dropIndicator, setDropIndicator] = useState(null); // { targetId, insertIndex, side } | null
        const [outlineDrop, setOutlineDrop] = useState(null); // { targetId, insertIndex, side } | null

        const canvasGridRef = useRef(null);
        const cardRefs = useRef({});
        const lastFieldsRef = useRef(form.data.fields);
        lastFieldsRef.current = form.data.fields;

        // Undo / Redo history with typing coalesce
        const historyRef = useRef({ past: [], future: [], lastCoalesceKey: null, lastCoalesceAt: 0 });
        const [, setHistoryVersion] = useState(0);
        const titleInputRef = useRef(null);
        const [nameError, setNameError] = useState('');
        const saveFormRef = useRef(null);

        const pushHistorySnapshot = useCallback((prevFields, prevSelectedId, coalesceKey = null) => {
            const now = Date.now();
            const hist = historyRef.current;
            const snapshot = {
                fields: prevFields.map((f) => ({
                    ...f,
                    options: Array.isArray(f.options) ? [...f.options] : [],
                    condition: f.condition ? { ...f.condition } : null,
                })),
                selectedId: prevSelectedId,
            };

            const isCoalesce = coalesceKey
                && hist.lastCoalesceKey === coalesceKey
                && (now - hist.lastCoalesceAt) < 700
                && hist.past.length > 0;

            if (!isCoalesce) {
                hist.past = [...hist.past.slice(-39), snapshot];
            }
            hist.future = [];
            hist.lastCoalesceKey = coalesceKey;
            hist.lastCoalesceAt = now;
            setHistoryVersion((v) => v + 1);
        }, []);

        const applyFields = useCallback((nextOrFn, { record = true, coalesceKey = null, nextSelectedId } = {}) => {
            const prevFields = lastFieldsRef.current;
            const nextFields = typeof nextOrFn === 'function' ? nextOrFn(prevFields) : nextOrFn;
            if (nextFields === prevFields) return;
            if (record) {
                pushHistorySnapshot(prevFields, selectedId, coalesceKey);
            }
            form.setData('fields', nextFields);
            if (nextSelectedId !== undefined) {
                setSelectedId(nextSelectedId);
            }
            setPreviewValues((prev) => ({ ...initialValuesFor(nextFields), ...prev }));
        }, [form, selectedId, pushHistorySnapshot]);

        const undo = useCallback(() => {
            const hist = historyRef.current;
            if (!hist.past.length) return;
            const target = hist.past[hist.past.length - 1];
            hist.past = hist.past.slice(0, -1);
            hist.future = [{
                fields: lastFieldsRef.current,
                selectedId,
            }, ...hist.future.slice(0, 39)];
            hist.lastCoalesceKey = null;
            form.setData('fields', target.fields);
            setSelectedId(target.selectedId || target.fields[0]?.id || null);
            setPreviewValues((prev) => ({ ...initialValuesFor(target.fields), ...prev }));
            setHistoryVersion((v) => v + 1);
        }, [form, selectedId]);

        const redo = useCallback(() => {
            const hist = historyRef.current;
            if (!hist.future.length) return;
            const target = hist.future[0];
            hist.future = hist.future.slice(1);
            hist.past = [...hist.past.slice(-39), {
                fields: lastFieldsRef.current,
                selectedId,
            }];
            hist.lastCoalesceKey = null;
            form.setData('fields', target.fields);
            setSelectedId(target.selectedId || target.fields[0]?.id || null);
            setPreviewValues((prev) => ({ ...initialValuesFor(target.fields), ...prev }));
            setHistoryVersion((v) => v + 1);
        }, [form, selectedId]);

        useEffect(() => {
            const onKeyDown = (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                    e.preventDefault();
                    saveFormRef.current?.();
                    return;
                }
                if (e.key === 'Escape') {
                    if (templatesOpen) setTemplatesOpen(false);
                    if (editingLabelId) setEditingLabelId(null);
                    return;
                }
                const targetEl = e.target;
                const inInput = targetEl && targetEl.closest && targetEl.closest('input, textarea, select, [contenteditable]');
                if (inInput) return;

                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
                    e.preventDefault();
                    if (e.shiftKey) redo();
                    else undo();
                } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'y') {
                    e.preventDefault();
                    redo();
                }
            };
            window.addEventListener('keydown', onKeyDown);
            return () => window.removeEventListener('keydown', onKeyDown);
        }, [undo, redo, templatesOpen, editingLabelId]);

        const selectedIndex = useMemo(
            () => form.data.fields.findIndex((f) => f.id === selectedId),
            [form.data.fields, selectedId],
        );
        const selectedField = selectedIndex >= 0 ? form.data.fields[selectedIndex] : null;

        const conditionalCount = useMemo(
            () => form.data.fields.filter((f) => Boolean(f.condition?.field)).length,
            [form.data.fields],
        );

        const filteredGroups = useMemo(() => {
            const term = paletteQuery.trim().toLowerCase();
            if (!term) return FIELD_GROUPS;
            return FIELD_GROUPS
                .map((g) => ({
                    ...g,
                    items: g.items.filter((it) => it.label.toLowerCase().includes(term) || it.type.toLowerCase().includes(term)),
                }))
                .filter((g) => g.items.length > 0);
        }, [paletteQuery]);

        // Field mutations
        const selectAndFocusField = (id, { scrollToCard = false } = {}) => {
            setSelectedId(id);
            setRightTab('field');
            if (scrollToCard && cardRefs.current[id]) {
                cardRefs.current[id].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        };

        const insertNewFieldAt = (type, atIndex = null) => {
            const fields = lastFieldsRef.current;
            const created = buildNewField(type, fields);
            const idx = atIndex === null
                ? (selectedIndex >= 0 ? selectedIndex + 1 : fields.length)
                : Math.max(0, Math.min(atIndex, fields.length));
            const next = [...fields.slice(0, idx), created, ...fields.slice(idx)];
            applyFields(next, { nextSelectedId: created.id });
            setViewMode('canvas');
            setRightTab('field');
            setTimeout(() => {
                if (cardRefs.current[created.id]) {
                    cardRefs.current[created.id].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }, 30);
        };

        const updateField = (index, patch, opts = {}) => {
            applyFields(
                (prev) => prev.map((f, i) => (i === index ? { ...f, ...patch } : f)),
                opts,
            );
        };

        const renameFieldKey = (index, rawKey, extraPatch = {}) => {
            const prevFields = lastFieldsRef.current;
            const target = prevFields[index];
            if (!target) return;
            const oldKey = target.key;
            let cleaned = String(rawKey || '')
                .toLowerCase()
                .replace(/[^a-z0-9_]+/g, '_')
                .replace(/^_+|_+$/g, '');
            if (/^[0-9]/.test(cleaned)) cleaned = `f_${cleaned}`;
            cleaned = cleaned || `field_${index + 1}`;

            // Keep key unique across sibling fields
            const others = new Set(prevFields.filter((_, i) => i !== index).map((f) => f.key));
            let uniqueKey = cleaned;
            let suffix = 2;
            while (others.has(uniqueKey)) {
                uniqueKey = `${cleaned}_${suffix}`;
                suffix += 1;
            }

            const next = prevFields.map((f, i) => {
                if (i === index) {
                    return { ...f, ...extraPatch, key: uniqueKey };
                }
                if (f.condition?.field && f.condition.field === oldKey) {
                    return { ...f, condition: { ...f.condition, field: uniqueKey } };
                }
                return f;
            });
            applyFields(next, { coalesceKey: `key-${target.id}` });
        };

        const duplicateField = (index) => {
            const fields = lastFieldsRef.current;
            const src = fields[index];
            if (!src) return;
            const clone = {
                ...src,
                id: uid(),
                key: makeFieldKey(`${src.label} copy`, fields),
                label: `${src.label} (copy)`,
                options: Array.isArray(src.options) ? [...src.options] : [],
                condition: src.condition ? { ...src.condition } : null,
                _syncKey: true,
            };
            const next = [...fields.slice(0, index + 1), clone, ...fields.slice(index + 1)];
            applyFields(next, { nextSelectedId: clone.id });
            setRightTab('field');
        };

        const removeField = (index) => {
            const fields = lastFieldsRef.current;
            const removed = fields[index];
            if (!removed) return;
            const next = fields.filter((_, i) => i !== index);
            const fallbackId = next[Math.min(index, next.length - 1)]?.id || null;
            applyFields(next, {
                nextSelectedId: selectedId === removed.id ? fallbackId : selectedId,
            });
            if (editingLabelId === removed.id) setEditingLabelId(null);
        };

        const moveField = (fromIndex, insertIndex) => {
            const fields = lastFieldsRef.current;
            if (fromIndex < 0 || fromIndex >= fields.length) return;
            if (insertIndex === fromIndex || insertIndex === fromIndex + 1) return;
            const copy = [...fields];
            const [item] = copy.splice(fromIndex, 1);
            const adjusted = fromIndex < insertIndex ? insertIndex - 1 : insertIndex;
            const safeIdx = Math.max(0, Math.min(adjusted, copy.length));
            copy.splice(safeIdx, 0, item);
            applyFields(copy, { nextSelectedId: item.id });
        };

        const applyTemplate = (tplKey, tpl) => {
            const fresh = cloneTemplateFields(tpl.fields);
            pushHistorySnapshot(lastFieldsRef.current, selectedId);
            if (tplKey !== 'blank' || (form.data.name || '').trim()) {
                setNameError('');
            }
            form.setData((prev) => ({
                ...prev,
                name: prev.name || (tplKey === 'blank' ? prev.name : tpl.name),
                slug: prev.slug || (tplKey === 'blank' ? prev.slug : ui.slugify(tpl.name)),
                description: prev.description || (tplKey === 'blank' ? '' : tpl.description),
                fields: fresh,
            }));
            setSelectedId(fresh[0]?.id || null);
            setPreviewValues(initialValuesFor(fresh));
            setViewMode('canvas');
            setTemplatesOpen(false);
        };

        // Drag & Drop Coordination
        const cleanupDrag = useCallback(() => {
            dragPayloadRef.current = null;
            dragBlockedRef.current = false;
            setDragUi(null);
            setDropIndicator(null);
            setOutlineDrop(null);
        }, []);

        const readDragPayload = (e) => {
            if (dragPayloadRef.current) return dragPayloadRef.current;
            try {
                const raw = e?.dataTransfer?.getData('application/x-modrix-fb');
                if (raw) return JSON.parse(raw);
                const plain = e?.dataTransfer?.getData('text/plain');
                if (plain) {
                    if (plain.startsWith('palette:')) {
                        const type = plain.slice(8);
                        return { kind: 'palette', type, label: type };
                    }
                    const foundIdx = lastFieldsRef.current.findIndex((f) => f.id === plain);
                    if (foundIdx >= 0) {
                        return { kind: 'field', id: plain, index: foundIdx, label: lastFieldsRef.current[foundIdx].label };
                    }
                }
            } catch (_) {
                // ignore malformed external drops
            }
            return null;
        };

        const beginDrag = (e, payload) => {
            dragPayloadRef.current = payload;
            setDragUi(payload);
            if (e?.dataTransfer) {
                e.dataTransfer.effectAllowed = payload.kind === 'palette' ? 'copy' : 'move';
                try {
                    e.dataTransfer.setData('application/x-modrix-fb', JSON.stringify(payload));
                    e.dataTransfer.setData('text/plain', payload.kind === 'palette' ? `palette:${payload.type}` : payload.id);
                } catch (_) {
                    // ignore restricted dataTransfer in synthetic events
                }
            }
        };

        const handleCanvasCardDragOver = (e, idx, field) => {
            const payload = readDragPayload(e);
            if (!payload) return;
            e.preventDefault();
            e.stopPropagation();
            if (e.dataTransfer) {
                e.dataTransfer.dropEffect = payload.kind === 'palette' ? 'copy' : 'move';
            }

            const rect = e.currentTarget.getBoundingClientRect();
            const gridRect = canvasGridRef.current?.getBoundingClientRect();
            const isZeroCoords = e.clientX === 0 && e.clientY === 0;
            const isSideBySide = (field.width === 'half' || field.width === 'third')
                && gridRect
                && rect.width < gridRect.width * 0.78;

            let side;
            let placeAfter;

            if (isZeroCoords) {
                if (payload.kind === 'field' && payload.index > idx) {
                    side = isSideBySide ? 'left' : 'top';
                    placeAfter = false;
                } else {
                    side = isSideBySide ? 'right' : 'bottom';
                    placeAfter = true;
                }
            } else if (isSideBySide) {
                placeAfter = e.clientX >= (rect.left + rect.width / 2);
                side = placeAfter ? 'right' : 'left';
            } else {
                placeAfter = e.clientY >= (rect.top + rect.height / 2);
                side = placeAfter ? 'bottom' : 'top';
            }

            const insertIndex = placeAfter ? idx + 1 : idx;

            if (payload.kind === 'field') {
                const fromIdx = lastFieldsRef.current.findIndex((f) => f.id === payload.id);
                if (fromIdx >= 0 && (insertIndex === fromIdx || insertIndex === fromIdx + 1)) {
                    if (dropIndicator !== null) setDropIndicator(null);
                    return;
                }
            }

            if (
                dropIndicator
                && dropIndicator.targetId === field.id
                && dropIndicator.insertIndex === insertIndex
                && dropIndicator.side === side
            ) {
                return;
            }
            setDropIndicator({ targetId: field.id, insertIndex, side });
        };

        const handleCanvasDropAt = (e, explicitIndex = null) => {
            e.preventDefault();
            e.stopPropagation();
            const payload = readDragPayload(e);
            if (!payload) {
                cleanupDrag();
                return;
            }
            const targetIndex = explicitIndex !== null
                ? explicitIndex
                : (dropIndicator ? dropIndicator.insertIndex : lastFieldsRef.current.length);

            if (payload.kind === 'palette') {
                insertNewFieldAt(payload.type, targetIndex);
            } else if (payload.kind === 'field') {
                const fromIdx = lastFieldsRef.current.findIndex((f) => f.id === payload.id);
                if (fromIdx >= 0) {
                    moveField(fromIdx, targetIndex);
                }
            }
            cleanupDrag();
        };

        // Inline Label Editing Handlers
        const startInlineEdit = (field) => {
            setEditingLabelId(field.id);
            setEditingLabelText(field.label || '');
        };

        const commitInlineEdit = (index, field) => {
            const trimmed = editingLabelText.trim();
            setEditingLabelId(null);
            if (!trimmed || trimmed === field.label) return;
            if (field._syncKey !== false && !NON_INPUT_TYPES.includes(field.type)) {
                renameFieldKey(index, makeFieldKey(trimmed, lastFieldsRef.current, index), { label: trimmed });
            } else {
                updateField(index, { label: trimmed });
            }
        };

        const handleFormNameChange = (name) => {
            if (nameError && name.trim()) {
                setNameError('');
            }
            if (form.errors?.name && name.trim()) {
                form.clearErrors?.('name');
            }
            form.setData((d) => ({ ...d, name, slug: slugSynced ? ui.slugify(name) : d.slug }));
        };

        const activeNameError = nameError || form.errors?.name || '';
        const displayErrors = nameError ? { ...form.errors, name: nameError } : form.errors;

        const saveForm = (e) => {
            e?.preventDefault();
            if (!(form.data.name || '').trim()) {
                setNameError('Enter a form name here before saving (e.g. Contact Us).');
                window.scrollTo({ top: 0, behavior: 'smooth' });
                setTimeout(() => {
                    titleInputRef.current?.focus();
                    titleInputRef.current?.select();
                }, 20);
                return;
            }
            form.transform((data) => ({
                ...data,
                name: (data.name || '').trim(),
                fields: JSON.stringify(data.fields.map(({ _syncKey, ...rest }) => rest)),
                settings: JSON.stringify(data.settings),
            }));
            const options = {
                preserveScroll: true,
                onSuccess: () => {
                    setNameError('');
                    savedSnapshotRef.current = currentSnapshotRef.current;
                    setJustSaved(true);
                    setTimeout(() => setJustSaved(false), 3000);
                },
                onError: (errs) => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    if (errs?.name) {
                        setTimeout(() => titleInputRef.current?.focus(), 20);
                    }
                },
            };
            exists
                ? form.put(route('plugin.modrix.form-builder.forms.update', formRecord.id), options)
                : form.post(route('plugin.modrix.form-builder.forms.store'), options);
        };
        saveFormRef.current = saveForm;

        const saveLabel = form.processing
            ? 'Saving…'
            : (exists && hasUnsavedChanges ? 'Save changes' : 'Save form');

        const saveTitle = hasUnsavedChanges
            ? 'You have unsaved changes (Ctrl+S). Click to save.'
            : (exists ? 'All changes saved (Ctrl+S).' : 'Save this form and open it in the editor (Ctrl+S).');

        const renderSaveButton = (extraClass = '') => h('button', {
            type: 'button',
            className: `btn mfb-save-btn${hasUnsavedChanges ? ' has-changes' : ''}${extraClass ? ` ${extraClass}` : ''}`,
            disabled: form.processing,
            onClick: saveForm,
            title: saveTitle,
        },
        h(FbIcon, { name: exists && !hasUnsavedChanges && !form.processing ? 'check' : 'save', size: 14 }),
        saveLabel);

        const hist = historyRef.current;
        const canUndo = hist.past.length > 0;
        const canRedo = hist.future.length > 0;
        const previewShellProps = shellClassesAndStyle(form.data.settings);

        return h(ui.AdminLayout, {
            title: exists ? `Studio: ${form.data.name || formRecord.name}` : 'New Form Studio',
            subtitle: 'Drag fields from the palette directly onto the layout grid, edit inline, configure logic, and test responsively.',
            actions: h(React.Fragment, null,
                h(Link, { className: 'btn btn-light', href: route('plugin.modrix.form-builder.forms.index') }, '← All forms'),
                exists
                    ? h(Link, { className: 'btn btn-light', href: route('plugin.modrix.form-builder.submissions.index', { form: formRecord.id }) },
                        h(FbIcon, { name: 'inbox', size: 14 }),
                        'Submissions')
                    : null,
                h('button', { type: 'button', className: 'btn btn-light', onClick: () => setTemplatesOpen(true) },
                    h(FbIcon, { name: 'template', size: 14 }),
                    'Templates'),
                h(Link, {
                    className: 'btn',
                    href: route('plugin.modrix.form-builder.forms.create'),
                    preserveState: false,
                    title: 'Open a fresh blank form in Form Studio',
                },
                h(FbIcon, { name: 'plus', size: 14 }),
                'Create new form')),
        },
        h(ui.ErrorFlash, { errors: displayErrors }),

        /* Sticky Studio Control Bar */
        h('div', { className: 'mfb-studio-bar' },
            h('div', { className: 'mfb-studio-identity' },
                h('div', { className: 'mfb-title-group' },
                    h('div', { className: `mfb-title-field${activeNameError ? ' is-error' : ''}` },
                        h('label', { className: 'mfb-title-label', htmlFor: 'mfb-form-name' },
                            'Form name ',
                            h('span', { 'aria-hidden': 'true' }, '*')),
                        h('input', {
                            id: 'mfb-form-name',
                            ref: titleInputRef,
                            type: 'text',
                            className: 'mfb-title-input',
                            'aria-label': 'Form name',
                            'aria-invalid': Boolean(activeNameError),
                            placeholder: 'e.g. Contact Us…',
                            value: form.data.name,
                            onChange: (e) => handleFormNameChange(e.target.value),
                        })),
                    activeNameError
                        ? h('span', { className: 'mfb-title-error', role: 'alert' }, activeNameError)
                        : null),
                h(SlugCopyPill, { slug: form.data.slug }),
                h('button', {
                    type: 'button',
                    className: `mfb-status-pill${form.data.status === 'active' ? ' is-active' : ''}`,
                    title: 'Click to toggle active / paused',
                    onClick: () => form.setData('status', form.data.status === 'active' ? 'paused' : 'active'),
                },
                h('span', { className: 'dot' }),
                form.data.status === 'active' ? 'Active' : 'Paused'),
                h('button', {
                    type: 'button',
                    className: 'mfb-share-btn',
                    title: 'Form link and embed code',
                    onClick: () => setShareOpen(true),
                },
                h(FbIcon, { name: 'share', size: 13 }),
                'Share & embed')),

            h('div', { className: 'mfb-studio-center' },
                h('div', { className: 'mfb-segmented', role: 'tablist', 'aria-label': 'Workspace mode' },
                    h('button', {
                        type: 'button',
                        role: 'tab',
                        'aria-selected': viewMode === 'canvas',
                        className: viewMode === 'canvas' ? 'on' : '',
                        onClick: () => setViewMode('canvas'),
                    },
                    h(FbIcon, { name: 'layers', size: 13 }),
                    'Canvas'),
                    h('button', {
                        type: 'button',
                        role: 'tab',
                        'aria-selected': viewMode === 'test',
                        className: viewMode === 'test' ? 'on' : '',
                        onClick: () => {
                            setPreviewErrors({});
                            setPreviewStatus('');
                            setViewMode('test');
                        },
                    },
                    h(FbIcon, { name: 'eye', size: 13 }),
                    'Test form')),
                viewMode === 'test'
                    ? h('div', { className: 'mfb-segmented', 'aria-label': 'Preview viewport size' },
                        [
                            { id: 'desktop', icon: 'desktop', title: 'Desktop view' },
                            { id: 'tablet', icon: 'tablet', title: 'Tablet view' },
                            { id: 'mobile', icon: 'mobile', title: 'Mobile view' },
                        ].map((vp) => h('button', {
                            key: vp.id,
                            type: 'button',
                            className: testViewport === vp.id ? 'on' : '',
                            title: vp.title,
                            onClick: () => setTestViewport(vp.id),
                        }, h(FbIcon, { name: vp.icon, size: 13 }))))
                    : null),

            h('div', { className: 'mfb-studio-tools' },
                h('button', {
                    type: 'button',
                    className: 'mfb-icon-btn',
                    title: 'Undo (Ctrl+Z)',
                    disabled: !canUndo,
                    onClick: undo,
                }, h(FbIcon, { name: 'undo', size: 14 })),
                h('button', {
                    type: 'button',
                    className: 'mfb-icon-btn',
                    title: 'Redo (Ctrl+Shift+Z)',
                    disabled: !canRedo,
                    onClick: redo,
                }, h(FbIcon, { name: 'redo', size: 14 })),
                hasUnsavedChanges
                    ? h('span', { className: 'mfb-dirty-badge', title: 'Unsaved edits' },
                        h('span', { className: 'mfb-dirty-dot' }),
                        'Unsaved')
                    : (justSaved ? h('span', { className: 'mfb-saved-badge' }, h(FbIcon, { name: 'check', size: 12 }), 'Saved') : null),
                renderSaveButton('mfb-studio-save-btn'))),

        /* 3-Column Visual Drag-and-Drop Studio */
        h('div', { className: 'mfb-studio-grid' },

            /* Left Pane: Field Palette + Outline */
            h('aside', { className: 'mfb-pane mfb-palette-pane' },
                h('div', { className: 'mfb-pane-head' },
                    h('div', { className: 'mfb-pane-tabs' },
                        h('button', {
                            type: 'button',
                            className: leftTab === 'palette' ? 'on' : '',
                            onClick: () => setLeftTab('palette'),
                        },
                        h(FbIcon, { name: 'plus', size: 12 }),
                        'Add fields'),
                        h('button', {
                            type: 'button',
                            className: leftTab === 'outline' ? 'on' : '',
                            onClick: () => setLeftTab('outline'),
                        },
                        h(FbIcon, { name: 'layers', size: 12 }),
                        `Outline (${form.data.fields.length})`)),
                    leftTab === 'palette'
                        ? h('div', { className: 'mfb-search-box' },
                            h(FbIcon, { name: 'search', size: 13 }),
                            h('input', {
                                type: 'text',
                                placeholder: 'Search fields…',
                                'aria-label': 'Search field types',
                                value: paletteQuery,
                                onChange: (e) => setPaletteQuery(e.target.value),
                            }),
                            paletteQuery
                                ? h('button', {
                                    type: 'button',
                                    className: 'mfb-search-clear',
                                    'aria-label': 'Clear search',
                                    onClick: () => setPaletteQuery(''),
                                }, h(FbIcon, { name: 'x', size: 12 }))
                                : null)
                        : null),

                h('div', { className: 'mfb-pane-scroll' },
                    leftTab === 'palette'
                        ? (filteredGroups.length
                            ? filteredGroups.map((group) => h('div', { key: group.name, className: 'mfb-palette-group' },
                                h('h5', { className: 'mfb-palette-group-title' },
                                    group.name,
                                    h('span', null, group.items.length)),
                                h('div', { className: 'mfb-palette-tiles' },
                                    group.items.map((item) => h('div', {
                                        key: item.type,
                                        role: 'button',
                                        tabIndex: 0,
                                        draggable: true,
                                        className: `mfb-palette-tile${dragUi?.kind === 'palette' && dragUi?.type === item.type ? ' is-dragging' : ''}`,
                                        'data-palette-type': item.type,
                                        title: `Drag onto canvas or click to add ${item.label}`,
                                        onDragStart: (e) => beginDrag(e, { kind: 'palette', type: item.type, label: item.label }),
                                        onDragEnd: cleanupDrag,
                                        onClick: () => insertNewFieldAt(item.type),
                                        onKeyDown: (e) => {
                                            if (e.key === 'Enter' || e.key === ' ') {
                                                e.preventDefault();
                                                insertNewFieldAt(item.type);
                                            }
                                        },
                                    },
                                    h('div', { className: 'mfb-palette-tile-top' },
                                        h('span', { className: `mfb-palette-ic cat-${TYPE_CATEGORY[item.type] || 'basic'}` },
                                            h(FbIcon, { name: item.type, size: 13 })),
                                        h('span', { className: 'mfb-palette-add', title: `Insert ${item.label}` },
                                            h(FbIcon, { name: 'plus', size: 12 }))),
                                    h('span', { className: 'mfb-palette-tile-name' }, item.label))))))
                            : h('div', { style: { fontSize: 12.5, color: 'var(--muted, #6b7280)', textAlign: 'center', padding: 20 } },
                                'No field types match your search.'))
                        : (form.data.fields.length
                            ? h('div', { className: 'mfb-outline-list' },
                                form.data.fields.map((field, idx) => {
                                    const isDropTop = outlineDrop?.targetId === field.id && outlineDrop?.side === 'top';
                                    const isDropBot = outlineDrop?.targetId === field.id && outlineDrop?.side === 'bottom';
                                    return h('div', {
                                        key: field.id,
                                        draggable: true,
                                        className: [
                                            'mfb-outline-item',
                                            field.id === selectedId ? 'is-selected' : '',
                                            dragUi?.kind === 'field' && dragUi?.id === field.id ? 'is-dragging' : '',
                                            isDropTop ? 'is-drop-top' : '',
                                            isDropBot ? 'is-drop-bottom' : '',
                                        ].filter(Boolean).join(' '),
                                        onMouseDownCapture: (e) => {
                                            dragBlockedRef.current = Boolean(e.target.closest('button'));
                                        },
                                        onDragStart: (e) => {
                                            if (dragBlockedRef.current) {
                                                e.preventDefault();
                                                return;
                                            }
                                            beginDrag(e, { kind: 'field', id: field.id, index: idx, label: field.label });
                                        },
                                        onDragOver: (e) => {
                                            const payload = readDragPayload(e);
                                            if (!payload) return;
                                            e.preventDefault();
                                            const rect = e.currentTarget.getBoundingClientRect();
                                            const after = e.clientY >= (rect.top + rect.height / 2);
                                            const insertIndex = after ? idx + 1 : idx;
                                            const side = after ? 'bottom' : 'top';
                                            if (payload.kind === 'field') {
                                                const fromIdx = lastFieldsRef.current.findIndex((f) => f.id === payload.id);
                                                if (fromIdx >= 0 && (insertIndex === fromIdx || insertIndex === fromIdx + 1)) {
                                                    if (outlineDrop) setOutlineDrop(null);
                                                    return;
                                                }
                                            }
                                            setOutlineDrop({ targetId: field.id, insertIndex, side });
                                        },
                                        onDrop: (e) => {
                                            e.preventDefault();
                                            const payload = readDragPayload(e);
                                            const insertAt = outlineDrop?.insertIndex ?? (idx + 1);
                                            if (payload?.kind === 'palette') {
                                                insertNewFieldAt(payload.type, insertAt);
                                            } else if (payload?.kind === 'field') {
                                                const fromIdx = lastFieldsRef.current.findIndex((f) => f.id === payload.id);
                                                if (fromIdx >= 0) moveField(fromIdx, insertAt);
                                            }
                                            cleanupDrag();
                                        },
                                        onDragEnd: cleanupDrag,
                                        onClick: () => selectAndFocusField(field.id, { scrollToCard: true }),
                                    },
                                    h('span', { className: 'mfb-outline-grip' }, h(FbIcon, { name: 'grip', size: 12 })),
                                    h('span', { className: `mfb-palette-ic cat-${TYPE_CATEGORY[field.type] || 'basic'}`, style: { width: 22, height: 22 } },
                                        h(FbIcon, { name: field.type, size: 12 })),
                                    h('div', { className: 'mfb-outline-meta' },
                                        h('strong', null, field.label || field.type, field.required ? ' *' : ''),
                                        h('small', null, NON_INPUT_TYPES.includes(field.type) ? field.type : `${field.key} · ${field.width || 'full'}`)),
                                    h('div', { className: 'mfb-outline-actions' },
                                        h('button', {
                                            type: 'button',
                                            disabled: idx === 0,
                                            title: 'Move up',
                                            onClick: (e) => { e.stopPropagation(); moveField(idx, idx - 1); },
                                        }, h(FbIcon, { name: 'up', size: 12 })),
                                        h('button', {
                                            type: 'button',
                                            disabled: idx >= form.data.fields.length - 1,
                                            title: 'Move down',
                                            onClick: (e) => { e.stopPropagation(); moveField(idx, idx + 2); },
                                        }, h(FbIcon, { name: 'down', size: 12 }))));
                                }))
                            : h('div', { style: { fontSize: 12.5, color: 'var(--muted, #6b7280)', textAlign: 'center', padding: 20 } },
                                'No fields on canvas yet.')))),

            /* Center Stage: Interactive WYSIWYG Canvas OR Test Mode */
            h('section', {
                className: 'mfb-canvas-stage',
                onDragLeave: (e) => {
                    if (!e.currentTarget.contains(e.relatedTarget)) {
                        setDropIndicator(null);
                    }
                },
            },
            h('div', { className: 'mfb-canvas-hint-bar' },
                viewMode === 'canvas'
                    ? h('span', null,
                        h('strong', null, 'Drag & Drop Canvas: '),
                        'Drop new fields anywhere on the grid, drag cards to reorder, or double-click labels to rename.')
                    : h('span', null,
                        h('strong', null, 'Interactive Test: '),
                        'Verify required validation, ratings, and conditional field visibility across breakpoints.'),
                h('span', null,
                    `${form.data.fields.length} field${form.data.fields.length === 1 ? '' : 's'}`,
                    conditionalCount > 0 ? ` · ${conditionalCount} conditional` : '')),

            viewMode === 'canvas'
                ? h('div', {
                    className: `mfb-canvas-shell ${previewShellProps.className}`,
                    style: previewShellProps.style,
                },
                (form.data.name || form.data.description)
                    ? h('div', { className: 'mfb-canvas-header-preview' },
                        form.data.name ? h('h2', null, form.data.name) : null,
                        form.data.description ? h('p', null, form.data.description) : null)
                    : null,

                h('div', {
                    ref: canvasGridRef,
                    className: 'mfb-canvas-grid',
                },
                form.data.fields.length === 0
                    ? h('div', {
                        className: `mfb-empty-canvas${dropIndicator?.targetId === 'empty' ? ' is-over' : ''}`,
                        onDragOver: (e) => {
                            if (!readDragPayload(e)) return;
                            e.preventDefault();
                            setDropIndicator({ targetId: 'empty', insertIndex: 0, side: 'bottom' });
                        },
                        onDrop: (e) => handleCanvasDropAt(e, 0),
                    },
                    h('span', { className: 'mfb-palette-ic cat-choice', style: { width: 42, height: 42 } },
                        h(FbIcon, { name: 'sparkles', size: 20 })),
                    h('div', { style: { fontWeight: 700, fontSize: 15, color: 'var(--ink, #111827)' } },
                        'Drop your first field here'),
                    h('div', { style: { fontSize: 13, color: 'var(--muted, #6b7280)', maxWidth: 340 } },
                        'Drag any field tile from the left sidebar onto this canvas, or start quickly from a ready blueprint.'),
                    h('div', { style: { display: 'flex', gap: 8, marginTop: 4 } },
                        h('button', { type: 'button', className: 'btn btn-light btn-sm', onClick: () => insertNewFieldAt('text', 0) }, '+ Add Text field'),
                        h('button', { type: 'button', className: 'btn btn-sm', onClick: () => setTemplatesOpen(true) }, 'Browse templates')))
                    : form.data.fields.map((field, idx) => {
                        const isSelected = field.id === selectedId;
                        const isBeingDragged = dragUi?.kind === 'field' && dragUi?.id === field.id;
                        const isLayoutOnly = NON_INPUT_TYPES.includes(field.type);
                        const isDropTarget = dropIndicator?.targetId === field.id;
                        const dropSideClass = isDropTarget ? `is-drop-${dropIndicator.side}` : '';
                        const widthCls = field.width === 'half' ? 'is-half' : field.width === 'third' ? 'is-third' : '';
                        const dropActionText = dragUi?.kind === 'palette'
                            ? `Insert ${dragUi.label}`
                            : 'Move here';

                        return h('div', {
                            key: field.id,
                            ref: (el) => { cardRefs.current[field.id] = el; },
                            draggable: editingLabelId !== field.id,
                            'data-field-id': field.id,
                            'data-field-index': idx,
                            className: [
                                'mfb-canvas-field',
                                widthCls,
                                isSelected ? 'is-selected' : '',
                                isBeingDragged ? 'is-dragging' : '',
                                dropSideClass,
                            ].filter(Boolean).join(' '),
                            onClick: () => selectAndFocusField(field.id),
                            onMouseDownCapture: (e) => {
                                dragBlockedRef.current = Boolean(
                                    e.target.closest('input, textarea, select, button, [data-no-drag]'),
                                );
                            },
                            onDragStart: (e) => {
                                if (dragBlockedRef.current || editingLabelId === field.id) {
                                    e.preventDefault();
                                    return;
                                }
                                beginDrag(e, { kind: 'field', id: field.id, index: idx, label: field.label });
                            },
                            onDragOver: (e) => handleCanvasCardDragOver(e, idx, field),
                            onDrop: (e) => handleCanvasDropAt(e),
                            onDragEnd: cleanupDrag,
                        },
                        isDropTarget
                            ? h('span', { className: `mfb-drop-pill pos-${dropIndicator.side}` }, dropActionText)
                            : null,

                        /* Card Header Chrome + Floating Actions */
                        h('div', { className: 'mfb-canvas-field-bar' },
                            h('div', { className: 'mfb-canvas-field-left' },
                                h('span', { className: 'mfb-drag-grip', title: 'Drag to reorder field' },
                                    h(FbIcon, { name: 'grip', size: 12 })),
                                h('span', { className: 'mfb-type-chip' },
                                    h(FbIcon, { name: field.type, size: 11 }),
                                    FIELD_CATALOG.find((c) => c.type === field.type)?.label || field.type),
                                !isLayoutOnly && field.key
                                    ? h('span', { className: 'mfb-key-badge', title: `Payload key: ${field.key}` }, field.key)
                                    : null,
                                field.condition?.field
                                    ? h('span', { className: 'mfb-cond-chip', title: 'Conditional visibility active' },
                                        h(FbIcon, { name: 'branch', size: 10 }),
                                        `If ${field.condition.field}`)
                                    : null),

                            h('div', { className: 'mfb-field-toolbar', 'data-no-drag': '1', onClick: (e) => e.stopPropagation() },
                                !isLayoutOnly && field.type !== 'hidden'
                                    ? h('div', { className: 'mfb-width-pills', role: 'group', 'aria-label': 'Field column width' },
                                        [
                                            { val: 'full', text: '1/1' },
                                            { val: 'half', text: '1/2' },
                                            { val: 'third', text: '1/3' },
                                        ].map((w) => h('button', {
                                            key: w.val,
                                            type: 'button',
                                            className: (field.width || 'full') === w.val ? 'on' : '',
                                            title: `${w.text} column width`,
                                            onClick: () => updateField(idx, { width: w.val }),
                                        }, w.text)))
                                    : null,
                                !isLayoutOnly && field.type !== 'hidden'
                                    ? h('button', {
                                        type: 'button',
                                        className: `mfb-mini-act is-req${field.required ? ' on' : ''}`,
                                        title: field.required ? 'Required (click to make optional)' : 'Optional (click to require)',
                                        onClick: () => updateField(idx, { required: !field.required }),
                                    }, '*')
                                    : null,
                                h('button', {
                                    type: 'button',
                                    className: 'mfb-mini-act',
                                    title: 'Move earlier',
                                    disabled: idx === 0,
                                    onClick: () => moveField(idx, idx - 1),
                                }, h(FbIcon, { name: 'up', size: 12 })),
                                h('button', {
                                    type: 'button',
                                    className: 'mfb-mini-act',
                                    title: 'Move later',
                                    disabled: idx >= form.data.fields.length - 1,
                                    onClick: () => moveField(idx, idx + 2),
                                }, h(FbIcon, { name: 'down', size: 12 })),
                                h('button', {
                                    type: 'button',
                                    className: 'mfb-mini-act',
                                    title: 'Duplicate field',
                                    onClick: () => duplicateField(idx),
                                }, h(FbIcon, { name: 'copy', size: 12 })),
                                h('button', {
                                    type: 'button',
                                    className: 'mfb-mini-act is-del',
                                    title: 'Remove field',
                                    onClick: () => removeField(idx),
                                }, h(FbIcon, { name: 'trash', size: 12 })))),

                        /* Field Content Mockup (inline-rename on double-click) */
                        h(CanvasFieldBody, {
                            field,
                            isEditing: editingLabelId === field.id,
                            editingText: editingLabelText,
                            onChangeEditingText: setEditingLabelText,
                            onStartInlineEdit: () => startInlineEdit(field),
                            onCommitInlineEdit: () => commitInlineEdit(idx, field),
                            onCancelInlineEdit: () => setEditingLabelId(null),
                        }));
                    }),

                /* Persistent End Drop Zone */
                form.data.fields.length > 0
                    ? h('div', {
                        className: [
                            'mfb-canvas-end-drop',
                            dragUi ? 'is-active' : '',
                            dropIndicator?.targetId === 'end' ? 'is-over' : '',
                        ].filter(Boolean).join(' '),
                        'data-drop-end': '1',
                        onDragOver: (e) => {
                            const payload = readDragPayload(e);
                            if (!payload) return;
                            e.preventDefault();
                            e.stopPropagation();
                            if (payload.kind === 'field') {
                                const fromIdx = lastFieldsRef.current.findIndex((f) => f.id === payload.id);
                                if (fromIdx === lastFieldsRef.current.length - 1) return;
                            }
                            setDropIndicator({ targetId: 'end', insertIndex: lastFieldsRef.current.length, side: 'bottom' });
                        },
                        onDrop: (e) => handleCanvasDropAt(e, lastFieldsRef.current.length),
                    },
                    h(FbIcon, { name: 'plus', size: 14 }),
                    dragUi
                        ? (dragUi.kind === 'palette'
                            ? `Release to add "${dragUi.label}" at the end of the form`
                            : `Release to move "${dragUi.label}" to the bottom`)
                        : 'Drag any field here or click an item on the left to append')
                    : null),

                /* Canvas Live Submit Footer Preview */
                form.data.fields.length > 0
                    ? h('div', {
                        className: `mfb-actions btn-${form.data.settings.button_align || 'left'}`,
                        style: { marginTop: 18 },
                    },
                    h('button', {
                        type: 'button',
                        className: 'btn mfb-submit-btn',
                        onClick: () => setRightTab('design'),
                        title: 'Click to edit button style in Design tab',
                    }, form.data.settings.submit_label || 'Submit'))
                    : null)

                /* Interactive Test Mode */
                : h('div', { className: 'mfb-test-stage' },
                    h('div', { className: `mfb-test-device is-${testViewport}` },
                        h('div', { className: 'mfb-test-bezel' },
                            h('div', { className: 'mfb-test-bezel-top' },
                                h('div', { className: 'mfb-test-dots' }, h('span'), h('span'), h('span')),
                                h('span', { className: 'mfb-test-bezel-url' }, `${testViewport.toUpperCase()} · /forms/${form.data.slug || 'preview'}`),
                                h('button', {
                                    type: 'button',
                                    className: 'btn btn-light btn-sm',
                                    onClick: () => {
                                        setPreviewValues(initialValuesFor(form.data.fields));
                                        setPreviewErrors({});
                                        setPreviewStatus('');
                                    },
                                }, 'Reset inputs')),
                            h('div', {
                                className: `mfb-test-viewport-body${testViewport === 'mobile' ? ' mfb-preview-mobile' : testViewport === 'tablet' ? ' mfb-preview-tablet' : ''}`,
                            },
                            previewStatus ? h('div', { className: 'flash ok', style: { marginBottom: 14 } }, previewStatus) : null,
                            h('div', previewShellProps,
                                (form.data.name || form.data.description)
                                    ? h('div', { className: 'mfb-head', style: { marginBottom: 18 } },
                                        form.data.name ? h('h2', { style: { fontSize: '1.35rem', marginBottom: 4 } }, form.data.name) : null,
                                        form.data.description ? h('p', null, form.data.description) : null)
                                    : null,
                                h(DynamicFormRenderer, {
                                    formDef: {
                                        name: form.data.name,
                                        description: form.data.description,
                                        fields: form.data.fields,
                                        settings: form.data.settings,
                                    },
                                    values: previewValues,
                                    errors: previewErrors,
                                    onChange: (k, v) => {
                                        setPreviewStatus('');
                                        setPreviewValues((prev) => ({ ...prev, [k]: v }));
                                    },
                                    onSubmit: (e) => {
                                        e.preventDefault();
                                        const errs = validatePreviewValues(form.data.fields, previewValues);
                                        setPreviewErrors(errs);
                                        if (Object.keys(errs).length > 0) {
                                            setPreviewStatus('');
                                            return;
                                        }
                                        setPreviewStatus(form.data.settings.success_message || 'Preview submission passed validation!');
                                    },
                                    previewMode: true,
                                }))))),
                    h('div', { className: 'mfb-payload-box' },
                        h('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } },
                            h('strong', null, 'Captured JSON payload (live preview)'),
                            h('span', { style: { opacity: 0.7 } }, `${Object.keys(previewValues).filter((k) => k !== '_fb_trap').length} keys`)),
                        h('pre', null, JSON.stringify(
                            Object.fromEntries(Object.entries(previewValues).filter(([k]) => k !== '_fb_trap')),
                            null,
                            2,
                        ))))),

            /* Right Pane: Contextual Inspector */
            h('aside', { className: 'mfb-pane mfb-inspector-pane' },
                h('div', { className: 'mfb-pane-head' },
                    h('div', { className: 'mfb-pane-tabs', style: { gridTemplateColumns: 'repeat(3, 1fr)' } },
                        [
                            { id: 'field', icon: 'sliders', label: 'Field' },
                            { id: 'design', icon: 'palette', label: 'Design' },
                            { id: 'settings', icon: 'settings', label: 'Settings' },
                        ].map((t) => h('button', {
                            key: t.id,
                            type: 'button',
                            className: rightTab === t.id ? 'on' : '',
                            onClick: () => setRightTab(t.id),
                        },
                        h(FbIcon, { name: t.icon, size: 12 }),
                        t.label)))),

                h('div', { className: 'mfb-pane-scroll' },
                    rightTab === 'field'
                        ? h(FieldInspector, {
                            field: selectedField,
                            index: selectedIndex,
                            totalCount: form.data.fields.length,
                            allFields: form.data.fields,
                            updateField,
                            renameFieldKey,
                            duplicateField,
                            moveField,
                            removeField,
                        })
                        : rightTab === 'design'
                            ? h(DesignInspector, {
                                settings: form.data.settings,
                                onUpdate: (k, v) => form.setData('settings', { ...form.data.settings, [k]: v }),
                            })
                            : h(SettingsInspector, {
                                data: form.data,
                                slugSynced,
                                nameError: activeNameError,
                                onNameChange: handleFormNameChange,
                                onSlugChange: (slug) => {
                                    setSlugSynced(false);
                                    form.setData('slug', slug);
                                },
                                onToggleSlugSync: () => {
                                    if (!slugSynced) {
                                        setSlugSynced(true);
                                        form.setData('slug', ui.slugify(form.data.name || ''));
                                    } else {
                                        setSlugSynced(false);
                                    }
                                },
                                onDescriptionChange: (description) => form.setData('description', description),
                                onStatusChange: (status) => form.setData('status', status),
                                onUpdateSetting: (k, v) => form.setData('settings', { ...form.data.settings, [k]: v }),
                            })),

                h('div', { className: 'mfb-inspector-foot' },
                    h('div', { className: 'mfb-inspector-foot-meta' },
                        h('span', { className: `mfb-save-status${hasUnsavedChanges ? ' is-dirty' : ''}` },
                            hasUnsavedChanges
                                ? 'Unsaved edits'
                                : (justSaved ? 'Form saved' : (exists ? 'All changes saved' : 'Ready to save'))),
                        h('kbd', { className: 'mfb-kbd-hint' }, 'Ctrl+S')),
                    renderSaveButton('mfb-inspector-save-btn')))),

        h(ShareModal, {
            open: shareOpen,
            onClose: () => setShareOpen(false),
            form: form.data,
            saved: exists,
            unsaved: hasUnsavedChanges,
        }),

        h(TemplatesModal, {
            open: templatesOpen,
            onClose: () => setTemplatesOpen(false),
            onSelect: applyTemplate,
        }));
    }

    /** Admin: Submissions Inbox */
    function SubmissionsIndex({ submissions, forms, counts, filters }) {
        const [q, setQ] = useState(filters?.q || '');
        const [selectedIds, setSelectedIds] = useState([]);

        const applyFilter = (overrides) => {
            const params = {
                form: filters?.form || '',
                status: filters?.status && filters.status !== 'all' ? filters.status : '',
                q: filters?.q || '',
                ...overrides,
            };
            Object.keys(params).forEach((k) => { if (!params[k]) delete params[k]; });
            router.get(route('plugin.modrix.form-builder.submissions.index'), params, { preserveState: true });
        };

        const runBulk = (action) => {
            if (!selectedIds.length) return;
            if (action === 'delete' && !window.confirm(`Delete ${selectedIds.length} selected submission(s)?`)) return;
            router.post(route('plugin.modrix.form-builder.submissions.bulk'), { ids: selectedIds, action }, {
                preserveScroll: true,
                onSuccess: () => setSelectedIds([]),
            });
        };

        const exportUrl = route('plugin.modrix.form-builder.submissions.export', {
            form_id: filters?.form || undefined,
            status: filters?.status && filters.status !== 'all' ? filters.status : undefined,
        });

        const toneFor = (st) => ({ new: 'purple', read: 'green', archived: 'gray', spam: 'amber' }[st] || 'gray');
        const allIds = submissions.data.map((s) => s.id);
        const allSelected = allIds.length > 0 && allIds.every((id) => selectedIds.includes(id));

        return h(ui.AdminLayout, {
            title: 'Form submissions',
            subtitle: 'Review captured entries, filter by form or status, apply bulk updates, or export CSV.',
            actions: h(React.Fragment, null,
                h('a', { className: 'btn btn-light', href: exportUrl }, 'Export CSV'),
                h(Link, { className: 'btn', href: route('plugin.modrix.form-builder.forms.index') }, 'Manage forms')),
        },
        h('div', { className: 'mfb-kpi-row' },
            [
                { key: 'all', label: 'All submissions', val: counts.all || 0, tone: '' },
                { key: 'new', label: 'Unread', val: counts.new || 0, tone: 'is-purple' },
                { key: 'read', label: 'Read', val: counts.read || 0, tone: 'is-green' },
                { key: 'spam', label: 'Spam blocked', val: counts.spam || 0, tone: 'is-amber' },
            ].map((item) => h('div', {
                key: item.key,
                className: 'mfb-kpi-card',
                style: { cursor: 'pointer' },
                onClick: () => applyFilter({ status: item.key }),
            },
            h('div', null,
                h('div', { className: 'mfb-kpi-label' }, item.label),
                h('div', { className: 'mfb-kpi-value' }, item.val)),
            h('div', { className: `mfb-kpi-icon ${item.tone}` }, h(FbIcon, { name: 'inbox', size: 17 }))))),

        h('div', { className: 'mfb-sub-filters' },
            h('div', { className: 'mfb-status-pills' },
                [
                    { key: 'all', label: 'All' },
                    { key: 'new', label: 'Unread' },
                    { key: 'read', label: 'Read' },
                    { key: 'archived', label: 'Archived' },
                    { key: 'spam', label: 'Spam' },
                ].map((tab) => h('button', {
                    key: tab.key,
                    type: 'button',
                    className: `mfb-filter-pill${(filters?.status || 'all') === tab.key ? ' on' : ''}`,
                    onClick: () => applyFilter({ status: tab.key }),
                },
                tab.label,
                h('span', { className: 'count' }, counts[tab.key] ?? 0))))),

        h('div', { className: 'panel' },
            h('form', { className: 'toolbar', onSubmit: (e) => { e.preventDefault(); applyFilter({ q }); } },
                h('select', {
                    value: filters?.form || '',
                    onChange: (e) => applyFilter({ form: e.target.value }),
                },
                h('option', { value: '' }, 'All forms'),
                forms.map((f) => h('option', { key: f.id, value: String(f.id) }, f.name))),
                h('input', {
                    type: 'search',
                    placeholder: 'Search submitted fields…',
                    value: q,
                    onChange: (e) => setQ(e.target.value),
                }),
                h('button', { className: 'btn btn-light', type: 'submit' }, 'Filter')),

            selectedIds.length > 0
                ? h('div', { className: 'toolbar', style: { background: 'var(--bg, #f8fafc)', padding: 10, borderRadius: 10, marginBottom: 12 } },
                    h('strong', null, `${selectedIds.length} selected`),
                    h('button', { type: 'button', className: 'btn btn-light btn-sm', onClick: () => runBulk('mark_read') }, 'Mark read'),
                    h('button', { type: 'button', className: 'btn btn-light btn-sm', onClick: () => runBulk('archive') }, 'Archive'),
                    h('button', { type: 'button', className: 'btn btn-light btn-sm', onClick: () => runBulk('spam') }, 'Mark spam'),
                    h('button', { type: 'button', className: 'btn btn-danger btn-sm', onClick: () => runBulk('delete') }, 'Delete'))
                : null,

            h('div', { className: 'table-wrap' },
                h('table', null,
                    h('thead', null,
                        h('tr', null,
                            h('th', { style: { width: 32 } },
                                h('input', {
                                    type: 'checkbox',
                                    checked: allSelected,
                                    onChange: (e) => setSelectedIds(e.target.checked ? allIds : []),
                                })),
                            h('th', null, 'Form'),
                            h('th', null, 'Summary'),
                            h('th', null, 'Status'),
                            h('th', null, 'Received'),
                            h('th', null, ''))),
                    h('tbody', null, submissions.data.length
                        ? submissions.data.map((s) => h('tr', { key: s.id },
                            h('td', null, h('input', {
                                type: 'checkbox',
                                checked: selectedIds.includes(s.id),
                                onChange: (e) => setSelectedIds(e.target.checked
                                    ? [...selectedIds, s.id]
                                    : selectedIds.filter((x) => x !== s.id)),
                            })),
                            h('td', { className: 'title' }, h(Link, { href: route('plugin.modrix.form-builder.submissions.show', s.id) }, s.form_name)),
                            h('td', { className: 'sub' }, s.preview || '—'),
                            h('td', null, h(ui.Badge, { tone: toneFor(s.status) }, s.status)),
                            h('td', { title: s.created_at }, s.created),
                            h('td', { className: 'actions' },
                                h(Link, { className: 'btn btn-light btn-sm', href: route('plugin.modrix.form-builder.submissions.show', s.id) }, 'Open'),
                                h(ui.DangerButton, { confirm: 'Delete this submission?', url: route('plugin.modrix.form-builder.submissions.destroy', s.id) }, 'Delete'))))
                        : h('tr', null, h('td', { colSpan: 6, className: 'empty' }, 'No submissions match these filters.'))))),
            h(ui.Pager, { paginator: submissions })));
    }

    /** Admin: Submission Detail */
    function SubmissionShow({ submission }) {
        const form = useForm({ status: submission.status, notes: submission.notes || '' });

        const submit = (e) => {
            e.preventDefault();
            form.put(route('plugin.modrix.form-builder.submissions.update', submission.id), { preserveScroll: true });
        };

        const renderEntryValue = (entry) => {
            if (!entry.value) return '—';
            if (entry.type === 'rating' && !Number.isNaN(Number(entry.value))) {
                const count = Math.max(0, Math.min(10, Number(entry.value)));
                return h('span', { style: { color: '#f59e0b', letterSpacing: 2 } },
                    '★'.repeat(count),
                    h('span', { style: { color: 'var(--muted)', fontSize: 12, marginLeft: 6, letterSpacing: 0 } }, `(${count})`));
            }
            if (entry.type === 'url' && /^https?:\/\//i.test(entry.value)) {
                return h('a', { href: entry.value, target: '_blank', rel: 'noreferrer' }, entry.value);
            }
            if (entry.type === 'email') {
                return h('a', { href: `mailto:${entry.value}` }, entry.value);
            }
            return entry.value;
        };

        return h(ui.AdminLayout, {
            title: `Submission #${submission.id}`,
            subtitle: `${submission.form_name} · ${submission.created_at}`,
            actions: h(React.Fragment, null,
                h(Link, { className: 'btn btn-light', href: route('plugin.modrix.form-builder.submissions.index') }, '← All submissions'),
                submission.form_id
                    ? h(Link, { className: 'btn btn-light', href: route('plugin.modrix.form-builder.forms.edit', submission.form_id) }, 'Edit form')
                    : null,
                h(ui.DangerButton, { confirm: 'Delete this submission?', url: route('plugin.modrix.form-builder.submissions.destroy', submission.id), small: false }, 'Delete')),
        },
        h('div', { className: 'grid-2' },
            h('div', { className: 'panel' },
                h('h2', null, 'Submitted values'),
                h('div', { className: 'mfb-kv-list', style: { marginTop: 12 } },
                    submission.entries.map((entry) => h('div', { key: entry.key, className: 'mfb-kv-item' },
                        h('div', { className: 'mfb-kv-key' },
                            h('span', null, entry.label),
                            h('span', { style: { fontFamily: 'ui-monospace, monospace', fontSize: 10.5 } }, entry.key)),
                        h('div', { className: 'mfb-kv-val' }, renderEntryValue(entry)))))),

            h('div', null,
                h('form', { className: 'panel', onSubmit: submit },
                    h('h2', null, 'Triage & notes'),
                    h('div', { className: 'field' },
                        h('label', null, 'Status'),
                        h('select', { value: form.data.status, onChange: (e) => form.setData('status', e.target.value) },
                            h('option', { value: 'new' }, 'New / Unread'),
                            h('option', { value: 'read' }, 'Read'),
                            h('option', { value: 'archived' }, 'Archived'),
                            h('option', { value: 'spam' }, 'Spam'))),
                    h('div', { className: 'field' },
                        h('label', null, 'Internal notes'),
                        h('textarea', {
                            rows: 4,
                            placeholder: 'Add follow-up context for your team…',
                            value: form.data.notes,
                            onChange: (e) => form.setData('notes', e.target.value),
                        })),
                    h('button', { className: 'btn', type: 'submit', disabled: form.processing },
                        form.processing ? 'Saving…' : 'Save changes')),
                h('div', { className: 'panel' },
                    h('h2', null, 'Metadata'),
                    h('table', null, h('tbody', null,
                        h('tr', null, h('td', { className: 'sub' }, 'Page URL'), h('td', { style: { wordBreak: 'break-all' } }, submission.page_url || '—')),
                        h('tr', null, h('td', { className: 'sub' }, 'IP address'), h('td', null, submission.ip_address || '—')),
                        h('tr', null, h('td', { className: 'sub' }, 'User agent'), h('td', { className: 'sub' }, submission.user_agent || '—'))))))));
    }

    return { FormsIndex, FormEditor, SubmissionsIndex, SubmissionShow };
}
