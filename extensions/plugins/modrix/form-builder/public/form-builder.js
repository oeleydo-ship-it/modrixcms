import { createFormBuilderAdminPages } from './form-builder-studio.js?v=1.3.4';

const { React, Inertia, ui, registerPlugin } = window.ModrixCMS;
const h = React.createElement;
const { useState, useMemo } = React;
const { Link, router, useForm, usePage } = Inertia;

const uid = () => Math.random().toString(36).slice(2, 10);

const FIELD_GROUPS = [
    {
        name: 'Basic fields',
        items: [
            { type: 'text', label: 'Text', placeholder: '' },
            { type: 'email', label: 'Email', placeholder: 'you@example.com' },
            { type: 'phone', label: 'Phone', placeholder: '+1 555 0100' },
            { type: 'textarea', label: 'Long text', placeholder: '' },
            { type: 'number', label: 'Number', placeholder: '' },
            { type: 'url', label: 'URL', placeholder: 'https://' },
        ],
    },
    {
        name: 'Choice & consent',
        items: [
            { type: 'select', label: 'Dropdown', placeholder: '' },
            { type: 'radio', label: 'Radio choice', placeholder: '' },
            { type: 'checkbox', label: 'Checkbox', placeholder: '' },
            { type: 'checkbox_group', label: 'Multi-check', placeholder: '' },
            { type: 'consent', label: 'Consent', placeholder: 'I agree to receive updates and accept the privacy policy.' },
            { type: 'rating', label: 'Star rating', placeholder: '' },
        ],
    },
    {
        name: 'Date, time & layout',
        items: [
            { type: 'date', label: 'Date', placeholder: '' },
            { type: 'time', label: 'Time', placeholder: '' },
            { type: 'hidden', label: 'Hidden', placeholder: '' },
            { type: 'heading', label: 'Section heading', placeholder: '' },
            { type: 'paragraph', label: 'Info text', placeholder: '' },
            { type: 'divider', label: 'Divider', placeholder: '' },
        ],
    },
];

const FIELD_CATALOG = FIELD_GROUPS.flatMap((g) => g.items);

const NON_INPUT_TYPES = ['heading', 'paragraph', 'divider'];

const ACCENT_SWATCHES = ['#5b3df5', '#2563eb', '#0f766e', '#16a34a', '#dc2626', '#ea580c', '#0f172a'];

const PHONE_PRESETS = [
    { label: 'Custom', placeholder: '+1 555 0100', defaultValue: '' },
    { label: 'US / Canada (+1)', placeholder: '+1 555 0100', defaultValue: '+1 ' },
    { label: 'UK (+44)', placeholder: '+44 7700 900077', defaultValue: '+44 ' },
    { label: 'UAE (+971)', placeholder: '+971 50 000 0000', defaultValue: '+971 ' },
    { label: 'India (+91)', placeholder: '+91 98765 43210', defaultValue: '+91 ' },
    { label: 'Australia (+61)', placeholder: '+61 400 000 000', defaultValue: '+61 ' },
];

