/**
 * The block palette.
 *
 * Blocks are grouped exactly as `BlockType::group()` groups them. Each entry is
 * draggable onto the canvas and also activatable from the keyboard, because a
 * builder that can only be used with a mouse is not usable.
 */

import { useMemo, useState } from 'react';
import { useDraggable } from '@dnd-kit/core';
import { Plus } from 'lucide-react';
import { SearchInput } from '@shared/ui/SearchInput';
import { cn } from '@shared/lib/cn';
import {
    BLOCK_GROUP,
    BLOCK_GROUP_LABEL,
    BLOCK_LABEL,
    PALETTE_BLOCK_TYPES,
    type BlockGroup,
} from '@renderer';
import { paletteDraggableId, type DragPayload } from '../dnd';
import {
    SHADCN_PRESET_CHOICES,
    SHADCN_PRESET_LABEL,
    isShadcnPreset,
    type PaletteChoice,
} from '../tree';

const label = (choice: PaletteChoice): string => {
    if (choice === 'columns') {
        return 'Columns';
    }
    if (isShadcnPreset(choice)) {
        return SHADCN_PRESET_LABEL[choice];
    }
    return BLOCK_LABEL[choice];
};

type PaletteGroupKey = BlockGroup | 'navigation' | 'shadcn';

const GROUP_ORDER: readonly PaletteGroupKey[] = [
    'shadcn',
    'navigation',
    'layout',
    'content',
    'code',
    'capture',
    'commerce',
    'persuasion',
    'media',
    'scheduling',
    'engagement',
];

function PaletteItem({ type, onAdd }: { type: PaletteChoice; onAdd: (type: PaletteChoice) => void }) {
    // The payload shape is shared with the canvas's drop resolver; see dnd.ts.
    const payload: DragPayload = { source: 'palette', blockType: type };

    const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
        id: paletteDraggableId(type),
        data: payload,
    });

    return (
        <li>
            <button
                ref={setNodeRef}
                type="button"
                {...listeners}
                {...attributes}
                onClick={() => onAdd(type)}
                title={`Drag a ${label(type)} block onto the page, or click to add it`}
                className={cn(
                    'group flex w-full items-center gap-2 rounded-md border px-2.5 py-2 text-left',
                    'border-[var(--color-border)] bg-[var(--color-surface)]',
                    'text-[length:var(--text-sm)] text-[var(--color-text)]',
                    'hover:border-[var(--color-primary)] hover:bg-[var(--color-surface-hover)]',
                    'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)]',
                    isDragging && 'opacity-50',
                )}
            >
                <span className="flex-1 truncate">{label(type)}</span>
                <Plus
                    className="h-3.5 w-3.5 shrink-0 text-[var(--color-text-subtle)] group-hover:text-[var(--color-text-brand)]"
                    aria-hidden
                />
            </button>
        </li>
    );
}

export function Palette({ onAdd }: { onAdd: (type: PaletteChoice) => void }) {
    const [query, setQuery] = useState('');

    const groups = useMemo(() => {
        const needle = query.trim().toLowerCase();
        return GROUP_ORDER.map((group) => {
            const candidates: PaletteChoice[] =
                group === 'shadcn'
                    ? [...SHADCN_PRESET_CHOICES]
                    : [
                          ...(group === 'layout' ? (['columns'] as const) : []),
                          ...PALETTE_BLOCK_TYPES,
                      ];

            return {
                group,
                types: candidates.filter(
                    (type) =>
                        (group === 'shadcn'
                            ? isShadcnPreset(type)
                            : type === 'columns'
                              ? group === 'layout'
                              : isShadcnPreset(type)
                                ? false
                                : group === 'navigation'
                                  ? ['navigation', 'logo'].includes(type)
                                  : BLOCK_GROUP[type] === group && !['navigation', 'logo'].includes(type)) &&
                        (needle === '' || label(type).toLowerCase().includes(needle)),
                ),
            };
        }).filter((entry) => entry.types.length > 0);
    }, [query]);

    return (
        <div className="flex h-full flex-col gap-3">
            <SearchInput
                value={query}
                onValueChange={setQuery}
                placeholder="Find a block or shadcn component"
                aria-label="Find a block"
                size="sm"
            />

            <div className="flex-1 overflow-y-auto pr-1">
                {groups.length === 0 ? (
                    <p className="px-1 py-6 text-center text-[length:var(--text-sm)] text-[var(--color-text-muted)]">
                        No block matches “{query}”.
                    </p>
                ) : (
                    groups.map(({ group, types }) => (
                        <details key={`${group}-${Boolean(query)}`} className="fnl-library-group" open={Boolean(query) || group === 'shadcn' || group === 'navigation' || group === 'layout'}>
                            <summary>{group === 'shadcn' ? 'shadcn/ui Components' : group === 'navigation' ? 'Navigation' : BLOCK_GROUP_LABEL[group]}<span>{types.length}</span></summary>
                            <ul className="flex flex-col gap-1">
                                {types.map((type) => (
                                    <PaletteItem key={type} type={type} onAdd={onAdd} />
                                ))}
                            </ul>
                        </details>
                    ))
                )}
            </div>
        </div>
    );
}
