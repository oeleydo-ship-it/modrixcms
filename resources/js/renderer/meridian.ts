/**
 * Finishing rules for the `meridian` design collection (`modrix/meridian`),
 * implementing the editorial glass & forest advisory layout while inheriting
 * active Theme Settings (`--site-*` CSS variables) on every block and page.
 */
const mobileHeaderRules = (scope: string): string => `
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] {
    padding: 10px 14px !important;
    border-radius: 16px !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] img {
    height: 28px !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] > .fnl-nested-columns {
    position: static !important;
    width: 100% !important;
    grid-template-columns: minmax(0, 1fr) auto !important;
    align-items: center !important;
    gap: 12px !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] > .fnl-nested-columns > .fnl-block {
    position: static !important;
    min-width: 0 !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] > .fnl-nested-columns > .fnl-block:last-child,
  ${scope} [data-meridian="header"] [data-meridian-role="header-cta"] {
    display: none !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"] {
    position: static !important;
    width: auto !important;
    display: flex !important;
    justify-content: flex-end !important;
    align-items: center !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"] .fnl-nav-toggle {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 40px !important;
    height: 40px !important;
    border-radius: 999px !important;
    border: 1px solid rgba(255, 255, 255, 0.24) !important;
    background: rgba(255, 255, 255, 0.1) !important;
    color: var(--site-header-text, var(--site-on-dark, #f7f8ee)) !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"]:not([data-open="true"]) > ul {
    display: none !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] > ul {
    display: flex !important;
    position: absolute !important;
    left: 0 !important;
    right: 0 !important;
    top: calc(100% + 10px) !important;
    width: 100% !important;
    min-width: 0 !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    margin: 0 !important;
    background: color-mix(in srgb, var(--site-header-bg, var(--site-dark, #15241b)) 97%, #000000) !important;
    color: var(--site-header-text, var(--site-on-dark, #f7f8ee)) !important;
    border: 1px solid rgba(255, 255, 255, 0.16) !important;
    border-radius: 20px !important;
    padding: 12px 14px !important;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.34) !important;
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    flex-direction: column !important;
    flex-wrap: nowrap !important;
    justify-content: flex-start !important;
    align-items: stretch !important;
    gap: 2px !important;
    z-index: 95 !important;
    max-height: calc(100vh - 104px) !important;
    max-height: calc(100dvh - 104px) !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    overscroll-behavior: contain;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] > ul > li,
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] > ul > li > .fnl-nav-details {
    display: block !important;
    flex-shrink: 0 !important;
    width: 100% !important;
    min-width: 0 !important;
    height: auto !important;
    margin: 0 !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] > ul > li > a,
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] > ul > li > .fnl-nav-details > .fnl-nav-summary {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    width: 100% !important;
    height: auto !important;
    min-height: 40px !important;
    line-height: 1.3 !important;
    padding: 9px 12px !important;
    border-radius: 10px !important;
    box-sizing: border-box !important;
    white-space: normal !important;
    font-size: 13px !important;
    color: var(--site-header-text, var(--site-on-dark, #f7f8ee)) !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] > ul > li > a:hover,
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] > ul > li > .fnl-nav-details > .fnl-nav-summary:hover,
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] > ul > li > .fnl-nav-details[open] > .fnl-nav-summary {
    background: rgba(255, 255, 255, 0.08) !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] .fnl-nav-caret {
    margin-left: auto !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] [data-fnl-dropdown] {
    --fnl-dropdown-top: auto;
    --fnl-dropdown-left: auto;
    --fnl-dropdown-right: auto;
    --fnl-dropdown-width: 100%;
    position: static !important;
    transform: none !important;
    left: auto !important;
    right: auto !important;
    width: 100% !important;
    min-width: 0 !important;
    max-width: 100% !important;
    height: auto !important;
    box-sizing: border-box !important;
    grid-template-columns: minmax(0, 1fr) !important;
    gap: 4px !important;
    margin-top: 6px !important;
    margin-bottom: 6px !important;
    background: rgba(255, 255, 255, 0.06) !important;
    color: var(--site-header-text, #f7f8ee) !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    box-shadow: none !important;
    padding: 8px !important;
    border-radius: 14px !important;
    animation: none !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] [data-fnl-dropdown]::before {
    display: none !important;
    content: none !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] .fnl-nav-dropdown-rich,
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] .fnl-nav-dropdown-link {
    padding: 8px 10px !important;
    border-radius: 10px !important;
    gap: 10px !important;
    align-items: center !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] .fnl-nav-dropdown-rich:hover {
    background: rgba(255, 255, 255, 0.08) !important;
    transform: none !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] .fnl-nav-dropdown-icon {
    width: 34px !important;
    height: 34px !important;
    border-radius: 8px !important;
    background: rgba(255, 255, 255, 0.12) !important;
    color: var(--site-accent, #b98b43) !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] .fnl-nav-dropdown-title {
    font-size: 13.5px !important;
    color: var(--site-header-text, #f7f8ee) !important;
  }
  ${scope} [data-meridian="header"] [data-meridian-role="header-bar"] nav[data-mobile="drawer"][data-open="true"] .fnl-nav-dropdown-desc {
    font-size: 11.5px !important;
    line-height: 1.35 !important;
    color: var(--site-on-dark-muted, rgba(247, 248, 238, 0.72)) !important;
  }
`;

