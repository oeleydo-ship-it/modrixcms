/**
 * Styling and micro-interactions for the `kairo` design collection (`modrix/kairo`),
 * matching the geometric SaaS layout while dynamically inheriting active Theme Settings
 * (`--site-*` CSS variables) across all reusable blocks and subpages.
 */

import { useEffect, type RefObject } from 'react';

/**
 * Interactive header glass scroll state and Advantage card switcher shared
 * between published builder pages (`builder-page.tsx`) and live canvas previews.
 */
export function useKairoMotion(root: RefObject<HTMLDivElement | null>, editable: boolean, revision: unknown): void {
  useEffect(() => {
    const page = root.current;
    if (!page || !page.querySelector('[data-kairo]')) return;

    const cleanups: Array<() => void> = [];

    if (!editable) {
      const headers = Array.from(page.querySelectorAll<HTMLElement>('[data-kairo="header"]'));
      const updateScrolled = (): void => {
        const scrolled = window.scrollY > 24;
        headers.forEach((header) => header.classList.toggle('is-scrolled', scrolled));
      };
      updateScrolled();
      window.addEventListener('scroll', updateScrolled, { passive: true });
      cleanups.push(() => window.removeEventListener('scroll', updateScrolled));

      const motionSections = Array.from(
        page.querySelectorAll<HTMLElement>(
          '[data-kairo="trusted-logos"], [data-kairo="top-features"], [data-kairo="advantages"], ' +
          '[data-kairo="stages"], [data-kairo="why-choose"], [data-kairo="reviews"], ' +
          '[data-kairo="pricing"], [data-kairo="faq"], [data-kairo="values"], ' +
          '[data-kairo="blog"], [data-kairo="cta-download"]',
        ),
      );
      const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
      if (reducedMotion || typeof IntersectionObserver === 'undefined') {
        motionSections.forEach((section) => section.setAttribute('data-kairo-inview', 'true'));
      } else if (motionSections.length > 0) {
        const pendingRafs: number[] = [];
        const observer = new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting || entry.boundingClientRect.top < window.innerHeight * 0.94) {
                entry.target.setAttribute('data-kairo-inview', 'true');
                observer.unobserve(entry.target);
              }
            });
          },
          { threshold: 0.06, rootMargin: '0px 0px -36px 0px' },
        );

        const vh = window.innerHeight || 900;
        motionSections.forEach((section) => {
          section.setAttribute('data-kairo-motion', 'ready');
          const rect = section.getBoundingClientRect();
          if (rect.bottom <= 0) {
            section.setAttribute('data-kairo-inview', 'true');
          } else if (rect.top < vh * 0.94) {
            const raf = requestAnimationFrame(() => {
              const raf2 = requestAnimationFrame(() => section.setAttribute('data-kairo-inview', 'true'));
              pendingRafs.push(raf2);
            });
            pendingRafs.push(raf);
          } else {
            observer.observe(section);
          }
        });

        cleanups.push(() => {
          observer.disconnect();
          pendingRafs.forEach((id) => cancelAnimationFrame(id));
        });
      }
    }

    const stacks = Array.from(page.querySelectorAll<HTMLElement>('[data-kairo="advantages"]'));
    stacks.forEach((section) => {
      const cards = Array.from(section.querySelectorAll<HTMLElement>('[data-kairo-role="advantage-card"]'));
      if (cards.length === 0) return;

      if (!cards.some((card) => card.classList.contains('is-active'))) {
        cards[0].classList.add('is-active');
      }

      cards.forEach((card) => {
        if (!editable) {
          card.setAttribute('tabindex', '0');
        }
        const activate = (): void => {
          cards.forEach((other) => other.classList.toggle('is-active', other === card));
        };
        const onKeyDown = (event: KeyboardEvent): void => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            activate();
          }
        };
        card.addEventListener('click', activate);
        card.addEventListener('keydown', onKeyDown);
        cleanups.push(() => {
          card.removeEventListener('click', activate);
          card.removeEventListener('keydown', onKeyDown);
        });
      });
    });

    return () => {
      cleanups.forEach((fn) => fn());
    };
  }, [root, editable, revision]);
}

