/**
 * Builder state.
 *
 * One Zustand store owns the document, the selection, the active breakpoint and
 * the undo stack. Every structural change goes through `commit()`, which is the
 * single place that pushes onto history and marks the document dirty — so undo
 * and autosave can never disagree about what changed.
 */

import { create } from 'zustand';
import {
    blankDocument,
    createRow,
    createSection,
    type BlockData,
    type Breakpoint,
    type ColumnData,
    type NodeRef,
    type PageDocument,
    type RowData,
    type SectionData,
    type StyleData,
    type VisibilityData,
} from '@renderer';
import {
    addRow,
    addSection,
    addSectionsAt,
    applyColumnLayout,
    createPaletteBlock,
    duplicateBlock,
    insertBlock,
    locateBlock,
    findStyledNode,
    sectionContaining,
    moveBlock,
    deleteBlockTidy,
    removeRow,
    removeSection,
    setColumnCount,
    setContainerColumns,
    setContainerLayout,
    updateBlock,
    updateBlockProps,
    updateColumn,
    updateNodeStyle,
    updateNodeVisibility,
    updateRow,
    updateSection,
    type ContainerLayout,
    type DropTarget,
    type PaletteChoice,
} from './tree';

/** Deep enough history to undo a whole editing session, capped so it cannot grow forever. */
export const HISTORY_LIMIT = 100;

export type SaveState = 'idle' | 'dirty' | 'saving' | 'saved' | 'conflict' | 'error';

export interface BuilderState {
    pageId: string;
    /** Optimistic concurrency token; the API rejects a stale one with a 409. */
    version: number;
    document: PageDocument;
    selected: NodeRef | null;
    breakpoint: Breakpoint;
    previewing: boolean;
    saveState: SaveState;
    lastSavedAt: number | null;
    past: PageDocument[];
    future: PageDocument[];

    /* lifecycle */
    load: (pageId: string, document: PageDocument, version: number) => void;
    markSaving: () => void;
    markSaved: (version: number, savedDocument?: PageDocument) => void;
    markConflict: () => void;
    markError: () => void;

    /* view */
    select: (ref: NodeRef | null) => void;
    setBreakpoint: (breakpoint: Breakpoint) => void;
    togglePreview: () => void;

    /* history */
    commit: (next: PageDocument) => void;
    undo: () => void;
    redo: () => void;
    canUndo: () => boolean;
    canRedo: () => boolean;

    /* edits */
    addBlock: (type: PaletteChoice, target: DropTarget, config?: { name: string; props: Record<string, unknown> }) => void;
    /** Switch a container between stack, row, grid and nested columns. */
    setContainerLayout: (blockId: string, layout: ContainerLayout) => void;
    /** Re-split a columns container, keeping its content. */
    setContainerColumns: (blockId: string, spans: readonly number[]) => void;
    dropBlock: (blockId: string, target: DropTarget) => void;
    deleteBlock: (blockId: string) => void;
    duplicate: (blockId: string) => void;
    setBlockProps: (blockId: string, patch: Record<string, unknown>) => void;
    renameBlock: (blockId: string, name: string) => void;
    /** Replace fields of a block in one undo step — props and children together. */
    patchBlock: (blockId: string, patch: Partial<BlockData>) => void;
    setStyle: (ref: NodeRef, style: StyleData) => void;
    setVisibility: (ref: NodeRef, visibility: VisibilityData) => void;
    appendSection: () => void;
    appendRow: (sectionId: string, columns: number) => void;
    deleteSection: (sectionId: string) => void;
    deleteRow: (rowId: string) => void;
    setColumns: (rowId: string, count: number) => void;
    setColumnLayout: (rowId: string, spans: readonly number[]) => void;
    patchSection: (sectionId: string, patch: Partial<SectionData>) => void;
    patchRow: (rowId: string, patch: Partial<RowData>) => void;
    patchColumn: (columnId: string, patch: Partial<ColumnData>) => void;
    /**
     * Drop a design-block fragment onto the page. `index` places it before the
     * section currently at that position; omitted, it appends. Ids are
     * regenerated on the way in, so the same fragment can be dropped twice.
     */
    insertSections: (sections: SectionData[], index?: number) => void;
    /** Swap one section for another in place — used when a section becomes a global component instance. */
    replaceSection: (sectionId: string, section: SectionData) => void;
}

