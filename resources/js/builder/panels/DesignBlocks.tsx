/**
 * The design-blocks library.
 *
 * A design block is a whole arrangement — a hero, a three-up feature row, a
 * pricing band — rather than a single element, so this sits beside the block
 * palette rather than inside it. Clicking one appends its sections to the page
 * with fresh ids, which means the same block can be used twice without the
 * document growing duplicate keys.
 *
 * A preview comes from one of two places. If the API sends rendered markup it
 * goes into a fully sandboxed iframe — no scripts, no same-origin access, no
 * chance to leak styles into the builder chrome — because it is server-authored
 * HTML this component did not build. Otherwise the fragment is rendered with the
 * shared renderer, which cannot drift from what insertion actually produces.
 */

import { useEffect, useMemo, useRef, useState } from 'react';
import { useDraggable } from '@dnd-kit/core';
import { LayoutTemplate, Plus } from 'lucide-react';
import { EmptyState } from '@shared/ui/EmptyState';
import { SearchInput } from '@shared/ui/SearchInput';
import { Skeleton } from '@shared/ui/Skeleton';
import { cn } from '@shared/lib/cn';
import { PageRenderer, blankDocument, type PageDocument } from '@renderer';
import { useBuilderStore } from '../store';
import { loadDesignBlocks, type DesignBlock } from '../api';
import { designDraggableId, type DragPayload } from '../dnd';

import { DESIGN_GROUPS, designGroup, designCollection } from '../designGroups';
import { useBuilderFunnel } from '../funnelContext';

type LoadState = 'loading' | 'ready' | 'unavailable';

export function DesignBlocks({ onInsert, onReplace, presentation = 'list' }: { presentation?: 'list' | 'grid'; onInsert: (block: DesignBlock) => void; onReplace?: (block: DesignBlock, sectionId: string) => void }) {
    const selectedSectionId = useBuilderStore(state => state.selected?.kind === 'section' ? state.selected.id : null);
    const [state, setState] = useState<LoadState>('loading');
    const [blocks, setBlocks] = useState<DesignBlock[]>([]);
    const [labels, setLabels] = useState<Record<string, string>>({});
    const [query, setQuery] = useState('');
    const funnel = useBuilderFunnel();
    const [chosenCollection, setCollection] = useState<string | null>(null);
    const templateBlock = funnel?.templateKey ? blocks.find(block => block.category === funnel.templateKey || block.key.startsWith(`${funnel.templateKey}-`)) : undefined;
    const templateCollection = templateBlock ? designCollection(templateBlock) : null;
    const collection = chosenCollection ?? templateCollection ?? 'all';

    useEffect(() => { setCollection(null); }, [funnel?.id, funnel?.templateKey]);

    useEffect(() => {
        let cancelled = false;

        loadDesignBlocks()
            .then((result) => {
                if (cancelled) {
                    return;
                }
                setBlocks(result.blocks);
                setLabels(result.categoryLabels);
                setState('ready');
            })
            .catch(() => {
                // A 404 means the library has not shipped yet. That is a state to
                // show, not an error to throw at someone mid-edit.
                if (!cancelled) {
                    setState('unavailable');
                }
            });

        return () => {
            cancelled = true;
        };
    }, []);

    const collections = useMemo(() => [...new Set(blocks.map(designCollection))].sort(), [blocks]);
    const categories = useMemo(() => {
        const needle = query.trim().toLowerCase();
        const matching = blocks.filter(block =>
            (collection === 'all' || designCollection(block) === collection) &&
            (!needle || `${block.name} ${block.category} ${labels[block.category] ?? ''} ${block.description ?? ''} ${designGroup(block)}`.toLowerCase().includes(needle)));
        return DESIGN_GROUPS.map(group => [group, matching.filter(block => designGroup(block) === group)] as const).filter(([, entries]) => entries.length);
    }, [blocks, labels, query, collection]);

    if (state === 'loading') {
        return (
            <div className="flex flex-col gap-2" aria-busy="true" aria-live="polite">
                <span className="sr-only">Loading design blocks</span>
                <Skeleton className="h-8 w-full" />
                <Skeleton className="h-24 w-full" />
                <Skeleton className="h-24 w-full" />
            </div>
        );
    }

    if (state === 'unavailable') {
        return (
            <EmptyState
                variant="bare"
                icon={<LayoutTemplate className="h-5 w-5" aria-hidden />}
                title="Design blocks are still loading"
                description="The design block library is not available yet. Keep building with the Blocks tab — nothing here is required."
            />
        );
    }

    if (blocks.length === 0) {
        return (
            <EmptyState
                variant="bare"
                icon={<LayoutTemplate className="h-5 w-5" aria-hidden />}
                title="No design blocks yet"
                description="Ready-made sections will appear here once the library is populated."
            />
        );
    }

    return (
        <div className="flex h-full min-h-0 flex-col gap-3">
            <label className="text-xs font-medium text-slate-600">Template collection
                <select aria-label="Template collection" value={collection} onChange={event => setCollection(event.target.value)} className="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                    <option value="all">All collections</option>
                    {collections.map(name => <option key={name} value={name}>{name}</option>)}
                </select>
            </label>
            {templateCollection && collection === templateCollection && <p className="text-xs text-slate-500">Showing sections that match this website’s {templateCollection} template.</p>}
            <SearchInput
                value={query}
                onValueChange={setQuery}
                placeholder="Find a design block"
                aria-label="Find a design block"
                size="sm"
            />

            <div className="min-h-0 flex-1 overflow-y-auto pr-1">
                {categories.length === 0 ? (
                    <p className="px-1 py-6 text-center text-[length:var(--text-sm)] text-[var(--color-text-muted)]">
                        No design block matches “{query}”.
                    </p>
                ) : (
                    categories.map(([category, entries]) => (
                        <details key={`${collection}-${category}-${Boolean(query)}`} className="fnl-library-group" open={Boolean(query) || collection !== 'all' || category === 'Navigation'}>
                            <summary>{category}<span>{entries.length}</span></summary>
                            <ul className={presentation === 'grid' ? 'grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3' : 'flex flex-col gap-2'}>
                                {entries.map((block) => (
                                    <li key={block.key}>
                                        <DesignBlockCard draggable={presentation !== 'grid'} block={block} onInsert={() => onInsert(block)} onReplace={selectedSectionId && onReplace && block.sections.length === 1 ? () => onReplace(block, selectedSectionId) : undefined} />
                                    </li>
                                ))}
                            </ul>
                        </details>
                    ))
                )}
            </div>
        </div>
    );
}

