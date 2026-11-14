/**
 * Pure, immutable operations on the page document tree.
 *
 * Nothing here touches React or the store: every function takes a document and
 * returns a new one, which is what makes undo/redo a matter of keeping old
 * references around rather than replaying commands.
 */

import {
    COLUMN_GRID,
    containerColumnSpans,
    createBlock,
    createColumn,
    isContainerBlock,
    newId,
    spanAt,
    type BlockData,
    type BlockType,
    type Breakpoint,
    type ColumnData,
    type NodeKind,
    type NodeRef,
    type PageDocument,
    type RowData,
    type SectionData,
    type StyleData,
    type VisibilityData,
} from '@renderer';

/* -------------------------------------------------------------------------- */
/* Lookup                                                                      */
/* -------------------------------------------------------------------------- */

export interface BlockLocation {
    block: BlockData;
    /** The list the block currently lives in. */
    siblings: BlockData[];
    index: number;
    /** Owning column, or the container block when nested. */
    parent: NodeRef;
}

function locateInBlocks(
    blocks: BlockData[],
    id: string,
    parent: NodeRef,
): BlockLocation | null {
    for (let index = 0; index < blocks.length; index += 1) {
        const block = blocks[index];
        if (!block) {
            continue;
        }
        if (block.id === id) {
            return { block, siblings: blocks, index, parent };
        }
        const nested = locateInBlocks(block.children ?? [], id, { kind: 'block', id: block.id });
        if (nested) {
            return nested;
        }
    }
    return null;
}

export function locateBlock(document: PageDocument, id: string): BlockLocation | null {
    for (const section of document.sections) {
        for (const row of section.rows) {
            for (const column of row.columns) {
                const found = locateInBlocks(column.blocks, id, { kind: 'column', id: column.id });
                if (found) {
                    return found;
                }
            }
        }
    }
    return null;
}

export function findSection(document: PageDocument, id: string): SectionData | null {
    return document.sections.find((section) => section.id === id) ?? null;
}

export function findRow(document: PageDocument, id: string): RowData | null {
    for (const section of document.sections) {
        const row = section.rows.find((candidate) => candidate.id === id);
        if (row) {
            return row;
        }
    }
    return null;
}

export function findColumn(document: PageDocument, id: string): ColumnData | null {
    for (const section of document.sections) {
        for (const row of section.rows) {
            const column = row.columns.find((candidate) => candidate.id === id);
            if (column) {
                return column;
            }
        }
    }
    return null;
}

export interface StyledNode {
    kind: NodeKind;
    id: string;
    label: string;
    style: StyleData;
    visibility: VisibilityData;
}

const DEFAULT_VISIBILITY: VisibilityData = { desktop: true, tablet: true, mobile: true };

/** The one lookup the properties panel needs, whatever kind of node is selected. */
export function findStyledNode(document: PageDocument, ref: NodeRef | null): StyledNode | null {
    if (!ref) {
        return null;
    }

    if (ref.kind === 'section') {
        const section = findSection(document, ref.id);
        return section
            ? {
                  kind: 'section',
                  id: section.id,
                  label: section.name ?? 'Section',
                  style: section.style ?? {},
                  visibility: section.visibility ?? DEFAULT_VISIBILITY,
              }
            : null;
    }
    if (ref.kind === 'row') {
        const row = findRow(document, ref.id);
        return row
            ? {
                  kind: 'row',
                  id: row.id,
                  label: row.name ?? 'Row',
                  style: row.style ?? {},
                  visibility: row.visibility ?? DEFAULT_VISIBILITY,
              }
            : null;
    }
    if (ref.kind === 'column') {
        const column = findColumn(document, ref.id);
        return column
            ? {
                  kind: 'column',
                  id: column.id,
                  label: column.name ?? 'Column',
                  style: column.style ?? {},
                  visibility: column.visibility ?? DEFAULT_VISIBILITY,
              }
            : null;
    }

    const located = locateBlock(document, ref.id);
    return located
        ? {
              kind: 'block',
              id: located.block.id,
              label: located.block.name ?? located.block.type,
              style: located.block.style ?? {},
              visibility: located.block.visibility ?? DEFAULT_VISIBILITY,
          }
        : null;
}

