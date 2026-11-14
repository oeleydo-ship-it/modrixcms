/**
 * The page builder entry point.
 *
 * Mounted by resources/views/builder.blade.php, which puts the page id on the
 * root element. The builder is a separate bundle from the dashboard because it
 * pulls in dnd-kit and the whole block registry, and most dashboard sessions
 * never open it.
 */

import { createRoot } from 'react-dom/client';
import { ThemeProvider } from '@shared/theme/ThemeProvider';
import { ToastProvider } from '@shared/ui/Toast';
import { configureApi } from '@shared/api/client';
import '@shared/styles/app.css';
import { BuilderApp } from './BuilderApp';

configureApi({
    onUnauthenticated: () => {
        const here = `${window.location.pathname}${window.location.search}`;
        window.location.assign('/admin/login');
    },
});

function mount(): void {
    const element = document.getElementById('builder');

    if (!element) {
        throw new Error('#builder is missing — check resources/views/builder.blade.php.');
    }

    const pageId = element.dataset['pageId'];
    const componentId = element.dataset['componentId'];

    if (!pageId && !componentId) {
        throw new Error('The builder needs a data-page-id or data-component-id on its root element.');
    }

    createRoot(element).render(
        <ThemeProvider>
            <ToastProvider>
                <BuilderApp source={componentId ? { kind: 'component', id: componentId } : { kind: 'page', id: pageId ?? '' }} />
            </ToastProvider>
        </ThemeProvider>,
    );
}

mount();
