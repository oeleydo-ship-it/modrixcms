/**
 * Narrowing helpers for `BlockData.props`, which is `Record<string, unknown>`
 * because its shape is decided by the BlockRegistry entry for the block type.
 * Every block component reads its props through these so a malformed document
 * degrades to the default instead of throwing.
 */

export function str(props: Record<string, unknown>, key: string, fallback = ''): string {
    const value = props[key];
    return typeof value === 'string' ? value : fallback;
}

export function optionalStr(props: Record<string, unknown>, key: string): string | null {
    const value = props[key];
    return typeof value === 'string' && value !== '' ? value : null;
}

export function num(props: Record<string, unknown>, key: string, fallback = 0): number {
    const value = props[key];
    if (typeof value === 'number' && Number.isFinite(value)) {
        return value;
    }
    if (typeof value === 'string' && value.trim() !== '' && Number.isFinite(Number(value))) {
        return Number(value);
    }
    return fallback;
}

export function bool(props: Record<string, unknown>, key: string, fallback = false): boolean {
    const value = props[key];
    return typeof value === 'boolean' ? value : fallback;
}

export function list(props: Record<string, unknown>, key: string): unknown[] {
    const value = props[key];
    return Array.isArray(value) ? value : [];
}

/** Array entries are usually small records; this narrows one safely. */
export function record(value: unknown): Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
        ? (value as Record<string, unknown>)
        : {};
}

export function recordList(props: Record<string, unknown>, key: string): Record<string, unknown>[] {
    return list(props, key).map(record);
}

/** One of a fixed set, falling back when the document carries something else. */
export function oneOf<T extends string>(
    props: Record<string, unknown>,
    key: string,
    allowed: readonly T[],
    fallback: T,
): T {
    const value = props[key];
    return typeof value === 'string' && (allowed as readonly string[]).includes(value) ? (value as T) : fallback;
}

/**
 * Only http(s), mailto, tel and same-origin paths may reach an href. Anything
 * else (notably `javascript:`) is dropped — page documents are authored data.
 */
export function safeHref(value: unknown): string | undefined {
    if (typeof value !== 'string') {
        return undefined;
    }
    const trimmed = value.trim();
    if (trimmed === '') {
        return undefined;
    }
    if (/^(https?:|mailto:|tel:)/i.test(trimmed)) {
        return trimmed;
    }
    if (/^[/#?]/.test(trimmed)) {
        return trimmed;
    }
    return undefined;
}
