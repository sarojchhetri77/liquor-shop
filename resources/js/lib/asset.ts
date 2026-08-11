import { usePage } from '@inertiajs/vue3';

/**
 * Resolve a file in `public/` against the path the app is mounted on, so assets
 * keep working when the app is served from a sub-directory rather than a root.
 * Route URLs need no such help — Wayfinder already bakes the base path in.
 */
export function asset(path: string): string {
    const base = usePage().props.basePath ?? '';

    return `${base}/${path.replace(/^\/+/, '')}`;
}
