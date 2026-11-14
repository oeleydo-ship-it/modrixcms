/**
 * The block picker.
 *
 * Opened by every "Add block" affordance on the canvas. Adding used to drop a
 * paragraph straight into the column, which meant the first act after "add"
 * was always "delete what I did not want and go find what I did". The picker
 * asks first: a searchable, grouped grid, one click to insert the chosen block
 * where the button was.
 *
 * Keyboard use is first-class — the search field takes focus on open, arrow
 * keys move through the results, Enter inserts — because this dialog sits on
 * the most frequent path in the whole builder.
 */

import { useEffect, useMemo, useRef, useState, type KeyboardEvent } from 'react';
import {
    AlignLeft,
    BadgeCheck,
    Box,
    CalendarCheck,
    CalendarDays,
    ChevronsDownUp,
    CircleHelp,
    Code,
    Columns3,
    FileCode,
    FileText,
    GalleryHorizontal,
    Gauge,
    Heading,
    Image,
    Images,
    LayoutGrid,
    LayoutPanelLeft,
    Mail,
    MapPin,
    Menu,
    Minus,
    MousePointerClick,
    MoveVertical,
    Package,
    PanelTop,
    Phone,
    Quote,
    Share2,
    ShoppingCart,
    Sparkles,
    Tags,
    Timer,
    Video,
    type LucideIcon,
} from 'lucide-react';
import { Modal } from '@shared/ui/Modal';
import { SearchInput } from '@shared/ui/SearchInput';
import { cn } from '@shared/lib/cn';
import {
    BLOCK_GROUP,
    BLOCK_GROUP_LABEL,
    BLOCK_LABEL,
    PALETTE_BLOCK_TYPES,
    type BlockGroup,
} from '@renderer';
import type { PaletteChoice } from '../tree';

const GROUP_ORDER: readonly BlockGroup[] = [
    'content',
    'capture',
    'persuasion',
    'commerce',
    'media',
    'layout',
    'scheduling',
    'engagement',
];

const ICON: Record<PaletteChoice, LucideIcon> = {
    columns: LayoutPanelLeft,
    container: Box,
    heading: Heading,
    paragraph: AlignLeft,
    image: Image,
    video: Video,
    button: MousePointerClick,
    icon: Sparkles,
    divider: Minus,
    spacer: MoveVertical,
    html: FileCode,
    custom_code: Code,
    form: FileText,
    email_optin: Mail,
    phone_input: Phone,
    checkout: ShoppingCart,
    product: Package,
    pricing_table: Tags,
    countdown: Timer,
    testimonial: Quote,
    faq: CircleHelp,
    accordion: ChevronsDownUp,
    tabs: PanelTop,
    progress_bar: Gauge,
    feature_cards: LayoutGrid,
    carousel: GalleryHorizontal,
    gallery: Images,
    social_icons: Share2,
    navigation: Menu,
    logo: BadgeCheck,
    map: MapPin,
    calendar: CalendarDays,
    booking: CalendarCheck,
    popup_trigger: Columns3,
};

/** One line on what each block is for — the label alone rarely says enough. */
const DESCRIPTION: Record<PaletteChoice, string> = {
    columns: 'Side-by-side columns inside this column',
    container: 'Group blocks in a stack, row or grid',
    heading: 'A title or section headline',
    paragraph: 'Body copy with bold, italic and links',
    image: 'A picture, optionally linked',
    video: 'YouTube, Vimeo, Wistia or a file',
    button: 'Link, next step, scroll or submit',
    icon: 'A single icon',
    divider: 'A horizontal rule',
    spacer: 'Vertical breathing room',
    html: 'Raw HTML embed',
    custom_code: 'HTML, CSS and JS together',
    form: 'A multi-field capture form',
    email_optin: 'Email field and a button',
    phone_input: 'Phone capture with SMS consent',
    checkout: 'Take payment for products',
    product: 'One product with price and buy button',
    pricing_table: 'Plans side by side',
    countdown: 'Deadline timer — fixed or evergreen',
    testimonial: 'A quote with rating and author',
    faq: 'Questions that expand to answers',
    accordion: 'Collapsible panels of any blocks',
    tabs: 'Tabbed panels of any blocks',
    progress_bar: 'Show how far through the funnel',
    feature_cards: 'A grid of icon, title and text',
    carousel: 'Slides that rotate',
    gallery: 'An image grid with lightbox',
    social_icons: 'Links to your social profiles',
    navigation: 'A menu of links',
    logo: 'Your workspace logo',
    map: 'An address on a map',
    calendar: 'Show availability',
    booking: 'Let visitors book a time',
    popup_trigger: 'Open a popup on click or intent',
};

/**
 * Blocks whose backing module has not shipped. They can be placed and styled,
 * but stay hidden on published pages — the picker says so up front rather than
 * letting someone build a page around a block visitors will never see.
 */
const COMING_SOON: ReadonlySet<PaletteChoice> = new Set<PaletteChoice>();

/** The blocks people reach for most, shown first when nothing is searched. */
const LABEL = (choice: PaletteChoice): string => (choice === 'columns' ? 'Columns' : BLOCK_LABEL[choice]);
const GROUP_OF = (choice: PaletteChoice): BlockGroup => (choice === 'columns' ? 'layout' : BLOCK_GROUP[choice]);
const CHOICES: readonly PaletteChoice[] = ['columns', ...PALETTE_BLOCK_TYPES];

const POPULAR: readonly PaletteChoice[] = [
    'heading',
    'paragraph',
    'button',
    'image',
    'video',
    'feature_cards',
    'testimonial',
    'countdown',
];

