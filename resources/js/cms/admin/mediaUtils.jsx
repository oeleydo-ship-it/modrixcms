/** Small helpers shared by the Media screen and the media picker. */

export function formatBytes(bytes) {
    if (!bytes) {
        return '0 KB';
    }

    if (bytes < 1024 * 1024) {
        return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    }

    return `${(bytes / 1024 / 1024).toFixed(bytes < 10 * 1024 * 1024 ? 1 : 0)} MB`;
}

export const KIND_TABS = [
    { value: '', label: 'All', count: 'all' },
    { value: 'image', label: 'Images', count: 'image' },
    { value: 'video', label: 'Videos', count: 'video' },
    { value: 'file', label: 'Files', count: 'file' },
];

/** The thumbnail for an item: the picture itself, or a badge with the file's extension. */
export function MediaThumb({ item }) {
    if (item.kind === 'image') {
        return <img src={item.url} alt={item.alt ?? ''} loading="lazy" />;
    }

    if (item.kind === 'video') {
        return <video src={`${item.url}#t=0.1`} preload="metadata" muted playsInline />;
    }

    return <span className="media-ext">{item.extension || 'file'}</span>;
}
