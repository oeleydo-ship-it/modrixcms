/**
 * The settings-panel editor for a block's repeated items — FAQ questions,
 * pricing plans, navigation links, feature cards, social profiles, tabs and
 * accordion panels.
 *
 * Text is also editable straight on the canvas; this is where the structure
 * lives: adding, removing and reordering, and the fields that have no natural
 * place on the canvas (a link's URL, a plan's highlight, a card's icon).
 *
 * Every change hands back the whole new list plus what kind of change it was,
 * so a block whose children line up with its items (tabs, accordion) can move
 * or remove the matching child in the same undo step.
 */

import { useState } from 'react';
import { ArrowDown, ArrowUp, ChevronDown, ChevronRight, Plus, Trash2 } from 'lucide-react';
import { Button } from '@shared/ui/Button';
import { Input } from '@shared/ui/Input';
import { Select } from '@shared/ui/Select';
import { Switch } from '@shared/ui/Switch';
import { Textarea } from '@shared/ui/Textarea';
import { cn } from '@shared/lib/cn';

export type ItemRecord = Record<string, unknown>;

export type ItemField =
    | { key: string; label: string; kind: 'text' | 'url' | 'textarea'; placeholder?: string; hint?: string }
    | { key: string; label: string; kind: 'select'; options: ReadonlyArray<{ value: string; label: string }> }
    | { key: string; label: string; kind: 'switch'; description?: string }
    /** A list of strings edited one per line. */
    | { key: string; label: string; kind: 'lines'; hint?: string };

export type ItemListChange =
    | { kind: 'edit'; index: number }
    | { kind: 'add' }
    | { kind: 'remove'; index: number }
    | { kind: 'move'; from: number; to: number };

export interface ItemListEditorProps {
    /** Plural noun for the heading and empty state: "questions", "plans". */
    noun: string;
    /** Singular noun for the add button: "question", "plan". */
    singular: string;
    items: ItemRecord[];
    fields: readonly ItemField[];
    /** A fresh item for "Add". */
    create: (index: number) => ItemRecord;
    /** The collapsed row's label. */
    title: (item: ItemRecord, index: number) => string;
    onChange: (items: ItemRecord[], change: ItemListChange) => void;
    /** Fewest items allowed — a tab strip with no tabs is not a block. */
    min?: number;
}

function text(item: ItemRecord, key: string): string {
    const value = item[key];
    return typeof value === 'string' ? value : typeof value === 'number' ? String(value) : '';
}

