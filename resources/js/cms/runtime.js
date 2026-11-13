/**
 * The runtime contract between the core and installed packages (window.ModrixCMS).
 *
 * Packages ship compiled ES modules. The core imports them before the Inertia app
 * starts; each one registers its React components:
 *
 *   const { React, registerTheme } = window.ModrixCMS;
 *   registerTheme('acme/harbor', { layout: Layout, pages: { 'Site/Page': Page }, blocks: { hero: Hero } });
 *
 *   ModrixCMS.registerPlugin('acme/gallery', {
 *       blocks: { 'acme/gallery:grid': Grid },      // page-builder blocks declared in PHP
 *       pages: { Photos: PhotosPage },              // Inertia::render('plugin:acme/gallery/Photos')
 *   });
 *
 * Anything a theme doesn't register falls back to the core component.
 */

const state = {
    theme: null,
    plugins: {},
};

export function registerTheme(name, definition = {}) {
    state.theme = {
        name,
        layout: definition.layout ?? null,
        pages: definition.pages ?? {},
        blocks: definition.blocks ?? {},
    };
}

export function registerPlugin(name, definition = {}) {
    state.plugins[name] = {
        pages: definition.pages ?? {},
        blocks: definition.blocks ?? {},
    };
}

export function activeTheme() {
    return state.theme;
}

/** Theme override → plugin block → core block (or null when nobody draws this type). */
export function blockComponent(type, coreBlocks) {
    if (state.theme?.blocks[type]) {
        return state.theme.blocks[type];
    }

    for (const plugin of Object.values(state.plugins)) {
        if (plugin.blocks[type]) {
            return plugin.blocks[type];
        }
    }

    return coreBlocks[type] ?? null;
}

/** "plugin:vendor/package/Page" → the page registered by that plugin. */
export function pluginPage(name) {
    const match = /^plugin:([a-z0-9-]+\/[a-z0-9-]+)\/(.+)$/.exec(name);

    return match ? (state.plugins[match[1]]?.pages[match[2]] ?? null) : null;
}

export function themePage(name) {
    return state.theme?.pages[name] ?? null;
}

export function themeLayout() {
    return state.theme?.layout ?? null;
}

/** Imports the compiled scripts listed in <script id="cms-packages">. A broken package is skipped. */
export async function loadPackages() {
    const node = document.getElementById('cms-packages');
    const config = node ? JSON.parse(node.textContent || '{}') : {};
    const urls = [...(config.plugins ?? []), ...(config.theme ?? [])];

    for (const url of urls) {
        try {
            await import(/* @vite-ignore */ url);
        } catch (error) {
            console.error(`[ModrixCMS] Could not load ${url}. The site continues without it.`, error);
        }
    }
}
