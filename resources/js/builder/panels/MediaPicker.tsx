/**
 * Image upload for the builder.
 *
 * Every place a block needs an image — an image block, a logo, a video poster,
 * a testimonial photo, a gallery, a section background — uses these instead of
 * a URL box. Uploading is the primary path: click or drop a file and it goes
 * into the workspace's media library, where it can be picked again from any
 * page. A pasted link is still possible behind "Use a link instead", for
 * images that already live somewhere else.
 */

import { useCallback, useEffect, useId, useRef, useState, type DragEvent } from 'react';
import { FileIcon, ImagePlus, Link2, Trash2, Upload, X } from 'lucide-react';
import { Button } from '@shared/ui/Button';
import { Input } from '@shared/ui/Input';
import { Modal } from '@shared/ui/Modal';
import { Spinner } from '@shared/ui/Spinner';
import { useToast } from '@shared/ui/Toast';
import { cn } from '@shared/lib/cn';
import { isOverriddenAt, valueAt, withBreakpoint } from '@renderer';
import { deleteMedia, listMedia, uploadMedia, type MediaItem } from '../api';
import { ResponsiveHeader, type ControlContext } from './controls';

const IMAGE_ACCEPT = 'image/jpeg,image/png,image/gif,image/webp,image/avif';
const VIDEO_ACCEPT = 'video/mp4,video/webm,video/quicktime';
const FILE_ACCEPT = [
    'image/*',
    'video/*',
    'audio/*',
    'application/pdf',
    'text/plain',
    'text/csv',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
].join(',');

function filenameFromUrl(url: string): string {
    try {
        const parsed = new URL(url);
        const last = parsed.pathname.split('/').filter(Boolean).pop();
        return decodeURIComponent(last || url);
    } catch {
        return decodeURIComponent(url.split('/').filter(Boolean).pop() || url);
    }
}

function isImageMime(value: string): boolean {
    return value.startsWith('image/');
}

function isMimeAllowed(accept: string, mimeType: string): boolean {
    const tokens = accept
        .split(',')
        .map((entry) => entry.trim().toLowerCase())
        .filter((entry) => entry.length > 0);

    if (tokens.length === 0 || tokens.includes('*/*')) {
        return true;
    }

    const lowered = mimeType.toLowerCase();
    const [type = ''] = lowered.split('/');

    for (const token of tokens) {
        if (token.includes('/')) {
            if (token.endsWith('/*')) {
                const prefix = token.slice(0, -1);
                if (type && lowered.startsWith(prefix)) {
                    return true;
                }
                continue;
            }

            if (lowered === token) {
                return true;
            }
            continue;
        }
    }

    return false;
}

function mimeMatchesMedia(item: MediaItem, accept?: string): boolean {
    return accept === undefined || isMimeAllowed(accept, item.mime_type);
}

function fileFiles(list: FileList | null, accept: string): File[] {
    return Array.from(list ?? []).filter((file) => isMimeAllowed(accept, file.type || ''));
}

function mediaFiles(list: FileList | null, accept: string): File[] {
    return fileFiles(list, accept);
}

function imageFiles(list: FileList | null): File[] {
    return fileFiles(list, IMAGE_ACCEPT);
}

/* -------------------------------------------------------------------------- */
/* Uploading                                                                   */
/* -------------------------------------------------------------------------- */

function useUploader() {
    const toast = useToast();
    const [progress, setProgress] = useState<number | null>(null);

    const upload = useCallback(
        async (files: File[]): Promise<MediaItem[]> => {
            const done: MediaItem[] = [];
            for (const [index, file] of files.entries()) {
                try {
                    setProgress(0);
                    const item = await uploadMedia(file, (fraction) =>
                        setProgress(Math.round(((index + fraction) / files.length) * 100)),
                    );
                    done.push(item);
                } catch (error) {
                    toast.error(`Could not upload ${file.name}`, error instanceof Error ? error.message : undefined);
                }
            }
            setProgress(null);
            return done;
        },
        [toast],
    );

    return { upload, progress, busy: progress !== null };
}

