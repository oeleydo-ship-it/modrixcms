import { Link, router, usePage } from '@inertiajs/react';
import AdminLayout, { ReferenceEmptyArt } from '@/cms/admin/AdminLayout';
import { ErrorFlash } from '@/cms/admin/forms';
import { PluginStatus, UploadPanel } from '@/cms/admin/extensions';

export default function PluginsIndex({ plugins, uploadsAllowed, maxMb }) {
    const { errors } = usePage().props;

    return (
        <AdminLayout title="Plugins" subtitle="Plugins add features: new blocks, admin screens, forms and more.">
            <ErrorFlash errors={errors} keys={['plugin']} />
            <div className="panel">
                <div className="table-wrap">
                    <table>
                        <thead><tr><th>Plugin</th><th>Status</th><th>Code scan</th><th></th></tr></thead>
                        <tbody>
                            {plugins.length ? plugins.map((plugin) => (
                                <tr key={plugin.name}>
                                    <td className="title">
                                        <Link href={route('admin.plugins.show', plugin.routeKey)}>{plugin.displayName}</Link>
                                        <div className="sub">{plugin.name} · v{plugin.version}{plugin.author ? ` · ${plugin.author}` : ''}</div>
                                        {plugin.description ? <div className="sub">{plugin.description}</div> : null}
                                    </td>
                                    <td><PluginStatus plugin={plugin} /></td>
                                    <td className="sub">
                                        {plugin.scan === null ? '—'
                                            : Object.keys(plugin.scanSummary).length
                                                ? Object.entries(plugin.scanSummary).map(([severity, count]) => `${count} ${severity}`).join(', ')
                                                : 'Nothing flagged'}
                                    </td>
                                    <td>
                                        <div className="actions">
                                            <Link className="btn btn-light btn-sm" href={route('admin.plugins.show', plugin.routeKey)}>{plugin.enabled ? 'Details' : 'Review'}</Link>
                                            {plugin.enabled && plugin.hasSettings ? <Link className="btn btn-light btn-sm" href={route('admin.plugins.settings', plugin.routeKey)}>Settings</Link> : null}
                                            {plugin.enabled ? <button className="btn btn-light btn-sm" type="button" onClick={() => router.post(route('admin.plugins.disable', plugin.routeKey), {}, { preserveScroll: true })}>Disable</button> : null}
                                        </div>
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan={4} className="empty">
                                        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', padding: '24px 0' }}>
                                            <ReferenceEmptyArt />
                                            <strong style={{ color: 'var(--ink)', fontSize: 15, marginBottom: 4 }}>Your plugins will show here</strong>
                                            <span>No plugins yet. Upload one below.</span>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <UploadPanel kind="plugin" url={route('admin.plugins.upload')} allowed={uploadsAllowed} maxMb={maxMb} />
        </AdminLayout>
    );
}
