import { useEffect, useState } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import { Button } from '@shared/ui/Button';
import { Input } from '@shared/ui/Input';
import { Select } from '@shared/ui/Select';
import { Switch } from '@shared/ui/Switch';
import { Textarea } from '@shared/ui/Textarea';
import {
    fetchEditableFields,
    fieldOperations,
    persistFieldOperation,
    notifyFormUpdated,
    type EditableFormField,
    type FieldDraft,
} from '../formFieldEditing';

const FIELD_TYPES = [
    { value: 'text', label: 'Text' },
    { value: 'email', label: 'Email' },
    { value: 'phone', label: 'Phone' },
    { value: 'textarea', label: 'Long text' },
    { value: 'number', label: 'Number' },
    { value: 'select', label: 'Dropdown' },
    { value: 'radio', label: 'Radio choice' },
    { value: 'checkbox', label: 'Checkbox' },
    { value: 'consent', label: 'Consent' },
    { value: 'date', label: 'Date' },
    { value: 'time', label: 'Time' },
    { value: 'url', label: 'URL' },
    { value: 'rating', label: 'Star rating' },
] as const;

const WIDTH_OPTIONS = [
    { value: 'full', label: 'Full width' },
    { value: 'half', label: 'Half width' },
    { value: 'third', label: 'One-third' },
];

