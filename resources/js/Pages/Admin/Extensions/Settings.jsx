import { Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/cms/admin/AdminLayout';
import { Field } from '@/cms/admin/forms';

/** Settings of a plugin, or the Customize screen of a theme, generated from its manifest. */
export default function ExtensionSettings({ title, subtitle, fields, values, action, back }) {
    const form = useForm(values);

    return (
        <AdminLayout title={title} subtitle={subtitle} actions={<Link className="btn btn-light" href={back}>Back</Link>}>
            <form className="panel" style={{ maxWidth: 720 }} onSubmit={(e) => { e.preventDefault(); form.put(action, { preserveScroll: true }); }}>
                {fields.map((field) => {
                    if (field.type === 'toggle') {
                        return (
                            <div key={field.name} className="field">
                                <label className="check"><input type="checkbox" checked={!!form.data[field.name]} onChange={(e) => form.setData(field.name, e.target.checked)} /> {field.label}</label>
                                {field.help ? <div className="help">{field.help}</div> : null}
                            </div>
                        );
                    }

                    if (field.type === 'color') {
                        return (
                            <Field key={field.name} form={form} name={field.name} label={field.label} help={field.help}>
                                <input id={`field-${field.name}`} type="color" value={form.data[field.name] ?? '#000000'} onChange={(e) => form.setData(field.name, e.target.value)} style={{ width: 72, height: 40, padding: 2 }} />
                            </Field>
                        );
                    }

                    const type = { textarea: 'textarea', select: 'select', email: 'email', url: 'url', number: 'number' }[field.type] ?? 'text';

                    return (
                        <Field key={field.name} form={form} name={field.name} label={field.label} help={field.help} type={type}
                            options={field.options} rows={type === 'textarea' ? 4 : undefined} min={field.min} max={field.max} />
                    );
                })}
                <button className="btn" type="submit" disabled={form.processing}>Save settings</button>
            </form>
        </AdminLayout>
    );
}
