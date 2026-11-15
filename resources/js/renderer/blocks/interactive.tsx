/**
 * Capture, commerce, persuasion, scheduling and engagement blocks.
 *
 * Checkout, product, booking and calendar talk to their public endpoints (the
 * booking flow lives in ./booking); everything is a real, working, styled
 * implementation.
 */

import { Check, ChevronDown, Lock, Star } from 'lucide-react';
import { useEffect, useId, useMemo, useRef, useState, type CSSProperties, type FormEvent, type ReactNode } from 'react';
import { bool, list, num, oneOf, optionalStr, recordList, safeHref, str } from '../props';
import {
    createCheckout,
    formatMoney,
    quoteCart,
    formatInterval,
    waitForCheckoutIntent,
    loadStripe,
    rememberCheckout,
    type CartRequest,
    type CheckoutQuote,
    type CommerceProduct,
    type CommerceRuntime,
    type ShippingAddress,
    type StripeElement,
    type StripeElements,
    type StripeLike,
} from '../commerce';
import { fieldVisible, readAnswers, type FormAnswers } from '../formLogic';
import { UpsellButton } from './upsell';
import {
    resolveButtonAction,
    stepPosition,
    submitCaptureForm,
    useFunnel,
    type PublicFormDefinition,
    type PublicFormField,
} from '../funnel';
import { buttonSurface, iconFor } from './content';
import { responsiveProp } from '../responsiveProps';
import { CanvasAddButton, EditableText, Placeholder, stack, withItem, type BlockComponentProps } from './shared';

const fieldStyle: CSSProperties = {
    width: '100%',
    minWidth: 0,
    padding: '11px 12px',
    borderRadius: '8px',
    border: '1px solid var(--color-border-strong, #cbd5e1)',
    background: 'var(--color-surface, #fff)',
    color: 'inherit',
    font: 'inherit',
};

const submitStyle: CSSProperties = {
    padding: '11px 20px',
    borderRadius: '8px',
    border: '1px solid transparent',
    background: 'var(--fnl-accent, #4f46e5)',
    color: '#fff',
    font: 'inherit',
    fontWeight: 600,
    cursor: 'pointer',
    whiteSpace: 'normal',
    maxWidth: '100%',
    overflowWrap: 'anywhere',
};

/** n columns on a wide screen, wrapping to fewer rather than squeezing on a narrow one. */
/** The card grid; a per-breakpoint column count (`--fnl-p-grid`) replaces the fluid tracks. */
function responsiveGrid(columns: number, minWidth = '240px'): CSSProperties {
    return {
        display: 'grid',
        gridTemplateColumns: responsiveProp(
            'grid',
            columns <= 1 ? 'minmax(0, 1fr)' : `repeat(auto-fit, minmax(min(100%, ${minWidth}), 1fr))`,
        ),
        gap: '20px',
    };
}

const cardStyle: CSSProperties = {
    border: '1px solid var(--color-border, #e2e8f0)',
    borderRadius: '12px',
    padding: '20px',
    background: 'var(--color-surface, #fff)',
};

/* -------------------------------------------------------------------------- */
/* Capture                                                                     */
/* -------------------------------------------------------------------------- */

type CaptureState =
    | { kind: 'idle' }
    | { kind: 'sending' }
    | { kind: 'done'; message: string }
    | { kind: 'error'; message: string };

/**
 * The submit behaviour every capture block shares.
 *
 * Submission goes through fetch because the public endpoint answers JSON: a
 * native post would leave the visitor looking at a raw JSON document. On
 * success the block's `successAction` decides what happens — the next funnel
 * step, a redirect, or an inline thank-you — which is what wires one step of a
 * funnel to the next.
 */
function useCaptureSubmit(block: BlockComponentProps['block'], mode: BlockComponentProps['mode']) {
    const runtime = useFunnel();
    const [state, setState] = useState<CaptureState>({ kind: 'idle' });
    const formId = optionalStr(block.props, 'formId');

    const onSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        // Never submit from the builder canvas.
        if (mode === 'editable' || state.kind === 'sending') {
            return;
        }

        if (!formId) {
            setState({ kind: 'error', message: 'This form is not connected yet.' });
            return;
        }

        const form = event.currentTarget;
        setState({ kind: 'sending' });

        void submitCaptureForm(form, `/f/${formId}`, block.props, runtime).then((outcome) => {
            if (outcome.kind === 'redirect') {
                window.location.assign(outcome.href);
                return;
            }
            setState(outcome.kind === 'message' ? { kind: 'done', message: outcome.message } : outcome);
        });
    };

    return { state, onSubmit };
}

function CaptureDone({ message }: { message: string }) {
    return (
        <div
            role="status"
            style={{
                display: 'flex',
                gap: '10px',
                alignItems: 'center',
                padding: '14px 16px',
                borderRadius: '10px',
                background: 'rgba(22,163,74,.08)',
                border: '1px solid rgba(22,163,74,.25)',
                color: 'inherit',
            }}
        >
            <span
                aria-hidden
                style={{
                    display: 'inline-flex',
                    width: '26px',
                    height: '26px',
                    borderRadius: '999px',
                    alignItems: 'center',
                    justifyContent: 'center',
                    background: '#16a34a',
                    color: '#fff',
                    flexShrink: 0,
                }}
            >
                <Check style={{ width: '15px', height: '15px' }} />
            </span>
            <span>{message}</span>
        </div>
    );
}

function CaptureError({ message }: { message: string }) {
    return (
        <p role="alert" style={{ margin: 0, fontSize: '13px', color: '#dc2626' }}>
            {message}
        </p>
    );
}

/** Label for a submit button that reflects an in-flight request. */
function submitText(state: CaptureState, label: string): string {
    return state.kind === 'sending' ? 'Sending…' : label;
}

/**
 * The two anti-spam inputs the public form endpoint scores: an off-screen
 * honeypot a person never fills, and the moment the form was rendered. The
 * timestamp is written after hydration, so a page served from cache still
 * reports when the visitor actually saw it.
 */
function AntiSpamFields({ definition }: { definition: PublicFormDefinition | null }) {
    const [renderedAt, setRenderedAt] = useState('');

    useEffect(() => {
        setRenderedAt(String(Math.floor(Date.now() / 1000)));
    }, []);

    const honeypot = definition?.honeypot ?? '_hp';
    const timing = definition?.timing ?? '_ts';

    return (
        <>
            <div aria-hidden style={{ position: 'absolute', left: '-10000px', width: '1px', height: '1px', overflow: 'hidden' }}>
                <label>
                    Leave this empty
                    <input type="text" name={honeypot} tabIndex={-1} autoComplete="off" defaultValue="" />
                </label>
            </div>
            <input type="hidden" name={timing} value={renderedAt} />
        </>
    );
}

const FIELD_AUTOCOMPLETE: Record<string, string> = {
    first_name: 'given-name',
    last_name: 'family-name',
    email: 'email',
    phone: 'tel',
    company: 'organization',
    address: 'street-address',
    country: 'country-name',
};

const FIELD_INPUT_TYPE: Record<string, string> = {
    email: 'email',
    phone: 'tel',
    date: 'date',
    time: 'time',
    url: 'url',
    number: 'number',
};

/** A default pair of fields for a form with no definition to draw from. */
const FALLBACK_FIELDS: PublicFormField[] = [
    { name: 'first_name', label: 'First name', type: 'first_name', placeholder: null, helpText: null, options: [], required: true, hidden: false, defaultValue: null, columnSpan: null },
    { name: 'email', label: 'Email', type: 'email', placeholder: null, helpText: null, options: [], required: true, hidden: false, defaultValue: null, columnSpan: null },
];

const fieldLabel: CSSProperties = { fontSize: '13px', fontWeight: 600 };

/** One field of a connected form, as the form builder defined it. */
function FormFieldInput({ field, inline }: { field: PublicFormField; inline: boolean }) {
    const id = useId();

    if (field.hidden || field.type === 'hidden') {
        return <input type="hidden" name={field.name} value={field.defaultValue ?? ''} />;
    }

    if (field.type === 'divider') {
        return <hr style={{ gridColumn: '1 / -1', border: 0, borderTop: '1px solid var(--color-border, #e2e8f0)', margin: '4px 0', width: '100%' }} />;
    }

    if (field.type === 'heading') {
        return (
            <div style={{ gridColumn: '1 / -1', textAlign: 'left', paddingTop: '6px' }}>
                <h3 style={{ margin: 0, fontSize: '17px', fontWeight: 700 }}>{field.label}</h3>
                {field.helpText && <p style={{ margin: '4px 0 0', fontSize: '13px', opacity: 0.75 }}>{field.helpText}</p>}
            </div>
        );
    }

    if (field.type === 'paragraph') {
        return (
            <div style={{ gridColumn: '1 / -1', textAlign: 'left', fontSize: '14px', opacity: 0.85 }}>
                {field.label && <strong style={{ display: 'block', marginBottom: '2px' }}>{field.label}</strong>}
                {field.helpText && <p style={{ margin: 0 }}>{field.helpText}</p>}
            </div>
        );
    }

    const span = field.columnSpan !== null && field.columnSpan > 0 ? Math.min(12, field.columnSpan) : 12;
    const wrapper: CSSProperties = inline
        ? { ...stack('6px'), minWidth: 0, flex: '1 1 200px', textAlign: 'left' }
        : { ...stack('6px'), minWidth: 0, gridColumn: `span ${span} / span ${span}`, textAlign: 'left' };
    const required = field.required ? <span aria-hidden style={{ color: '#dc2626' }}> *</span> : null;
    const help = field.helpText ? <span style={{ fontSize: '12px', opacity: 0.7 }}>{field.helpText}</span> : null;
    const defaultValue = field.defaultValue ?? undefined;

    if (field.type === 'checkbox' || field.type === 'consent' || field.type === 'checkbox_group' || field.type === 'radio') {
        const multiple = field.type === 'checkbox_group' || (field.type === 'checkbox' && field.options.length > 0);

        if ((field.type === 'checkbox' || field.type === 'consent') && field.options.length === 0) {
            // A single checkbox: a consent box or a yes/no.
            return (
                <label style={{ ...wrapper, flexDirection: 'row', alignItems: 'flex-start', gap: '8px', fontSize: '14px' }}>
                    <input type="checkbox" name={field.name} value="1" defaultChecked={defaultValue === '1' || defaultValue === 'true'} required={field.required} style={{ marginTop: '3px' }} />
                    <span>
                        {field.placeholder || field.label}
                        {required}
                        {help && <span style={{ display: 'block' }}>{help}</span>}
                    </span>
                </label>
            );
        }

        return (
            <fieldset style={{ ...wrapper, border: 0, padding: 0, margin: 0 }}>
                <legend style={{ ...fieldLabel, padding: 0, marginBottom: '6px' }}>
                    {field.label}
                    {required}
                </legend>
                {field.options.map((option) => (
                    <label key={option} style={{ display: 'flex', gap: '8px', alignItems: 'center', fontSize: '14px' }}>
                        <input
                            type={multiple ? 'checkbox' : 'radio'}
                            name={multiple ? `${field.name}[]` : field.name}
                            value={option}
                            required={field.required && !multiple}
                            defaultChecked={defaultValue === option}
                        />
                        <span>{option}</span>
                    </label>
                ))}
                {help}
            </fieldset>
        );
    }

    if (field.type === 'yes_no' || field.type === 'rating') {
        const choices = field.type === 'yes_no' ? [{ value: 'yes', label: 'Yes' }, { value: 'no', label: 'No' }] : ['1', '2', '3', '4', '5'].map((value) => ({ value, label: value }));

        return (
            <fieldset style={{ ...wrapper, border: 0, padding: 0, margin: 0 }}>
                <legend style={{ ...fieldLabel, padding: 0, marginBottom: '6px' }}>
                    {field.label}
                    {required}
                </legend>
                <div role="radiogroup" style={{ display: 'flex', gap: field.type === 'rating' ? '10px' : '18px', flexWrap: 'wrap' }}>
                    {choices.map((choice) => (
                        <label key={choice.value} style={{ display: 'flex', gap: '6px', alignItems: 'center', fontSize: '14px', cursor: 'pointer' }}>
                            <input type="radio" name={field.name} value={choice.value} required={field.required} defaultChecked={defaultValue === choice.value} />
                            <span>{field.type === 'rating' ? `${choice.label} ★` : choice.label}</span>
                        </label>
                    ))}
                </div>
                {help}
            </fieldset>
        );
    }

    let control: ReactNode;

    if (field.type === 'textarea') {
        control = (
            <textarea
                id={id}
                name={field.name}
                required={field.required}
                placeholder={field.placeholder ?? undefined}
                defaultValue={defaultValue}
                rows={4}
                style={{ ...fieldStyle, resize: 'vertical' }}
            />
        );
    } else if ((field.type === 'select' || field.type === 'dropdown' || field.type === 'country') && field.options.length > 0) {
        control = (
            <select id={id} name={field.name} required={field.required} defaultValue={defaultValue ?? ''} style={fieldStyle}>
                <option value="" disabled>
                    {field.placeholder ?? 'Choose…'}
                </option>
                {field.options.map((option) => (
                    <option key={option} value={option}>
                        {option}
                    </option>
                ))}
            </select>
        );
    } else {
        control = (
            <input
                id={id}
                name={field.name}
                type={FIELD_INPUT_TYPE[field.type] ?? 'text'}
                required={field.required}
                autoComplete={FIELD_AUTOCOMPLETE[field.type]}
                placeholder={field.placeholder ?? undefined}
                defaultValue={defaultValue}
                style={fieldStyle}
            />
        );
    }

    return (
        <div style={wrapper}>
            <label htmlFor={id} style={fieldLabel}>
                {field.label}
                {required}
            </label>
            {control}
            {help}
        </div>
    );
}