/* -------------------------------------------------------------------------- */
/* Structural edits                                                            */
/* -------------------------------------------------------------------------- */

function mapBlocks(blocks: BlockData[], fn: (block: BlockData) => BlockData | null): BlockData[] {
    const out: BlockData[] = [];
    for (const block of blocks) {
        const mapped = fn(block);
        if (mapped === null) {
            continue;
        }
        out.push(
            mapped.children && mapped.children.length > 0
                ? { ...mapped, children: mapBlocks(mapped.children, fn) }
                : mapped,
        );
    }
    return out;
}

function mapColumns(document: PageDocument, fn: (column: ColumnData) => ColumnData): PageDocument {
    return {
        ...document,
        sections: document.sections.map((section) => ({
            ...section,
            rows: section.rows.map((row) => ({ ...row, columns: row.columns.map(fn) })),
        })),
    };
}

/** Apply a transform to every block in the document, top-down. */
export function mapAllBlocks(
    document: PageDocument,
    fn: (block: BlockData) => BlockData | null,
): PageDocument {
    return mapColumns(document, (column) => ({ ...column, blocks: mapBlocks(column.blocks, fn) }));
}

export function updateBlock(
    document: PageDocument,
    id: string,
    patch: Partial<BlockData>,
): PageDocument {
    return mapAllBlocks(document, (block) => (block.id === id ? withPanelChildren({ ...block, ...patch }) : block));
}

/** Keep content panels editable when labels are added in either editor surface. */
function withPanelChildren(block: BlockData): BlockData {
    const items = block.type === 'tabs' ? block.props.tabs : block.type === 'accordion' ? block.props.items : null;
    if (!Array.isArray(items) || (block.children?.length ?? 0) >= items.length) return block;
    const children = [...(block.children ?? [])];
    while (children.length < items.length) {
        const panel = createBlock('container');
        panel.children = [createBlock('paragraph')];
        children.push(panel);
    }
    return { ...block, children };
}

export function updateBlockProps(
    document: PageDocument,
    id: string,
    patch: Record<string, unknown>,
): PageDocument {
    return mapAllBlocks(document, (block) =>
        block.id === id ? withPanelChildren({ ...block, props: { ...block.props, ...patch } }) : block,
    );
}

export function removeBlock(document: PageDocument, id: string): PageDocument {
    return mapAllBlocks(document, (block) => (block.id === id ? null : block));
}

/** Where a dragged block should land. */
export type DropTarget =
    | { kind: 'column'; columnId: string; index?: number }
    | { kind: 'into'; blockId: string; index?: number }
    | { kind: 'before'; blockId: string };

function insertAt(blocks: BlockData[], block: BlockData, index?: number): BlockData[] {
    const next = [...blocks];
    next.splice(index ?? next.length, 0, block);
    return next;
}

export function insertBlock(document: PageDocument, block: BlockData, target: DropTarget): PageDocument {
    if (target.kind === 'column') {
        return mapColumns(document, (column) =>
            column.id === target.columnId
                ? { ...column, blocks: insertAt(column.blocks, block, target.index) }
                : column,
        );
    }

    if (target.kind === 'into') {
        return mapAllBlocks(document, (candidate) =>
            candidate.id === target.blockId && isContainerBlock(candidate.type)
                ? { ...candidate, children: insertAt(candidate.children ?? [], block, target.index) }
                : candidate,
        );
    }

    const located = locateBlock(document, target.blockId);
    if (!located) {
        return document;
    }

    if (located.parent.kind === 'column') {
        return mapColumns(document, (column) =>
            column.id === located.parent.id
                ? { ...column, blocks: insertAt(column.blocks, block, located.index) }
                : column,
        );
    }

    return mapAllBlocks(document, (candidate) =>
        candidate.id === located.parent.id
            ? { ...candidate, children: insertAt(candidate.children ?? [], block, located.index) }
            : candidate,
    );
}

