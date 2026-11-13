import { usePage } from '@inertiajs/react';

/** Shown to an admin who is previewing a theme. Only they see it. */
export default function PreviewBar() {
    const { theme } = usePage().props;

    if (!theme?.previewing) {
        return null;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    return (
        <div style={{ position: 'sticky', top: 0, zIndex: 1000, display: 'flex', gap: 12, alignItems: 'center', justifyContent: 'center', flexWrap: 'wrap', padding: '10px 16px', background: '#0d1220', color: '#fff', font: '500 14px/1.4 system-ui, sans-serif' }}>
            <span>Previewing theme <strong>{theme.displayName}</strong>. Only you can see this.</span>
            <form method="POST" action={theme.exitPreviewUrl} style={{ margin: 0 }}>
                <input type="hidden" name="_token" value={csrf} />
                <button type="submit" style={{ border: 0, borderRadius: 999, padding: '6px 14px', background: '#fff', color: '#0d1220', font: 'inherit', cursor: 'pointer' }}>
                    Exit preview
                </button>
            </form>
        </div>
    );
}
