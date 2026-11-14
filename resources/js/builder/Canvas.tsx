import { SectionInsertPicker } from './panels/SectionInsertPicker';
/**
 * The editing canvas.
 *
 * The canvas does not know how to paint a page — `@renderer` does, in the exact
 * same component the public page uses. What the canvas adds is the editing
 * layer: drop zones, drag handles and selection, injected through the renderer's
 * `slots` so the renderer never learns what dnd-kit is.
 *
 * The frame is sized to the active breakpoint rather than relying on the real
 * viewport, so a mobile preview is genuinely mobile-width inside a wide window.
 *
 * The `DndContext` deliberately lives one level up, in `BuilderApp`, because it
 * has to enclose the block palette and the design-block library as well as this
 * canvas. Everything here is a droppable or a draggable *within* that context.
 */

import { useMemo, useState, type ReactNode } from 'react';
import { useDraggable, useDroppable } from '@dnd-kit/core';
import { Columns2, GripVertical, Link2, Plus, Trash2 } from 'lucide-react';
import {
    FunnelProvider,
    PageRenderer,
    type FunnelRuntime,
    type BlockData,
    type ColumnData,
    type RowData,
    type NodeRef,
    type PageDocument,
    type SectionData,
    type Breakpoint,
} from '@renderer';
import { cn } from '@shared/lib/cn';
import { blockDraggableId, dropZoneId, type DragPayload } from './dnd';
import { isColumnsContainer } from './tree';

/** Frame widths per breakpoint. Matches the media-query tiers in the renderer. */
const FRAME_WIDTH: Record<Breakpoint, number | null> = {
    desktop: null,
    tablet: 834,
    mobile: 390,
};

export interface CanvasProps {
    document: PageDocument;
    breakpoint: Breakpoint;
    selected: NodeRef | null;
    previewing: boolean;
    onSelect: (ref: NodeRef | null) => void;
    onBlockPropsChange: (blockId: string, patch: Record<string, unknown>) => void;
    onAppendToColumn: (columnId: string) => void;
    onSetColumns: (rowId: string, count: number) => void;
    /** "Add block" inside a container — how a nested column gets its content. */
    onAppendToBlock: (blockId: string) => void;
    /** The on-canvas remove button. */
    onDeleteBlock: (blockId: string) => void;
    onAppendSection: () => void;
    onInsertSections?: (sections: SectionData[], beforeId: string | null) => void;
    /** Forms, products and steps, so blocks preview with real content. */
    runtime?: FunnelRuntime | null;
}

/* -------------------------------------------------------------------------- */
/* Drag and drop primitives                                                    */
/* -------------------------------------------------------------------------- */

function BlockDragHandle({ blockId }: { blockId: string }) {
    const payload: DragPayload = { source: 'canvas', blockId };
    const { attributes, listeners, setNodeRef } = useDraggable({
        id: blockDraggableId(blockId),
        data: payload,
    });

    return (
        <button
            ref={setNodeRef}
            type="button"
            className="fnl-drag-handle"
            aria-label="Move this block"
            {...attributes}
            {...listeners}
        >
            <GripVertical aria-hidden size={14} />
        </button>
    );
}

function DropBefore({ blockId }: { blockId: string }) {
    const { setNodeRef, isOver } = useDroppable({ id: dropZoneId({ kind: 'before', blockId }) });

    return <div ref={setNodeRef} className={cn('fnl-drop-line', isOver && 'is-over')} aria-hidden />;
}

/**
 * The seam above a section, where a whole ready-made section can land.
 *
 * It is taller than a block drop line because it has to be hittable while
 * dragging a section-sized thing, and it only shows itself when something is
 * over it — an always-visible gap between every section would make the canvas
 * stop looking like the page.
 */
function SectionSeam({ section, onInsert }: { section: SectionData; onInsert?: () => void }) {
    const drag = useDraggable({ id: `section-canvas:${section.id}`, data: { source: 'section', sectionId: section.id } });
    const { setNodeRef, isOver } = useDroppable({
        id: dropZoneId({ kind: 'section-before', sectionId: section.id }),
    });

    return <div ref={setNodeRef} className={cn('fnl-section-seam', isOver && 'is-over')}>{onInsert && <button type="button" className="fnl-insert-section" aria-label={`Insert section before ${section.name || 'this section'}`} onClick={(event) => { event.stopPropagation(); onInsert(); }}><Plus size={14} aria-hidden /><span>Insert section</span></button>}<button type="button" ref={drag.setNodeRef} {...drag.attributes} {...drag.listeners} className="fnl-section-move" aria-label={`Drag section: ${section.name || 'Section'}`}><GripVertical size={14} />{section.name || 'Section'}</button></div>;
}