export const MERIDIAN_CSS = `
.fnl-page [data-meridian] {
  font-family: var(--site-body-font, 'DM Sans', system-ui, sans-serif);
}
.fnl-page [data-meridian] h1,
.fnl-page [data-meridian] h2,
.fnl-page [data-meridian] h3,
.fnl-page [data-meridian] h4 {
  font-family: var(--site-heading-font, 'Parkinsans', system-ui, sans-serif);
}

/* Curved accent underline highlight inside Meridian headings (dynamic --site-accent) */
.fnl-page [data-meridian] h1 u,
.fnl-page [data-meridian] h2 u {
  text-decoration: none;
  position: relative;
  display: inline-block;
  padding-bottom: 4px;
}
.fnl-page [data-meridian] h1 u::after,
.fnl-page [data-meridian] h2 u::after {
  content: "";
  position: absolute;
  left: -2%;
  right: -2%;
  bottom: -4px;
  height: 11px;
  background: var(--site-accent, #b98b43);
  -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 220 14' preserveAspectRatio='none'%3E%3Cpath d='M3 10.5C68 2.2 154 2.2 217 9.5' stroke='%23000' stroke-width='4.5' stroke-linecap='round' fill='none'/%3E%3C/svg%3E") no-repeat center / 100% 100%;
  mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 220 14' preserveAspectRatio='none'%3E%3Cpath d='M3 10.5C68 2.2 154 2.2 217 9.5' stroke='%23000' stroke-width='4.5' stroke-linecap='round' fill='none'/%3E%3C/svg%3E") no-repeat center / 100% 100%;
  pointer-events: none;
}

/* Floating translucent header bar & dropdown mega-menus */
.fnl-page [data-meridian="header"] {
  overflow: visible !important;
}
.fnl-page:not(.fnl-editable) > [data-meridian="header"]:has(+ [data-meridian="hero"]) {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 75;
}
.fnl-page [data-meridian="header"] .fnl-row,
.fnl-page [data-meridian="header"] .fnl-column {
  width: 100% !important;
  min-width: 0 !important;
}
.fnl-page [data-meridian="header"] [data-meridian-role="header-bar"] {
  position: relative !important;
  width: 100% !important;
  box-sizing: border-box !important;
  overflow: visible !important;
  background: color-mix(in srgb, var(--site-header-bg, var(--site-dark, #15241b)) 88%, transparent) !important;
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.14) !important;
  border-radius: 18px !important;
  box-shadow: 0 14px 36px rgba(10, 18, 13, 0.24);
  transition: background-color 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}
.fnl-page [data-meridian="header"].is-scrolled [data-meridian-role="header-bar"] {
  background: color-mix(in srgb, var(--site-header-bg, var(--site-dark, #15241b)) 96%, transparent) !important;
  box-shadow: 0 20px 44px rgba(10, 18, 13, 0.32);
}
.fnl-page [data-meridian="header"] [data-meridian-role="header-bar"] > .fnl-nested-columns {
  width: 100% !important;
  overflow: visible !important;
}
.fnl-page [data-meridian="header"] [data-meridian-role="header-bar"] img {
  height: 34px !important;
  width: auto !important;
  max-width: none !important;
  display: block;
  flex-shrink: 0;
}
@media (min-width: 961px) {
  .fnl-page:not([data-fnl-breakpoint="mobile"]) [data-meridian="header"] [data-meridian-role="header-bar"] > .fnl-nested-columns {
    grid-template-columns: auto minmax(0, 1fr) auto !important;
    align-items: center !important;
    gap: 20px !important;
  }
  .fnl-page:not([data-fnl-breakpoint="mobile"]) [data-meridian="header"] nav > ul {
    flex-wrap: nowrap !important;
    justify-content: center !important;
    align-items: center !important;
    gap: 20px !important;
  }
  .fnl-page:not([data-fnl-breakpoint="mobile"]) [data-meridian="header"] nav > ul > li {
    flex-shrink: 0 !important;
  }
}
.fnl-page [data-meridian="header"] nav > ul {
  align-items: center !important;
}
.fnl-page [data-meridian="header"] nav > ul > li,
.fnl-page [data-meridian="header"] nav .fnl-nav-details {
  display: inline-flex;
  align-items: center;
  margin: 0;
}
.fnl-page [data-meridian="header"] nav > ul > li > a,
.fnl-page [data-meridian="header"] nav .fnl-nav-details > .fnl-nav-summary {
  display: inline-flex !important;
  align-items: center !important;
  height: 34px !important;
  line-height: 1 !important;
  padding: 0 4px !important;
  margin: 0 !important;
  list-style: none;
  font-size: 12.5px;
  font-weight: 500;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  white-space: nowrap !important;
  color: var(--site-header-text, var(--site-on-dark, #f7f8ee)) !important;
  transition: color 0.18s ease, opacity 0.18s ease;
}
.fnl-page [data-meridian="header"] nav .fnl-nav-summary::-webkit-details-marker {
  display: none;
}
.fnl-page [data-meridian="header"] nav > ul > li > a:hover,
.fnl-page [data-meridian="header"] nav .fnl-nav-summary:hover,
.fnl-page [data-meridian="header"] nav .fnl-nav-details[open] > .fnl-nav-summary {
  color: var(--site-accent, #b98b43) !important;
}
.fnl-page [data-meridian="header"] .fnl-nav-caret {
  display: inline-block;
  width: 6px;
  height: 6px;
  margin-left: 6px;
  font-size: 0 !important;
  line-height: 0;
  border-right: 1.5px solid currentColor;
  border-bottom: 1.5px solid currentColor;
  transform: rotate(45deg) translateY(-1px);
  vertical-align: middle;
  transition: transform 0.22s ease;
}
.fnl-page [data-meridian="header"] .fnl-nav-details[open] .fnl-nav-caret {
  transform: rotate(-135deg) translateY(-1px);
}
.fnl-page [data-meridian="header"] [data-fnl-dropdown] {
  --fnl-dropdown-top: calc(100% + 16px);
  --fnl-dropdown-left: 50%;
  --fnl-dropdown-right: auto;
  --fnl-dropdown-width: min(620px, 90vw);
  --fnl-dropdown-cols: repeat(2, minmax(0, 1fr));
  --fnl-dropdown-gap: 12px;
  --fnl-dropdown-padding: 16px;
  --fnl-dropdown-radius: min(var(--site-card-radius, 22px), 24px);
  --fnl-dropdown-border: 1px solid var(--site-border, #cdd6d0);
  --fnl-dropdown-bg: #ffffff;
  --fnl-dropdown-color: var(--site-heading, #15241b);
  --fnl-dropdown-shadow: 0 24px 56px rgba(15, 28, 20, 0.24);
  box-sizing: border-box !important;
  transform: translateX(-50%);
  animation: meridian-dropdown-in 0.2s cubic-bezier(0.22, 1, 0.36, 1);
}
.fnl-page [data-meridian="header"] [data-fnl-dropdown],
.fnl-page [data-meridian="header"] [data-fnl-dropdown] * {
  white-space: normal !important;
}
.fnl-page [data-meridian="header"] [data-fnl-dropdown] .fnl-nav-dropdown-rich,
.fnl-page [data-meridian="header"] [data-fnl-dropdown] .fnl-nav-dropdown-link {
  min-width: 0 !important;
  width: 100% !important;
  max-width: 100% !important;
  box-sizing: border-box !important;
  overflow: hidden;
}
.fnl-page [data-meridian="header"] [data-fnl-dropdown] .fnl-nav-dropdown-copy {
  min-width: 0 !important;
  flex: 1 1 0% !important;
}
.fnl-page [data-meridian="header"] [data-fnl-dropdown] .fnl-nav-dropdown-title,
.fnl-page [data-meridian="header"] [data-fnl-dropdown] .fnl-nav-dropdown-desc {
  display: block;
  overflow-wrap: break-word;
  word-break: normal;
}
.fnl-page [data-meridian="header"] [data-fnl-dropdown] .fnl-nav-dropdown-icon {
  flex-shrink: 0 !important;
}
.fnl-page [data-meridian="header"] [data-fnl-dropdown]::before {
  content: "";
  position: absolute;
  top: -18px;
  left: 0;
  right: 0;
  height: 18px;
}
@keyframes meridian-dropdown-in {
  from { opacity: 0; transform: translate(-50%, 8px); }
  to { opacity: 1; transform: translate(-50%, 0); }
}
.fnl-page [data-meridian="header"] .fnl-nav-dropdown-rich {
  padding: 12px 14px;
  border-radius: 14px;
  align-items: center !important;
  transition: background-color 0.18s ease, transform 0.18s ease;
}
.fnl-page [data-meridian="header"] .fnl-nav-dropdown-rich:hover {
  background: var(--site-surface, #f6f3ee);
  transform: translateY(-1px);
}
.fnl-page [data-meridian="header"] .fnl-nav-dropdown-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--site-primary-soft, #e9f0ec);
  color: var(--site-primary, #15241b);
  transition: background-color 0.18s ease, color 0.18s ease;
}
.fnl-page [data-meridian="header"] .fnl-nav-dropdown-rich:hover .fnl-nav-dropdown-icon {
  background: var(--site-primary, #15241b);
  color: var(--site-on-dark, #f7f8ee);
}
.fnl-page [data-meridian="header"] .fnl-nav-dropdown-title {
  font-family: var(--site-heading-font, 'Parkinsans', sans-serif);
  font-size: 14px;
  font-weight: 600;
  color: var(--site-heading, #15241b);
}
.fnl-page [data-meridian="header"] .fnl-nav-dropdown-desc {
  font-size: 12px;
  line-height: 1.4;
  color: var(--site-muted, #606a64);
}
.fnl-page [data-meridian="header"] a[role="button"],
.fnl-page [data-meridian="header"] [data-meridian-role="header-cta"] a {
  transition: transform 0.2s ease, background-color 0.2s ease;
}
.fnl-page [data-meridian="header"] [data-meridian-role="header-cta"] a:hover {
  transform: translateY(-1px);
}

/* Mobile header & frosted accordion drawer */
@media (max-width: 960px) {
  ${mobileHeaderRules('.fnl-page:not([data-fnl-breakpoint="desktop"])')}
}
${mobileHeaderRules('.fnl-page[data-fnl-breakpoint="mobile"]')}

/* Hero gradient overlay, pill emblem & dark frosted quick assessment card */
.fnl-page [data-meridian="hero"] {
  position: relative;
}
.fnl-page [data-meridian="hero"]::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(110deg, color-mix(in srgb, var(--site-dark, #15241b) 88%, transparent) 0%, color-mix(in srgb, var(--site-dark, #15241b) 48%, transparent) 52%, color-mix(in srgb, var(--site-dark, #15241b) 72%, transparent) 100%);
  pointer-events: none;
  z-index: 0;
}
.fnl-page [data-meridian="hero"] > * {
  position: relative;
  z-index: 1;
}
.fnl-page [data-meridian-role="hero-form"] {
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  box-shadow: 0 28px 60px rgba(10, 18, 13, 0.38);
}
.fnl-page [data-meridian-role="hero-form"] form {
  gap: 12px !important;
}
.fnl-page [data-meridian-role="hero-form"] form label {
  color: rgba(247, 248, 238, 0.88) !important;
  font-size: 12.5px !important;
  font-weight: 500 !important;
}
.fnl-page [data-meridian-role="hero-form"] form input,
.fnl-page [data-meridian-role="hero-form"] form textarea,
.fnl-page [data-meridian-role="hero-form"] form select {
  background: rgba(255, 255, 255, 0.08) !important;
  border: 1px solid rgba(255, 255, 255, 0.2) !important;
  color: #f7f8ee !important;
  border-radius: 10px !important;
  padding: 11px 14px !important;
}
.fnl-page [data-meridian-role="hero-form"] form input::placeholder,
.fnl-page [data-meridian-role="hero-form"] form textarea::placeholder {
  color: rgba(247, 248, 238, 0.48) !important;
}
.fnl-page [data-meridian-role="hero-form"] form input:focus,
.fnl-page [data-meridian-role="hero-form"] form textarea:focus,
.fnl-page [data-meridian-role="hero-form"] form select:focus {
  outline: none !important;
  border-color: var(--site-accent, #b98b43) !important;
  background: rgba(255, 255, 255, 0.12) !important;
}
.fnl-page [data-meridian-role="hero-form"] form button[type="submit"] {
  width: 100% !important;
  justify-self: stretch !important;
  background: var(--site-accent, #b98b43) !important;
  color: #15241b !important;
  border: 0 !important;
  border-radius: var(--site-button-radius, 12px) !important;
  padding: 14px 20px !important;
  font-weight: 600 !important;
  cursor: pointer;
  transition: transform 0.18s ease, filter 0.18s ease;
}
.fnl-page [data-meridian-role="hero-form"] form button[type="submit"]:hover {
  transform: translateY(-1px);
  filter: brightness(1.06);
}

/* Contact light card form overrides */
.fnl-page [data-meridian-role="contact-form"] form input,
.fnl-page [data-meridian-role="contact-form"] form textarea,
.fnl-page [data-meridian-role="contact-form"] form select {
  background: var(--site-background, #f7f8ee) !important;
  border: 1px solid var(--site-border, #cdd6d0) !important;
  color: var(--site-heading, #15241b) !important;
  border-radius: 10px !important;
  padding: 12px 14px !important;
}
.fnl-page [data-meridian-role="contact-form"] form button[type="submit"] {
  background: var(--site-primary, #15241b) !important;
  color: var(--site-on-dark, #f7f8ee) !important;
  border-radius: var(--site-button-radius, 12px) !important;
  padding: 14px 26px !important;
  font-weight: 600 !important;
}

/* Vertical Routes Tabs styling obeying Theme Settings */
.fnl-page [data-meridian="routes"] {
  --fnl-tablist-bg: #ffffff;
  --fnl-tablist-border: var(--site-border, #cdd6d0);
  --fnl-tablist-radius: var(--site-card-radius, 20px);
  --fnl-tab-radius: calc(var(--site-card-radius, 20px) * 0.65);
  --fnl-tab-active-bg: var(--site-primary, #15241b);
  --fnl-tab-active-color: var(--site-on-dark, #f7f8ee);
  --fnl-tab-inactive-bg: transparent;
  --fnl-tab-inactive-color: var(--site-body, #425349);
  --fnl-tab-active-shadow: 0 10px 24px rgba(21, 36, 27, 0.16);
}
.fnl-page [data-meridian="routes"] [role="tablist"] {
  padding: 12px !important;
}
.fnl-page [data-meridian="routes"] [role="tab"] {
  font-family: var(--site-heading-font, 'Parkinsans', sans-serif);
  font-size: 15px;
  font-weight: 500;
  padding: 14px 16px !important;
  transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
}
.fnl-page [data-meridian="routes"] [role="tabpanel"] {
  background: var(--site-primary-soft, #e9f0ec);
  border: 1px solid var(--site-border, #cdd6d0);
  border-radius: var(--site-card-radius, 20px);
  padding: 32px;
}

/* Card hover lifts, frosted services, and continuous marquees */
.fnl-page [data-meridian] [data-meridian-role="hover-card"] {
  transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1), border-color 0.25s ease, box-shadow 0.25s ease, background-color 0.25s ease;
}
.fnl-page [data-meridian] [data-meridian-role="hover-card"]:hover {
  transform: translateY(-4px);
  border-color: var(--site-accent, #b98b43) !important;
  box-shadow: 0 20px 44px rgba(15, 28, 20, 0.1);
}
.fnl-page [data-meridian] [data-meridian-role="service-card"] {
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  transition: transform 0.24s ease, border-color 0.24s ease, background-color 0.24s ease;
}
.fnl-page [data-meridian] [data-meridian-role="service-card"]:hover {
  transform: translateY(-3px);
  border-color: var(--site-accent, #b98b43) !important;
  background: rgba(247, 248, 238, 0.12) !important;
}
.fnl-page [data-meridian] [data-meridian-role="photo-zoom"] {
  overflow: hidden;
}
.fnl-page [data-meridian] [data-meridian-role="photo-zoom"] img {
  transition: transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
}
.fnl-page [data-meridian] [data-meridian-role="photo-zoom"]:hover img {
  transform: scale(1.045);
}

/* Horizontal infinite marquees (destinations gallery & live desk ticker) */
.fnl-page [data-meridian-role="marquee-viewport"] {
  overflow: hidden;
  width: 100%;
  mask-image: linear-gradient(to right, transparent, #000 5%, #000 95%, transparent);
  -webkit-mask-image: linear-gradient(to right, transparent, #000 5%, #000 95%, transparent);
}
.fnl-page [data-meridian-role="marquee-track"] > div {
  flex-wrap: nowrap !important;
  width: max-content !important;
}
.fnl-page:not(.fnl-editable) [data-meridian-role="marquee-track"] > div {
  animation: meridian-marquee-x 38s linear infinite;
}
.fnl-page:not(.fnl-editable) [data-meridian-role="marquee-reverse"] > div {
  animation: meridian-marquee-rev 42s linear infinite;
}
.fnl-page [data-meridian-role="marquee-viewport"]:hover [data-meridian-role="marquee-track"] > div,
.fnl-page [data-meridian-role="marquee-viewport"]:hover [data-meridian-role="marquee-reverse"] > div {
  animation-play-state: paused;
}
@keyframes meridian-marquee-x {
  from { transform: translateX(0); }
  to { transform: translateX(-50%); }
}
@keyframes meridian-marquee-rev {
  from { transform: translateX(-50%); }
  to { transform: translateX(0); }
}

/* Process step track hairline divider with center dot */
.fnl-page [data-meridian-role="process-track"] {
  position: relative;
}
.fnl-page [data-meridian-role="process-track"]::before {
  content: "";
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: var(--site-border, #cdd6d0);
}
.fnl-page [data-meridian-role="process-track"]::after {
  content: "";
  position: relative;
  display: block;
  width: 10px;
  height: 10px;
  margin: 0 auto;
  border-radius: 999px;
  background: var(--site-accent, #b98b43);
  border: 3px solid var(--site-surface, #f6f3ee);
  box-sizing: content-box;
}

/* Sticky split columns on desktop */
@media (min-width: 961px) {
  .fnl-page [data-meridian] .fnl-column:has([data-meridian-role="sticky-col"]),
  .fnl-page [data-meridian] [data-meridian-role="sticky-col"] {
    position: sticky;
    top: 104px;
    align-self: start;
  }
}

/* FAQ Accordion cards with circular toggle emblem */
.fnl-page [data-meridian="faq"] [data-meridian-role="faq-list"] > div > div {
  background: #ffffff;
  border: 1px solid var(--site-border, #cdd6d0) !important;
  border-radius: calc(var(--site-card-radius, 20px) * 0.8) !important;
  padding: 0 22px;
  margin-bottom: 12px;
  transition: border-color 0.22s ease, box-shadow 0.22s ease;
}
.fnl-page [data-meridian="faq"] [data-meridian-role="faq-list"] > div > div:hover {
  border-color: var(--site-accent, #b98b43) !important;
}
.fnl-page [data-meridian="faq"] [data-meridian-role="faq-list"] button[aria-expanded] {
  padding: 19px 0 !important;
  font-family: var(--site-heading-font, 'Parkinsans', sans-serif) !important;
  font-size: 16.5px !important;
  font-weight: 500 !important;
  color: var(--site-heading, #15241b) !important;
}
.fnl-page [data-meridian="faq"] [data-meridian-role="faq-list"] button[aria-expanded] svg {
  width: 34px !important;
  height: 34px !important;
  padding: 8px;
  border-radius: 999px;
  border: 1px solid var(--site-border, #cdd6d0);
  background: var(--site-background, #f7f8ee);
  color: var(--site-heading, #15241b);
  transition: transform 0.25s ease, background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}
.fnl-page [data-meridian="faq"] [data-meridian-role="faq-list"] button[aria-expanded="true"] svg {
  background: var(--site-primary, #15241b);
  border-color: var(--site-primary, #15241b);
  color: var(--site-on-dark, #f7f8ee);
}

/* Footer newsletter opt-in dark input + antique-gold action pill */
.fnl-page [data-meridian="footer"] [data-meridian-role="footer-optin"] input[type="email"] {
  background: rgba(255, 255, 255, 0.08) !important;
  border: 1px solid rgba(255, 255, 255, 0.2) !important;
  color: #f7f8ee !important;
  border-radius: var(--site-button-radius, 10px) !important;
  padding: 12px 14px !important;
  flex: 1 1 180px;
}
.fnl-page [data-meridian="footer"] [data-meridian-role="footer-optin"] input[type="email"]::placeholder {
  color: rgba(247, 248, 238, 0.55) !important;
}
.fnl-page [data-meridian="footer"] [data-meridian-role="footer-optin"] button[type="submit"] {
  background: var(--site-accent, #b98b43) !important;
  color: #15241b !important;
  border: 0 !important;
  border-radius: var(--site-button-radius, 10px) !important;
  padding: 12px 18px !important;
  font-weight: 600 !important;
}

@media (prefers-reduced-motion: reduce) {
  .fnl-page:not(.fnl-editable) [data-meridian-role="marquee-track"] > div,
  .fnl-page:not(.fnl-editable) [data-meridian-role="marquee-reverse"] > div {
    animation: none !important;
    flex-wrap: wrap !important;
    width: 100% !important;
  }
}
`;
