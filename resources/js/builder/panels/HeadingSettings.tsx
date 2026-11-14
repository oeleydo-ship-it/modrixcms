/**
 * The heading's content fields.
 *
 * Heading is the block people touch most, so it gets the most care. Two things
 * it does that the generic editor did not: it separates the *level* (which is
 * document structure and SEO) from the *size* (which is design), and it shows
 * the outline consequence of the level you pick. Choosing H1 for "make it big"
 * is the single most common mistake in a page builder, and the panel is the
 * only place to head it off.
 *
 * Everything else the block needs — size, weight, line height, letter spacing,
 * colour, alignment, margin — comes from the shared Typography and Spacing
 * groups, because a heading is not a special case for any of them.
 */

import { Input } from '@shared/ui/Input';
import { Textarea } from '@shared/ui/Textarea';
import { cn } from '@shared/lib/cn';
import { Alert } from '@shared/ui/Alert';
import { PanelNote } from './controls';
import { propText, type PropsPatch } from './BlockContentFields';

const LEVELS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as const;
type Level = (typeof LEVELS)[number];

const LEVEL_HELP: Record<Level, string> = {
    h1: 'The page title. Use exactly one per page — it is what search engines and screen readers treat as the name of the whole page.',
    h2: 'A major section heading. The usual choice for the headline of a band.',
    h3: 'A sub-heading inside an H2 section.',
    h4: 'A fourth-level heading — rarely needed on a landing page.',
    h5: 'A fifth-level heading, usually only inside long-form content.',
    h6: 'The deepest level. If you are reaching for it to get small text, set a font size instead.',
};

function isLevel(value: string): value is Level {
    return (LEVELS as readonly string[]).includes(value);
}

export function HeadingContent({
    props,
    set,
    /** How many H1s the page already has, so we can warn about a second one. */
    h1Count,
}: {
    props: Record<string, unknown>;
    set: PropsPatch;
    h1Count: number;
}) {
    const raw = propText(props, 'level').toLowerCase();
    const level: Level = isLevel(raw) ? raw : 'h2';

    return (
        <>
            <Textarea
                label="Text"
                rows={3}
                value={propText(props, 'text')}
                onChange={(event) => set({ text: event.currentTarget.value })}
            />

            <fieldset className="flex flex-col gap-1.5">
                <legend className="text-[length:var(--text-xs)] font-medium text-[var(--color-text)]">
                    Level
                </legend>
                <div
                    role="radiogroup"
                    aria-label="Heading level"
                    onKeyDown={(event) => {
                        const delta = event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : 0;
                        if (delta === 0) {
                            return;
                        }
                        event.preventDefault();
                        const index = LEVELS.indexOf(level);
                        const next = LEVELS[(index + delta + LEVELS.length) % LEVELS.length];
                        if (next) {
                            set({ level: next });
                            event.currentTarget.querySelectorAll<HTMLButtonElement>('[role="radio"]')[LEVELS.indexOf(next)]?.focus();
                        }
                    }}
                    className="flex gap-1 rounded-md bg-[var(--color-surface-sunken)] p-0.5"
                >
                    {LEVELS.map((option) => {
                        const selected = option === level;
                        return (
                            <button
                                key={option}
                                type="button"
                                role="radio"
                                aria-checked={selected}
                                aria-label={`Heading level ${option.slice(1)}`}
                                title={LEVEL_HELP[option]}
                                tabIndex={selected ? 0 : -1}
                                onClick={() => set({ level: option })}
                                className={cn(
                                    'flex-1 rounded px-1 py-1 text-[length:var(--text-xs)] uppercase',
                                    'focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[var(--color-ring)]',
                                    selected
                                        ? 'bg-[var(--color-surface)] font-semibold text-[var(--color-text)] shadow-[var(--shadow-xs)]'
                                        : 'text-[var(--color-text-muted)] hover:text-[var(--color-text)]',
                                )}
                            >
                                {option}
                            </button>
                        );
                    })}
                </div>
                <PanelNote>{LEVEL_HELP[level]}</PanelNote>
            </fieldset>

            {level === 'h1' && h1Count > 1 && (
                <Alert tone="warning" title="More than one H1 on this page">
                    {h1Count} headings are set to H1. Search engines and screen readers expect a single page
                    title — make the others H2 and use a font size to keep them looking large.
                </Alert>
            )}

            <Input
                label="Links to"
                size="sm"
                placeholder="https://… — leave blank for a plain heading"
                value={propText(props, 'link')}
                onChange={(event) => set({ link: event.currentTarget.value })}
            />

            <PanelNote>
                Choosing H1-H6 applies a predefined size and line height for desktop, tablet and mobile. You can fine-tune the size and weight under Typography afterward.
            </PanelNote>
        </>
    );
}
