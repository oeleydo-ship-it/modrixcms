import { Head, useForm, usePage } from '@inertiajs/react';
import { Check, Field } from '@/cms/admin/forms';

/** First visit after a deploy: create the admin account (shown only while the site has none). */
export default function Setup({ requiresToken, defaults }) {
    const { site } = usePage().props;
    const form = useForm({
        site_name: defaults?.site_name ?? '',
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        setup_token: '',
        starter_content: true,
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('setup.store'), { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <main className="auth">
            <Head title={`Set up ${site.name}`} />
            <div className="auth-card" style={{ width: 'min(460px, 100%)' }}>
                <div className="logo">
                    {site.logo ? <img src={site.logo} alt={site.name} /> : <><img src="/favicon.svg" alt="" /> {site.name}</>}
                </div>
                <h1>Welcome! Create your admin account</h1>
                <p>This is the first visit, so there is no admin yet. Choose the email and password you will use to sign in.</p>

                <form onSubmit={submit}>
                    <Field form={form} name="site_name" label="Site name" required maxLength={100} />
                    <Field form={form} name="name" label="Your name" required autoFocus maxLength={100} autoComplete="name" />
                    <Field form={form} name="email" label="Admin email (your username)" type="email" required autoComplete="username" />
                    <Field form={form} name="password" label="Password" type="password" required autoComplete="new-password" help="At least 10 characters." />
                    <Field form={form} name="password_confirmation" label="Repeat password" type="password" required autoComplete="new-password" />
                    {requiresToken ? (
                        <Field form={form} name="setup_token" label="Setup token" type="password" required autoComplete="off" help="The SETUP_TOKEN value from this site's environment settings." />
                    ) : null}
                    <Check form={form} name="starter_content" label="Add the starter pages and a sample post" />
                    <button className="btn" type="submit" disabled={form.processing} style={{ marginTop: 16 }}>Create admin and open the dashboard</button>
                </form>
            </div>
        </main>
    );
}