const mobileHeaderRules = (scope: string): string => `
  ${scope} [data-kairo="header"] {
    overflow: visible !important;
  }
  ${scope} [data-kairo="header"] [data-kairo-role="header-bar"] {
    position: relative !important;
    width: 100% !important;
    padding: 6px 0 !important;
    min-height: 60px !important;
  }
  ${scope} [data-kairo="header"] [data-kairo-role="header-bar"] img {
    height: 28px !important;
  }
  ${scope} [data-kairo="header"] [data-kairo-role="header-bar"] > .fnl-nested-columns {
    position: static !important;
    width: 100% !important;
    grid-template-columns: minmax(0, 1fr) auto !important;
    align-items: center !important;
    gap: 12px !important;
  }
  ${scope} [data-kairo="header"] [data-kairo-role="header-bar"] [data-fnl-type="navigation"] {
    position: static !important;
  }
  ${scope} [data-kairo="header"] [data-kairo-role="header-bar"] > .fnl-nested-columns > .fnl-block:last-child,
  ${scope} [data-kairo="header"] [data-kairo-role="header-cta"] {
    display: none !important;
  }
  ${scope} [data-kairo="header"] [data-kairo-role="header-bar"] nav[data-mobile="drawer"] {
    position: static !important;
    width: auto !important;
    display: flex !important;
    justify-content: flex-end !important;
    align-items: center !important;
  }
  ${scope} [data-kairo="header"] [data-kairo-role="header-bar"] nav[data-mobile="drawer"] .fnl-nav-toggle {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 40px !important;
    height: 40px !important;
    border-radius: 999px !important;
    border: 0 !important;
    background: var(--site-primary, #0066ff) !important;
    color: var(--site-on-primary, #ffffff) !important;
    margin: 0 !important;
    padding: 0 !important;
    box-shadow: 0 4px 12px rgba(0, 102, 255, 0.25) !important;
  }
  ${scope} [data-kairo="header"] [data-kairo-role="header-bar"] nav[data-mobile="drawer"]:not([data-open="true"]) > ul {
    display: none !important;
  }
  ${scope} [data-kairo="header"] [data-kairo-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] > ul {
    display: flex !important;
    position: absolute !important;
    left: 0 !important;
    right: 0 !important;
    top: calc(100% + 10px) !important;
    min-width: 0 !important;
    width: 100% !important;
    box-sizing: border-box !important;
    margin: 0 !important;
    background: var(--site-header-bg, #ffffff) !important;
    color: var(--site-header-text, #0c0407) !important;
    border: 1px solid var(--site-border, #eaeaea) !important;
    border-radius: 20px !important;
    padding: 10px !important;
    box-shadow: 0 20px 44px rgba(12, 4, 7, 0.14) !important;
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 2px !important;
    z-index: 95 !important;
    max-height: calc(100vh - 96px) !important;
    overflow-y: auto !important;
  }
  ${scope} [data-kairo="header"] [data-kairo-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] > ul > li,
  ${scope} [data-kairo="header"] [data-kairo-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] > ul > li > a {
    display: flex !important;
    align-items: center !important;
    width: 100% !important;
    padding: 10px 14px !important;
    border-radius: 12px !important;
    font-size: 15px !important;
    font-weight: 500 !important;
    color: var(--site-header-text, #0c0407) !important;
    box-sizing: border-box !important;
  }
  ${scope} [data-kairo="header"] [data-kairo-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] > ul > li > a:hover {
    background: var(--site-surface, #f7f7f8) !important;
    color: var(--site-primary, #0066ff) !important;
  }
`;

