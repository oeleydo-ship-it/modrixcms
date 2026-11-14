import { api } from '@shared/api/client';

export interface EditableFormField {
    id: string;
    type: string;
    name: string;
    label: string;
    help_text: string | null;
    is_required: boolean;
    [key: string]: unknown;
}
export interface FieldDraft extends EditableFormField { removed?: boolean; isNew?: boolean }
export interface FieldOperation { method: 'post' | 'patch' | 'delete'; id: string; body?: Record<string, unknown> }

/** PATCH only the properties explicitly edited here; keep choices, custom links and rules untouched. */
export function fieldOperations(original: EditableFormField[], draft: FieldDraft[]): FieldOperation[] {
    return draft.flatMap((field): FieldOperation[] => {
        if (field.isNew) {
            return field.removed ? [] : [{ method: 'post', id: field.id, body: { type: field.type, label: field.label, help_text: field.help_text || null, is_required: field.is_required } }];
        }
        const before = original.find((item) => item.id === field.id);
        if (!before) return [];
        if (field.removed) return [{ method: 'delete', id: field.id }];
        const body: Record<string, unknown> = {};
        for (const key of ['label', 'help_text', 'is_required'] as const) {
            if ((field[key] ?? null) !== (before[key] ?? null)) body[key] = field[key];
        }
        return Object.keys(body).length ? [{ method: 'patch', id: field.id, body }] : [];
    });
}
export async function fetchEditableFields(formId: string): Promise<EditableFormField[]> {
    const response = await api.get<{ data: { fields: EditableFormField[] } }>(`forms/${formId}`);
    return response.data.data.fields;
}
export async function persistFieldOperation(formId: string, operation: FieldOperation): Promise<EditableFormField | null> {
    if (operation.method === 'delete') { await api.delete(`form-fields/${operation.id}`); return null; }
    const response = operation.method === 'post'
        ? await api.post<{ data: EditableFormField }>(`forms/${formId}/fields`, operation.body)
        : await api.patch<{ data: EditableFormField }>(`form-fields/${operation.id}`, operation.body);
    return response.data.data;
}
export function notifyFormUpdated(formId: string): void {
    window.dispatchEvent(new CustomEvent('fnl:form-updated', { detail: { formId } }));
}
