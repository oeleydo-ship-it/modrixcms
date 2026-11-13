import { Link } from '@inertiajs/react';

export default function SettingsTabs() {
    const tabs = [
        ['admin.settings.general', 'General'],
        ['admin.settings.smtp', 'SMTP'],
        ['admin.settings.branding', 'Logo & favicon'],
    ];

    return (
        <nav className="tabs" aria-label="Settings">
            {tabs.map(([name, label]) => (
                <Link key={name} href={route(name)} className={route().current(`${name}*`) ? 'on' : undefined}>{label}</Link>
            ))}
        </nav>
    );
}
