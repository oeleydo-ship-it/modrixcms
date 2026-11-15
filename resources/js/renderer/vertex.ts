/**
 * Styling, drop-down menu interactions, module filtering, and
 * reveal motion for the `vertex` design collection (`modrix/vertex`), following
 * Theme Settings (`--site-*`) and Customize (`--vertex-*`) variables dynamically.
 */

import { useEffect, type RefObject } from 'react';

const QUICK_ROUTES = [
  { label: 'Features', desc: 'What the free core includes: store, checkout, orders, pages and settings', url: '/platform', tags: 'features products checkout orders invoices pages seo languages roles backups' },
  { label: 'Themes & modules', desc: 'Install themes and modules from the admin, or upload a ZIP file', url: '/modules', tags: 'themes modules extensions payments paypal razorpay reviews newsletter' },
  { label: 'Use cases', desc: 'Small shops, agencies and developers', url: '/solutions', tags: 'use cases shops agencies developers clients' },
  { label: 'Try the demo', desc: 'Import the example pages of this theme', url: '/demo', tags: 'demo import example pages' },
  { label: 'Get started', desc: 'Install commands, hosting needs and the MIT license', url: '/developers', tags: 'install composer artisan hosting license php mysql' },
];

export function useVertexMotion(root: RefObject<HTMLDivElement | null>, editable: boolean, revision: unknown): void {
  useEffect(() => {
    const page = root.current;
    if (!page || !page.querySelector('[data-vertex]')) return;

    const cleanups: Array<() => void> = [];

    if (!editable) {
      // Sticky scroll shadow for header
      const headers = Array.from(page.querySelectorAll<HTMLElement>('[data-vertex="header"]'));
      const onScroll = (): void => {
        const scrolled = window.scrollY > 18;
        headers.forEach((h) => h.classList.toggle('is-scrolled', scrolled));
      };
      onScroll();
      window.addEventListener('scroll', onScroll, { passive: true });
      cleanups.push(() => window.removeEventListener('scroll', onScroll));

      // Desktop hover-intent + keyboard/viewport positioning for mega-menu dropdowns
      const navDetails = Array.from(page.querySelectorAll<HTMLDetailsElement>('[data-vertex="header"] details.fnl-nav-details'));
      const hoverTimers = new WeakMap<HTMLDetailsElement, number>();

      const positionDropdown = (details: HTMLDetailsElement): void => {
        const panel = details.querySelector<HTMLElement>('[data-fnl-dropdown]');
        if (!panel) return;
        panel.style.removeProperty('--vertex-shift-x');
        if (window.innerWidth <= 960 || page.dataset['fnlBreakpoint'] === 'mobile') return;
        const rect = panel.getBoundingClientRect();
        const pad = 16;
        let shift = 0;
        if (rect.left < pad) {
          shift = pad - rect.left;
        } else if (rect.right > window.innerWidth - pad) {
          shift = window.innerWidth - pad - rect.right;
        }
        if (shift !== 0) {
          panel.style.setProperty('--vertex-shift-x', `${Math.round(shift)}px`);
        }
      };

      const closeOthers = (except?: HTMLDetailsElement): void => {
        navDetails.forEach((d) => {
          if (d !== except && d.open) {
            d.open = false;
          }
        });
      };

      navDetails.forEach((details) => {
        const onEnter = (): void => {
          if (window.innerWidth <= 960 || page.dataset['fnlBreakpoint'] === 'mobile') return;
          const t = hoverTimers.get(details);
          if (t !== undefined) {
            window.clearTimeout(t);
            hoverTimers.delete(details);
          }
          closeOthers(details);
          if (!details.open) {
            details.open = true;
          }
          requestAnimationFrame(() => positionDropdown(details));
        };

        const onLeave = (): void => {
          if (window.innerWidth <= 960 || page.dataset['fnlBreakpoint'] === 'mobile') return;
          const t = window.setTimeout(() => {
            details.open = false;
          }, 140);
          hoverTimers.set(details, t);
        };

        const onToggle = (): void => {
          if (details.open) {
            closeOthers(details);
            requestAnimationFrame(() => positionDropdown(details));
          }
        };

        details.addEventListener('mouseenter', onEnter);
        details.addEventListener('mouseleave', onLeave);
        details.addEventListener('toggle', onToggle);

        cleanups.push(() => {
          const t = hoverTimers.get(details);
          if (t !== undefined) window.clearTimeout(t);
          details.removeEventListener('mouseenter', onEnter);
          details.removeEventListener('mouseleave', onLeave);
          details.removeEventListener('toggle', onToggle);
        });
      });

      // Header inline quick-search overlay on search button click
      const searchBtns = Array.from(page.querySelectorAll<HTMLElement>('[data-vertex-role="header-search"] a, [data-vertex-role="header-search"] button'));
      let searchPopover: HTMLElement | null = null;

      const closeSearchPopover = (): void => {
        if (searchPopover) {
          searchPopover.remove();
          searchPopover = null;
        }
      };

      const openSearchPopover = (anchorHeader: HTMLElement): void => {
        if (searchPopover) {
          closeSearchPopover();
          return;
        }
        const bar = document.createElement('div');
        bar.className = 'vertex-search-popover';
        bar.setAttribute('role', 'dialog');
        bar.setAttribute('aria-label', 'Quick search Modrix Commerce');
        bar.innerHTML = `
          <div class="vertex-search-inner">
            <div class="vertex-search-row">
              <input type="search" class="vertex-search-input" placeholder="Search features, modules, the demo..." aria-label="Search Modrix Commerce pages and features" />
              <button type="button" class="vertex-search-close" aria-label="Close search">&times;</button>
            </div>
            <div class="vertex-search-results"></div>
          </div>
        `;
        anchorHeader.appendChild(bar);
        searchPopover = bar;

        const input = bar.querySelector<HTMLInputElement>('.vertex-search-input');
        const resultsEl = bar.querySelector<HTMLElement>('.vertex-search-results');
        const closeBtn = bar.querySelector<HTMLButtonElement>('.vertex-search-close');

        const renderResults = (query: string): void => {
          if (!resultsEl) return;
          const q = query.trim().toLowerCase();
          const matched = QUICK_ROUTES.filter((item) =>
            !q || `${item.label} ${item.desc} ${item.tags}`.toLowerCase().includes(q),
          );
          resultsEl.innerHTML = matched.length === 0
            ? '<p class="vertex-search-empty">No matching pages. Try searching "payments", "install", or "demo".</p>'
            : matched.map((item) => `
              <a class="vertex-search-hit" href="${item.url}">
                <strong>${item.label}</strong>
                <span>${item.desc}</span>
              </a>
            `).join('');
        };

        renderResults('');
        input?.focus();
        input?.addEventListener('input', () => renderResults(input.value));
        closeBtn?.addEventListener('click', closeSearchPopover);
      };

      searchBtns.forEach((btn) => {
        const header = btn.closest<HTMLElement>('[data-vertex="header"]');
        if (!header) return;
        const onClick = (e: Event): void => {
          e.preventDefault();
          closeOthers();
          openSearchPopover(header);
        };
        btn.addEventListener('click', onClick);
        cleanups.push(() => btn.removeEventListener('click', onClick));
      });

      const onDocClick = (e: MouseEvent): void => {
        if (searchPopover && e.target instanceof Node && !searchPopover.contains(e.target) && !(e.target instanceof Element && e.target.closest('[data-vertex-role="header-search"]'))) {
          closeSearchPopover();
        }
      };
      const onEsc = (e: KeyboardEvent): void => {
        if (e.key === 'Escape') {
          closeSearchPopover();
          closeOthers();
        }
      };
      document.addEventListener('click', onDocClick);
      document.addEventListener('keydown', onEsc);
      cleanups.push(() => {
        document.removeEventListener('click', onDocClick);
        document.removeEventListener('keydown', onEsc);
        closeSearchPopover();
      });

      // Module directory category filter buttons (/modules)
      const moduleFilterBars = Array.from(page.querySelectorAll<HTMLElement>('[data-vertex-role="module-filters"]'));
      moduleFilterBars.forEach((bar) => {
        const section = bar.closest<HTMLElement>('[data-vertex]');
        if (!section) return;
        const buttons = Array.from(bar.querySelectorAll<HTMLElement>('[data-vertex-filter]'));
        const cards = Array.from(section.querySelectorAll<HTMLElement>('[data-vertex-module-cat]'));

        const applyFilter = (cat: string): void => {
          buttons.forEach((b) => b.classList.toggle('is-active', b.dataset['vertexFilter'] === cat));
          cards.forEach((card) => {
            const match = cat === 'all' || (card.dataset['vertexModuleCat'] ?? '').split(',').includes(cat);
            card.style.display = match ? '' : 'none';
          });
        };

        buttons.forEach((btn) => {
          const clickTarget = btn.querySelector<HTMLElement>('a, button') ?? btn;
          const onFilterClick = (e: Event): void => {
            e.preventDefault();
            applyFilter(btn.dataset['vertexFilter'] ?? 'all');
          };
          clickTarget.addEventListener('click', onFilterClick);
          cleanups.push(() => clickTarget.removeEventListener('click', onFilterClick));
        });
      });

      // CLI copy-to-clipboard buttons (/demo & /developers)
      const copyTriggers = Array.from(page.querySelectorAll<HTMLElement>('[data-vertex-copy]'));
      copyTriggers.forEach((holder) => {
        const textToCopy = holder.dataset['vertexCopy'] ?? '';
        const btn = holder.querySelector<HTMLElement>('a, button') ?? holder;
        const onCopy = async (e: Event): Promise<void> => {
          if (!textToCopy) return;
          e.preventDefault();
          try {
            await navigator.clipboard.writeText(textToCopy);
            const label = btn.textContent ?? 'Copy';
            btn.setAttribute('data-original-label', label);
            btn.textContent = 'Copied!';
            window.setTimeout(() => {
              const orig = btn.getAttribute('data-original-label');
              if (orig) btn.textContent = orig;
            }, 1800);
          } catch {
            // Fallback silently when clipboard is denied
          }
        };
        btn.addEventListener('click', onCopy);
        cleanups.push(() => btn.removeEventListener('click', onCopy));
      });

      // Smooth viewport-aware reveal animations
      const revealTargets = Array.from(
        page.querySelectorAll<HTMLElement>(
          '[data-vertex="spotlight"] [data-vertex-role="spotlight-card"], ' +
          '[data-vertex="solutions"] [data-vertex-role="solve-card"], ' +
          '[data-vertex="trending"] [data-vertex-role="topic-card"], ' +
          '[data-vertex="domains"] [data-vertex-role="domain-card"], ' +
          '[data-vertex="modules"] [data-vertex-role="module-card"]',
        ),
      );
      const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

      if (reducedMotion || typeof IntersectionObserver === 'undefined') {
        revealTargets.forEach((el) => el.setAttribute('data-vertex-reveal', 'visible'));
      } else if (revealTargets.length > 0) {
        const observer = new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting || entry.boundingClientRect.top < window.innerHeight * 0.95) {
                entry.target.setAttribute('data-vertex-reveal', 'visible');
                observer.unobserve(entry.target);
              }
            });
          },
          { threshold: 0.06, rootMargin: '0px 0px -24px 0px' },
        );

        const vh = window.innerHeight || 900;
        revealTargets.forEach((el, idx) => {
          const rect = el.getBoundingClientRect();
          if (rect.top < vh * 0.94) {
            el.setAttribute('data-vertex-reveal', 'visible');
          } else {
            el.style.transitionDelay = `${(idx % 3) * 60}ms`;
            el.setAttribute('data-vertex-reveal', 'pending');
            observer.observe(el);
          }
        });

        cleanups.push(() => observer.disconnect());
      }
    }

    return () => {
      cleanups.forEach((fn) => fn());
    };
  }, [root, editable, revision]);
}

