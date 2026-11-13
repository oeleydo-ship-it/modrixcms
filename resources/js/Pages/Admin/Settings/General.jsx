import { useForm } from '@inertiajs/react';
import AdminLayout from '@/cms/admin/AdminLayout';
import { Field } from '@/cms/admin/forms';
import SettingsTabs from './Tabs';

export default function General({ values, githubPlaceholder }) {
    const form = useForm(values);

    return (
        <AdminLayout title="Settings" subtitle="Site name, contact details and links.">
            <SettingsTabs />
            <form onSubmit={(e) => { e.preventDefault(); form.put(route('admin.settings.general.update'), { preserveScroll: true }); }}>
                <div className="grid-2">
                    <div>
                        <div className="panel">
                            <h2>Site</h2>
                            <p className="hint">Shown in the header, page titles and emails.</p>
                            <Field form={form} name="site_name" label="Site name" required maxLength={100} />
                            <Field form={form} name="tagline" label="Tagline" maxLength={200} help="Used as the home page description for search engines." />
                            <Field form={form} name="contact_email" label="Contact email" type="email" help="Shown in the footer." />
                            <Field form={form} name="footer_text" label="Footer text" maxLength={300} />
                        </div>
                        <div className="panel">
                            <h2>Links</h2>
                            <p className="hint">Where the “Download” and “Marketplace” buttons go.</p>
                            <Field form={form} name="github_url" label="GitHub / download URL" type="url" placeholder={githubPlaceholder} />
                            <Field form={form} name="marketplace_url" label="Marketplace URL" type="url" placeholder="https://" />
                        </div>
                    </div>
                    <div className="panel">
                        <h2>Social</h2>
                        <p className="hint">Full https:// links. Empty ones are hidden.</p>
                        <Field form={form} name="social_x" label="X (Twitter)" type="url" />
                        <Field form={form} name="social_facebook" label="Facebook" type="url" />
                        <Field form={form} name="social_linkedin" label="LinkedIn" type="url" />
                        <Field form={form} name="social_youtube" label="YouTube" type="url" />
                        <button className="btn" type="submit" disabled={form.processing}>Save settings</button>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