const STARTER_TEMPLATES = {
    blank: {
        name: 'Blank Canvas',
        description: 'Start empty and drag fields from the left palette onto the grid.',
        fields: [],
    },
    contact: {
        name: 'Contact Us',
        description: 'Clean two-column contact form ready for any page.',
        fields: [
            { id: uid(), type: 'text', key: 'full_name', label: 'Full name', placeholder: 'Jane Doe', help: '', required: true, width: 'half', default_value: '', options: [], min: null, max: null, condition: null },
            { id: uid(), type: 'email', key: 'email', label: 'Email address', placeholder: 'jane@example.com', help: '', required: true, width: 'half', default_value: '', options: [], min: null, max: null, condition: null },
            { id: uid(), type: 'phone', key: 'phone', label: 'Phone number', placeholder: '+1 555 0100', help: 'Optional, for faster follow-up.', required: false, width: 'full', default_value: '', options: [], min: null, max: null, condition: null },
            { id: uid(), type: 'textarea', key: 'message', label: 'How can we help?', placeholder: 'Write your message…', help: '', required: true, width: 'full', default_value: '', options: [], min: null, max: null, rows: 4, condition: null },
        ],
    },
    lead: {
        name: 'Lead Capture',
        description: 'Capture sales leads with company size and conditional budget questions.',
        fields: [
            { id: uid(), type: 'text', key: 'full_name', label: 'Full name', placeholder: 'Alex Rivera', help: '', required: true, width: 'half', default_value: '', options: [], min: null, max: null, condition: null },
            { id: uid(), type: 'email', key: 'work_email', label: 'Work email', placeholder: 'alex@company.com', help: '', required: true, width: 'half', default_value: '', options: [], min: null, max: null, condition: null },
            { id: uid(), type: 'select', key: 'inquiry_type', label: 'What are you interested in?', placeholder: '', help: '', required: true, width: 'full', default_value: '', options: ['Product Demo', 'Enterprise Pricing', 'Partnership', 'General Question'], min: null, max: null, condition: null },
            { id: uid(), type: 'select', key: 'team_size', label: 'Team size', placeholder: '', help: '', required: false, width: 'half', default_value: '', options: ['1-10', '11-50', '51-200', '200+'], min: null, max: null, condition: { field: 'inquiry_type', operator: 'equals', value: 'Enterprise Pricing' } },
            { id: uid(), type: 'phone', key: 'phone', label: 'Phone number', placeholder: '+1 555 0100', help: '', required: false, width: 'half', default_value: '', options: [], min: null, max: null, condition: null },
            { id: uid(), type: 'textarea', key: 'details', label: 'Project details', placeholder: 'Tell us about your timeline and goals…', help: '', required: false, width: 'full', default_value: '', options: [], min: null, max: null, rows: 4, condition: null },
        ],
    },
    callback: {
        name: 'Callback Request',
        description: 'Phone-first callback scheduling with preferred time slot and SMS consent.',
        fields: [
            { id: uid(), type: 'text', key: 'name', label: 'Your name', placeholder: 'Sam Taylor', help: '', required: true, width: 'half', default_value: '', options: [], min: null, max: null, condition: null },
            { id: uid(), type: 'phone', key: 'mobile_phone', label: 'Mobile number', placeholder: '+1 555 0199', help: '', required: true, width: 'half', default_value: '+1 ', options: [], min: null, max: null, condition: null },
            { id: uid(), type: 'date', key: 'preferred_date', label: 'Preferred date', placeholder: '', help: '', required: false, width: 'half', default_value: '', options: [], min: null, max: null, condition: null },
            { id: uid(), type: 'time', key: 'preferred_time', label: 'Preferred time', placeholder: '', help: '', required: false, width: 'half', default_value: '10:00', options: [], min: null, max: null, condition: null },
            { id: uid(), type: 'consent', key: 'sms_consent', label: 'SMS permission', placeholder: 'I agree to receive a callback and SMS confirmation.', help: '', required: true, width: 'full', default_value: '', options: [], min: null, max: null, condition: null },
        ],
    },
    feedback: {
        name: 'Customer Feedback',
        description: 'Collect star ratings and detailed product feedback.',
        fields: [
            { id: uid(), type: 'rating', key: 'satisfaction', label: 'Overall satisfaction', placeholder: '', help: '1 = Poor, 5 = Excellent', required: true, width: 'full', default_value: '5', options: [], min: 1, max: 5, max_stars: 5, condition: null },
            { id: uid(), type: 'radio', key: 'recommend', label: 'Would you recommend us?', placeholder: '', help: '', required: true, width: 'full', default_value: '', options: ['Definitely', 'Maybe', 'Not right now'], min: null, max: null, condition: null },
            { id: uid(), type: 'textarea', key: 'improve', label: 'What could we improve?', placeholder: 'Share anything that slowed you down…', help: '', required: false, width: 'full', default_value: '', options: [], min: null, max: null, rows: 4, condition: { field: 'recommend', operator: 'not_equals', value: 'Definitely' } },
            { id: uid(), type: 'email', key: 'email', label: 'Email (optional for follow-up)', placeholder: 'you@example.com', help: '', required: false, width: 'full', default_value: '', options: [], min: null, max: null, condition: null },
        ],
    },
    event: {
        name: 'Event Registration',
        description: 'Register attendees with session preferences and dietary notes.',
        fields: [
            { id: uid(), type: 'text', key: 'attendee_name', label: 'Attendee name', placeholder: '', help: '', required: true, width: 'half', default_value: '', options: [], min: null, max: null, condition: null },
            { id: uid(), type: 'email', key: 'email', label: 'Email', placeholder: '', help: '', required: true, width: 'half', default_value: '', options: [], min: null, max: null, condition: null },
            { id: uid(), type: 'date', key: 'arrival_date', label: 'Arrival date', placeholder: '', help: '', required: true, width: 'half', default_value: '', options: [], min: null, max: null, condition: null },
            { id: uid(), type: 'number', key: 'guests', label: 'Number of guests', placeholder: '1', help: '', required: true, width: 'half', default_value: '1', options: [], min: 1, max: 10, condition: null },
            { id: uid(), type: 'checkbox_group', key: 'tracks', label: 'Sessions you plan to attend', placeholder: '', help: '', required: true, width: 'full', default_value: '', options: ['Opening Keynote', 'Hands-on Workshop', 'Networking Dinner'], min: null, max: null, condition: null },
            { id: uid(), type: 'checkbox', key: 'agree_updates', label: 'Send me event reminders by email', placeholder: '', help: '', required: false, width: 'full', default_value: '', options: [], min: null, max: null, condition: null },
        ],
    },
};

