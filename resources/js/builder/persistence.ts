import type { PageDocument } from '@renderer';
import type { BuilderState } from './store';

type PersistenceState = Pick<BuilderState, 'pageId' | 'document' | 'version' | 'saveState' | 'markSaving' | 'markSaved' | 'markConflict' | 'markError'>;

export function errorStatus(error: unknown): number | undefined {
    if (typeof error !== 'object' || error === null) return undefined;
    return (error as { status?: number }).status ?? (error as { response?: { status?: number } }).response?.status;
}

/** Autosave, Save, Publish, History and Back share one queue and version token. */
export function createDraftSaver(
    getState: () => PersistenceState,
    save: (document: PageDocument, version: number) => Promise<{ version: number }>,
) {
    let pending: Promise<void> | null = null;

    async function drain(): Promise<void> {
        while (true) {
            const state = getState();
            if (state.saveState === 'conflict') {
                throw Object.assign(new Error('Resolve the save conflict before saving.'), { status: 409 });
            }
            if (state.saveState !== 'dirty' && state.saveState !== 'error') return;

            const { pageId, document, version } = state;
            state.markSaving();
            try {
                const result = await save(document, version);
                if (getState().pageId !== pageId) return;
                getState().markSaved(result.version, document);
            } catch (error) {
                if (getState().pageId === pageId) {
                    if (errorStatus(error) === 409) getState().markConflict();
                    else getState().markError();
                }
                throw error;
            }
            // An edit made while the request was in flight must also be saved.
            if (getState().saveState !== 'dirty') return;
        }
    }

    return {
        flush(): Promise<void> {
            if (pending) return pending;
            pending = drain().finally(() => { pending = null; });
            return pending;
        },
    };
}
