/**
 * The layer/outline tree.
 *
 * A flat list with an indent per depth rather than a nested one, so arrow-key
 * navigation is a single roving tabindex and the DOM stays shallow on long
 * pages. Selecting a layer selects the same node on the canvas.
 */

import { useMemo, useRef, useState } from 'react';
import { ChevronDown, ChevronRight, EyeOff, Search } from 'lucide-react';
import { Input } from '@shared/ui/Input';
import { cn } from '@shared/lib/cn';
import { BREAKPOINT_LABEL, type Breakpoint, type NodeRef } from '@renderer';
import type { OutlineNode } from '../tree';
import { visibleLayers } from '../layers';

export function LayerTree({
    nodes,
    selected,
    onSelect,
    breakpoint = 'desktop',
}: {
    nodes: OutlineNode[];
    selected: NodeRef | null;
    onSelect: (ref: NodeRef) => void;
    breakpoint?: Breakpoint;
}) {
    const listRef = useRef<HTMLUListElement>(null);
    const [query, setQuery] = useState('');
    const [collapsed, setCollapsed] = useState<Set<string>>(() => new Set());
    const rows = useMemo(() => visibleLayers(nodes, query, collapsed), [nodes, query, collapsed]);
    const branches = useMemo(() => new Set(nodes.filter((node, index) => (nodes[index + 1]?.depth ?? -1) > node.depth).map(node => node.ref.id)), [nodes]);
    const selectedIndex = rows.findIndex(node => selected?.kind === node.ref.kind && selected.id === node.ref.id);
    const toggle = (id: string): void => setCollapsed(current => {
        const next = new Set(current);
        if (next.has(id)) next.delete(id);
        else next.add(id);
        return next;
    });

    const focus = (index: number) => {
        const next = Math.max(0, Math.min(rows.length - 1, index));
        const target = rows[next];
        if (!target) {
            return;
        }
        onSelect(target.ref);
        listRef.current
            ?.querySelectorAll<HTMLButtonElement>('[data-layer]')
            [next]?.focus();
    };

    if (nodes.length === 0) {
        return (
            <p className="px-1 py-6 text-center text-[length:var(--text-sm)] text-[var(--color-text-muted)]">
                This page has no sections yet.
            </p>
        );
    }

    return (
        <div className="flex flex-col gap-3">
        <Input size="sm" type="search" label="Search layers" leadingIcon={<Search size={14} />} placeholder="Name or layer type…" value={query} onChange={event => setQuery(event.currentTarget.value)} onKeyDown={event => {
            if (event.key === 'Escape' && query) { event.stopPropagation(); setQuery(''); }
        }} />
        <div className="flex items-center justify-between gap-2 text-[length:var(--text-xs)] text-[var(--color-text-muted)]">
            <span role="status">{rows.length} of {nodes.length} layers</span>
            <button type="button" className="hover:text-[var(--color-text)]" disabled={Boolean(query.trim())} onClick={() => setCollapsed(collapsed.size ? new Set() : new Set(branches))}>
                {collapsed.size ? 'Expand all' : 'Collapse all'}
            </button>
        </div>
        {rows.length === 0 && <p className="py-4 text-center text-[length:var(--text-sm)] text-[var(--color-text-muted)]">No matching layers. Try another name or type.</p>}
        <ul ref={listRef} role="tree" aria-label="Page outline" className="flex flex-col gap-0.5">
            {rows.map((node, index) => {
                const isSelected = selected?.kind === node.ref.kind && selected.id === node.ref.id;
                const hasChildren = branches.has(node.ref.id);
                const expanded = Boolean(query.trim()) || !collapsed.has(node.ref.id);
                return (
                    <li key={`${node.ref.kind}:${node.ref.id}`} role="none">
                        <div className="flex items-center" style={{ paddingLeft: `${node.depth * 12}px` }}>
                        {hasChildren ? <button type="button" tabIndex={-1} className="shrink-0 p-1 text-[var(--color-text-muted)]" disabled={Boolean(query.trim())} aria-label={`${expanded ? 'Collapse' : 'Expand'} ${node.label}`} onClick={() => toggle(node.ref.id)}>
                            {expanded ? <ChevronDown aria-hidden size={14} /> : <ChevronRight aria-hidden size={14} />}
                        </button> : <span className="w-[22px] shrink-0" />}
                        <button
                            type="button"
                            data-layer
                            role="treeitem"
                            aria-level={node.depth + 1}
                            aria-selected={isSelected}
                            aria-expanded={hasChildren ? expanded : undefined}
                            tabIndex={index === (selectedIndex < 0 ? 0 : selectedIndex) ? 0 : -1}
                            onClick={() => onSelect(node.ref)}
                            onKeyDown={(event) => {
                                if (event.key === 'ArrowDown') {
                                    event.preventDefault();
                                    focus(index + 1);
                                }
                                if (event.key === 'ArrowUp') {
                                    event.preventDefault();
                                    focus(index - 1);
                                }
                                if (event.key === 'Home' || event.key === 'End') {
                                    event.preventDefault();
                                    focus(event.key === 'Home' ? 0 : rows.length - 1);
                                }
                                if (event.key === 'ArrowRight') {
                                    event.preventDefault();
                                    if (hasChildren && !expanded) toggle(node.ref.id);
                                    else if ((rows[index + 1]?.depth ?? -1) > node.depth) focus(index + 1);
                                }
                                if (event.key === 'ArrowLeft') {
                                    event.preventDefault();
                                    if (hasChildren && expanded && !query.trim()) toggle(node.ref.id);
                                    else {
                                        const parentIndex = rows.slice(0, index).findLastIndex(parent => parent.depth < node.depth);
                                        if (parentIndex >= 0) focus(parentIndex);
                                    }
                                }
                            }}
                            className={cn(
                                'flex min-w-0 flex-1 items-center gap-2 rounded-md py-1.5 px-2 text-left',
                                'text-[length:var(--text-sm)] capitalize',
                                'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)]',
                                isSelected
                                    ? 'bg-[var(--color-primary-soft)] font-medium text-[var(--color-text-brand)]'
                                    : 'text-[var(--color-text-muted)] hover:bg-[var(--color-surface-hover)] hover:text-[var(--color-text)]',
                            )}
                        >
                            <span className="flex-1 truncate">{node.label}</span>
                            {node.hidden ? (
                                <EyeOff className="h-3.5 w-3.5 shrink-0" aria-label={`Hidden on ${BREAKPOINT_LABEL[breakpoint]}`} />
                            ) : null}
                        </button>
                        </div>
                    </li>
                );
            })}
        </ul>
        </div>
    );
}
