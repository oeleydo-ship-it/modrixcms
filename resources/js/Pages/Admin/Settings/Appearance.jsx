import { useEffect } from 'react';
import { router, useForm } from '@inertiajs/react';
import AdminLayout, { applyThemeVariables, THEME_PRESETS } from '@/cms/admin/AdminLayout';
import { Check, Field } from '@/cms/admin/forms';
import SettingsTabs from './Tabs';

function ColorInput({ form, name, label, help }) {
    const error = form.errors[name];
    const value = form.data[name] ?? '#000000';

    return (
        <div className={`field${error ? ' has-error' : ''}`}>
            <label htmlFor={`field-${name}`}>{label}</label>
            <div className="color-input-wrap">
                <input
                    id={`field-${name}`}
                    type="color"
                    value={value}
                    onChange={(e) => form.setData(name, e.target.value)}
                />
                <input
                    type="text"
                    value={value}
                    onChange={(e) => form.setData(name, e.target.value)}
                    aria-label={`${label} hex`}
                />
            </div>
            {help ? <div className="help">{help}</div> : null}
            {error ? <div className="error">{error}</div> : null}
        </div>
    );
}

export default function Appearance({ values }) {
    const form = useForm(values);

    useEffect(() => {
        applyThemeVariables(form.data);
    }, [form.data]);

    const submit = (e) => {
        e.preventDefault();
        form.put(route('admin.settings.appearance.update'), { preserveScroll: true });
    };

    const reset = () => {
        router.post(route('admin.settings.appearance.reset'), {}, { preserveScroll: true });
    };

    return (
        <AdminLayout
            title="Settings"
            subtitle="Customize the admin sidebar, font typography, spacing and colors."
            actions={
                <>
                    <button className="btn btn-light" type="button" onClick={reset}>
                        Reset to reference
                    </button>
                    <button className="btn" type="button" onClick={submit} disabled={form.processing}>
                        Save design
                    </button>
                </>
            }
        >
            <SettingsTabs />

            <div className="panel" style={{ marginBottom: 16 }}>
                <h2>Theme presets</h2>
                <p className="hint">Apply a preset with one click, then fine-tune any color, font or spacing token below.</p>
                <div className="preset-grid" style={{ gridTemplateColumns: 'repeat(auto-fill, minmax(180px, 1fr))' }}>
                    {THEME_PRESETS.map((preset) => (
                        <button
                            key={preset.name}
                            type="button"
                            className="preset-btn"
                            onClick={() => form.setData({ ...form.data, ...preset.values })}
                        >
                            <span className="preset-swatch" style={{ background: preset.swatch }} />
                            <span>{preset.name}</span>
                        </button>
                    ))}
                </div>
            </div>

            <form onSubmit={submit}>
                <div className="grid-2">
                    <div>
                        <div className="panel">
                            <h2>Left sidebar</h2>
                            <p className="hint">Customize the dark sidebar frame, active pill highlight, width and store badge.</p>
                            <div className="row">
                                <ColorInput form={form} name="admin_sidebar_bg" label="Sidebar & frame background" />
                                <ColorInput form={form} name="admin_sidebar_text" label="Sidebar link color" />
                            </div>
                            <div className="row">
                                <ColorInput form={form} name="admin_sidebar_active_bg" label="Active item pill background" />
                                <ColorInput form={form} name="admin_sidebar_active_text" label="Active item text color" />
                            </div>
                            <div className="row">
                                <ColorInput form={form} name="admin_brand_color" label="Brand icon badge color" />
                                <ColorInput form={form} name="admin_avatar_color" label="Store avatar badge color" />
                            </div>
                            <div className="row">
                                <Field form={form} name="admin_sidebar_width" label="Sidebar width (px)" type="number" min={180} max={320} />
                                <Field
                                    form={form}
                                    name="admin_frame_inset"
                                    label="Outer frame inset"
                                    type="select"
                                    options={{
                                        '0px': '0px (Flush)',
                                        '4px': '4px (Subtle)',
                                        '6px': '6px (Reference default)',
                                        '10px': '10px (Deep frame)',
                                    }}
                                />
                            </div>
                            <div className="row">
                                <Field form={form} name="admin_store_handle" label="Name in the sidebar (blank: site name)" maxLength={40} />
                                <Field form={form} name="admin_store_badge" label="Badge next to the name (optional)" maxLength={20} />
                            </div>
                        </div>

                        <div className="panel">
                            <h2>Canvas & surface colors</h2>
                            <p className="hint">Main rounded viewport, cards, borders, text and primary action buttons.</p>
                            <div className="row">
                                <ColorInput form={form} name="admin_canvas_bg" label="Main canvas background" />
                                <ColorInput form={form} name="admin_surface_bg" label="Panel / card surface" />
                            </div>
                            <div className="row">
                                <ColorInput form={form} name="admin_surface_muted" label="Muted / table header surface" />
                                <ColorInput form={form} name="admin_border_color" label="Card & table border color" />
                            </div>
                            <div className="row">
                                <ColorInput form={form} name="admin_primary_color" label="Primary button color" />
                                <ColorInput form={form} name="admin_accent_color" label="Accent & badge color" />
                            </div>
                            <div className="row">
                                <ColorInput form={form} name="admin_text_color" label="Primary text color" />
                                <ColorInput form={form} name="admin_muted_color" label="Secondary / muted text" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <div className="panel">
                            <h2>Font typography</h2>
                            <p className="hint">Choose font families, base font size, heading weight and letter spacing.</p>
                            <Field
                                form={form}
                                name="admin_font_family"
                                label="Body font family"
                                type="select"
                                options={{
                                    Inter: 'Inter (Reference default)',
                                    'System UI': 'System UI (San Francisco / Segoe)',
                                    'Plus Jakarta Sans': 'Plus Jakarta Sans',
                                    'DM Sans': 'DM Sans',
                                    'Bricolage Grotesque': 'Bricolage Grotesque',
                                    'JetBrains Mono': 'JetBrains Mono',
                                }}
                            />
                            <Field
                                form={form}
                                name="admin_heading_font"
                                label="Heading font family"
                                type="select"
                                options={{
                                    Inter: 'Inter (Reference default)',
                                    'System UI': 'System UI',
                                    'Plus Jakarta Sans': 'Plus Jakarta Sans',
                                    'DM Sans': 'DM Sans',
                                    'Bricolage Grotesque': 'Bricolage Grotesque',
                                }}
                            />
                            <div className="row">
                                <Field
                                    form={form}
                                    name="admin_font_size"
                                    label="Base font size"
                                    type="select"
                                    options={{
                                        '12px': '12px (Compact)',
                                        '13px': '13px (Reference default)',
                                        '14px': '14px (Medium)',
                                        '15px': '15px (Large)',
                                    }}
                                />
                                <Field
                                    form={form}
                                    name="admin_heading_weight"
                                    label="Heading weight"
                                    type="select"
                                    options={{
                                        '500': '500 (Medium)',
                                        '600': '600 (Reference semibold)',
                                        '700': '700 (Bold)',
                                    }}
                                />
                            </div>
                            <Field
                                form={form}
                                name="admin_letter_spacing"
                                label="Letter spacing"
                                type="select"
                                options={{
                                    '-0.02em': 'Tight (-0.02em)',
                                    '-0.01em': 'Reference (-0.01em)',
                                    '0em': 'Normal (0em)',
                                    '0.01em': 'Relaxed (+0.01em)',
                                }}
                            />
                        </div>

                        <div className="panel">
                            <h2>Spacing & corner radius</h2>
                            <p className="hint">Control navigation density, canvas padding, card corners and button shape.</p>
                            <div className="row">
                                <Field
                                    form={form}
                                    name="admin_spacing"
                                    label="Layout density"
                                    type="select"
                                    options={{
                                        compact: 'Compact',
                                        default: 'Default (Reference)',
                                        comfortable: 'Comfortable',
                                        spacious: 'Spacious',
                                    }}
                                />
                                <Field
                                    form={form}
                                    name="admin_content_padding"
                                    label="Canvas padding"
                                    type="select"
                                    options={{
                                        '16px': '16px',
                                        '20px': '20px',
                                        '24px': '24px (Reference)',
                                        '32px': '32px',
                                        '40px': '40px',
                                    }}
                                />
                            </div>
                            <div className="row">
                                <Field
                                    form={form}
                                    name="admin_radius"
                                    label="Card & canvas radius"
                                    type="select"
                                    options={{
                                        '0px': '0px (Square)',
                                        '6px': '6px',
                                        '8px': '8px',
                                        '12px': '12px (Reference)',
                                        '16px': '16px',
                                        '20px': '20px',
                                    }}
                                />
                                <Field
                                    form={form}
                                    name="admin_button_radius"
                                    label="Primary button shape"
                                    type="select"
                                    options={{
                                        '999px': 'Pill (Reference)',
                                        '10px': 'Soft rounded (10px)',
                                        '8px': 'Rounded (8px)',
                                        '4px': 'Sharp (4px)',
                                    }}
                                />
                            </div>
                            <hr style={{ border: 0, borderTop: '1px solid var(--line)', margin: '14px 0' }} />
                            <Check form={form} name="admin_show_assistant" label="Show bottom floating “Work with Sidekick” bar" />
                            <Field form={form} name="admin_assistant_label" label="Assistant bar placeholder" maxLength={80} />
                            <button className="btn" type="submit" disabled={form.processing}>
                                Save design
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