function ColumnDropZone({ column, onAppend }: { column: ColumnData; onAppend: (columnId: string) => void }) {
    const { setNodeRef, isOver } = useDroppable({ id: dropZoneId({ kind: 'column', columnId: column.id }) });

    return (
        <div ref={setNodeRef} className={cn('fnl-column-drop', isOver && 'is-over')}>
            <button type="button" className="fnl-add-block" onClick={() => onAppend(column.id)}>
                <Plus aria-hidden size={14} />
                Add block
            </button>
        </div>
    );
}

/**
 * Hover tools for a block: remove, and for a nested column, a way to reach the
 * Columns block that owns it — its only visible surface is the gutter.
 */
function NodeTools({
    label,
    onDelete,
    onSelectParent,
}: {
    label: string;
    onDelete: () => void;
    onSelectParent?: () => void;
}) {
    return (
        <div className="fnl-node-tools">
            {onSelectParent && (
                <button
                    type="button"
                    aria-label="Select the Columns block"
                    title="Select the Columns block"
                    onClick={(event) => {
                        event.stopPropagation();
                        onSelectParent();
                    }}
                >
                    <Columns2 aria-hidden size={13} />
                </button>
            )}
            <button
                type="button"
                aria-label={`Remove ${label}`}
                title={`Remove ${label}`}
                onClick={(event) => {
                    event.stopPropagation();
                    onDelete();
                }}
            >
                <Trash2 aria-hidden size={13} />
            </button>
        </div>
    );
}

/** The end of a stacking container: a nested column's own "Add block". */
function ContainerDropZone({ block, onAppend }: { block: BlockData; onAppend: (blockId: string) => void }) {
    const { setNodeRef, isOver } = useDroppable({ id: dropZoneId({ kind: 'into', blockId: block.id }) });
    const empty = (block.children ?? []).length === 0;

    return (
        <div ref={setNodeRef} className={cn('fnl-column-drop', empty && 'is-empty', isOver && 'is-over')}>
            <button
                type="button"
                className="fnl-add-block"
                onClick={(event) => {
                    event.stopPropagation();
                    onAppend(block.id);
                }}
            >
                <Plus aria-hidden size={14} />
                Add block
            </button>
        </div>
    );
}

/** The tail of the page: appends a section on click, accepts a drop otherwise. */
function PageEnd({ onAppendSection }: { onAppendSection: () => void }) {
    const { setNodeRef, isOver } = useDroppable({ id: dropZoneId({ kind: 'page-end' }) });

    return (
        <div ref={setNodeRef} className={cn('fnl-page-end', isOver && 'is-over')}>
            <button type="button" className="fnl-add-section" onClick={onAppendSection}>
                <Plus aria-hidden size={16} />
                Add section
            </button>
        </div>
    );
}

/** Every nested column's id, mapped to the Columns block that owns it. */
function columnCells(document: PageDocument): Map<string, string> {
    const cells = new Map<string, string>();
    const walk = (blocks: BlockData[]): void => {
        for (const block of blocks) {
            if (isColumnsContainer(block)) {
                for (const child of block.children ?? []) {
                    cells.set(child.id, block.id);
                }
            }
            walk(block.children ?? []);
        }
    };
    for (const section of document.sections) {
        for (const row of section.rows) {
            for (const column of row.columns) {
                walk(column.blocks);
            }
        }
    }
    return cells;
}

/* -------------------------------------------------------------------------- */
/* Canvas                                                                      */
/* -------------------------------------------------------------------------- */

