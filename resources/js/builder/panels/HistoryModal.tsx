import { useEffect, useMemo, useState } from 'react';
import { Clock3, History, RotateCcw } from 'lucide-react';
import { PageRenderer } from '@renderer';
import { Alert } from '@shared/ui/Alert';
import { Badge } from '@shared/ui/Badge';
import { Button } from '@shared/ui/Button';
import { EmptyState } from '@shared/ui/EmptyState';
import { Modal } from '@shared/ui/Modal';
import { Spinner } from '@shared/ui/Spinner';
import { listPageRevisions, restorePageRevision, type PageRevision } from '../api';

function revisionDate(value: string | null): string {
    if (!value) return 'Date unavailable';
    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? 'Date unavailable'
        : new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(date);
}

export function HistoryModal({
    open,
    pageId,
    currentVersion,
    onClose,
    onRestored,
}: {
    open: boolean;
    pageId: string;
    currentVersion: number;
    onClose: () => void;
    onRestored: (result: { version: number; document: PageRevision['document'] }) => void;
}) {
    const [revisions, setRevisions] = useState<PageRevision[]>([]);
    const [selectedId, setSelectedId] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);
    const [restoring, setRestoring] = useState(false);
    const [confirming, setConfirming] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!open) return;
        let cancelled = false;
        setLoading(true);
        setError(null);
        setConfirming(false);
        void listPageRevisions(pageId).then(
            (rows) => {
                if (cancelled) return;
                setRevisions(rows);
                setSelectedId(rows[0]?.id ?? null);
                setLoading(false);
            },
            (reason: unknown) => {
                if (cancelled) return;
                setError(typeof reason === 'object' && reason !== null && 'message' in reason ? String(reason.message) : 'Could not load revision history.');
                setLoading(false);
            },
        );
        return () => { cancelled = true; };
    }, [open, pageId]);

    const selected = useMemo(
        () => revisions.find((revision) => revision.id === selectedId) ?? null,
        [revisions, selectedId],
    );

    const close = () => { if (!restoring) onClose(); };
    const restore = async () => {
        if (!selected || restoring) return;
        setRestoring(true);
        setError(null);
        try {
            const result = await restorePageRevision(pageId, selected.id);
            onRestored(result);
        } catch (reason) {
            setError(typeof reason === 'object' && reason !== null && 'message' in reason ? String(reason.message) : 'Could not restore this revision.');
            setRestoring(false);
            setConfirming(false);
        }
    };

    return (
        <Modal
            open={open}
            onClose={close}
            title="Page history"
            description="Preview a saved or published snapshot, then restore it as the current draft."
            size="xl"
            dismissOnOverlayClick={!restoring}
            footer={selected ? (
                <>
                    <Button variant="secondary" disabled={restoring} onClick={close}>Close</Button>
                    {confirming ? (
                        <Button variant="primary" loading={restoring} leadingIcon={<RotateCcw size={15} />} onClick={() => void restore()}>
                            Confirm restore
                        </Button>
                    ) : (
                        <Button variant="primary" leadingIcon={<RotateCcw size={15} />} onClick={() => setConfirming(true)}>
                            Restore version {selected.version}
                        </Button>
                    )}
                </>
            ) : <Button variant="secondary" onClick={close}>Close</Button>}
        >
            {loading ? (
                <div className="grid min-h-72 place-items-center"><Spinner label="Loading page history" /></div>
            ) : error && revisions.length === 0 ? (
                <Alert tone="danger" title="History unavailable">{error}</Alert>
            ) : revisions.length === 0 ? (
                <EmptyState icon={<History size={20} />} title="No saved revisions yet" description="Revisions appear when you save or publish." />
            ) : (
                <div className="fnl-history-layout">
                    <aside className="fnl-history-list" aria-label="Saved revisions">
                        {revisions.map((revision) => (
                            <button
                                key={revision.id}
                                type="button"
                                className={revision.id === selectedId ? 'is-active' : undefined}
                                onClick={() => { setSelectedId(revision.id); setConfirming(false); }}
                            >
                                <span className="flex items-center justify-between gap-2">
                                    <strong>Version {revision.version}</strong>
                                    {revision.version === currentVersion && <Badge tone="success">Current</Badge>}
                                </span>
                                <span>{revision.label || 'Automatic snapshot'}</span>
                                <time dateTime={revision.created_at ?? undefined}><Clock3 size={12} /> {revisionDate(revision.created_at)}</time>
                            </button>
                        ))}
                    </aside>

                    <section className="fnl-history-preview" aria-label="Revision preview">
                        {error && <Alert tone="danger" title="Restore failed">{error}</Alert>}
                        {confirming && (
                            <Alert tone="warning" title={`Restore version ${selected?.version}?`}>
                                Your current server draft is saved as another history entry first, so this restore can be undone later.
                            </Alert>
                        )}
                        {selected && (
                            <div className="fnl-history-preview-frame">
                                <div className="fnl-history-preview-page">
                                    <PageRenderer document={selected.document} mode="static" breakpoint="desktop" />
                                </div>
                            </div>
                        )}
                    </section>
                </div>
            )}
        </Modal>
    );
}
