/**
 * The builder shell: toolbar, palette, canvas, layer tree and properties.
 *
 * The `DndContext` is mounted here rather than in the canvas so that it
 * encloses the left-hand panels too. dnd-kit only pairs a draggable with a
 * droppable inside the same context, so a provider nested in the canvas can
 * reorder blocks that are already on the page but can never accept a block
 * dragged in from the palette — the drag just ends nowhere, silently.
 *
 * Saving is manual (Save button or Ctrl/⌘+S) rather than automatic. The one
 * rule it must never break: a 409 from the server means someone else saved after
 * us, so the local document is stale and must NOT be pushed again. The store
 * moves to `conflict` and stays there until the editor reloads — silently
 * retrying would overwrite the other person's work.
 */

import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
    DndContext,
    DragOverlay,
    PointerSensor,
    pointerWithin,
    useSensor,
    useSensors,
    type DragEndEvent,
    type DragStartEvent,
} from '@dnd-kit/core';
import {
    ArrowLeft,
    Download,
    Eye,
    History,
    Loader2,
    Monitor,
    PanelLeftClose,
    PanelLeftOpen,
    PanelRightClose,
    PanelRightOpen,
    Redo2,
    Rocket,
    Save,
    Keyboard,
    Smartphone,
    Sparkles,
    Tablet,
    Undo2,
} from 'lucide-react';
import type { Breakpoint } from '@renderer';
import { Button } from '@shared/ui/Button';
import { Spinner } from '@shared/ui/Spinner';
import { Tabs } from '@shared/ui/Tabs';
import { EmptyState } from '@shared/ui/EmptyState';
import { Modal } from '@shared/ui/Modal';
import { useToast } from '@shared/ui/Toast';
import { cn } from '@shared/lib/cn';
import { normaliseSettings, serialiseDocument, CURRENT_SCHEMA_VERSION, type PageDocument } from '@renderer';
import {
    globalComponentUsage,
    loadGlobalComponent,
    loadPage,
    publishPage,
    saveGlobalComponent,
    savePage,
    type SaveResult,
} from './api';
import { BuilderModeContext, type BuilderSource } from './builderMode';
import { SectionLibrary } from './panels/SectionLibrary';
import { useBuilderStore } from './store';
import { outline, cloneSection, paletteTarget, sectionContaining, type DropTarget, type PaletteChoice } from './tree';
import { createDraftSaver, errorStatus } from './persistence';
import { builderShortcut } from './shortcuts';
import { BuilderFunnelContext, type BuilderFunnel } from './funnelContext';
import { BlockPicker } from './panels/BlockPicker';
import {
    blockTargetFor,
    dragLabel,
    decodeDropZone,
    isDragPayload,
    sectionIndexFor,
    type DragPayload,
} from './dnd';
import { Canvas } from './Canvas';
import { useCanvasRuntime } from './useCanvasRuntime';
import { Palette } from './panels/Palette';
import { DesignBlocks } from './panels/DesignBlocks';
import { SectionOrder } from './panels/SectionOrder';
import { reorderSection } from './sectionOrder';
import { LayerTree } from './panels/LayerTree';
import { Properties } from './panels/Properties';
import { TemplateLibrary } from './panels/TemplateLibrary';
import { AiPageModal } from './panels/AiPageModal';
import { HistoryModal } from './panels/HistoryModal';
import { FunnelPanel } from './panels/FunnelPanel';
import { funnelWidgetConfig, type FunnelWidgetId } from './funnelWidgets';
import './builder.css';

/**
 * Where the back button goes: Admin → Pages in Modrix CMS.
 */
const DASHBOARD_FUNNELS = '/admin/pages';

type LeftTab = 'blocks' | 'design' | 'library' | 'funnel';

const LEFT_TABS = [
    { id: 'blocks', label: 'Blocks' },
    { id: 'design', label: 'Designs' },
    { id: 'library', label: 'Saved' },
] as const;

/** A component is edited alone, so it only gets the block palette. */
const COMPONENT_LEFT_TABS = [{ id: 'blocks', label: 'Blocks' }] as const;

/**
 * Save the document being edited, wherever it lives. A global component is
 * exactly one section, so a component document with any other count is
 * refused here rather than sent to be rejected.
 */