/** Remove then re-insert — the only safe order when both ends are in the tree. */
export function moveBlock(document: PageDocument, blockId: string, target: DropTarget): PageDocument {
    const located = locateBlock(document, blockId);
    if (!located) {
        return document;
    }
    // Refusing to drop a container into itself keeps the tree acyclic.
    if (target.kind === 'into' && containsBlock(located.block, target.blockId)) {
        return document;
    }
    if (target.kind === 'before' && containsBlock(located.block, target.blockId)) {
        return document;
    }
    return insertBlock(removeBlock(document, blockId), located.block, target);
}

export function containsBlock(block: BlockData, id: string): boolean {
    if (block.id === id) {
        return true;
    }
    return (block.children ?? []).some((child) => containsBlock(child, id));
}

/** A deep copy with fresh ids all the way down. */
export function cloneBlock(block: BlockData): BlockData {
    return {
        ...block,
        id: newId(),
        children: (block.children ?? []).map(cloneBlock),
    };
}

export function duplicateBlock(document: PageDocument, id: string): PageDocument {
    const located = locateBlock(document, id);
    if (!located) {
        return document;
    }
    const copy = cloneBlock(located.block);

    if (located.parent.kind === 'column') {
        return mapColumns(document, (column) =>
            column.id === located.parent.id
                ? { ...column, blocks: insertAt(column.blocks, copy, located.index + 1) }
                : column,
        );
    }

    return mapAllBlocks(document, (candidate) =>
        candidate.id === located.parent.id
            ? { ...candidate, children: insertAt(candidate.children ?? [], copy, located.index + 1) }
            : candidate,
    );
}

/* -------------------------------------------------------------------------- */
/* Nested columns — a container laid out as columns of containers              */
/* -------------------------------------------------------------------------- */

export type ContainerLayout = 'stack' | 'row' | 'grid' | 'columns';

/** What the palette and the block picker can add: any block type, or a ready-split Columns container. */
export type PaletteChoice = BlockType | 'columns';

export function createPaletteBlock(choice: PaletteChoice): BlockData {
    return choice === 'columns' ? createColumnsBlock() : createBlock(choice);
}

function clampSpans(spans: readonly number[]): number[] {
    return spans.slice(0, 6).map((span) => Math.max(1, Math.min(COLUMN_GRID, Math.round(span))));
}

/** Every cell of a columns container is a stacking container, so it can hold anything — more columns included. */
function asCell(block: BlockData): BlockData {
    return block.type === 'container' ? block : { ...createBlock('container'), children: [block] };
}

/** A container pre-split into columns — what "Columns" in the palette drops. */
export function createColumnsBlock(spans: readonly number[] = [6, 6]): BlockData {
    const block = createBlock('container');
    const clamped = clampSpans(spans);
    return {
        ...block,
        props: { ...block.props, layout: 'columns', gap: '24px', columns: clamped, stackBelow: 'mobile' },
        children: clamped.map(() => createBlock('container')),
    };
}

export function isColumnsContainer(block: BlockData): boolean {
    return block.type === 'container' && block.props['layout'] === 'columns';
}

/**
 * Re-splits a container into columns, keeping its content. Mirrors
 * `applyColumnLayout`: blocks from a removed column fold into the last one, and
 * a container that was not columns yet moves its content into the first.
 */
export function setContainerColumns(
    document: PageDocument,
    blockId: string,
    spans: readonly number[],
): PageDocument {
    const clamped = clampSpans(spans);
    if (clamped.length === 0) {
        return document;
    }

    return mapAllBlocks(document, (block) => {
        if (block.id !== blockId || block.type !== 'container') {
            return block;
        }

        const existing = block.children ?? [];
        const cells = isColumnsContainer(block)
            ? existing.map(asCell)
            : existing.length > 0
              ? [{ ...createBlock('container'), children: existing }]
              : [];

        const children = clamped.map((_, index) => cells[index] ?? createBlock('container'));
        const orphans = cells.slice(clamped.length).flatMap((cell) => cell.children ?? []);
        const last = children[children.length - 1];
        if (last && orphans.length > 0) {
            children[children.length - 1] = { ...last, children: [...(last.children ?? []), ...orphans] };
        }

        return { ...block, props: { ...block.props, layout: 'columns', columns: clamped }, children };
    });
}

