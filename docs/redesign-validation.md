# Redesign validation
Branch: feature/portfolio-redesign. Implementation date: 2026-10-03.

## Content baseline
The initial local database contained zero projects and zero articles. Production-facing empty states were reviewed separately from QA fixture states. No client project or result has been invented. The hero fallback is an explicitly labeled process illustration. Three real project screenshots/descriptions are still required for final editorial and asset-quality review.

The former landing source used a forced dark palette, gradient/glow, cursor animation loop, and gated reveal content. Those behaviors were removed. Runtime automation was unavailable during the initial audit, so no trustworthy before-change browser screenshot exists.

## Evidence
Backend contracts and regression tests cover published/featured ordering, public draft/missing 404, resolved media, escaped descriptions, admin edit props, multipart method spoofing, explicit false flags, sanitized owner-authorized article preview without persistence, and unchanged auth access.

Visual/browser evidence and the final quality command results are recorded below after execution. Fixture screenshots are labeled QA and do not represent Andre's client work.

## Runtime constraints
Chrome DevTools MCP is not configured. The web-perf skill's MCP workflow cannot run. The Windows Computer Use Node runtime also exits at initialization. Verification therefore uses the existing local Chrome executable, ChromeDriver/Pest Dusk, and a headless Chrome DevTools Protocol test harness. These checks require no new npm dependency and no changes to the user's browser profile.

Lab performance uses a local isolated PHP server, production assets, simulated mobile network and CPU throttling. This does not establish real-user INP or field p75. Production TLS/compression/cache headers and real image payloads must be checked at deployment.

## Visual and interaction results

140 distinct screen/viewport/theme combinations passed the final geometry and JavaScript-error checks: 70 public fixture cases, 50 CMS fixture cases, and 20 real empty-state cases. Widths: 360, 390, 768, 1024, 1440px; light and dark. The final CMS matrix includes the localized article editor. Lazy media was loaded by scrolling before captures. Representative [screenshots](screenshots/README.md) are committed; full machine-readable evidence is in [validation/](validation/).

Reviewed the public hero/work composition, mobile flow, quiet login, CMS list density, editor controls, long title wrapping, absent-cover fallback, and real empty states. No horizontal page overflow or JavaScript errors were reported. Desktop 200% zoom geometry was checked using a 720×450 CSS viewport equivalent to a 1440×900 viewport at 200%; native browser zoom and assistive-technology review remain manual release checks.

31 scripted interaction checks passed. They cover first/repeat intro, Skip/Escape, reduced motion, storage denial, no View Transitions API, theme/system changes, idle animations, shared screenshot naming, project return focus/scroll, labeled mobile menu and focus trap/return, local escaped project preview, field error association/input retention, sanitized article preview, editor dirty state, and CMS sidebar focus return. A real focus-return defect in article preview was found and fixed before the final run.

Twenty normal-text role pairs were measured across the two themes. Minimum contrast: light **5.05:1**, dark **7.28:1**. This verifies the role combinations, not every possible user-authored image, Markdown color, or third-party editor state. Buttons and form controls use 44px targets; a complete manual WCAG audit is still required before release.

## Performance results

Lab setup: Chrome 150, 390×844 viewport, 4× CPU slowdown, 150ms simulated network latency, 200,000 bytes/s download, disabled cache, three cold loads, production build. This uses custom CDP/PerformanceObserver measurements rather than Lighthouse or field telemetry.

| Environment | LCP samples | Median LCP | Observed layout-shift sum |
| --- | --- | --- | --- |
| Isolated PHP, three QA public projects, no compression | 4.308 / 3.732 / 3.576s | **3.732s** | 0 |
| Real EnvKit HTTPS, empty portfolio, no asset compression observed | 3.688 / 3.112 / 3.024s | **3.112s** | 0 |

The **2.5s LCP target is not met**. These are separate content environments and cannot be compared as a before/after experiment. The earlier QA implementation measured a median 5.576s and approximately 787KB of selected resources; after route/layout splitting, local WOFF2 fonts and current-page/cover hints, the QA transfer is approximately 462KB. The main application JavaScript chunk fell from approximately 411KB to 193KB. CMS/editor dependencies stay on their routes; Milkdown remains a large editor-only chunk and the build reports that warning.

The scripted trusted-input Event Timing samples are recorded in [event-timing.json](validation/event-timing.json); the longest observed event duration was **80ms**. They are lab event durations, **not an INP score**. The layout-shift observer sums entries without recent input; it does not implement the complete field CLS session-window calculation. No field p75 claim is made.

The synthetic QA first cover is under 80KB and has dimensions; gallery images are lazy and preserve aspect ratio. Actual project covers should stay under 200KB. Before release, verify static asset compression/cache headers and measure with real screenshots on the production-equivalent host, then resolve the LCP target and collect field Web Vitals where available.

## Quality and release status

Production build, Svelte types (0 errors/warnings), ESLint, full Pint check and `git diff --check` pass. Pest: **79 passed, 2 skipped, 500 assertions**. The two existing registration tests are skipped because registration is disabled in Fortify. Dusk: **6 passed, 18 assertions**, covering public navigation, Indonesian login labels, valid/invalid credentials, reset navigation, and checked/unchecked remember cookies. Browser assertions now wait for the asynchronously resolved Inertia form before accessing controls.

The development database remains **0 projects / 0 posts**. The .env SHA-256 matched before and after Dusk. Browser migrations and QA seeds used only the guarded dedicated SQLite file. No dependency or database migration was added by the redesign. Unrelated local skill/IDE-helper installation changes are excluded from its commit. Generated Wayfinder routes remain generated artifacts.

Impeccable was applied as a bounded final polish pass against the existing Studio Engineering contract, using source and desktop/mobile screenshot evidence. It corrected over-compressed tracking, footer touch targets, semantic feedback colors, public/CMS close labels, and missing error associations in auth, settings, scheduling, and gallery upload. The pinned composition, palette, typography, panel radii and annotated layout remain the visual authority. Product/context initialization was not rerun for this scoped refinement.

The PR targets `dev` and remains draft for real-content, manual accessibility, and performance acceptance review. No production deployment is performed. Three real works with screenshots and defensible contribution narratives are needed for editorial validation; QA samples are never copied to the development or production database.

Separate pre-existing policy finding: User does not implement MustVerifyEmail, so the current verified middleware does not enforce email verification. This redesign preserves that policy and does not add signup, passkeys, or new authorization rules.