/* -------------------------------------------------------------------------- */
/* The library                                                                 */
/* -------------------------------------------------------------------------- */

export function MediaLibraryModal({
    open,
    onClose,
    onSelect,
    multiple = false,
    accept = IMAGE_ACCEPT,
}: {
    open: boolean;
    onClose: () => void;
    onSelect: (items: MediaItem[]) => void;
    multiple?: boolean;
    accept?: string;
}) {
    const toast = useToast();
    const { upload, progress, busy } = useUploader();
    const [items, setItems] = useState<MediaItem[] | null>(null);
    const [chosen, setChosen] = useState<string[]>([]);
    const [dragging, setDragging] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (!open) {
            return;
        }
        setChosen([]);
        setItems(null);
        listMedia()
            .then(setItems)
            .catch(() => {
                setItems([]);
                toast.error('Could not load your media library');
            });
    }, [open, toast]);

    const addFiles = async (files: File[]): Promise<void> => {
        if (files.length === 0) {
            return;
        }
        const uploaded = await upload(files);
        if (uploaded.length > 0) {
            setItems((current) => [...uploaded, ...(current ?? [])]);
            setChosen((current) => (multiple ? [...uploaded.map((item) => item.id), ...current] : [uploaded[0]!.id]));
        }
    };

    const toggle = (id: string): void => {
        setChosen((current) =>
            multiple ? (current.includes(id) ? current.filter((entry) => entry !== id) : [...current, id]) : [id],
        );
    };

    const remove = async (item: MediaItem): Promise<void> => {
        if (!window.confirm(`Delete ${item.name}? Pages already using it will show a broken media file.`)) {
            return;
        }
        try {
            await deleteMedia(item.id);
            setItems((current) => (current ?? []).filter((entry) => entry.id !== item.id));
            setChosen((current) => current.filter((entry) => entry !== item.id));
        } catch (error) {
            toast.error('Could not delete the media file', error instanceof Error ? error.message : undefined);
        }
    };

    const confirm = (): void => {
        const selected = (items ?? []).filter((item) => chosen.includes(item.id));
        if (selected.length > 0) {
            onSelect(selected);
            onClose();
        }
    };

    return (
        <Modal
            open={open}
            onClose={onClose}
            size="xl"
            title="Media library"
            description="Upload new media files or pick one you have used before."
            footer={
                <>
                    <Button variant="ghost" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button onClick={confirm} disabled={chosen.length === 0}>
                        {multiple && chosen.length > 1 ? `Use ${chosen.length} items` : 'Use item'}
                    </Button>
                </>
            }
        >
            <div
                onDragOver={(event) => {
                    event.preventDefault();
                    setDragging(true);
                }}
                onDragLeave={() => setDragging(false)}
                onDrop={(event: DragEvent) => {
                    event.preventDefault();
                    setDragging(false);
                    void addFiles(mediaFiles(event.dataTransfer.files, accept));
                }}
                className={cn(
                    'flex flex-col gap-4 rounded-lg',
                    dragging && 'outline-2 outline-dashed outline-[var(--color-primary)]',
                )}
            >
                <div className="flex items-center justify-between gap-3">
                    <p className="text-[length:var(--text-sm)] text-[var(--color-text-muted)]">
                        Drop files here, or
                    </p>
                    <Button size="sm" onClick={() => inputRef.current?.click()} loading={busy}>
                        <Upload aria-hidden size={14} />
                        Upload files
                    </Button>
                    <input
                        ref={inputRef}
                        type="file"
                        accept={accept}
                        multiple
                        hidden
                        onChange={(event) => {
                            void addFiles(mediaFiles(event.currentTarget.files, accept));
                            event.currentTarget.value = '';
                        }}
                    />
                </div>

                {progress !== null && (
                    <div className="h-1.5 overflow-hidden rounded-full bg-[var(--color-surface-sunken)]" role="progressbar" aria-valuenow={progress}>
                        <div className="h-full bg-[var(--color-primary)] transition-[width]" style={{ width: `${progress}%` }} />
                    </div>
                )}

                {items === null ? (
                    <div className="flex justify-center py-12">
                        <Spinner label="Loading media" />
                    </div>
                ) : items.length === 0 ? (
                    <p className="py-12 text-center text-[length:var(--text-sm)] text-[var(--color-text-muted)]">
                        No media yet. Upload one to get started.
                    </p>
                ) : (
                    <ul className="grid max-h-[55vh] grid-cols-2 gap-3 overflow-y-auto pr-1 sm:grid-cols-4">
                        {items
                            .filter((item) => mimeMatchesMedia(item, accept))
                            .map((item) => {
                            const selected = chosen.includes(item.id);
                            return (
                                <li key={item.id} className="group relative">
                                    <button
                                        type="button"
                                        onClick={() => toggle(item.id)}
                                        onDoubleClick={() => {
                                            if (!multiple) {
                                                onSelect([item]);
                                                onClose();
                                            }
                                        }}
                                        aria-pressed={selected}
                                        className={cn(
                                            'block w-full overflow-hidden rounded-md border-2 bg-[var(--color-surface-sunken)]',
                                            selected ? 'border-[var(--color-primary)]' : 'border-transparent hover:border-[var(--color-border-strong)]',
                                        )}
                                    >
                                        {isImageMime(item.mime_type) ? (
                                            <img
                                                src={item.url}
                                                alt={item.alt ?? item.name}
                                                loading="lazy"
                                                className="aspect-square w-full object-cover"
                                            />
                                        ) : (
                                            <div className="flex aspect-square w-full items-center justify-center bg-[var(--color-surface-sunken)]">
                                                <FileIcon aria-hidden className="text-[var(--color-text-subtle)]" size={32} />
                                            </div>
                                        )}
                                        <span className="block truncate px-2 py-1 text-left text-[length:var(--text-2xs)] text-[var(--color-text-muted)]">
                                            {item.name}
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => void remove(item)}
                                        aria-label={`Delete ${item.name}`}
                                        className="absolute top-1.5 right-1.5 hidden rounded-md bg-[var(--color-surface)] p-1 text-[var(--color-danger,#dc2626)] shadow group-hover:block focus-visible:block"
                                    >
                                        <Trash2 aria-hidden size={14} />
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>
        </Modal>
    );
}

/* -------------------------------------------------------------------------- */
/* One image                                                                   */
/* -------------------------------------------------------------------------- */

export function ImageField({
    label,
    value,
    onChange,
    hint,
}: {
    label: string;
    value: string;
    onChange: (url: string) => void;
    hint?: string;
}) {
    const id = useId();
    const { upload, progress, busy } = useUploader();
    const [libraryOpen, setLibraryOpen] = useState(false);
    const [linkMode, setLinkMode] = useState(false);
    const [dragging, setDragging] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);

    const addFiles = async (files: File[]): Promise<void> => {
        const [uploaded] = await upload(files.slice(0, 1));
        if (uploaded) {
            onChange(uploaded.url);
        }
    };

    return (
        <div className="flex flex-col gap-1.5">
            <span id={id} className="text-[length:var(--text-xs)] font-medium text-[var(--color-text)]">
                {label}
            </span>

            {value ? (
                <div className="group relative overflow-hidden rounded-md border border-[var(--color-border)] bg-[var(--color-surface-sunken)]">
                    <img src={value} alt="" className="h-28 w-full object-cover" />
                    <div className="absolute inset-x-0 bottom-0 flex gap-1 bg-gradient-to-t from-black/60 to-transparent p-1.5">
                        <Button size="xs" variant="secondary" onClick={() => inputRef.current?.click()} loading={busy}>
                            <Upload aria-hidden size={12} />
                            Replace
                        </Button>
                        <Button size="xs" variant="secondary" onClick={() => setLibraryOpen(true)}>
                            Library
                        </Button>
                        <Button size="xs" variant="secondary" iconOnly aria-label={`Remove ${label.toLowerCase()}`} onClick={() => onChange('')}>
                            <X aria-hidden size={12} />
                        </Button>
                    </div>
                </div>
            ) : (
                <div
                    role="button"
                    tabIndex={0}
                    aria-labelledby={id}
                    onClick={() => inputRef.current?.click()}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            inputRef.current?.click();
                        }
                    }}
                    onDragOver={(event) => {
                        event.preventDefault();
                        setDragging(true);
                    }}
                    onDragLeave={() => setDragging(false)}
                    onDrop={(event) => {
                            event.preventDefault();
                            setDragging(false);
                            void addFiles(imageFiles(event.dataTransfer.files));
                        }}
                    className={cn(
                        'flex cursor-pointer flex-col items-center justify-center gap-1 rounded-md border border-dashed px-3 py-5 text-center',
                        'border-[var(--color-border-strong)] bg-[var(--color-surface)] hover:border-[var(--color-primary)]',
                        'focus-visible:outline-2 focus-visible:outline-[var(--color-ring)]',
                        dragging && 'border-[var(--color-primary)] bg-[color-mix(in_srgb,var(--color-primary)_6%,transparent)]',
                    )}
                >
                    {busy ? (
                        <>
                            <Spinner label="Uploading" />
                            <span className="text-[length:var(--text-2xs)] text-[var(--color-text-muted)]">{progress}%</span>
                        </>
                    ) : (
                        <>
                            <ImagePlus aria-hidden size={20} className="text-[var(--color-text-subtle)]" />
                            <span className="text-[length:var(--text-sm)] font-medium text-[var(--color-text)]">Upload an image</span>
                            <span className="text-[length:var(--text-2xs)] text-[var(--color-text-muted)]">
                                Click or drop · JPG, PNG, GIF, WebP · up to 10 MB
                            </span>
                        </>
                    )}
                </div>
            )}

            <input
                ref={inputRef}
                type="file"
                accept={IMAGE_ACCEPT}
                hidden
                onChange={(event) => {
                    void addFiles(imageFiles(event.currentTarget.files));
                    event.currentTarget.value = '';
                }}
            />

            <div className="flex items-center justify-between gap-2">
                {!value && (
                    <button
                        type="button"
                        onClick={() => setLibraryOpen(true)}
                        className="text-[length:var(--text-xs)] font-medium text-[var(--color-text-brand)] hover:underline"
                    >
                        Choose from library
                    </button>
                )}
                <button
                    type="button"
                    onClick={() => setLinkMode((current) => !current)}
                    className="ml-auto inline-flex items-center gap-1 text-[length:var(--text-xs)] text-[var(--color-text-muted)] hover:text-[var(--color-text)]"
                >
                    <Link2 aria-hidden size={12} />
                    {linkMode ? 'Hide link' : 'Use a link instead'}
                </button>
            </div>

            {linkMode && (
                <Input
                    size="sm"
                    aria-label={`${label} link`}
                    placeholder="https://example.com/image.jpg"
                    value={value}
                    onChange={(event) => onChange(event.currentTarget.value)}
                />
            )}

            {hint && <span className="text-[length:var(--text-2xs)] text-[var(--color-text-muted)]">{hint}</span>}

            <MediaLibraryModal
                open={libraryOpen}
                onClose={() => setLibraryOpen(false)}
                accept={IMAGE_ACCEPT}
                onSelect={([item]) => item && onChange(item.url)}
            />
        </div>
    );
}