/**
 * Delete a block and keep a columns container consistent: removing one of its
 * columns drops that column's span too, and removing the last column removes
 * the now-empty Columns block itself.
 */
export function deleteBlockTidy(document: PageDocument, id: string): PageDocument {
    const located = locateBlock(document, id);
    if (!located) {
        return document;
    }
    if (located.parent.kind !== 'block') {
        return removeBlock(document, id);
    }

    const parent = locateBlock(document, located.parent.id)?.block;
    if (!parent || !isColumnsContainer(parent)) {
        return removeBlock(document, id);
    }

    const cells = parent.children ?? [];
    if (cells.length <= 1) {
        return removeBlock(document, parent.id);
    }

    const spans = containerColumnSpans(parent.props).slice(0, cells.length);
    spans.splice(located.index, 1);

    return updateBlock(removeBlock(document, id), parent.id, {
        props: { ...parent.props, columns: spans.length > 0 ? spans : [COLUMN_GRID] },
        children: cells.filter((cell) => cell.id !== id),
    });
}

/** Switch a container's layout; leaving columns flattens the cells back into one list. */
export function setContainerLayout(
    document: PageDocument,
    blockId: string,
    layout: ContainerLayout,
): PageDocument {
    const located = locateBlock(document, blockId);
    if (!located || located.block.type !== 'container') {
        return document;
    }

    if (layout === 'columns') {
        return setContainerColumns(document, blockId, containerColumnSpans(located.block.props));
    }

    const block = located.block;
    const children = isColumnsContainer(block)
        ? (block.children ?? []).flatMap((cell) => (cell.type === 'container' ? (cell.children ?? []) : [cell]))
        : block.children;
    const { columns: _columns, stackBelow: _stackBelow, ...props } = block.props;

    return updateBlock(document, blockId, { props: { ...props, layout }, children: children ?? [] });
}

/* -------------------------------------------------------------------------- */
/* Style and visibility, for any node kind                                     */
/* -------------------------------------------------------------------------- */

export function updateNodeStyle(
    document: PageDocument,
    ref: NodeRef,
    style: StyleData,
): PageDocument {
    switch (ref.kind) {
        case 'section':
            return {
                ...document,
                sections: document.sections.map((section) =>
                    section.id === ref.id ? { ...section, style } : section,
                ),
            };
        case 'row':
            return {
                ...document,
                sections: document.sections.map((section) => ({
                    ...section,
                    rows: section.rows.map((row) => (row.id === ref.id ? { ...row, style } : row)),
                })),
            };
        case 'column':
            return mapColumns(document, (column) => (column.id === ref.id ? { ...column, style } : column));
        case 'block':
            return updateBlock(document, ref.id, { style });
    }
}

export function updateNodeVisibility(
    document: PageDocument,
    ref: NodeRef,
    visibility: VisibilityData,
): PageDocument {
    switch (ref.kind) {
        case 'section':
            return {
                ...document,
                sections: document.sections.map((section) =>
                    section.id === ref.id ? { ...section, visibility } : section,
                ),
            };
        case 'row':
            return {
                ...document,
                sections: document.sections.map((section) => ({
                    ...section,
                    rows: section.rows.map((row) => (row.id === ref.id ? { ...row, visibility } : row)),
                })),
            };
        case 'column':
            return mapColumns(document, (column) =>
                column.id === ref.id ? { ...column, visibility } : column,
            );
        case 'block':
            return updateBlock(document, ref.id, { visibility });
    }
}

/* -------------------------------------------------------------------------- */
/* Sections, rows and columns                                                  */
/* -------------------------------------------------------------------------- */

export function addSection(document: PageDocument, section: SectionData): PageDocument {
    return { ...document, sections: [...document.sections, section] };
}

export function removeSection(document: PageDocument, id: string): PageDocument {
    return { ...document, sections: document.sections.filter((section) => section.id !== id) };
}

