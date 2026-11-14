/**
 * The funnel the page being edited belongs to.
 *
 * Only the builder needs this — to offer "go to step X" as a button target and
 * "continue to the next step" as a form's success action — so it is a small
 * React context provided by the shell rather than state threaded through every
 * panel between the shell and the one field that reads it.
 */

import { createContext, useContext } from 'react';

export interface BuilderFunnelStep {
    id: string;
    name: string;
    type: string;
    position: number;
    pageId: string | null;
}

export interface BuilderFunnel {
    id: string;
    name: string;
    currentStepId: string;
    templateKey?: string | null;
    steps: BuilderFunnelStep[];
}

export const BuilderFunnelContext = createContext<BuilderFunnel | null>(null);

export function useBuilderFunnel(): BuilderFunnel | null {
    return useContext(BuilderFunnelContext);
}

/** Narrow the API payload; anything malformed means "no funnel context". */
export function toBuilderFunnel(raw: unknown): BuilderFunnel | null {
    if (typeof raw !== 'object' || raw === null) {
        return null;
    }

    const source = raw as Record<string, unknown>;
    const steps = Array.isArray(source['steps']) ? source['steps'] : [];

    if (typeof source['id'] !== 'string' || typeof source['current_step_id'] !== 'string') {
        return null;
    }

    return {
        id: source['id'],
        name: typeof source['name'] === 'string' ? source['name'] : 'Funnel',
        currentStepId: source['current_step_id'],
        templateKey: typeof source['template_key'] === 'string' ? source['template_key'] : null,
        steps: steps
            .filter((step): step is Record<string, unknown> => typeof step === 'object' && step !== null)
            .filter((step) => typeof step['id'] === 'string')
            .map((step) => ({
                id: String(step['id']),
                name: typeof step['name'] === 'string' ? step['name'] : 'Step',
                type: typeof step['type'] === 'string' ? step['type'] : '',
                position: typeof step['position'] === 'number' ? step['position'] : 0,
                pageId: typeof step['page_id'] === 'string' ? step['page_id'] : null,
            }))
            .sort((a, b) => a.position - b.position),
    };
}
