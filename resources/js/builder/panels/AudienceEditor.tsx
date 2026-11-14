/**
 * "Who sees this" for a section, row, column or block (§63).
 *
 * Rules live in the node's visibility settings and are checked on the server
 * when a published page loads, so content hidden from a visitor is never
 * sent to their browser. The builder canvas always shows everything.
 */

import { Button } from '@shared/ui/Button';
import { Input } from '@shared/ui/Input';
import { Select } from '@shared/ui/Select';
import { PanelNote } from './controls';

type Field = 'known_contact' | 'tag' | 'lifecycle_stage' | 'purchased_product' | 'country' | 'utm_source';

interface Rule {
    field: Field;
    operator: 'is' | 'is_not';
    value: string;
}

interface Audience {
    match: 'all' | 'any';
    rules: Rule[];
}

const FIELDS: { value: Field; label: string; hint?: string }[] = [
    { value: 'known_contact', label: 'Is a known contact' },
    { value: 'tag', label: 'Has tag', hint: 'The tag name, e.g. vip' },
    { value: 'lifecycle_stage', label: 'Lifecycle stage' },
    { value: 'purchased_product', label: 'Bought product', hint: 'The product ID, from Products' },
    { value: 'country', label: 'Country', hint: 'Two-letter code, e.g. DE' },
    { value: 'utm_source', label: 'Campaign source', hint: 'The utm_source value, e.g. newsletter' },
];

const STAGES = ['subscriber', 'lead', 'marketing_qualified', 'sales_qualified', 'opportunity', 'customer', 'evangelist', 'other'];

export function AudienceEditor({ audience, onChange }: { audience: Audience | null | undefined; onChange: (audience: Audience | null) => void }) {
    const rules = audience?.rules ?? [];
    const match = audience?.match ?? 'all';
    const update = (next: Rule[], nextMatch: 'all' | 'any' = match): void => onChange(next.length > 0 ? { match: nextMatch, rules: next } : null);
    const change = (index: number, patch: Partial<Rule>): void => update(rules.map((rule, position) => (position === index ? { ...rule, ...patch } : rule)));

    return (
        <div className="flex flex-col gap-2 pt-2">
            <PanelNote>
                Show this only to some visitors. Rules are checked when the published page loads; hidden content is never sent. Greet known visitors with{' '}
                <code>{'{{ contact.first_name | there }}'}</code> in any text.
            </PanelNote>

            {rules.length > 1 && (
                <Select
                    size="sm"
                    label="Show when"
                    options={[
                        { value: 'all', label: 'All rules match' },
                        { value: 'any', label: 'Any rule matches' },
                    ]}
                    value={match}
                    onChange={(event) => update(rules, event.currentTarget.value === 'any' ? 'any' : 'all')}
                />
            )}

            {rules.map((rule, index) => {
                const field = FIELDS.find((option) => option.value === rule.field);
                return (
                    <div key={index} className="flex flex-col gap-1.5 rounded-md border border-[var(--color-border)] p-2">
                        <Select size="sm" label="Visitor" options={FIELDS.map(({ value, label }) => ({ value, label }))} value={rule.field} onChange={(event) => change(index, { field: event.currentTarget.value as Field, value: '' })} />
                        <Select
                            size="sm"
                            label="Condition"
                            options={[
                                { value: 'is', label: rule.field === 'known_contact' ? 'Yes' : 'Matches' },
                                { value: 'is_not', label: rule.field === 'known_contact' ? 'No' : 'Does not match' },
                            ]}
                            value={rule.operator}
                            onChange={(event) => change(index, { operator: event.currentTarget.value === 'is_not' ? 'is_not' : 'is' })}
                        />
                        {rule.field === 'lifecycle_stage' ? (
                            <Select
                                size="sm"
                                label="Stage"
                                options={[{ value: '', label: 'Choose a stage' }, ...STAGES.map((stage) => ({ value: stage, label: stage.replace(/_/g, ' ') }))]}
                                value={rule.value}
                                onChange={(event) => change(index, { value: event.currentTarget.value })}
                            />
                        ) : rule.field !== 'known_contact' ? (
                            <Input size="sm" label="Value" hint={field?.hint} value={rule.value} maxLength={120} onChange={(event) => change(index, { value: event.currentTarget.value })} />
                        ) : null}
                        <Button size="xs" variant="ghost" onClick={() => update(rules.filter((_, position) => position !== index))}>
                            Remove rule
                        </Button>
                    </div>
                );
            })}

            {rules.length < 10 && (
                <Button size="xs" variant="secondary" onClick={() => update([...rules, { field: 'tag', operator: 'is', value: '' }])}>
                    Add audience rule
                </Button>
            )}
        </div>
    );
}
