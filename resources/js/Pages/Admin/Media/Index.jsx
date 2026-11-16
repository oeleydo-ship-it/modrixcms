import { useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AdminLayout from '@/cms/admin/AdminLayout';
import { Pager, confirmVisit } from '@/cms/admin/forms';
import { KIND_TABS, MediaThumb, formatBytes } from '@/cms/admin/mediaUtils';

export default function MediaIndex({ items, filters, counts, upload }) {
    const { errors } = usePage().props;
    const input = useRef(null);
    const [q, setQ] = useState(filters.q ?? '');
    const [dragging, setDragging] = useState(false);
    const [progress, setProgress] = useState(null);
    const [selected, setSelected] = useState([]);
    const [openId, setOpenId] = useState(null);
    const open = items.data.find((item) => item.id === openId) ?? null;

    const visit = (next) => router.get(route('admin.media.index'), { kind: next.kind || undefined, q: next.q || undefined }, { preserveState: true });

    const send = (list) => {
        const files = Array.from(list);

        if (!files.length) {
            return;
        }

        router.post(route('admin.media.store'), { files }, {
            forceFormData: true,
            preserveScroll: true,
            onProgress: (event) => setProgress(event?.percentage ?? 0),
            onFinish: () => {
                setProgress(null);
                if (input.current) {
                    input.current.value = '';
                }
            },
        });
    };

    const toggle = (id) => setSelected((current) => (current.includes(id) ? current.filter((value) => value !== id) : [...current, id]));

    const removeSelected = () => confirmVisit(
        `Delete ${selected.length} selected file${selected.length === 1 ? '' : 's'}? This can’t be undone.`,
        'delete',
        route('admin.media.destroy-many'),
        { ids: selected },
        { onSuccess: () => setSelected([]) },
    );

    return (
        <AdminLayout
            title="Media"
            subtitle="Upload images and files, then reuse them anywhere on your site."
            actions={<button type="button" className="btn" onClick={() => input.current?.click()} disabled={progress !== null}>{progress !== null ? `Uploading… ${Math.round(progress)}%` : 'Upload files'}</button>}
        >
            <input ref={input} type="file" multiple hidden accept={upload.accept} onChange={(event) => send(event.target.files)} />

            {errors?.files ? <div className="flash err" role="alert">{errors.files}</div> : null}

            <div
                className={`media-drop${dragging ? ' is-over' : ''}`}
                onDragOver={(event) => { event.preventDefault(); setDragging(true); }}
                onDragLeave={() => setDragging(false)}
                onDrop={(event) => { event.preventDefault(); setDragging(false); send(event.dataTransfer.files); }}
            >
                <span><strong>Drop files here</strong> or <button type="button" className="media-link" onClick={() => input.current?.click()}>browse your computer</button></span>
                <span className="sub">Images, videos, PDFs, Office documents, text, zip and audio · up to {upload.maxMb} MB each</span>
            </div>

            <div className="panel">
                <div className="media-tabs" role="tablist">
                    {KIND_TABS.map((tab) => (
                        <button
                            key={tab.value}
                            type="button"
                            role="tab"
                            aria-selected={filters.kind === tab.value}
                            className={filters.kind === tab.value ? 'is-active' : ''}
                            onClick={() => visit({ kind: tab.value, q })}
                        >
                            {tab.label} <span className="media-count">{counts[tab.count]}</span>
                        </button>
                    ))}
                    <span className="media-used sub">{formatBytes(counts.bytes)} used</span>
                </div>

                <form className="toolbar" onSubmit={(event) => { event.preventDefault(); visit({ kind: filters.kind, q }); }}>
                    <input type="search" value={q} onChange={(event) => setQ(event.target.value)} placeholder="Search by file name or description…" />
                    <button className="btn btn-light" type="submit">Search</button>
                    {selected.length ? <button type="button" className="btn btn-danger" onClick={removeSelected}>Delete {selected.length} selected</button> : null}
                </form>

                {items.data.length ? (
                    <ul className="media-grid">
                        {items.data.map((item) => (
                            <li key={item.id} className={`media-card${selected.includes(item.id) ? ' is-selected' : ''}`}>
                                <label className="media-check" title="Select">
                                    <input type="checkbox" checked={selected.includes(item.id)} onChange={() => toggle(item.id)} />
                                </label>
                                <button type="button" className="media-thumb" onClick={() => setOpenId(item.id)} aria-label={`Details for ${item.name}`}>
                                    <MediaThumb item={item} />
                                </button>
                                <div className="media-meta">
                                    <span className="media-name" title={item.name}>{item.name}</span>
                                    <span className="sub">{item.extension.toUpperCase()} · {formatBytes(item.size)}</span>
                                </div>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="empty media-empty">{filters.q || filters.kind ? 'Nothing matches that search.' : 'No files yet. Upload your first image or document above.'}</p>
                )}

                <Pager paginator={items} />
            </div>

            {open ? <MediaDetails key={open.id} item={open} onClose={() => setOpenId(null)} /> : null}
        </AdminLayout>
    );
}

function MediaDetails({ item, onClose }) {
    const [name, setName] = useState(item.name);
    const [alt, setAlt] = useState(item.alt ?? '');
    const [copied, setCopied] = useState(false);
    const address = new URL(item.url, window.location.origin).href;

    const save = (event) => {
        event.preventDefault();
        router.put(route('admin.media.update', item.id), { name, alt }, { preserveScroll: true });
    };

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(address);
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        } catch {
            window.prompt('Copy this address:', address);
        }
    };

    return (
        <div className="media-modal" role="dialog" aria-modal="true" aria-label={`Details for ${item.name}`} onClick={(event) => event.target === event.currentTarget && onClose()}>
            <div className="media-sheet">
                <button type="button" className="media-close" onClick={onClose} aria-label="Close">×</button>
                <div className="media-preview"><MediaThumb item={item} /></div>
                <form className="media-form" onSubmit={save}>
                    <label>File name<input type="text" value={name} onChange={(event) => setName(event.target.value)} required maxLength={255} /></label>
                    {item.kind === 'image' ? <label>Description (alt text)<input type="text" value={alt} onChange={(event) => setAlt(event.target.value)} maxLength={500} /></label> : null}
                    <label>Address<input type="text" value={address} readOnly onFocus={(event) => event.target.select()} /></label>
                    <p className="sub">
                        {item.extension.toUpperCase()} · {formatBytes(item.size)}
                        {item.width ? ` · ${item.width}×${item.height}` : ''}
                        {item.uploaded_by ? ` · uploaded by ${item.uploaded_by}` : ''}
                    </p>
                    <div className="actions">
                        <button className="btn" type="submit">Save</button>
                        <button className="btn btn-light" type="button" onClick={copy}>{copied ? 'Copied' : 'Copy address'}</button>
                        <a className="btn btn-light" href={item.url} target="_blank" rel="noopener">Open</a>
                        <button className="btn btn-danger" type="button" onClick={() => confirmVisit(`Delete “${item.name}”? This can’t be undone.`, 'delete', route('admin.media.destroy', item.id), {}, { onSuccess: onClose })}>Delete</button>
                    </div>
                </form>
            </div>
        </div>
    );
}