/* -------------------------------------------------------------------------- */
/* File field                                                                  */
/* -------------------------------------------------------------------------- */

export function FileField({
    label,
    value,
    onChange,
    hint,
    accept = FILE_ACCEPT,
}: {
    label: string;
    value: string;
    onChange: (url: string) => void;
    hint?: string;
    accept?: string;
}) {
    const id = useId();
    const { upload, progress, busy } = useUploader();
    const [libraryOpen, setLibraryOpen] = useState(false);
    const [linkMode, setLinkMode] = useState(false);
    const [dragging, setDragging] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);
    const fileDisplayName = value ? filenameFromUrl(value) : 'No file selected';

    // Callers already filter through mediaFiles(), so the list is uploaded as-is.
    const addFiles = async (files: File[]): Promise<void> => {
        const [uploaded] = await upload(files);
        if (uploaded) {
            onChange(uploaded.url);
        }
    };

    return (
        <div className="flex flex-col gap-1.5">
            <span id={id} className="text-[length:var(--text-xs)] font-medium text-[var(--color-text)]">
                {label}
            </span>

            {value ? (
                <div className="group relative overflow-hidden rounded-md border border-[var(--color-border)] bg-[var(--color-surface-sunken)]">
                    <div className="flex items-center gap-2 p-2">
                        <FileIcon aria-hidden size={16} className="shrink-0 text-[var(--color-text-subtle)]" />
                        <div className="min-w-0">
                            <p className="truncate text-[length:var(--text-sm)]">{fileDisplayName}</p>
                            <p className="truncate text-[length:var(--text-2xs)] text-[var(--color-text-muted)]">
                                Stored media item
                            </p>
                        </div>
                    </div>
                    <div className="absolute right-1.5 top-1.5 flex gap-1">
                        <Button size="xs" variant="secondary" onClick={() => inputRef.current?.click()} loading={busy}>
                            <Upload aria-hidden size={12} />
                            Replace
                        </Button>
                        <Button size="xs" variant="secondary" iconOnly aria-label={`Remove ${label.toLowerCase()}`} onClick={() => onChange('')}>
                            <X aria-hidden size={12} />
                        </Button>
                    </div>
                </div>
            ) : (
                <div
                    role="button"
                    tabIndex={0}
                    aria-labelledby={id}
                    onClick={() => inputRef.current?.click()}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            inputRef.current?.click();
                        }
                    }}
                    onDragOver={(event) => {
                        event.preventDefault();
                        setDragging(true);
                    }}
                    onDragLeave={() => setDragging(false)}
                    onDrop={(event) => {
                        event.preventDefault();
                        setDragging(false);
                        void addFiles(mediaFiles(event.dataTransfer.files, accept));
                    }}
                    className={cn(
                        'flex cursor-pointer flex-col items-center justify-center gap-1 rounded-md border border-dashed px-3 py-5 text-center',
                        'border-[var(--color-border-strong)] bg-[var(--color-surface)] hover:border-[var(--color-primary)]',
                        'focus-visible:outline-2 focus-visible:outline-[var(--color-ring)]',
                        dragging && 'border-[var(--color-primary)] bg-[color-mix(in_srgb,var(--color-primary)_6%,transparent)]',
                    )}
                >
                    {busy ? (
                        <>
                            <Spinner label="Uploading" />
                            <span className="text-[length:var(--text-2xs)] text-[var(--color-text-muted)]">{progress}%</span>
                        </>
                    ) : (
                        <>
                            <FileIcon aria-hidden size={20} className="text-[var(--color-text-subtle)]" />
                            <span className="text-[length:var(--text-sm)] font-medium text-[var(--color-text)]">Upload a file</span>
                            <span className="text-[length:var(--text-2xs)] text-[var(--color-text-muted)]">Drop a file or click to select</span>
                        </>
                    )}
                </div>
            )}

            <input
                ref={inputRef}
                type="file"
                accept={accept}
                hidden
                onChange={(event) => {
                    void addFiles(mediaFiles(event.currentTarget.files, accept));
                    event.currentTarget.value = '';
                }}
            />

            <div className="flex items-center justify-between gap-2">
                {!value && (
                    <button
                        type="button"
                        onClick={() => setLibraryOpen(true)}
                        className="text-[length:var(--text-xs)] font-medium text-[var(--color-text-brand)] hover:underline"
                    >
                        Choose from library
                    </button>
                )}
                <button
                    type="button"
                    onClick={() => setLinkMode((current) => !current)}
                    className="ml-auto inline-flex items-center gap-1 text-[length:var(--text-xs)] text-[var(--color-text-muted)] hover:text-[var(--color-text)]"
                >
                    <Link2 aria-hidden size={12} />
                    {linkMode ? 'Hide link' : 'Use a link instead'}
                </button>
            </div>

            {linkMode && (
                <Input
                    size="sm"
                    aria-label={`${label} link`}
                    placeholder="https://example.com/file.mp4"
                    value={value}
                    onChange={(event) => onChange(event.currentTarget.value)}
                />
            )}

            <MediaLibraryModal
                open={libraryOpen}
                onClose={() => setLibraryOpen(false)}
                accept={accept}
                onSelect={([item]) => {
                    if (item) {
                        onChange(item.url);
                    }
                }}
            />

            {hint && <span className="text-[length:var(--text-2xs)] text-[var(--color-text-muted)]">{hint}</span>}
        </div>
    );
}

