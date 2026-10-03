export const projectMotion = $state({
    source: '',
    returnUrl: '',
    scrollY: 0,
    returning: false,
});
let introConsidered = false;
const initialPath =
    typeof window === 'undefined' ? '' : window.location.pathname;
export function claimIntro(): boolean {
    if (introConsidered || typeof window === 'undefined') {
        return false;
    }

    introConsidered = true;
    const navigation = performance.getEntriesByType('navigation')[0] as
        | PerformanceNavigationTiming
        | undefined;

    if (
        initialPath !== '/' ||
        window.location.hash ||
        navigation?.type === 'back_forward' ||
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    ) {
        return false;
    }

    try {
        if (localStorage.getItem('portfolio:intro:v1')) {
            return false;
        }

        localStorage.setItem('portfolio:intro:v1', 'seen');
    } catch {
        /* In-memory guard covers Inertia navigation when storage is denied. */
    }

    return true;
}
export function selectProject(
    event: MouseEvent | undefined,
    source: string,
): void {
    if (
        event &&
        (event.button !== 0 ||
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey)
    ) {
        return;
    }

    projectMotion.source = source;
    projectMotion.returnUrl =
        window.location.pathname +
        window.location.search +
        window.location.hash;
    projectMotion.scrollY = window.scrollY;
}
export function returnToWork(event?: MouseEvent): void {
    if (
        !event ||
        (event.button === 0 &&
            !event.metaKey &&
            !event.ctrlKey &&
            !event.shiftKey &&
            !event.altKey)
    ) {
        projectMotion.returning = true;
    }
}
