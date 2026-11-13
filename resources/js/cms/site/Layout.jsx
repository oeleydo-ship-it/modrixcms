import { useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import useReveal from './useReveal';

/** Brand: the uploaded logo, or the icon + site name. */
export function Brand({ size = 28 }) {
    const { site, links } = usePage().props;

    return (
        <Link className="brand" href={links.home} aria-label={`${site.name} home`}>
            {site.logo ? (
                <img className="brand-logo" src={site.logo} alt={site.name} />
            ) : (
                <>
                    <img src="/favicon.svg" alt="" width={size} height={size} />
                    <span>{site.name}</span>
                </>
            )}
        </Link>
    );
}

export function Nav() {
    const { props: { links, menu }, url } = usePage();
    const [open, setOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);
    const onHome = url === '/' || url.startsWith('/?');
    const home = onHome ? '' : links.home;

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    useEffect(() => setOpen(false), [url]);

    return (
        <header className={`nav${scrolled ? ' scrolled' : ''}`} id="top">
            <div className="wrap nav-inner">
                <Brand />
                <nav className={`nav-links${open ? ' open' : ''}`} aria-label="Main">
                    <a href={`${home}#features`}>Features</a>
                    <a href={`${home}#marketplace`}>Marketplace</a>
                    <Link href={links.blog} className={url.startsWith('/blog') ? 'current' : undefined}>Blog</Link>
                    {menu.map((item) => (
                        <Link key={item.slug} href={item.url} className={url === `/${item.slug}` ? 'current' : undefined}>{item.title}</Link>
                    ))}
                </nav>
                <a className="btn btn-sm" href={links.github}>Get it on GitHub</a>
                <button className="menu" type="button" aria-label="Open menu" aria-expanded={open} onClick={() => setOpen(!open)}>
                    <span></span><span></span>
                </button>
            </div>
        </header>
    );
}

export function Footer() {
    const { props: { links, menu, footer }, url } = usePage();
    const home = url === '/' ? '' : links.home;

    return (
        <footer className="footer">
            <div className="wrap footer-inner">
                <Brand size={24} />
                <nav aria-label="Footer">
                    <a href={`${home}#features`}>Features</a>
                    <a href={`${home}#marketplace`}>Marketplace</a>
                    <Link href={links.blog}>Blog</Link>
                    {menu.map((item) => <Link key={item.slug} href={item.url}>{item.title}</Link>)}
                    <a href={links.github}>GitHub</a>
                    {Object.entries(footer.socials).map(([label, href]) => (
                        <a key={label} href={href} rel="noopener" target="_blank">{label}</a>
                    ))}
                </nav>
                <p>
                    © {new Date().getFullYear()} {footer.text}
                    {footer.email ? <> · <a href={`mailto:${footer.email}`}>{footer.email}</a></> : null}
                </p>
            </div>
        </footer>
    );
}

/** The core public layout: header, page, footer. Themes can replace it. */
export default function Layout({ children }) {
    useReveal();

    return (
        <>
            <Nav />
            <main>{children}</main>
            <Footer />
        </>
    );
}
