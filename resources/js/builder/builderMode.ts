/**
 * What the builder is editing: a page, or a global component.
 *
 * A few controls only make sense for one of the two — you cannot make a
 * component out of the component you are editing, and a component has no
 * publish button of its own — so panels read the mode from here rather than
 * having it threaded through every props list between the shell and them.
 */

import { createContext, useContext } from 'react';

export type BuilderSource = { kind: 'page'; id: string } | { kind: 'component'; id: string };

export const BuilderModeContext = createContext<BuilderSource>({ kind: 'page', id: '' });

export function useBuilderMode(): BuilderSource {
    return useContext(BuilderModeContext);
}
