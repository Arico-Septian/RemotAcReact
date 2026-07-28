import './bootstrap';
import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = 'Radnext';

createInertiaApp({
    title: (title) => (title ? `${appName} ${title}` : appName),
    resolve: (name) =>
        resolvePageComponent<{ default: ResolvedComponent }>(
            `./Pages/${name}.tsx`,
            import.meta.glob<{ default: ResolvedComponent }>('./Pages/**/*.tsx'),
        ).then((module) => module.default),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: false,
});
