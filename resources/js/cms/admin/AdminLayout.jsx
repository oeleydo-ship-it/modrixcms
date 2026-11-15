import { useEffect, useMemo, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AiJobNotifications from './AiJobNotifications';

const ICONS = {
    dashboard: (
        <>
            <path d="M3 10.5L12 3l9 7.5" />
            <path d="M5 9.5V20h14V9.5" />
            <path d="M9.5 20v-6h5v6" />
        </>
    ),
    page: (
        <>
            <rect x="4" y="3" width="16" height="18" rx="2.5" />
            <path d="M8 8h8M8 12h8M8 16h5" />
        </>
    ),
    sparkles: (
        <>
            <path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z" />
            <path d="M19 16l.8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8z" />
        </>
    ),
    posts: (
        <>
            <path d="M4 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z" />
            <path d="M8 9h8M8 13h8M8 17h5" />
        </>
    ),
    layers: (
        <>
            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
            <circle cx="7" cy="7" r="1.25" />
        </>
    ),
    palette: (
        <>
            <path d="M3 9l1.5-5h15L21 9" />
            <path d="M3 9c0 1.7 1.3 3 3 3s3-1.3 3-3c0 1.7 1.3 3 3 3s3-1.3 3-3c0 1.7 1.3 3 3 3s3-1.3 3-3" />
            <path d="M5 12v8h14v-8" />
        </>
    ),
    plug: (
        <>
            <rect x="3" y="3" width="7" height="7" rx="1.5" />
            <rect x="14" y="3" width="7" height="7" rx="1.5" />
            <rect x="3" y="14" width="7" height="7" rx="1.5" />
            <path d="M17.5 14v7M14 17.5h7" />
        </>
    ),
    gear: (
        <>
            <circle cx="12" cy="12" r="3" />
            <path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M4.9 19.1l1.8-1.8M17.3 6.7l1.8-1.8" />
        </>
    ),
    sliders: (
        <>
            <path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3" />
            <path d="M1 14h6M9 8h6M17 16h6" />
        </>
    ),
    mail: (
        <>
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <path d="m3 7 9 6 9-6" />
        </>
    ),
    image: (
        <>
            <rect x="3" y="3" width="18" height="18" rx="3" />
            <circle cx="8.5" cy="8.5" r="1.5" />
            <path d="M21 15l-5-5L5 21" />
        </>
    ),
    user: (
        <>
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6" />
        </>
    ),
};

export const THEME_PRESETS = [
    {
        name: 'Reference Default',
        swatch: '#0a0a0a',
        values: {
            admin_sidebar_bg: '#0a0a0a',
            admin_sidebar_text: '#d4d4d4',
            admin_sidebar_active_bg: '#2b2b2b',
            admin_sidebar_active_text: '#ffffff',
            admin_sidebar_width: '240',
            admin_canvas_bg: '#ffffff',
            admin_surface_bg: '#ffffff',
            admin_surface_muted: '#f7f7f7',
            admin_primary_color: '#1f1f1f',
            admin_accent_color: '#5b3df5',
            admin_brand_color: '#22a559',
            admin_text_color: '#1a1a1a',
            admin_muted_color: '#616161',
            admin_border_color: '#e3e3e3',
            admin_font_family: 'Inter',
            admin_heading_font: 'Inter',
            admin_font_size: '13px',
            admin_heading_weight: '600',
            admin_letter_spacing: '-0.01em',
            admin_spacing: 'default',
            admin_content_padding: '24px',
            admin_radius: '12px',
            admin_button_radius: '999px',
            admin_frame_inset: '6px',
            admin_avatar_color: '#5b3df5',
        },
    },
    {
        name: 'Polaris Tinted Gray',
        swatch: '#1a1a1a',
        values: {
            admin_sidebar_bg: '#111111',
            admin_sidebar_text: '#d4d4d4',
            admin_sidebar_active_bg: '#2e2e2e',
            admin_sidebar_active_text: '#ffffff',
            admin_canvas_bg: '#f1f2f4',
            admin_surface_bg: '#ffffff',
            admin_surface_muted: '#f7f8fa',
            admin_primary_color: '#1f1f1f',
            admin_accent_color: '#008060',
            admin_brand_color: '#22a559',
            admin_text_color: '#1a1a1a',
            admin_muted_color: '#616161',
            admin_border_color: '#dfe3e8',
        },
    },
    {
        name: 'Modrix Indigo',
        swatch: '#5b3df5',
        values: {
            admin_sidebar_bg: '#0d1220',
            admin_sidebar_text: '#c4cada',
            admin_sidebar_active_bg: '#262d47',
            admin_sidebar_active_text: '#ffffff',
            admin_canvas_bg: '#ffffff',
            admin_surface_bg: '#ffffff',
            admin_surface_muted: '#f5f6f9',
            admin_primary_color: '#5b3df5',
            admin_accent_color: '#5b3df5',
            admin_brand_color: '#5b3df5',
            admin_text_color: '#0d1220',
            admin_muted_color: '#5b6478',
            admin_border_color: '#e4e6ee',
        },
    },
    {
        name: 'Emerald Commerce',
        swatch: '#008060',
        values: {
            admin_sidebar_bg: '#071410',
            admin_sidebar_text: '#cbe3db',
            admin_sidebar_active_bg: '#13332a',
            admin_sidebar_active_text: '#ffffff',
            admin_canvas_bg: '#ffffff',
            admin_surface_bg: '#ffffff',
            admin_surface_muted: '#f4f9f7',
            admin_primary_color: '#008060',
            admin_accent_color: '#008060',
            admin_brand_color: '#22a559',
            admin_text_color: '#111815',
            admin_muted_color: '#52665e',
            admin_border_color: '#dce8e3',
        },
    },
];

const FONT_MAP = {
    Inter: '"Inter", -apple-system, BlinkMacSystemFont, "San Francisco", "Segoe UI", Roboto, sans-serif',
    'System UI': '-apple-system, BlinkMacSystemFont, "San Francisco", "Segoe UI", Roboto, sans-serif',
    'Plus Jakarta Sans': '"Plus Jakarta Sans", "Inter", system-ui, sans-serif',
    'DM Sans': '"DM Sans", "Inter", system-ui, sans-serif',
    'Bricolage Grotesque': '"Bricolage Grotesque", "Inter", system-ui, sans-serif',
    'JetBrains Mono': '"JetBrains Mono", ui-monospace, monospace',
};

const SPACING_MAP = {
    compact: { nav_py: '4px', card_pad: '16px', gap: '12px' },
    default: { nav_py: '6px', card_pad: '20px', gap: '16px' },
    comfortable: { nav_py: '8px', card_pad: '24px', gap: '20px' },
    spacious: { nav_py: '10px', card_pad: '28px', gap: '24px' },
};

export function applyThemeVariables(theme) {
    if (typeof document === 'undefined' || !theme) {
        return;
    }

    const root = document.documentElement;
    const density = SPACING_MAP[theme.admin_spacing] ?? SPACING_MAP.default;
    const width = String(theme.admin_sidebar_width || '240').replace(/[^0-9]/g, '') || '240';

    const vars = {
        '--admin-sidebar-bg': theme.admin_sidebar_bg,
        '--admin-sidebar-text': theme.admin_sidebar_text,
        '--admin-sidebar-active-bg': theme.admin_sidebar_active_bg,
        '--admin-sidebar-active-text': theme.admin_sidebar_active_text,
        '--admin-sidebar-width': `${width}px`,
        '--admin-canvas-bg': theme.admin_canvas_bg,
        '--admin-surface': theme.admin_surface_bg,
        '--admin-surface-muted': theme.admin_surface_muted,
        '--admin-primary': theme.admin_primary_color,
        '--admin-accent': theme.admin_accent_color,
        '--admin-brand-icon': theme.admin_brand_color,
        '--ink': theme.admin_text_color,
        '--muted': theme.admin_muted_color,
        '--line': theme.admin_border_color,
        '--bg': theme.admin_canvas_bg,
        '--white': theme.admin_surface_bg,
        '--brand': theme.admin_accent_color,
        '--font': FONT_MAP[theme.admin_font_family] ?? FONT_MAP.Inter,
        '--display': FONT_MAP[theme.admin_heading_font] ?? FONT_MAP.Inter,
        '--admin-font-size': theme.admin_font_size,
        '--admin-heading-weight': theme.admin_heading_weight,
        '--admin-letter-spacing': theme.admin_letter_spacing,
        '--admin-content-pad': theme.admin_content_padding,
        '--radius': theme.admin_radius,
        '--admin-btn-radius': theme.admin_button_radius,
        '--admin-frame-inset': theme.admin_frame_inset,
        '--admin-nav-py': density.nav_py,
        '--admin-card-pad': density.card_pad,
        '--admin-gap': density.gap,
    };

    Object.entries(vars).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            root.style.setProperty(key, String(value));
        }
    });
}

