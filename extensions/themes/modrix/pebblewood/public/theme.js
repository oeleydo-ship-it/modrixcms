/*
 * Pebblewood theme: a coral announcement bar, a cream header with a phone number and a
 * "Book a visit" pill, and a rounded cream footer card. Pages and blocks use the core
 * components; colours and fonts come from Theme settings (--site-* variables).
 *
 * Plain JavaScript (React.createElement), so it runs as-is; React and Inertia come from
 * window.ModrixCMS.
 */
const { React, Inertia, registerTheme } = window.ModrixCMS;
const h = React.createElement;
const { Link, usePage } = Inertia;

function Brand() {
    const { site, links } = usePage().props;

    return h(Link, { className: 'pw-brand', href: links.home, 'aria-label': `${site.name} home` },
        site.logo ? h('img', { src: site.logo, alt: site.name }) : site.name);
}

function telHref(phone) {
    return `tel:${String(phone).replace(/[^\d+]/g, '')}`;
}

function Header() {
    const { links, menu, theme } = usePage().props;
    const { url } = usePage();
    const [open, setOpen] = React.useState(false);
    const settings = theme.settings || {};
    const ctaUrl = settings.cta_url || '/contact';

    React.useEffect(() => setOpen(false), [url]);

    const current = (href) => (url === href || (href !== '/' && url.startsWith(href)) ? 'current' : undefined);

    return h(React.Fragment, null,
        settings.show_announcement !== false && settings.announcement_text
            ? h('div', { className: 'pw-announcement' },
                h('p', null,
                    settings.announcement_text,
                    settings.announcement_link_label ? h(React.Fragment, null, ' — ', h('a', { href: settings.announcement_url || ctaUrl }, settings.announcement_link_label)) : null))
            : null,
        h('header', { className: 'pw-header', id: 'top' },
            h('div', { className: 'pw-wrap pw-header-inner' },
                h(Brand),
                h('nav', { className: `pw-nav${open ? ' open' : ''}`, 'aria-label': 'Main' },
                    menu.map((item) => h(Link, { key: item.slug, href: item.url, className: current(item.url) }, item.title)),
                    h(Link, { href: links.blog, className: current(links.blog) }, 'Blog'),
                    h('a', { className: 'pw-btn pw-nav-cta', href: ctaUrl }, settings.cta_label || 'Book a visit'),
                ),
                settings.phone
                    ? h('a', { className: 'pw-call', href: telHref(settings.phone) }, h('strong', null, 'Call us'), h('span', null, settings.phone))
                    : null,
                h('a', { className: 'pw-btn pw-header-cta', href: ctaUrl }, settings.cta_label || 'Book a visit'),
                h('button', { className: 'pw-menu', type: 'button', 'aria-label': open ? 'Close menu' : 'Open menu', 'aria-expanded': open, onClick: () => setOpen(!open) },
                    h('span'), h('span')),
            ),
        ),
    );
}

function Footer() {
    const { links, menu, footer, theme } = usePage().props;
    const settings = theme.settings || {};
    const socials = Object.entries(footer.socials || {});

    return h('footer', { className: 'pw-footer' },
        h('div', { className: 'pw-wrap' },
            h('div', { className: 'pw-footer-card' },
                h('div', { className: 'pw-footer-grid' },
                    h('div', { className: 'pw-footer-about' },
                        h(Brand),
                        settings.footer_about ? h('p', null, settings.footer_about) : null,
                        settings.footer_badge ? h('p', { className: 'pw-footer-badge' }, settings.footer_badge) : null,
                        socials.length
                            ? h('div', { className: 'pw-socials' }, socials.map(([label, href]) => h('a', { key: label, href, target: '_blank', rel: 'noopener', 'aria-label': label }, label.slice(0, 2))))
                            : null,
                    ),
                    h('nav', { 'aria-label': 'Footer' },
                        h('h4', null, 'Explore'),
                        menu.map((item) => h(Link, { key: item.slug, href: item.url }, item.title)),
                        h(Link, { href: links.blog }, 'Blog'),
                    ),
                    h('div', { className: 'pw-footer-contact' },
                        h('h4', null, 'Get in touch'),
                        settings.phone ? h('a', { href: telHref(settings.phone) }, settings.phone) : null,
                        footer.email ? h('a', { href: `mailto:${footer.email}` }, footer.email) : null,
                        h('a', { className: 'pw-btn', href: settings.cta_url || '/contact' }, settings.cta_label || 'Book a visit'),
                    ),
                ),
                h('p', { className: 'pw-copy' }, `© ${new Date().getFullYear()} ${footer.text || ''}`),
            ),
        ),
    );
}

function Layout({ children }) {
    return h(React.Fragment, null,
        h(Header),
        h('main', { className: 'pw-main' }, children),
        h(Footer),
    );
}

registerTheme('modrix/pebblewood', {
    layout: Layout,
});