export function addRow(document: PageDocument, sectionId: string, row: RowData): PageDocument {
    return {
        ...document,
        sections: document.sections.map((section) =>
            section.id === sectionId ? { ...section, rows: [...section.rows, row] } : section,
        ),
    };
}

export function removeRow(document: PageDocument, id: string): PageDocument {
    return {
        ...document,
        sections: document.sections.map((section) => ({
            ...section,
            rows: section.rows.filter((row) => row.id !== id),
        })),
    };
}

/**
 * Re-splits a row into columns of the given twelfths, keeping existing content.
 *
 * This is the one primitive behind both "how many columns" and the named layout
 * presets (1-2, 3-1 …): a preset is just a list of spans.
 */
export function applyColumnLayout(
    document: PageDocument,
    rowId: string,
    spans: readonly number[],
): PageDocument {
    const clamped = spans
        .slice(0, 6)
        .map((span) => Math.max(1, Math.min(COLUMN_GRID, Math.round(span))));

    if (clamped.length === 0) {
        return document;
    }

    return {
        ...document,
        sections: document.sections.map((section) => ({
            ...section,
            rows: section.rows.map((row) => {
                if (row.id !== rowId) {
                    return row;
                }
                const columns: ColumnData[] = [];
                // Tablet and mobile widths were chosen for the old columns; a
                // different number of columns starts those tiers afresh.
                const keepTiers = clamped.length === row.columns.length;

                clamped.forEach((span, index) => {
                    const existing = row.columns[index];
                    columns.push(
                        existing
                            ? { ...existing, span: keepTiers ? { ...existing.span, desktop: span } : { desktop: span } }
                            : createColumn(span),
                    );
                });

                // Content from removed columns is never silently dropped.
                const orphans = row.columns.slice(clamped.length).flatMap((column) => column.blocks);
                const last = columns[columns.length - 1];
                if (last && orphans.length > 0) {
                    columns[columns.length - 1] = { ...last, blocks: [...last.blocks, ...orphans] };
                }

                return { ...row, columns };
            }),
        })),
    };
}

/** Column widths as they actually render at a tier, with stacking folded in. */
export function effectiveSpans(row: RowData, breakpoint: Breakpoint): number[] {
    const stacked = row.stackBelow === 'tablet' ? ['tablet', 'mobile'] : row.stackBelow === 'none' || row.stackBelow == null ? [] : ['mobile'];
    return row.columns.map((column) => (stacked.includes(breakpoint) ? COLUMN_GRID : spanAt(column.span, breakpoint)));
}

/**
 * Sets how a row lays out on one tier without touching the others.
 *
 * `spans` has one entry per existing column (a tier can rearrange columns but
 * never add or remove them; that is a desktop decision). `'stack'` puts every
 * column at full width. `'desktop'` makes tablet follow desktop again.
 *
 * The result is written back in the most natural shape: stacking stays a
 * `stackBelow` setting whenever it can, and a tier only carries its own spans
 * when they differ from what it would inherit.
 */
export function rowLayoutAt(
    row: RowData,
    breakpoint: Exclude<Breakpoint, 'desktop'>,
    choice: readonly number[] | 'stack' | 'desktop',
): Pick<RowData, 'columns' | 'stackBelow'> {
    const count = row.columns.length;
    const clamp = (span: number) => Math.max(1, Math.min(COLUMN_GRID, Math.round(span)));
    const layout: Record<Breakpoint, number[]> = {
        desktop: effectiveSpans(row, 'desktop'),
        tablet: effectiveSpans(row, 'tablet'),
        mobile: effectiveSpans(row, 'mobile'),
    };
    layout[breakpoint] =
        choice === 'stack'
            ? Array.from({ length: count }, () => COLUMN_GRID)
            : choice === 'desktop'
              ? [...layout.desktop]
              : Array.from({ length: count }, (_, index) => clamp(choice[index] ?? COLUMN_GRID));

    const full = (spans: number[]) => spans.every((span) => span === COLUMN_GRID);
    const same = (a: number[], b: number[]) => a.every((span, index) => span === b[index]);
    const stackBelow = full(layout.tablet) && full(layout.mobile) && !full(layout.desktop) ? 'tablet' : full(layout.mobile) ? 'mobile' : 'none';

    return {
        stackBelow,
        columns: row.columns.map((column, index) => {
            const span: ColumnData['span'] = { desktop: layout.desktop[index] ?? COLUMN_GRID };
            if (stackBelow !== 'tablet' && layout.tablet[index] !== span.desktop) {
                span.tablet = layout.tablet[index];
            }
            if (stackBelow === 'none' && !same(layout.mobile, Array.from({ length: count }, () => COLUMN_GRID))) {
                span.mobile = layout.mobile[index];
            }
            return { ...column, span };
        }),
    };
}