const PREVIEW_WIDTH = 1200;
const PREVIEW_HEIGHT = 560;
/** 240px of panel across 1200px of page — the panel column is fixed-width. */
const PREVIEW_SCALE = 0.2;

function DesignBlockCard({ block, onInsert, onReplace, draggable = true }: { draggable?: boolean; block: DesignBlock; onInsert: () => void; onReplace?: () => void }) {
    const previewRef = useRef<HTMLDivElement | null>(null);
    const [previewVisible, setPreviewVisible] = useState(false);
    useEffect(() => {
        const element = previewRef.current;
        if (!element) return;
        if (typeof IntersectionObserver === 'undefined') {
            setPreviewVisible(true);
            return;
        }
        const observer = new IntersectionObserver(entries => {
            if (entries.some(entry => entry.isIntersecting)) {
                setPreviewVisible(true);
                observer.disconnect();
            }
        }, { rootMargin: '160px' });
        observer.observe(element);
        return () => observer.disconnect();
    }, []);
    const sections = block.sections.length;
    const summary = block.description ?? `${sections} section${sections === 1 ? '' : 's'}`;

    // The whole fragment travels in the drag payload rather than just the key,
    // so the drop is resolved without a second round trip. Ids are regenerated
    // at insert time, so carrying one copy around is safe.
    const payload: DragPayload = { source: 'design', name: block.name, sections: block.sections };

    const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
        id: draggable ? designDraggableId(block.key) : `section-picker:${block.key}`,
        disabled: !draggable,
        data: payload,
    });

    // When the API sends no rendered preview, render the fragment for real with
    // the shared renderer. It is the same code the canvas and the public page
    // use, so the thumbnail cannot drift from what actually gets inserted.
    const fallbackDocument = useMemo<PageDocument | null>(
        () => (block.previewHtml ? null : { ...blankDocument(), settings: block.previewSettings ?? blankDocument().settings, sections: block.sections }),
        [block],
    );

    const previewStyle = {
        width: PREVIEW_WIDTH,
        height: PREVIEW_HEIGHT,
        transform: `scale(${PREVIEW_SCALE})`,
    };

    // The card is a container rather than one giant <button>, because a preview
    // can contain buttons and links of its own and nesting those inside a button
    // is neither valid HTML nor navigable. The footer button stretches over the
    // whole card instead, so the click target is the card and the focus target
    // is one control.
    return (
        <div
            className={cn(
                'group relative flex flex-col overflow-hidden rounded-md border',
                'border-[var(--color-border)] bg-[var(--color-surface)]',
                'hover:border-[var(--color-primary)]',
                'focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-[var(--color-ring)]',
                isDragging && 'opacity-50',
            )}
        >
            {/*
              The thumbnail is the drag handle. It carries the drag listeners
              rather than the card, so the footer button below stays a plain
              button — clickable and reachable by keyboard for anyone who is
              not dragging with a pointer at all.
            */}
            {/*
              A click on the thumbnail inserts too: it is most of the card, and in the
              "Insert a section" dialog dragging is off. Drags only start after 5px of
              movement, so a plain click never doubles as a drag.
            */}
            <div
                ref={(element) => { previewRef.current = element; setNodeRef(element); }}
                aria-hidden
                {...listeners}
                {...attributes}
                onClick={onInsert}
                title={draggable ? `Click or drag ${block.name} onto the page` : `Add ${block.name} to the page`}
                className={cn(
                    'h-24 w-full overflow-hidden bg-[var(--color-surface-sunken)]',
                    draggable ? 'cursor-grab active:cursor-grabbing' : 'cursor-pointer',
                )}
            >
                <div className="pointer-events-none h-full w-full">
                    {previewVisible && block.previewHtml ? (
                        <iframe
                            // No scripts, no same-origin, no forms: server-rendered
                            // markup this component did not build gets no capabilities.
                            sandbox=""
                            loading="lazy"
                            tabIndex={-1}
                            title={`${block.name} preview`}
                            srcDoc={block.previewHtml}
                            className="origin-top-left border-0"
                            style={previewStyle}
                        />
                    ) : previewVisible && fallbackDocument ? (
                        <div className="origin-top-left" style={previewStyle}>
                            <PageRenderer document={fallbackDocument} mode="static" breakpoint="desktop" />
                        </div>
                    ) : (
                        <div className="flex h-full items-center justify-center text-[var(--color-text-subtle)]">
                            <LayoutTemplate className="h-6 w-6" aria-hidden />
                        </div>
                    )}
                </div>
            </div>

            {/*
              The click target stretches over the footer only, not the whole
              card: an overlay across the thumbnail would sit on top of the
              drag handle and swallow every pointer event before dnd-kit saw it.
            */}
            <div className="relative flex items-start gap-2 px-2.5 py-2">
                <span className="min-w-0 flex-1">
                    <button
                        type="button"
                        onClick={onInsert}
                        aria-label={`Add ${block.name} to the page — ${summary}`}
                        className={cn(
                            'block max-w-full truncate text-left',
                            'text-[length:var(--text-sm)] font-medium text-[var(--color-text)]',
                            'after:absolute after:inset-0 after:content-[""]',
                            'focus-visible:outline-none',
                        )}
                    >
                        {block.name}
                    </button>
                    <span className="block truncate text-[length:var(--text-2xs)] text-[var(--color-text-muted)]">
                        {summary}
                    </span>
                </span>
                <Plus
                    aria-hidden
                    className="mt-0.5 h-3.5 w-3.5 shrink-0 text-[var(--color-text-subtle)] group-hover:text-[var(--color-text-brand)]"
                />
            </div>
            {onReplace && <button type="button" onClick={onReplace} aria-label={`Replace selected section with ${block.name}`} className="relative z-10 w-full border-t border-[var(--color-border)] px-3 py-2 text-left text-xs font-medium text-[var(--color-text-brand)]">Replace selected section</button>}
        </div>
    );
}
