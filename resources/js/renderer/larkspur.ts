/**
 * Styling and motion for the `larkspur` design collection (`modrix/larkspur`, Larkspur Family Health).
 *
 * Everything reads the active Theme Settings (`--site-*`), so colour, font and button changes
 * made in Appearance → Theme settings carry through. Motion: headlines reveal word by word,
 * marked blocks fade up as they enter the viewport, the promise band scrolls endlessly and
 * the header gains a shadow once the page scrolls. All of it is skipped on the builder canvas
 * and for visitors who prefer reduced motion.
 */

import { useEffect, type RefObject } from 'react';

const SPLIT_SELECTOR = '[data-larkspur-role~="split-heading"]';
const FADE_SELECTOR = '[data-larkspur-role~="fade-up"]';

/** Wraps each word of a heading in a clipped span so it can rise into view; keeps <em>/<br>. */
function splitWords(heading: HTMLElement): void {
    if (heading.dataset.lkSplit === '1') {
        return;
    }
    heading.dataset.lkSplit = '1';
    let index = 0;

    const walk = (node: Node): void => {
        Array.from(node.childNodes).forEach((child) => {
            if (child.nodeType === Node.TEXT_NODE) {
                const parts = (child.textContent ?? '').split(/(\s+)/);
                const fragment = document.createDocumentFragment();
                parts.forEach((part) => {
                    if (part === '') {
                        return;
                    }
                    if (/^\s+$/.test(part)) {
                        fragment.appendChild(document.createTextNode(' '));
                        return;
                    }
                    const outer = document.createElement('span');
                    outer.className = 'lk-word';
                    const inner = document.createElement('span');
                    inner.textContent = part;
                    inner.style.transitionDelay = `${index * 45}ms`;
                    index++;
                    outer.appendChild(inner);
                    fragment.appendChild(outer);
                });
                child.replaceWith(fragment);
            } else if (child.nodeType === Node.ELEMENT_NODE && (child as Element).tagName !== 'BR') {
                walk(child);
            }
        });
    };

    walk(heading);
}

/**
 * Makes pill tab bars slide: drag with a mouse (touch already swipes), the clicked tab
 * glides to the centre, and `data-lk-more` tells the CSS which edge has more tabs.
 */
function wireTabSliders(page: HTMLElement): Array<() => void> {
    const cleanups: Array<() => void> = [];

    page.querySelectorAll<HTMLElement>('[data-larkspur-role~="faq-tabs"] [role="tablist"]').forEach((list) => {
        const update = (): void => {
            const max = list.scrollWidth - list.clientWidth;
            list.dataset.lkMore = max <= 1 ? 'none' : list.scrollLeft <= 8 ? 'end' : list.scrollLeft >= max - 8 ? 'start' : 'both';
        };

        let startX = 0;
        let startScroll = 0;
        let dragging = false;
        let moved = false;

        const onMove = (event: PointerEvent): void => {
            const dx = event.clientX - startX;
            if (Math.abs(dx) > 4) {
                moved = true;
                list.classList.add('is-dragging');
            }
            list.scrollLeft = startScroll - dx;
        };
        const onUp = (): void => {
            dragging = false;
            // The click that follows a drag fires right after this; clear the flag once it has passed.
            window.setTimeout(() => {
                moved = false;
            }, 0);
            list.classList.remove('is-dragging');
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', onUp);
        };
        const onDown = (event: PointerEvent): void => {
            if (event.pointerType !== 'mouse' || event.button !== 0 || list.scrollWidth <= list.clientWidth + 1) {
                return;
            }
            dragging = true;
            moved = false;
            startX = event.clientX;
            startScroll = list.scrollLeft;
            window.addEventListener('pointermove', onMove);
            window.addEventListener('pointerup', onUp);
        };
        // A drag must not count as a click on the tab it ended over.
        const onClickCapture = (event: MouseEvent): void => {
            if (moved) {
                event.stopPropagation();
                event.preventDefault();
                moved = false;
            }
        };
        const onClick = (event: MouseEvent): void => {
            const tab = (event.target as HTMLElement).closest<HTMLElement>('[role="tab"]');
            if (!tab || dragging) {
                return;
            }
            const listRect = list.getBoundingClientRect();
            const tabRect = tab.getBoundingClientRect();
            list.scrollBy({ left: tabRect.left - listRect.left - (listRect.width - tabRect.width) / 2, behavior: 'smooth' });
        };

        update();
        list.addEventListener('scroll', update, { passive: true });
        list.addEventListener('pointerdown', onDown);
        list.addEventListener('click', onClickCapture, true);
        list.addEventListener('click', onClick);
        window.addEventListener('resize', update);
        cleanups.push(() => {
            list.removeEventListener('scroll', update);
            list.removeEventListener('pointerdown', onDown);
            list.removeEventListener('click', onClickCapture, true);
            list.removeEventListener('click', onClick);
            window.removeEventListener('resize', update);
            onUp();
        });
    });

    return cleanups;
}