function makeFieldKey(label, existingFields, excludeIndex = -1) {
    let base = ui.slugify(label || 'field').replace(/-/g, '_') || 'field';
    if (/^[0-9]/.test(base)) base = `f_${base}`;
    base = base.slice(0, 54);
    const used = new Set(existingFields.filter((_, idx) => idx !== excludeIndex).map((f) => f.key));
    let candidate = base;
    let counter = 2;
    while (used.has(candidate)) {
        candidate = `${base.slice(0, 50)}_${counter}`;
        counter += 1;
    }
    return candidate;
}

function buildNewField(type, existingFields) {
    const catalogItem = FIELD_CATALOG.find((c) => c.type === type);
    const label = catalogItem?.label || 'Field';
    return {
        id: uid(),
        type,
        key: makeFieldKey(label, existingFields),
        label,
        placeholder: catalogItem?.placeholder || '',
        help: '',
        required: false,
        width: 'full',
        default_value: type === 'rating' ? '5' : '',
        options: ['select', 'radio', 'checkbox_group'].includes(type) ? ['Option 1', 'Option 2'] : [],
        min: null,
        max: null,
        min_length: null,
        max_length: null,
        min_date: '',
        max_date: '',
        rows: 4,
        max_stars: 5,
        condition: null,
        _syncKey: true,
    };
}

function isFieldVisible(field, values) {
    const cond = field.condition;
    if (!cond || !cond.field) {
        return true;
    }
    const raw = values[cond.field];
    const expected = String(cond.value ?? '');
    const op = cond.operator || 'equals';

    if (Array.isArray(raw)) {
        if (op === 'not_empty') return raw.length > 0;
        if (op === 'is_empty') return raw.length === 0;
        if (op === 'not_equals') return !raw.includes(expected);
        return raw.includes(expected);
    }

    const actual = typeof raw === 'boolean' ? (raw ? '1' : '0') : String(raw ?? '');
    if (op === 'not_equals') return actual !== expected;
    if (op === 'contains') return expected !== '' && actual.toLowerCase().includes(expected.toLowerCase());
    if (op === 'not_empty') return actual.trim() !== '';
    if (op === 'is_empty') return actual.trim() === '';
    if (op === 'greater_than') return actual !== '' && expected !== '' && !Number.isNaN(Number(actual)) && !Number.isNaN(Number(expected)) && Number(actual) > Number(expected);
    if (op === 'less_than') return actual !== '' && expected !== '' && !Number.isNaN(Number(actual)) && !Number.isNaN(Number(expected)) && Number(actual) < Number(expected);
    return actual === expected;
}

function initialValuesFor(fields) {
    const out = { _fb_trap: '' };
    (fields || []).forEach((field) => {
        if (NON_INPUT_TYPES.includes(field.type)) return;
        if (field.type === 'checkbox' || field.type === 'consent') out[field.key] = field.default_value === '1' || field.default_value === 'true';
        else if (field.type === 'checkbox_group') out[field.key] = [];
        else out[field.key] = field.default_value ?? '';
    });
    return out;
}