/**
 * Posts to the public form endpoint. The fields are the connected form's own,
 * as built in Forms — the block owns only the layout, the button and what
 * happens after a successful submit.
 */
export function FormBlock({ block, mode, onPropsChange }: BlockComponentProps) {
    const formId = optionalStr(block.props, 'formId');
    const layout = oneOf(block.props, 'layout', ['stacked', 'inline'] as const, 'stacked');
    const runtime = useFunnel();
    const definition = formId ? (runtime?.forms[formId] ?? null) : null;
    const { state, onSubmit } = useCaptureSubmit(block, mode);
    // Answers so far, for fields shown or hidden by earlier answers.
    const [answers, setAnswers] = useState<FormAnswers>({});

    if (mode !== 'editable') {
        // A visitor never sees a builder instruction: an unconnected form, or
        // one whose form was deactivated or deleted, renders nothing at all.
        if (!formId || (runtime !== null && definition === null)) {
            return null;
        }
    }

    if (state.kind === 'done') {
        return <CaptureDone message={state.message} />;
    }

    const inline = layout === 'inline';
    const fields = definition && definition.fields.length > 0 ? definition.fields : FALLBACK_FIELDS;

    return (
        <form
            method="post"
            action={formId ? `/f/${formId}` : undefined}
            onSubmit={onSubmit}
            onChange={(event) => setAnswers(readAnswers(event.currentTarget))}
            aria-busy={state.kind === 'sending'}
            data-fnl-preview-next={runtime?.templatePreview ? optionalStr(block.props, 'previewNextHref') ?? undefined : undefined}
            style={
                inline
                    ? { position: 'relative', display: 'flex', gap: '10px', flexWrap: 'wrap', alignItems: 'flex-end' }
                    : { position: 'relative', display: 'grid', gridTemplateColumns: 'repeat(12, minmax(0, 1fr))', gap: '12px' }
            }
        >
            {mode === 'editable' && !formId && (
                <p style={{ gridColumn: '1 / -1', margin: 0, fontSize: '12px', opacity: 0.7 }}>
                    Preview fields — connect a form in the settings to use its real fields.
                </p>
            )}
            {fields
                .filter((field) => mode === 'editable' || fieldVisible(field, answers))
                .map((field) => (
                    <FormFieldInput key={field.name} field={field} inline={inline} />
                ))}
            <AntiSpamFields definition={definition} />
            {optionalStr(block.props, 'consentText') && <EditableText as="p" multiline value={str(block.props, 'consentText', '')} mode={mode} ariaLabel="Edit consent text" style={{ gridColumn: '1 / -1', margin: 0, fontSize: '14px', color: '#85847f', ...(inline ? { flexBasis: '100%' } : {}) }} {...(mode === 'editable' && onPropsChange ? { onChange: (next: string) => onPropsChange({ consentText: next }) } : {})} />}
            <button
                type="submit"
                style={{
                    ...submitStyle,
                    opacity: state.kind === 'sending' ? 0.7 : 1,
                    ...(inline ? {} : { gridColumn: '1 / -1', justifySelf: 'start' }),
                }}
            >
                {mode === 'editable' && onPropsChange ? (
                    <EditableText value={str(block.props, 'submitLabel', 'Submit')} mode={mode} placeholder="Button label" ariaLabel="Edit submit label" onChange={(next) => onPropsChange({ submitLabel: next })} />
                ) : (
                    submitText(state, str(block.props, 'submitLabel', 'Submit'))
                )}
            </button>
            {state.kind === 'error' && (
                <div style={inline ? { flexBasis: '100%' } : { gridColumn: '1 / -1' }}>
                    <CaptureError message={state.message} />
                </div>
            )}
        </form>
    );
}

export function EmailOptinBlock({ block, mode, onPropsChange }: BlockComponentProps) {
    const formId = optionalStr(block.props, 'formId');
    const runtime = useFunnel();
    const consent = optionalStr(block.props, 'consentText');
    const inline = oneOf(block.props, 'layout', ['inline', 'stacked'] as const, 'inline') === 'inline';
    const label = str(block.props, 'label', 'Email address');
    const showLabel = bool(block.props, 'showLabel', false);
    const fieldName = str(block.props, 'fieldName', 'email') || 'email';
    const required = bool(block.props, 'required', true);
    const id = useId();
    const { state, onSubmit } = useCaptureSubmit(block, mode);

    if (state.kind === 'done') {
        return <CaptureDone message={state.message} />;
    }

    return (
        <form
            method="post"
            action={formId ? `/f/${formId}` : undefined}
            onSubmit={onSubmit}
            aria-busy={state.kind === 'sending'}
            data-fnl-preview-next={runtime?.templatePreview ? optionalStr(block.props, 'previewNextHref') ?? undefined : undefined}
            style={{ ...stack('8px'), position: 'relative' }}
        >
            {showLabel ? (
                <label htmlFor={id} style={{ ...fieldLabel, textAlign: 'left' }}>
                    {mode === 'editable' && onPropsChange ? (
                        <EditableText
                            value={label}
                            mode={mode}
                            placeholder="Field label"
                            ariaLabel="Edit email field label"
                            onChange={(next) => onPropsChange({ label: next })}
                        />
                    ) : (
                        label
                    )}
                    {required && <span aria-hidden style={{ color: '#dc2626' }}> *</span>}
                </label>
            ) : (
                <label htmlFor={id} style={{ position: 'absolute', width: 1, height: 1, overflow: 'hidden' }}>
                    {label}
                </label>
            )}
            <div style={{ display: 'flex', gap: '10px', flexDirection: inline ? 'row' : 'column', flexWrap: 'wrap' }}>
                <input
                    id={id}
                    name={fieldName}
                    type="email"
                    required={required}
                    autoComplete="email"
                    placeholder={str(block.props, 'placeholder', 'you@example.com')}
                    style={{ ...fieldStyle, flex: inline ? '1 1 220px' : undefined }}
                />
                <button type="submit" style={{ ...submitStyle, opacity: state.kind === 'sending' ? 0.7 : 1 }}>
                    {mode === 'editable' && onPropsChange ? (
                    <EditableText value={str(block.props, 'submitLabel', 'Subscribe')} mode={mode} placeholder="Button label" ariaLabel="Edit submit label" onChange={(next) => onPropsChange({ submitLabel: next })} />
                ) : (
                    submitText(state, str(block.props, 'submitLabel', 'Subscribe'))
                )}
                </button>
            </div>
            {consent && (
                mode === 'editable' && onPropsChange ? (
                    <EditableText as="p" multiline value={consent} mode={mode} placeholder="Consent text" ariaLabel="Edit consent text" style={{ margin: 0, fontSize: '12px', opacity: 0.75, textAlign: 'left' }} onChange={(next) => onPropsChange({ consentText: next })} />
                ) : (
                    <p style={{ margin: 0, fontSize: '12px', opacity: 0.75, textAlign: 'left' }}>{consent}</p>
                )
            )}
            <AntiSpamFields definition={formId ? (runtime?.forms[formId] ?? null) : null} />
            {state.kind === 'error' && <CaptureError message={state.message} />}
        </form>
    );
}

/** Dialling codes for the default-country setting on the phone block. */
const DIAL_CODES: Record<string, string> = {
    US: '1', CA: '1', GB: '44', IE: '353', AU: '61', NZ: '64', IN: '91', PK: '92', BD: '880', LK: '94',
    AE: '971', SA: '966', QA: '974', KW: '965', BH: '973', OM: '968', EG: '20', JO: '962', LB: '961', TR: '90',
    DE: '49', FR: '33', ES: '34', IT: '39', NL: '31', BE: '32', CH: '41', AT: '43', SE: '46', NO: '47',
    DK: '45', FI: '358', PL: '48', PT: '351', GR: '30', IL: '972', ZA: '27', NG: '234', KE: '254', GH: '233',
    SG: '65', MY: '60', ID: '62', PH: '63', TH: '66', VN: '84', JP: '81', KR: '82', CN: '86', HK: '852',
    BR: '55', MX: '52', AR: '54', CL: '56', CO: '57', PE: '51',
};

