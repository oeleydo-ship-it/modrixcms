import { useForm } from '@inertiajs/react';

/** Upload form for a theme or plugin zip. */
export function UploadPanel({ kind, url, allowed, maxMb }) {
    const form = useForm({ package: null });

    return (
        <div className="panel">
            <h2>Upload a {kind}</h2>
            {allowed ? (
                <>
                    <p className="hint">A .zip with <code>{kind}.json</code> at its root, up to {maxMb} MB. Uploading a higher version of an installed {kind} updates it. Only install {kind}s from people you trust: they run code on your server and in your visitors’ browsers.</p>
                    <form onSubmit={(e) => { e.preventDefault(); form.post(url, { forceFormData: true, onSuccess: () => form.reset() }); }}>
                        <div className={`field${form.errors.package ? ' has-error' : ''}`}>
                            <label htmlFor="package">{kind === 'theme' ? 'Theme' : 'Plugin'} zip</label>
                            <input id="package" type="file" accept=".zip,application/zip" required onChange={(e) => form.setData('package', e.target.files[0] ?? null)} />
                            {form.errors.package ? <div className="error">{form.errors.package}</div> : null}
                        </div>
                        <button className="btn" type="submit" disabled={form.processing}>{form.processing ? 'Uploading…' : `Upload ${kind}`}</button>
                    </form>
                </>
            ) : (
                <p className="hint">Uploads are turned off on this site (<code>CMS_ALLOW_UPLOADS=false</code>). Copy {kind}s into <code>extensions/{kind}s/vendor/name</code> on the server instead.</p>
            )}
        </div>
    );
}

const ORDER = ['critical', 'high', 'medium', 'info'];

/** Findings of the code scan. */
export function ScanTable({ scan, kind }) {
    if (scan === null) {
        return <p className="hint">Not scanned yet. The code is scanned when it is uploaded or first enabled.</p>;
    }

    if (scan.length === 0) {
        return <p className="hint">The code scan found nothing risky. A clean scan doesn’t prove a package is safe.</p>;
    }

    const sorted = [...scan].sort((a, b) => ORDER.indexOf(a.severity) - ORDER.indexOf(b.severity));

    return (
        <>
            <p className="hint">The code scan flagged these lines. They are not necessarily harmful: check that each one makes sense for what the {kind} does.</p>
            <div className="table-wrap">
                <table>
                    <thead><tr><th>Level</th><th>Where</th><th>What it does</th></tr></thead>
                    <tbody>
                        {sorted.map((finding, i) => (
                            <tr key={i}>
                                <td><span className={`badge ${['critical', 'high'].includes(finding.severity) ? 'amber' : 'gray'}`}>{finding.severity[0].toUpperCase() + finding.severity.slice(1)}</span></td>
                                <td className="sub"><code>{finding.file}:{finding.line}</code></td>
                                <td>{finding.message}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}

export function PluginStatus({ plugin }) {
    if (plugin.problem) return <span className="badge amber">Broken</span>;
    if (!plugin.compatible) return <span className="badge amber">Incompatible</span>;
    if (plugin.error) return <span className="badge amber">Failed</span>;
    if (plugin.enabled) return <span className="badge green">Enabled</span>;

    return <span className="badge gray">Disabled</span>;
}