function validatePreviewValues(fields, values) {
    const errors = {};
    (fields || []).forEach((field) => {
        if (NON_INPUT_TYPES.includes(field.type) || field.type === 'hidden') return;
        if (!isFieldVisible(field, values)) return;

        const val = values[field.key];
        if (field.type === 'checkbox' || field.type === 'consent') {
            if (field.required && !val) errors[field.key] = `${field.label} must be checked.`;
            return;
        }
        if (field.type === 'checkbox_group') {
            if (field.required && (!Array.isArray(val) || val.length === 0)) errors[field.key] = `Choose at least one option for ${field.label}.`;
            return;
        }

        const str = String(val ?? '').trim();
        if (field.required && str === '') {
            errors[field.key] = `${field.label} is required.`;
            return;
        }
        if (str === '') return;

        if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(str)) {
            errors[field.key] = 'Enter a valid email address.';
        } else if (field.type === 'url' && !/^https?:\/\/\S+$/i.test(str)) {
            errors[field.key] = 'Enter a valid URL starting with http:// or https://.';
        } else if (field.type === 'phone' && !/^[0-9+\-\s().]{5,40}$/.test(str)) {
            errors[field.key] = 'Enter a valid phone number.';
        } else if (field.type === 'number') {
            const num = Number(str);
            if (Number.isNaN(num)) errors[field.key] = 'Enter a valid number.';
            else if (field.min !== null && field.min !== undefined && num < field.min) errors[field.key] = `Must be at least ${field.min}.`;
            else if (field.max !== null && field.max !== undefined && num > field.max) errors[field.key] = `Must be at most ${field.max}.`;
        } else if (['text', 'textarea', 'phone', 'url'].includes(field.type)) {
            if (field.min_length && str.length < field.min_length) errors[field.key] = `Must be at least ${field.min_length} characters.`;
            else if (field.max_length && str.length > field.max_length) errors[field.key] = `Must be at most ${field.max_length} characters.`;
        }
    });
    return errors;
}

function shellClassesAndStyle(settings, extraClass = '') {
    const s = settings || {};
    const classes = [
        'mfb-shell',
        `is-${s.card_style || 'card'}`,
        `r-${s.border_radius || 'rounded'}`,
        `sp-${s.spacing || 'comfortable'}`,
        extraClass,
    ].filter(Boolean).join(' ');
    // The form follows the active theme unless it has its own colour. The old default purple was saved
    // on every form, so it counts as "no colour chosen".
    const own = typeof s.accent_color === 'string' && /^#[0-9a-f]{3,6}$/i.test(s.accent_color) && s.accent_color.toLowerCase() !== '#5b3df5';
    const style = own ? { '--mfb-accent': s.accent_color, '--mfb-on-accent': '#ffffff' } : undefined;
    return { className: classes, style };
}