export function PhoneInputBlock({ block, mode, onPropsChange }: BlockComponentProps) {
    const formId = optionalStr(block.props, 'formId');
    const runtime = useFunnel();
    const requireConsent = bool(block.props, 'requireConsent', true);
    const consentText = str(block.props, 'consentText', 'I agree to receive text messages. Reply STOP to opt out.');
    const label = str(block.props, 'label', 'Phone number');
    const showLabel = bool(block.props, 'showLabel', true);
    const fieldName = str(block.props, 'fieldName', 'phone') || 'phone';
    const required = bool(block.props, 'required', true);
    const inline = oneOf(block.props, 'layout', ['inline', 'stacked'] as const, 'inline') === 'inline';
    const dialCode = DIAL_CODES[str(block.props, 'defaultCountry', '').toUpperCase()] ?? null;
    const id = useId();
    const { state, onSubmit } = useCaptureSubmit(block, mode);

    if (state.kind === 'done') {
        return <CaptureDone message={state.message} />;
    }

    return (
        <form
            method="post"
            action={formId ? `/f/${formId}` : undefined}
            onSubmit={onSubmit}
            aria-busy={state.kind === 'sending'}
            data-fnl-preview-next={runtime?.templatePreview ? optionalStr(block.props, 'previewNextHref') ?? undefined : undefined}
            style={{ ...stack('8px'), position: 'relative' }}
        >
            {showLabel ? (
                <label htmlFor={id} style={{ ...fieldLabel, textAlign: 'left' }}>
                    {mode === 'editable' && onPropsChange ? (
                        <EditableText
                            value={label}
                            mode={mode}
                            placeholder="Phone field label"
                            ariaLabel="Edit phone field label"
                            onChange={(next) => onPropsChange({ label: next })}
                        />
                    ) : (
                        label
                    )}
                    {required && <span aria-hidden style={{ color: '#dc2626' }}> *</span>}
                </label>
            ) : (
                <label htmlFor={id} style={{ position: 'absolute', width: 1, height: 1, overflow: 'hidden' }}>
                    {label}
                </label>
            )}
            <div style={{ display: 'flex', gap: '10px', flexDirection: inline ? 'row' : 'column', flexWrap: 'wrap' }}>
                <input
                    id={id}
                    name={fieldName}
                    type="tel"
                    required={required}
                    autoComplete="tel"
                    placeholder={str(block.props, 'placeholder', '+1 555 000 0000')}
                    // The default country pre-fills its dialling code, so a visitor
                    // only types the local number.
                    defaultValue={dialCode ? `+${dialCode} ` : undefined}
                    style={{ ...fieldStyle, flex: inline ? '1 1 200px' : undefined }}
                />
                <button type="submit" style={{ ...submitStyle, opacity: state.kind === 'sending' ? 0.7 : 1 }}>
                    {mode === 'editable' && onPropsChange ? (
                    <EditableText value={str(block.props, 'submitLabel', 'Text me')} mode={mode} placeholder="Button label" ariaLabel="Edit submit label" onChange={(next) => onPropsChange({ submitLabel: next })} />
                ) : (
                    submitText(state, str(block.props, 'submitLabel', 'Text me'))
                )}
                </button>
            </div>
            {requireConsent && (
                <label style={{ display: 'flex', gap: '8px', alignItems: 'flex-start', fontSize: '12px', textAlign: 'left' }}>
                    <input type="checkbox" name="sms_consent" required />
                    <span>
                        {mode === 'editable' && onPropsChange ? (
                            <EditableText
                                value={consentText}
                                mode={mode}
                                placeholder="SMS consent text"
                                ariaLabel="Edit SMS consent text"
                                onChange={(next) => onPropsChange({ consentText: next })}
                            />
                        ) : (
                            consentText
                        )}
                    </span>
                </label>
            )}
            <AntiSpamFields definition={formId ? (runtime?.forms[formId] ?? null) : null} />
            {state.kind === 'error' && <CaptureError message={state.message} />}
        </form>
    );
}

/* -------------------------------------------------------------------------- */
/* Commerce                                                                    */
/* -------------------------------------------------------------------------- */

/* -------------------------------------------------------------------------- */
/* Checkout                                                                    */
/* -------------------------------------------------------------------------- */

const checkoutCard: CSSProperties = {
    border: '1px solid var(--color-border, #e2e8f0)',
    borderRadius: '14px',
    background: '#fff',
    color: '#0f172a',
    padding: '20px',
};

const checkoutLabel: CSSProperties = { fontSize: '13px', fontWeight: 600, color: '#334155' };

function OrderSummary({
    lines,
    currency,
    breakdown,
}: {
    lines: { name: string; price: number; compareAt: number | null }[];
    currency: string;
    breakdown?: CheckoutQuote | null;
}) {
    const total = breakdown?.total ?? lines.reduce((sum, line) => sum + line.price, 0);
    const row = (label: string, amount: string, muted = false) => (
        <div style={{ display: 'flex', justifyContent: 'space-between', gap: '12px', fontSize: '14px', color: muted ? '#64748b' : undefined }}>
            <span>{label}</span>
            <span style={{ whiteSpace: 'nowrap' }}>{amount}</span>
        </div>
    );

    return (
        <div style={{ ...stack('10px'), textAlign: 'left' }}>
            <span style={{ ...checkoutLabel, textTransform: 'uppercase', letterSpacing: '.06em', color: '#64748b' }}>Order summary</span>
            {(breakdown
                ? breakdown.lines.map((line) => ({ name: line.quantity > 1 ? `${line.name} × ${line.quantity}` : line.name, price: line.total, compareAt: null }))
                : lines
            ).map((line, index) => (
                <div key={`${line.name}-${index}`} style={{ display: 'flex', justifyContent: 'space-between', gap: '12px', fontSize: '15px' }}>
                    <span>{line.name}</span>
                    <span style={{ whiteSpace: 'nowrap' }}>
                        {line.compareAt !== null && <s style={{ color: '#94a3b8', marginRight: '6px' }}>{formatMoney(line.compareAt, currency)}</s>}
                        {formatMoney(line.price, currency)}
                    </span>
                </div>
            ))}
            {breakdown && breakdown.discount > 0 && row(`Discount${breakdown.coupon ? ` (${breakdown.coupon.code})` : ''}`, `−${formatMoney(breakdown.discount, currency)}`, true)}
            {breakdown && breakdown.shipping > 0 && row('Shipping', formatMoney(breakdown.shipping, currency), true)}
            {breakdown && breakdown.tax > 0 && row(breakdown.tax_inclusive ? `Includes ${breakdown.tax_label}` : breakdown.tax_label, formatMoney(breakdown.tax, currency), true)}
            <div style={{ display: 'flex', justifyContent: 'space-between', borderTop: '1px solid #e2e8f0', paddingTop: '10px', fontWeight: 700, fontSize: '17px' }}>
                <span>Total</span>
                <span>{formatMoney(total, currency)}</span>
            </div>
            {breakdown?.exchange_rate && breakdown.source_currency !== breakdown.currency && (
                <small style={{ color: '#64748b' }}>
                    Converted from {breakdown.source_currency} at {breakdown.exchange_rate} on {breakdown.exchange_rate_date}.
                </small>
            )}
        </div>
    );
}

function PayButton({ label, busy, disabled, fullWidth = true }: { label: string; busy: boolean; disabled?: boolean; fullWidth?: boolean }) {
    return (
        <button
            type="submit"
            disabled={busy || disabled}
            aria-busy={busy}
            style={{
                ...submitStyle,
                width: fullWidth ? '100%' : undefined,
                padding: '15px 20px',
                fontSize: '16px',
                display: 'inline-flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '8px',
                opacity: busy || disabled ? 0.7 : 1,
                cursor: busy ? 'progress' : 'pointer',
            }}
        >
            <Lock style={{ width: '16px', height: '16px' }} aria-hidden />
            {busy ? 'Processing payment…' : label}
        </button>
    );
}

const EMPTY_ADDRESS: ShippingAddress = { name: '', line1: '', line2: '', city: '', state: '', postal_code: '', country: '' };

/**
 * The live order form: Stripe's Payment Element in deferred-intent mode.
 *
 * Totals come from the server's quote endpoint, re-asked whenever the buyer
 * ticks the order bump or applies a coupon, so discount, shipping and tax are
 * the server's numbers. On submit the server creates the checkout from its own
 * prices; if its total differs from what was shown (a price changed since the
 * page loaded) the element is updated before the card is confirmed, so the
 * buyer is never charged an amount they were not shown.
 */
