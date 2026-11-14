/**
 * The builder's Library tab: this workspace's global components and saved
 * sections.
 *
 * The two look alike but behave differently, and the panel says so:
 *  - a **global component** is inserted as a live instance — edit the
 *    component once and every page embedding it changes;
 *  - a **saved section** is inserted as a copy — it owns its content from the
 *    moment it lands and never changes again.
 */

import { useCallback, useEffect, useState } from 'react';
import { Copy, ExternalLink, Layers, Link2, Plus, RefreshCw, Trash2 } from 'lucide-react';
import { Button } from '@shared/ui/Button';
import { EmptyState } from '@shared/ui/EmptyState';
import { Skeleton } from '@shared/ui/Skeleton';
import { useToast } from '@shared/ui/Toast';
import type { SectionData } from '@renderer';
import {
    deleteSavedSection,
    listGlobalComponents,
    listSavedSections,
    loadSavedSection,
    mintGlobalComponentInstance,
    type GlobalComponentSummary,
    type SavedSection,
} from '../api';

interface LibraryState {
    components: GlobalComponentSummary[];
    sections: SavedSection[];
}

export function SectionLibrary({ onInsert }: { onInsert: (section: SectionData, label: string) => void }) {
    const toast = useToast();
    const [state, setState] = useState<LibraryState | null>(null);
    const [failed, setFailed] = useState<string | null>(null);
    const [busy, setBusy] = useState<string | null>(null);

    const load = useCallback(() => {
        setFailed(null);
        Promise.all([listGlobalComponents(), listSavedSections()])
            .then(([components, sections]) => setState({ components, sections }))
            .catch((error: unknown) => setFailed(error instanceof Error ? error.message : 'The library could not be loaded.'));
    }, []);

    useEffect(() => {
        load();
        // Something saved to the library from Section settings shows up here
        // without a reload.
        window.addEventListener('fnl:library-changed', load);
        return () => window.removeEventListener('fnl:library-changed', load);
    }, [load]);

    const run = async (key: string, task: () => Promise<void>): Promise<void> => {
        setBusy(key);
        try {
            await task();
        } catch (error) {
            toast.error('That did not work', error instanceof Error ? error.message : undefined);
        } finally {
            setBusy(null);
        }
    };

    if (failed) {
        return (
            <EmptyState
                variant="bare"
                title="Library unavailable"
                description={failed}
                action={<Button size="sm" leadingIcon={<RefreshCw className="h-3.5 w-3.5" />} onClick={load}>Try again</Button>}
            />
        );
    }

    if (state === null) {
        return (
            <div className="flex flex-col gap-2 pt-2">
                <Skeleton className="h-9 w-full" />
                <Skeleton className="h-9 w-full" />
                <Skeleton className="h-9 w-full" />
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-5 pt-2">
            <details className="fnl-library-group" open>
                <summary className="flex items-center gap-2">
                    <Link2 className="h-3.5 w-3.5 text-[var(--color-text-subtle)]" aria-hidden />
                    <h3 className="text-[length:var(--text-xs)] font-semibold uppercase tracking-wide text-[var(--color-text-subtle)]">
                        Global components
                    </h3>
                </summary>
                <p className="text-[length:var(--text-2xs)] leading-snug text-[var(--color-text-muted)]">
                    Inserted live. Edit a component once and every page using it updates.
                </p>
                {state.components.length === 0 ? (
                    <p className="rounded-md border border-dashed border-[var(--color-border)] px-3 py-3 text-[length:var(--text-xs)] text-[var(--color-text-muted)]">
                        None yet. Select a section and choose “Make global” in its settings.
                    </p>
                ) : (
                    <ul className="flex flex-col gap-1.5">
                        {state.components.map((component) => (
                            <li key={component.id} className="flex items-center gap-1 rounded-md border border-[var(--color-border)] bg-[var(--color-surface)] px-2 py-1.5">
                                <span className="flex min-w-0 flex-1 flex-col">
                                    <span className="truncate text-[length:var(--text-sm)] font-medium">{component.name}</span>
                                    <span className="text-[length:var(--text-2xs)] text-[var(--color-text-muted)]">{component.typeLabel}</span>
                                </span>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    iconOnly
                                    aria-label={`Edit ${component.name}`}
                                    title="Edit the component (opens in a new tab)"
                                    onClick={() => window.open(`/builder/components/${component.id}`, '_blank', 'noopener')}
                                >
                                    <ExternalLink className="h-3.5 w-3.5" />
                                </Button>
                                <Button
                                    size="sm"
                                    variant="secondary"
                                    leadingIcon={<Plus className="h-3.5 w-3.5" />}
                                    loading={busy === component.id}
                                    onClick={() =>
                                        void run(component.id, async () => {
                                            onInsert(await mintGlobalComponentInstance(component.id), component.name);
                                        })
                                    }
                                >
                                    Insert
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}
            </details>

            <details className="fnl-library-group" open>
                <summary className="flex items-center gap-2">
                    <Copy className="h-3.5 w-3.5 text-[var(--color-text-subtle)]" aria-hidden />
                    <h3 className="text-[length:var(--text-xs)] font-semibold uppercase tracking-wide text-[var(--color-text-subtle)]">
                        Saved sections
                    </h3>
                </summary>
                <p className="text-[length:var(--text-2xs)] leading-snug text-[var(--color-text-muted)]">
                    Inserted as a copy you can change freely on this page.
                </p>
                {state.sections.length === 0 ? (
                    <EmptyState
                        variant="bare"
                        icon={<Layers className="h-5 w-5" />}
                        title="No saved sections"
                        description="Select a section and choose “Save to library” to reuse it on other pages."
                    />
                ) : (
                    <ul className="flex flex-col gap-1.5">
                        {state.sections.map((saved) => (
                            <li key={saved.id} className="flex items-center gap-1 rounded-md border border-[var(--color-border)] bg-[var(--color-surface)] px-2 py-1.5">
                                <span className="min-w-0 flex-1 truncate text-[length:var(--text-sm)] font-medium">{saved.name}</span>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    iconOnly
                                    aria-label={`Delete ${saved.name}`}
                                    loading={busy === `delete-${saved.id}`}
                                    onClick={() => {
                                        if (!window.confirm(`Delete “${saved.name}” from the library? Pages that already use it keep their copy.`)) {
                                            return;
                                        }
                                        void run(`delete-${saved.id}`, async () => {
                                            await deleteSavedSection(saved.id);
                                            setState((current) =>
                                                current ? { ...current, sections: current.sections.filter((entry) => entry.id !== saved.id) } : current,
                                            );
                                        });
                                    }}
                                >
                                    <Trash2 className="h-3.5 w-3.5" />
                                </Button>
                                <Button
                                    size="sm"
                                    variant="secondary"
                                    leadingIcon={<Plus className="h-3.5 w-3.5" />}
                                    loading={busy === saved.id}
                                    onClick={() =>
                                        void run(saved.id, async () => {
                                            onInsert(await loadSavedSection(saved.id), saved.name);
                                        })
                                    }
                                >
                                    Insert
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}
            </details>
        </div>
    );
}
