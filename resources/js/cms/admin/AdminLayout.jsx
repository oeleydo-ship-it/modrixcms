import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';

const ICONS = {
    dashboard: <path d="M3 13h8V3H3zM13 21h8V11h-8zM3 21h8v-6H3zM13 3v6h8V3z" />,
    page: <><path d="M6 3h9l4 4v14H6z" /><path d="M15 3v4h4" /></>,
    posts: <path d="M4 5h16M4 10h10M4 15h16M4 20h8" />,
    layers: <><path d="M3 7l9-4 9 4-9 4z" /><path d="m3 12 9 4 9-4M3 17l9 4 9-4" /></>,
    palette: <><path d="M12 3a9 9 0 1 0 0 18c1 0 1.5-.8 1.5-1.5 0-1.2-1-1.5-1-2.5S13.5 15 15 15h2a4 4 0 0 0 4-4c0-4.4-4-8-9-8z" /><circle cx="7.5" cy="11" r="1" /><circle cx="10.5" cy="7" r="1" /><circle cx="15" cy="7.5" r="1" /></>,
    plug: <path d="M9 3v4M15 3v4M7 7h10v4a5 5 0 0 1-10 0zM12 16v5" />,
    gear: <><circle cx="12" cy="12" r="3" /><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9 7 7M17 17l2.1 2.1M4.9 19.1 7 17M17 7l2.1-2.1" /></>,
    mail: <><rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" /></>,
    image: <><rect x="3" y="3" width="18" height="18" rx="4" /><path d="M8 15l3-3 2 2 3-4" /></>,
    user: <><circle cx="12" cy="8" r="4" /><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6" /></>,
};

/**
 * The admin shell: sidebar (core + plugin screens), title bar, safe-mode warning and
 * the "saved" message. Plugin admin pages use it too: ModrixCMS.ui.AdminLayout.
 */
export default function AdminLayout({ title, subtitle, actions, children }) {
    const { nav, site, flash, cms } = usePage().props;
    const [menuOpen, setMenuOpen] = useState(false);

    return (
        <div className="shell">
            <Head title={`${title} · ${site.name} Admin`} />
            <aside className={`side${menuOpen ? ' open' : ''}`}>
                <Link className="side-brand" href={route('admin.dashboard')}>
                    {site.logo ? <img src={site.logo} alt={site.name} /> : <><img src="/favicon.svg" alt="" /> {site.name}</>}
                </Link>

                {(nav ?? []).map((group) => (
                    <div key={group.label}>
                        <div className="side-label">{group.label}</div>
                        {group.items.map((item) => (
                            <Link key={item.url} href={item.url} className={item.active ? 'on' : undefined}>
                                <svg viewBox="0 0 24 24">{ICONS[item.icon] ?? ICONS.plug}</svg>{item.title}
                            </Link>
                        ))}
                    </div>
                ))}

                <div className="side-foot">
                    <a href={route('home')} target="_blank" rel="noopener"><svg viewBox="0 0 24 24"><path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6" /></svg>View site</a>
                    <button type="button" onClick={() => router.post(route('admin.logout'))}>
                        <svg viewBox="0 0 24 24"><path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10" /></svg>Sign out
                    </button>
                </div>
            </aside>

            <div className="main">
                <div className="topbar">
                    <div>
                        <button className="btn btn-light btn-sm menu-toggle" type="button" onClick={() => setMenuOpen(!menuOpen)}>☰ Menu</button>
                        <h1>{title}</h1>
                        {subtitle ? <p>{subtitle}</p> : null}
                    </div>
                    <div className="actions">{actions}</div>
                </div>

                {cms?.safeMode ? (
                    <div className="flash err" role="alert">
                        <strong>Safe mode is on.</strong> No plugins are loaded and the site uses the default theme. Turn it off with <code>php artisan cms:safe-mode off</code> (or remove <code>CMS_SAFE_MODE</code> from .env).
                    </div>
                ) : null}

                {flash?.status ? <div className="flash ok" role="status">{flash.status}</div> : null}

                {children}
            </div>
        </div>
    );
}