/** Re-splits a row into `count` equal columns, keeping the existing content. */
export function setColumnCount(document: PageDocument, rowId: string, count: number): PageDocument {
    const clamped = Math.max(1, Math.min(6, count));
    const span = Math.floor(COLUMN_GRID / clamped);
    return applyColumnLayout(document, rowId, Array.from({ length: clamped }, () => span));
}

/* -------------------------------------------------------------------------- */
/* Node patches — the settings panels write through these                      */
/* -------------------------------------------------------------------------- */

export function updateSection(
    document: PageDocument,
    id: string,
    patch: Partial<SectionData>,
): PageDocument {
    return {
        ...document,
        sections: document.sections.map((section) =>
            section.id === id ? { ...section, ...patch } : section,
        ),
    };
}

export function updateRow(document: PageDocument, id: string, patch: Partial<RowData>): PageDocument {
    return {
        ...document,
        sections: document.sections.map((section) => ({
            ...section,
            rows: section.rows.map((row) => (row.id === id ? { ...row, ...patch } : row)),
        })),
    };
}

export function updateColumn(
    document: PageDocument,
    id: string,
    patch: Partial<ColumnData>,
): PageDocument {
    return mapColumns(document, (column) => (column.id === id ? { ...column, ...patch } : column));
}

/** The row a column belongs to — the column panel needs its siblings' spans. */
export function findColumnRow(document: PageDocument, columnId: string): RowData | null {
    for (const section of document.sections) {
        for (const row of section.rows) {
            if (row.columns.some((column) => column.id === columnId)) {
                return row;
            }
        }
    }
    return null;
}

/** The section a row belongs to — needed to duplicate or re-home a row. */
export function findRowSection(document: PageDocument, rowId: string): SectionData | null {
    return document.sections.find((section) => section.rows.some((row) => row.id === rowId)) ?? null;
}

/* -------------------------------------------------------------------------- */
/* Fragment insertion — design blocks                                          */
/* -------------------------------------------------------------------------- */

/**
 * A deep copy of a section with fresh ids all the way down, so the same design
 * block can be dropped onto a page twice without colliding ids.
 */
export function cloneSection(section: SectionData): SectionData {
    return {
        ...section,
        id: newId(),
        rows: section.rows.map((row) => ({
            ...row,
            id: newId(),
            columns: row.columns.map((column) => ({
                ...column,
                id: newId(),
                blocks: column.blocks.map(cloneBlock),
            })),
        })),
    };
}

export function addSections(document: PageDocument, sections: SectionData[]): PageDocument {
    return addSectionsAt(document, sections, document.sections.length);
}

/**
 * Insert a ready-made fragment at a position rather than only at the end.
 *
 * Every section is cloned on the way in, which is what makes it safe to drop
 * the same design block twice: the library hands back one set of ids per HTTP
 * response, and two insertions from a single page load would otherwise share
 * them.
 */
export function addSectionsAt(
    document: PageDocument,
    sections: SectionData[],
    index: number,
): PageDocument {
    const next = [...document.sections];
    const at = Math.max(0, Math.min(index, next.length));
    next.splice(at, 0, ...sections.map(cloneSection));
    return { ...document, sections: next };
}

/** The index of a section, or null when the id is not on this page. */
export function sectionIndexOf(document: PageDocument, sectionId: string): number | null {
    const index = document.sections.findIndex((section) => section.id === sectionId);
    return index === -1 ? null : index;
}

