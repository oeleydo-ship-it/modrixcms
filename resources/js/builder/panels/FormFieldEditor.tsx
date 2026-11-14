import { useEffect, useState } from 'react';
import { fetchEditableFields, fieldOperations, persistFieldOperation, notifyFormUpdated, type EditableFormField, type FieldDraft } from '../formFieldEditing';

export function FormFieldEditor({ formId }: { formId: string }) {
    const [original, setOriginal] = useState<EditableFormField[]>([]);
    const [draft, setDraft] = useState<FieldDraft[] | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);
    const [saved, setSaved] = useState(false);
    const [newType, setNewType] = useState('text');
    useEffect(() => {
        let cancelled = false;
        void fetchEditableFields(formId).then((fields) => { if (!cancelled) { setOriginal(fields); setDraft(fields); } }).catch(() => { if (!cancelled) setError('Could not load form fields. Close and reopen this panel to retry.'); });
        return () => { cancelled = true; };
    }, [formId]);
    const edit = (id: string, changes: Partial<FieldDraft>) => { setSaved(false); setDraft((rows) => rows?.map((row) => row.id === id ? { ...row, ...changes } : row) ?? null); };
    async function save() {
        if (!draft) return;
        const operations = fieldOperations(original, draft);
        if (draft.some((field) => !field.removed && !field.label.trim())) { setError('Every field needs a label.'); return; }
        setBusy(true); setError(null); setSaved(false);
        let changed = false;
        try {
            for (const operation of operations) {
                const field = await persistFieldOperation(formId, operation);
                changed = true;
                setOriginal((rows) => field ? [...rows.filter((row) => row.id !== operation.id), field] : rows.filter((row) => row.id !== operation.id));
                setDraft((rows) => rows?.flatMap((row) => row.id === operation.id ? (field ? [field] : []) : [row]) ?? null);
            }
            setSaved(true);
        } catch (problem) {
            const detail = problem as { message?: string; errors?: Record<string, string[]> };
            setError([detail.message ?? 'Could not save fields.', ...Object.values(detail.errors ?? {}).flat(), 'Any completed changes were saved; retry saves only the remaining edits.'].join(' '));
        } finally {
            if (changed) notifyFormUpdated(formId);
            setBusy(false);
        }
    }
    return <div style={{ display: 'grid', gap: 10, padding: '10px 0' }}>
        <p style={{ margin: 0, fontSize: 12 }}>These fields belong to the connected form. Saving updates every page using it, including published pages. Page Undo does not undo form changes.</p>
        {error && <p role="alert" style={{ color: '#b91c1c', fontSize: 12 }}>{error}</p>}
        {draft === null && !error && <p role="status">Loading fields…</p>}
        {draft && <fieldset disabled={busy} style={{ border: 0, padding: 0, margin: 0, display: 'grid', gap: 10 }}>
            {draft.map((field, index) => <div key={field.id} style={{ border: '1px solid #dbe2ea', padding: 8, borderRadius: 6, opacity: field.removed ? .6 : 1 }}>
                <div style={{ fontSize: 11, marginBottom: 6 }}>{field.type} · {field.isNew ? 'New field' : field.name}</div>
                {!field.removed && <>
                    <label style={{ display: 'grid', gap: 4, fontSize: 12 }}>Field {index + 1} label<input aria-label={`Field ${index + 1} label`} value={field.label} maxLength={160} onChange={(event) => edit(field.id, { label: event.target.value })} style={{ width: '100%', padding: 6, border: '1px solid #cbd5e1', borderRadius: 4 }} /></label>
                    <label style={{ display: 'grid', gap: 4, fontSize: 12, marginTop: 6 }}>Help text<input aria-label={`Field ${index + 1} help text`} value={field.help_text ?? ''} maxLength={255} onChange={(event) => edit(field.id, { help_text: event.target.value || null })} style={{ width: '100%', padding: 6, border: '1px solid #cbd5e1', borderRadius: 4 }} /></label>
                    <label style={{ display: 'block', fontSize: 12, marginTop: 6 }}><input type="checkbox" checked={field.is_required} onChange={(event) => edit(field.id, { is_required: event.target.checked })} /> Required</label>
                </>}
                <button type="button" onClick={() => edit(field.id, { removed: !field.removed })} style={{ fontSize: 12, marginTop: 6, color: '#9f1239' }}>{field.removed ? 'Undo removal' : 'Remove field'}</button>
            </div>)}
            <label style={{ display: 'grid', gap: 4, fontSize: 12 }}>New field type<select aria-label="New field type" value={newType} onChange={(event) => setNewType(event.target.value)}>{['text', 'email', 'textarea', 'date'].map((type) => <option key={type} value={type}>{type}</option>)}</select></label>
            <button type="button" onClick={() => { setSaved(false); setDraft((rows) => [...(rows ?? []), { id: `new-${crypto.randomUUID()}`, name: '', type: newType, label: 'New field', help_text: null, is_required: false, isNew: true }]); }}>Add field</button>
            <button type="button" onClick={() => void save()} disabled={fieldOperations(original, draft).length === 0}>{busy ? 'Saving fields…' : 'Save form fields'}</button>
        </fieldset>}
        {saved && <p role="status" style={{ color: '#15803d', fontSize: 12 }}>Form fields saved. The canvas has been refreshed.</p>}
    </div>;
}