function LiveCheckout({
    commerce,
    products,
    bumpProduct,
    bumpHeadline,
    allowCoupon,
    submitLabel,
    showSummary,
    saveCard,
    nextStepUrl,
    collectPhone,
    collectAddress,
}: {
    commerce: CommerceRuntime;
    products: CommerceProduct[];
    bumpProduct: CommerceProduct | null;
    bumpHeadline: string;
    allowCoupon: boolean;
    submitLabel: string;
    showSummary: boolean;
    saveCard: boolean;
    nextStepUrl: string | null;
    collectPhone: boolean;
    collectAddress: boolean;
}) {
    const mountRef = useRef<HTMLDivElement>(null);
    const addressRef = useRef<HTMLDivElement>(null);
    const [phone, setPhone] = useState('');
    const phoneId = useId();
    const stripeRef = useRef<{ stripe: StripeLike; elements: StripeElements } | null>(null);
    const [email, setEmail] = useState('');
    const [loading, setLoading] = useState(true);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [paid, setPaid] = useState(false);
    const emailId = useId();
    const couponId = useId();
    const [bumpChecked, setBumpChecked] = useState(false);
    const [couponInput, setCouponInput] = useState('');
    const [coupon, setCoupon] = useState<string | null>(null);
    const [couponError, setCouponError] = useState<string | null>(null);
    const [quote, setQuote] = useState<CheckoutQuote | null>(null);
    const [shipping, setShipping] = useState<ShippingAddress>(EMPTY_ADDRESS);

    const currency = quote?.currency ?? commerce.checkoutCurrency;
    const initialTotal = quote?.total ?? products.reduce((sum, product) => sum + product.price, 0);
    // A subscription is checked out on its own; a trial only saves the card.
    const recurring = products.length === 1 ? (products[0]?.recurring ?? null) : null;
    const elementsMode = recurring === null ? 'payment' : recurring.trialDays !== null ? 'setup' : 'subscription';
    const displayTotal = quote?.total ?? initialTotal;
    const needsShipping = quote?.requires_shipping ?? (products.some((product) => product.requiresShipping) || (bumpChecked && bumpProduct?.requiresShipping === true));

    const cart = (couponCode: string | null): CartRequest => ({
        items: products.map((product) => ({ product_id: product.id, quantity: 1 })),
        bump_product_ids: bumpChecked && bumpProduct ? [bumpProduct.id] : [],
        coupon_code: couponCode,
        currency: commerce.checkoutCurrency,
    });

    // Re-price whenever the bump or coupon changes. A coupon that stops
    // applying (for example after unticking the product it was for) is dropped.
    useEffect(() => {
        let cancelled = false;
        quoteCart(commerce, cart(coupon))
            .then((next) => {
                if (!cancelled) {
                    setQuote(next);
                    stripeRef.current?.elements.update({ amount: next.total });
                }
            })
            .catch((failure: unknown) => {
                if (cancelled) {
                    return;
                }
                if (coupon !== null) {
                    setCoupon(null);
                    setCouponError(failure instanceof Error ? failure.message : 'That code is not valid.');
                } else {
                    setError(failure instanceof Error ? failure.message : 'Prices could not be loaded. Please refresh.');
                    setLoading(false);
                }
            });
        return () => {
            cancelled = true;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [bumpChecked, coupon]);

    useEffect(() => {
        let cancelled = false;
        let element: StripeElement | null = null;
        let address: StripeElement | null = null;

        if (!commerce.publishableKey || !commerce.stripeAccount || quote === null) {
            return;
        }

        loadStripe(commerce.publishableKey, commerce.stripeAccount)
            .then((stripe) => {
                if (cancelled || !mountRef.current) {
                    return;
                }
                const elements = stripe.elements({
                    mode: elementsMode,
                    ...(elementsMode === 'setup' ? {} : { amount: initialTotal }),
                    currency: currency.toLowerCase(),
                    ...(saveCard && elementsMode === 'payment' ? { setupFutureUsage: 'off_session' } : {}),
                    appearance: {
                        theme: 'stripe',
                        variables: { colorPrimary: '#4f46e5', borderRadius: '8px', fontFamily: 'Inter, system-ui, sans-serif' },
                    },
                });
                element = elements.create('payment', { layout: 'tabs' });
                element.mount(mountRef.current);
                // Stripe's own address form: it validates per country and hands
                // the address to the payment as billing details on confirm.
                if (collectAddress && addressRef.current) {
                    address = elements.create('address', { mode: 'billing' });
                    address.mount(addressRef.current);
                }
                stripeRef.current = { stripe, elements };
                setLoading(false);
            })
            .catch((failure: unknown) => {
                if (!cancelled) {
                    setError(failure instanceof Error ? failure.message : 'The payment form could not load.');
                    setLoading(false);
                }
            });

        return () => {
            cancelled = true;
            element?.destroy();
            address?.destroy();
        };
        // The first server quote establishes Stripe's immutable currency.
        // Later quote changes update only the amount in the quote effect above.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [commerce.publishableKey, commerce.stripeAccount, quote === null, saveCard, collectAddress, elementsMode]);

    const applyCoupon = (): void => {
        setCouponError(null);
        const code = couponInput.trim();
        if (code === '') {
            return;
        }
        quoteCart(commerce, cart(code))
            .then((next) => {
                setCoupon(code);
                setQuote(next);
                stripeRef.current?.elements.update({ amount: next.total });
            })
            .catch((failure: unknown) => setCouponError(failure instanceof Error ? failure.message : 'That code is not valid.'));
    };

    const pay = async (event: FormEvent<HTMLFormElement>): Promise<void> => {
        event.preventDefault();
        const ready = stripeRef.current;
        if (!ready || busy) {
            return;
        }

        setBusy(true);
        setError(null);

        try {
            const submitted = await ready.elements.submit();
            if (submitted.error) {
                throw new Error(submitted.error.message ?? 'Check your card details.');
            }

            const checkout = await createCheckout(commerce, email, cart(coupon), needsShipping ? shipping : null);

            if (elementsMode !== 'setup' && checkout.total !== displayTotal) {
                ready.elements.update({ amount: checkout.total });
                const resubmitted = await ready.elements.submit();
                if (resubmitted.error) {
                    throw new Error(resubmitted.error.message ?? 'Check your card details.');
                }
            }

            const intent = await waitForCheckoutIntent(commerce, checkout);
            const billingPhone = collectPhone && phone.trim() !== '' ? { payment_method_data: { billing_details: { phone: phone.trim() } } } : {};
            const result =
                intent.intentType === 'setup'
                    ? await ready.stripe.confirmSetup({
                          elements: ready.elements,
                          clientSecret: intent.clientSecret,
                          redirect: 'if_required',
                          confirmParams: { return_url: nextStepUrl ?? window.location.href, ...billingPhone },
                      })
                    : await ready.stripe.confirmPayment({
                          elements: ready.elements,
                          clientSecret: intent.clientSecret,
                          redirect: 'if_required',
                          confirmParams: { return_url: nextStepUrl ?? window.location.href, receipt_email: email, ...billingPhone },
                      });

            if (result.error) {
                throw new Error(result.error.message ?? 'Your payment was not completed.');
            }

            // Remembered for this tab so an upsell step can add to the order.
            rememberCheckout(commerce, checkout);

            if (nextStepUrl) {
                window.location.assign(nextStepUrl);
                return;
            }
            setPaid(true);
        } catch (failure) {
            setError(failure instanceof Error ? failure.message : 'Your payment was not completed.');
        } finally {
            setBusy(false);
        }
    };

    if (paid) {
        return <CaptureDone message="Payment received — thank you! Your receipt is on its way." />;
    }

    const addressField = (key: keyof ShippingAddress, label: string, options: { required?: boolean; autoComplete?: string; maxLength?: number } = {}) => (
        <label style={{ ...stack('4px'), textAlign: 'left' }}>
            <span style={{ fontSize: '12px', color: '#475569' }}>{label}</span>
            <input
                required={options.required ?? false}
                autoComplete={options.autoComplete}
                maxLength={options.maxLength}
                value={shipping[key] ?? ''}
                onChange={(changeEvent) => setShipping({ ...shipping, [key]: key === 'country' ? changeEvent.currentTarget.value.toUpperCase() : changeEvent.currentTarget.value })}
                style={fieldStyle}
            />
        </label>
    );

    return (
        <form data-fnl-checkout onSubmit={(formEvent) => void pay(formEvent)} style={{ ...checkoutCard, ...stack('18px') }}>
            {showSummary && <OrderSummary lines={products} currency={quote?.currency ?? products[0]?.currency ?? currency} breakdown={quote} />}
            {bumpProduct && (
                <label style={{ display: 'flex', gap: '12px', alignItems: 'flex-start', padding: '14px', border: '2px dashed #f59e0b', borderRadius: '10px', background: '#fffbeb', textAlign: 'left', cursor: 'pointer' }}>
                    <input type="checkbox" checked={bumpChecked} onChange={(changeEvent) => setBumpChecked(changeEvent.currentTarget.checked)} style={{ marginTop: '4px', width: '18px', height: '18px' }} />
                    <span style={stack('4px')}>
                        <strong style={{ fontSize: '15px' }}>{bumpHeadline || `Yes! Add ${bumpProduct.name} to my order`}</strong>
                        <span style={{ fontSize: '14px', color: '#475569' }}>
                            {bumpProduct.description ? `${bumpProduct.description} ` : ''}
                            <strong>{formatMoney(bumpProduct.price, bumpProduct.currency)}</strong>
                        </span>
                    </span>
                </label>
            )}
            <label htmlFor={emailId} style={{ ...stack('6px'), textAlign: 'left' }}>
                <span style={checkoutLabel}>Email for your receipt</span>
                <input id={emailId} type="email" required autoComplete="email" value={email} onChange={(changeEvent) => setEmail(changeEvent.currentTarget.value)} placeholder="you@example.com" style={fieldStyle} />
            </label>
            {collectPhone && (
                <label htmlFor={phoneId} style={{ ...stack('6px'), textAlign: 'left' }}>
                    <span style={checkoutLabel}>Phone</span>
                    <input id={phoneId} type="tel" required autoComplete="tel" value={phone} onChange={(changeEvent) => setPhone(changeEvent.currentTarget.value)} style={fieldStyle} />
                </label>
            )}
            {needsShipping && (
                <fieldset style={{ ...stack('8px'), border: 0, padding: 0, margin: 0, textAlign: 'left' }}>
                    <legend style={{ ...checkoutLabel, marginBottom: '6px' }}>Shipping address</legend>
                    {addressField('name', 'Full name', { autoComplete: 'shipping name' })}
                    {addressField('line1', 'Address', { required: true, autoComplete: 'shipping address-line1' })}
                    {addressField('line2', 'Apartment, suite (optional)', { autoComplete: 'shipping address-line2' })}
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px' }}>
                        {addressField('city', 'City', { required: true, autoComplete: 'shipping address-level2' })}
                        {addressField('state', 'State / region', { autoComplete: 'shipping address-level1' })}
                        {addressField('postal_code', 'Postal code', { required: true, autoComplete: 'shipping postal-code' })}
                        {addressField('country', 'Country code (e.g. US)', { required: true, autoComplete: 'shipping country', maxLength: 2 })}
                    </div>
                </fieldset>
            )}
            {collectAddress && (
                <div style={{ ...stack('6px'), textAlign: 'left' }}>
                    <span style={checkoutLabel}>Billing address</span>
                    <div ref={addressRef} />
                </div>
            )}
            {allowCoupon && (
                <div style={{ ...stack('6px'), textAlign: 'left' }}>
                    <label htmlFor={couponId} style={checkoutLabel}>
                        Coupon code
                    </label>
                    <div style={{ display: 'flex', gap: '8px' }}>
                        <input id={couponId} value={couponInput} onChange={(changeEvent) => setCouponInput(changeEvent.currentTarget.value)} style={{ ...fieldStyle, flex: 1 }} autoComplete="off" />
                        <button type="button" onClick={applyCoupon} style={{ ...submitStyle, padding: '10px 14px', background: '#334155' }}>
                            Apply
                        </button>
                    </div>
                    {coupon && quote?.coupon && <span role="status" style={{ fontSize: '13px', color: '#047857' }}>Code {quote.coupon.code} applied.</span>}
                    {couponError && <span role="alert" style={{ fontSize: '13px', color: '#b91c1c' }}>{couponError}</span>}
                </div>
            )}
            <div style={{ ...stack('6px'), textAlign: 'left' }}>
                <span style={checkoutLabel}>Payment</span>
                <div ref={mountRef} style={{ minHeight: loading ? '160px' : undefined }}>
                    {loading && !error && (
                        <div style={{ height: '160px', borderRadius: '8px', background: '#f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#64748b', fontSize: '14px' }}>
                            Loading secure payment form…
                        </div>
                    )}
                </div>
            </div>
            {error && <CaptureError message={error} />}
            {recurring !== null && (
                <p style={{ margin: 0, fontSize: '14px', color: '#475569', textAlign: 'center' }}>
                    {recurring.trialDays !== null
                        ? `Free for ${recurring.trialDays} days, then ${formatMoney(displayTotal, currency)} ${formatInterval(recurring)}. Cancel any time.`
                        : `${formatMoney(displayTotal, currency)} ${formatInterval(recurring)}, renewing automatically until cancelled.`}
                </p>
            )}
            <PayButton
                label={
                    recurring?.trialDays
                        ? `Start free trial`
                        : `${submitLabel} · ${formatMoney(displayTotal, currency)}${recurring ? ` ${formatInterval(recurring)}` : ''}`
                }
                busy={busy}
                disabled={loading}
            />
            <p style={{ margin: 0, fontSize: '12px', color: '#64748b', textAlign: 'center', lineHeight: 1.5 }}>
                Payments are processed securely by Stripe.
                {saveCard && ' Your card is saved with Stripe so you can add offers to this order in one click.'}
            </p>
        </form>
    );
}

/** What the checkout looks like on the canvas: the real layout, no live form. */
function CheckoutPreview({
    submitLabel,
    showSummary,
    note,
    collectPhone,
    collectAddress,
    templatePreview = false,
    previewNextHref,
}: {
    submitLabel: string;
    showSummary: boolean;
    note: string;
    collectPhone: boolean;
    collectAddress: boolean;
    templatePreview?: boolean;
    previewNextHref?: string;
}) {
    return (
        <div style={{ ...checkoutCard, ...stack('18px') }}>
            {showSummary && (templatePreview ? <div style={{ ...stack('8px'), textAlign: 'left' }}><strong>Your selected product</strong><span style={{ fontSize: '14px', color: '#64748b' }}>The connected catalog supplies the description, price and currency.</span></div> : (
                <OrderSummary
                    lines={[
                        { name: 'Your product', price: 4700, compareAt: 9700 },
                        { name: 'Bonus', price: 0, compareAt: null },
                    ]}
                    currency="USD"
                />
            ))}
            <div style={{ ...stack('6px'), textAlign: 'left' }}>
                <span style={checkoutLabel}>Email for your receipt</span>
                <div style={{ ...fieldStyle, color: '#94a3b8' }}>you@example.com</div>
            </div>
            {collectPhone && (
                <div style={{ ...stack('6px'), textAlign: 'left' }}>
                    <span style={checkoutLabel}>Phone</span>
                    <div style={{ ...fieldStyle, color: '#94a3b8' }}>+1 555 000 0000</div>
                </div>
            )}
            {collectAddress && (
                <div style={{ ...stack('6px'), textAlign: 'left' }}>
                    <span style={checkoutLabel}>Billing address</span>
                    <div style={{ ...fieldStyle, color: '#94a3b8' }}>Country · Address · City · Postal code</div>
                </div>
            )}
            <div style={{ ...stack('6px'), textAlign: 'left' }}>
                <span style={checkoutLabel}>Payment</span>
                <div style={{ ...fieldStyle, color: '#94a3b8', display: 'flex', justifyContent: 'space-between' }}>
                    <span>Card number</span>
                    <span>MM / YY · CVC</span>
                </div>
            </div>
            {templatePreview ? <button type="button" data-fnl-preview-next={previewNextHref ?? '#preview-complete'} style={{ ...submitStyle, width: '100%', whiteSpace: 'normal' }}>Try checkout flow</button> : <PayButton label={submitLabel} busy={false} disabled />}
            <p style={{ margin: 0, fontSize: '12px', color: '#64748b', textAlign: 'center' }}>{note}</p>
        </div>
    );
}

export function CheckoutBlock({ block, mode }: BlockComponentProps) {
    const runtime = useFunnel();
    const commerce = runtime?.commerce ?? null;
    const submitLabel = str(block.props, 'submitLabel', 'Complete order');
    const showSummary = bool(block.props, 'showOrderSummary', true);
    const saveCard = bool(block.props, 'saveCardForUpsells', true);
    const collectPhone = bool(block.props, 'collectPhone', false);
    const collectAddress = bool(block.props, 'collectBillingAddress', false);
    const productIds = list(block.props, 'productIds').filter((id): id is string => typeof id === 'string');
    const bumpProductId = str(block.props, 'bumpProductId', '');
    const bumpHeadline = str(block.props, 'bumpHeadline', '');
    const allowCoupon = bool(block.props, 'allowCoupon', false);

    if (runtime?.templatePreview) {
        return <CheckoutPreview templatePreview previewNextHref={optionalStr(block.props, 'previewNextHref') ?? undefined} submitLabel={submitLabel} showSummary={showSummary} collectPhone={collectPhone} collectAddress={collectAddress} note="Template preview only. Choose your product and connect Stripe after installation. No payment or delivery takes place here." />;
    }

    if (mode === 'editable') {
        return (
            <CheckoutPreview
                submitLabel={submitLabel}
                showSummary={showSummary}
                collectPhone={collectPhone}
                collectAddress={collectAddress}
                note={
                    productIds.length === 0
                        ? 'Choose the products to sell in this block’s settings. The live card form appears on the published page.'
                        : 'Preview — the live Stripe card form appears on the published page.'
                }
            />
        );
    }

    const products = productIds
        .map((id) => commerce?.products[id])
        .filter((product): product is CommerceProduct => product !== undefined);

    // A visitor gets a plain, calm notice — never the builder's setup
    // instructions. The owner sees why in the checkout block's settings.
    if (!commerce?.ready || products.length === 0) {
        return (
            <div role="status" style={{ ...checkoutCard, textAlign: 'center', color: '#475569' }}>
                <Lock style={{ width: '18px', height: '18px', marginBottom: '6px' }} aria-hidden />
                <p style={{ margin: 0 }}>Checkout is temporarily unavailable. Please try again shortly.</p>
            </div>
        );
    }

    return (
        <LiveCheckout
            commerce={commerce}
            products={products}
            bumpProduct={bumpProductId !== '' && !productIds.includes(bumpProductId) ? (commerce.products[bumpProductId] ?? null) : null}
            bumpHeadline={bumpHeadline}
            allowCoupon={allowCoupon}
            submitLabel={submitLabel}
            showSummary={showSummary}
            saveCard={saveCard}
            nextStepUrl={runtime?.nextStepUrl ?? null}
            collectPhone={collectPhone}
            collectAddress={collectAddress}
        />
    );
}


/**
 * Where a product's buy button sends the visitor: to the checkout on this page
 * when there is one, otherwise on to the next funnel step, where the checkout
 * normally lives.
 */
function buyProduct(nextStepUrl: string | null): void {
    const checkout = document.querySelector('[data-fnl-checkout]');
    if (checkout) {
        checkout.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
    }
    if (nextStepUrl) {
        window.location.assign(nextStepUrl);
    }
}

/** A product from the Commerce catalogue, priced by the server. */
export function ProductBlock({ block, mode, onPropsChange }: BlockComponentProps) {
    const productId = optionalStr(block.props, 'productId');
    const runtime = useFunnel();
    const product = productId ? runtime?.commerce?.products[productId] : undefined;
    const showPrice = bool(block.props, 'showPrice', true);
    const showImage = bool(block.props, 'showImage', true);
    const showDescription = bool(block.props, 'showDescription', true);
    const buttonLabel = str(block.props, 'buttonLabel', 'Buy now');

    if (!product) {
        if (mode !== 'editable') {
            // Unpicked, archived or out of the catalogue: nothing for a visitor.
            return null;
        }
        if (!productId) {
            return <Placeholder label="Product" detail="Pick a product in the properties panel." />;
        }
    }

    const name = product?.name ?? 'Your product';
    const price = product?.price ?? 4700;
    const currency = product?.currency ?? 'USD';
    const compareAt = product?.compareAt ?? null;
    const image = product?.image ?? null;
    const description = product?.description ?? null;

    return (
        <div style={{ ...cardStyle, ...stack('14px'), color: 'var(--fnl-color, #0f172a)', textAlign: 'left' }}>
            {showImage && image && (
                <img
                    src={image}
                    alt={product?.imageAlt ?? name}
                    loading="lazy"
                    style={{ display: 'block', width: '100%', aspectRatio: '4 / 3', objectFit: 'cover', borderRadius: '10px' }}
                />
            )}
            {showImage && !image && mode === 'editable' && (
                <div
                    aria-hidden
                    style={{ width: '100%', aspectRatio: '4 / 3', borderRadius: '10px', background: 'rgba(15,23,42,.06)' }}
                />
            )}
            <h3 style={{ margin: 0, fontSize: '1.25em', fontWeight: 700 }}>{name}</h3>
            {showDescription && description && <p style={{ margin: 0, opacity: 0.8, lineHeight: 1.6 }}>{description}</p>}
            {showPrice && (
                <p style={{ margin: 0, display: 'flex', alignItems: 'baseline', gap: '10px' }}>
                    <span style={{ fontSize: '1.75em', fontWeight: 800 }}>{formatMoney(price, currency)}</span>
                    {compareAt !== null && compareAt > price && (
                        <span style={{ textDecoration: 'line-through', opacity: 0.55 }}>{formatMoney(compareAt, currency)}</span>
                    )}
                </p>
            )}
            <button
                type="button"
                style={{ ...buttonSurface('primary', 'md', true) }}
                onClick={() => {
                    if (mode !== 'editable') {
                        buyProduct(runtime?.nextStepUrl ?? product?.checkoutUrl ?? null);
                    }
                }}
            >
                {mode === 'editable' && onPropsChange ? (
                    <EditableText
                        value={buttonLabel}
                        mode={mode}
                        ariaLabel="Edit buy button label"
                        onChange={(next) => onPropsChange({ buttonLabel: next })}
                    />
                ) : (
                    buttonLabel
                )}
            </button>
            {mode === 'editable' && !product && (
                <p style={{ margin: 0, fontSize: '12px', opacity: 0.6 }}>Preview — the live product appears on the published page.</p>
            )}
        </div>
    );
}

export function PricingTableBlock({ block, mode, onPropsChange }: BlockComponentProps) {
    const plans = recordList(block.props, 'plans');
    const editing = mode === 'editable' && onPropsChange !== undefined;
    const patchPlan = (index: number, change: Record<string, unknown>) =>
        onPropsChange?.({ plans: withItem(plans, index, change) });
    const edit = (index: number, key: string) =>
        editing ? { onChange: (next: string) => patchPlan(index, { [key]: next }) } : {};
    const runtime = useFunnel();
    const columns = Math.max(1, num(block.props, 'columns', 3));

    return (
        <div style={{ ...responsiveGrid(Math.min(columns, plans.length || 1), '250px'), alignItems: 'stretch' }}>
            {plans.map((plan, index) => {
                const highlighted = plan['highlighted'] === true;
                const features = Array.isArray(plan['features']) ? plan['features'] : [];
                // A plan's button follows the same rules as a button block:
                // `action: next_step` (optionally with targetStepId) moves on
                // through the funnel; otherwise buttonUrl is a plain link.
                const planAction = resolveButtonAction(
                    { action: plan['action'] ?? 'url', url: plan['buttonUrl'], targetStepId: plan['targetStepId'], productId: plan['productId'] },
                    runtime,
                );
                const href = mode === 'editable' ? undefined : planAction.kind === 'link' ? planAction.href : undefined;
                return (
                    <div
                        key={`${String(plan['name'] ?? 'plan')}-${index}`}
                        style={{
                            ...cardStyle,
                            position: 'relative',
                            padding: '28px 24px',
                            borderRadius: '16px',
                            borderWidth: highlighted ? '2px' : '1px',
                            borderColor: highlighted ? 'var(--fnl-accent, #4f46e5)' : undefined,
                            boxShadow: highlighted
                                ? '0 20px 45px -12px rgba(79,70,229,.35)'
                                : '0 1px 2px rgba(15,23,42,.04)',
                            transform: highlighted ? 'translateY(-4px)' : undefined,
                            display: 'flex',
                            flexDirection: 'column',
                            gap: '18px',
                            color: 'var(--fnl-color, #0f172a)',
                        }}
                    >
                        {highlighted && (
                            <span
                                style={{
                                    position: 'absolute',
                                    top: '-12px',
                                    left: '50%',
                                    transform: 'translateX(-50%)',
                                    padding: '4px 12px',
                                    borderRadius: '999px',
                                    background: 'var(--fnl-accent, #4f46e5)',
                                    color: '#fff',
                                    fontSize: '.75em',
                                    fontWeight: 600,
                                    letterSpacing: '.02em',
                                    whiteSpace: 'nowrap',
                                }}
                            >
                                Most popular
                            </span>
                        )}
                        <EditableText
                            as="h3"
                            value={String(plan['name'] ?? 'Plan')}
                            mode={mode}
                            placeholder="Plan name"
                            ariaLabel={`Edit plan name ${index + 1}`}
                            style={{ margin: 0, fontSize: '1.0625em', fontWeight: 600 }}
                            {...edit(index, 'name')}
                        />
                        <p style={{ margin: 0, display: 'flex', alignItems: 'baseline', gap: '6px' }}>
                            <EditableText
                                value={String(plan['price'] ?? '')}
                                mode={mode}
                                placeholder="$0"
                                ariaLabel={`Edit plan price ${index + 1}`}
                                style={{ fontSize: '2.5em', fontWeight: 800, letterSpacing: '-.02em', lineHeight: 1 }}
                                {...edit(index, 'price')}
                            />
                            {plan['interval'] || editing ? (
                                <span style={{ opacity: 0.65, fontSize: '.9375em' }}>
                                    /{' '}
                                    <EditableText
                                        value={String(plan['interval'] ?? '')}
                                        mode={mode}
                                        placeholder="month"
                                        ariaLabel={`Edit billing interval ${index + 1}`}
                                        {...edit(index, 'interval')}
                                    />
                                </span>
                            ) : null}
                        </p>
                        <hr style={{ margin: 0, border: 0, borderTop: '1px solid var(--color-border, #e2e8f0)' }} />
                        <ul style={{ margin: 0, padding: 0, listStyle: 'none', ...stack('12px') }}>
                            {features.map((feature, featureIndex) => (
                                <li
                                    key={`${String(feature)}-${featureIndex}`}
                                    style={{ display: 'flex', gap: '10px', alignItems: 'flex-start', fontSize: '.9375em', textAlign: 'left' }}
                                >
                                    <span
                                        aria-hidden
                                        style={{
                                            display: 'inline-flex',
                                            width: '20px',
                                            height: '20px',
                                            borderRadius: '999px',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            flexShrink: 0,
                                            background: 'rgba(79,70,229,.1)',
                                            color: 'var(--fnl-accent, #4f46e5)',
                                        }}
                                    >
                                        <Check style={{ width: '13px', height: '13px' }} />
                                    </span>
                                    <EditableText
                                        value={String(feature)}
                                        mode={mode}
                                        placeholder="Feature"
                                        ariaLabel={`Edit feature ${featureIndex + 1}`}
                                        {...(editing
                                            ? {
                                                  onChange: (next: string) =>
                                                      patchPlan(index, {
                                                          // An emptied feature is removed rather than left as a blank row.
                                                          features: next.trim() === ''
                                                              ? features.filter((_, position) => position !== featureIndex)
                                                              : features.map((entry, position) => (position === featureIndex ? next : entry)),
                                                      }),
                                              }
                                            : {})}
                                    />
                                </li>
                            ))}
                        </ul>
                        <CanvasAddButton
                            mode={mode}
                            label="Add a feature"
                            {...(editing ? { onAdd: () => patchPlan(index, { features: [...features, 'New feature'] }) } : {})}
                        />
                        {mode !== 'editable' && planAction.kind === 'upsell' ? (
                            <UpsellButton productId={planAction.productId} thenHref={planAction.thenHref} fullWidth style={{
                                ...submitStyle,
                                display: 'block',
                                textAlign: 'center',
                                textDecoration: 'none',
                                marginTop: 'auto',
                                padding: '13px 20px',
                                ...(highlighted
                                    ? {}
                                    : {
                                          background: 'transparent',
                                          color: 'var(--fnl-accent, #4f46e5)',
                                          border: '1px solid var(--fnl-accent, #4f46e5)',
                                      }),
                            }}>
                                {String(plan['buttonLabel'] ?? 'Choose plan')}
                            </UpsellButton>
                        ) : (
                            <a
                                href={href ?? '#'}
                                onClick={editing ? (event) => event.preventDefault() : undefined}
                                style={{
                                    ...submitStyle,
                                    display: 'block',
                                    textAlign: 'center',
                                    textDecoration: 'none',
                                    marginTop: 'auto',
                                    padding: '13px 20px',
                                    ...(highlighted
                                        ? {}
                                        : {
                                              background: 'transparent',
                                              color: 'var(--fnl-accent, #4f46e5)',
                                              border: '1px solid var(--fnl-accent, #4f46e5)',
                                          }),
                                }}
                            >
                                <EditableText
                                    value={String(plan['buttonLabel'] ?? 'Choose plan')}
                                    mode={mode}
                                    placeholder="Button label"
                                    ariaLabel={`Edit plan button ${index + 1}`}
                                    {...edit(index, 'buttonLabel')}
                                />
                            </a>
                        )}
                    </div>
                );
            })}
            {editing && (
                <CanvasAddButton
                    mode={mode}
                    label="Add a plan"
                    onAdd={() =>
                        onPropsChange?.({
                            plans: [
                                ...plans,
                                { name: 'New plan', price: '$49', interval: 'month', features: ['A feature'], buttonLabel: 'Choose plan', buttonUrl: null, highlighted: false },
                            ],
                        })
                    }
                />
            )}
        </div>
    );
}

/* -------------------------------------------------------------------------- */
/* Persuasion                                                                  */
/* -------------------------------------------------------------------------- */

function remaining(target: number): { days: number; hours: number; minutes: number; seconds: number } {
    const total = Math.max(0, Math.floor((target - Date.now()) / 1000));
    return {
        days: Math.floor(total / 86400),
        hours: Math.floor((total % 86400) / 3600),
        minutes: Math.floor((total % 3600) / 60),
        seconds: total % 60,
    };
}

export function CountdownBlock({ block, mode }: BlockComponentProps) {
    const mode_ = oneOf(block.props, 'mode', ['fixed', 'evergreen', 'daily'] as const, 'fixed');
    const endsAt = optionalStr(block.props, 'endsAt');
    const durationMinutes = num(block.props, 'durationMinutes', 60);
    const showLabels = bool(block.props, 'showLabels', true);

    const target = useMemo(() => {
        if (mode_ === 'fixed' && endsAt) {
            const parsed = Date.parse(endsAt);
            return Number.isNaN(parsed) ? Date.now() + durationMinutes * 60_000 : parsed;
        }
        // Daily: counts down to the visitor's next local midnight.
        if (mode_ === 'daily') {
            const midnight = new Date();
            midnight.setHours(24, 0, 0, 0);
            return midnight.getTime();
        }
        // Evergreen: each visitor gets their own deadline, remembered across
        // visits, so a refresh does not reset the offer.
        if (mode_ === 'evergreen' && mode !== 'editable' && typeof window !== 'undefined') {
            try {
                const key = `fnl-countdown:${block.id}`;
                const stored = Number(window.localStorage.getItem(key));
                if (stored > 0) {
                    return stored;
                }
                const deadline = Date.now() + durationMinutes * 60_000;
                window.localStorage.setItem(key, String(deadline));
                return deadline;
            } catch {
                // Storage blocked: fall through to a per-page-view deadline.
            }
        }
        return Date.now() + durationMinutes * 60_000;
    }, [mode_, endsAt, durationMinutes, block.id, mode]);

    const [left, setLeft] = useState(() => remaining(target));

    useEffect(() => {
        if (mode === 'editable') {
            return;
        }
        const timer = window.setInterval(() => setLeft(remaining(target)), 1000);
        return () => window.clearInterval(timer);
    }, [target, mode]);

    const expired = left.days + left.hours + left.minutes + left.seconds === 0;
    const expiredAction = oneOf(block.props, 'expiredAction', ['hide', 'message', 'redirect'] as const, 'hide');

    useEffect(() => {
        if (!expired || mode === 'editable' || expiredAction !== 'redirect') {
            return;
        }
        const href = safeHref(block.props['redirectUrl']);
        if (href) {
            window.location.assign(href);
        }
    }, [expired, expiredAction, mode, block.props]);

    if (expired && expiredAction === 'redirect' && mode === 'editable') {
        return <Placeholder label="Countdown" detail="Expired — redirects on the published page." />;
    }
    if (expired && expiredAction === 'hide') {
        return mode === 'editable' ? (
            <Placeholder label="Countdown" detail="Expired — hidden on the published page." />
        ) : null;
    }
    if (expired) {
        return <p style={{ margin: 0 }}>{str(block.props, 'expiredMessage', 'This offer has closed.')}</p>;
    }

    const units: Array<[string, number]> = [
        ['Days', left.days],
        ['Hours', left.hours],
        ['Minutes', left.minutes],
        ['Seconds', left.seconds],
    ];

    return (
        <div
            role="timer"
            aria-live="off"
            style={{ display: 'flex', gap: '10px', flexWrap: 'wrap', justifyContent: 'var(--fnl-text-align, flex-start)' }}
        >
            {units.map(([label, value]) => (
                <div
                    key={label}
                    style={{
                        // Sized in em so a smaller font size on the block shrinks
                        // the tiles too, keeping all four on one line in a
                        // narrow column instead of wrapping "Seconds" alone.
                        minWidth: '4.875em',
                        textAlign: 'center',
                        padding: '.875em .625em',
                        borderRadius: '.875em',
                        background: 'var(--fnl-background-color, rgba(15,23,42,.9))',
                        color: 'var(--fnl-color, #fff)',
                        boxShadow: 'inset 0 -2px 0 rgba(255,255,255,.06), 0 8px 20px -10px rgba(15,23,42,.6)',
                    }}
                >
                    <div
                        style={{
                            fontSize: '2.125em',
                            fontWeight: 800,
                            lineHeight: 1,
                            letterSpacing: '-.02em',
                            fontVariantNumeric: 'tabular-nums',
                        }}
                    >
                        {String(value).padStart(2, '0')}
                    </div>
                    {showLabels && (
                        <div
                            style={{
                                marginTop: '6px',
                                fontSize: '.6875em',
                                textTransform: 'uppercase',
                                letterSpacing: '.08em',
                                opacity: 0.65,
                            }}
                        >
                            {label}
                        </div>
                    )}
                </div>
            ))}
        </div>
    );
}

/** Initials for an avatar with no photo — "Dana Whitfield" becomes "DW". */
function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

export function TestimonialBlock({ block, mode, onPropsChange }: BlockComponentProps) {
    const edit = (key: string) =>
        mode === 'editable' && onPropsChange ? { onChange: (next: string) => onPropsChange({ [key]: next }) } : {};
    const rating = Math.max(0, Math.min(5, num(block.props, 'rating', 5)));
    const avatar = optionalStr(block.props, 'avatarSrc');
    const layout = oneOf(block.props, 'layout', ['card', 'bare'] as const, 'card');
    const author = str(block.props, 'authorName', '');
    const card = layout === 'card';

    return (
        <figure
            style={{
                margin: 0,
                height: card ? '100%' : undefined,
                ...(card
                    ? {
                          ...cardStyle,
                          padding: '26px',
                          borderRadius: '16px',
                          boxShadow: '0 1px 2px rgba(15,23,42,.04), 0 12px 32px -18px rgba(15,23,42,.25)',
                          color: 'var(--fnl-color, #0f172a)',
                      }
                    : {}),
                ...stack('16px'),
            }}
        >
            {rating > 0 && (
                <div
                    aria-label={`${rating} out of 5`}
                    style={{ display: 'flex', gap: '3px', justifyContent: card ? undefined : 'inherit' }}
                >
                    {Array.from({ length: rating }, (_, index) => (
                        <Star
                            key={index}
                            style={{ width: '17px', height: '17px', fill: '#f59e0b', color: '#f59e0b' }}
                            aria-hidden
                        />
                    ))}
                </div>
            )}
            <blockquote style={{ margin: 0, fontSize: card ? '1.0625em' : 'inherit', lineHeight: 1.6, flex: 1 }}>
                {card ? '\u201C' : ''}
                <EditableText value={str(block.props, 'quote', '')} mode={mode} multiline placeholder="What they said" ariaLabel="Edit quote" {...edit('quote')} />
                {card ? '\u201D' : ''}
            </blockquote>
            <figcaption style={{ display: 'flex', gap: '12px', alignItems: 'center', justifyContent: card ? undefined : 'inherit' }}>
                {avatar ? (
                    <img
                        src={avatar}
                        alt=""
                        style={{ width: '44px', height: '44px', borderRadius: '999px', objectFit: 'cover' }}
                    />
                ) : (
                    author !== '' && (
                        <span
                            aria-hidden
                            style={{
                                display: 'inline-flex',
                                width: '44px',
                                height: '44px',
                                borderRadius: '999px',
                                alignItems: 'center',
                                justifyContent: 'center',
                                flexShrink: 0,
                                // Flat, like the rest of the design system.
                                background: 'var(--fnl-accent, #4f46e5)',
                                color: '#fff',
                                fontSize: '15px',
                                fontWeight: 700,
                            }}
                        >
                            {initials(author)}
                        </span>
                    )
                )}
                <span>
                    <EditableText as="strong" value={str(block.props, 'authorName', '')} mode={mode} placeholder="Name" ariaLabel="Edit author name" style={{ display: 'block' }} {...edit('authorName')} />
                    <EditableText value={str(block.props, 'authorTitle', '')} mode={mode} placeholder="Role, company" ariaLabel="Edit author title" style={{ opacity: 0.7, fontSize: '.8125em' }} {...edit('authorTitle')} />
                </span>
            </figcaption>
        </figure>
    );
}

function Disclosure({
    title,
    children,
    open,
    onToggle,
    editing = false,
}: {
    title: ReactNode;
    children: ReactNode;
    open: boolean;
    onToggle: () => void;
    /**
     * On the canvas every panel is open and the title is a heading rather than
     * a button, so clicking into the text edits it instead of collapsing it.
     */
    editing?: boolean;
}) {
    const contentId = useId();

    if (editing) {
        return (
            <div style={{ borderBottom: '1px solid var(--color-border, #e2e8f0)' }}>
                <h3 style={{ margin: 0, padding: '14px 0', fontSize: 'inherit', fontWeight: 600 }}>{title}</h3>
                <div style={{ paddingBottom: '14px' }}>{children}</div>
            </div>
        );
    }

    return (
        <div style={{ borderBottom: '1px solid var(--color-border, #e2e8f0)' }}>
            <h3 style={{ margin: 0 }}>
                <button
                    type="button"
                    aria-expanded={open}
                    aria-controls={contentId}
                    onClick={onToggle}
                    style={{
                        display: 'flex',
                        width: '100%',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        gap: '12px',
                        padding: '14px 0',
                        background: 'transparent',
                        border: 0,
                        color: 'inherit',
                        font: 'inherit',
                        fontWeight: 600,
                        textAlign: 'left',
                        cursor: 'pointer',
                    }}
                >
                    {title}
                    <ChevronDown
                        style={{
                            width: '18px',
                            height: '18px',
                            flexShrink: 0,
                            transform: open ? 'rotate(180deg)' : undefined,
                            transition: 'transform 150ms',
                        }}
                        aria-hidden
                    />
                </button>
            </h3>
            <div id={contentId} hidden={!open} style={{ paddingBottom: '14px' }}>
                {children}
            </div>
        </div>
    );
}

function useDisclosureSet(allowMultiple: boolean, initial: number[] = [0]) {
    const [open, setOpen] = useState<number[]>(initial);
    const toggle = (index: number) => {
        setOpen((current) => {
            if (current.includes(index)) {
                return current.filter((entry) => entry !== index);
            }
            return allowMultiple ? [...current, index] : [index];
        });
    };
    return { open, toggle };
}

export function FaqBlock({ block, mode, onPropsChange }: BlockComponentProps) {
    const items = recordList(block.props, 'items');
    const editing = mode === 'editable' && onPropsChange !== undefined;
    const patch = (index: number, change: Record<string, unknown>) => onPropsChange?.({ items: withItem(items, index, change) });
    const { open, toggle } = useDisclosureSet(bool(block.props, 'allowMultipleOpen', false), bool(block.props, 'initialOpen', true) ? [0] : []);

    // FAQPage structured data lets search engines show the answers directly.
    // `<` is escaped so an answer containing "</script>" cannot end the tag.
    const schema =
        mode !== 'editable' && bool(block.props, 'emitSchemaOrg', true) && items.length > 0
            ? JSON.stringify({
                  '@context': 'https://schema.org',
                  '@type': 'FAQPage',
                  mainEntity: items.map((item) => ({
                      '@type': 'Question',
                      name: String(item['question'] ?? ''),
                      acceptedAnswer: { '@type': 'Answer', text: String(item['answer'] ?? '') },
                  })),
              }).replace(/</g, '\\u003c')
            : null;

    return (
        <div>
            {schema && <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: schema }} />}
            {items.map((item, index) => (
                <Disclosure
                    key={editing ? index : `${String(item['question'] ?? index)}`}
                    editing={editing}
                    title={
                        <EditableText
                            value={String(item['question'] ?? '')}
                            mode={mode}
                            placeholder="Question"
                            ariaLabel={`Edit question ${index + 1}`}
                            {...(editing ? { onChange: (next: string) => patch(index, { question: next }) } : {})}
                        />
                    }
                    open={open.includes(index)}
                    onToggle={() => toggle(index)}
                >
                    <EditableText
                        as="p"
                        multiline
                        value={String(item['answer'] ?? '')}
                        mode={mode}
                        placeholder="Answer"
                        ariaLabel={`Edit answer ${index + 1}`}
                        style={{ margin: 0, whiteSpace: 'pre-line' }}
                        {...(editing ? { onChange: (next: string) => patch(index, { answer: next }) } : {})}
                    />
                </Disclosure>
            ))}
            <CanvasAddButton
                mode={mode}
                label="Add a question"
                {...(editing ? { onAdd: () => onPropsChange?.({ items: [...items, { question: 'A new question', answer: 'Its answer.' }] }) } : {})}
            />
        </div>
    );
}

/**
 * The accordion pairs its `items` prop with its child blocks positionally: item
 * `n` is the heading for child `n`.
 */
export function AccordionBlock({ block, children, mode, onPropsChange }: BlockComponentProps) {
    const items = recordList(block.props, 'items');
    const editing = mode === 'editable' && onPropsChange !== undefined;
    const { open, toggle } = useDisclosureSet(bool(block.props, 'allowMultipleOpen', false), bool(block.props, 'initialOpen', true) ? [0] : []);
    const panels = Array.isArray(children) ? children : children ? [children] : [];

    return (
        <div>
            {items.map((item, index) => (
                <Disclosure
                    key={editing ? index : `${String(item['title'] ?? index)}`}
                    editing={editing}
                    title={
                        <EditableText
                            value={String(item['title'] ?? `Section ${index + 1}`)}
                            mode={mode}
                            placeholder="Panel title"
                            ariaLabel={`Edit panel title ${index + 1}`}
                            {...(editing
                                ? { onChange: (next: string) => onPropsChange?.({ items: withItem(items, index, { title: next }) }) }
                                : {})}
                        />
                    }
                    open={open.includes(index)}
                    onToggle={() => toggle(index)}
                >
                    {panels[index] ?? (editing ? <p style={{ margin: 0, fontSize: '12px', opacity: 0.6 }}>Drop a block into this accordion to fill this panel.</p> : null)}
                </Disclosure>
            ))}
            <CanvasAddButton
                mode={mode}
                label="Add a panel"
                {...(editing ? { onAdd: () => onPropsChange?.({ items: [...items, { id: null, title: `Section ${items.length + 1}` }] }) } : {})}
            />
        </div>
    );
}

export function TabsBlock({ block, children, mode, onPropsChange }: BlockComponentProps) {
    const editing = mode === 'editable' && onPropsChange !== undefined;
    const tabs = recordList(block.props, 'tabs');
    const vertical = oneOf(block.props, 'orientation', ['horizontal', 'vertical'] as const, 'horizontal') === 'vertical';
    const initialTab = Math.max(0, Math.round(num(block.props, 'initialTab', 0)));
    const [selectedIndex, setActive] = useState(initialTab);
    const active = Math.min(selectedIndex, Math.max(0, tabs.length - 1));
    const baseId = useId();
    const panels = Array.isArray(children) ? children : children ? [children] : [];
    const scope = `fnl-tabs-${block.id.replace(/[^A-Za-z0-9]/g, '')}`;
    const mobileCols = tabs.length === 4 ? 2 : Math.min(3, Math.max(1, tabs.length));
    const responsiveCss = vertical
        ? ''
        : `@media (max-width:640px){[data-tabs-scope="${scope}"]>[role="tablist"]{grid-template-columns:repeat(${mobileCols},minmax(0,1fr))!important}[data-tabs-scope="${scope}"] [role="tab"]{padding:9px 8px!important;font-size:12px!important}}.fnl-page[data-fnl-breakpoint="mobile"] [data-tabs-scope="${scope}"]>[role="tablist"]{grid-template-columns:repeat(${mobileCols},minmax(0,1fr))!important}.fnl-page[data-fnl-breakpoint="mobile"] [data-tabs-scope="${scope}"] [role="tab"]{padding:9px 8px!important;font-size:12px!important}`;

    return (
        <div
            data-tabs-scope={scope}
            style={{ display: 'flex', flexDirection: vertical ? 'row' : 'column', gap: 'var(--fnl-gap, 16px)' }}
        >
            {responsiveCss && <style>{responsiveCss}</style>}
            <div
                role="tablist"
                aria-orientation={vertical ? 'vertical' : 'horizontal'}
                style={
                    vertical
                        ? {
                              display: 'flex',
                              flexDirection: 'column',
                              gap: '6px',
                              background: 'var(--fnl-tablist-bg, #ffffff)',
                              padding: '8px',
                              borderRadius: 'var(--fnl-tablist-radius, 16px)',
                              border: '1px solid var(--fnl-tablist-border, #e4e4e7)',
                              minWidth: '180px',
                          }
                        : {
                              display: 'grid',
                              gridTemplateColumns: `repeat(${Math.max(1, tabs.length + (editing ? 1 : 0))}, minmax(0, 1fr))`,
                              gap: '8px',
                              background: 'var(--fnl-tablist-bg, #ffffff)',
                              padding: '8px',
                              borderRadius: 'var(--fnl-tablist-radius, 16px)',
                              border: '1px solid var(--fnl-tablist-border, #e4e4e7)',
                              alignItems: 'center',
                          }
                }
            >
                {tabs.map((tab, index) => {
                    const isSelected = active === index;
                    return (
                        <button
                            key={`tab-${index}`}
                            type="button"
                            role="tab"
                            id={`${baseId}-tab-${index}`}
                            aria-selected={isSelected}
                            aria-controls={`${baseId}-panel-${index}`}
                            tabIndex={isSelected ? 0 : -1}
                            onClick={() => setActive(index)}
                            onKeyDown={(event) => {
                                // Do not intercept cursor movement while editing the tab label.
                                if (event.target !== event.currentTarget) return;
                                const forward = vertical ? 'ArrowDown' : 'ArrowRight';
                                const backward = vertical ? 'ArrowUp' : 'ArrowLeft';
                                let next = active;
                                if (event.key === forward) next = (active + 1) % tabs.length;
                                else if (event.key === backward) next = (active - 1 + tabs.length) % tabs.length;
                                else if (event.key === 'Home') next = 0;
                                else if (event.key === 'End') next = tabs.length - 1;
                                else return;
                                event.preventDefault();
                                setActive(next);
                                event.currentTarget.parentElement?.querySelectorAll<HTMLButtonElement>('[role="tab"]')[next]?.focus();
                            }}
                            style={{
                                padding: '12px 16px',
                                background: isSelected ? 'var(--fnl-tab-active-bg, #f4f4f2)' : 'var(--fnl-tab-inactive-bg, transparent)',
                                border: isSelected
                                    ? '1px solid var(--fnl-tab-active-border, transparent)'
                                    : '1px solid var(--fnl-tab-inactive-border, transparent)',
                                borderRadius: 'var(--fnl-tab-radius, 12px)',
                                boxShadow: isSelected ? 'var(--fnl-tab-active-shadow, none)' : 'none',
                                color: isSelected ? 'var(--fnl-tab-active-color, #09090b)' : 'var(--fnl-tab-inactive-color, #71717a)',
                                font: 'inherit',
                                fontSize: 'var(--fnl-font-size, 15px)',
                                fontWeight: isSelected ? 700 : 600,
                                textAlign: 'center',
                                cursor: 'pointer',
                                transition: 'background-color 180ms ease, color 180ms ease, border-color 180ms ease, box-shadow 180ms ease',
                                whiteSpace: 'nowrap',
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                            }}
                        >
                            {editing && isSelected ? (
                                <EditableText
                                    value={String(tab['label'] ?? `Tab ${index + 1}`)}
                                    mode={mode}
                                    placeholder="Tab label"
                                    ariaLabel={`Edit tab label ${index + 1}`}
                                    onChange={(next) => onPropsChange?.({ tabs: withItem(tabs, index, { label: next }) })}
                                />
                            ) : (
                                String(tab['label'] ?? `Tab ${index + 1}`)
                            )}
                        </button>
                    );
                })}
                {editing && (
                    <CanvasAddButton
                        mode={mode}
                        label="Add a tab"
                        onAdd={() => onPropsChange?.({ tabs: [...tabs, { id: null, label: `Tab ${tabs.length + 1}` }] })}
                    />
                )}
            </div>
            {tabs.map((_tab, index) => (
                <div
                    key={`panel-${index}`}
                    role="tabpanel"
                    id={`${baseId}-panel-${index}`}
                    aria-labelledby={`${baseId}-tab-${index}`}
                    hidden={active !== index}
                    style={{ flex: 1 }}
                >
                    {panels[index] ?? null}
                </div>
            ))}
        </div>
    );
}

export function ProgressBarBlock({ block, mode, onPropsChange }: BlockComponentProps) {
    const runtime = useFunnel();
    // "Funnel step position" counts this page's place among the published
    // steps. Without a funnel behind the page — the builder, a preview — the
    // manual values stand in.
    const position = oneOf(block.props, 'source', ['manual', 'funnel_step'] as const, 'manual') === 'funnel_step'
        ? stepPosition(runtime)
        : null;
    const value = position ? position.current : num(block.props, 'value', 0);
    const max = position ? Math.max(1, position.total) : Math.max(1, num(block.props, 'max', 100));
    const percent = Math.max(0, Math.min(100, (value / max) * 100));
    // {current} and {total} in the label are filled in, so "Step {current} of {total}" stays true.
    const label = str(block.props, 'label', '')
        .replace(/\{current\}/g, String(value))
        .replace(/\{total\}/g, String(max));

    return (
        <div style={stack('6px')}>
            {(label || bool(block.props, 'showPercentage', false) || mode === 'editable') && (
                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '.8125em' }}>
                    {mode === 'editable' && onPropsChange ? (
                        <EditableText
                            value={str(block.props, 'label', '')}
                            mode={mode}
                            placeholder="Label"
                            ariaLabel="Edit progress label"
                            onChange={(next) => onPropsChange({ label: next })}
                        />
                    ) : (
                        <span>{label}</span>
                    )}
                    {bool(block.props, 'showPercentage', false) && <span>{Math.round(percent)}%</span>}
                </div>
            )}
            <div
                role="progressbar"
                aria-valuenow={value}
                aria-valuemin={0}
                aria-valuemax={max}
                aria-label={label || 'Progress'}
                // The track is styleable so a template can match its own design;
                // the fill is the block's own background colour.
                style={{
                    height: 'var(--fnl-track-height, 8px)',
                    borderRadius: 'var(--fnl-track-radius, 999px)',
                    background: 'var(--fnl-track-color, rgba(15,23,42,.12))',
                    overflow: 'hidden',
                }}
            >
                <div
                    style={{
                        width: `${percent}%`,
                        height: '100%',
                        // The block's background colour is the fill colour.
                        background: 'var(--fnl-background-color, var(--fnl-accent, #4f46e5))',
                        borderRadius: 'inherit',
                    }}
                />
            </div>
        </div>
    );
}

