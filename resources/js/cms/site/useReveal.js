import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';

const SELECTOR = '.section-head, .card, .deploy-card, .terminal, .market-copy, .market-tiles, .compare, .final-inner';

/** Fades sections in as they scroll into view (re-run on every page visit). */
export default function useReveal() {
    const { url } = usePage();

    useEffect(() => {
        if (!('IntersectionObserver' in window)) {
            return undefined;
        }

        const io = new IntersectionObserver(
            (entries) => entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in');
                    io.unobserve(entry.target);
                }
            }),
            { threshold: 0.12 },
        );

        document.querySelectorAll(SELECTOR).forEach((el) => {
            if (!el.classList.contains('in')) {
                el.classList.add('reveal');
                io.observe(el);
            }
        });

        return () => io.disconnect();
    }, [url]);
}