async function saveDocument(source: BuilderSource, document: PageDocument, version: number): Promise<SaveResult> {
    if (source.kind === 'page') {
        return savePage(source.id, document, version);
    }

    const [section, ...extra] = document.sections;
    if (!section || extra.length > 0) {
        throw Object.assign(new Error('A global component is exactly one section. Remove the extra sections to save.'), {
            status: 422,
        });
    }

    return saveGlobalComponent(source.id, section, version);
}

const BREAKPOINTS: ReadonlyArray<{ value: Breakpoint; label: string; Icon: typeof Monitor }> = [
    { value: 'desktop', label: 'Desktop', Icon: Monitor },
    { value: 'tablet', label: 'Tablet', Icon: Tablet },
    { value: 'mobile', label: 'Mobile', Icon: Smartphone },
];

function flushActiveEditor(): void {
    const active = document.activeElement;
    if (active instanceof HTMLElement && (active.isContentEditable || active.matches('input, textarea, select'))) {
        active.blur();
    }
}

function SaveIndicator({ state, at }: { state: string; at: number | null }) {
    if (state === 'saving') {
        return (
            <span className="fnl-save" role="status">
                <Loader2 aria-hidden className="fnl-spin" size={14} /> Saving…
            </span>
        );
    }

    if (state === 'conflict') {
        return (
            <span className="fnl-save is-error" role="status">
                Someone else saved this page
            </span>
        );
    }

    if (state === 'error') {
        return (
            <span className="fnl-save is-error" role="status">
                Could not save
            </span>
        );
    }

    if (state === 'dirty') {
        return (
            <span className="fnl-save is-dirty" role="status">
                Unsaved changes
            </span>
        );
    }

    if (state === 'saved' && at !== null) {
        return (
            <span className="fnl-save" role="status">
                Saved
            </span>
        );
    }

    return null;
}