export function FeatureCardsBlock({ block, mode, onPropsChange }: BlockComponentProps) {
    const items = recordList(block.props, 'items');
    const editing = mode === 'editable' && onPropsChange !== undefined;
    const edit = (index: number, key: string) =>
        editing ? { onChange: (next: string) => onPropsChange?.({ items: withItem(items, index, { [key]: next }) }) } : {};
    const columns = Math.max(1, num(block.props, 'columns', 3));

    // One column reads as a checklist, not a card grid: lighter, no boxes.
    const list = columns <= 1;

    return (
        <div style={list ? { ...responsiveGrid(1), gap: '16px' } : responsiveGrid(columns)}>
            {items.map((item, index) => {
                const Icon = iconFor(String(item['icon'] ?? 'star'));
                return (
                    <div
                        key={editing ? index : `${String(item['title'] ?? index)}`}
                        style={
                            list
                                ? { display: 'flex', gap: '14px', alignItems: 'flex-start' }
                                : {
                                      ...cardStyle,
                                      padding: '24px',
                                      borderRadius: '16px',
                                      color: 'var(--fnl-color, #0f172a)',
                                      boxShadow: '0 1px 2px rgba(15,23,42,.04)',
                                      ...stack('12px'),
                                  }
                        }
                    >
                        <span
                            aria-hidden
                            style={{
                                display: 'inline-flex',
                                width: list ? '34px' : '44px',
                                height: list ? '34px' : '44px',
                                borderRadius: list ? '10px' : '12px',
                                alignItems: 'center',
                                justifyContent: 'center',
                                flexShrink: 0,
                                background: 'rgba(79,70,229,.1)',
                                color: 'var(--fnl-accent, #4f46e5)',
                            }}
                        >
                            <Icon style={{ width: list ? '18px' : '22px', height: list ? '18px' : '22px' }} />
                        </span>
                        <div style={stack('6px')}>
                            <EditableText as="h3" value={String(item['title'] ?? '')} mode={mode} placeholder="Title" ariaLabel={`Edit card title ${index + 1}`} style={{ margin: 0, fontSize: '1.0625em', fontWeight: 600 }} {...edit(index, 'title')} />
                            <EditableText as="p" multiline value={String(item['body'] ?? '')} mode={mode} placeholder="Describe it" ariaLabel={`Edit card text ${index + 1}`} style={{ margin: 0, opacity: 0.8, lineHeight: 1.6 }} {...edit(index, 'body')} />
                        </div>
                    </div>
                );
            })}
            {editing && (
                <CanvasAddButton
                    mode={mode}
                    label="Add a card"
                    onAdd={() => onPropsChange?.({ items: [...items, { icon: 'star', title: 'New feature', body: 'What makes it worth having.' }] })}
                />
            )}
        </div>
    );
}