export function useLarkspurMotion(root: RefObject<HTMLDivElement | null>, editable: boolean, revision: unknown): void {
    useEffect(() => {
        const page = root.current;
        if (!page || editable || !page.querySelector('[data-larkspur]')) {
            return;
        }

        const cleanups: Array<() => void> = [...wireTabSliders(page)];

        const headers = Array.from(page.querySelectorAll<HTMLElement>('[data-larkspur="header"]'));
        const onScroll = (): void => {
            const scrolled = window.scrollY > 16;
            headers.forEach((header) => header.classList.toggle('is-scrolled', scrolled));
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        cleanups.push(() => window.removeEventListener('scroll', onScroll));

        const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
        const headings = Array.from(page.querySelectorAll<HTMLElement>(SPLIT_SELECTOR));
        const fades = Array.from(page.querySelectorAll<HTMLElement>(FADE_SELECTOR));
        const heroes = Array.from(page.querySelectorAll<HTMLElement>('[data-larkspur="hero"]'));

        if (reducedMotion || typeof IntersectionObserver === 'undefined') {
            [...headings, ...fades, ...heroes].forEach((el) => el.setAttribute('data-lk-inview', 'true'));
            return () => cleanups.forEach((fn) => fn());
        }

        headings.forEach((wrapper) => {
            const heading = wrapper.querySelector<HTMLElement>('h1, h2');
            if (heading) {
                splitWords(heading);
            }
        });

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.setAttribute('data-lk-inview', 'true');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
        );

        const frames: number[] = [];
        [...headings, ...fades, ...heroes].forEach((el, i) => {
            el.setAttribute('data-lk-motion', 'ready');
            if (el.matches(FADE_SELECTOR)) {
                el.style.transitionDelay = `${(i % 4) * 70}ms`;
            }
            const rect = el.getBoundingClientRect();
            if (rect.bottom <= 0) {
                el.setAttribute('data-lk-inview', 'true');
            } else if (rect.top < window.innerHeight * 0.95) {
                // Already on screen: start after first paint so the transition runs.
                frames.push(requestAnimationFrame(() => frames.push(requestAnimationFrame(() => el.setAttribute('data-lk-inview', 'true')))));
            } else {
                observer.observe(el);
            }
        });

        cleanups.push(() => {
            observer.disconnect();
            frames.forEach((id) => cancelAnimationFrame(id));
        });

        return () => cleanups.forEach((fn) => fn());
    }, [root, editable, revision]);
}

const ARROW_MASK =
    "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='%23000' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 11 11 5M6 5h5v5'/%3E%3C/svg%3E\")";