export function BuilderApp({ source }: { source: BuilderSource }) {
    const pageId = source.id;
    const editingComponent = source.kind === 'component';
    // How many pages a component is on — shown next to its save state, because
    // every save of a component lands on all of them.
    const [usage, setUsage] = useState<number | null>(null);
    const toast = useToast();
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState<string | null>(null);
    const [pageName, setPageName] = useState('');
    const [publishing, setPublishing] = useState(false);
    const [templatesOpen, setTemplatesOpen] = useState(false);
    const [aiModalOpen, setAiModalOpen] = useState(false);
    const [historyOpen, setHistoryOpen] = useState(false);
    const [shortcutsOpen, setShortcutsOpen] = useState(false);
    const [leftTab, setLeftTab] = useState<LeftTab>('blocks');
    const [rightTab, setRightTab] = useState<'block' | 'layers'>('block');
    // Either side panel folds away to give the canvas room; remembered per browser.
    const [collapsed, setCollapsed] = useState<{ left: boolean; right: boolean }>(readCollapsedPanels);
    const togglePanel = useCallback((side: 'left' | 'right'): void => {
        setCollapsed((current) => {
            const next = { ...current, [side]: !current[side] };
            writeCollapsedPanels(next);
            return next;
        });
    }, []);
    const [funnel, setFunnel] = useState<BuilderFunnel | null>(null);
    // Where the block picker will insert. Null means the picker is closed.
    const [pickerTarget, setPickerTarget] = useState<DropTarget | null>(null);

    const store = useBuilderStore();
    const {
        pageRole,
        document: doc,
        selected,
        breakpoint,
        previewing,
        saveState,
        lastSavedAt,
    } = store;
    const isGlobalChrome = pageRole === 'header' || pageRole === 'footer';
    const busy = publishing;
    const actionPending = useRef(false);
    const saver = useMemo(() => createDraftSaver(
        useBuilderStore.getState,
        (document, version) => saveDocument(source, document, version),
    ), [source.kind, source.id]);

    const reportSaveError = useCallback((error: unknown): void => {
        if (errorStatus(error) === 409) {
            useBuilderStore.getState().markConflict();
            toast.error('This page changed elsewhere', 'Export your draft before reloading the latest version.');
        } else {
            toast.error('Could not save the page', 'Your changes are still here. Use Retry save to try again.');
        }
    }, [toast]);

    const saveNow = useCallback(async (): Promise<void> => {
        // Inline text and buffered settings commit on blur; include them in Save / Ctrl+S.
        flushActiveEditor();
        const beforeState = useBuilderStore.getState().saveState;
        if (beforeState !== 'dirty' && beforeState !== 'error') {
            return;
        }
        try {
            await saver.flush();
            if (useBuilderStore.getState().saveState === 'saved') {
                toast.success('Draft saved');
            }
        } catch (error) {
            reportSaveError(error);
        }
    }, [saver, reportSaveError, toast]);

    const exportDraft = useCallback((): void => {
        const data = serialiseDocument(useBuilderStore.getState().document);
        const url = URL.createObjectURL(new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = `${pageName.replace(/[^a-z0-9_-]+/gi, '-').replace(/^-|-$/g, '') || 'page'}-draft.json`;
        link.click();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    }, [pageName]);

    /* ---------------------------------------------------------------- load */

    useEffect(() => {
        let cancelled = false;
        setLoading(true);
        setLoadError(null);
        setUsage(null);

        const request = editingComponent
            ? loadGlobalComponent(pageId).then((component) => {
                  if (cancelled) {
                      return;
                  }
                  setPageName(`${component.name} · global component`);
                  setFunnel(null);
                  setLeftTab('blocks');
                  store.load(
                      component.id,
                      { schemaVersion: CURRENT_SCHEMA_VERSION, settings: normaliseSettings(null), sections: [component.section] },
                      component.version,
                  );
                  setLoading(false);
                  void globalComponentUsage(component.id)
                      .then((pages) => !cancelled && setUsage(pages.length))
                      .catch(() => undefined);
              })
            : loadPage(pageId).then((page) => {
                  if (cancelled) {
                      return;
                  }
                  setPageName(page.name);
                  setFunnel(page.funnel);
                  setLeftTab(page.funnel ? 'funnel' : 'blocks');
                  store.load(page.id, page.document, page.version, page.role);
                  setLoading(false);
              });

        request.catch(() => {
            if (!cancelled) {
                setLoadError(editingComponent ? 'This global component could not be loaded.' : 'This page could not be loaded.');
                setLoading(false);
            }
        });

        return () => {
            cancelled = true;
        };
        // The store is intentionally excluded: loading must happen once per page id.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [pageId, editingComponent]);

    /* ---------------------------------------------------- unsaved warning */

    useEffect(() => {
        const beforeUnload = (event: BeforeUnloadEvent): void => {
            flushActiveEditor();
            if (['dirty', 'saving', 'error', 'conflict'].includes(useBuilderStore.getState().saveState)) {
                event.preventDefault();
                event.returnValue = '';
            }
        };
        window.addEventListener('beforeunload', beforeUnload);
        return () => window.removeEventListener('beforeunload', beforeUnload);
    }, []);

    /* ------------------------------------------------- keyboard shortcuts */

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent): void => {
            if (loading || busy || actionPending.current) return;
            const target = event.target as HTMLElement | null;
            const editing = Boolean(target?.isContentEditable || target?.closest('input, textarea, select, [role="textbox"]'));
            const modalOpen = templatesOpen || historyOpen || shortcutsOpen || pickerTarget !== null || Boolean(document.querySelector('[role="dialog"][aria-modal="true"]'));
            const shortcut = builderShortcut(event, editing, modalOpen);
            if (!shortcut) return;
            const state = useBuilderStore.getState();
            if (shortcut === 'save') { event.preventDefault(); void saveNow(); return; }
            if (shortcut === 'escape') {
                event.preventDefault();
                if (state.previewing) state.togglePreview();
                else state.select(null);
                return;
            }
            if (state.previewing) return;
            if (shortcut === 'left-panel' || shortcut === 'right-panel') {
                event.preventDefault();
                togglePanel(shortcut === 'left-panel' ? 'left' : 'right');
            } else if (shortcut === 'undo' || shortcut === 'redo') {
                event.preventDefault();
                state[shortcut]();
            } else if (state.selected?.kind === 'block') {
                if (sectionContaining(state.document, state.selected)?.globalComponentId) return;
                event.preventDefault();
                if (shortcut === 'duplicate') state.duplicate(state.selected.id);
                else if (shortcut === 'delete') state.deleteBlock(state.selected.id);
            }
        };

        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [loading, busy, templatesOpen, historyOpen, shortcutsOpen, pickerTarget, togglePanel, saveNow]);

    const openHistory = useCallback((): void => {
        if (actionPending.current) return;
        const state = useBuilderStore.getState();
        if (state.saveState === 'conflict') {
            toast.error('Resolve the save conflict first', 'Reload the page before restoring an earlier revision.');
            return;
        }
        setHistoryOpen(true);
    }, [toast]);

    /* ------------------------------------------------------------- leave */

    /**
     * Back to the pages list. With manual saving, confirm before discarding
     * any unsaved edits rather than autosaving them on navigation.
     */
    const navigateFromBuilder = useCallback((destination: string): void => {
        if (actionPending.current) return;
        flushActiveEditor();
        const { saveState: currentSaveState } = useBuilderStore.getState();
        if (['dirty', 'saving', 'error', 'conflict'].includes(currentSaveState)) {
            if (!window.confirm('You have unsaved changes. Leave without saving?')) {
                return;
            }
        }
        window.location.assign(destination);
    }, []);

    const leaveBuilder = useCallback(() => navigateFromBuilder(
        funnel ? `${DASHBOARD_FUNNELS}/${funnel.id}` : DASHBOARD_FUNNELS,
    ), [funnel, editingComponent, navigateFromBuilder]);

    /* ------------------------------------------------------------ publish */

    const handlePublish = useCallback(async (): Promise<void> => {
        if (actionPending.current || useBuilderStore.getState().saveState === 'conflict') return;
        flushActiveEditor();
        actionPending.current = true;
        setPublishing(true);
        try {
            await saver.flush();
            const state = useBuilderStore.getState();
            const result = await publishPage(state.pageId, state.version);
            useBuilderStore.getState().markSaved(result.version, state.document);
            toast.success('Page published');
        } catch (error) {
            if (errorStatus(error) === 409) reportSaveError(error);
            else toast.error('Could not publish this page', 'Your draft is still available. Save it and try again.');
        } finally {
            actionPending.current = false;
            setPublishing(false);
        }
    }, [saver, toast, reportSaveError]);

    /* --------------------------------------------------------- drag and drop */

    const addFunnelWidget = useCallback((id: FunnelWidgetId, dropTarget?: DropTarget): void => {
        if (!funnel) return;
        const config = funnelWidgetConfig(id, funnel);
        if (!config) return;
        let state = useBuilderStore.getState();
        if (state.document.sections.length === 0 && !dropTarget) {
            state.appendSection();
            state = useBuilderStore.getState();
        }
        const target = dropTarget ?? paletteTarget(state.document, state.selected);
        const owner = target ? sectionContaining(state.document, target.kind === 'column'
            ? { kind: 'column', id: target.columnId } : { kind: 'block', id: target.blockId }) : null;
        if (!target || owner?.globalComponentId) {
            toast.error('Choose an editable column', 'Global components must be edited from their own editor or detached first.');
            return;
        }
        state.addBlock(config.type, target, config);
        setRightTab('block');
    }, [funnel, toast]);

    const [dragging, setDragging] = useState<DragPayload | null>(null);

    // A small activation distance so a click to select, or a click on a
    // palette entry, is never swallowed by the drag sensor.
    const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 5 } }));

    const handleDragStart = useCallback((event: DragStartEvent): void => {
        const payload = event.active.data.current;
        setDragging(isDragPayload(payload) ? payload : null);
    }, []);

    const handleDragEnd = useCallback(
        (event: DragEndEvent): void => {
            setDragging(null);

            const payload = event.active.data.current;
            const zone = event.over ? decodeDropZone(String(event.over.id)) : null;

            if (!isDragPayload(payload) || zone === null) {
                return;
            }

            const state = useBuilderStore.getState();

            // A design block is a set of whole sections, so it lands *between*
            // sections. A zone pointing at a column or a block is resolved up
            // to whichever section encloses it.
            if (payload.source === 'design') {
                state.insertSections(payload.sections, sectionIndexFor(state.document, zone));
                toast.success(`${payload.name} added`);
                return;
            }

            if (payload.source === 'section') {
                const next = reorderSection(state.document, payload.sectionId, sectionIndexFor(state.document, zone));
                if (next !== state.document) state.commit(next);
                state.select({ kind: 'section', id: payload.sectionId });
                return;
            }

            const target = blockTargetFor(state.document, zone);

            if (target === null) {
                return;
            }

            if (payload.source === 'palette') {
                state.addBlock(payload.blockType, target);
                return;
            }

            if (payload.source === 'widget') {
                addFunnelWidget(payload.widgetId, target);
                return;
            }

            // Dropping a block immediately before itself is a no-op, not a move.
            if (target.kind === 'before' && target.blockId === payload.blockId) {
                return;
            }

            state.dropBlock(payload.blockId, target);
        },
        [toast, addFunnelWidget],
    );

    const nodes = useMemo(() => outline(doc, breakpoint), [doc, breakpoint]);
    const canvasRuntime = useCanvasRuntime(doc, funnel);

    if (loading) {
        return (
            <div className="fnl-builder-loading">
                <Spinner label="Loading the page" />
            </div>
        );
    }

    if (loadError !== null) {
        return (
            <div className="fnl-builder-loading">
                <EmptyState
                    title="Page unavailable"
                    description={loadError}
                    action={<Button onClick={() => window.location.assign(DASHBOARD_FUNNELS)}>Back to pages</Button>}
                />
            </div>
        );
    }

    const insertPicked = (type: PaletteChoice): void => {
        if (pickerTarget) {
            useBuilderStore.getState().addBlock(type, pickerTarget);
        }
    };

    return (
        <BuilderModeContext.Provider value={source}>
        <BuilderFunnelContext.Provider value={funnel}>
        <div className={cn('fnl-builder', previewing && 'is-previewing')}>
            <header className="fnl-builder-bar" inert={busy}>
                <div className="fnl-builder-bar-left">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={leaveBuilder}
                        aria-label={funnel ? `Back to ${funnel.name}` : 'Back to pages'}
                        title={funnel ? `Back to ${funnel.name}` : 'Back to pages'}
                    >
                        <ArrowLeft aria-hidden size={16} />
                    </Button>
                    {!previewing && (
                        <Button
                            variant="secondary"
                            size="sm"
                            onClick={() => togglePanel('left')}
                            aria-pressed={!collapsed.left}
                            aria-label={collapsed.left ? 'Show blocks panel' : 'Hide blocks panel'}
                            title={`${collapsed.left ? 'Show' : 'Hide'} blocks panel (Ctrl+[)`}
                        >
                            {collapsed.left ? <PanelLeftOpen aria-hidden size={16} /> : <PanelLeftClose aria-hidden size={16} />}
                        </Button>
                    )}
                    <span className="fnl-page-name">{pageName}</span>
                    {isGlobalChrome && (
                        <span className="fnl-save" title="Shared across every page using the global chrome">
                            {pageRole === 'header' ? 'Global header · all pages' : 'Global footer · all pages'}
                        </span>
                    )}
                    {editingComponent && usage !== null && (
                        <span className="fnl-save" title="Saving updates every page that uses this component">
                            Used on {usage} {usage === 1 ? 'page' : 'pages'}
                        </span>
                    )}
                    <SaveIndicator state={saveState} at={lastSavedAt} />
                </div>

                <div className="fnl-builder-bar-center" role="group" aria-label="Preview size">
                    {BREAKPOINTS.map(({ value, label, Icon }) => (
                        <button
                            key={value}
                            type="button"
                            className={cn('fnl-bp', breakpoint === value && 'is-active')}
                            aria-pressed={breakpoint === value}
                            aria-label={label}
                            title={label}
                            onClick={() => store.setBreakpoint(value)}
                        >
                            <Icon aria-hidden size={16} />
                        </button>
                    ))}
                </div>

                <div className="fnl-builder-bar-right">
                    <Button
                        variant={saveState === 'dirty' || saveState === 'error' ? 'primary' : 'secondary'}
                        size="sm"
                        onClick={() => void saveNow()}
                        loading={saveState === 'saving'}
                        disabled={saveState === 'conflict'}
                        title="Save draft (Ctrl+S or ⌘S)"
                    >
                        <Save aria-hidden size={16} /> Save
                    </Button>
                    <Button variant="ghost" size="sm" iconOnly onClick={exportDraft} aria-label="Export draft as JSON" title="Export draft as JSON">
                        <Download aria-hidden size={16} />
                    </Button>
                    <Button variant="ghost" size="sm" iconOnly onClick={() => setShortcutsOpen(true)} aria-label="Keyboard shortcuts" title="Keyboard shortcuts">
                        <Keyboard aria-hidden size={16} />
                    </Button>
                    {!isGlobalChrome && !editingComponent && (
                        <Button variant="secondary" size="sm" onClick={() => setAiModalOpen(true)}>
                            <Sparkles aria-hidden size={15} />
                            AI modify
                        </Button>
                    )}
                    {!isGlobalChrome && (
                        <Button variant="secondary" size="sm" onClick={() => setTemplatesOpen(true)}>Templates</Button>
                    )}
                    {!editingComponent && (
                        <Button
                            variant="ghost"
                            size="sm"
                            disabled={saveState === 'saving'}
                            onClick={openHistory}
                        >
                            <History aria-hidden size={16} />
                            History
                        </Button>
                    )}
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => store.undo()}
                        disabled={!store.canUndo()}
                        aria-label="Undo"
                    >
                        <Undo2 aria-hidden size={16} />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => store.redo()}
                        disabled={!store.canRedo()}
                        aria-label="Redo"
                    >
                        <Redo2 aria-hidden size={16} />
                    </Button>
                    <Button
                        variant={previewing ? 'secondary' : 'ghost'}
                        size="sm"
                        onClick={() => store.togglePreview()}
                        aria-pressed={previewing}
                    >
                        <Eye aria-hidden size={16} />
                        Preview
                    </Button>
                    {!editingComponent && (
                        <Button variant="primary" size="sm" onClick={handlePublish} loading={publishing} disabled={saveState === 'conflict'}>
                            <Rocket aria-hidden size={16} />
                            Publish
                        </Button>
                    )}
                    {!previewing && (
                        <Button
                            variant="secondary"
                            size="sm"
                            onClick={() => togglePanel('right')}
                            aria-pressed={!collapsed.right}
                            aria-label={collapsed.right ? 'Show settings panel' : 'Hide settings panel'}
                            title={`${collapsed.right ? 'Show' : 'Hide'} settings panel (Ctrl+])`}
                        >
                            {collapsed.right ? <PanelRightOpen aria-hidden size={16} /> : <PanelRightClose aria-hidden size={16} />}
                        </Button>
                    )}
                </div>
            </header>

            {(saveState === 'conflict' || saveState === 'error') && (
                <div className="fnl-save-banner" role="alert">
                    <p>{saveState === 'conflict'
                        ? 'This page changed elsewhere. Export your draft to keep a copy, then reload the latest version.'
                        : 'Your changes could not be saved. They are still available in this tab.'}</p>
                    <Button size="sm" variant="secondary" onClick={exportDraft}><Download aria-hidden size={14} /> Export draft</Button>
                    {saveState === 'error' ? (
                        <Button size="sm" onClick={() => void saveNow()} disabled={busy}>Retry save</Button>
                    ) : (
                        <Button size="sm" variant="secondary" onClick={() => window.location.reload()}>Reload latest</Button>
                    )}
                </div>
            )}

            <Modal open={shortcutsOpen} onClose={() => setShortcutsOpen(false)} title="Keyboard shortcuts" size="sm" description="Use Ctrl on Windows or ⌘ on Mac. Text fields keep their own editing shortcuts.">
                <dl className="fnl-shortcuts">
                    {[
                        ['Save draft', 'Ctrl / ⌘ + S'],
                        ['Undo', 'Ctrl / ⌘ + Z'],
                        ['Redo', 'Ctrl / ⌘ + Shift + Z, Ctrl + Y'],
                        ['Duplicate selected block', 'Ctrl / ⌘ + D'],
                        ['Delete selected block', 'Delete / Backspace'],
                        ['Clear selection / exit preview', 'Escape'],
                        ['Toggle blocks panel', 'Ctrl / ⌘ + ['],
                        ['Toggle settings panel', 'Ctrl / ⌘ + ]'],
                        ['Navigate layers', '↑ ↓ ← →, Home, End'],
                    ].map(([label, keys]) => <div key={label}><dt>{label}</dt><dd><kbd>{keys}</kbd></dd></div>)}
                </dl>
            </Modal>

            {!isGlobalChrome && (
                <TemplateLibrary open={templatesOpen} onClose={() => setTemplatesOpen(false)} onSaveDraft={saveNow} />
            )}
            {!isGlobalChrome && !editingComponent && (
                <AiPageModal
                    open={aiModalOpen}
                    pageId={pageId}
                    pageTitle={pageName}
                    onClose={() => setAiModalOpen(false)}
                />
            )}
            {!editingComponent && (
                <HistoryModal
                    open={historyOpen}
                    pageId={pageId}
                    currentVersion={store.version}
                    onClose={() => setHistoryOpen(false)}
                    onRestored={(result) => {
                        store.load(pageId, result.document, result.version);
                        store.markSaved(result.version);
                        setHistoryOpen(false);
                        toast.success('Page restored', `Version ${result.version} is now the current draft.`);
                    }}
                />
            )}
            <DndContext
                sensors={sensors}
                collisionDetection={pointerWithin}
                onDragStart={handleDragStart}
                onDragEnd={handleDragEnd}
                onDragCancel={() => setDragging(null)}
            >
                <div className="fnl-builder-body" inert={busy || templatesOpen || aiModalOpen || historyOpen || shortcutsOpen}>
                {!previewing && collapsed.left && (
                    <div className="fnl-panel-rail fnl-panel-rail-left">
                        <button type="button" className="fnl-panel-rail-button" onClick={() => togglePanel('left')} aria-label="Show blocks panel" title="Show blocks panel (Ctrl+[)">
                            <PanelLeftOpen aria-hidden size={16} />
                            <span>Blocks</span>
                        </button>
                    </div>
                )}
                {!previewing && !collapsed.left && (
                    <aside className="fnl-panel fnl-panel-left" aria-label="Blocks and layers">
                        <section className="fnl-panel-section">
                            <Tabs
                                variant="pill"
                                className={funnel ? 'fnl-funnel-tabs' : undefined}
                                value={leftTab}
                                onValueChange={(id) => setLeftTab(id === 'design' || id === 'library' || id === 'funnel' ? id : 'blocks')}
                                tabs={editingComponent ? COMPONENT_LEFT_TABS : funnel ? [{ id: 'funnel', label: 'Funnel' }, ...LEFT_TABS] : LEFT_TABS}
                            >
                                {leftTab === 'funnel' && funnel ? (
                                    <FunnelPanel funnel={funnel} document={doc} onAdd={addFunnelWidget}
                                        onOpenStep={(id) => void navigateFromBuilder(`/builder/${encodeURIComponent(id)}`)}
                                        onSelectBlock={(id) => {
                                            store.select({ kind: 'block', id });
                                            setRightTab('block');
                                            if (collapsed.right) togglePanel('right');
                                            const element = window.document.querySelector(`[data-fnl-id="${CSS.escape(id)}"]`);
                                            element?.scrollIntoView({ block: 'center', behavior: 'smooth' });
                                        }} />
                                ) : leftTab === 'blocks' ? (
                                    <Palette
                                        onAdd={(type) => {
                                            let state = useBuilderStore.getState();
                                            if (state.document.sections.length === 0) {
                                                state.appendSection();
                                                state = useBuilderStore.getState();
                                            }
                                            const target = paletteTarget(state.document, state.selected);
                                            if (target) state.addBlock(type, target);
                                            else toast.error('Choose an editable column', 'Global components must be edited from their own editor or detached first.');
                                        }}
                                    />
                                ) : leftTab === 'library' && !editingComponent ? (
                                    <SectionLibrary
                                        onInsert={(section, label) => {
                                            useBuilderStore.getState().insertSections([section]);
                                            toast.success(`${label} added`, 'It was appended to the bottom of the page.');
                                        }}
                                    />
                                ) : !editingComponent ? (
                                    <DesignBlocks
                                        onReplace={(block, sectionId) => {
                                            const state = useBuilderStore.getState();
                                            if (!block.sections[0] || !state.document.sections.some(section => section.id === sectionId)) return;
                                            state.replaceSection(sectionId, cloneSection(block.sections[0]));
                                            toast.success(`${block.name} replaced the section`, 'Undo restores your previous section.');
                                        }}
                                        onInsert={(block) => {
                                            useBuilderStore.getState().insertSections(block.sections);
                                            toast.success(`${block.name} added`, 'It was appended to the bottom of the page.');
                                        }}
                                    />
                                ) : null}
                            </Tabs>
                        </section>

                    </aside>
                )}

                <main className="fnl-canvas" aria-label="Page canvas">
                    <Canvas
                        document={doc}
                        breakpoint={breakpoint}
                        selected={selected}
                        previewing={previewing}
                        onSelect={(ref) => {
                            store.select(ref);
                            if (ref) setRightTab('block');
                        }}
                        onBlockPropsChange={store.setBlockProps}
                        onAppendToColumn={(columnId) => setPickerTarget({ kind: 'column', columnId })}
                        onSetColumns={store.setColumns}
                        onAppendToBlock={(blockId) => setPickerTarget({ kind: 'into', blockId })}
                        onDeleteBlock={store.deleteBlock}
                        onAppendSection={
                            editingComponent
                                ? () => toast.error('A global component is one section', 'Add rows and blocks inside it instead.')
                                : store.appendSection
                        }
                        onInsertSections={editingComponent ? undefined : (sections, beforeId) => {
                            const state = useBuilderStore.getState();
                            const index = beforeId === null ? state.document.sections.length : state.document.sections.findIndex(section => section.id === beforeId);
                            if (index < 0) {
                                toast.error('Insertion position no longer exists', 'Choose another gap between sections.');
                                return;
                            }
                            state.insertSections(sections, index);
                            const first = useBuilderStore.getState().document.sections[index];
                            if (first) state.select({ kind: 'section', id: first.id });
                            toast.success('Section added', 'Inserted at your selected position. Undo is available.');
                        }}
                        runtime={canvasRuntime}
                    />
                </main>

                {!previewing && collapsed.right && (
                    <div className="fnl-panel-rail fnl-panel-rail-right">
                        <button type="button" className="fnl-panel-rail-button" onClick={() => togglePanel('right')} aria-label="Show settings panel" title="Show settings panel (Ctrl+])">
                            <PanelRightOpen aria-hidden size={16} />
                            <span>Settings</span>
                        </button>
                    </div>
                )}
                {!previewing && !collapsed.right && (
                    <aside className="fnl-panel fnl-panel-right" aria-label="Properties">
                        <button type="button" className="m-2 rounded-md border border-slate-200 px-3 py-2 text-sm" onClick={() => { store.select(null); setRightTab('block'); }}>Page body settings</button>
                        <Tabs
                            variant="pill"
                            value={rightTab}
                            onValueChange={(id) => setRightTab(id === 'layers' ? 'layers' : 'block')}
                            tabs={[{ id: 'block', label: 'Block' }, { id: 'layers', label: 'Layers' }]}
                        >
                        {rightTab === 'layers' ? (
                            <section className="fnl-panel-section">
                                <SectionOrder />
                                <h2 className="fnl-panel-title">Layers</h2>
                                <LayerTree nodes={nodes} selected={selected} breakpoint={breakpoint} onSelect={store.select} />
                            </section>
                        ) : (
                        <Properties
                            document={doc}
                            selected={selected}
                            breakpoint={breakpoint}
                            onStyleChange={store.setStyle}
                            onVisibilityChange={store.setVisibility}
                            onPropsChange={store.setBlockProps}
                            onRename={store.renameBlock}
                            onDuplicate={store.duplicate}
                            onDelete={store.deleteBlock}
                        />
                        )}
                        </Tabs>
                    </aside>
                )}
                </div>

                <DragOverlay dropAnimation={null}>
                    {dragging === null ? null : (
                        <div className="fnl-drag-ghost">{dragLabel(dragging)}</div>
                    )}
                </DragOverlay>
            </DndContext>

            <BlockPicker open={pickerTarget !== null} onClose={() => setPickerTarget(null)} onPick={insertPicked} />
        </div>
        </BuilderFunnelContext.Provider>
        </BuilderModeContext.Provider>
    );
}

const PANELS_KEY = 'fnl.builder.collapsedPanels';

function readCollapsedPanels(): { left: boolean; right: boolean } {
    try {
        const stored = JSON.parse(window.localStorage.getItem(PANELS_KEY) ?? '{}') as Record<string, unknown>;
        return { left: stored['left'] === true, right: stored['right'] === true };
    } catch {
        return { left: false, right: false };
    }
}

function writeCollapsedPanels(value: { left: boolean; right: boolean }): void {
    try {
        window.localStorage.setItem(PANELS_KEY, JSON.stringify(value));
    } catch {
        // Private windows can refuse storage; the panels just reset next visit.
    }
}
