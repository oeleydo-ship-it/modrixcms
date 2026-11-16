import { useCallback, useEffect, useRef, useState } from 'react';
import { KIND_TABS, MediaThumb, formatBytes } from '@/cms/admin/mediaUtils';

const csrfToken = () => decodeURIComponent((document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/) ?? [])[1] ?? '');

/**
 * Pick something from the media library, or upload a new file and pick that.
 * `kind` limits the list (for example "image"); onSelect receives the chosen item.
 */
export default function MediaLibraryModal({ kind = '', onSelect, onClose }) {
    const input = useRef(null);
    const [tab, setTab] = useState(kind);
    const [q, setQ] = useState('');
    const [term, setTerm] = useState('');
    const [page, setPage] = useState(1);
    const [state, setState] = useState({ items: [], lastPage: 1, loading: true, error: null });
    const [uploading, setUploading] = useState(false);

    const load = useCallback(async () => {
        setState((current) => ({ ...current, loading: true, error: null }));

        try {
            const query = new URLSearchParams({ page: String(page) });
            if (tab) {
                query.set('kind', tab);
            }
            if (term) {
                query.set('q', term);
            }

            const response = await fetch(`${route('admin.media.list')}?${query}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) {
                throw new Error('Could not load your media library.');
            }

            const body = await response.json();
            setState({ items: body.data, lastPage: body.meta.last_page, loading: false, error: null });
        } catch (error) {
            setState({ items: [], lastPage: 1, loading: false, error: error.message });
        }
    }, [tab, term, page]);

    useEffect(() => {
        load();
    }, [load]);

    useEffect(() => {
        const onKey = (event) => event.key === 'Escape' && onClose();
        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [onClose]);

    const upload = async (files) => {
        const list = Array.from(files);
        if (!list.length) {
            return;
        }

        setUploading(true);
        const form = new FormData();
        list.forEach((file) => form.append('files[]', file));

        try {
            const response = await fetch(route('admin.media.store'), {
                method: 'POST',
                body: form,
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-XSRF-TOKEN': csrfToken() },
            });
            const body = await response.json().catch(() => ({}));

            if (!response.ok && !body.errors?.length) {
                throw new Error(body.message ?? 'The upload failed.');
            }

            setState((current) => ({ ...current, error: body.errors?.length ? body.errors.join(' ') : null }));
            setPage(1);
            await load();
            if (body.data?.length === 1 && !body.errors?.length) {
                onSelect(body.data[0]);
            }
        } catch (error) {
            setState((current) => ({ ...current, error: error.message }));
        } finally {
            setUploading(false);
            if (input.current) {
                input.current.value = '';
            }
        }
    };

    const tabs = kind ? [] : KIND_TABS;

    return (
        <div className="media-modal" role="dialog" aria-modal="true" aria-label="Media library" onClick={(event) => event.target === event.currentTarget && onClose()}>
            <div className="media-sheet media-picker">
                <button type="button" className="media-close" onClick={onClose} aria-label="Close">×</button>
                <h3>Media library</h3>

                <div className="media-picker-bar">
                    {tabs.length ? (
                        <div className="media-tabs" role="tablist">
                            {tabs.map((item) => (
                                <button key={item.value} type="button" role="tab" aria-selected={tab === item.value} className={tab === item.value ? 'is-active' : ''} onClick={() => { setTab(item.value); setPage(1); }}>{item.label}</button>
                            ))}
                        </div>
                    ) : null}
                    <form className="toolbar" onSubmit={(event) => { event.preventDefault(); setTerm(q); setPage(1); }}>
                        <input type="search" value={q} onChange={(event) => setQ(event.target.value)} placeholder="Search…" />
                        <button className="btn btn-light btn-sm" type="submit">Search</button>
                        <button className="btn btn-sm" type="button" onClick={() => input.current?.click()} disabled={uploading}>{uploading ? 'Uploading…' : 'Upload'}</button>
                    </form>
                    <input ref={input} type="file" multiple hidden accept={kind === 'image' ? '.jpg,.jpeg,.png,.gif,.webp,.avif' : undefined} onChange={(event) => upload(event.target.files)} />
                </div>

                {state.error ? <div className="flash err" role="alert">{state.error}</div> : null}

                {state.items.length ? (
                    <ul className="media-grid">
                        {state.items.map((item) => (
                            <li key={item.id} className="media-card">
                                <button type="button" className="media-thumb" onClick={() => onSelect(item)} aria-label={`Use ${item.name}`}>
                                    <MediaThumb item={item} />
                                </button>
                                <div className="media-meta">
                                    <span className="media-name" title={item.name}>{item.name}</span>
                                    <span className="sub">{formatBytes(item.size)}</span>
                                </div>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="empty media-empty">{state.loading ? 'Loading…' : 'Nothing here yet. Upload a file to get started.'}</p>
                )}

                {state.lastPage > 1 ? (
                    <div className="pager">
                        <button type="button" className="btn btn-light btn-sm" disabled={page <= 1} onClick={() => setPage(page - 1)}>← Previous</button>
                        <span>Page {page} of {state.lastPage}</span>
                        <button type="button" className="btn btn-light btn-sm" disabled={page >= state.lastPage} onClick={() => setPage(page + 1)}>Next →</button>
                    </div>
                ) : null}
            </div>
        </div>
    );
}
