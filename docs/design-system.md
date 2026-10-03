# Studio Engineering
This is the UI contract for agungandre.my.id. Read this document when creating or changing a screen. The implementation is Svelte 5 + Inertia 3 + Tailwind 4; no additional component or animation packages are required.

## Audience and voice
The public portfolio helps prospective clients assess Andre's work. Public navigation and interface text use English. Authentication and CMS interface text use Indonesian. Preserve the author's project descriptions and article language. Bali is a biographical location, not a decorative theme. Do not invent clients, screenshots, contributions, or measured outcomes.

## Composition
Public screens use PublicLayout.svelte: wordmark, Work/About/Writing/Contact, appearance controls, mobile navigation, skip link, and footer. Use .studio-shell (maximum 80rem), .studio-section, .section-kicker, .section-title, and .studio-title. Hero becomes two columns at 1024px; text and actions precede the preview on mobile. Selected work uses a generous screenshot stage and a concise explanation. About follows work. No automatic carousel.

CMS uses .cms-page, .cms-header, .cms-title, .cms-panel, .cms-field, .cms-input, and .cms-actionbar. Put the create action in the page header. Lists prioritize actionable content, with separate publication and featured labels. Show a real empty state, a distinct filter-empty state, and retained input on errors. Do not disguise a missing server prop as empty content.

Authentication uses AuthSimpleLayout.svelte. Its visual panel appears from 1024px; mobile focuses on the form. No intro on auth/CMS.

## Tokens
resources/css/app.css owns all tokens in CSS-first @theme. Never create tailwind.config.js. Aliases keep existing local component classes working.

| Role | Light | Dark |
| --- | --- | --- |
| Surface | #f9f8f5 | #15151b |
| Lowest surface / card | #ffffff | #191920 |
| Low surface / sidebar | #f2f1ee | #1e1e26 |
| Container | #eae9e6 | #262630 |
| On surface | #20212a | #eeeef2 |
| Muted text | #60616d | #b6b5c4 |
| Primary / on primary | #5044bd / #ffffff | #c2b6ff / #2e2073 |
| Primary container / on container | #e8e3ff / #352779 | #39305e / #e7dfff |
| Outline | #85838f | #93919f |
| Error | #b3261e | #ffb4ab |
| Success | #236747 | #9ed8b5 |
| Warning | #79510a | #ebc47d |

Secondary and tertiary roles have independent container/on-container pairs. Status labels always contain text. Do not use brand gradients to convey published/draft/scheduled status. Gradient is limited to frame accents and small identity elements. Surfaces establish hierarchy before shadows.

Instrument Sans: 400, 500, 600, 700, self-hosted Latin WOFF2 files in resources/fonts through Laravel Vite's local font integration. Preload only 400 and 600; load other weights on demand. The OFL license is included alongside the files. Hero 40–80px, body 16–18px, interface labels 13–14px. Keep reading text around 68 characters wide. Monospace is limited to technical metadata.

Spacing scale: 4, 8, 12, 16, 24, 32, 48, 64, 96px. Control radius 12px, panels 20px, screenshot stages 28px. Small labels may use pills. Menu elevation is modest; dialog elevation is stronger. Interactive controls have at least 44px touch area. Avoid arbitrary hardcoded page palettes.

Tracking uses shared roles: --tracking-display (-0.04em), --tracking-heading (-0.03em), and --tracking-wordmark (-0.03em). Avoid compressing headings further. The portfolio wordmark and footer links have 44px targets. Selection, caret, scrollbar colors and focus rings follow the semantic palette; CMS table numerals use tabular spacing.

## Components and states
Use local Button/Input/Checkbox/Dialog/Sheet/DropdownMenu wrappers. Dialog, sheet, menu, and checkbox use installed Bits UI primitives. Preserve complete trigger/item props when using child snippets; this includes IDs, handlers, and keyboard behavior. DialogContent must have DialogTitle and DialogDescription. Test Escape, tab containment, and focus return.

