import { useEffect, useState } from 'react';
import { api } from '@shared/api/client';
import { Button } from '@shared/ui/Button';
import { Modal } from '@shared/ui/Modal';
import { TemplatePreview } from '@shared/templates/TemplatePreview';
import { normaliseBlock, normaliseDocument, normaliseSection, createSection, createRow } from '@renderer';
import { useBuilderStore } from '../store';
import { cloneBlock, insertBlock } from '../tree';
import type { PageResponse } from '../api';

type Template = { id: string; name: string; description: string | null; type: string; category_label: string; payload?: unknown };
type InstallResult = { page: PageResponse | null; fragment: unknown };
const CATEGORIES = [
    ['lead_generation', 'Lead generation'], ['product_sales', 'Product sales'], ['webinar', 'Webinar'],
    ['course', 'Course'], ['booking', 'Booking'], ['newsletter', 'Newsletter'], ['saas_signup', 'SaaS signup'],
    ['free_trial', 'Free trial'], ['event', 'Event'], ['ecommerce', 'E-commerce'],
    ['application', 'Application'], ['survey', 'Survey'], ['sections', 'Sections & blocks'],
] as const;

function message(error: unknown): string {
    return typeof error === 'object' && error !== null && 'message' in error && typeof error.message === 'string'
        ? error.message : 'The template could not be loaded. Please try again.';
}

