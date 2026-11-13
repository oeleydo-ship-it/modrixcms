import React from 'react';
import * as jsxRuntime from 'react/jsx-runtime';
import { createRoot } from 'react-dom/client';
import { createInertiaApp, Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { activeTheme, loadPackages, pluginPage, registerPlugin, registerTheme, themePage } from './cms/runtime';
import * as ui from './cms/ui';
import SiteShell from './cms/site/SiteShell';
import MissingPage from './cms/MissingPage';

const corePages = import.meta.glob('./Pages/**/*.jsx');

// Everything packages may use. Packages must not bundle their own React.
window.ModrixCMS = Object.freeze({
    version: '1.0.0',
    React,
    jsx: jsxRuntime,
    Inertia: { Head, Link, router, useForm, usePage },
    ui,
    registerTheme,
    registerPlugin,
    activeTheme,
});

async function resolve(name) {
    let page;

    if (name.startsWith('plugin:')) {
        page = pluginPage(name) ?? MissingPage;
    } else {
        page = themePage(name) ?? (await corePages[`./Pages/${name}.jsx`]?.())?.default ?? MissingPage;
    }

    // Public pages are drawn inside the theme's layout (or the core one).
    if (name.startsWith('Site/') && page.layout === undefined) {
        page.layout = (children) => <SiteShell>{children}</SiteShell>;
    }

    return page;
}

(async () => {
    await loadPackages();

    createInertiaApp({
        resolve,
        title: (title) => title,
        setup({ el, App, props }) {
            createRoot(el).render(<App {...props} />);
        },
        progress: { color: '#5b3df5' },
    });
})();