export function ItemListEditor({ noun, singular, items, fields, create, title, onChange, min = 0 }: ItemListEditorProps) {
    // The row just added opens by itself, so its fields are right there to fill.
    const [open, setOpen] = useState<number | null>(null);

    const edit = (index: number, patch: ItemRecord): void => {
        onChange(
            items.map((item, position) => (position === index ? { ...item, ...patch } : item)),
            { kind: 'edit', index },
        );
    };

    const move = (from: number, to: number): void => {
        if (to < 0 || to >= items.length) {
            return;
        }
        const next = [...items];
        const [moved] = next.splice(from, 1);
        if (moved === undefined) {
            return;
        }
        next.splice(to, 0, moved);
        onChange(next, { kind: 'move', from, to });
        setOpen((current) => (current === from ? to : current === to ? from : current));
    };

    const remove = (index: number): void => {
        onChange(items.filter((_, position) => position !== index), { kind: 'remove', index });
        setOpen(null);
    };

    const add = (): void => {
        onChange([...items, create(items.length)], { kind: 'add' });
        setOpen(items.length);
    };

    return (
        <div className="flex flex-col gap-2">
            <div className="flex items-center justify-between">
                <span className="text-[length:var(--text-xs)] font-medium text-[var(--color-text-muted)]">
                    {items.length} {items.length === 1 ? singular : noun}
                </span>
                <Button size="sm" variant="ghost" leadingIcon={<Plus className="h-3.5 w-3.5" />} onClick={add}>
                    Add {singular}
                </Button>
            </div>

            {items.length === 0 && (
                <p className="rounded-md border border-dashed border-[var(--color-border)] px-3 py-4 text-center text-[length:var(--text-xs)] text-[var(--color-text-muted)]">
                    No {noun} yet.
                </p>
            )}

            <ul className="flex flex-col gap-1.5">
                {items.map((item, index) => {
                    const expanded = open === index;
                    const label = title(item, index).trim() || `${singular.charAt(0).toUpperCase()}${singular.slice(1)} ${index + 1}`;

                    return (
                        <li
                            key={index}
                            className={cn(
                                'rounded-md border border-[var(--color-border)] bg-[var(--color-surface)]',
                                expanded && 'border-[var(--color-primary)]',
                            )}
                        >
                            <div className="flex items-center gap-1 px-1.5 py-1">
                                <button
                                    type="button"
                                    className="flex min-w-0 flex-1 items-center gap-1.5 rounded px-1 py-1 text-left text-[length:var(--text-sm)] hover:bg-[var(--color-surface-hover)]"
                                    aria-expanded={expanded}
                                    onClick={() => setOpen(expanded ? null : index)}
                                >
                                    {expanded ? (
                                        <ChevronDown className="h-3.5 w-3.5 shrink-0" aria-hidden />
                                    ) : (
                                        <ChevronRight className="h-3.5 w-3.5 shrink-0" aria-hidden />
                                    )}
                                    <span className="truncate">{label}</span>
                                </button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    iconOnly
                                    aria-label={`Move ${label} up`}
                                    disabled={index === 0}
                                    onClick={() => move(index, index - 1)}
                                >
                                    <ArrowUp className="h-3.5 w-3.5" />
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    iconOnly
                                    aria-label={`Move ${label} down`}
                                    disabled={index === items.length - 1}
                                    onClick={() => move(index, index + 1)}
                                >
                                    <ArrowDown className="h-3.5 w-3.5" />
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    iconOnly
                                    aria-label={`Remove ${label}`}
                                    disabled={items.length <= min}
                                    onClick={() => remove(index)}
                                >
                                    <Trash2 className="h-3.5 w-3.5" />
                                </Button>
                            </div>

                            {expanded && (
                                <div className="flex flex-col gap-3 border-t border-[var(--color-border)] p-2.5">
                                    {fields.map((field) => {
                                        switch (field.kind) {
                                            case 'textarea':
                                                return (
                                                    <Textarea
                                                        key={field.key}
                                                        label={field.label}
                                                        rows={3}
                                                        placeholder={field.placeholder}
                                                        hint={field.hint}
                                                        value={text(item, field.key)}
                                                        onChange={(event) => edit(index, { [field.key]: event.currentTarget.value })}
                                                    />
                                                );
                                            case 'lines':
                                                return (
                                                    <Textarea
                                                        key={field.key}
                                                        label={field.label}
                                                        rows={4}
                                                        hint={field.hint ?? 'One per line.'}
                                                        value={(Array.isArray(item[field.key]) ? (item[field.key] as unknown[]) : [])
                                                            .map((entry) => String(entry))
                                                            .join('\n')}
                                                        onChange={(event) =>
                                                            edit(index, {
                                                                [field.key]: event.currentTarget.value
                                                                    .split('\n')
                                                                    // Trailing blank lines are kept while typing so
                                                                    // Enter can start the next entry.
                                                                    .map((line, position, all) =>
                                                                        position === all.length - 1 ? line : line.trimEnd(),
                                                                    ),
                                                            })
                                                        }
                                                        onBlur={(event) =>
                                                            edit(index, {
                                                                [field.key]: event.currentTarget.value
                                                                    .split('\n')
                                                                    .map((line) => line.trim())
                                                                    .filter((line) => line !== ''),
                                                            })
                                                        }
                                                    />
                                                );
                                            case 'select':
                                                return (
                                                    <Select
                                                        key={field.key}
                                                        label={field.label}
                                                        size="sm"
                                                        options={field.options}
                                                        value={text(item, field.key) || (field.options[0]?.value ?? '')}
                                                        onChange={(event) => edit(index, { [field.key]: event.currentTarget.value })}
                                                    />
                                                );
                                            case 'switch':
                                                return (
                                                    <Switch
                                                        key={field.key}
                                                        size="sm"
                                                        label={field.label}
                                                        description={field.description}
                                                        checked={item[field.key] === true}
                                                        onCheckedChange={(checked) => edit(index, { [field.key]: checked })}
                                                    />
                                                );
                                            default:
                                                return (
                                                    <Input
                                                        key={field.key}
                                                        label={field.label}
                                                        size="sm"
                                                        type={field.kind === 'url' ? 'url' : 'text'}
                                                        placeholder={field.placeholder ?? (field.kind === 'url' ? 'https://…' : undefined)}
                                                        hint={field.hint}
                                                        value={text(item, field.key)}
                                                        onChange={(event) =>
                                                            edit(index, {
                                                                [field.key]:
                                                                    field.kind === 'url' && event.currentTarget.value === ''
                                                                        ? null
                                                                        : event.currentTarget.value,
                                                            })
                                                        }
                                                    />
                                                );
                                        }
                                    })}
                                </div>
                            )}
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}
