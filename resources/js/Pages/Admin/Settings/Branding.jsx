import { useForm } from '@inertiajs/react';
import AdminLayout from '@/cms/admin/AdminLayout';
import { FileField } from '@/cms/admin/forms';
import SettingsTabs from './Tabs';

export default function Branding({ logo, favicon }) {
    const form = useForm({ logo: null, favicon: null, remove_logo: false, remove_favicon: false, _method: 'put' });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('admin.settings.branding.update'), { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() });
    };

    const remove = (name, label) => (
        <label className="check"><input type="checkbox" checked={form.data[name]} onChange={(e) => form.setData(name, e.target.checked)} /> {label}</label>
    );

    return (
        <AdminLayout title="Settings" subtitle="Your logo and browser icon.">
            <SettingsTabs />
            <form onSubmit={submit}>
                <div className="grid-2">
                    <div className="panel">
                        <h2>Logo</h2>
                        <p className="hint">Shown in the site header, footer and admin. PNG, JPG or WebP up to 2 MB; a wide logo about 400×100 works best.</p>
                        {logo ? (
                            <>
                                <div className="preview"><img src={logo} alt="Current logo" /></div>
                                <div className="preview dark"><img src={logo} alt="Current logo on dark" /></div>
                                {remove('remove_logo', 'Remove logo (use the default)')}
                            </>
                        ) : (
                            <div className="preview"><img src="/favicon.svg" alt="" style={{ height: 40 }} /> <span>Default logo: icon + site name</span></div>
                        )}
                        <FileField form={form} name="logo" label="Upload a new logo" accept="image/png,image/jpeg,image/webp" />
                    </div>

                    <div className="panel">
                        <h2>Favicon</h2>
                        <p className="hint">The icon in browser tabs. Square PNG (at least 64×64) or ICO, up to 512 KB.</p>
                        {favicon ? (
                            <>
                                <div className="preview"><img src={favicon} alt="Current favicon" style={{ height: 48 }} /></div>
                                {remove('remove_favicon', 'Remove favicon')}
                            </>
                        ) : null}
                        <FileField form={form} name="favicon" label="Upload a favicon" accept="image/png,image/x-icon,.ico" />
                        <button className="btn" type="submit" disabled={form.processing}>Save branding</button>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