const mobileHeaderRules = (scope: string): string => `
  ${scope} [data-larkspur="header"] { overflow: visible !important; }
  ${scope} [data-larkspur="header"] [data-larkspur-role~="announce-item"]:not(:last-child) { display: none !important; }
  ${scope} [data-larkspur="header"] [data-larkspur-role~="header-bar"] > .fnl-nested-columns {
    grid-template-columns: minmax(0, 1fr) auto !important;
    align-items: center !important;
    gap: 12px !important;
    position: static !important;
  }
  ${scope} [data-larkspur="header"] [data-larkspur-role~="header-bar"] { position: relative !important; width: 100% !important; max-width: none !important; box-sizing: border-box !important; }
  ${scope} [data-larkspur="header"] [data-larkspur-role~="header-bar"] > .fnl-nested-columns { width: 100% !important; justify-content: stretch !important; }
  ${scope} [data-larkspur="header"] [data-larkspur-role~="header-bar"] [data-fnl-type="logo"] { justify-self: start !important; }
  ${scope} [data-larkspur="header"] [data-larkspur-role~="header-bar"] [data-fnl-type="navigation"] { justify-self: end !important; }
  ${scope} [data-larkspur="header"] [data-larkspur-role~="announce-item"]::before { display: none !important; }
  ${scope} [data-larkspur="header"] [data-larkspur-role~="header-cta"] { display: none !important; }
  ${scope} [data-larkspur="header"] [data-fnl-type="navigation"] { position: static !important; }
  ${scope} [data-larkspur="header"] nav[data-mobile="drawer"] {
    position: static !important;
    display: flex !important;
    justify-content: flex-end !important;
  }
  ${scope} [data-larkspur="header"] nav[data-mobile="drawer"] .fnl-nav-toggle {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 44px !important;
    height: 44px !important;
    border: 0 !important;
    border-radius: 999px !important;
    background: transparent !important;
    color: var(--site-primary, #1a6083) !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  ${scope} [data-larkspur="header"] nav[data-mobile="drawer"]:not([data-open="true"]) > ul { display: none !important; }
  ${scope} [data-larkspur="header"] nav[data-mobile="drawer"][data-open="true"] > ul {
    display: flex !important;
    flex-direction: column !important;
    align-items: stretch !important;
    position: absolute !important;
    left: 0 !important;
    right: 0 !important;
    top: 100% !important;
    width: 100% !important;
    box-sizing: border-box !important;
    margin: 0 !important;
    padding: 16px 20px 24px !important;
    gap: 4px !important;
    background: var(--site-header-bg, #ffffff) !important;
    border-top: 1px solid var(--site-border, #e1e4e8) !important;
    box-shadow: 0 24px 40px -24px rgba(20, 38, 56, 0.45) !important;
    z-index: 95 !important;
    animation: lk-drawer 300ms cubic-bezier(0.625, 0.05, 0, 1) both;
  }
  ${scope} [data-larkspur="header"] nav[data-mobile="drawer"][data-open="true"] > ul > li > a {
    display: block !important;
    padding: 10px 0 !important;
    font-size: 22px !important;
    color: var(--site-header-text, #2f2f2f) !important;
  }
`;

/** Pill tab bars (services filter, FAQ topics) scroll sideways on phones and keep their full labels. */
const mobileTabRules = (scope: string): string => `
  ${scope} [data-larkspur-role~="faq-tabs"] [role="tablist"] {
    display: flex !important;
    flex-wrap: nowrap !important;
    width: 100% !important;
    max-width: 100% !important;
    overflow-x: auto !important;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
  }
  ${scope} [data-larkspur-role~="faq-tabs"] [role="tablist"]::-webkit-scrollbar { display: none; }
  ${scope} [data-larkspur-role~="faq-tabs"] [role="tab"] {
    flex: 0 0 auto !important;
    overflow: visible !important;
    text-overflow: clip !important;
    white-space: nowrap !important;
    font-size: 14px !important;
    padding: 8px 16px !important;
  }
`;