export function Canvas({
    document,
    breakpoint,
    selected,
    previewing,
    onSelect,
    onBlockPropsChange,
    onAppendToColumn,
    onSetColumns,
    onAppendToBlock,
    onDeleteBlock,
    onAppendSection,
    onInsertSections,
    runtime = null,
}: CanvasProps) {
    const [insertion, setInsertion] = useState<{ beforeId: string | null } | null>(null);
    // Nodes inside a global component instance are the component's, not the
    // page's: the server replaces them on save. They get no drag handles or
    // drop zones, and the section is covered by a click target that selects it,
    // so nothing inside can be edited by accident and silently reverted.
    const locked = useMemo(() => {
        const sections = new Map<string, string>();
        const nodes = new Set<string>();
        const walk = (blocks: SectionData['rows'][number]['columns'][number]['blocks']): void => {
            for (const block of blocks) {
                nodes.add(block.id);
                walk(block.children ?? []);
            }
        };
        for (const section of document.sections) {
            if (!section.globalComponentId) {
                continue;
            }
            sections.set(section.id, section.name ?? 'Global component');
            for (const row of section.rows) {
                nodes.add(row.id);
                for (const column of row.columns) {
                    nodes.add(column.id);
                    walk(column.blocks);
                }
            }
        }
        return { sections, nodes, cells: columnCells(document) };
    }, [document]);

    const slots = useMemo(
        () => ({
            wrapNode: (ref: NodeRef, element: ReactNode): ReactNode => {
                if (previewing) {
                    return element;
                }

                if (ref.kind === 'section' && locked.sections.has(ref.id)) {
                    return (
                        <div className="fnl-instance" key={ref.id}>
                            {element}
                            <button
                                type="button"
                                className={cn('fnl-instance-cover', selected?.kind === 'section' && selected.id === ref.id && 'is-selected')}
                                onClick={(event) => {
                                    event.stopPropagation();
                                    onSelect({ kind: 'section', id: ref.id });
                                }}
                                aria-label={`Global component: ${locked.sections.get(ref.id) ?? ''}. Select to edit or detach.`}
                            >
                                <span className="fnl-instance-badge">
                                    <Link2 aria-hidden size={12} /> Global · {locked.sections.get(ref.id)}
                                </span>
                            </button>
                        </div>
                    );
                }

                if (ref.kind !== 'block' || locked.nodes.has(ref.id)) {
                    return element;
                }

                // A nested column is positioned by its parent's layout preset,
                // not by dragging, so it gets no handle and no drop line.
                const parentId = locked.cells.get(ref.id);
                if (parentId !== undefined) {
                    return (
                        <div className="fnl-block-wrap fnl-cell-wrap" key={ref.id}>
                            <NodeTools
                                label="this column"
                                onDelete={() => onDeleteBlock(ref.id)}
                                onSelectParent={() => onSelect({ kind: 'block', id: parentId })}
                            />
                            {element}
                        </div>
                    );
                }

                return (
                    <div className="fnl-block-wrap" key={ref.id}>
                        <DropBefore blockId={ref.id} />
                        <BlockDragHandle blockId={ref.id} />
                        <NodeTools label="this block" onDelete={() => onDeleteBlock(ref.id)} />
                        {element}
                    </div>
                );
            },
            columnAppend: (column: ColumnData): ReactNode =>
                previewing || locked.nodes.has(column.id) ? null : <ColumnDropZone column={column} onAppend={onAppendToColumn} />,
            rowAppend: (row: RowData): ReactNode => previewing || locked.nodes.has(row.id) ? null : (
                <div style={{ gridColumn: '1 / -1' }}>
                    <button type="button" className="fnl-add-block" disabled={row.columns.length >= 6}
                        title={row.columns.length >= 6 ? 'A row supports up to six columns' : 'Add an empty column and distribute the row evenly'}
                        onClick={(event) => { event.stopPropagation(); onSetColumns(row.id, row.columns.length + 1); onSelect({ kind: 'row', id: row.id }); }}>
                        <Columns2 size={14} aria-hidden />{row.columns.length >= 6 ? 'Maximum 6 columns' : 'Add column'}
                    </button>
                </div>
            ),
            blockAppend: (block: BlockData): ReactNode =>
                previewing || locked.nodes.has(block.id) || isColumnsContainer(block) ? null : (
                    <ContainerDropZone block={block} onAppend={onAppendToBlock} />
                ),
            sectionBefore: (section: SectionData): ReactNode =>
                previewing ? null : <SectionSeam section={section} onInsert={onInsertSections ? () => setInsertion({ beforeId: section.id }) : undefined} />,
            pageAppend: (): ReactNode =>
                previewing ? null : <PageEnd onAppendSection={onInsertSections ? () => setInsertion({ beforeId: null }) : onAppendSection} />,
        }),
        [previewing, onAppendToColumn, onSetColumns, onAppendToBlock, onDeleteBlock, onAppendSection, onInsertSections, locked, selected, onSelect],
    );

    const width = FRAME_WIDTH[breakpoint];

    return (
        <div
            className="fnl-canvas-scroll"
            onClick={(event) => {
                // Clicking the surround, not a node, clears the selection.
                if (event.target === event.currentTarget) {
                    onSelect(null);
                }
            }}
        >
            {insertion && onInsertSections && !previewing && <SectionInsertPicker onClose={() => setInsertion(null)} onInsert={(sections) => { onInsertSections(sections, insertion.beforeId); setInsertion(null); }} />}
            <div
                className="fnl-canvas-frame"
                style={width === null ? undefined : { width, maxWidth: '100%' }}
                data-breakpoint={breakpoint}
            >
                <FunnelProvider value={runtime}>
                    <PageRenderer
                        document={document}
                        mode={previewing ? 'static' : 'editable'}
                        breakpoint={breakpoint}
                        selected={previewing ? null : selected}
                        onSelect={previewing ? undefined : onSelect}
                        onBlockPropsChange={previewing ? undefined : onBlockPropsChange}
                        slots={previewing ? undefined : slots}
                    />
                </FunnelProvider>
            </div>
        </div>
    );
}
