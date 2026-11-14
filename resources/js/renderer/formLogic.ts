/**
 * Conditional form fields (§24), evaluated in the browser exactly as
 * `App\Domain\Forms\Services\FormLogic` evaluates them on the server: a field
 * the visitor's answers hide is not rendered, so it is never posted, and the
 * server neither requires nor stores it.
 */

export type ConditionOperator = 'equals' | 'not_equals' | 'contains' | 'not_contains' | 'greater_than' | 'less_than' | 'is_empty' | 'is_not_empty';

export interface ConditionRule {
    field: string;
    operator: ConditionOperator;
    value: string | number | null;
}

export interface FieldConditions {
    action: 'show' | 'hide';
    match: 'all' | 'any';
    rules: ConditionRule[];
}

export type FormAnswers = Record<string, string | string[]>;

const OPERATORS: readonly ConditionOperator[] = ['equals', 'not_equals', 'contains', 'not_contains', 'greater_than', 'less_than', 'is_empty', 'is_not_empty'];

export function normaliseConditions(raw: unknown): FieldConditions | null {
    if (typeof raw !== 'object' || raw === null) {
        return null;
    }
    const source = raw as Record<string, unknown>;
    const rules = (Array.isArray(source['rules']) ? source['rules'] : []).flatMap((item): ConditionRule[] => {
        if (typeof item !== 'object' || item === null) {
            return [];
        }
        const rule = item as Record<string, unknown>;
        const value = rule['value'];
        return typeof rule['field'] === 'string' && OPERATORS.includes(rule['operator'] as ConditionOperator)
            ? [{ field: rule['field'], operator: rule['operator'] as ConditionOperator, value: typeof value === 'string' || typeof value === 'number' ? value : null }]
            : [];
    });

    if (rules.length === 0) {
        return null;
    }

    return { action: source['action'] === 'hide' ? 'hide' : 'show', match: source['match'] === 'any' ? 'any' : 'all', rules };
}

/** The current answers of a form element, as they would be posted. */
export function readAnswers(form: HTMLFormElement): FormAnswers {
    const answers: FormAnswers = {};
    const data = new FormData(form);

    data.forEach((value, key) => {
        if (typeof value !== 'string' || value === '') {
            return;
        }
        if (key.endsWith('[]')) {
            const name = key.slice(0, -2);
            const current = answers[name];
            answers[name] = [...(Array.isArray(current) ? current : []), value];
        } else {
            answers[key] = value;
        }
    });

    return answers;
}

function ruleMatches(rule: ConditionRule, answers: FormAnswers): boolean {
    const actual = answers[rule.field];
    const expected = rule.value === null ? '' : String(rule.value).trim().toLowerCase();
    const list = (Array.isArray(actual) ? actual : actual === undefined ? [] : [actual]).map((value) => value.trim().toLowerCase()).filter((value) => value !== '');
    const contains = expected !== '' && list.some((value) => value.includes(expected));
    const numeric = (value: string | undefined): value is string => value !== undefined && value !== '' && !Number.isNaN(Number(value));
    const first = list[0];

    switch (rule.operator) {
        case 'equals':
            return list.includes(expected);
        case 'not_equals':
            return !list.includes(expected);
        case 'contains':
            return contains;
        case 'not_contains':
            return !contains;
        case 'greater_than':
            return numeric(first) && numeric(expected) && Number(first) > Number(expected);
        case 'less_than':
            return numeric(first) && numeric(expected) && Number(first) < Number(expected);
        case 'is_empty':
            return list.length === 0;
        case 'is_not_empty':
            return list.length > 0;
    }
}

export function fieldVisible(field: { conditions?: FieldConditions | null }, answers: FormAnswers): boolean {
    const conditions = field.conditions;
    if (!conditions || conditions.rules.length === 0) {
        return true;
    }
    const results = conditions.rules.map((rule) => ruleMatches(rule, answers));
    const matched = conditions.match === 'any' ? results.includes(true) : !results.includes(false);
    return conditions.action === 'hide' ? !matched : matched;
}
