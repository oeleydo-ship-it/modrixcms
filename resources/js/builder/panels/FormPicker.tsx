/**
 * Choosing which form a capture block posts to.
 *
 * It used to be a text box asking for a form's ID — a ULID nobody knows by
 * heart. This lists the workspace's forms by name. A block still pointing at a
 * form that no longer exists keeps its value and says so, rather than silently
 * showing the first form in the list as if it were connected.
 */

import { useEffect, useState } from 'react';
import { Button } from '@shared/ui/Button';
import { Select } from '@shared/ui/Select';
import { listForms, type FormOption } from '../api';
import { FormFieldEditor } from './FormFieldEditor';
import { PanelNote } from './controls';

export function FormPicker({
    value,
    onChange,
    hint,
}: {
    value: string;
    onChange: (formId: string | null) => void;
    hint?: string;
}) {
    const [forms, setForms] = useState<FormOption[] | null>(null);
    const [failed, setFailed] = useState(false);
    const [editing, setEditing] = useState(false);
    useEffect(() => { setEditing(false); }, [value]);

    useEffect(() => {
        let cancelled = false;
        listForms()
            .then((result) => {
                if (!cancelled) {
                    setForms(result);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setFailed(true);
                    setForms([]);
                }
            });
        return () => {
            cancelled = true;
        };
    }, []);

    if (forms === null) {
        return <Select label="Form" size="sm" options={[{ value: '', label: 'Loading forms…' }]} value="" disabled onChange={() => undefined} />;
    }

    const known = value === '' || forms.some((form) => form.id === value);
    const selected = forms.find((form) => form.id === value);

    return (
        <>
            <Select
                label="Form"
                size="sm"
                hint={hint ?? 'Submissions create or update a contact through this form.'}
                options={[
                    { value: '', label: 'Not connected' },
                    ...(!known ? [{ value, label: 'A form that no longer exists' }] : []),
                    ...forms.map((form) => ({ value: form.id, label: form.isActive ? form.name : `${form.name} (inactive)` })),
                ]}
                value={value}
                onChange={(event) => onChange(event.currentTarget.value === '' ? null : event.currentTarget.value)}
            />
            {failed && <PanelNote>Your forms could not be loaded. Reload the builder to try again.</PanelNote>}
            {!failed && forms.length === 0 && (
                <PanelNote>This workspace has no forms yet. Create one under Forms, then pick it here.</PanelNote>
            )}
            {!known && <PanelNote>The connected form was deleted. Pick another, or visitors will not see this block.</PanelNote>}
            {selected && (
                <div className="flex flex-col gap-2">
                    <Button variant="secondary" size="sm" onClick={() => setEditing((open) => !open)}>
                        {editing ? 'Close field editor' : 'Edit form fields'}
                    </Button>
                    {editing && <FormFieldEditor key={value} formId={value} />}
                </div>
            )}
            {selected && !selected.isActive && (
                <PanelNote>That form is inactive, so this block is hidden on the published page until it is switched on.</PanelNote>
            )}
        </>
    );
}

/**
 * Choosing a saved form for the "Saved form" block, by name. The block shows the
 * form exactly as designed under Forms, so only the form is chosen here.
 */
export function SiteFormPicker({ value, onChange }: { value: string; onChange: (slug: string) => void }) {
    const [forms, setForms] = useState<FormOption[] | null>(null);

    useEffect(() => {
        let cancelled = false;
        listForms().then((result) => { if (!cancelled) { setForms(result); } }).catch(() => { if (!cancelled) { setForms([]); } });
        return () => { cancelled = true; };
    }, []);

    if (forms === null) {
        return <Select label="Form" size="sm" options={[{ value: '', label: 'Loading forms…' }]} value="" disabled onChange={() => undefined} />;
    }

    const known = value === '' || forms.some((form) => form.slug === value);

    return (
        <>
            <Select
                label="Form"
                size="sm"
                hint="Shown in full, with its own fields, layout and the colours of the active theme. Design it under Forms."
                options={[
                    { value: '', label: 'Choose a form…' },
                    ...(!known ? [{ value, label: 'A form that no longer exists' }] : []),
                    ...forms.filter((form) => form.slug !== '').map((form) => ({ value: form.slug, label: form.isActive ? form.name : `${form.name} (inactive)` })),
                ]}
                value={value}
                onChange={(event) => onChange(event.currentTarget.value)}
            />
            {forms.length === 0 && <PanelNote>This workspace has no forms yet. Create one under Forms, then pick it here.</PanelNote>}
            {!known && <PanelNote>The chosen form was deleted. Pick another, or visitors will not see this block.</PanelNote>}
        </>
    );
}
