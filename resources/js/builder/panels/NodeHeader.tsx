/**
 * The header every settings panel shares: what is selected, what it is called,
 * and the two destructive-ish actions that belong next to the name.
 *
 * The name is a real document field on all four node kinds, so renaming works
 * the same everywhere — which is what makes the layer tree readable on a long
 * page.
 */

import { useId } from 'react';
import { Copy, Trash } from 'lucide-react';
import { Button } from '@shared/ui/Button';
import { Input } from '@shared/ui/Input';
import type { NodeKind } from '@renderer';

const KIND_LABEL: Record<NodeKind, string> = {
    section: 'Section',
    row: 'Row',
    column: 'Column',
    block: 'Block',
};

export interface NodeHeaderProps {
    kind: NodeKind;
    /** The type name shown as the title — "Heading", "Section", … */
    title: string;
    name: string;
    /** Shown in the name field when the node has no name of its own. */
    namePlaceholder: string;
    onRename: (name: string) => void;
    onDuplicate?: () => void;
    onDelete?: () => void;
}

export function NodeHeader({
    kind,
    title,
    name,
    namePlaceholder,
    onRename,
    onDuplicate,
    onDelete,
}: NodeHeaderProps) {
    const nameId = useId();

    return (
        <header className="flex flex-col gap-2 border-b border-[var(--color-border)] pb-3">
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <p className="text-[length:var(--text-2xs)] tracking-wide text-[var(--color-text-subtle)] uppercase">
                        {KIND_LABEL[kind]}
                    </p>
                    <p className="truncate font-semibold">{title}</p>
                </div>
                {(onDuplicate || onDelete) && (
                    <span className="flex shrink-0 gap-1">
                        {onDuplicate && (
                            <Button
                                variant="ghost"
                                size="sm"
                                iconOnly
                                title={`Duplicate ${KIND_LABEL[kind].toLowerCase()}`}
                                aria-label={`Duplicate ${KIND_LABEL[kind].toLowerCase()}`}
                                onClick={onDuplicate}
                            >
                                <Copy className="h-4 w-4" aria-hidden />
                            </Button>
                        )}
                        {onDelete && (
                            <Button
                                variant="ghost"
                                size="sm"
                                iconOnly
                                title={`Delete ${KIND_LABEL[kind].toLowerCase()}`}
                                aria-label={`Delete ${KIND_LABEL[kind].toLowerCase()}`}
                                onClick={onDelete}
                            >
                                <Trash className="h-4 w-4 text-[var(--color-danger)]" aria-hidden />
                            </Button>
                        )}
                    </span>
                )}
            </div>
            <Input
                id={nameId}
                label="Layer name"
                size="sm"
                value={name}
                placeholder={namePlaceholder}
                onChange={(event) => onRename(event.currentTarget.value)}
            />
        </header>
    );
}