/* -------------------------------------------------------------------------- */
/* A gallery's images                                                          */
/* -------------------------------------------------------------------------- */

interface GalleryImage {
    src: string;
    alt: string;
}

function toGalleryImages(value: unknown): GalleryImage[] {
    if (!Array.isArray(value)) {
        return [];
    }
    return value
        .map((entry) => {
            if (typeof entry === 'string') {
                return { src: entry, alt: '' };
            }
            if (typeof entry === 'object' && entry !== null) {
                const record = entry as Record<string, unknown>;
                return {
                    src: typeof record['src'] === 'string' ? record['src'] : '',
                    alt: typeof record['alt'] === 'string' ? record['alt'] : '',
                };
            }
            return { src: '', alt: '' };
        })
        .filter((image) => image.src !== '');
}

export function GalleryImagesField({
    images: raw,
    onChange,
}: {
    images: unknown;
    onChange: (images: GalleryImage[]) => void;
}) {
    const images = toGalleryImages(raw);
    const { upload, progress, busy } = useUploader();
    const [libraryOpen, setLibraryOpen] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);

    const append = (added: { url: string; alt?: string | null }[]): void => {
        onChange([...images, ...added.map((item) => ({ src: item.url, alt: item.alt ?? '' }))]);
    };

    const move = (index: number, delta: number): void => {
        const target = index + delta;
        if (target < 0 || target >= images.length) {
            return;
        }
        const next = [...images];
        const [moved] = next.splice(index, 1);
        next.splice(target, 0, moved!);
        onChange(next);
    };

    return (
        <div className="flex flex-col gap-2">
            <span className="text-[length:var(--text-xs)] font-medium text-[var(--color-text)]">
                Images ({images.length})
            </span>

            {images.length > 0 && (
                <ul className="flex flex-col gap-2">
                    {images.map((image, index) => (
                        <li key={`${image.src}-${index}`} className="flex items-center gap-2 rounded-md border border-[var(--color-border)] p-1.5">
                            <img src={image.src} alt="" className="h-10 w-10 shrink-0 rounded object-cover" />
                            <input
                                aria-label={`Alt text for image ${index + 1}`}
                                placeholder="Alt text"
                                value={image.alt}
                                onChange={(event) => {
                                    const next = [...images];
                                    next[index] = { ...image, alt: event.currentTarget.value };
                                    onChange(next);
                                }}
                                className="min-w-0 flex-1 rounded border border-[var(--color-border)] bg-[var(--color-surface)] px-1.5 py-1 text-[length:var(--text-xs)]"
                            />
                            <span className="flex shrink-0 flex-col">
                                <button type="button" aria-label="Move up" onClick={() => move(index, -1)} disabled={index === 0} className="px-1 text-[10px] leading-none disabled:opacity-30">
                                    ▲
                                </button>
                                <button type="button" aria-label="Move down" onClick={() => move(index, 1)} disabled={index === images.length - 1} className="px-1 text-[10px] leading-none disabled:opacity-30">
                                    ▼
                                </button>
                            </span>
                            <button
                                type="button"
                                aria-label={`Remove image ${index + 1}`}
                                onClick={() => onChange(images.filter((_, position) => position !== index))}
                                className="shrink-0 rounded p-1 text-[var(--color-text-muted)] hover:text-[var(--color-danger,#dc2626)]"
                            >
                                <Trash2 aria-hidden size={14} />
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            {progress !== null && (
                <div className="h-1.5 overflow-hidden rounded-full bg-[var(--color-surface-sunken)]" role="progressbar" aria-valuenow={progress}>
                    <div className="h-full bg-[var(--color-primary)]" style={{ width: `${progress}%` }} />
                </div>
            )}

            <div className="flex gap-2">
                <Button size="sm" onClick={() => inputRef.current?.click()} loading={busy}>
                    <Upload aria-hidden size={14} />
                    Upload images
                </Button>
                <Button size="sm" variant="secondary" onClick={() => setLibraryOpen(true)}>
                    From library
                </Button>
            </div>

            <input
                ref={inputRef}
                type="file"
                accept={IMAGE_ACCEPT}
                multiple
                hidden
                onChange={async (event) => {
                    const files = imageFiles(event.currentTarget.files);
                    event.currentTarget.value = '';
                    append(await upload(files));
                }}
            />

            <MediaLibraryModal open={libraryOpen} onClose={() => setLibraryOpen(false)} multiple onSelect={append} />
        </div>
    );
}

/* -------------------------------------------------------------------------- */
/* A responsive background image                                               */
/* -------------------------------------------------------------------------- */

/** The Background group's image, bound to the active breakpoint like every style control. */
/**
 * The file behind a section's background video. Upload one, pick it from the
 * library or paste a link; a background video is muted and looped, so short
 * and small beats long and sharp.
 */
export function BackgroundVideoControl({
    id,
    label,
    context,
}: {
    id: string;
    label: string;
    context: ControlContext;
}) {
    const raw = valueAt(context.value, context.breakpoint);
    const current = typeof raw === 'string' ? raw.replace(/^url\(["']?|["']?\)$/g, '') : '';
    const overridden = isOverriddenAt(context.value, context.breakpoint);

    return (
        <div className="flex flex-col gap-1">
            <ResponsiveHeader
                id={id}
                label={label}
                breakpoint={context.breakpoint}
                overridden={overridden}
                inherited={!overridden && current !== ''}
                onReset={() => context.onChange(withBreakpoint(context.value, context.breakpoint, null))}
            />
            <FileField
                label=""
                accept={VIDEO_ACCEPT}
                hint="MP4 or WebM. Keep it under about 5 MB: it loads on every visit."
                value={current}
                onChange={(url) => context.onChange(withBreakpoint(context.value, context.breakpoint, url || null))}
            />
        </div>
    );
}

export function BackgroundImageControl({
    id,
    label,
    context,
}: {
    id: string;
    label: string;
    context: ControlContext;
}) {
    const raw = valueAt(context.value, context.breakpoint);
    const current = typeof raw === 'string' ? raw.replace(/^url\(["']?|["']?\)$/g, '') : '';
    const overridden = isOverriddenAt(context.value, context.breakpoint);

    return (
        <div className="flex flex-col gap-1">
            <ResponsiveHeader
                id={id}
                label={label}
                breakpoint={context.breakpoint}
                overridden={overridden}
                inherited={!overridden && current !== ''}
                onReset={() => context.onChange(withBreakpoint(context.value, context.breakpoint, null))}
            />
            <ImageField
                label=""
                value={current}
                onChange={(url) => context.onChange(withBreakpoint(context.value, context.breakpoint, url || null))}
            />
        </div>
    );
}