/**
 * Reference-style illustration (matching the document + teal tray empty state in the reference).
 */
export function ReferenceEmptyArt() {
    return (
        <div className="polaris-empty-art" aria-hidden="true">
            <svg viewBox="0 0 160 160" fill="none">
                <circle cx="80" cy="84" r="54" fill="#f1f2f4" />
                <g filter="url(#docShadow)">
                    <path d="M48 30h48l16 16v68H48V30z" fill="#ffffff" />
                    <path d="M96 30v16h16L96 30z" fill="#dfe3e8" />
                </g>
                <rect x="57" y="40" width="16" height="4" rx="2" fill="#2563eb" />
                <rect x="57" y="50" width="22" height="22" rx="4" fill="#e0f2fe" />
                <path d="M61 61h14c0 4-3.1 7-7 7s-7-3-7-7z" fill="#2b8a82" />
                <rect x="85" y="55" width="18" height="3.5" rx="1.75" fill="#d4d4d8" />
                <rect x="85" y="62" width="12" height="3.5" rx="1.75" fill="#e4e4e7" />
                <rect x="57" y="78" width="22" height="22" rx="4" fill="#fae3dc" />
                <path d="M64 83h8l-1 13h-6l-1-13z" fill="#e06344" />
                <rect x="85" y="83" width="18" height="3.5" rx="1.75" fill="#d4d4d8" />
                <rect x="85" y="90" width="14" height="3.5" rx="1.75" fill="#e4e4e7" />
                <path
                    d="M31 103h34l5 9h20l5-9h34c-5.5 21.5-25.2 35-49 35s-43.5-13.5-49-35z"
                    fill="#349890"
                />
                <path d="M38 103h27l5 9h20l5-9h27l-5-9H43l-5 9z" fill="#26736d" opacity="0.85" />
                <defs>
                    <filter id="docShadow" x="42" y="26" width="76" height="96" filterUnits="userSpaceOnUse">
                        <feDropShadow dx="0" dy="2" stdDeviation="3" floodColor="#000000" floodOpacity="0.08" />
                    </filter>
                </defs>
            </svg>
        </div>
    );
}

