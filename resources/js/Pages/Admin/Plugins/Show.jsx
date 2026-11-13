import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/cms/admin/AdminLayout';
import { confirmVisit, ErrorFlash } from '@/cms/admin/forms';
import { PluginStatus, ScanTable } from '@/cms/admin/extensions';

export default function PluginShow({ plugin }) {
    const { errors } = usePage().props;
    const [trust, setTrust] = useState(false);
    const [deleteData, setDeleteData] = useState(false);

    return (
        <AdminLayout
            title={plugin.displayName}
            subtitle={`${plugin.name} · v${plugin.version}${plugin.author ? ` · by ${plugin.author}` : ''}`}
            actions={<Link className="btn btn-light" href={route('admin.plugins.index')}>All plugins</Link>}
        >
            <ErrorFlash errors={errors} keys={['plugin']} />
            {plugin.error ? <div className="flash err" role="alert"><strong>This plugin failed and was turned off:</strong> {plugin.error}</div> : null}

            <div className="grid-2">
                <div className="panel">
                    <h2>About</h2>
                    {plugin.problem ? <p className="error">{plugin.problem}</p> : (
                        <>
                            <p className="hint">{plugin.description || 'No description.'}</p>
                            <table>
                                <tbody>
                                    <tr><td className="sub">Status</td><td><PluginStatus plugin={plugin} /></td></tr>
                                    <tr><td className="sub">Needs CMS</td><td>{plugin.cmsVersion} (this site: {plugin.siteVersion})</td></tr>
                                    <tr><td className="sub">License</td><td>{plugin.license ?? '—'}</td></tr>
                                    <tr><td className="sub">Adds</td><td>{plugin.adds.length ? plugin.adds.join(', ') : 'code only'}</td></tr>
                                    {plugin.trustedAt ? <tr><td className="sub">Trusted</td><td>{plugin.trustedAt}{plugin.trustedBy ? ` by ${plugin.trustedBy}` : ''}</td></tr> : null}
                                </tbody>
                            </table>
                        </>
                    )}
                </div>

                <div className="panel">
                    <h2>{plugin.enabled ? 'Manage' : 'Enable'}</h2>
                    {plugin.enabled ? (
                        <div className="actions">
                            {plugin.hasSettings ? <Link className="btn btn-light" href={route('admin.plugins.settings', plugin.routeKey)}>Settings</Link> : null}
                            <button className="btn btn-light" type="button" onClick={() => router.post(route('admin.plugins.disable', plugin.routeKey), {}, { preserveScroll: true })}>Disable</button>
                        </div>
                    ) : plugin.usable ? (
                        <form onSubmit={(e) => { e.preventDefault(); router.post(route('admin.plugins.enable', plugin.routeKey), trust ? { trust: 1 } : {}, { preserveScroll: true }); }}>
                            <p className="hint">Enabling runs the plugin’s code on your server and in the browser, and creates its database tables. Read the code scan first.</p>
                            {!plugin.trusted ? <label className="check" style={{ margin: 0 }}><input type="checkbox" checked={trust} onChange={(e) => setTrust(e.target.checked)} required /> I trust this code</label> : null}
                            <div style={{ marginTop: 14 }}><button className="btn" type="submit">Enable plugin</button></div>
                        </form>
                    ) : (
                        <p className="hint">This plugin can’t be enabled. Upload a fixed or compatible version, or uninstall it.</p>
                    )}

                    <hr style={{ border: 0, borderTop: '1px solid var(--line, #e4e6ee)', margin: '22px 0' }} />
                    <h2>Uninstall</h2>
                    {plugin.hasMigrations ? (
                        <label className="check"><input type="checkbox" checked={deleteData} onChange={(e) => setDeleteData(e.target.checked)} /> Also delete its data (drops the plugin’s database tables)</label>
                    ) : null}
                    <button className="btn btn-danger" type="button"
                        onClick={() => confirmVisit(`Uninstall ${plugin.displayName}? Its files are removed.`, 'delete', route('admin.plugins.destroy', plugin.routeKey), deleteData ? { delete_data: 1 } : {})}>
                        Uninstall
                    </button>
                </div>
            </div>

            <div className="panel">
                <h2>Code scan</h2>
                <ScanTable scan={plugin.scan} kind="plugin" />
            </div>
        </AdminLayout>
    );
}
