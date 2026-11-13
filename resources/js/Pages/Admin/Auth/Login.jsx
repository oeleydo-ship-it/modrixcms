import { Head, useForm, usePage } from '@inertiajs/react';
import { Field } from '@/cms/admin/forms';

export default function Login() {
    const { site } = usePage().props;
    const form = useForm({ email: '', password: '', remember: false });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('admin.login.store'), { onFinish: () => form.reset('password') });
    };

    return (
        <main className="auth">
            <Head title={`Sign in · ${site.name} Admin`} />
            <div className="auth-card">
                <div className="logo">
                    {site.logo ? <img src={site.logo} alt={site.name} /> : <><img src="/favicon.svg" alt="" /> {site.name}</>}
                </div>
                <h1>Admin sign in</h1>
                <p>Manage pages, blog posts, themes and plugins.</p>

                <form onSubmit={submit}>
                    <Field form={form} name="email" label="Email" type="email" required autoFocus autoComplete="username" />
                    <Field form={form} name="password" label="Password" type="password" required autoComplete="current-password" />
                    <label className="check">
                        <input type="checkbox" checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} /> Keep me signed in
                    </label>
                    <button className="btn" type="submit" disabled={form.processing}>Sign in</button>
                </form>
            </div>
        </main>
    );
}
