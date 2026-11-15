import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/cms/admin/AdminLayout';
import { confirmVisit, ErrorFlash } from '@/cms/admin/forms';
import { ScanTable, UploadPanel } from '@/cms/admin/extensions';

/** One-click demo content: the theme's pages, header and footer, built in the visual builder. */
function DemoImport({ theme }) {
    const [open, setOpen] = useState(false);
    const [setHome, setSetHome] = useState(true);
    const [replace, setReplace] = useState(false);
    const [busy, setBusy] = useState(false);
    const demo = theme.demo;
    const parts = [
        `${demo.pages.length} ${demo.pages.length === 1 ? 'page' : 'pages'}`,
        demo.header ? 'site header' : null,
        demo.footer ? 'site footer' : null,
    ].filter(Boolean).join(', ');

    if (!theme.active) {
        return <p className="hint">Includes demo content ({parts}). Activate the theme to import it.</p>;
    }

    const submit = () => {
        router.post(route('admin.themes.demo', theme.routeKey), { set_home: setHome, replace }, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => { setBusy(false); setOpen(false); },
        });
    };

    return (
        <div className="theme-demo">
            {!open ? (
                <button className="btn btn-sm" type="button" onClick={() => setOpen(true)}>Import demo</button>
            ) : (
                <div className="panel" style={{ marginTop: 12 }}>
                    <h3 style={{ marginTop: 0 }}>Import the {theme.displayName} demo</h3>
                    <p className="hint">
                        Adds {parts}, all published and editable in the visual builder: {demo.pages.join(', ')}.
                        Importing again updates these pages instead of copying them.
                    </p>
                    <label className="check"><input type="checkbox" checked={setHome} onChange={(e) => setSetHome(e.target.checked)} /> Make the demo home page your home page</label>
                    <label className="check"><input type="checkbox" checked={replace} onChange={(e) => setReplace(e.target.checked)} /> Replace pages that use the same address (their old content stays in History)</label>
                    <div className="actions" style={{ marginTop: 12 }}>
                        <button className="btn btn-sm" type="button" disabled={busy} onClick={submit}>{busy ? 'Importing…' : 'Import demo'}</button>
                        <button className="btn btn-light btn-sm" type="button" disabled={busy} onClick={() => setOpen(false)}>Cancel</button>
                    </div>
                </div>
            )}
        </div>
    );
}

export default function ThemesIndex({ themes, uploadsAllowed, maxMb }) {
    const { errors } = usePage().props;

    const run = (theme, routeName) => {
        const trustNote = theme.needsTrust
            ? `${theme.displayName} contains scripts that run in your visitors’ and your browser. Only continue if you trust where it came from.`
            : null;

        confirmVisit(trustNote, 'post', route(routeName, theme.routeKey), theme.needsTrust ? { trust: 1 } : {});
    };

    return (
        <AdminLayout title="Appearance" subtitle="Themes change how your public site looks. Preview one before you activate it.">
            <ErrorFlash errors={errors} keys={['theme']} />

            <div className="theme-grid">
                {themes.map((theme) => (
                    <article key={theme.name} className={`theme-card${theme.active ? ' is-active' : ''}`}>
                        <div className="theme-shot">
                            {theme.screenshot ? <img src={theme.screenshot} alt={`${theme.displayName} screenshot`} loading="lazy" /> : <span>{theme.displayName.slice(0, 1)}</span>}
                        </div>
                        <div className="theme-body">
                            <h2>
                                {theme.displayName}
                                {theme.active ? <span className="badge green">Active</span> : null}
                                {theme.isDefault ? <span className="badge gray">Default</span> : null}
                            </h2>
                            <div className="sub">{theme.name} · v{theme.version}{theme.author ? ` · ${theme.author}` : ''}</div>

                            {theme.problem ? <p className="error">{theme.problem}</p>
                                : !theme.compatible ? <p className="error">Needs CMS {theme.cmsVersion}; this site runs {theme.siteVersion}.</p>
                                    : <p className="hint">{theme.description}</p>}

                            <div className="actions">
                                {theme.usable && !theme.active ? (
                                    <>
                                        <button className="btn btn-sm" type="button" onClick={() => run(theme, 'admin.themes.activate')}>Activate</button>
                                        <button className="btn btn-light btn-sm" type="button" onClick={() => run(theme, 'admin.themes.preview')}>Preview</button>
                                    </>
                                ) : null}
                                {theme.usable ? <Link className="btn btn-light btn-sm" href={theme.designUrl}>Theme settings</Link> : null}
                                {theme.hasSettings ? <Link className="btn btn-light btn-sm" href={route('admin.themes.customize', theme.routeKey)}>More options</Link> : null}
                                {!theme.active && !theme.isDefault ? (
                                    <button className="btn btn-danger btn-sm" type="button"
                                        onClick={() => confirmVisit(`Delete ${theme.displayName}? Its files and settings are removed.`, 'delete', route('admin.themes.destroy', theme.routeKey))}>
                                        Delete
                                    </button>
                                ) : null}
                            </div>

                            {theme.usable && theme.demo ? <DemoImport theme={theme} /> : null}

                            {theme.scan?.length ? (
                                <details className="theme-scan">
                                    <summary>Code scan: {theme.scan.length} {theme.scan.length === 1 ? 'note' : 'notes'}</summary>
                                    <ScanTable scan={theme.scan} kind="theme" />
                                </details>
                            ) : null}
                        </div>
                    </article>
                ))}
            </div>

            <UploadPanel kind="theme" url={route('admin.themes.upload')} allowed={uploadsAllowed} maxMb={maxMb} />
        </AdminLayout>
    );
}

ThemesIndex.reload = () => router.reload({ only: ['themes'] });
