import { Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/cms/admin/AdminLayout';
import { confirmVisit, ErrorFlash } from '@/cms/admin/forms';
import { ScanTable, UploadPanel } from '@/cms/admin/extensions';

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
                                {theme.hasSettings ? <Link className="btn btn-light btn-sm" href={route('admin.themes.customize', theme.routeKey)}>Customize</Link> : null}
                                {!theme.active && !theme.isDefault ? (
                                    <button className="btn btn-danger btn-sm" type="button"
                                        onClick={() => confirmVisit(`Delete ${theme.displayName}? Its files and settings are removed.`, 'delete', route('admin.themes.destroy', theme.routeKey))}>
                                        Delete
                                    </button>
                                ) : null}
                            </div>

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