Buttons support hover, pressed, focus-visible, disabled and processing. Keep action text visible while processing. Form errors use InputError with role=alert and a stable id referenced by aria-describedby on the control. Invalid controls set aria-invalid. Saving feedback uses role=status and aria-live=polite. Do not rely on color alone.

File arrays must associate individual errors (for example images.0) with their upload control as well as the array-level error. Auth and settings controls use the same associations. Dialog/Sheet close labels default to Indonesian; public navigation passes an English closeLabel.

Actual patterns:
- ProjectForm.svelte: named inputs, hidden 0/1 flags, multipart method spoofing, escaped local preview, sticky manual-save actions.
- PostForm.svelte: Markdown editor, sanitized server preview using useHttp, draft/now/scheduled, device-local schedule converted to ISO.
- ProjectPreview.svelte: dimensioned cover, failed image fallback, unique transition source.
- ThemePicker.svelte: light/dark/system aria-pressed buttons; same reactive state in all shells.

Project gallery upload replaces the complete gallery. Empty upload retains existing gallery. Do not present append/reorder/remove features the backend does not support. Description remains plain escaped text with paragraphs. Article HTML is produced only by the existing server sanitizer.

## Appearance
app.blade.php applies appearance before fonts/styles. theme.svelte.ts owns reactive state, cookie/localStorage persistence, system changes, and cleanup. Storage failure retains a session preference and cookie. System changes affect the resolved color only when the selected preference is system. Do not force a page theme or create separate theme stores.

## Motion
No new dependency. CSS/Svelte/native browser APIs are sufficient.
- Control feedback: 160ms.
- Menus/sheets: 240ms.
- Reveal budget: 360ms.
- Screenshot transition: 560ms.
- Easing: cubic-bezier(.2,0,0,1).
Animate transform/opacity. Do not animate a layout loop or run idle requestAnimationFrame loops.

The hero intro is progressive decoration; heading, navigation and CTA are always available. It runs once for portfolio:intro:v1 on direct initial home entry, with a 1200ms timeout. Skip/Escape finish immediately. Anchor, back/forward, Inertia arrival and reduced motion skip it. Storage denial uses an application memory guard. Failed/loading covers retain a readable static frame.

Inertia's viewTransition option links a selected thumbnail stage to the detail cover under project-cover. Only the clicked source receives the name; other hero/list previews use none. Reduced motion uses ordinary/static navigation. Unsupported browsers navigate normally. Return navigation restores source scroll and focus. Clear timeouts/media listeners/animation handles when components unmount.

## Data contracts and routes
PublicProjectSummary / PublicProjectDetail are in types/project.ts. Media URLs are resolved by ProjectData on the server; do not concatenate /storage in public pages. Home receives ordered published projects and three published latestPosts. Detail GET projects.show resolves a published slug only. Admin binding stays ID based. Use generated Wayfinder functions for forms and navigation.

Keep auth middleware, manage-posts owner gate, Fortify routes, upload validation and scheduling unchanged. No registration, passkey, autosave, analytics, or media library additions are part of this design.

## Screen review
Check 360, 390, 768, 1024 and 1440px in both themes, plus 200% zoom. Check long titles, absent images, real empty content, validation errors, processing and failed requests. Verify no horizontal page overflow.

Keyboard: skip link, navigation, appearance, forms, dropdown arrows, sheet/dialog focus containment, Escape and focus return. Normal text contrast ≥4.5:1; large text ≥3:1. Examine status and focus indicators too.

Performance targets are LCP ≤2.5s, INP ≤200ms, CLS ≤0.1. Lab measurements are not field p75 evidence. Target the first cover ≤200KB; set dimensions and fetch priority, lazy-load gallery images, and keep editor assets off public routes. See redesign-validation.md and testing.md for measured evidence and outstanding real-content review.

## Decision record
Studio Engineering was chosen over a pure gallery (too asset-dependent) and editorial-only layout (less visual continuity). Keep shadcn-style local wrappers plus Bits UI, with Material 3 color roles/surfaces/states. Native motion was chosen over Motion/GSAP/Three.js because screenshot transitions need no timeline framework or GPU scene. No database migration is needed for the first project detail.
