/**
 * The builder's drag-and-drop vocabulary.
 *
 * Three things can be dragged — a block type from the palette, a ready-made
 * design block, and a block that is already on the canvas — and they can be
 * dropped onto four kinds of zone. Rather than spread that matrix across the
 * components, the encoding and the resolution live here as pure functions, so
 * the drop rules can be reasoned about (and tested) without a DOM.
 *
 * One thing worth knowing about the arrangement: the `DndContext` has to sit
 * *above* both the left panel and the canvas. dnd-kit only pairs a draggable
 * with a droppable inside the same context, so a provider mounted inside the
 * canvas can move a block around the page but can never accept anything
 * dragged in from a panel — the drag simply ends nowhere.
 */

import type { NodeRef, PageDocument, SectionData } from '@renderer';
import { sectionContaining, sectionIndexOf, type DropTarget, type PaletteChoice } from './tree';
import { FUNNEL_WIDGETS, isFunnelWidgetId, type FunnelWidgetId } from './funnelWidgets';

/* -------------------------------------------------------------------------- */
/* What is being dragged                                                       */
/* -------------------------------------------------------------------------- */

export type DragPayload =
    /** A new block, from the palette. */
    | { source: 'palette'; blockType: PaletteChoice }
    | { source: 'widget'; widgetId: FunnelWidgetId }
    /** A ready-made arrangement of whole sections, from the design library. */
    | { source: 'design'; name: string; sections: SectionData[] }
    /** A block that already exists on the page, being moved. */
    | { source: 'canvas'; blockId: string }
    | { source: 'section'; sectionId: string };

export function isDragPayload(value: unknown): value is DragPayload {
    if (typeof value !== 'object' || value === null) {
        return false;
    }

    const source = (value as { source?: unknown }).source;

    if (source === 'widget') return isFunnelWidgetId((value as { widgetId?: unknown }).widgetId);

    return source === 'palette' || source === 'design' || source === 'canvas' || source === 'section';
}

/** A short label for the drag overlay. */
export function dragLabel(payload: DragPayload): string {
    if (payload.source === 'widget') return FUNNEL_WIDGETS.find(widget => widget.id === payload.widgetId)?.label ?? 'Funnel widget';
    if (payload.source === 'design') {
        return payload.name;
    }
    if (payload.source === 'palette') {
        return payload.blockType.replace(/_/g, ' ');
    }
    return payload.source === 'section' ? 'Moving section' : 'Moving block';
}

/* -------------------------------------------------------------------------- */
/* Where it can land                                                           */
/* -------------------------------------------------------------------------- */

export type DropZone =
    /** Into a column, at the end. */
    | { kind: 'column'; columnId: string }
    /** Immediately before a block. */
    | { kind: 'before'; blockId: string }
    /** Into a container block, at the end. */
    | { kind: 'into'; blockId: string }
    /** Between sections, immediately before this one. */
    | { kind: 'section-before'; sectionId: string }
    /** After the last section. */
    | { kind: 'page-end' };

/**
 * dnd-kit ids are plain strings, so the zone is encoded into one. Section ids
 * are ULIDs and never contain a colon, which is what makes a single split safe.
 */
export function dropZoneId(zone: DropZone): string {
    switch (zone.kind) {
        case 'column':
            return `column:${zone.columnId}`;
        case 'before':
            return `before:${zone.blockId}`;
        case 'into':
            return `into:${zone.blockId}`;
        case 'section-before':
            return `section-before:${zone.sectionId}`;
        case 'page-end':
            return 'page-end';
    }
}

export function decodeDropZone(id: string): DropZone | null {
    if (id === 'page-end') {
        return { kind: 'page-end' };
    }

    const separator = id.indexOf(':');
    if (separator === -1) {
        return null;
    }

    const kind = id.slice(0, separator);
    const value = id.slice(separator + 1);

    if (value === '') {
        return null;
    }

    if (kind === 'column') {
        return { kind: 'column', columnId: value };
    }
    if (kind === 'before') {
        return { kind: 'before', blockId: value };
    }
    if (kind === 'into') {
        return { kind: 'into', blockId: value };
    }
    if (kind === 'section-before' || kind === 'section-list-before') {
        return { kind: 'section-before', sectionId: value };
    }

    return null;
}

/** Draggable ids, kept in one place so the two ends cannot drift apart. */
export function paletteDraggableId(type: PaletteChoice): string {
    return `palette:${type}`;
}

export function designDraggableId(key: string): string {
    return `design:${key}`;
}

export function blockDraggableId(blockId: string): string {
    return `block:${blockId}`;
}

/* -------------------------------------------------------------------------- */
/* Resolution                                                                  */
/* -------------------------------------------------------------------------- */

/**
 * Where a single block should go for this zone.
 *
 * A block dropped on a *section* zone has no column of its own to land in, so
 * it goes into the first column of the section it was aimed at — which is what
 * someone dragging a heading onto the seam between two sections means.
 */
export function blockTargetFor(document: PageDocument, zone: DropZone): DropTarget | null {
    if (zone.kind === 'column') {
        return { kind: 'column', columnId: zone.columnId };
    }

    if (zone.kind === 'before') {
        return { kind: 'before', blockId: zone.blockId };
    }

    if (zone.kind === 'into') {
        return { kind: 'into', blockId: zone.blockId };
    }

    const section =
        zone.kind === 'page-end'
            ? document.sections[document.sections.length - 1]
            : document.sections.find((candidate) => candidate.id === zone.sectionId);

    const columns = section?.rows[0]?.columns ?? [];
    const column = zone.kind === 'page-end' ? columns[columns.length - 1] : columns[0];

    return column ? { kind: 'column', columnId: column.id } : null;
}

/**
 * The index a ready-made fragment should be inserted at.
 *
 * Sections are the unit here, so a zone pointing at a column or a block is
 * resolved up to whichever section encloses it: dragging a pricing band onto
 * the middle of the hero means "put it before the hero", not "put it inside".
 */
export function sectionIndexFor(document: PageDocument, zone: DropZone): number {
    if (zone.kind === 'page-end') {
        return document.sections.length;
    }

    if (zone.kind === 'section-before') {
        return sectionIndexOf(document, zone.sectionId) ?? document.sections.length;
    }

    const ref: NodeRef =
        zone.kind === 'column'
            ? { kind: 'column', id: zone.columnId }
            : { kind: 'block', id: zone.blockId };

    const section = sectionContaining(document, ref);

    if (section === null) {
        return document.sections.length;
    }

    return sectionIndexOf(document, section.id) ?? document.sections.length;
}
