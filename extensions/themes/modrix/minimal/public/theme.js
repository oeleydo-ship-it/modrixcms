/*
 * Minimal theme: replaces the core layout's header with a quieter one and restyles the
 * "Call to action" block. Every other page and block uses the core components.
 *
 * Plain JavaScript (React.createElement), so it works as-is; React and Inertia come from
 * window.ModrixCMS. A bigger theme would write JSX and compile it to a file like this.
 */
const { React, Inertia, ui, registerTheme } = window.ModrixCMS;
const h = React.createElement;
const { Link, usePage } = Inertia;

function Header() {
    const { site, links, menu, theme } = usePage().props;
    const { url } = usePage();
    const [open, setOpen] = React.useState(false);
    const settings = theme.settings;

    return h('header', { className: 'nav minimal-nav', id: 'top' },
        h('div', { className: 'wrap nav-inner' },
            h(Link, { className: 'brand', href: links.home, 'aria-label': `${site.name} home` },
                site.logo ? h('img', { className: 'brand-logo', src: site.logo, alt: site.name }) : h('span', null, site.name)),
            h('nav', { className: `nav-links${open ? ' open' : ''}`, 'aria-label': 'Main' },
                h(Link, { href: links.blog, className: url.startsWith('/blog') ? 'current' : undefined }, 'Journal'),
                menu.map((item) => h(Link, { key: item.slug, href: item.url, className: url === `/${item.slug}` ? 'current' : undefined }, item.title)),
            ),
            settings.show_cta ? h('a', { className: 'btn btn-sm', href: settings.cta_url || links.github }, settings.cta_label || 'Get started') : null,
            h('button', { className: 'menu', type: 'button', 'aria-label': 'Open menu', 'aria-expanded': open, onClick: () => setOpen(!open) },
                h('span'), h('span')),
        ),
    );
}

function Layout({ children }) {
    return h(React.Fragment, null,
        h(Header),
        h('main', null, children),
        h(ui.Footer),
    );
}

/** A plain, left-aligned call to action instead of the coloured banner. */
function Cta({ data, anchor }) {
    return h('section', { className: 'section minimal-cta', id: anchor },
        h('div', { className: 'wrap' },
            data.title ? h('h2', null, data.title) : null,
            data.text ? h('p', null, data.text) : null,
            h('div', { className: 'cta-row' },
                data.primary_label ? h('a', { className: 'btn', href: data.primary_url }, data.primary_label) : null,
                data.secondary_label ? h('a', { className: 'btn btn-light', href: data.secondary_url }, data.secondary_label) : null,
            ),
        ),
    );
}

registerTheme('modrix/minimal', {
    layout: Layout,
    blocks: { cta: Cta },
});