export const KAIRO_CSS = `
.fnl-page [data-kairo] {
  font-family: var(--site-body-font, 'Inter', system-ui, -apple-system, sans-serif);
}
.fnl-page [data-kairo] h1,
.fnl-page [data-kairo] h2,
.fnl-page [data-kairo] h3,
.fnl-page [data-kairo] h4 {
  font-family: var(--site-heading-font, 'Inter', system-ui, -apple-system, sans-serif);
}

/* Header navigation, CTA row & crisp scrolled glass state */
.fnl-page [data-kairo="header"] {
  transition: box-shadow 220ms ease, background-color 220ms ease;
}
.fnl-page [data-kairo="header"].is-scrolled {
  box-shadow: 0 10px 30px -14px rgba(12, 4, 7, 0.1);
}
.fnl-page [data-kairo="header"] [data-kairo-role="header-bar"] > .fnl-nested-columns {
  align-items: center !important;
}
.fnl-page [data-kairo="header"] nav > ul {
  justify-content: center !important;
  align-items: center !important;
}
.fnl-page [data-kairo="header"] [data-kairo-role="header-cta"] > div {
  display: flex !important;
  align-items: center !important;
  justify-content: flex-end !important;
  flex-wrap: nowrap !important;
  gap: 10px !important;
}
.fnl-page [data-kairo="header"] [data-kairo-role="header-cta"] [data-fnl-type="button"]:first-child a {
  background: transparent !important;
  border: 0 !important;
  box-shadow: none !important;
}
.fnl-page [data-kairo="header"] [data-kairo-role="header-cta"] [data-fnl-type="button"]:first-child a:hover {
  transform: none !important;
  box-shadow: none !important;
  color: var(--site-primary, #0066ff) !important;
}
.fnl-page [data-kairo="header"] nav a {
  transition: color 180ms ease;
}
.fnl-page [data-kairo="header"] nav a:hover {
  color: var(--site-primary, #0066ff) !important;
}

/* Hero trust avatar strip & crisp device mockups */
.fnl-page [data-kairo] [data-kairo-role="trust-row"] > div,
.fnl-page [data-kairo] [data-kairo-role="hero-rating-pill"] > div,
.fnl-page [data-kairo] [data-kairo-role="pill-badge"] > div,
.fnl-page [data-kairo] [data-kairo-role="role-pill"] > div {
  display: flex !important;
  align-items: center !important;
}
.fnl-page [data-kairo] [data-kairo-role="trust-row"] [data-fnl-type="image"] {
  flex-shrink: 0 !important;
}

/* Button arrow glide + Theme Settings radius & hover tokens */
.fnl-page [data-kairo] a[role="button"],
.fnl-page [data-kairo] .fnl-btn,
.fnl-page [data-kairo] [data-fnl-type="button"] a,
.fnl-page [data-kairo] [data-fnl-type="button"] button {
  border-radius: var(--site-button-radius, 999px) !important;
  transition: transform 200ms cubic-bezier(0.22, 1, 0.36, 1), box-shadow 200ms ease, background-color 180ms ease, border-color 180ms ease;
}
.fnl-page [data-kairo] [data-fnl-type="button"] a:hover,
.fnl-page [data-kairo] [data-fnl-type="button"] button:hover {
  transform: var(--site-button-hover-transform, translateY(-2px));
  box-shadow: var(--site-button-hover-shadow, 0 12px 24px -10px var(--site-primary, #0066ff));
}
.fnl-page [data-kairo] [data-fnl-type="button"] a svg,
.fnl-page [data-kairo] [data-fnl-type="button"] button svg {
  transition: transform 180ms ease;
}
.fnl-page [data-kairo] [data-fnl-type="button"] a:hover svg,
.fnl-page [data-kairo] [data-fnl-type="button"] button:hover svg {
  transform: translateX(3px);
}

/* Hero entrance choreography + dual phone float */
@keyframes kairo-hero-shell {
  from { opacity: 0; transform: translateY(12px) scale(0.988); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes kairo-rise-up {
  from { opacity: 0; transform: translateY(28px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes kairo-device-rise {
  from { opacity: 0; transform: translateY(36px) scale(0.955); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes kairo-ui-settle {
  from { opacity: 0; transform: translateY(14px) scale(0.96); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
.fnl-page:not(.fnl-editable) [data-kairo="hero"] > .fnl-section-inner > .fnl-row {
  animation: kairo-hero-shell 580ms cubic-bezier(0.22, 1, 0.36, 1) both;
}
.fnl-page:not(.fnl-editable) [data-kairo="hero"] .fnl-column:nth-child(1) > .fnl-block:nth-child(1) {
  animation: kairo-rise-up 600ms cubic-bezier(0.22, 1, 0.36, 1) 60ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo="hero"] .fnl-column:nth-child(1) > .fnl-block:nth-child(2) {
  animation: kairo-rise-up 640ms cubic-bezier(0.22, 1, 0.36, 1) 140ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo="hero"] .fnl-column:nth-child(1) > .fnl-block:nth-child(3) {
  animation: kairo-rise-up 640ms cubic-bezier(0.22, 1, 0.36, 1) 220ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo="hero"] .fnl-column:nth-child(1) > .fnl-block:nth-child(4) {
  animation: kairo-rise-up 640ms cubic-bezier(0.22, 1, 0.36, 1) 300ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo="hero"] .fnl-column:nth-child(1) > .fnl-block:nth-child(5) {
  animation: kairo-rise-up 640ms cubic-bezier(0.22, 1, 0.36, 1) 380ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo="hero"] [data-kairo-role="hero-devices"] {
  animation: kairo-device-rise 780ms cubic-bezier(0.16, 1, 0.3, 1) 180ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo="page-hero"] .fnl-column > .fnl-block:nth-child(1) {
  animation: kairo-rise-up 580ms cubic-bezier(0.22, 1, 0.36, 1) 40ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo="page-hero"] .fnl-column > .fnl-block:nth-child(2) {
  animation: kairo-rise-up 620ms cubic-bezier(0.22, 1, 0.36, 1) 120ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo="page-hero"] .fnl-column > .fnl-block:nth-child(3) {
  animation: kairo-rise-up 620ms cubic-bezier(0.22, 1, 0.36, 1) 200ms both;
}

/* Scroll-triggered entrance choreography for Top Features ("service" bento cards) & content sections */
.fnl-page:not(.fnl-editable) [data-kairo-motion="ready"]:not([data-kairo-inview="true"]) .fnl-row > .fnl-column {
  opacity: 0;
}
.fnl-page:not(.fnl-editable) [data-kairo="top-features"][data-kairo-motion="ready"]:not([data-kairo-inview="true"]) > .fnl-section-inner > .fnl-row:nth-child(1) .fnl-column {
  opacity: 1;
}
.fnl-page:not(.fnl-editable) [data-kairo="top-features"][data-kairo-motion="ready"]:not([data-kairo-inview="true"]) > .fnl-section-inner > .fnl-row:nth-child(1) .fnl-column > .fnl-block,
.fnl-page:not(.fnl-editable) [data-kairo="top-features"][data-kairo-motion="ready"]:not([data-kairo-inview="true"]) [data-kairo-role="bento-card"] [data-fnl-type="image"] {
  opacity: 0;
}
.fnl-page:not(.fnl-editable) [data-kairo-motion="ready"][data-kairo-inview="true"] .fnl-row > .fnl-column {
  animation: kairo-rise-up 660ms cubic-bezier(0.22, 1, 0.36, 1) both;
}
.fnl-page:not(.fnl-editable) [data-kairo="top-features"][data-kairo-inview="true"] > .fnl-section-inner > .fnl-row:nth-child(1) .fnl-column {
  animation: none !important;
}
.fnl-page:not(.fnl-editable) [data-kairo="top-features"][data-kairo-inview="true"] > .fnl-section-inner > .fnl-row:nth-child(1) .fnl-column > .fnl-block:nth-child(1) {
  animation: kairo-rise-up 560ms cubic-bezier(0.22, 1, 0.36, 1) 20ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo="top-features"][data-kairo-inview="true"] > .fnl-section-inner > .fnl-row:nth-child(1) .fnl-column > .fnl-block:nth-child(2) {
  animation: kairo-rise-up 620ms cubic-bezier(0.22, 1, 0.36, 1) 100ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo="top-features"][data-kairo-inview="true"] > .fnl-section-inner > .fnl-row:nth-child(1) .fnl-column > .fnl-block:nth-child(3) {
  animation: kairo-rise-up 620ms cubic-bezier(0.22, 1, 0.36, 1) 180ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo="top-features"][data-kairo-inview="true"] > .fnl-section-inner > .fnl-row:nth-child(2) > .fnl-column:nth-child(1) {
  animation: kairo-rise-up 680ms cubic-bezier(0.22, 1, 0.36, 1) 140ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo="top-features"][data-kairo-inview="true"] > .fnl-section-inner > .fnl-row:nth-child(2) > .fnl-column:nth-child(2) {
  animation: kairo-rise-up 680ms cubic-bezier(0.22, 1, 0.36, 1) 260ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo="top-features"][data-kairo-inview="true"] > .fnl-section-inner > .fnl-row:nth-child(2) > .fnl-column:nth-child(3) {
  animation: kairo-rise-up 680ms cubic-bezier(0.22, 1, 0.36, 1) 380ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo="top-features"][data-kairo-inview="true"] > .fnl-section-inner > .fnl-row:nth-child(2) > .fnl-column:nth-child(1) [data-fnl-type="image"] {
  animation: kairo-ui-settle 660ms cubic-bezier(0.16, 1, 0.3, 1) 240ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo="top-features"][data-kairo-inview="true"] > .fnl-section-inner > .fnl-row:nth-child(2) > .fnl-column:nth-child(2) [data-fnl-type="image"] {
  animation: kairo-ui-settle 660ms cubic-bezier(0.16, 1, 0.3, 1) 360ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo="top-features"][data-kairo-inview="true"] > .fnl-section-inner > .fnl-row:nth-child(2) > .fnl-column:nth-child(3) [data-fnl-type="image"] {
  animation: kairo-ui-settle 660ms cubic-bezier(0.16, 1, 0.3, 1) 480ms both;
}
.fnl-page:not(.fnl-editable) [data-kairo-motion="ready"][data-kairo-inview="true"] .fnl-row > .fnl-column:nth-child(2) {
  animation-delay: 120ms;
}
.fnl-page:not(.fnl-editable) [data-kairo-motion="ready"][data-kairo-inview="true"] .fnl-row > .fnl-column:nth-child(3) {
  animation-delay: 240ms;
}

.fnl-page:not(.fnl-editable) [data-kairo-role="hero-devices"] img {
  animation: kairo-float 6s ease-in-out infinite;
}
@keyframes kairo-float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-7px); }
}

/* Continuous partner logo strip + role chips marquee */
.fnl-page [data-kairo-role="marquee-viewport"],
.fnl-page [data-kairo-role="why-chips-card"] {
  overflow: hidden !important;
  -webkit-mask-image: linear-gradient(90deg, transparent, #000 7%, #000 93%, transparent);
  mask-image: linear-gradient(90deg, transparent, #000 7%, #000 93%, transparent);
}
.fnl-page [data-kairo-role="marquee-track"] > div,
.fnl-page [data-kairo-role="marquee-reverse"] > div {
  display: flex !important;
  flex-wrap: nowrap !important;
  width: max-content !important;
  flex-direction: row !important;
}
.fnl-page:not(.fnl-editable) [data-kairo-role="marquee-track"] > div {
  animation: kairo-marquee-x 34s linear infinite;
}
.fnl-page:not(.fnl-editable) [data-kairo-role="marquee-reverse"] > div {
  animation: kairo-marquee-rev 38s linear infinite;
}
.fnl-page [data-kairo-role="marquee-viewport"]:hover [data-kairo-role="marquee-track"] > div,
.fnl-page [data-kairo-role="why-chips-card"]:hover [data-kairo-role="marquee-track"] > div,
.fnl-page [data-kairo-role="why-chips-card"]:hover [data-kairo-role="marquee-reverse"] > div {
  animation-play-state: paused;
}
@keyframes kairo-marquee-x {
  from { transform: translate3d(0, 0, 0); }
  to { transform: translate3d(-50%, 0, 0); }
}
@keyframes kairo-marquee-rev {
  from { transform: translate3d(-50%, 0, 0); }
  to { transform: translate3d(0, 0, 0); }
}

/* Cards elevation + crisp active states */
.fnl-page [data-kairo] [data-kairo-role="bento-card"],
.fnl-page [data-kairo] [data-kairo-role="stage-card"],
.fnl-page [data-kairo] [data-kairo-role="review-card"],
.fnl-page [data-kairo] [data-kairo-role="value-card"],
.fnl-page [data-kairo] [data-kairo-role="pricing-card"],
.fnl-page [data-kairo] [data-kairo-role="pricing-card-pro"] {
  transition: transform 240ms cubic-bezier(0.22, 1, 0.36, 1), box-shadow 240ms ease, border-color 200ms ease;
}
.fnl-page [data-kairo] [data-kairo-role="bento-card"]:hover,
.fnl-page [data-kairo] [data-kairo-role="stage-card"]:hover,
.fnl-page [data-kairo] [data-kairo-role="review-card"]:hover,
.fnl-page [data-kairo] [data-kairo-role="value-card"]:hover {
  transform: var(--site-card-hover-transform, translateY(-4px));
  box-shadow: var(--site-shadow, 0 12px 30px -12px rgba(12, 4, 7, 0.14));
}

/* Advantages interactive outline cards */
.fnl-page [data-kairo="advantages"] [data-kairo-role="advantage-card"] {
  transition: border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
}
.fnl-page [data-kairo="advantages"] [data-kairo-role="advantage-card"]:hover {
  border-color: color-mix(in srgb, var(--site-primary, #0066ff) 50%, var(--site-border, #eaeaea)) !important;
}
.fnl-page [data-kairo="advantages"]:not(:has([data-kairo-role="advantage-card"].is-active)) .fnl-column:nth-child(2) > [data-kairo-role="advantage-card"]:nth-child(4),
.fnl-page [data-kairo="advantages"] [data-kairo-role="advantage-card"].is-active {
  border-color: var(--site-primary, #0066ff) !important;
  box-shadow: 0 10px 26px -12px rgba(0, 102, 255, 0.25) !important;
}

/* Pricing Monthly/Yearly floating pill tabs & Pro Kairo Recommends bottom notch */
.fnl-page [data-kairo="pricing"] [role="tablist"] {
  display: inline-flex !important;
  flex-direction: row !important;
  flex-wrap: nowrap !important;
  width: fit-content !important;
  max-width: 100% !important;
  margin: 0 auto 38px !important;
  padding: 5px !important;
  gap: 4px !important;
  background: var(--site-background, #ffffff) !important;
  border: 1px solid var(--site-border, #eaeaea) !important;
  border-radius: 999px !important;
  box-shadow: 0 4px 14px rgba(12, 4, 7, 0.05) !important;
}
.fnl-page [data-kairo="pricing"] [role="tab"] {
  border: 0 !important;
  border-radius: 999px !important;
  padding: 10px 22px !important;
  font-size: 14px !important;
  font-weight: 600 !important;
  color: var(--site-heading, #0c0407) !important;
  background: transparent !important;
  cursor: pointer;
  white-space: nowrap !important;
  transition: background-color 180ms ease, color 180ms ease !important;
}
.fnl-page [data-kairo="pricing"] [role="tab"][aria-selected="true"] {
  background: var(--site-primary, #0066ff) !important;
  color: var(--site-on-primary, #ffffff) !important;
  box-shadow: 0 6px 16px -4px rgba(0, 102, 255, 0.35) !important;
}
.fnl-page [data-kairo="pricing"] [data-fnl-type="tabs"] > div {
  align-items: center !important;
}
.fnl-page [data-kairo="pricing"] [role="tabpanel"] {
  width: 100% !important;
  border: 0 !important;
  padding: 0 0 18px 0 !important;
  background: transparent !important;
}
.fnl-page [data-kairo="pricing"] [data-kairo-role="pricing-card-pro"] {
  position: relative !important;
  overflow: visible !important;
  box-shadow: 0 18px 40px -18px rgba(0, 102, 255, 0.28) !important;
}
.fnl-page [data-kairo="pricing"] [data-kairo-role="recommend-pill"] {
  margin-bottom: -48px !important;
  box-shadow: 0 10px 22px -6px rgba(0, 102, 255, 0.45) !important;
}

/* Sticky FAQ intro column + boxed card items */
@media (min-width: 961px) {
  .fnl-page [data-kairo] .fnl-column:has([data-kairo-role="sticky-col"]),
  .fnl-page [data-kairo] [data-kairo-role="sticky-col"] {
    position: sticky !important;
    top: 108px !important;
    align-self: flex-start !important;
  }
}
.fnl-page [data-kairo="faq"] [data-kairo-role="faq-list"] > div > div > div > div {
  background: var(--site-background, #ffffff) !important;
  border: 1px solid var(--site-border, #eaeaea) !important;
  border-radius: 16px !important;
  padding: 4px 20px !important;
  margin-bottom: 12px !important;
  transition: border-color 180ms ease, box-shadow 180ms ease;
}
.fnl-page [data-kairo="faq"] [data-kairo-role="faq-list"] > div > div > div > div:hover {
  border-color: var(--site-primary, #0066ff) !important;
}
.fnl-page [data-kairo="faq"] [data-kairo-role="faq-list"] button[aria-expanded] {
  padding: 16px 0 !important;
  font-size: 16px !important;
  color: var(--site-heading, #0c0407) !important;
}
.fnl-page [data-kairo="faq"] [data-kairo-role="faq-list"] button[aria-expanded] svg {
  color: var(--site-primary, #0066ff) !important;
}

/* Mobile responsive rules for live pages and visual builder mobile toggle */
@media (max-width: 960px) {
  ${mobileHeaderRules('.fnl-page')}
  .fnl-page [data-kairo="pricing"] [data-kairo-role="recommend-pill"] {
    margin-bottom: 0 !important;
  }
}
${mobileHeaderRules('.fnl-page.fnl-bp-mobile')}

@media (prefers-reduced-motion: reduce) {
  .fnl-page:not(.fnl-editable) [data-kairo="hero"] > .fnl-section-inner > .fnl-row,
  .fnl-page:not(.fnl-editable) [data-kairo="hero"] .fnl-column > .fnl-block,
  .fnl-page:not(.fnl-editable) [data-kairo="hero"] [data-kairo-role="hero-devices"],
  .fnl-page:not(.fnl-editable) [data-kairo="page-hero"] .fnl-column > .fnl-block,
  .fnl-page:not(.fnl-editable) [data-kairo="trusted-logos"] .fnl-column,
  .fnl-page:not(.fnl-editable) [data-kairo-motion] .fnl-row > .fnl-column,
  .fnl-page:not(.fnl-editable) [data-kairo="top-features"] .fnl-column > .fnl-block,
  .fnl-page:not(.fnl-editable) [data-kairo="top-features"] [data-kairo-role="bento-card"] [data-fnl-type="image"],
  .fnl-page:not(.fnl-editable) [data-kairo-role="hero-devices"] img,
  .fnl-page:not(.fnl-editable) [data-kairo-role="marquee-track"] > div,
  .fnl-page:not(.fnl-editable) [data-kairo-role="marquee-reverse"] > div {
    animation: none !important;
    opacity: 1 !important;
    transform: none !important;
  }
}
`;
