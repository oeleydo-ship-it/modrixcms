import { useMemo, useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/cms/admin/AdminLayout';

/**
 * Appearance → Theme settings: one theme's own colours, typography, buttons, sections and
 * links, one tab per group, with a live preview. Saved values become --site-* CSS variables
 * that the site, builder pages and themed designs follow.
 */
export default function ThemeDesign({ theme, groups, fonts, values, defaults, action }) {
    const form = useForm({ values: { ...values } });
    const groupKeys = Object.keys(groups);
    const [tab, setTab] = useState(groupKeys[0]);
    const current = form.data.values;

    const set = (name, value) => form.setData('values', { ...current, [name]: value });
    const vars = useMemo(() => cssVariables(groups, current), [groups, current]);
    const fontsHref = useMemo(() => fontsUrl(fonts, current), [fonts, current]);
    const changed = JSON.stringify(current) !== JSON.stringify(values);

    const resetGroup = () => {
        const next = { ...current };
        Object.keys(groups[tab].fields).forEach((name) => { next[name] = defaults[name]; });
        form.setData('values', next);
    };

    return (
        <AdminLayout
            title={`Theme settings · ${theme.displayName}`}
            subtitle={theme.active ? 'Your live theme. Pages linked to these settings update everywhere when you save.' : 'Saved for this theme; they apply when it is active or previewed.'}
            actions={<>
                <Link className="btn btn-light" href={route('admin.themes.index')}>All themes</Link>
                {theme.hasOwnSettings ? <Link className="btn btn-light" href={theme.customizeUrl}>More options</Link> : null}
                <button className="btn" type="button" disabled={form.processing || !changed} onClick={() => form.put(action, { preserveScroll: true })}>Save theme settings</button>
            </>}
        >
            {fontsHref ? <link rel="stylesheet" href={fontsHref} /> : null}

            <nav className="tabs" aria-label="Theme settings">
                {groupKeys.map((key) => (
                    <a key={key} href={`#${key}`} className={tab === key ? 'on' : undefined} onClick={(e) => { e.preventDefault(); setTab(key); }}>{groups[key].label}</a>
                ))}
            </nav>

            <div className="design-layout">
                <div className="panel">
                    <div className="design-head">
                        <div>
                            <h2>{groups[tab].label}</h2>
                            <p className="hint">{groups[tab].description}</p>
                        </div>
                        <button className="btn btn-light btn-sm" type="button" onClick={resetGroup}>Reset to theme defaults</button>
                    </div>

                    <div className={tab === 'colors' ? 'design-colors' : undefined}>
                        {Object.entries(groups[tab].fields).map(([name, field]) => (
                            <DesignField key={name} name={name} field={field} value={current[name]} onChange={(value) => set(name, value)} error={form.errors[`values.${name}`]} />
                        ))}
                    </div>
                </div>

                <div className="panel design-preview-panel">
                    <h2>Preview</h2>
                    <p className="hint">How sections, text and buttons look with these settings.</p>
                    <Preview vars={vars} />
                </div>
            </div>
        </AdminLayout>
    );
}

function DesignField({ name, field, value, onChange, error }) {
    const id = `design-${name}`;

    if (field.type === 'toggle') {
        return (
            <div className="field">
                <label className="check"><input type="checkbox" checked={!!value} onChange={(e) => onChange(e.target.checked)} /> {field.label}</label>
            </div>
        );
    }

    return (
        <div className={`field${error ? ' has-error' : ''}`}>
            <label htmlFor={id}>{field.label}</label>
            {field.type === 'color' ? (
                <div className="color-row">
                    <input type="color" aria-label={`${field.label} picker`} value={value} onChange={(e) => onChange(e.target.value)} />
                    <input id={id} type="text" value={value} maxLength={7} pattern="#[0-9a-fA-F]{6}" onChange={(e) => onChange(e.target.value)} />
                </div>
            ) : (
                <select id={id} value={value} onChange={(e) => onChange(e.target.value)}>
                    {Object.entries(field.options).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                </select>
            )}
            {field.help ? <div className="help">{field.help}</div> : null}
            {error ? <div className="error">{error}</div> : null}
        </div>
    );
}

/** A small page made only of the settings, so every change shows at once. */
function Preview({ vars }) {
    const button = {
        display: 'inline-block', padding: 'var(--site-button-padding)', borderRadius: 'var(--site-button-radius)',
        fontWeight: 'var(--site-button-weight)', textTransform: 'var(--site-button-transform)', textDecoration: 'none',
        transition: 'transform .2s ease, box-shadow .2s ease',
    };
    const heading = (level) => ({
        fontFamily: 'var(--site-heading-font)', fontWeight: 'var(--site-heading-weight)', color: 'var(--site-heading)',
        letterSpacing: 'var(--site-heading-tracking)', lineHeight: 'var(--site-heading-line-height)',
        fontSize: `calc(var(--site-${level}-size) * 0.62)`, margin: '0 0 10px',
    });

    return (
        <div className="design-preview" style={{ ...vars, fontFamily: 'var(--site-body-font)', fontSize: 'var(--site-font-size)', lineHeight: 'var(--site-line-height)', color: 'var(--site-body)', background: 'var(--site-background)' }}>
            <header style={{
                display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12, padding: '0 20px',
                height: 'calc(var(--site-header-height) * 0.72)', background: 'var(--site-header-bg)', color: 'var(--site-header-text)',
                borderBottom: 'var(--site-header-border)', backdropFilter: 'var(--site-header-blur)',
            }}>
                <strong style={{ fontFamily: 'var(--site-heading-font)', fontSize: 15, letterSpacing: '-.02em' }}>Modrix</strong>
                <nav style={{ display: 'flex', alignItems: 'center', gap: 'var(--site-nav-gap)', fontSize: 12, fontWeight: 'var(--site-nav-weight)', opacity: 0.8 }}>
                    <span>Features</span>
                    <span>Blog</span>
                    <span>Pricing</span>
                </nav>
            </header>

            <section style={{ position: 'relative', padding: 'calc(var(--site-section-padding) * 0.4) 24px', overflow: 'hidden' }}>
                <div aria-hidden="true" style={{
                    position: 'absolute', top: -60, right: -60, width: 220, height: 220, pointerEvents: 'none',
                    background: 'radial-gradient(circle, color-mix(in srgb, var(--site-primary) 20%, transparent), transparent 65%)',
                    opacity: 'var(--site-hero-glow)',
                }} />
                <span style={{
                    display: 'inline-flex', alignItems: 'center', gap: 6, padding: '3px 10px', marginBottom: 10,
                    background: 'var(--site-surface)', border: '1px solid var(--site-border)', borderRadius: 'var(--site-badge-radius)',
                    fontSize: 11, fontWeight: 600, color: 'var(--site-secondary)',
                }}>
                    Open source commerce
                </span>
                <p style={{ color: 'var(--site-primary)', fontWeight: 700, fontSize: 11, letterSpacing: '.08em', textTransform: 'var(--site-eyebrow-transform)', margin: '0 0 6px' }}>New release</p>
                <h1 style={heading('h1')}>
                    Build pages your visitors{' '}
                    <em style={{
                        fontStyle: 'normal', color: 'var(--site-primary)', background: 'var(--site-hero-em-bg)',
                        padding: 'var(--site-hero-em-padding)', borderRadius: 'var(--site-hero-em-radius)',
                    }}>remember</em>
                </h1>
                <p style={{ margin: '0 0 18px', maxWidth: 460 }}>
                    Body text reads like this.{' '}
                    <a href="#preview" onClick={(e) => e.preventDefault()} style={{ color: 'var(--site-link)', textDecoration: 'var(--site-link-decoration)', textUnderlineOffset: 'var(--site-link-underline-offset)' }}>Links look like this</a>,
                    and <span style={{ color: 'var(--site-muted)' }}>muted text like this</span>.
                </p>
                <span style={{ display: 'inline-flex', gap: 10, flexWrap: 'wrap' }}>
                    <a href="#preview" onClick={(e) => e.preventDefault()} className="design-preview-btn" style={{ ...button, background: 'var(--site-button-bg)', color: 'var(--site-button-fg)', border: 'var(--site-button-border)' }}>Main button</a>
                    <a href="#preview" onClick={(e) => e.preventDefault()} style={{ ...button, background: 'color-mix(in srgb, var(--site-secondary) 10%, transparent)', color: 'var(--site-secondary)', border: 'var(--site-button-border-width) solid transparent' }}>Second button</a>
                </span>
            </section>

            <section style={{ padding: 'calc(var(--site-section-padding) * 0.35) 24px', background: 'var(--site-surface)', borderTop: '1px solid var(--site-border)', borderBottom: '1px solid var(--site-border)' }}>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(140px, 1fr))', gap: 'var(--site-grid-gap)' }}>
                    {['Fast', 'Flexible'].map((title) => (
                        <div key={title} className="design-preview-card" style={{ background: 'var(--site-background)', border: '1px solid var(--site-border)', borderRadius: 'var(--site-card-radius)', boxShadow: 'var(--site-shadow)', padding: 'var(--site-card-padding)' }}>
                            <h3 style={heading('h3')}>{title}</h3>
                            <p style={{ margin: 0, fontSize: '.9em' }}>Cards use the surface, padding, gap, corners and shadow settings.</p>
                            <span style={{ display: 'inline-block', marginTop: 10, background: 'var(--site-accent)', color: 'var(--site-heading)', borderRadius: 'var(--site-badge-radius)', padding: '2px 10px', fontSize: 12, fontWeight: 600 }}>Accent</span>
                        </div>
                    ))}
                </div>
            </section>

            <section style={{ margin: '16px 20px', padding: 'calc(var(--site-section-padding) * 0.3) 20px', background: 'var(--site-dark)', color: 'var(--site-on-dark-muted)', borderRadius: 'calc(var(--site-section-radius) * 0.65)' }}>
                <h2 style={{ ...heading('h2'), color: 'var(--site-on-dark)' }}>A dark section</h2>
                <p style={{ margin: 0 }}>Featured dark bands use the dark-section colours and corner radius.</p>
            </section>

            <footer style={{
                display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12, padding: '14px 20px',
                background: 'var(--site-footer-bg)', color: 'var(--site-footer-text)', borderTop: 'var(--site-footer-border)', fontSize: 12,
            }}>
                <strong style={{ color: 'var(--site-heading)' }}>Modrix</strong>
                <span>© Your store</span>
            </footer>
        </div>
    );
}

/** Mirrors App\Cms\Themes\DesignTokens::cssVariables() for the live preview. */
function cssVariables(groups, values) {
    const vars = {};
    const systemFonts = { Georgia: 'Georgia, "Times New Roman", serif', System: 'system-ui, -apple-system, "Segoe UI", sans-serif' };

    Object.values(groups).forEach((group) => Object.entries(group.fields).forEach(([name, field]) => {
        const key = typeof values[name] === 'boolean' ? (values[name] ? '1' : '0') : String(values[name]);
        const varName = `--site-${field.var ?? name.replaceAll('_', '-')}`;
        let value = field.css?.[key] ?? key;

        if (field.var === 'heading-font' || field.var === 'body-font') {
            value = systemFonts[key] ?? `"${key}", system-ui, sans-serif`;
        }

        vars[varName] = value;
    }));

    const outline = values.button_style === 'outline';
    vars['--site-button-bg'] = outline ? 'transparent' : 'var(--site-primary)';
    vars['--site-button-fg'] = outline ? 'var(--site-primary)' : 'var(--site-on-primary)';
    vars['--site-button-border'] = outline
        ? 'var(--site-button-border-width) solid var(--site-primary)'
        : 'var(--site-button-border-width) solid transparent';

    const hoverMap = {
        none: ['none', 'none'],
        lift: ['translateY(-2px)', 'none'],
        glow: ['translateY(-2px)', '0 12px 24px -10px var(--site-primary)'],
        scale: ['scale(1.03)', 'none'],
    };
    const [hoverTransform, hoverShadow] = hoverMap[values.button_hover] ?? hoverMap.glow;
    vars['--site-button-hover-transform'] = hoverTransform;
    vars['--site-button-hover-shadow'] = hoverShadow;

    const highlightMap = {
        underline: ['linear-gradient(transparent 62%, var(--site-accent) 62%, var(--site-accent) 92%, transparent 92%)', '0 .1em', '0'],
        pill: ['color-mix(in srgb, var(--site-accent) 45%, transparent)', '0 .25em', '.2em'],
        plain: ['none', '0', '0'],
    };
    const [emBg, emPadding, emRadius] = highlightMap[values.hero_highlight] ?? highlightMap.underline;
    vars['--site-hero-em-bg'] = emBg;
    vars['--site-hero-em-padding'] = emPadding;
    vars['--site-hero-em-radius'] = emRadius;

    const scale = Number(groups.typography.fields.heading_scale.css[values.heading_scale] ?? 1);
    Object.entries({ h1: 3.25, h2: 2.4, h3: 1.5, h4: 1.2 }).forEach(([level, rem]) => { vars[`--site-${level}-size`] = `${rem * scale}rem`; });

    return vars;
}

function fontsUrl(fonts, values) {
    const families = [...new Set([fonts[values.heading_font], fonts[values.body_font]].filter(Boolean))];

    return families.length ? `https://fonts.bunny.net/css?family=${families.join('|')}&display=swap` : null;
}
