# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- Prospective clients and recruiters are equally important audiences. Both need to understand Andre's capabilities, inspect his work, and decide whether to start a project or hiring conversation.
- Andre maintains his own portfolio and writing through the personal CMS.

## Product Purpose

agungandre.my.id is Agung Andre's portfolio, personal branding website, and space for writing. It helps visitors evaluate his web and mobile work, understand the decisions behind it, and contact him through the existing email channel.

Success means visitors can make an informed assessment and start a relevant project or hiring conversation. No numerical conversion target has been established.

## Positioning

Andre is an independent full-stack developer based in Bali, Indonesia. His current profile connects interface development with the systems behind it and identifies Laravel, Svelte, and Flutter as core technologies.

Open decision: a distinctive service proposition, preferred project types, and commercial service boundaries have not been defined. Do not invent a specialization, exclusive advantage, availability, pricing, or delivery promise.

## Operating Context

- Public visitors can browse selected work, project details, the author's background, a paginated writing index, and individual articles without signing in.
- Contact uses the existing email address, contact@agungandre.my.id. GitHub and LinkedIn links provide additional profile context.
- The CMS supports deliberate manual editing and saving of projects and articles. Blog management requires the configured owner gate in addition to the existing authentication middleware.
- Local development uses EnvKit and the project's existing Vite workflow. Framework, routing, testing, and branch conventions are maintained in [AGENTS.md](AGENTS.md).

## Capabilities and Constraints

- Projects have publication and featured states, descriptions, technology metadata, cover/gallery images, and optional live and repository links. Public project routes resolve published slugs only.
- Project descriptions remain plain escaped text. Uploading a gallery replaces the entire gallery; an empty upload retains it. Do not imply unsupported append, reorder, or individual removal operations.
- Articles use Markdown with server-sanitized rendering and preview, image uploads, and draft, immediate, or scheduled publication. Only published, due articles appear publicly.
- Preserve existing authentication, authorization, upload validation, media handling, and scheduling behavior during design work. Features such as registration, passkeys, autosave, analytics, or a media library require their own explicit scope.
- Reuse the existing application structure and dependencies. Follow generated Wayfinder routes for frontend navigation and submissions.

## Brand Commitments

- Preserve Agung Andre's identity and the existing biographical account. The public profile also uses Andre and Anak Agung Gede Andre Kusuma.
- Public navigation and interface text use English. Authentication and CMS interface text use Indonesian. Preserve the language of authored project descriptions and articles.
- Bali is a biographical location, not a decorative theme.
- [docs/design-system.md](docs/design-system.md) is the existing Studio Engineering UI contract. Init does not establish a replacement visual direction.

## Evidence on Hand

- Current public profile and process copy: [resources/js/pages/Welcome.svelte](resources/js/pages/Welcome.svelte).
- Implemented public and CMS workflows: [routes/web.php](routes/web.php) and [routes/admin.php](routes/admin.php).
- Design and evaluation records: [docs/design-system.md](docs/design-system.md), [docs/redesign-validation.md](docs/redesign-validation.md), [docs/screenshots](docs/screenshots), and [docs/validation](docs/validation).
- The existing validation record identifies QA fixtures and outstanding real-content review. Its screenshots and measurements are evaluation evidence, not proof of client outcomes or current production performance.
- Do not fabricate clients, testimonials, screenshots of real work, contributions, or measured outcomes. Real project claims must be supported by the author's supplied work and contribution narratives.

## Product Principles

1. Help clients and recruiters assess fit with equal priority.
2. Explain real work and decisions with evidence the author can defend.
3. Keep browsing, reading, contact, and content maintenance understandable and dependable.
4. Preserve authored content, language choices, and existing workflow behavior.
5. Make access to essential content and actions independent of decorative effects.

## Accessibility & Inclusion

The existing UI contract requires keyboard navigation, visible focus, usable touch targets, accessible form errors and saving feedback, responsive layouts without horizontal overflow, and support for reduced motion and 200% zoom. Preserve light, dark, and system appearance preferences.

Normal text contrast must meet 4.5:1 and large text 3:1. Existing automated checks do not establish complete WCAG compliance; the recorded manual accessibility review remains outstanding. No additional audience-specific access needs have been identified.