const mobileHeaderRules = (scope: string): string => `
  ${scope} [data-vertex="header"] [data-vertex-role="utility-bar"] {
    display: none !important;
  }
  ${scope} [data-vertex="header"] [data-vertex-role="header-bar"] {
    min-height: 62px !important;
    padding: 8px 0 !important;
  }
  ${scope} [data-vertex="header"] [data-vertex-role="header-bar"] > .fnl-nested-columns {
    position: static !important;
    width: 100% !important;
    grid-template-columns: minmax(0, 1fr) auto !important;
    align-items: center !important;
    gap: 12px !important;
  }
  ${scope} [data-vertex="header"] [data-vertex-role="header-bar"] > .fnl-nested-columns > .fnl-block:last-child,
  ${scope} [data-vertex="header"] [data-vertex-role="header-actions"] {
    display: none !important;
  }
  ${scope} [data-vertex="header"] [data-vertex-role="header-bar"] img {
    height: 30px !important;
  }
  ${scope} [data-vertex="header"] [data-vertex-role="header-bar"] nav[data-mobile="drawer"] {
    position: static !important;
    width: auto !important;
    display: flex !important;
    justify-content: flex-end !important;
    align-items: center !important;
  }
  ${scope} [data-vertex="header"] [data-vertex-role="header-bar"] nav[data-mobile="drawer"] .fnl-nav-toggle {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 38px !important;
    height: 38px !important;
    border-radius: 4px !important;
    border: 1px solid var(--site-border, #d8e0e8) !important;
    background: var(--site-background, #ffffff) !important;
    color: var(--site-heading, #1b2430) !important;
    margin: 0 !important;
  }
  ${scope} [data-vertex="header"] [data-vertex-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] > ul {
    display: flex !important;
    position: absolute !important;
    left: 0 !important;
    right: 0 !important;
    top: calc(100% + 1px) !important;
    width: 100% !important;
    min-width: 0 !important;
    max-width: 100% !important;
    background: var(--site-background, #ffffff) !important;
    color: var(--site-heading, #1b2430) !important;
    border: 1px solid var(--site-border, #dfe5ec) !important;
    border-top: 3px solid var(--site-primary, #006994) !important;
    border-radius: 0 0 6px 6px !important;
    padding: 12px 16px 18px !important;
    box-shadow: 0 20px 44px rgba(12, 35, 64, 0.16) !important;
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 2px !important;
    z-index: 95 !important;
    max-height: calc(100dvh - 80px) !important;
    overflow-y: auto !important;
  }
  ${scope} [data-vertex="header"] [data-vertex-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] > ul > li,
  ${scope} [data-vertex="header"] [data-vertex-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] details {
    width: 100% !important;
    display: block !important;
  }
  ${scope} [data-vertex="header"] [data-vertex-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] summary.fnl-nav-summary,
  ${scope} [data-vertex="header"] [data-vertex-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] > ul > li > a {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    width: 100% !important;
    padding: 11px 8px !important;
    font-size: 14.5px !important;
    font-weight: 500 !important;
    border-bottom: 1px solid var(--site-border, #edf1f5) !important;
    color: var(--site-heading, #1b2430) !important;
  }
  ${scope} [data-vertex="header"] [data-vertex-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] [data-fnl-dropdown] {
    position: static !important;
    transform: none !important;
    left: auto !important;
    right: auto !important;
    width: 100% !important;
    min-width: 0 !important;
    grid-template-columns: minmax(0, 1fr) !important;
    box-shadow: none !important;
    border: 0 !important;
    border-left: 2px solid var(--site-primary, #006994) !important;
    background: var(--site-surface, #f3f5f8) !important;
    padding: 8px 10px !important;
    margin: 4px 0 8px !important;
    gap: 6px !important;
  }
  ${scope} [data-vertex="header"] [data-vertex-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] .fnl-nav-dropdown-icon {
    width: 30px !important;
    height: 30px !important;
  }
  ${scope} [data-vertex="hero"] [data-vertex-role="hero-split"] > .fnl-nested-columns {
    grid-template-columns: minmax(0, 1fr) !important;
    gap: 28px !important;
  }
  ${scope} [data-vertex="announcement"] [data-vertex-role="banner-row"] > .fnl-nested-columns {
    grid-template-columns: minmax(0, 1fr) !important;
    gap: 16px !important;
  }
  ${scope} [data-vertex="trending"] [data-vertex-role="topic-centered-wrap"] {
    max-width: 100% !important;
    width: 100% !important;
  }
`;