function DynamicFormRenderer({ formDef, values, errors = {}, onChange, onSubmit, processing = false, submitted = false, previewMode = false }) {
    const fields = formDef.fields || [];
    const settings = formDef.settings || {};
    const formUid = useMemo(() => uid(), []);

    if (submitted && !previewMode) {
        return h('div', { className: 'mfb-sent', role: 'status' }, settings.success_message || 'Thank you! Your submission has been received.');
    }

    const hasRequired = fields.some((f) => f.required && !NON_INPUT_TYPES.includes(f.type));
    const widthClass = (width) => (width === 'half' ? ' is-half' : width === 'third' ? ' is-third' : '');

    const renderInput = (field, inputId, describedBy) => {
        const val = values[field.key];
        const setVal = (next) => onChange(field.key, next);
        const invalid = Boolean(errors[field.key] || errors[`${field.key}.0`]);
        const invalidCls = invalid ? ' is-invalid' : '';

        if (field.type === 'textarea') {
            return h('textarea', {
                id: inputId,
                className: `mfb-textarea${invalidCls}`,
                name: field.key,
                rows: field.rows || 4,
                minLength: field.min_length || undefined,
                maxLength: field.max_length || undefined,
                placeholder: field.placeholder || '',
                required: field.required,
                'aria-describedby': describedBy || undefined,
                'aria-invalid': invalid || undefined,
                value: val ?? '',
                onChange: (e) => setVal(e.target.value),
            });
        }

        if (field.type === 'select') {
            return h('select', {
                id: inputId,
                className: `mfb-select${invalidCls}`,
                name: field.key,
                required: field.required,
                'aria-describedby': describedBy || undefined,
                'aria-invalid': invalid || undefined,
                value: val ?? '',
                onChange: (e) => setVal(e.target.value),
            },
            h('option', { value: '' }, field.placeholder || '— Select —'),
            (field.options || []).map((opt, idx) => h('option', { key: `${idx}-${opt}`, value: opt }, opt)));
        }

        if (field.type === 'radio') {
            return h('div', { className: 'mfb-choice-list', role: 'radiogroup', 'aria-labelledby': `${inputId}-lbl` },
                (field.options || []).map((opt, idx) => h('label', { key: `${idx}-${opt}`, className: 'mfb-choice' },
                    h('input', {
                        type: 'radio',
                        name: field.key,
                        value: opt,
                        checked: val === opt,
                        required: field.required,
                        onChange: () => setVal(opt),
                    }),
                    opt)));
        }

        if (field.type === 'checkbox' || field.type === 'consent') {
            const box = h('label', { className: 'mfb-choice', htmlFor: inputId },
                h('input', {
                    id: inputId,
                    type: 'checkbox',
                    name: field.key,
                    checked: Boolean(val),
                    required: field.required,
                    onChange: (e) => setVal(e.target.checked),
                }),
                h('span', null,
                    field.placeholder || field.label,
                    field.required ? h('span', { className: 'mfb-req' }, '*') : null));
            return field.type === 'consent' ? h('div', { className: 'mfb-consent-box' }, box) : box;
        }

        if (field.type === 'checkbox_group') {
            const selected = Array.isArray(val) ? val : [];
            const toggleOption = (opt) => {
                if (selected.includes(opt)) setVal(selected.filter((x) => x !== opt));
                else setVal([...selected, opt]);
            };
            return h('div', { className: 'mfb-choice-list', 'aria-labelledby': `${inputId}-lbl` },
                (field.options || []).map((opt, idx) => h('label', { key: `${idx}-${opt}`, className: 'mfb-choice' },
                    h('input', {
                        type: 'checkbox',
                        checked: selected.includes(opt),
                        onChange: () => toggleOption(opt),
                    }),
                    opt)));
        }

        if (field.type === 'rating') {
            const current = Number(val || 0);
            const totalStars = Math.max(3, Math.min(10, Number(field.max_stars || 5)));
            return h('div', { className: 'mfb-stars', role: 'radiogroup', 'aria-label': field.label },
                Array.from({ length: totalStars }, (_, idx) => idx + 1).map((star) => h('button', {
                    key: star,
                    type: 'button',
                    className: `mfb-star${current >= star ? ' on' : ''}`,
                    onClick: () => setVal(String(star)),
                    'aria-label': `${star} star${star > 1 ? 's' : ''}`,
                }, '★')));
        }

        if (field.type === 'hidden') {
            return h('input', { type: 'hidden', name: field.key, value: val ?? '' });
        }

        const inputType = field.type === 'phone' ? 'tel' : field.type;
        return h('input', {
            id: inputId,
            className: `mfb-input${invalidCls}`,
            type: inputType,
            name: field.key,
            placeholder: field.placeholder || '',
            required: field.required,
            min: field.type === 'number' && field.min !== null ? field.min : (field.type === 'date' && field.min_date ? field.min_date : undefined),
            max: field.type === 'number' && field.max !== null ? field.max : (field.type === 'date' && field.max_date ? field.max_date : undefined),
            minLength: ['text', 'phone', 'url'].includes(field.type) && field.min_length ? field.min_length : undefined,
            maxLength: ['text', 'phone', 'url'].includes(field.type) && field.max_length ? field.max_length : undefined,
            'aria-describedby': describedBy || undefined,
            'aria-invalid': invalid || undefined,
            value: val ?? '',
            onChange: (e) => setVal(e.target.value),
        });
    };

    return h('form', { className: 'mfb-grid', onSubmit, noValidate: previewMode },
        settings.show_required_note && hasRequired
            ? h('div', { className: 'mfb-required-note' }, 'Fields marked ', h('span', { className: 'mfb-req' }, '*'), ' are required.')
            : null,
        fields.map((field) => {
            if (!isFieldVisible(field, values)) {
                return null;
            }
            if (field.type === 'divider') {
                return h('hr', { key: field.id || field.key, className: 'mfb-divider-block' });
            }
            if (field.type === 'heading') {
                return h('div', { key: field.id || field.key, className: 'mfb-heading-block' },
                    h('h3', null, field.label),
                    field.help ? h('p', null, field.help) : null);
            }
            if (field.type === 'paragraph') {
                return h('div', { key: field.id || field.key, className: 'mfb-paragraph-block' },
                    field.label ? h('strong', null, field.label) : null,
                    field.help ? h('span', null, field.help) : null);
            }
            if (field.type === 'hidden') {
                return renderInput(field, `mfb-${formUid}-${field.key}`, '');
            }
            const inputId = `mfb-${formUid}-${field.id || field.key}`;
            const helpId = field.help ? `${inputId}-help` : '';
            const err = errors[field.key] || errors[`${field.key}.0`];
            const isCheckSingle = field.type === 'checkbox' || field.type === 'consent';
            return h('div', { key: field.id || field.key, className: `mfb-field${widthClass(field.width)}` },
                !isCheckSingle
                    ? h('label', {
                        className: 'mfb-label',
                        id: `${inputId}-lbl`,
                        htmlFor: ['radio', 'checkbox_group', 'rating'].includes(field.type) ? undefined : inputId,
                    }, field.label, field.required ? h('span', { className: 'mfb-req' }, '*') : null)
                    : null,
                renderInput(field, inputId, helpId),
                field.help ? h('div', { id: helpId, className: 'mfb-help' }, field.help) : null,
                err ? h('div', { className: 'mfb-error', role: 'alert' }, err) : null);
        }),
        settings.honeypot !== false
            ? h('label', { className: 'mfb-trap', 'aria-hidden': 'true' }, 'Leave blank ',
                h('input', {
                    name: '_fb_trap',
                    tabIndex: -1,
                    autoComplete: 'off',
                    value: values._fb_trap ?? '',
                    onChange: (e) => onChange('_fb_trap', e.target.value),
                }))
            : null,
        h('div', { className: `mfb-actions btn-${settings.button_align || 'left'}` },
            h('button', { className: 'btn mfb-submit-btn', type: 'submit', disabled: processing },
                processing ? 'Sending…' : (settings.submit_label || 'Submit'))));
}

