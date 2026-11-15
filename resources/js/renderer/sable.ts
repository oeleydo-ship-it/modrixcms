/**
 * Styling, mega-dropdown alignment, interactive process step switcher, hero chat
 * demo toggle, entrance motion, and architectural gridline markers for the
 * `sable` design collection (`modrix/sable`).
 *
 * Dynamically reads Theme Settings (`--site-*`) and Customize (`--sable-*`)
 * CSS custom properties so every section updates live across both published
 * pages and Visual Page Builder canvases.
 */

import { useEffect, type RefObject } from 'react';

export function useSableMotion(root: RefObject<HTMLDivElement | null>, editable: boolean, revision: unknown): void {
  useEffect(() => {
    const page = root.current;
    if (!page || !page.querySelector('[data-sable]')) return;

    const cleanups: Array<() => void> = [];

    // 1. Sticky scroll elevation + Desktop hover intent & viewport clamping for Pages 4-col dropdown
    if (!editable) {
      const headers = Array.from(page.querySelectorAll<HTMLElement>('[data-sable="header"]'));
      const onScroll = (): void => {
        const scrolled = window.scrollY > 18;
        headers.forEach((h) => h.classList.toggle('is-scrolled', scrolled));
      };
      onScroll();
      window.addEventListener('scroll', onScroll, { passive: true });
      cleanups.push(() => window.removeEventListener('scroll', onScroll));

      const navDetails = Array.from(page.querySelectorAll<HTMLDetailsElement>('[data-sable="header"] details.fnl-nav-details'));
      const hoverTimers = new WeakMap<HTMLDetailsElement, number>();

      const positionDropdown = (details: HTMLDetailsElement): void => {
        const panel = details.querySelector<HTMLElement>('[data-fnl-dropdown]');
        if (!panel) return;
        panel.style.removeProperty('--sable-shift-x');
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
          panel.style.setProperty('--sable-shift-x', `${Math.round(shift)}px`);
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
          }, 160);
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

      const onResize = (): void => {
        navDetails.forEach((d) => {
          if (d.open) positionDropdown(d);
        });
      };
      window.addEventListener('resize', onResize, { passive: true });
      cleanups.push(() => window.removeEventListener('resize', onResize));

      // Section entrance animations
      const motionSections = Array.from(
        page.querySelectorAll<HTMLElement>(
          '[data-sable="services"], [data-sable="process"], [data-sable="stats"], ' +
          '[data-sable="cases"], [data-sable="testimonial"], [data-sable="bento"], ' +
          '[data-sable="faq"], [data-sable="articles"], [data-sable="cta-band"], ' +
          '[data-sable="about-team"], [data-sable="about-story"], [data-sable="pricing"], [data-sable="contact"]',
        ),
      );
      const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
      if (reducedMotion || typeof IntersectionObserver === 'undefined') {
        motionSections.forEach((s) => s.setAttribute('data-sable-inview', 'true'));
      } else if (motionSections.length > 0) {
        const pendingRafs: number[] = [];
        const observer = new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting || entry.boundingClientRect.top < window.innerHeight * 0.94) {
                entry.target.setAttribute('data-sable-inview', 'true');
                observer.unobserve(entry.target);
              }
            });
          },
          { threshold: 0.05, rootMargin: '0px 0px -32px 0px' },
        );

        const vh = window.innerHeight || 900;
        motionSections.forEach((section) => {
          section.setAttribute('data-sable-motion', 'ready');
          const rect = section.getBoundingClientRect();
          if (rect.bottom <= 0) {
            section.setAttribute('data-sable-inview', 'true');
          } else if (rect.top < vh * 0.94) {
            const r1 = requestAnimationFrame(() => {
              const r2 = requestAnimationFrame(() => section.setAttribute('data-sable-inview', 'true'));
              pendingRafs.push(r2);
            });
            pendingRafs.push(r1);
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

    // 2. Interactive "How we work?" 3-Step Switcher
    const processSections = Array.from(page.querySelectorAll<HTMLElement>('[data-sable="process"]'));
    processSections.forEach((section) => {
      const steps = Array.from(section.querySelectorAll<HTMLElement>('[data-sable-role="process-step"]'));
      const panels = Array.from(section.querySelectorAll<HTMLElement>('[data-sable-role="process-panel"]'));
      if (steps.length === 0 || panels.length === 0) return;

      const activate = (index: number): void => {
        steps.forEach((el, idx) => el.classList.toggle('is-active', idx === index));
        panels.forEach((el, idx) => el.classList.toggle('is-active', idx === index));
      };

      const activeIdx = steps.findIndex((s) => s.classList.contains('is-active'));
      activate(activeIdx >= 0 ? activeIdx : 0);

      steps.forEach((step, idx) => {
        if (!editable) {
          step.setAttribute('role', 'button');
          step.setAttribute('tabindex', '0');
        }
        const onClick = (): void => activate(idx);
        const onKey = (e: KeyboardEvent): void => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            activate(idx);
          }
        };
        step.addEventListener('click', onClick);
        step.addEventListener('keydown', onKey);
        cleanups.push(() => {
          step.removeEventListener('click', onClick);
          step.removeEventListener('keydown', onKey);
        });
      });
    });

    // 3. Interactive Contact Inquiry Card on /contact (progressive enhancement over native container)
    if (!editable) {
      const formMounts = Array.from(page.querySelectorAll<HTMLElement>('[data-sable-role="contact-form-mount"]'));
      formMounts.forEach((mount) => {
        if (mount.querySelector('.sable-contact-form')) return;
        const form = document.createElement('form');
        form.className = 'sable-contact-form';
        form.innerHTML = `
          <div class="sable-form-grid">
            <label class="sable-field">
              <span>First name</span>
              <input type="text" name="first_name" placeholder="Elena" required />
            </label>
            <label class="sable-field">
              <span>Last name</span>
              <input type="text" name="last_name" placeholder="Rostova" required />
            </label>
          </div>
          <label class="sable-field">
            <span>Work email</span>
            <input type="email" name="email" placeholder="elena@company.com" required />
          </label>
          <label class="sable-field">
            <span>What are you building?</span>
            <textarea name="message" rows="4" placeholder="Tell us about your current workflows, team size and timeline..." required></textarea>
          </label>
          <label class="sable-checkbox">
            <input type="checkbox" required />
            <span>I agree to receive a tailored scoping reply from Sable Systems.</span>
          </label>
          <button type="submit" class="sable-submit-btn">Send message</button>
          <div class="sable-form-status" hidden></div>
        `;
        const onSubmit = (event: Event): void => {
          event.preventDefault();
          const data = new FormData(form);
          const name = String(data.get('first_name') ?? '').trim() || 'there';
          const status = form.querySelector<HTMLElement>('.sable-form-status');
          if (status) {
            status.hidden = false;
            status.textContent = `Thanks, ${name} — your project brief has been queued for an engineering partner review.`;
          }
          form.reset();
        };
        form.addEventListener('submit', onSubmit);
        mount.appendChild(form);
        cleanups.push(() => {
          form.removeEventListener('submit', onSubmit);
          form.remove();
        });
      });

      // 4. Interactive Hero Chat Input Hint
      const chatPrompts = Array.from(page.querySelectorAll<HTMLElement>('[data-sable-role="hero-chat-input"]'));
      chatPrompts.forEach((bar) => {
        if (bar.dataset.sableBound) return;
        bar.dataset.sableBound = '1';
        const input = bar.querySelector('p');
        const replies = [
          'Type a message...',
          'Routing inquiry to Enterprise AI...',
          'Booked technical discovery for Thu 10:00 AM',
        ];
        let step = 0;
        const onTap = (): void => {
          step = (step + 1) % replies.length;
          if (input) input.textContent = replies[step];
        };
        bar.addEventListener('click', onTap);
        cleanups.push(() => bar.removeEventListener('click', onTap));
      });
    }

    return () => {
      cleanups.forEach((fn) => fn());
    };
  }, [root, editable, revision]);
}

