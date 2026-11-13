import { Link, router } from '@inertiajs/react';

/**
 * Form controls bound to an Inertia useForm() object:
 *   const form = useForm({ title: '' });
 *   <Field form={form} name="title" label="Title" required />
 */
export function Field({ form, name, label, type = 'text', help, options, children, className, ...props }) {
    const error = form.errors[name];
    const value = form.data[name] ?? '';
    const onChange = (e) => form.setData(name, e.target.value);
    const id = props.id ?? `field-${name}`;

    let control;

    if (type === 'textarea') {
        control = <textarea id={id} name={name} value={value} onChange={onChange} {...props} />;
    } else if (type === 'select') {
        control = (
            <select id={id} name={name} value={value} onChange={onChange} {...props}>
                {Object.entries(options ?? {}).map(([optionValue, optionLabel]) => <option key={optionValue} value={optionValue}>{optionLabel}</option>)}
            </select>
        );
    } else {
        control = <input id={id} name={name} type={type} value={type === 'password' ? value : value} onChange={onChange} {...props} />;
    }

    return (
        <div className={['field', error && 'has-error', className].filter(Boolean).join(' ')}>
            {label ? <label htmlFor={id}>{label}</label> : null}
            {children ?? control}
            {help ? <div className="help">{help}</div> : null}
            {error ? <div className="error">{error}</div> : null}
        </div>
    );
}

export function Check({ form, name, label }) {
    return (
        <label className="check">
            <input type="checkbox" name={name} checked={!!form.data[name]} onChange={(e) => form.setData(name, e.target.checked)} /> {label}
        </label>
    );
}

/** A file input bound to a useForm() field (send the form with forceFormData). */
export function FileField({ form, name, label, accept, help }) {
    const error = form.errors[name];

    return (
        <div className={`field${error ? ' has-error' : ''}`}>
            {label ? <label htmlFor={`field-${name}`}>{label}</label> : null}
            <input id={`field-${name}`} type="file" name={name} accept={accept} onChange={(e) => form.setData(name, e.target.files[0] ?? null)} />
            {help ? <div className="help">{help}</div> : null}
            {error ? <div className="error">{error}</div> : null}
        </div>
    );
}

/** Laravel paginator → Previous / Page x of y / Next. */
export function Pager({ paginator }) {
    if (!paginator || paginator.last_page <= 1) {
        return null;
    }

    return (
        <div className="pager">
            {paginator.prev_page_url ? <Link href={paginator.prev_page_url} rel="prev" preserveScroll>← Previous</Link> : <span>← Previous</span>}
            <span>Page {paginator.current_page} of {paginator.last_page}</span>
            {paginator.next_page_url ? <Link href={paginator.next_page_url} rel="next" preserveScroll>Next →</Link> : <span>Next →</span>}
        </div>
    );
}

/** Sends a request after the admin confirms (delete buttons and the like). */
export function confirmVisit(message, method, url, data = {}, options = {}) {
    if (message && !window.confirm(message)) {
        return;
    }

    router.visit(url, { method, data, preserveScroll: true, ...options });
}

export function DangerButton({ confirm, url, method = 'delete', data, children, small = true }) {
    return (
        <button className={`btn btn-danger${small ? ' btn-sm' : ''}`} type="button" onClick={() => confirmVisit(confirm, method, url, data)}>
            {children}
        </button>
    );
}

export function Badge({ tone = 'gray', children }) {
    return <span className={`badge ${tone}`}>{children}</span>;
}

export function ErrorFlash({ errors, keys }) {
    const message = (keys ?? Object.keys(errors ?? {})).map((key) => errors?.[key]).find(Boolean);

    return message ? <div className="flash err" role="alert">{message}</div> : null;
}

/** Fills a slug from a title until the slug is edited by hand. */
export function slugify(text) {
    return String(text)
        .toLowerCase()
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}