/**
 * The admin shell: left dark sidebar (with brand, collapse button, Ctrl+K search, nav groups,
 * pinned Settings and store profile pill), rounded white main canvas, floating Sidekick bar,
 * and live Theme Customizer drawer.
 */
export default function AdminLayout({ title, subtitle, actions, children }) {
    const { nav, site, flash, cms, adminTheme } = usePage().props;
    const [menuOpen, setMenuOpen] = useState(false);
    const [collapsed, setCollapsed] = useState(false);
    const [searchOpen, setSearchOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [storeMenuOpen, setStoreMenuOpen] = useState(false);
    const [customizerOpen, setCustomizerOpen] = useState(false);
    const [sidekickQuery, setSidekickQuery] = useState('');

    const themeDefaults = THEME_PRESETS[0].values;
    const initialTheme = useMemo(() => ({
        ...themeDefaults,
        admin_store_handle: '',
        admin_store_badge: '',
        admin_show_assistant: true,
        admin_assistant_label: 'Work with Sidekick',
        ...(adminTheme ?? {}),
    }), [adminTheme]);

    const customizerForm = useForm(initialTheme);

    useEffect(() => {
        applyThemeVariables(customizerForm.data);
    }, [customizerForm.data]);

    useEffect(() => {
        const onKeyDown = (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                setSearchOpen((open) => !open);
            } else if (e.key === 'Escape') {
                setSearchOpen(false);
                setStoreMenuOpen(false);
            }
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, []);

    const flatNavItems = useMemo(() => {
        const list = [];
        (nav ?? []).forEach((group) => {
            (group.items ?? []).forEach((item) => {
                list.push({ ...item, group: group.label });
            });
        });
        return list;
    }, [nav]);

    const activeItem = useMemo(
        () => flatNavItems.find((item) => item.active) ?? flatNavItems[0],
        [flatNavItems],
    );

    const filteredSearchItems = useMemo(() => {
        const q = searchQuery.trim().toLowerCase();
        if (!q) {
            return flatNavItems;
        }
        return flatNavItems.filter(
            (item) => item.title.toLowerCase().includes(q) || item.group.toLowerCase().includes(q),
        );
    }, [flatNavItems, searchQuery]);

    // The sidebar shows the site's own name unless a custom name is set.
    const storeHandle = customizerForm.data.admin_store_handle || site?.name || 'Admin';
    const storeBadge = customizerForm.data.admin_store_badge ?? '';
    const storeInitials = String(storeHandle).replace(/[^a-zA-Z0-9]/g, '').slice(0, 2).toLowerCase() || 'ad';

    const saveCustomization = (e) => {
        e?.preventDefault();
        customizerForm.put(route('admin.settings.appearance.update'), {
            preserveScroll: true,
            onSuccess: () => setCustomizerOpen(false),
        });
    };

    const resetCustomization = () => {
        router.post(route('admin.settings.appearance.reset'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                customizerForm.setData({
                    ...themeDefaults,
                    admin_store_handle: '',
                    admin_store_badge: '',
                    admin_show_assistant: true,
                    admin_assistant_label: 'Work with Sidekick',
                });
            },
        });
    };

    // Split groups into main groups vs Settings group for reference-style layout
    const mainGroups = (nav ?? []).filter((g) => g.label !== 'Settings');
    const settingsGroup = (nav ?? []).find((g) => g.label === 'Settings');
    const isSettingsActive = settingsGroup?.items?.some((item) => item.active) ?? false;

    return (
        <div className={`shell${collapsed ? ' is-collapsed' : ''}`}>
            <Head title={`${title} · ${site.name} Admin`} />

            <aside className={`side${menuOpen ? ' open' : ''}`}>
                {/* Top Brand Row + Sidebar Collapse Toggle */}
                <div className="side-top">
                    <Link className="side-brand" href={route('admin.dashboard')}>
                        {site.logo ? (
                            <img src={site.logo} alt={site.name} />
                        ) : (
                            <>
                                <span className="side-brand-bag" title={site.name}>
                                    <svg viewBox="0 0 24 24">
                                        <path d="M16 7V6a4 4 0 0 0-8 0v1H5L3.8 20.2A1.7 1.7 0 0 0 5.5 22h13a1.7 1.7 0 0 0 1.7-1.8L19 7h-3zm-6-1a2 2 0 0 1 4 0v1h-4V6zm2.4 11.2c-1.5 0-2.6-.7-2.8-1.8l1.4-.3c.1.6.7 1 1.4 1 .7 0 1.2-.3 1.2-.8 0-.5-.4-.7-1.5-1-1.5-.4-2.3-1-2.3-2.2 0-1.2 1-2.1 2.5-2.1 1.4 0 2.3.7 2.6 1.7l-1.4.3c-.2-.5-.6-.9-1.2-.9-.6 0-1.1.3-1.1.8 0 .5.4.7 1.5 1 1.5.4 2.3 1 2.3 2.2 0 1.3-1 2.1-2.6 2.1z" />
                                    </svg>
                                </span>
                                <span style={{ display: 'none' }}>{site.name}</span>
                            </>
                        )}
                    </Link>

                    <button
                        type="button"
                        className="side-collapse-btn"
                        onClick={() => setCollapsed(!collapsed)}
                        title={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                        aria-label="Toggle sidebar"
                    >
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="3" />
                            <path d="M15 3v18" />
                        </svg>
                    </button>
                </div>

                {/* Search Pill (Ctrl K) */}
                <button type="button" className="side-search" onClick={() => setSearchOpen(true)}>
                    <span className="side-search-left">
                        <svg viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                        <span>Search</span>
                    </span>
                    <span className="side-search-kbd">Ctrl K</span>
                </button>

                {/* Navigation Groups */}
                <div className="side-nav">
                    {mainGroups.map((group, groupIndex) => (
                        <div key={group.label || groupIndex} className="side-group">
                            {groupIndex > 0 && group.label ? (
                                <div className="side-label">
                                    <span>
                                        {group.label}
                                        <span className="side-label-chevron">›</span>
                                    </span>
                                </div>
                            ) : null}

                            {group.items.map((item) => {
                                const subItems =
                                    item.title === 'Pages'
                                        ? [
                                              { title: 'All pages', url: route('admin.pages.index') },
                                              { title: 'New block page', url: route('admin.pages.create') },
                                          ]
                                        : item.title === 'Blog posts'
                                          ? [
                                                { title: 'All posts', url: route('admin.posts.index') },
                                                { title: 'Write post', url: route('admin.posts.create') },
                                                { title: 'Categories', url: route('admin.categories.index') },
                                            ]
                                          : [];

                                return (
                                    <div key={item.url}>
                                        <Link
                                            href={item.url}
                                            className={`side-link${item.active ? ' on' : ''}`}
                                        >
                                            <svg className="nav-ic" viewBox="0 0 24 24">
                                                {ICONS[item.icon] ?? ICONS.plug}
                                            </svg>
                                            <span>{item.title}</span>
                                        </Link>

                                        {item.active && subItems.length > 0 ? (
                                            <div className="side-sub">
                                                {subItems.map((sub) => (
                                                    <Link
                                                        key={sub.title}
                                                        href={sub.url}
                                                        className="side-sub-link"
                                                    >
                                                        {sub.title}
                                                    </Link>
                                                ))}
                                            </div>
                                        ) : null}
                                    </div>
                                );
                            })}
                        </div>
                    ))}
                </div>

                {/* Pinned Bottom Area: Settings + Store Profile Bar */}
                <div className="side-foot">
                    {settingsGroup ? (
                        <div>
                            <Link
                                href={route('admin.settings.general')}
                                className={`side-link${isSettingsActive ? ' on' : ''}`}
                            >
                                <svg className="nav-ic" viewBox="0 0 24 24">
                                    {ICONS.gear}
                                </svg>
                                <span>Settings</span>
                            </Link>

                            <div className="side-sub">
                                {settingsGroup.items.map((item) => (
                                    <Link
                                        key={item.url}
                                        href={item.url}
                                        className={`side-sub-link${item.active ? ' on' : ''}`}
                                    >
                                        {item.title}
                                    </Link>
                                ))}
                            </div>
                        </div>
                    ) : null}

                    {/* Store Switcher & Quick Links Row (Matches `[ca] codearena [dev]` + Bell) */}
                    <div className="side-store-row">
                        {storeMenuOpen ? (
                            <div className="side-popover">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setStoreMenuOpen(false);
                                        setCustomizerOpen(true);
                                    }}
                                >
                                    <svg viewBox="0 0 24 24">{ICONS.sliders}</svg>
                                    Customize UI & theme
                                </button>
                                <Link href={route('admin.account')} onClick={() => setStoreMenuOpen(false)}>
                                    <svg viewBox="0 0 24 24">{ICONS.user}</svg>
                                    My account
                                </Link>
                                <div className="side-popover-divider" />
                                <button type="button" onClick={() => router.post(route('admin.logout'))}>
                                    <svg viewBox="0 0 24 24">
                                        <path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10" />
                                    </svg>
                                    Sign out
                                </button>
                            </div>
                        ) : null}

                        <button
                            type="button"
                            className="side-store-btn"
                            onClick={() => setStoreMenuOpen(!storeMenuOpen)}
                        >
                            <span
                                className="side-store-avatar"
                                style={{ background: customizerForm.data.admin_avatar_color || '#5b3df5' }}
                            >
                                {storeInitials}
                            </span>
                            <span className="side-store-name">{storeHandle}</span>
                            {storeBadge ? <span className="side-store-badge">{storeBadge}</span> : null}
                        </button>

                        <button
                            type="button"
                            className="side-bell-btn"
                            onClick={() => setCustomizerOpen(true)}
                            title="Customize Admin UI"
                            aria-label="Notifications and UI customizer"
                        >
                            <svg viewBox="0 0 24 24">
                                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" />
                                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                            </svg>
                        </button>
                    </div>

                    {/* Direct Sign out link (View site lives in the top bar) */}
                    <div style={{ display: 'flex', gap: 6, padding: '2px 4px 0', fontSize: 11.5 }}>
                        <button
                            type="button"
                            onClick={() => router.post(route('admin.logout'))}
                            style={{
                                background: 'none',
                                border: 0,
                                color: '#8a8a8a',
                                font: 'inherit',
                                cursor: 'pointer',
                                padding: '2px 4px',
                                marginLeft: 'auto',
                            }}
                        >
                            Sign out
                        </button>
                    </div>
                </div>
            </aside>

            {/* Rounded White Main Content Canvas */}
            <div className="main">
                <div className="topbar">
                    <div className="topbar-left">
                        <button
                            className="btn btn-light btn-sm menu-toggle"
                            type="button"
                            onClick={() => setMenuOpen(!menuOpen)}
                        >
                            ☰ Menu
                        </button>

                        <div className="topbar-title-row">
                            <span className="topbar-icon">
                                <svg viewBox="0 0 24 24">
                                    {ICONS[activeItem?.icon] ?? ICONS.dashboard}
                                </svg>
                            </span>
                            <h1>{title}</h1>
                        </div>

                        {subtitle ? <p className="topbar-subtitle">{subtitle}</p> : null}
                    </div>

                    <div className="actions">
                        {actions}
                        <a
                            className="btn btn-light btn-sm"
                            href={route('home')}
                            target="_blank"
                            rel="noopener"
                            title="Open your public site in a new tab"
                        >
                            View site
                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                                style={{ width: 13, height: 13, stroke: 'currentColor', strokeWidth: 1.8, fill: 'none' }}
                            >
                                <path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6" />
                            </svg>
                        </a>
                    </div>
                </div>

                {cms?.safeMode ? (
                    <div className="flash err" role="alert">
                        <strong>Safe mode is on.</strong> No plugins are loaded and the site uses the default theme. Turn it off with <code>php artisan cms:safe-mode off</code> (or remove <code>CMS_SAFE_MODE</code> from .env).
                    </div>
                ) : null}

                {flash?.status ? <div className="flash ok" role="status">{flash.status}</div> : null}

                {children}
                <AiJobNotifications />

                {/* Floating Bottom Assistant Bar (Work with Sidekick) */}
                {customizerForm.data.admin_show_assistant ? (
                    <form
                        className="sidekick-bar"
                        onSubmit={(e) => {
                            e.preventDefault();
                            if (sidekickQuery.trim()) {
                                setSearchQuery(sidekickQuery);
                                setSearchOpen(true);
                            } else {
                                setCustomizerOpen(true);
                            }
                        }}
                    >
                        <span className="sidekick-avatar" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <circle cx="9" cy="12" r="2.2" />
                                <circle cx="15" cy="12" r="2.2" />
                                <path d="M6.8 12H5M19 12h-1.8M11.2 12h1.6" />
                            </svg>
                        </span>
                        <input
                            type="text"
                            className="sidekick-input"
                            value={sidekickQuery}
                            onChange={(e) => setSidekickQuery(e.target.value)}
                            placeholder={customizerForm.data.admin_assistant_label || 'Work with Sidekick'}
                            aria-label="Work with Sidekick"
                        />
                        <div className="sidekick-tools">
                            <button
                                type="button"
                                className="sidekick-tool-btn"
                                onClick={() => setSearchOpen(true)}
                                title="Quick actions (Ctrl K)"
                            >
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 5v14M5 12h14" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                className="sidekick-tool-btn"
                                onClick={() => setSearchOpen(true)}
                                title="Voice / search"
                            >
                                <svg viewBox="0 0 24 24">
                                    <rect x="9" y="2" width="6" height="11" rx="3" />
                                    <path d="M5 10a7 7 0 0 0 14 0M12 17v4M8 21h8" />
                                </svg>
                            </button>
                            <span className="sidekick-divider" />
                            <button
                                type="button"
                                className="sidekick-tool-btn"
                                onClick={() => setCustomizerOpen((v) => !v)}
                                title="Toggle UI customizer panel"
                            >
                                <svg viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="18" height="18" rx="3" />
                                    <path d="M15 3v18" />
                                </svg>
                            </button>
                        </div>
                    </form>
                ) : null}
            </div>

            {/* Command Palette Modal (Ctrl K) */}
            {searchOpen ? (
                <div className="cmd-backdrop" onClick={() => setSearchOpen(false)}>
                    <div className="cmd-modal" onClick={(e) => e.stopPropagation()}>
                        <div className="cmd-input-row">
                            <svg viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-3.5-3.5" />
                            </svg>
                            <input
                                type="text"
                                autoFocus
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Jump to any admin screen or setting…"
                            />
                        </div>
                        <div className="cmd-list">
                            {filteredSearchItems.map((item) => (
                                <Link
                                    key={item.url}
                                    href={item.url}
                                    className="cmd-item"
                                    onClick={() => setSearchOpen(false)}
                                >
                                    <span>{item.title}</span>
                                    <span className="cmd-group">{item.group}</span>
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>
            ) : null}

            {/* Live Theme Customizer Drawer */}
            {customizerOpen ? (
                <>
                    <div className="customizer-backdrop" onClick={() => setCustomizerOpen(false)} />
                    <aside className="customizer-drawer" aria-label="Customize Admin Design">
                        <div className="customizer-head">
                            <div>
                                <h2>Customize Admin UI</h2>
                                <div style={{ fontSize: 11.5, color: '#616161' }}>
                                    Live preview · Sidebar, typography, spacing & colors
                                </div>
                            </div>
                            <button
                                type="button"
                                className="btn btn-light btn-sm"
                                onClick={() => setCustomizerOpen(false)}
                            >
                                ✕
                            </button>
                        </div>

                        <form className="customizer-body" onSubmit={saveCustomization}>
                            {/* Presets */}
                            <div className="customizer-section">
                                <h3>Presets</h3>
                                <div className="preset-grid">
                                    {THEME_PRESETS.map((preset) => (
                                        <button
                                            key={preset.name}
                                            type="button"
                                            className="preset-btn"
                                            onClick={() =>
                                                customizerForm.setData({
                                                    ...customizerForm.data,
                                                    ...preset.values,
                                                })
                                            }
                                        >
                                            <span
                                                className="preset-swatch"
                                                style={{ background: preset.swatch }}
                                            />
                                            <span>{preset.name}</span>
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Left Sidebar */}
                            <div className="customizer-section">
                                <h3>Left Sidebar</h3>
                                <div className="color-grid">
                                    <ColorField
                                        label="Sidebar background"
                                        value={customizerForm.data.admin_sidebar_bg}
                                        onChange={(v) => customizerForm.setData('admin_sidebar_bg', v)}
                                    />
                                    <ColorField
                                        label="Sidebar text"
                                        value={customizerForm.data.admin_sidebar_text}
                                        onChange={(v) => customizerForm.setData('admin_sidebar_text', v)}
                                    />
                                    <ColorField
                                        label="Active pill bg"
                                        value={customizerForm.data.admin_sidebar_active_bg}
                                        onChange={(v) => customizerForm.setData('admin_sidebar_active_bg', v)}
                                    />
                                    <ColorField
                                        label="Brand bag icon"
                                        value={customizerForm.data.admin_brand_color}
                                        onChange={(v) => customizerForm.setData('admin_brand_color', v)}
                                    />
                                </div>
                                <div className="row" style={{ marginTop: 10 }}>
                                    <div className="field" style={{ marginBottom: 0 }}>
                                        <label>Sidebar width (px)</label>
                                        <input
                                            type="number"
                                            min={180}
                                            max={320}
                                            value={customizerForm.data.admin_sidebar_width}
                                            onChange={(e) =>
                                                customizerForm.setData('admin_sidebar_width', e.target.value)
                                            }
                                        />
                                    </div>
                                    <div className="field" style={{ marginBottom: 0 }}>
                                        <label>Outer frame inset</label>
                                        <select
                                            value={customizerForm.data.admin_frame_inset}
                                            onChange={(e) =>
                                                customizerForm.setData('admin_frame_inset', e.target.value)
                                            }
                                        >
                                            <option value="0px">0px (Flush)</option>
                                            <option value="4px">4px (Subtle)</option>
                                            <option value="6px">6px (Reference)</option>
                                            <option value="10px">10px (Deep frame)</option>
                                        </select>
                                    </div>
                                </div>
                                <div className="row" style={{ marginTop: 10 }}>
                                    <div className="field" style={{ marginBottom: 0 }}>
                                        <label>Sidebar name (blank: site name)</label>
                                        <input
                                            type="text"
                                            placeholder={site?.name}
                                            value={customizerForm.data.admin_store_handle ?? ''}
                                            onChange={(e) =>
                                                customizerForm.setData('admin_store_handle', e.target.value)
                                            }
                                        />
                                    </div>
                                    <div className="field" style={{ marginBottom: 0 }}>
                                        <label>Badge (optional)</label>
                                        <input
                                            type="text"
                                            value={customizerForm.data.admin_store_badge ?? ''}
                                            onChange={(e) =>
                                                customizerForm.setData('admin_store_badge', e.target.value)
                                            }
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Typography */}
                            <div className="customizer-section">
                                <h3>Font & Typography</h3>
                                <div className="row">
                                    <div className="field" style={{ marginBottom: 0 }}>
                                        <label>Body font</label>
                                        <select
                                            value={customizerForm.data.admin_font_family}
                                            onChange={(e) =>
                                                customizerForm.setData('admin_font_family', e.target.value)
                                            }
                                        >
                                            {Object.keys(FONT_MAP).map((f) => (
                                                <option key={f} value={f}>
                                                    {f}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="field" style={{ marginBottom: 0 }}>
                                        <label>Heading font</label>
                                        <select
                                            value={customizerForm.data.admin_heading_font}
                                            onChange={(e) =>
                                                customizerForm.setData('admin_heading_font', e.target.value)
                                            }
                                        >
                                            {Object.keys(FONT_MAP)
                                                .filter((f) => f !== 'JetBrains Mono')
                                                .map((f) => (
                                                    <option key={f} value={f}>
                                                        {f}
                                                    </option>
                                                ))}
                                        </select>
                                    </div>
                                </div>
                                <div className="row" style={{ marginTop: 10 }}>
                                    <div className="field" style={{ marginBottom: 0 }}>
                                        <label>Font size</label>
                                        <select
                                            value={customizerForm.data.admin_font_size}
                                            onChange={(e) =>
                                                customizerForm.setData('admin_font_size', e.target.value)
                                            }
                                        >
                                            <option value="12px">12px (Compact)</option>
                                            <option value="13px">13px (Reference)</option>
                                            <option value="14px">14px (Medium)</option>
                                            <option value="15px">15px (Large)</option>
                                        </select>
                                    </div>
                                    <div className="field" style={{ marginBottom: 0 }}>
                                        <label>Heading weight</label>
                                        <select
                                            value={customizerForm.data.admin_heading_weight}
                                            onChange={(e) =>
                                                customizerForm.setData('admin_heading_weight', e.target.value)
                                            }
                                        >
                                            <option value="500">500 (Medium)</option>
                                            <option value="600">600 (Reference Semibold)</option>
                                            <option value="700">700 (Bold)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {/* Spacing & Shape */}
                            <div className="customizer-section">
                                <h3>Spacing & Radius</h3>
                                <div className="row">
                                    <div className="field" style={{ marginBottom: 0 }}>
                                        <label>Density</label>
                                        <select
                                            value={customizerForm.data.admin_spacing}
                                            onChange={(e) =>
                                                customizerForm.setData('admin_spacing', e.target.value)
                                            }
                                        >
                                            <option value="compact">Compact</option>
                                            <option value="default">Default (Reference)</option>
                                            <option value="comfortable">Comfortable</option>
                                            <option value="spacious">Spacious</option>
                                        </select>
                                    </div>
                                    <div className="field" style={{ marginBottom: 0 }}>
                                        <label>Content padding</label>
                                        <select
                                            value={customizerForm.data.admin_content_padding}
                                            onChange={(e) =>
                                                customizerForm.setData('admin_content_padding', e.target.value)
                                            }
                                        >
                                            <option value="16px">16px</option>
                                            <option value="20px">20px</option>
                                            <option value="24px">24px (Reference)</option>
                                            <option value="32px">32px</option>
                                            <option value="40px">40px</option>
                                        </select>
                                    </div>
                                </div>
                                <div className="row" style={{ marginTop: 10 }}>
                                    <div className="field" style={{ marginBottom: 0 }}>
                                        <label>Card radius</label>
                                        <select
                                            value={customizerForm.data.admin_radius}
                                            onChange={(e) =>
                                                customizerForm.setData('admin_radius', e.target.value)
                                            }
                                        >
                                            <option value="6px">6px</option>
                                            <option value="8px">8px</option>
                                            <option value="12px">12px (Reference)</option>
                                            <option value="16px">16px</option>
                                            <option value="20px">20px</option>
                                        </select>
                                    </div>
                                    <div className="field" style={{ marginBottom: 0 }}>
                                        <label>Button shape</label>
                                        <select
                                            value={customizerForm.data.admin_button_radius}
                                            onChange={(e) =>
                                                customizerForm.setData('admin_button_radius', e.target.value)
                                            }
                                        >
                                            <option value="999px">Pill (Reference)</option>
                                            <option value="8px">Rounded (8px)</option>
                                            <option value="4px">Sharp (4px)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {/* Colors */}
                            <div className="customizer-section">
                                <h3>Canvas & Colors</h3>
                                <div className="color-grid">
                                    <ColorField
                                        label="Canvas background"
                                        value={customizerForm.data.admin_canvas_bg}
                                        onChange={(v) => customizerForm.setData('admin_canvas_bg', v)}
                                    />
                                    <ColorField
                                        label="Card surface"
                                        value={customizerForm.data.admin_surface_bg}
                                        onChange={(v) => customizerForm.setData('admin_surface_bg', v)}
                                    />
                                    <ColorField
                                        label="Primary button"
                                        value={customizerForm.data.admin_primary_color}
                                        onChange={(v) => customizerForm.setData('admin_primary_color', v)}
                                    />
                                    <ColorField
                                        label="Border color"
                                        value={customizerForm.data.admin_border_color}
                                        onChange={(v) => customizerForm.setData('admin_border_color', v)}
                                    />
                                    <ColorField
                                        label="Primary text"
                                        value={customizerForm.data.admin_text_color}
                                        onChange={(v) => customizerForm.setData('admin_text_color', v)}
                                    />
                                    <ColorField
                                        label="Muted text"
                                        value={customizerForm.data.admin_muted_color}
                                        onChange={(v) => customizerForm.setData('admin_muted_color', v)}
                                    />
                                </div>
                            </div>

                            {/* Sidekick Assistant Bar */}
                            <div className="customizer-section">
                                <label className="check">
                                    <input
                                        type="checkbox"
                                        checked={!!customizerForm.data.admin_show_assistant}
                                        onChange={(e) =>
                                            customizerForm.setData('admin_show_assistant', e.target.checked)
                                        }
                                    />
                                    Show bottom “Work with Sidekick” bar
                                </label>
                            </div>
                        </form>

                        <div className="customizer-foot">
                            <button
                                type="button"
                                className="btn btn-light btn-sm"
                                onClick={resetCustomization}
                            >
                                Reset to reference
                            </button>
                            <button
                                type="button"
                                className="btn btn-sm"
                                onClick={saveCustomization}
                                disabled={customizerForm.processing}
                            >
                                {customizerForm.processing ? 'Saving…' : 'Save design'}
                            </button>
                        </div>
                    </aside>
                </>
            ) : null}
        </div>
    );
}

function ColorField({ label, value, onChange }) {
    return (
        <div className="color-field">
            <label>{label}</label>
            <div className="color-input-wrap">
                <input
                    type="color"
                    value={value || '#000000'}
                    onChange={(e) => onChange(e.target.value)}
                />
                <input
                    type="text"
                    value={value || ''}
                    onChange={(e) => onChange(e.target.value)}
                />
            </div>
        </div>
    );
}