export function FormFieldEditor({ formId }: { formId: string }) {
    const [original, setOriginal] = useState<EditableFormField[]>([]);
    const [draft, setDraft] = useState<FieldDraft[] | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);
    const [saved, setSaved] = useState(false);
    const [newType, setNewType] = useState('text');

    useEffect(() => {
        let cancelled = false;
        void fetchEditableFields(formId)
            .then((fields) => {
                if (!cancelled) {
                    setOriginal(fields);
                    setDraft(fields);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setError('Could not load form fields. Close and reopen this panel to retry.');
                }
            });
        return () => {
            cancelled = true;
        };
    }, [formId]);

    const edit = (id: string, changes: Partial<FieldDraft>) => {
        setSaved(false);
        setDraft((rows) => rows?.map((row) => (row.id === id ? { ...row, ...changes } : row)) ?? null);
    };

    async function save() {
        if (!draft) return;
        const operations = fieldOperations(original, draft);
        if (draft.some((field) => !field.removed && !field.label.trim())) {
            setError('Every field needs a label.');
            return;
        }
        setBusy(true);
        setError(null);
        setSaved(false);
        let changed = false;
        try {
            for (const operation of operations) {
                const field = await persistFieldOperation(formId, operation);
                changed = true;
                setOriginal((rows) =>
                    field ? [...rows.filter((row) => row.id !== operation.id), field] : rows.filter((row) => row.id !== operation.id),
                );
                setDraft((rows) => rows?.flatMap((row) => (row.id === operation.id ? (field ? [field] : []) : [row])) ?? null);
            }
            setSaved(true);
        } catch (problem) {
            const detail = problem as { message?: string; errors?: Record<string, string[]> };
            setError(
                [
                    detail.message ?? 'Could not save fields.',
                    ...Object.values(detail.errors ?? {}).flat(),
                    'Any completed changes were saved; retry saves only the remaining edits.',
                ].join(' '),
            );
        } finally {
            if (changed) notifyFormUpdated(formId);
            setBusy(false);
        }
    }

    const addField = () => {
        setSaved(false);
        const typeLabel = FIELD_TYPES.find((t) => t.value === newType)?.label ?? 'Field';
        setDraft((rows) => [
            ...(rows ?? []),
            {
                id: `new-${crypto.randomUUID()}`,
                name: typeLabel.toLowerCase().replace(/[^a-z0-9]+/g, '_'),
                type: newType,
                label: typeLabel,
                placeholder: newType === 'phone' ? '+1 555 000 0000' : null,
                help_text: null,
                is_required: false,
                width: 'full',
                options: ['select', 'radio', 'checkbox_group'].includes(newType) ? ['Option 1', 'Option 2'] : [],
                isNew: true,
            },
        ]);
    };

    return (
        <div className="flex flex-col gap-2.5 py-2">
            <p className="text-[length:var(--text-xs)] text-[var(--color-text-muted)]">
                These fields belong to the connected form. Saving updates every page using it.
            </p>
            {error && (
                <p role="alert" className="text-[length:var(--text-xs)] text-[var(--color-danger)]">
                    {error}
                </p>
            )}
            {draft === null && !error && (
                <p role="status" className="text-[length:var(--text-xs)] text-[var(--color-text-muted)]">
                    Loading fields…
                </p>
            )}
            {draft && (
                <fieldset disabled={busy} className="m-0 flex flex-col gap-2.5 border-0 p-0">
                    {draft.map((field, index) => (
                        <div
                            key={field.id}
                            className="flex flex-col gap-2 rounded-md border border-[var(--color-border)] bg-[var(--color-surface-subtle)] p-2.5"
                            style={{ opacity: field.removed ? 0.55 : 1 }}
                        >
                            <div className="flex items-center justify-between gap-2 text-[length:var(--text-xs)] font-medium text-[var(--color-text-muted)]">
                                <span>
                                    {field.type} · {field.isNew ? 'New field' : field.name}
                                </span>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    iconOnly
                                    title={field.removed ? 'Undo removal' : 'Remove field'}
                                    aria-label={field.removed ? 'Undo removal' : 'Remove field'}
                                    onClick={() => edit(field.id, { removed: !field.removed })}
                                >
                                    <Trash2 className="h-3.5 w-3.5 text-[var(--color-danger)]" aria-hidden />
                                </Button>
                            </div>
                            {!field.removed && (
                                <>
                                    <Input
                                        label={`Field ${index + 1} label`}
                                        size="sm"
                                        value={field.label}
                                        maxLength={160}
                                        onChange={(event) => edit(field.id, { label: event.target.value })}
                                    />
                                    <Input
                                        label="Field name"
                                        size="sm"
                                        value={field.name}
                                        maxLength={60}
                                        onChange={(event) =>
                                            edit(field.id, {
                                                name: event.target.value.replace(/[^a-z0-9_]/gi, '_').toLowerCase(),
                                            })
                                        }
                                    />
                                    <Input
                                        label="Placeholder"
                                        size="sm"
                                        value={field.placeholder ?? ''}
                                        maxLength={200}
                                        onChange={(event) => edit(field.id, { placeholder: event.target.value || null })}
                                    />
                                    <Input
                                        label="Help text"
                                        size="sm"
                                        value={field.help_text ?? ''}
                                        maxLength={255}
                                        onChange={(event) => edit(field.id, { help_text: event.target.value || null })}
                                    />
                                    <Select
                                        label="Width"
                                        size="sm"
                                        options={WIDTH_OPTIONS}
                                        value={field.width ?? 'full'}
                                        onChange={(event) => edit(field.id, { width: event.currentTarget.value })}
                                    />
                                    {['select', 'radio', 'checkbox_group'].includes(field.type) && (
                                        <Textarea
                                            label="Options (one per line)"
                                            rows={3}
                                            value={(field.options ?? []).join('\n')}
                                            onChange={(event) =>
                                                edit(field.id, {
                                                    options: event.currentTarget.value.split('\n'),
                                                })
                                            }
                                        />
                                    )}
                                    <Switch
                                        size="sm"
                                        label="Required"
                                        checked={field.is_required}
                                        onCheckedChange={(checked) => edit(field.id, { is_required: checked })}
                                    />
                                </>
                            )}
                            {field.removed && (
                                <Button variant="ghost" size="sm" onClick={() => edit(field.id, { removed: false })}>
                                    Undo removal
                                </Button>
                            )}
                        </div>
                    ))}
                    <div className="flex items-end gap-2">
                        <div className="flex-1">
                            <Select
                                label="New field type"
                                size="sm"
                                options={[...FIELD_TYPES]}
                                value={newType}
                                onChange={(event) => setNewType(event.currentTarget.value)}
                            />
                        </div>
                        <Button variant="secondary" size="sm" onClick={addField}>
                            <Plus className="h-3.5 w-3.5" aria-hidden /> Add
                        </Button>
                    </div>
                    <Button
                        variant="primary"
                        size="sm"
                        onClick={() => void save()}
                        disabled={fieldOperations(original, draft).length === 0}
                    >
                        {busy ? 'Saving fields…' : 'Save form fields'}
                    </Button>
                </fieldset>
            )}
            {saved && (
                <p role="status" className="text-[length:var(--text-xs)] text-[var(--color-success)]">
                    Form fields saved. The canvas has been refreshed.
                </p>
            )}
        </div>
    );
}