/**
 * The section a node lives in, whatever kind of node it is.
 *
 * Used to resolve a drop: a design block dragged over a *block* still has to
 * land between two sections, so the block's ancestry is walked back up to the
 * section that encloses it.
 */
export function sectionContaining(document: PageDocument, ref: NodeRef): SectionData | null {
    if (ref.kind === 'section') {
        return findSection(document, ref.id);
    }

    if (ref.kind === 'row') {
        return findRowSection(document, ref.id);
    }

    if (ref.kind === 'column') {
        const row = findColumnRow(document, ref.id);
        return row ? findRowSection(document, row.id) : null;
    }

    const located = locateBlock(document, ref.id);
    if (!located) {
        return null;
    }

    // A block's parent is either a column or another block; walking up through
    // the containing block chain is not needed, because every chain bottoms
    // out at a column.
    if (located.parent.kind === 'column') {
        return sectionContaining(document, { kind: 'column', id: located.parent.id });
    }

    return sectionContaining(document, { kind: 'block', id: located.parent.id });
}

/** Flat outline for the layer tree. */
export interface OutlineNode {
    ref: NodeRef;
    label: string;
    depth: number;
    hidden: boolean;
}

export function outline(document: PageDocument, breakpoint: Breakpoint = 'desktop'): OutlineNode[] {
    const nodes: OutlineNode[] = [];

    const pushBlocks = (blocks: BlockData[], depth: number, parentHidden: boolean) => {
        for (const block of blocks) {
            const hidden = parentHidden || block.visibility?.[breakpoint] === false;
            nodes.push({
                ref: { kind: 'block', id: block.id },
                label: block.name ?? block.type.replace(/_/g, ' '),
                depth,
                hidden,
            });
            pushBlocks(block.children ?? [], depth + 1, hidden);
        }
    };

    document.sections.forEach((section, sectionIndex) => {
        const sectionHidden = section.visibility?.[breakpoint] === false;
        nodes.push({
            ref: { kind: 'section', id: section.id },
            label: section.name ?? `Section ${sectionIndex + 1}`,
            depth: 0,
            hidden: sectionHidden,
        });
        section.rows.forEach((row, rowIndex) => {
            const rowHidden = sectionHidden || row.visibility?.[breakpoint] === false;
            nodes.push({
                ref: { kind: 'row', id: row.id },
                label: row.name ?? `Row ${rowIndex + 1}`,
                depth: 1,
                hidden: rowHidden,
            });
            row.columns.forEach((column, columnIndex) => {
                const columnHidden = rowHidden || column.visibility?.[breakpoint] === false;
                nodes.push({
                    ref: { kind: 'column', id: column.id },
                    label: column.name ?? `Column ${columnIndex + 1}`,
                    depth: 2,
                    hidden: columnHidden,
                });
                pushBlocks(column.blocks, 3, columnHidden);
            });
        });
    });

    return nodes;
}

/** Palette clicks follow the selection, including blocks nested in containers. */
export function paletteTarget(document: PageDocument, selected: NodeRef | null): DropTarget | null {
    if (selected && sectionContaining(document, selected)?.globalComponentId) return null;
    if (selected?.kind === 'block') {
        const located = locateBlock(document, selected.id);
        if (located) {
            if (isContainerBlock(located.block.type) && !isColumnsContainer(located.block)) {
                return { kind: 'into', blockId: selected.id };
            }
            if (isColumnsContainer(located.block)) {
                const cell = located.block.children?.[0];
                return cell ? { kind: 'into', blockId: cell.id } : null;
            }
            return located.parent.kind === 'column'
                ? { kind: 'column', columnId: located.parent.id, index: located.index + 1 }
                : { kind: 'into', blockId: located.parent.id, index: located.index + 1 };
        }
    }
    const column = selected?.kind === 'column' ? findColumn(document, selected.id)
        : selected?.kind === 'row' ? findRow(document, selected.id)?.columns[0]
        : selected?.kind === 'section' ? findSection(document, selected.id)?.rows[0]?.columns[0]
        : document.sections.find(section => !section.globalComponentId)?.rows[0]?.columns[0];
    return column ? { kind: 'column', columnId: column.id } : null;
}
