import { useForm, usePage } from '@inertiajs/react';
import AdminLayout from '@/cms/admin/AdminLayout';
import { Field } from '@/cms/admin/forms';

export default function Account() {
    const { auth } = usePage().props;
    const form = useForm({ name: auth.user.name, email: auth.user.email, current_password: '', password: '', password_confirmation: '' });

    const submit = (e) => {
        e.preventDefault();
        form.put(route('admin.account.update'), { preserveScroll: true, onSuccess: () => form.reset('current_password', 'password', 'password_confirmation') });
    };

    return (
        <AdminLayout title="My account" subtitle="Your name, sign-in email and password.">
            <form className="panel" onSubmit={submit} style={{ maxWidth: 560 }}>
                <Field form={form} name="name" label="Name" required />
                <Field form={form} name="email" label="Email" type="email" required autoComplete="username" />

                <h2 style={{ marginTop: 26 }}>Change password</h2>
                <p className="hint">Leave empty to keep your current password.</p>
                <Field form={form} name="current_password" label="Current password" type="password" autoComplete="current-password" />
                <Field form={form} name="password" label="New password" type="password" autoComplete="new-password" help="At least 10 characters." />
                <Field form={form} name="password_confirmation" label="Repeat new password" type="password" autoComplete="new-password" />

                <button className="btn" type="submit" disabled={form.processing}>Save account</button>
            </form>
        </AdminLayout>
    );
}