export const SABLE_CSS = `
/* -------------------------------------------------------------------------- */
/* Sable Systems (modrix/sable) — Theme & Design Collection CSS               */
/* Uses Theme Settings (--site-*) and Customize (--sable-*) tokens dynamically */
/* -------------------------------------------------------------------------- */

.fnl-page:has([data-sable]) {
  background: var(--site-background, #fdfcfc);
  color: var(--site-body, #716b63);
  font-family: var(--site-body-font, 'Geist', 'Inter', system-ui, sans-serif);
  -webkit-font-smoothing: antialiased;
}

.fnl-page [data-sable] h1,
.fnl-page [data-sable] h2,
.fnl-page [data-sable] h3,
.fnl-page [data-sable] h4 {
  font-family: var(--site-heading-font, 'Geist', 'Inter', system-ui, sans-serif);
}

/* Signature Two-Tone Hero Split Highlight (em accent text) */
.fnl-page [data-sable="hero"] h1 em,
.fnl-page [data-sable="inner-hero"] h1 em {
  font-style: normal;
  color: var(--sable-accent, var(--site-accent, #eb812c));
}

/* Header Capsule Pill & Sticky Shell */
.fnl-page [data-sable="header"] {
  position: sticky;
  top: 0;
  z-index: 60;
  background: rgba(253, 252, 252, 0.92);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-bottom: 1px solid var(--sable-grid-color, var(--site-border, #edebe9));
  transition: box-shadow 0.25s ease, background-color 0.25s ease;
}

.fnl-page [data-sable="header"].is-scrolled {
  background: rgba(253, 252, 252, 0.96);
  box-shadow: 0 8px 30px -16px rgba(19, 19, 20, 0.1);
}

.fnl-page [data-sable-role="nav-capsule"] {
  background: var(--sable-capsule-bg, rgba(255, 255, 255, 0.85));
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid var(--sable-grid-color, var(--site-border, #edebe9));
  border-radius: 999px;
  padding: 5px 18px;
}

.fnl-page [data-sable="header"] .fnl-block nav ul {
  align-items: center;
  flex-wrap: nowrap !important;
  gap: 24px !important;
}

.fnl-page [data-sable="header"] .fnl-block nav ul > li > a,
.fnl-page [data-sable="header"] .fnl-block nav summary.fnl-nav-summary {
  font-size: 14px;
  font-weight: 500;
  color: var(--site-header-text, #131314) !important;
  white-space: nowrap;
  padding: 6px 2px;
  transition: color 0.18s ease;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.fnl-page [data-sable="header"] .fnl-block nav ul > li > a:hover,
.fnl-page [data-sable="header"] .fnl-block nav summary.fnl-nav-summary:hover,
.fnl-page [data-sable="header"] .fnl-block nav details[open] > summary.fnl-nav-summary {
  color: var(--sable-accent, var(--site-accent, #eb812c)) !important;
}

.fnl-page [data-sable="header"] .fnl-block nav summary.fnl-nav-summary::-webkit-details-marker {
  display: none;
}

/* 4-Column Pages Mega-Dropdown */
.fnl-page [data-sable="header"] details.fnl-nav-details > .fnl-nav-dropdown[data-fnl-dropdown] {
  --fnl-dropdown-top: calc(100% + 14px);
  --fnl-dropdown-left: 50%;
  --fnl-dropdown-right: auto;
  --fnl-dropdown-width: min(740px, calc(100vw - 32px));
  --fnl-dropdown-cols: repeat(4, minmax(0, 1fr));
  --fnl-dropdown-gap: 12px;
  --fnl-dropdown-padding: 20px;
  --fnl-dropdown-radius: 16px;
  --fnl-dropdown-border: 1px solid var(--sable-grid-color, var(--site-border, #edebe9));
  --fnl-dropdown-bg: var(--site-background, #ffffff);
  --fnl-dropdown-color: var(--site-heading, #131314);
  --fnl-dropdown-shadow: 0 24px 60px -16px rgba(19, 19, 20, 0.14);
  transform: translateX(calc(-50% + var(--sable-shift-x, 0px)));
  animation: sableDropdownReveal 0.18s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.fnl-page [data-sable="header"] .fnl-nav-dropdown-link {
  padding: 10px 12px !important;
  border-radius: 10px;
  font-size: 13.5px;
  transition: background-color 0.16s ease, color 0.16s ease;
}

.fnl-page [data-sable="header"] .fnl-nav-dropdown-link:hover {
  background: var(--sable-surface, var(--site-surface, #f5f3f1));
  color: var(--sable-accent, var(--site-accent, #eb812c)) !important;
}

@keyframes sableDropdownReveal {
  from { opacity: 0; transform: translateX(calc(-50% + var(--sable-shift-x, 0px))) translateY(-4px); }
  to   { opacity: 1; transform: translateX(calc(-50% + var(--sable-shift-x, 0px))) translateY(0); }
}

/* Architectural Vertical Rail Guides & + Crosshair Intersections */
.fnl-page [data-sable]:not([data-sable="header"]) > .fnl-section-inner {
  max-width: var(--site-content-width, 1180px);
  margin-inline: auto;
  border-left: var(--sable-rail-border, 1px solid var(--sable-grid-color, var(--site-border, #edebe9)));
  border-right: var(--sable-rail-border, 1px solid var(--sable-grid-color, var(--site-border, #edebe9)));
  position: relative;
}

.fnl-page [data-sable="hero"],
.fnl-page [data-sable="services"],
.fnl-page [data-sable="process"],
.fnl-page [data-sable="stats"],
.fnl-page [data-sable="cases"],
.fnl-page [data-sable="testimonial"],
.fnl-page [data-sable="bento"],
.fnl-page [data-sable="faq"],
.fnl-page [data-sable="articles"],
.fnl-page [data-sable="cta-band"],
.fnl-page [data-sable="inner-hero"],
.fnl-page [data-sable="about-story"],
.fnl-page [data-sable="about-team"],
.fnl-page [data-sable="pricing"],
.fnl-page [data-sable="contact"],
.fnl-page [data-sable="footer"] {
  border-bottom: 1px solid var(--sable-grid-color, var(--site-border, #edebe9));
}

.fnl-page [data-sable]:not([data-sable="header"]) > .fnl-section-inner::before,
.fnl-page [data-sable]:not([data-sable="header"]) > .fnl-section-inner::after {
  content: '+';
  position: absolute;
  bottom: -10px;
  width: 18px;
  height: 18px;
  display: var(--sable-crosshair-display, flex);
  align-items: center;
  justify-content: center;
  font-family: 'Geist Mono', ui-monospace, monospace;
  font-size: 15px;
  font-weight: 400;
  line-height: 1;
  color: #b7b1a9;
  background: var(--site-background, #fdfcfc);
  z-index: 4;
  pointer-events: none;
}

.fnl-page [data-sable]:not([data-sable="header"]) > .fnl-section-inner::before {
  left: -9.5px;
}

.fnl-page [data-sable]:not([data-sable="header"]) > .fnl-section-inner::after {
  right: -9.5px;
}

.fnl-page [data-sable="footer"] > .fnl-section-inner::before,
.fnl-page [data-sable="footer"] > .fnl-section-inner::after {
  bottom: auto;
  top: -10px;
}

/* Pill Buttons & Hover Polish */
.fnl-page [data-sable] .fnl-block a[role="button"],
.fnl-page [data-sable] .fnl-block button {
  transition: transform 0.18s ease, background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}

.fnl-page [data-sable] [data-sable-role="btn-primary"] a:hover,
.fnl-page [data-sable] [data-sable-role="btn-primary"] button:hover {
  background-color: var(--sable-accent, var(--site-accent, #eb812c)) !important;
  border-color: var(--sable-accent, var(--site-accent, #eb812c)) !important;
  color: #ffffff !important;
  transform: translateY(-1px);
}

.fnl-page [data-sable] [data-sable-role="btn-soft"] a:hover,
.fnl-page [data-sable] [data-sable-role="btn-soft"] button:hover {
  background-color: #ebe7e3 !important;
  transform: translateY(-1px);
}

/* Interactive Hero Chat Mockup */
.fnl-page [data-sable-role="hero-chat-stage"] {
  background-color: #211815;
  background-image: url('/templates/sable/hero-grain.svg');
  background-size: cover;
  background-position: center;
  border-radius: 16px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 24px 64px -20px rgba(19, 19, 20, 0.25);
}

.fnl-page [data-sable-role="hero-chat-input"] {
  cursor: pointer;
  transition: border-color 0.2s ease, background-color 0.2s ease;
}

.fnl-page [data-sable-role="hero-chat-input"]:hover {
  border-color: rgba(255, 255, 255, 0.45) !important;
}

/* Services & Case Study Hover Transitions */
.fnl-page [data-sable-role="service-card"] {
  transition: transform 0.22s ease, box-shadow 0.22s ease, background-color 0.22s ease;
}

.fnl-page [data-sable-role="service-card"]:hover {
  transform: translateY(-3px);
  box-shadow: 0 16px 36px -18px rgba(19, 19, 20, 0.14);
}

.fnl-page [data-sable-role="case-media"] {
  position: relative;
  overflow: hidden;
  border-radius: 14px;
}

.fnl-page [data-sable-role="case-badge"] {
  position: absolute !important;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 3;
  box-shadow: 0 14px 36px rgba(19, 19, 20, 0.18);
  transition: transform 0.25s ease;
}

.fnl-page [data-sable-role="case-card"]:hover [data-sable-role="case-badge"] {
  transform: translate(-50%, -54%) scale(1.03);
}

/* Interactive 3-Step Process Switcher */
.fnl-page [data-sable-role="process-step"] {
  cursor: pointer;
  border-left: 2px solid var(--sable-grid-color, var(--site-border, #edebe9));
  transition: border-color 0.22s ease, background-color 0.22s ease;
}

.fnl-page [data-sable-role="process-step"].is-active {
  border-left-color: var(--sable-accent, var(--site-accent, #eb812c));
  background: rgba(245, 243, 241, 0.65);
}

.fnl-page [data-sable-role="process-step"].is-active h4 {
  color: var(--site-heading, #131314) !important;
}

.fnl-page [data-sable-role="process-panel"] {
  display: none;
  animation: sablePanelIn 0.28s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.fnl-page [data-sable-role="process-panel"].is-active {
  display: flex;
}

@keyframes sablePanelIn {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Bento Cards */
.fnl-page [data-sable-role="bento-card-dark"] {
  position: relative;
  overflow: hidden;
  background-color: #171413;
  background-size: cover;
  background-position: center;
}

/* Contact Page Form Enhancement */
.fnl-page .sable-contact-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
  margin-top: 8px;
}

.fnl-page .sable-form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.fnl-page .sable-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 13.5px;
  font-weight: 500;
  color: var(--site-heading, #131314);
}

.fnl-page .sable-field input,
.fnl-page .sable-field textarea {
  width: 100%;
  box-sizing: border-box;
  border-radius: 10px;
  border: 1px solid var(--sable-grid-color, var(--site-border, #e2dfdc));
  background: #ffffff;
  color: var(--site-heading, #131314);
  padding: 12px 14px;
  font: inherit;
  font-weight: 400;
  transition: border-color 0.16s ease, box-shadow 0.16s ease;
}

.fnl-page .sable-field input:focus,
.fnl-page .sable-field textarea:focus {
  outline: none;
  border-color: var(--sable-accent, var(--site-accent, #eb812c));
  box-shadow: 0 0 0 3px rgba(235, 129, 44, 0.14);
}

.fnl-page .sable-checkbox {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: var(--site-muted, #716b63);
  cursor: pointer;
}

.fnl-page .sable-submit-btn {
  align-self: flex-start;
  border: 0;
  border-radius: 999px;
  background: var(--site-heading, #131314);
  color: #ffffff;
  font: inherit;
  font-size: 14.5px;
  font-weight: 500;
  padding: 13px 26px;
  cursor: pointer;
  transition: background-color 0.18s ease, transform 0.18s ease;
}

.fnl-page .sable-submit-btn:hover {
  background: var(--sable-accent, var(--site-accent, #eb812c));
  transform: translateY(-1px);
}

.fnl-page .sable-form-status {
  padding: 12px 16px;
  border-radius: 10px;
  background: #ffffff;
  border: 1px solid var(--sable-accent, var(--site-accent, #eb812c));
  color: var(--site-heading, #131314);
  font-size: 14px;
}

/* Giant Cropped Bottom Watermark in Footer */
.fnl-page [data-sable-role="footer-watermark"] {
  display: var(--sable-watermark-display, block);
  max-height: 158px;
  overflow: hidden;
  user-select: none;
  pointer-events: none;
  margin-top: 28px;
}

.fnl-page [data-sable-role="footer-watermark"] img {
  display: block;
  width: 100%;
  height: auto;
}

/* Entrance Motion */
.fnl-page [data-sable][data-sable-motion="ready"] {
  opacity: 0;
  transform: translateY(18px);
  transition: opacity 0.55s cubic-bezier(0.22, 1, 0.36, 1), transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
}

.fnl-page [data-sable][data-sable-motion="ready"][data-sable-inview="true"] {
  opacity: 1;
  transform: none;
}

@media (max-width: 960px) {
  .fnl-page [data-sable="header"] [data-sable-role="nav-capsule"] {
    background: transparent !important;
    border-color: transparent !important;
    backdrop-filter: none !important;
    padding: 0 !important;
  }

  .fnl-page [data-sable]:not([data-sable="header"]) > .fnl-section-inner {
    border-left: none;
    border-right: none;
  }

  .fnl-page [data-sable]:not([data-sable="header"]) > .fnl-section-inner::before,
  .fnl-page [data-sable]:not([data-sable="header"]) > .fnl-section-inner::after {
    display: none;
  }

  .fnl-page .sable-form-grid {
    grid-template-columns: 1fr;
  }
}

.fnl-page[data-fnl-breakpoint="mobile"] [data-sable="header"] [data-sable-role="nav-capsule"] {
  background: transparent !important;
  border-color: transparent !important;
  backdrop-filter: none !important;
  padding: 0 !important;
}
`;
