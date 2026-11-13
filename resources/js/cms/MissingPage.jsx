/** Shown when a page's component is missing, e.g. a plugin page whose script failed to load. */
export default function MissingPage() {
    return (
        <div style={{ padding: '48px 24px', textAlign: 'center', fontFamily: 'system-ui, sans-serif' }}>
            <h1 style={{ fontSize: 24, marginBottom: 8 }}>This screen couldn’t be loaded</h1>
            <p style={{ color: '#5b6478' }}>The plugin or theme that provides it may be disabled or broken. Check Admin → Plugins.</p>
        </div>
    );
}
