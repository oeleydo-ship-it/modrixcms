/*
 * Contact form plugin: React components, registered with the CMS at load time.
 *
 * Plain JavaScript (React.createElement), so it works as-is. Larger plugins usually write
 * JSX and compile it to a file like this one, with React and Inertia taken from
 * window.ModrixCMS instead of bundled (see docs/cms/extension-development.md).
 */
const { React, Inertia, ui, registerPlugin } = window.ModrixCMS;
const h = React.createElement;
const { useForm, usePage, router } = Inertia;

/** The page-builder block: { data, anchor } come from the CMS. */
function ContactFormBlock({ data, anchor }) {
    const { contactForm } = usePage().props;
    const form = useForm({ name: '', email: '', message: '', website: '' });

    const submit = (event) => {
        event.preventDefault();
        form.post(contactForm.sendUrl, { preserveScroll: true, errorBag: 'contactForm', onSuccess: () => form.reset() });
    };

    const input = (name, label, props = {}) => h('label', null, label,
        h(props.rows ? 'textarea' : 'input', {
            name,
            value: form.data[name],
            onChange: (e) => form.setData(name, e.target.value),
            required: true,
            ...props,
        }),
        form.errors[name] ? h('span', { className: 'contact-form-error' }, form.errors[name]) : null,
    );

    return h('section', { className: 'section contact-form-block', id: anchor },
        h('div', { className: 'wrap' },
            data.title || data.text ? h('div', { className: 'section-head' },
                data.title ? h('h2', null, data.title) : null,
                data.text ? h('p', null, data.text) : null,
            ) : null,
            contactForm?.sent
                ? h('p', { className: 'contact-form-sent', role: 'status' }, contactForm.successMessage)
                : h('form', { className: 'contact-form', onSubmit: submit },
                    h('div', { className: 'contact-form-row' },
                        input('name', 'Name', { maxLength: 120, autoComplete: 'name' }),
                        input('email', 'Email', { type: 'email', maxLength: 200, autoComplete: 'email' }),
                    ),
                    input('message', 'Message', { rows: 6, maxLength: 5000 }),
                    // Bots fill in every field; people never see this one.
                    h('label', { className: 'contact-form-trap', 'aria-hidden': 'true' }, 'Website ',
                        h('input', { name: 'website', tabIndex: -1, autoComplete: 'off', value: form.data.website, onChange: (e) => form.setData('website', e.target.value) })),
                    h('button', { className: 'btn', type: 'submit', disabled: form.processing }, data.button_label || 'Send message'),
                ),
        ),
    );
}

/** Admin → Messages. */
function MessagesPage({ messages }) {
    const remove = (message) => {
        if (window.confirm('Delete this message?')) {
            router.delete(route('plugin.modrix.contact-form.messages.destroy', message.id), { preserveScroll: true });
        }
    };

    return h(ui.AdminLayout, { title: 'Messages', subtitle: 'Sent with the contact form block.' },
        h('div', { className: 'panel' },
            h('div', { className: 'table-wrap' },
                h('table', null,
                    h('thead', null, h('tr', null, h('th', null, 'From'), h('th', null, 'Message'), h('th', null, 'Received'), h('th'))),
                    h('tbody', null, messages.data.length
                        ? messages.data.map((message) => h('tr', { key: message.id },
                            h('td', { className: 'title' }, message.name, h('div', { className: 'sub' }, h('a', { href: `mailto:${message.email}` }, message.email))),
                            h('td', { style: { whiteSpace: 'pre-line', maxWidth: 520 } }, message.message),
                            h('td', { className: 'sub' }, message.received),
                            h('td', null, h('button', { className: 'btn btn-danger btn-sm', type: 'button', onClick: () => remove(message) }, 'Delete')),
                        ))
                        : h('tr', null, h('td', { colSpan: 4, className: 'empty' }, 'No messages yet. Add the “Contact form” block to a page.'))),
                ),
            ),
            h(ui.Pager, { paginator: messages }),
        ),
    );
}

registerPlugin('modrix/contact-form', {
    blocks: { 'modrix/contact-form:form': ContactFormBlock },
    pages: { Messages: MessagesPage },
});
