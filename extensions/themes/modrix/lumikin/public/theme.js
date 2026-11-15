/*
 * Lumikin theme: a white sticky header (logo, centred links, berry pill) and a sand footer
 * with a photo call-to-action band, for the regular CMS pages (blog, posts, block pages).
 * Visual-builder pages use the theme's own global header and footer from its demo.
 * Colours and fonts come from Theme settings (--site-* variables).
 *
 * Plain JavaScript (React.createElement); React and Inertia come from window.ModrixCMS.
 */
const { React, Inertia, registerTheme } = window.ModrixCMS;
const h = React.createElement;
const { Link, usePage } = Inertia;
const LOGO = '/extensions/themes/modrix/lumikin/logo.svg';

function Brand() {
    const { site, links } = usePage().props;

    return h(Link, { className: 'lk-brand', href: links.home, 'aria-label': `${site.name} home` },
        h('img', { src: site.logo || LOGO, alt: site.name }));
}

function Header() {
    const { links, menu, theme } = usePage().props;
    const { url } = usePage();
    const [open, setOpen] = React.useState(false);
    const settings = theme.settings || {};
    const ctaUrl = settings.cta_url || '/contact';
    const label = settings.cta_label || 'Schedule a visit';

    React.useEffect(() => setOpen(false), [url]);

    const current = (href) => (url === href || (href !== '/' && url.startsWith(href)) ? 'current' : undefined);

    return h('header', { className: 'lk-header', id: 'top' },
        h('div', { className: 'lk-wrap lk-header-inner' },
            h(Brand),
            h('nav', { className: `lk-nav${open ? ' open' : ''}`, 'aria-label': 'Main' },
                menu.map((item) => h(Link, { key: item.slug, href: item.url, className: current(item.url) }, item.title)),
                h(Link, { href: links.blog, className: current(links.blog) }, 'Blog'),
                h('a', { className: 'lk-btn lk-nav-cta', href: ctaUrl }, label, ' →'),
            ),
            h('a', { className: 'lk-btn lk-header-cta', href: ctaUrl }, label, ' →'),
            h('button', { className: 'lk-menu', type: 'button', 'aria-label': open ? 'Close menu' : 'Open menu', 'aria-expanded': open, onClick: () => setOpen(!open) },
                h('span'), h('span')),
        ),
    );
}

function Footer() {
    const { links, menu, footer, theme } = usePage().props;
    const settings = theme.settings || {};
    const socials = Object.entries(footer.socials || {});

    return h('footer', { className: 'lk-footer' },
        h('div', { className: 'lk-wrap' },
            h('div', { className: 'lk-footer-cta' },
                h('h2', null, 'Inspiring young minds through joyful learning, growth and confidence'),
                h('a', { className: 'lk-btn lk-btn-white', href: settings.cta_url || '/contact' }, 'Enrol your child →'),
            ),
            h('div', { className: 'lk-footer-grid' },
                h('div', { className: 'lk-footer-about' },
                    h(Brand),
                    settings.footer_about ? h('p', null, settings.footer_about) : null,
                    socials.length ? h('h4', null, 'Follow us') : null,
                    socials.length
                        ? h('div', { className: 'lk-socials' }, socials.map(([name, href]) => h('a', { key: name, href, target: '_blank', rel: 'noopener', 'aria-label': name }, name.slice(0, 2))))
                        : null,
                ),
                h('nav', { 'aria-label': 'Footer' },
                    h('h4', null, 'Quick links'),
                    menu.map((item) => h(Link, { key: item.slug, href: item.url }, item.title)),
                    h(Link, { href: links.blog }, 'Blog'),
                ),
                h('div', { className: 'lk-footer-contact' },
                    settings.address ? h(React.Fragment, null, h('h4', null, 'Address'), h('p', null, settings.address)) : null,
                    h('h4', null, 'Contact'),
                    settings.phone ? h('a', { href: `tel:${String(settings.phone).replace(/[^\d+]/g, '')}` }, settings.phone) : null,
                    footer.email ? h('a', { href: `mailto:${footer.email}` }, footer.email) : null,
                ),
            ),
            h('p', { className: 'lk-copy' }, `© ${new Date().getFullYear()} ${footer.text || ''}`),
        ),
    );
}

function Layout({ children }) {
    return h(React.Fragment, null, h(Header), h('main', { className: 'lk-main' }, children), h(Footer));
}

registerTheme('modrix/lumikin', { layout: Layout });