export const VERTEX_CSS = `
.fnl-page [data-vertex] {
  font-family: var(--site-body-font, 'Plus Jakarta Sans', 'Inter', system-ui, sans-serif);
}
.fnl-page [data-vertex] h1,
.fnl-page [data-vertex] h2,
.fnl-page [data-vertex] h3,
.fnl-page [data-vertex] h4 {
  font-family: var(--site-heading-font, 'Plus Jakarta Sans', 'Inter', system-ui, sans-serif);
}

/* Header & top utility strip */
.fnl-page [data-vertex="header"] {
  position: var(--site-header-position, sticky);
  top: 0;
  z-index: 80;
  overflow: visible !important;
  background: var(--site-header-bg, #ffffff) !important;
  border-bottom: var(--site-header-border, 1px solid var(--site-border, #e2e8f0)) !important;
  transition: box-shadow 0.22s ease;
}
.fnl-page [data-vertex="header"].is-scrolled {
  box-shadow: 0 8px 28px -8px rgba(12, 35, 64, 0.14);
}
.fnl-page [data-vertex="header"] [data-vertex-role="utility-bar"] {
  display: var(--vertex-utility-display, block);
  border-bottom: 1px solid color-mix(in srgb, var(--site-border, #e2e8f0) 65%, transparent);
  padding: 5px 0 4px;
}
.fnl-page [data-vertex="header"] [data-vertex-role="utility-bar"] a {
  text-decoration: none;
  font-size: 12px;
  color: var(--site-muted, #64748b);
  transition: color 0.16s ease;
}
.fnl-page [data-vertex="header"] [data-vertex-role="utility-bar"] a:hover {
  color: var(--site-primary, #006994);
}
.fnl-page [data-vertex="header"] [data-vertex-role="header-bar"] {
  position: relative !important;
  width: 100% !important;
  overflow: visible !important;
  min-height: calc(var(--site-header-height, 84px) - 26px);
  display: flex;
  align-items: center;
}
.fnl-page [data-vertex="header"] [data-vertex-role="header-bar"] img {
  height: 36px !important;
  width: auto !important;
  max-width: none !important;
  display: block;
}
@media (min-width: 961px) {
  .fnl-page:not([data-fnl-breakpoint="mobile"]) [data-vertex="header"] [data-vertex-role="header-bar"] > .fnl-nested-columns {
    grid-template-columns: auto minmax(0, 1fr) auto !important;
    align-items: center !important;
    gap: 28px !important;
  }
  .fnl-page:not([data-fnl-breakpoint="mobile"]) [data-vertex="header"] nav > ul {
    flex-wrap: nowrap !important;
    justify-content: flex-start !important;
    align-items: center !important;
    gap: var(--site-nav-gap, 28px) !important;
    margin-left: 16px !important;
  }
  .fnl-page:not([data-fnl-breakpoint="mobile"]) [data-vertex="header"] nav > ul > li {
    flex-shrink: 0 !important;
  }
}

/* Desktop navigation links & dropdown mega-menus */
.fnl-page [data-vertex="header"] nav > ul > li > a,
.fnl-page [data-vertex="header"] nav .fnl-nav-details > .fnl-nav-summary {
  display: inline-flex !important;
  align-items: center !important;
  gap: 5px;
  padding: 10px 2px !important;
  font-size: 14.5px;
  font-weight: var(--site-nav-weight, 500);
  color: var(--site-header-text, #333e48) !important;
  white-space: nowrap !important;
  transition: color 0.16s ease;
}
.fnl-page [data-vertex="header"] nav .fnl-nav-summary::-webkit-details-marker {
  display: none;
}
.fnl-page [data-vertex="header"] nav > ul > li > a:hover,
.fnl-page [data-vertex="header"] nav .fnl-nav-summary:hover,
.fnl-page [data-vertex="header"] nav .fnl-nav-details[open] > .fnl-nav-summary {
  color: var(--site-primary, #006994) !important;
}
.fnl-page [data-vertex="header"] .fnl-nav-caret {
  display: inline-block;
  width: 6px;
  height: 6px;
  font-size: 0 !important;
  line-height: 0;
  border-right: 1.5px solid currentColor;
  border-bottom: 1.5px solid currentColor;
  transform: rotate(45deg) translateY(-1px);
  margin-left: 3px;
  transition: transform 0.2s ease;
}
.fnl-page [data-vertex="header"] .fnl-nav-details[open] .fnl-nav-caret {
  transform: rotate(-135deg) translateY(-1px);
}
.fnl-page [data-vertex="header"] [data-fnl-dropdown] {
  --fnl-dropdown-top: calc(100% + 10px);
  --fnl-dropdown-left: 50%;
  --fnl-dropdown-right: auto;
  --fnl-dropdown-width: min(var(--vertex-dropdown-width, 560px), 92vw);
  --fnl-dropdown-cols: var(--vertex-dropdown-cols, repeat(2, minmax(0, 1fr)));
  --fnl-dropdown-gap: 10px;
  --fnl-dropdown-padding: 18px;
  --fnl-dropdown-radius: min(var(--site-card-radius, 6px), 8px);
  --fnl-dropdown-border: 1px solid var(--site-border, #dce3ec);
  --fnl-dropdown-bg: var(--site-background, #ffffff);
  --fnl-dropdown-color: var(--site-heading, #1b2430);
  --fnl-dropdown-shadow: 0 22px 48px -12px rgba(12, 35, 64, 0.22);
  border-top: 3px solid var(--site-primary, #006994) !important;
  transform: translateX(calc(-50% + var(--vertex-shift-x, 0px)));
  animation: vertex-dropdown-in 0.18s cubic-bezier(0.22, 1, 0.36, 1);
}
.fnl-page [data-vertex="header"] [data-fnl-dropdown] * {
  white-space: normal !important;
}
.fnl-page [data-vertex="header"] [data-fnl-dropdown] .fnl-nav-dropdown-rich,
.fnl-page [data-vertex="header"] [data-fnl-dropdown] .fnl-nav-dropdown-link {
  padding: 11px 12px !important;
  border-radius: 4px !important;
  transition: background-color 0.15s ease, transform 0.15s ease;
}
.fnl-page [data-vertex="header"] [data-fnl-dropdown] .fnl-nav-dropdown-rich:hover,
.fnl-page [data-vertex="header"] [data-fnl-dropdown] .fnl-nav-dropdown-link:hover {
  background: color-mix(in srgb, var(--site-primary, #006994) 7%, transparent) !important;
}
.fnl-page [data-vertex="header"] [data-fnl-dropdown] .fnl-nav-dropdown-icon {
  width: 34px !important;
  height: 34px !important;
  border-radius: 4px !important;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: color-mix(in srgb, var(--site-primary, #006994) 10%, var(--site-background, #ffffff));
  color: var(--site-primary, #006994);
}
.fnl-page [data-vertex="header"] [data-fnl-dropdown] .fnl-nav-dropdown-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--site-heading, #1b2430);
}
.fnl-page [data-vertex="header"] [data-fnl-dropdown] .fnl-nav-dropdown-desc {
  font-size: 12px;
  line-height: 1.4;
  color: var(--site-muted, #64748b);
}
@keyframes vertex-dropdown-in {
  from { opacity: 0; transform: translateX(calc(-50% + var(--vertex-shift-x, 0px))) translateY(6px); }
  to { opacity: 1; transform: translateX(calc(-50% + var(--vertex-shift-x, 0px))) translateY(0); }
}

/* Header search icon & inline quick-search popover */
.fnl-page [data-vertex="header"] [data-vertex-role="header-search"] a,
.fnl-page [data-vertex="header"] [data-vertex-role="header-search"] button {
  width: 36px !important;
  height: 36px !important;
  padding: 0 !important;
  min-width: 36px !important;
  border-radius: 4px !important;
  border: 1px solid transparent !important;
  background: transparent !important;
  font-size: 0 !important;
  line-height: 0 !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  position: relative;
  cursor: pointer;
}
.fnl-page [data-vertex="header"] [data-vertex-role="header-search"] a::before,
.fnl-page [data-vertex="header"] [data-vertex-role="header-search"] button::before {
  content: "";
  width: 18px;
  height: 18px;
  background-color: var(--site-primary, #006994);
  -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='7.5'/%3E%3Cpath d='m20 20-3.5-3.5'/%3E%3C/svg%3E") no-repeat center / contain;
  mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='7.5'/%3E%3Cpath d='m20 20-3.5-3.5'/%3E%3C/svg%3E") no-repeat center / contain;
}
.fnl-page [data-vertex="header"] [data-vertex-role="header-search"] a:hover,
.fnl-page [data-vertex="header"] [data-vertex-role="header-search"] button:hover {
  background: color-mix(in srgb, var(--site-primary, #006994) 8%, #ffffff) !important;
  border-color: color-mix(in srgb, var(--site-primary, #006994) 24%, transparent) !important;
}
.fnl-page .vertex-search-popover {
  position: absolute;
  left: 0;
  right: 0;
  top: 100%;
  background: var(--site-background, #ffffff);
  border-bottom: 1px solid var(--site-border, #dce3ec);
  border-top: 2px solid var(--site-primary, #006994);
  box-shadow: 0 22px 44px -14px rgba(12, 35, 64, 0.18);
  padding: 18px 24px;
  z-index: 90;
}
.fnl-page .vertex-search-inner {
  max-width: var(--site-content-width, 1280px);
  margin: 0 auto;
}
.fnl-page .vertex-search-row {
  display: flex;
  align-items: center;
  gap: 10px;
}
.fnl-page .vertex-search-input {
  flex: 1;
  height: 44px;
  padding: 0 14px;
  font-size: 15px;
  border: 1px solid var(--site-border, #cbd5e1);
  border-radius: 4px;
  background: var(--site-surface, #f8fafc);
  color: var(--site-heading, #1b2430);
}
.fnl-page .vertex-search-input:focus {
  outline: 2px solid var(--site-primary, #006994);
  outline-offset: -1px;
  background: #ffffff;
}
.fnl-page .vertex-search-close {
  width: 40px;
  height: 40px;
  border: 1px solid var(--site-border, #cbd5e1);
  background: #ffffff;
  color: var(--site-heading, #1b2430);
  border-radius: 4px;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
}
.fnl-page .vertex-search-results {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
  margin-top: 14px;
}
.fnl-page .vertex-search-hit {
  display: flex;
  flex-direction: column;
  gap: 3px;
  padding: 10px 12px;
  border-radius: 4px;
  border: 1px solid var(--site-border, #e2e8f0);
  text-decoration: none;
  background: var(--site-background, #ffffff);
  transition: border-color 0.16s ease, background-color 0.16s ease;
}
.fnl-page .vertex-search-hit:hover {
  border-color: var(--site-primary, #006994);
  background: color-mix(in srgb, var(--site-primary, #006994) 5%, #ffffff);
}
.fnl-page .vertex-search-hit strong {
  font-size: 13.5px;
  color: var(--site-primary, #006994);
}
.fnl-page .vertex-search-hit span {
  font-size: 12px;
  color: var(--site-muted, #64748b);
}
.fnl-page .vertex-search-empty {
  font-size: 13px;
  color: var(--site-muted, #64748b);
  margin: 4px 0 0;
}

/* Dark Navy Hero & Outline Ghost CTAs */
.fnl-page [data-vertex="hero"] {
  background: linear-gradient(
    114deg,
    var(--site-dark, #0c2340) 0%,
    color-mix(in srgb, var(--site-dark, #0c2340) 56%, var(--site-primary, #006994)) 55%,
    var(--vertex-hero-end, #165187) 100%
  ) !important;
  position: relative;
  overflow: hidden;
}
.fnl-page [data-vertex="hero"] [data-vertex-role="outline-cta"] a:hover,
.fnl-page [data-vertex="announcement"] [data-vertex-role="outline-cta"] a:hover,
.fnl-page [data-vertex="cta-band"] [data-vertex-role="outline-cta"] a:hover {
  background: #ffffff !important;
  color: var(--site-dark, #0c2340) !important;
  border-color: #ffffff !important;
}

/* Announcement banner strip */
.fnl-page [data-vertex="announcement"] {
  background: linear-gradient(
    90deg,
    var(--vertex-banner-bg, #005f87) 0%,
    color-mix(in srgb, var(--vertex-banner-bg, #005f87) 70%, var(--site-primary, #006994)) 100%
  ) !important;
}

/* Spotlight & Trending Topic enterprise cards */
.fnl-page [data-vertex="spotlight"] [data-vertex-role="spotlight-card"],
.fnl-page [data-vertex="trending"] [data-vertex-role="topic-card"],
.fnl-page [data-vertex="domains"] [data-vertex-role="domain-card"],
.fnl-page [data-vertex="modules"] [data-vertex-role="module-card"] {
  transition: transform 0.26s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.26s ease, border-color 0.22s ease, opacity 0.45s ease;
}
.fnl-page [data-vertex="spotlight"] [data-vertex-role="spotlight-card"]:hover,
.fnl-page [data-vertex="trending"] [data-vertex-role="topic-card"]:hover {
  transform: translateY(-4px);
  box-shadow: 0 18px 36px -14px rgba(12, 35, 64, 0.14) !important;
  border-color: color-mix(in srgb, var(--site-primary, #006994) 38%, var(--site-border, #dce3ec)) !important;
}

/* Filter chips (/modules) */
.fnl-page [data-vertex-role="module-filters"] .fnl-block a,
.fnl-page [data-vertex-role="module-filters"] .fnl-block button {
  transition: background-color 0.16s ease, color 0.16s ease, border-color 0.16s ease;
}
.fnl-page [data-vertex-role="module-filters"] .is-active a,
.fnl-page [data-vertex-role="module-filters"] .is-active button {
  background: var(--site-primary, #006994) !important;
  color: var(--site-on-primary, #ffffff) !important;
  border-color: var(--site-primary, #006994) !important;
}

/* Dark CTA Band */
.fnl-page [data-vertex="cta-band"] {
  background: linear-gradient(
    135deg,
    var(--site-dark, #0c2340) 0%,
    color-mix(in srgb, var(--site-dark, #0c2340) 78%, var(--vertex-hero-end, #165187)) 100%
  ) !important;
}

/* Footer with VMware multi-stop spectrum top accent bar */
.fnl-page [data-vertex="footer"] {
  position: relative;
}
.fnl-page [data-vertex="footer"]::before {
  content: "";
  display: block;
  width: 100%;
  height: 4px;
  background: linear-gradient(
    90deg,
    var(--vertex-stripe-start, #6cc04a) 0%,
    var(--site-secondary, #00a99d) 36%,
    var(--site-primary, #006994) 68%,
    var(--vertex-stripe-end, #5b3df5) 100%
  );
}
.fnl-page [data-vertex="footer"] nav[data-fnl-nav] ul {
  gap: 8px !important;
}
.fnl-page [data-vertex="footer"] nav[data-fnl-nav] a {
  font-size: 13.5px !important;
  color: var(--site-footer-text, #4a5565) !important;
  text-decoration: none !important;
  transition: color 0.16s ease;
}
.fnl-page [data-vertex="footer"] nav[data-fnl-nav] a:hover {
  color: var(--site-primary, #006994) !important;
}
.fnl-page [data-vertex] [data-vertex-role="inline-link"] a {
  text-decoration: none !important;
  transition: color 0.16s ease;
}
.fnl-page [data-vertex] [data-vertex-role="inline-link"] a:hover {
  color: var(--site-primary, #006994) !important;
  text-decoration: underline !important;
}

/* Smooth reveal states */
.fnl-page:not(.fnl-editable) [data-vertex-reveal="pending"] {
  opacity: 0;
  transform: translateY(18px);
}
.fnl-page:not(.fnl-editable) [data-vertex-reveal="visible"] {
  opacity: 1;
  transform: translateY(0);
}

@media (max-width: 960px) {
  ${mobileHeaderRules('.fnl-page[data-fnl-breakpoint="viewport"]')}
}
${mobileHeaderRules('.fnl-page[data-fnl-breakpoint="mobile"]')}

@media (prefers-reduced-motion: reduce) {
  .fnl-page:not(.fnl-editable) [data-vertex-reveal="pending"] {
    opacity: 1 !important;
    transform: none !important;
  }
  .fnl-page [data-vertex] * {
    animation: none !important;
    transition: none !important;
  }
}
`.trim();