export const LARKSPUR_CSS = `
.fnl-page [data-larkspur] { font-family: var(--site-body-font, 'Mulish', sans-serif); letter-spacing: -0.01rem; }
.fnl-page [data-larkspur] :is(h1, h2, h3, h4) { font-family: var(--site-heading-font, 'Instrument Sans', sans-serif); }
.fnl-page [data-larkspur] :is(h1, h2) em { font-style: normal; color: var(--site-primary, #1a6083); }
.fnl-page [data-larkspur="hero"] h1 em,
.fnl-page [data-larkspur="cta-banner"] h2 em { color: inherit; }
.fnl-page [data-larkspur] p a { color: inherit; text-decoration: none; }
.fnl-page [data-larkspur] p a:hover { text-decoration: underline; text-underline-offset: 4px; }

/* Header: announcement strip, centred menu and the scrolled shadow */
.fnl-page [data-larkspur="header"] { transition: box-shadow 240ms ease; }
.fnl-page [data-larkspur="header"].is-scrolled { box-shadow: 0 12px 30px -18px rgba(20, 38, 56, 0.35), inset 0 -1px 0 var(--site-border, #e1e4e8) !important; }
.fnl-page [data-larkspur="header"] [data-larkspur-role~="announcement"] > div { justify-content: center; align-items: center; column-gap: 0 !important; }
.fnl-page [data-larkspur="header"] [data-larkspur-role~="announce-item"] + [data-larkspur-role~="announce-item"]::before {
  content: '|'; margin: 0 20px; opacity: 0.5;
}
.fnl-page [data-larkspur="header"] [data-larkspur-role~="announce-item"] { display: inline-flex; }
.fnl-page [data-larkspur="header"] [data-larkspur-role~="header-bar"] > .fnl-nested-columns { align-items: center !important; }
.fnl-page [data-larkspur="header"] nav > ul { justify-content: center !important; flex-wrap: nowrap !important; }
.fnl-page [data-larkspur="header"] nav > ul > li > a { white-space: nowrap; text-decoration: none; text-underline-offset: 6px; text-decoration-thickness: 2px; }
.fnl-page [data-larkspur="header"] nav > ul > li > a:hover { text-decoration: underline; color: var(--site-secondary, #15394c); }
.fnl-page [data-larkspur="header"] [data-larkspur-role~="header-cta"] > div { justify-content: flex-end; flex-wrap: nowrap !important; }
@media (max-width: 1199px) {
  .fnl-page [data-larkspur="header"] [data-larkspur-role~="header-cta"] [data-larkspur-role~="btn-text"] { display: none !important; }
}

/* Pill buttons with the round arrow cell */
.fnl-page [data-larkspur-role~="btn"] :is(a, button) {
  position: relative;
  display: inline-flex !important;
  align-items: center;
  min-height: 48px;
  padding: 0 54px 0 24px !important;
  white-space: nowrap;
  transition: background-color 300ms ease, color 300ms ease, border-color 300ms ease !important;
}
.fnl-page [data-larkspur-role~="btn"] :is(a, button)::before {
  content: '';
  position: absolute;
  right: 7px;
  top: 50%;
  width: 34px;
  height: 34px;
  margin-top: -17px;
  border-radius: 999px;
  background: var(--lk-cell, var(--site-background, #ffffff));
  transition: background-color 300ms ease;
}
.fnl-page [data-larkspur-role~="btn"] :is(a, button)::after {
  content: '';
  position: absolute;
  right: 16px;
  top: 50%;
  width: 16px;
  height: 16px;
  margin-top: -8px;
  background: var(--lk-arrow, var(--site-primary, #1a6083));
  -webkit-mask: ${ARROW_MASK} center / contain no-repeat;
  mask: ${ARROW_MASK} center / contain no-repeat;
  transition: transform 400ms cubic-bezier(0.625, 0.05, 0, 1), background-color 300ms ease;
}
.fnl-page [data-larkspur-role~="btn"] :is(a, button):hover::after { transform: rotate(45deg); }
.fnl-page [data-larkspur-role~="btn-primary"] :is(a, button):hover { background: var(--site-secondary, #15394c) !important; }
.fnl-page [data-larkspur-role~="btn-light"],
.fnl-page [data-larkspur-role~="btn-outline"] { --lk-cell: var(--site-primary, #1a6083); --lk-arrow: var(--site-on-primary, #ffffff); }
.fnl-page [data-larkspur-role~="btn-light"] :is(a, button):hover,
.fnl-page [data-larkspur-role~="btn-outline"] :is(a, button):hover { background: var(--site-primary-soft, #e3edf5) !important; }
.fnl-page [data-larkspur-role~="btn-ghost-light"] { --lk-cell: color-mix(in srgb, var(--site-on-dark, #ffffff) 16%, transparent); --lk-arrow: var(--site-on-dark, #ffffff); }
.fnl-page [data-larkspur-role~="btn-ghost-light"] :is(a, button):hover { background: color-mix(in srgb, var(--site-on-dark, #ffffff) 10%, transparent) !important; }
.fnl-page [data-larkspur-role~="btn-text"] :is(a, button) {
  min-height: 0; padding: 4px 24px 4px 0 !important; text-decoration: none !important;
}
.fnl-page [data-larkspur-role~="btn-text"] :is(a, button)::before { display: none; }
.fnl-page [data-larkspur-role~="btn-text"] :is(a, button)::after { right: 0; }
.fnl-page [data-larkspur-role~="btn-text"] :is(a, button):hover { text-decoration: underline !important; text-underline-offset: 6px; }

/* Eyebrow pill dot */
.fnl-page [data-larkspur-role~="eyebrow"] > div { align-items: center; flex-wrap: nowrap; }
.fnl-page [data-larkspur-role~="eyebrow-dot"] svg { padding: 3px; }

/* Photo cards: captions, plaques and badges sit at the bottom of the photo */
.fnl-page [data-larkspur-role~="clinic-photo"],
.fnl-page [data-larkspur-role~="photo-card"],
.fnl-page [data-larkspur-role~="provider-photo"] { display: flex; flex-direction: column; justify-content: flex-end; }
.fnl-page [data-larkspur-role~="clinic-photo"] > div,
.fnl-page [data-larkspur-role~="photo-card"] > div,
.fnl-page [data-larkspur-role~="provider-photo"] > div { flex: 0 0 auto; }

/* Icon + text lines stay on one row; the text takes the remaining width */
.fnl-page [data-larkspur-role~="icon-line"] > div { flex-wrap: nowrap !important; }
.fnl-page [data-larkspur-role~="icon-line"] > div > :last-child { flex: 1 1 0; min-width: 0; }
.fnl-page [data-larkspur-role~="clinic-plaque"] > div { flex-wrap: nowrap !important; align-items: center; }
.fnl-page [data-larkspur-role~="clinic-plaque"] img { width: 32px; height: 32px; }

/* Chips with a leading dot */
.fnl-page [data-larkspur-role~="chip"] p { display: inline-flex; align-items: center; gap: 6px; }
.fnl-page [data-larkspur-role~="chip"] p::before {
  content: ''; width: 6px; height: 6px; border-radius: 999px; flex-shrink: 0; background: var(--site-primary, #1a6083);
}
.fnl-page [data-larkspur-role~="chip-warm"] p::before { background: var(--site-accent, #ca9352); }

/* Cards lift gently when Theme settings allow it */
.fnl-page [data-larkspur-role~="card"] { transition: transform 400ms cubic-bezier(0.22, 1, 0.36, 1), box-shadow 400ms ease; }
.fnl-page:not(.fnl-editable) [data-larkspur-role~="card"]:hover { transform: var(--site-card-hover-transform, translateY(-4px)); }
.fnl-page [data-larkspur-role~="clinic-photo"],
.fnl-page [data-larkspur-role~="provider-photo"] { transition: background-size 900ms cubic-bezier(0.22, 1, 0.36, 1); overflow: hidden; }

/* Promise marquee */
.fnl-page [data-larkspur-role~="marquee-viewport"] { overflow: hidden; width: 100%; }
.fnl-page [data-larkspur-role~="marquee-track"] > div { flex-wrap: nowrap !important; width: max-content; }
.fnl-page [data-larkspur-role~="marquee-item"] > div { flex-wrap: nowrap !important; }
.fnl-page:not(.fnl-editable) [data-larkspur-role~="marquee-track"] > div { animation: lk-marquee 40s linear infinite; }
.fnl-page [data-larkspur-role~="marquee-viewport"]:hover [data-larkspur-role~="marquee-track"] > div { animation-play-state: paused; }
@keyframes lk-marquee { from { transform: translateX(0); } to { transform: translateX(calc(-50% - 20px)); } }

/* Hero: ink gradient over the photo, which settles from a slight zoom */
.fnl-page [data-larkspur="hero"]::after {
  background: linear-gradient(rgba(12, 43, 59, 0.25) 0%, rgba(12, 43, 59, 0.55) 45%, rgba(12, 43, 59, 0.88) 100%) !important;
}
.fnl-page:not(.fnl-editable) [data-larkspur="hero"]::before { transition: transform 2400ms cubic-bezier(0.22, 1, 0.36, 1); }
.fnl-page:not(.fnl-editable) [data-larkspur="hero"][data-lk-motion="ready"]::before { transform: scale(1.15); }
.fnl-page:not(.fnl-editable) [data-larkspur="hero"][data-lk-inview="true"]::before { transform: none; }

/* Word-by-word headline reveal */
.fnl-page [data-larkspur] .lk-word { display: inline-block; overflow: hidden; vertical-align: bottom; padding-bottom: 0.08em; margin-bottom: -0.08em; }
.fnl-page [data-larkspur] .lk-word > span { display: inline-block; transition: transform 900ms cubic-bezier(0.625, 0.05, 0, 1), opacity 600ms ease; }
.fnl-page [data-larkspur-role~="split-heading"][data-lk-motion="ready"] .lk-word > span { transform: translateY(110%); opacity: 0; }
.fnl-page [data-larkspur-role~="split-heading"][data-lk-inview="true"] .lk-word > span { transform: none; opacity: 1; }

/* Fade up on scroll */
.fnl-page [data-larkspur-role~="fade-up"] { transition: opacity 800ms cubic-bezier(0.22, 1, 0.36, 1), transform 800ms cubic-bezier(0.22, 1, 0.36, 1); }
.fnl-page [data-larkspur-role~="fade-up"][data-lk-motion="ready"] { opacity: 0; transform: translateY(24px); }
.fnl-page [data-larkspur-role~="fade-up"][data-lk-inview="true"] { opacity: 1; transform: none; }

/* Category tabs (vertical pills) */
.fnl-page [data-larkspur-role~="category-tabs"] {
  --fnl-tablist-bg: transparent; --fnl-tablist-border: transparent; --fnl-tablist-radius: 0;
  --fnl-tab-radius: 999px; --fnl-tab-active-bg: var(--site-primary, #1a6083); --fnl-tab-active-color: var(--site-on-primary, #ffffff);
  --fnl-tab-inactive-color: var(--site-header-text, #2f2f2f); --fnl-tab-inactive-border: var(--site-border, #e1e4e8);
  --fnl-tab-active-border: var(--site-primary, #1a6083); --fnl-font-size: 14px; --fnl-gap: 56px;
}
.fnl-page [data-larkspur-role~="category-tabs"] [role="tablist"] { padding: 0 !important; min-width: 200px !important; gap: 8px !important; }
.fnl-page [data-larkspur-role~="category-tabs"] [role="tablist"]::before { content: 'Category'; font-size: 14px; color: var(--site-header-text, #2f2f2f); margin-bottom: 4px; }
.fnl-page [data-larkspur-role~="category-tabs"] [role="tab"] { text-align: left !important; padding: 8px 16px !important; font-weight: 400 !important; background-color: var(--fnl-tab-inactive-bg, #ffffff); }
.fnl-page [data-larkspur-role~="category-tabs"] [role="tab"][aria-selected="false"] { background: var(--site-background, #ffffff) !important; }
.fnl-page [data-larkspur-role~="category-tabs"] [role="tab"]:hover { border-color: var(--site-primary, #1a6083) !important; }
.fnl-page [data-larkspur-role~="category-tabs"] [role="tabpanel"] { flex: 1; animation: lk-fade 500ms ease both; }

/* FAQ topic tabs (horizontal pills) */
.fnl-page [data-larkspur-role~="faq-tabs"] {
  --fnl-tablist-radius: 999px; --fnl-tablist-border: var(--site-border, #e1e4e8); --fnl-tab-radius: 999px;
  --fnl-tab-active-bg: var(--site-primary, #1a6083); --fnl-tab-active-color: var(--site-on-primary, #ffffff);
  --fnl-tab-inactive-color: var(--site-header-text, #2f2f2f); --fnl-font-size: 15px; --fnl-gap: 24px;
}
.fnl-page [data-larkspur-role~="faq-tabs"] [role="tablist"] { display: inline-flex !important; width: fit-content; max-width: 100%; padding: 4px !important; gap: 2px !important; overflow-x: auto; }
.fnl-page [data-larkspur-role~="faq-tabs"] [role="tab"] { padding: 8px 16px !important; font-weight: 500 !important; }
.fnl-page [data-larkspur-role~="faq-tabs"] [role="tabpanel"] { border-top: 1px solid var(--site-border, #e1e4e8); animation: lk-fade 400ms ease both; }

/* Sliding tab bars: grab cursor and a soft fade on the edge that has more tabs */
.fnl-page [data-larkspur-role~="faq-tabs"] [role="tablist"][data-lk-more]:not([data-lk-more="none"]) { cursor: grab; }
.fnl-page [data-larkspur-role~="faq-tabs"] [role="tablist"].is-dragging { cursor: grabbing; user-select: none; scroll-snap-type: none; }
.fnl-page [data-larkspur-role~="faq-tabs"] [role="tablist"].is-dragging [role="tab"] { pointer-events: none; }
.fnl-page [data-larkspur-role~="faq-tabs"] [role="tablist"][data-lk-more="end"] {
  -webkit-mask-image: linear-gradient(to right, #000 calc(100% - 48px), transparent);
  mask-image: linear-gradient(to right, #000 calc(100% - 48px), transparent);
}
.fnl-page [data-larkspur-role~="faq-tabs"] [role="tablist"][data-lk-more="start"] {
  -webkit-mask-image: linear-gradient(to left, #000 calc(100% - 48px), transparent);
  mask-image: linear-gradient(to left, #000 calc(100% - 48px), transparent);
}
.fnl-page [data-larkspur-role~="faq-tabs"] [role="tablist"][data-lk-more="both"] {
  -webkit-mask-image: linear-gradient(to right, transparent, #000 48px, #000 calc(100% - 48px), transparent);
  mask-image: linear-gradient(to right, transparent, #000 48px, #000 calc(100% - 48px), transparent);
}

/* Service filter tabs: pill bar like the FAQ, cards below without a rule */
.fnl-page [data-larkspur-role~="service-tabs"] { --fnl-gap: 32px; }
.fnl-page [data-larkspur-role~="service-tabs"] [role="tabpanel"] { border-top: 0 !important; }
.fnl-page [data-larkspur-role~="service-tabs"] [role="tablist"] { flex-wrap: nowrap; scrollbar-width: none; }
.fnl-page [data-larkspur-role~="service-grid"] > .fnl-nested-columns { row-gap: 20px !important; }
.fnl-page [data-larkspur-role~="service-photo-card"] > .fnl-nested-columns > .fnl-block:last-child > div { height: 100%; }
.fnl-page [data-larkspur-role~="service-photo-card"] img { transition: transform 900ms cubic-bezier(0.22, 1, 0.36, 1); }
.fnl-page [data-larkspur-role~="service-photo-card"] > .fnl-nested-columns > .fnl-block:first-child { overflow: hidden; border-radius: 12px; }
.fnl-page:not(.fnl-editable) [data-larkspur-role~="service-photo-card"]:hover img { transform: scale(1.05); }
.fnl-page [data-larkspur-role~="service-foot"] > div { flex-wrap: nowrap !important; }

/* Service pages: breadcrumb and numbered expect steps */
.fnl-page [data-larkspur-role~="breadcrumb"] a { color: var(--site-primary, #1a6083); }
.fnl-page [data-larkspur-role~="expect-step"] > div { flex-wrap: nowrap !important; align-items: flex-start; }
.fnl-page [data-larkspur-role~="expect-list"] > div { border-bottom: 1px solid var(--site-border, #e1e4e8); }

/* FAQ rows */
.fnl-page [data-larkspur-role~="faq-list"] > div > div { border-bottom-color: var(--site-border, #e1e4e8) !important; }
.fnl-page [data-larkspur-role~="faq-list"] h3 { font-size: 20px; font-weight: 400; }
.fnl-page [data-larkspur-role~="faq-list"] button[aria-expanded] { padding: 24px 0 !important; font-weight: 400 !important; color: var(--site-primary, #1a6083) !important; }
.fnl-page [data-larkspur-role~="faq-list"] button[aria-expanded] svg {
  width: 28px !important; height: 28px !important; padding: 6px; border-radius: 999px;
  background: color-mix(in srgb, var(--site-primary, #1a6083) 12%, #ffffff); color: var(--site-primary, #1a6083);
  transition: transform 300ms ease, background-color 300ms ease, color 300ms ease !important;
}
.fnl-page [data-larkspur-role~="faq-list"] button[aria-expanded="true"] svg { background: var(--site-primary, #1a6083); color: var(--site-on-primary, #ffffff); }
.fnl-page [data-larkspur-role~="faq-list"] [id] { color: var(--site-body, #575757); font-family: var(--site-body-font, 'Mulish', sans-serif); font-size: 15px; line-height: 1.5; max-width: 40rem; padding-bottom: 24px !important; }

/* Tick lists end with a hairline */
.fnl-page [data-larkspur-role~="tick-list"] > div > :last-child [data-larkspur-role~="tick-row"],
.fnl-page [data-larkspur-role~="tick-list"] > div { border-bottom: 1px solid var(--site-border, #e1e4e8); }
.fnl-page [data-larkspur-role~="tick-row"] > div { flex-wrap: nowrap; align-items: center; }

/* Review carousel arrows */
.fnl-page [data-larkspur-role~="review-carousel"] button[aria-label] {
  width: 48px; height: 48px; border-radius: 999px; border: 1px solid var(--site-border, #e1e4e8);
  background: var(--site-background, #ffffff); color: var(--site-primary, #1a6083);
}
.fnl-page [data-larkspur-role~="review-carousel"] button[aria-label]:last-of-type { background: var(--site-primary, #1a6083); color: var(--site-on-primary, #ffffff); border-color: transparent; }

/* Sticky FAQ intro and tilted appointment card */
@media (min-width: 992px) {
  .fnl-page [data-larkspur-role~="sticky-col"] { position: sticky; top: 140px; }
}
.fnl-page [data-larkspur-role~="appointment-card"] { transform: rotate(-2deg); transition: transform 500ms cubic-bezier(0.22, 1, 0.36, 1); }
.fnl-page [data-larkspur-role~="appointment-card"]:hover { transform: rotate(0deg); }

/* Ticket stub notches */
.fnl-page [data-larkspur-role~="ticket"] { position: relative; }

@keyframes lk-fade { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
@keyframes lk-drawer { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: none; } }

@media (max-width: 991px) {
  ${mobileHeaderRules('.fnl-page')}
  .fnl-page [data-larkspur-role~="category-tabs"] > div { flex-direction: column !important; }
  .fnl-page [data-larkspur-role~="category-tabs"] [role="tablist"] { flex-direction: row !important; flex-wrap: wrap; min-width: 0 !important; }
  .fnl-page [data-larkspur-role~="category-tabs"] [role="tablist"]::before { display: none; }
}
@media (max-width: 767px) {
  ${mobileTabRules('.fnl-page')}
}
${mobileTabRules('.fnl-page[data-fnl-breakpoint="mobile"]')}
${mobileHeaderRules('.fnl-page[data-fnl-breakpoint="mobile"]')}
${mobileHeaderRules('.fnl-page[data-fnl-breakpoint="tablet"]')}

@media (prefers-reduced-motion: reduce) {
  .fnl-page [data-larkspur] .lk-word > span,
  .fnl-page [data-larkspur-role~="fade-up"] { transition: none !important; opacity: 1 !important; transform: none !important; }
  .fnl-page [data-larkspur-role~="marquee-track"] > div { animation: none !important; }
}
`;