export interface BlockPickerProps {
    open: boolean;
    onClose: () => void;
    onPick: (type: PaletteChoice) => void;
}

export function BlockPicker({ open, onClose, onPick }: BlockPickerProps) {
    const [query, setQuery] = useState('');
    const [active, setActive] = useState(0);
    const gridRef = useRef<HTMLDivElement>(null);

    // Every open starts clean: a stale search from the last insert would hide
    // most of the library behind a filter nobody remembers typing.
    useEffect(() => {
        if (open) {
            setQuery('');
            setActive(0);
        }
    }, [open]);

    const groups = useMemo(() => {
        const needle = query.trim().toLowerCase();

        const matches = (type: PaletteChoice): boolean =>
            needle === '' ||
            LABEL(type).toLowerCase().includes(needle) ||
            DESCRIPTION[type].toLowerCase().includes(needle) ||
            BLOCK_GROUP_LABEL[GROUP_OF(type)].toLowerCase().includes(needle);

        const grouped = GROUP_ORDER.map((group) => ({
            key: group as string,
            label: BLOCK_GROUP_LABEL[group],
            types: CHOICES.filter((type) => GROUP_OF(type) === group && matches(type)),
        })).filter((entry) => entry.types.length > 0);

        return needle === '' ? [{ key: 'popular', label: 'Popular', types: [...POPULAR] }, ...grouped] : grouped;
    }, [query]);

    // A flat list in display order, so arrow keys walk the grid the way it reads.
    const flat = useMemo(() => groups.flatMap((group) => group.types), [groups]);

    useEffect(() => {
        setActive(0);
    }, [query]);

    const pick = (type: PaletteChoice | undefined): void => {
        if (type) {
            onPick(type);
            onClose();
        }
    };

    const onKeyDown = (event: KeyboardEvent<HTMLDivElement>): void => {
        if (flat.length === 0) {
            return;
        }

        const columns = 3;
        const moves: Record<string, number> = {
            ArrowRight: 1,
            ArrowLeft: -1,
            ArrowDown: columns,
            ArrowUp: -columns,
        };

        if (event.key in moves) {
            event.preventDefault();
            const next = Math.max(0, Math.min(flat.length - 1, active + (moves[event.key] ?? 0)));
            setActive(next);
            gridRef.current
                ?.querySelector<HTMLElement>(`[data-picker-index="${next}"]`)
                ?.scrollIntoView({ block: 'nearest' });
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            pick(flat[active]);
        }
    };

    let index = -1;

    return (
        <Modal
            open={open}
            onClose={onClose}
            title="Add a block"
            description="Pick what to add. It lands where you clicked, and you can drag it anywhere afterwards."
            size="xl"
        >
            <div className="flex flex-col gap-4" onKeyDown={onKeyDown}>
                <SearchInput
                    value={query}
                    onValueChange={setQuery}
                    placeholder="Search blocks — try “form”, “timer” or “price”"
                    aria-label="Search blocks"
                    autoFocus
                />

                <div ref={gridRef} className="max-h-[60vh] overflow-y-auto pr-1" role="listbox" aria-label="Blocks">
                    {flat.length === 0 ? (
                        <p className="py-10 text-center text-[length:var(--text-sm)] text-[var(--color-text-muted)]">
                            No block matches “{query}”.
                        </p>
                    ) : (
                        groups.map((group) => (
                            <section key={group.key} className="mb-5 last:mb-0">
                                <h3 className="mb-2 text-[length:var(--text-2xs)] font-semibold tracking-wide text-[var(--color-text-subtle)] uppercase">
                                    {group.label}
                                </h3>
                                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    {group.types.map((type) => {
                                        index += 1;
                                        const position = index;
                                        const Icon = ICON[type];
                                        const selected = position === active;

                                        return (
                                            <button
                                                key={`${group.key}-${type}`}
                                                type="button"
                                                role="option"
                                                aria-selected={selected}
                                                data-picker-index={position}
                                                onMouseEnter={() => setActive(position)}
                                                onClick={() => pick(type)}
                                                className={cn(
                                                    'group flex items-start gap-3 rounded-lg border p-3 text-left transition-colors',
                                                    'border-[var(--color-border)] bg-[var(--color-surface)]',
                                                    'hover:border-[var(--color-primary)]',
                                                    'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-ring)]',
                                                    selected &&
                                                        'border-[var(--color-primary)] bg-[color-mix(in_srgb,var(--color-primary)_6%,transparent)]',
                                                )}
                                            >
                                                <span
                                                    className={cn(
                                                        'flex h-9 w-9 shrink-0 items-center justify-center rounded-md',
                                                        'bg-[var(--color-surface-sunken)] text-[var(--color-text-muted)]',
                                                        'group-hover:text-[var(--color-text-brand)]',
                                                        selected && 'text-[var(--color-text-brand)]',
                                                    )}
                                                >
                                                    <Icon className="h-4.5 w-4.5" aria-hidden />
                                                </span>
                                                <span className="min-w-0">
                                                    <span className="flex items-center gap-1.5 truncate text-[length:var(--text-sm)] font-medium text-[var(--color-text)]">
                                                        {LABEL(type)}
                                                        {COMING_SOON.has(type) && (
                                                            <span className="rounded bg-[var(--color-surface-sunken)] px-1.5 text-[length:var(--text-2xs)] font-medium text-[var(--color-text-muted)]">
                                                                Coming soon
                                                            </span>
                                                        )}
                                                    </span>
                                                    <span className="block text-[length:var(--text-2xs)] leading-snug text-[var(--color-text-muted)]">
                                                        {DESCRIPTION[type]}
                                                    </span>
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                            </section>
                        ))
                    )}
                </div>
            </div>
        </Modal>
    );
}