/* -------------------------------------------------------------------------- */
/* Scheduling and engagement                                                   */
/* -------------------------------------------------------------------------- */

export { BookingBlock, CalendarBlock } from './booking';

/** Ask whichever popup system is on the page to open this popup. */
function openPopup(popupId: string | null): void {
    if (popupId && typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('fnl:open-popup', { detail: { popupId } }));
    }
}

export function PopupTriggerBlock({ block, mode, onPropsChange }: BlockComponentProps) {
    const popupId = optionalStr(block.props, 'popupId');
    const trigger = oneOf(block.props, 'trigger', ['click', 'scroll', 'exit_intent', 'delay'] as const, 'click');
    const delaySeconds = Math.max(0, num(block.props, 'delaySeconds', 5));
    const scrollPercent = Math.max(1, Math.min(100, num(block.props, 'scrollPercent', 50)));

    // Automatic triggers fire once per page view, and never in the builder.
    useEffect(() => {
        if (mode === 'editable' || trigger === 'click' || !popupId) {
            return;
        }
        let fired = false;
        const fire = (): void => {
            if (!fired) {
                fired = true;
                openPopup(popupId);
            }
        };
        if (trigger === 'delay') {
            const timer = window.setTimeout(fire, delaySeconds * 1000);
            return () => window.clearTimeout(timer);
        }
        if (trigger === 'scroll') {
            const onScroll = (): void => {
                const max = document.documentElement.scrollHeight - window.innerHeight;
                if (max <= 0 || (window.scrollY / max) * 100 >= scrollPercent) {
                    fire();
                }
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            return () => window.removeEventListener('scroll', onScroll);
        }
        const onLeave = (event: MouseEvent): void => {
            if (event.clientY <= 0) {
                fire();
            }
        };
        document.addEventListener('mouseout', onLeave);
        return () => document.removeEventListener('mouseout', onLeave);
    }, [mode, trigger, popupId, delaySeconds, scrollPercent]);

    if (trigger !== 'click') {
        // Invisible on the live page; a labelled stand-in only on the canvas.
        return mode === 'editable' ? (
            <Placeholder
                label="Popup trigger"
                detail={`Fires on ${trigger.replace('_', ' ')} — invisible on the published page.`}
            />
        ) : null;
    }

    return (
        <button
            type="button"
            style={buttonSurface('primary', 'md', false)}
            data-fnl-popup={popupId ?? undefined}
            onClick={() => {
                if (mode !== 'editable') {
                    openPopup(popupId);
                }
            }}
        >
            <EditableText
                value={str(block.props, 'label', 'Open')}
                mode={mode}
                placeholder="Button label"
                ariaLabel="Edit popup button label"
                {...(mode === 'editable' && onPropsChange ? { onChange: (next: string) => onPropsChange({ label: next }) } : {})}
            />
        </button>
    );
}