export const useBuilderStore = create<BuilderState>()((set, get) => ({
    pageId: '',
    version: 0,
    document: blankDocument(),
    selected: null,
    breakpoint: 'desktop',
    previewing: false,
    saveState: 'idle',
    lastSavedAt: null,
    past: [],
    future: [],

    load: (pageId, document, version) =>
        set({
            pageId,
            document,
            version,
            past: [],
            future: [],
            selected: null,
            saveState: 'idle',
            lastSavedAt: null,
            previewing: false,
        }),

    markSaving: () => set({ saveState: 'saving' }),
    markSaved: (version, savedDocument) => set((state) => ({
        version,
        saveState: state.saveState === 'conflict' ? 'conflict' : savedDocument && state.document !== savedDocument ? 'dirty' : 'saved',
        lastSavedAt: Date.now(),
    })),
    markConflict: () => set({ saveState: 'conflict' }),
    markError: () => set({ saveState: 'error' }),

    select: (ref) => set({ selected: ref }),
    setBreakpoint: (breakpoint) => set({ breakpoint }),
    togglePreview: () =>
        set((state) => ({ previewing: !state.previewing, selected: state.previewing ? state.selected : null })),

    commit: (next) =>
        set((state) => next === state.document ? state : ({
            document: next,
            past: [...state.past, state.document].slice(-HISTORY_LIMIT),
            future: [],
            saveState: state.saveState === 'conflict' ? 'conflict' : 'dirty',
            selected: findStyledNode(next, state.selected) ? state.selected : null,
        })),

    undo: () =>
        set((state) => {
            const previous = state.past[state.past.length - 1];
            if (!previous) {
                return state;
            }
            return {
                document: previous,
                past: state.past.slice(0, -1),
                future: [state.document, ...state.future].slice(0, HISTORY_LIMIT),
                saveState: state.saveState === 'conflict' ? 'conflict' : 'dirty',
                selected: findStyledNode(previous, state.selected) ? state.selected : null,
            };
        }),

    redo: () =>
        set((state) => {
            const next = state.future[0];
            if (!next) {
                return state;
            }
            return {
                document: next,
                past: [...state.past, state.document].slice(-HISTORY_LIMIT),
                future: state.future.slice(1),
                saveState: state.saveState === 'conflict' ? 'conflict' : 'dirty',
                selected: findStyledNode(next, state.selected) ? state.selected : null,
            };
        }),

    canUndo: () => get().past.length > 0,
    canRedo: () => get().future.length > 0,

    addBlock: (type, target, config) => {
        const block: BlockData = createPaletteBlock(type);
        if (config) {
            block.name = config.name;
            block.props = { ...block.props, ...config.props };
        }
        const next = insertBlock(get().document, block, target);
        if (next === get().document) return;
        get().commit(next);
        set({ selected: { kind: 'block', id: block.id } });
    },

    setContainerLayout: (blockId, layout) => get().commit(setContainerLayout(get().document, blockId, layout)),

    setContainerColumns: (blockId, spans) => get().commit(setContainerColumns(get().document, blockId, spans)),

    dropBlock: (blockId, target) => {
        get().commit(moveBlock(get().document, blockId, target));
    },

    deleteBlock: (blockId) => {
        get().commit(deleteBlockTidy(get().document, blockId));
        const selected = get().selected;
        if (selected?.kind === 'block' && selected.id === blockId) {
            set({ selected: null });
        }
    },

    duplicate: (blockId) => {
        const current = get().document;
        if (sectionContaining(current, { kind: 'block', id: blockId })?.globalComponentId) return;
        const next = duplicateBlock(current, blockId);
        if (next === current) return;
        const located = locateBlock(next, blockId);
        const copy = located?.siblings[located.index + 1];
        get().commit(next);
        if (copy) set({ selected: { kind: 'block', id: copy.id } });
    },

    setBlockProps: (blockId, patch) => get().commit(updateBlockProps(get().document, blockId, patch)),

    renameBlock: (blockId, name) => get().commit(updateBlock(get().document, blockId, { name })),

    patchBlock: (blockId, patch) => get().commit(updateBlock(get().document, blockId, patch)),

    setStyle: (ref, style) => get().commit(updateNodeStyle(get().document, ref, style)),

    setVisibility: (ref, visibility) => get().commit(updateNodeVisibility(get().document, ref, visibility)),

    appendSection: () => {
        const section = createSection();
        if (get().document.settings.contentWidth === '100%') {
            section.width = 'full';
            section.style = { ...section.style, maxWidth: { desktop: '100%', tablet: '100%', mobile: '100%' } };
        }
        get().commit(addSection(get().document, section));
        set({ selected: { kind: 'section', id: section.id } });
    },

    appendRow: (sectionId, columns) => {
        const span = Math.floor(12 / Math.max(1, columns));
        const row = createRow(Array.from({ length: Math.max(1, columns) }, () => span));
        get().commit(addRow(get().document, sectionId, row));
        set({ selected: { kind: 'row', id: row.id } });
    },

    deleteSection: (sectionId) => {
        get().commit(removeSection(get().document, sectionId));
        set({ selected: null });
    },

    deleteRow: (rowId) => {
        get().commit(removeRow(get().document, rowId));
        set({ selected: null });
    },

    setColumns: (rowId, count) => get().commit(setColumnCount(get().document, rowId, count)),

    setColumnLayout: (rowId, spans) => get().commit(applyColumnLayout(get().document, rowId, spans)),

    patchSection: (sectionId, patch) => get().commit(updateSection(get().document, sectionId, patch)),

    patchRow: (rowId, patch) => get().commit(updateRow(get().document, rowId, patch)),

    patchColumn: (columnId, patch) => get().commit(updateColumn(get().document, columnId, patch)),

    insertSections: (sections, index) => {
        if (sections.length === 0) {
            return;
        }
        const current = get().document;
        const at = Math.max(0, Math.min(index ?? current.sections.length, current.sections.length));
        const inserted = current.settings.contentWidth === '100%' ? sections.map(section => section.globalComponentId ? section : {
            ...section, width: 'full', style: { ...section.style, maxWidth: { desktop: '100%', tablet: '100%', mobile: '100%' } },
        }) : sections;
        const next = addSectionsAt(current, inserted, at);
        get().commit(next);
        // Select the first section that landed, so the properties panel and
        // the canvas both move to what was just dropped.
        const first = next.sections[at];
        if (first) {
            set({ selected: { kind: 'section', id: first.id } });
        }
    },

    replaceSection: (sectionId, section) => {
        const current = get().document;
        get().commit({
            ...current,
            sections: current.sections.map((entry) => (entry.id === sectionId ? section : entry)),
        });
        set({ selected: { kind: 'section', id: section.id } });
    },
}));
