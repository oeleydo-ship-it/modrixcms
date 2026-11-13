import { useForm, usePage } from '@inertiajs/react';
import AdminLayout from '@/cms/admin/AdminLayout';
import { Check, Field } from '@/cms/admin/forms';
import SettingsTabs from './Tabs';

export default function Smtp({ values, passwordSaved, siteName }) {
    const { auth } = usePage().props;
    const form = useForm({ ...values, mail_password: '' });
    const test = useForm({ test_to: auth.user.email });

    return (
        <AdminLayout title="Settings" subtitle="Send site emails through your own mail server.">
            <SettingsTabs />
            <div className="grid-2">
                <form className="panel" onSubmit={(e) => { e.preventDefault(); form.put(route('admin.settings.smtp.update'), { preserveScroll: true, onSuccess: () => form.setData('mail_password', '') }); }}>
                    <h2>SMTP server</h2>
                    <p className="hint">From your email provider (Gmail, Mailgun, Brevo, your host…).</p>
                    <Check form={form} name="mail_enabled" label="Use these SMTP settings" />
                    <div className="row">
                        <Field form={form} name="mail_host" label="Host" placeholder="smtp.example.com" />
                        <Field form={form} name="mail_port" label="Port" type="number" min={1} max={65535} />
                    </div>
                    <Field form={form} name="mail_encryption" label="Encryption" type="select"
                        options={{ tls: 'TLS (STARTTLS, usually port 587)', ssl: 'SSL (usually port 465)', none: 'None' }} />
                    <div className="row">
                        <Field form={form} name="mail_username" label="Username" autoComplete="off" />
                        <Field form={form} name="mail_password" label="Password" type="password" autoComplete="new-password" help={passwordSaved ? 'Saved. Leave empty to keep it.' : 'Stored encrypted.'} />
                    </div>
                    <div className="row">
                        <Field form={form} name="mail_from_address" label="From email" type="email" placeholder="hello@example.com" />
                        <Field form={form} name="mail_from_name" label="From name" placeholder={siteName} />
                    </div>
                    <button className="btn" type="submit" disabled={form.processing}>Save SMTP settings</button>
                </form>

                <form className="panel" onSubmit={(e) => { e.preventDefault(); test.post(route('admin.settings.smtp.test'), { preserveScroll: true }); }}>
                    <h2>Send a test email</h2>
                    <p className="hint">Save first, then check that mail arrives.</p>
                    <Field form={test} name="test_to" label="Send to" type="email" required />
                    <button className="btn btn-light" type="submit" disabled={test.processing}>Send test</button>
                </form>
            </div>
        </AdminLayout>
    );
}