/** Apply through the installation API so the server validates nodes and creates capture forms. */
export function TemplateLibrary({ open, onClose }: { open: boolean; onClose: () => void }) {
    const [rows, setRows] = useState<Template[]>([]);
    const [type, setType] = useState('page');
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [selected, setSelected] = useState<Template | null>(null);
    const [loading, setLoading] = useState(false);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [retry, setRetry] = useState(0);
    const [savingTemplate, setSavingTemplate] = useState(false);
    const [templateName, setTemplateName] = useState('');
    const [templateCategory, setTemplateCategory] = useState('lead_generation');
    const [createdName, setCreatedName] = useState<string | null>(null);
    const saveState = useBuilderStore((state) => state.saveState);
    const ready = saveState === 'saved' || saveState === 'idle';

    useEffect(() => {
        if (!open) return;
        const controller = new AbortController();
        setLoading(true);
        setError(null);
        setSelected(null);
        const timer = window.setTimeout(() => {
            api.get<{ data: Template[]; meta?: { last_page?: number } }>('templates', {
                params: { type, search, page, per_page: 12 }, signal: controller.signal,
            }).then((response) => {
                if (controller.signal.aborted) return;
                setRows(response.data.data);
                setLastPage(response.data.meta?.last_page ?? 1);
            }).catch((reason: unknown) => {
                if (!controller.signal.aborted) setError(message(reason));
            }).finally(() => { if (!controller.signal.aborted) setLoading(false); });
        }, 200);
        return () => { window.clearTimeout(timer); controller.abort(); };
    }, [open, type, search, page, retry]);

    async function preview(template: Template) {
        setBusy(true);
        setError(null);
        try {
            const response = await api.get<{ data: Template }>(`templates/${template.id}`);
            setSelected(response.data.data);
        } catch (reason) { setError(message(reason)); }
        finally { setBusy(false); }
    }

    async function install() {
        if (!selected || busy) return;
        const before = useBuilderStore.getState();
        if (!['saved', 'idle'].includes(before.saveState)) return;
        if (selected.type === 'page' && !window.confirm('Replace this page with the template? Your current layout is kept in Undo. The new layout is saved as a draft.')) return;
        setBusy(true);
        setError(null);
        try {
            const response = await api.post<{ data: InstallResult }>(`templates/${selected.id}/install`, {
                page_id: before.pageId, expected_version: before.version,
            });
            const result = response.data.data;
            const state = useBuilderStore.getState();
            if (selected.type === 'page') {
                if (!result.page) throw new Error('The server did not return the installed page. Reload before editing.');
                state.commit(normaliseDocument(result.page.document));
                state.select(null);
                state.markSaved(result.page.version);
            } else if (selected.type === 'section') {
                state.insertSections([normaliseSection(result.fragment)]);
            } else {
                const block = cloneBlock(normaliseBlock(result.fragment));
                const columns = state.document.sections.flatMap((section) => section.rows.flatMap((row) => row.columns));
                const selectedColumn = state.selected?.kind === 'column' ? state.selected.id : null;
                const columnId = columns.find((column) => column.id === selectedColumn)?.id ?? columns[0]?.id;
                if (columnId) {
                    state.commit(insertBlock(state.document, block, { kind: 'column', columnId }));
                    state.select({ kind: 'block', id: block.id });
                } else {
                    const section = createSection();
                    const row = createRow();
                    row.columns[0]!.blocks = [block];
                    section.rows = [row];
                    state.insertSections([section]);
                }
            }
            onClose();
        } catch (reason) {
            if (typeof reason === 'object' && reason !== null && 'status' in reason && reason.status === 409) useBuilderStore.getState().markConflict();
            setError(message(reason));
        } finally { setBusy(false); }
    }

    async function saveTemplate() {
        const state = useBuilderStore.getState();
        const name = templateName.trim();
        if (busy || !['saved', 'idle'].includes(state.saveState) || name.length < 2 || name.length > 120) return;
        setBusy(true);
        setError(null);
        setCreatedName(null);
        try {
            await api.post('templates', { type: 'page', page_id: state.pageId, name, category: templateCategory });
            setCreatedName(name);
            setSavingTemplate(false);
            setTemplateName('');
            setType('page');
            setSearch('');
            setPage(1);
            setRetry((value) => value + 1);
        } catch (reason) { setError(message(reason)); }
        finally { setBusy(false); }
    }

    return <Modal open={open} onClose={() => { if (!busy) onClose(); }} title="Template library" size="full"
        description="Choose a complete page or add a reusable section or block. Changes stay editable in this builder."
        footer={<><Button variant="secondary" disabled={busy} onClick={onClose}>Close</Button>{selected && <Button loading={busy} disabled={!ready} onClick={() => void install()}>{selected.type === 'page' ? 'Replace page with template' : `Insert ${selected.type}`}</Button>}</>}>
        <div className="flex flex-wrap items-center gap-3 pb-4">
            <input className="rounded border border-[var(--color-border)] p-2" aria-label="Search templates" placeholder="Search templates" value={search} disabled={busy} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
            <select className="rounded border border-[var(--color-border)] p-2" aria-label="Template type" value={type} disabled={busy} onChange={(event) => { setType(event.target.value); setPage(1); }}><option value="page">Pages</option><option value="section">Sections</option><option value="block">Blocks</option></select>
            <Button variant="secondary" disabled={busy} onClick={() => { setSavingTemplate(!savingTemplate); setError(null); }}>Save page as template</Button>
        </div>
        {!ready && <p role="status" className="mb-3 text-sm">{saveState === 'conflict' ? 'This page changed elsewhere. Reload it before saving or installing a template.' : saveState === 'error' ? 'Resolve the page save error before saving or installing a template.' : 'Waiting for your current changes to finish saving…'}</p>}
        {savingTemplate && <form className="mb-4 rounded-lg border border-[var(--color-border)] p-4" onSubmit={(event) => { event.preventDefault(); void saveTemplate(); }}>
            <h3 className="mb-2 font-semibold">Save this page to your template library</h3>
            <p className="mb-3 text-sm text-[var(--color-text-muted)]">Creates a reusable copy of the saved draft. Your current page stays unchanged.</p>
            <div className="flex flex-wrap items-end gap-3">
                <label className="flex flex-col gap-1 text-sm">Template name<input className="rounded border border-[var(--color-border)] p-2" value={templateName} required minLength={2} maxLength={120} disabled={busy} onChange={(event) => setTemplateName(event.target.value)} /></label>
                <label className="flex flex-col gap-1 text-sm">Category<select className="rounded border border-[var(--color-border)] p-2" value={templateCategory} disabled={busy} onChange={(event) => setTemplateCategory(event.target.value)}>{CATEGORIES.map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></label>
                <Button type="submit" loading={busy} disabled={!ready || templateName.trim().length < 2}>Save template</Button>
                <Button variant="ghost" disabled={busy} onClick={() => setSavingTemplate(false)}>Cancel</Button>
            </div>
        </form>}
        {createdName && <p role="status" className="mb-3 text-sm">“{createdName}” was saved to your templates. Find it under Pages in this library.</p>}
        {error && <div role="alert" className="mb-3 text-red-600">{error} <Button size="sm" variant="ghost" onClick={() => setRetry((value) => value + 1)}>Retry library</Button></div>}
        {selected ? <><Button variant="ghost" disabled={busy} onClick={() => setSelected(null)}>← Back to templates</Button><h3 className="my-3 font-semibold">{selected.name}</h3><TemplatePreview payload={selected.payload} type={selected.type} /></> : loading ? <p role="status">Loading templates…</p> : <>
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">{rows.map((template) => <article key={template.id} className="rounded-lg border border-[var(--color-border)] p-4"><p className="text-xs text-[var(--color-text-muted)]">{template.category_label}</p><h3 className="my-2 font-semibold">{template.name}</h3><p className="mb-4 text-sm">{template.description}</p><Button size="sm" variant="secondary" disabled={busy} onClick={() => void preview(template)}>Preview {template.type}</Button></article>)}</div>
            {rows.length === 0 && !error && <p>No templates match these filters. Save a page as a template, or try another search.</p>}
            {lastPage > 1 && <div className="mt-4 flex items-center gap-3"><Button disabled={page <= 1 || busy} variant="secondary" onClick={() => setPage(page - 1)}>Previous</Button><span>Page {page} of {lastPage}</span><Button disabled={page >= lastPage || busy} variant="secondary" onClick={() => setPage(page + 1)}>Next</Button></div>}
        </>}
    </Modal>;
}