/** Public Page-Builder Block */
function FormBlock({ data, anchor }) {
    const formDef = data.form;
    const initial = useMemo(() => initialValuesFor(formDef?.fields || []), [formDef?.slug]);
    const form = useForm({ ...initial, _anchor: anchor || 'form' });

    if (!formDef) {
        return h('section', { className: 'mfb-section', id: anchor },
            h('div', { className: 'wrap' },
                h('div', { className: 'mfb-empty-note' }, 'Select an active form in the page builder to display it here.')));
    }

    const heading = data.title || formDef.name;
    const intro = data.description || formDef.description;
    const layout = data.layout || 'card';

    const handleSubmit = (e) => {
        e.preventDefault();
        form.post(formDef.submitUrl, {
            preserveScroll: true,
            errorBag: `formBuilder_${formDef.slug}`,
            onSuccess: () => form.reset(),
        });
    };

    const formEl = h(DynamicFormRenderer, {
        formDef,
        values: form.data,
        errors: form.errors,
        onChange: (k, v) => form.setData(k, v),
        onSubmit: handleSubmit,
        processing: form.processing,
        submitted: formDef.submitted,
    });

    const headEl = (heading || intro)
        ? h('div', { className: 'mfb-head' },
            heading ? h('h2', null, heading) : null,
            intro ? h('p', null, intro) : null)
        : null;

    const shellProps = shellClassesAndStyle(formDef.settings, layout === 'minimal' ? 'is-minimal' : '');

    return h('section', { className: 'mfb-section', id: anchor },
        h('div', { className: 'wrap' },
            h('div', shellProps,
                layout === 'split'
                    ? h('div', { className: 'mfb-split' }, headEl || h('div'), formEl)
                    : h(React.Fragment, null, headEl, formEl))));
}

const { FormsIndex, FormEditor, SubmissionsIndex, SubmissionShow } = createFormBuilderAdminPages({
    React,
    h,
    Link,
    router,
    useForm,
    usePage,
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
    isFieldVisible,
    initialValuesFor,
    validatePreviewValues,
    shellClassesAndStyle,
    DynamicFormRenderer,
});

registerPlugin('modrix/form-builder', {
    blocks: { 'modrix/form-builder:form': FormBlock },
    pages: {
        FormsIndex,
        FormEditor,
        SubmissionsIndex,
        SubmissionShow,
    },
});
