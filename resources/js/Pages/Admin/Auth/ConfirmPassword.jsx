import { useForm } from '@inertiajs/react';
import AdminLayout from '@/cms/admin/AdminLayout';
import { Field } from '@/cms/admin/forms';

export default function ConfirmPassword() {
    const form = useForm({ password: '' });

    return (
        <AdminLayout title="Confirm your password" subtitle="Installing, enabling or removing themes and plugins changes code on your server.">
            <div className="panel" style={{ maxWidth: 460 }}>
                <p className="hint">For your security, enter your password to continue. You won’t be asked again for 15 minutes.</p>
                <form onSubmit={(e) => { e.preventDefault(); form.post(route('admin.password.confirm.store')); }}>
                    <Field form={form} name="password" label="Password" type="password" required autoFocus autoComplete="current-password" />
                    <button className="btn" type="submit" disabled={form.processing}>Confirm</button>
                </form>
            </div>
        </AdminLayout>
    );
}
