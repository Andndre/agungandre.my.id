<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { toUrl } from '@/lib/utils';
    import { dashboard, login, register } from '@/routes';
    import { onMount } from 'svelte';
    import { fly, fade } from 'svelte/transition';
    import { spring } from 'svelte/motion';

    // Auth state
    const auth = $derived(page.props.auth);

    // Projects from server (will be populated when passed as prop)
    let {
        projects = [],
    }: {
        projects?: Array<{
            id: number;
            title: string;
            description: string;
            slug: string;
            cover_image: string | null;
            tech_stack: string[] | null;
            live_url: string | null;
            repo_url: string | null;
            is_featured: boolean;
        }>;
    } = $props();

    // Scroll state
    let scrollY = $state(0);
    let mouseX = $state(0);
    let mouseY = $state(0);

    // Mouse follower spring
    const cursor = spring({ x: 0, y: 0 }, { stiffness: 0.15, damping: 20 });

    function handleMouseMove(e: MouseEvent) {
        cursor.set({ x: e.clientX, y: e.clientY });
        mouseX = e.clientX;
        mouseY = e.clientY;
    }

    // Intersection observer refs
    let heroRef = $state<HTMLElement | null>(null);
    let timelineRef = $state<HTMLElement | null>(null);
    let philosophyRef = $state<HTMLElement | null>(null);
    let projectsRef = $state<HTMLElement | null>(null);

    // Reveal states
    let heroVisible = $state(false);
    let timelineItems = $state<boolean[]>([false, false, false, false, false, false]);
    let philosophyVisible = $state(false);
    let projectsVisible = $state(false);

    onMount(() => {
        // Animate hero in
        setTimeout(() => { heroVisible = true; }, 100);

        // Setup intersection observer for scroll reveals
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const id = entry.target.getAttribute('data-timeline-index');
                        if (id !== null) {
                            timelineItems[parseInt(id)] = true;
                        }
                        if (entry.target === philosophyRef) philosophyVisible = true;
                        if (entry.target === projectsRef) projectsVisible = true;
                    }
                });
            },
            { threshold: 0.15 }
        );

        if (timelineRef) observer.observe(timelineRef);
        if (philosophyRef) observer.observe(philosophyRef);
        if (projectsRef) observer.observe(projectsRef);

        return () => observer.disconnect();
    });

    // Timeline data
    const timelineEvents = [
        {
            year: '2004',
            title: 'Born in Timuhun',
            description: 'Klungkung, Bali, Indonesia — where the journey began.',
            icon: '🌴',
        },
        {
            year: '2020',
            title: 'COVID Era: Self-Taught Start',
            description:
                'Discovered 3D modeling in Blender. Made Minecraft plugins. Built the programming logic that still guides me today.',
            icon: '🎮',
        },
        {
            year: '2022',
            title: 'Entered College',
            description:
                'Joined S1 Pendidikan Teknik Informatika at Universitas Pendidikan Ganesha (Undiksha). SDN 2 Timuhun → SMP 2 Banjarangkan → SMAS Pariwisata Saraswati Klungkung.',
            icon: '🎓',
        },
        {
            year: '2023',
            title: 'HMJ TI Coordinator',
            description:
                'Became Coordinator of the Networking & Programming Division at Informatics Student Association (HMJ TI Undiksha).',
            icon: '💻',
        },
        {
            year: '2023',
            title: 'Laravel Projects Era',
            description:
                'Built Profile, News, Recruitment, and INTEGER #5 event systems for HMJ TI. Discovered Laravel and never looked back.',
            icon: '🚀',
        },
        {
            year: 'Present',
            title: 'Fullstack Developer',
            description:
                'Handling projects from lecturers, seniors, and personal clients. Specializing in Flutter, Laravel, Next.js, Svelte, and Tailwind CSS.',
            icon: '⚡',
        },
    ];

    const philosophyCards = [
        {
            title: 'AI-Powered Velocity',
            description:
                'Leverage AI as a force multiplier — not a crutch. Ship faster without sacrificing craft or understanding.',
            icon: '⚡',
            color: 'from-cyan-400 to-blue-500',
        },
        {
            title: 'Clean, Intentional Code',
            description:
                'Every line has purpose. Build systems that are readable, maintainable, and a joy to work with.',
            icon: '🎯',
            color: 'from-violet-400 to-purple-500',
        },
        {
            title: 'Ship, Iterate, Grow',
            description:
                'Perfection is the enemy of progress. Ship, gather feedback, and evolve — fast, always forward.',
            icon: '🌊',
            color: 'from-emerald-400 to-teal-500',
        },
    ];

    const techBadges = ['Flutter', 'Laravel', 'Next.js', 'Svelte', 'Tailwind CSS'];
</script>

<svelte:window bind:scrollY onmousemove={handleMouseMove} />

<AppHead title="Andre — Fullstack Developer">
    <link rel="preconnect" href="https://rsms.me/" />
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
</AppHead>

<!-- Cursor follower (desktop only) -->
<div
    class="pointer-events-none fixed z-50 hidden h-24 w-24 rounded-full bg-gradient-to-r from-cyan-400/20 to-violet-400/20 blur-2xl transition-all duration-100 lg:block"
    style="transform: translate({$cursor.x - 48}px, {$cursor.y - 48}px);"
></div>

<div class="min-h-screen bg-[#0a0a0a] text-[#EDEDEC]">

    <!-- ═══ NAVBAR ═══ -->
    {#if heroVisible}
        <nav
            in:fade={{ duration: 400, delay: 200 }}
            class="fixed top-0 z-40 w-full border-b border-white/5 bg-[#0a0a0a]/80 backdrop-blur-xl"
        >
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <span class="font-mono text-sm font-medium tracking-tight text-cyan-400">
                    &lt;andre /&gt;
                </span>
                <div class="flex items-center gap-6">
                    {#if auth.user}
                        <Link
                            href={toUrl(dashboard())}
                            class="text-sm text-[#A1A09A] transition-colors hover:text-[#EDEDEC]"
                        >
                            Dashboard
                        </Link>
                    {:else}
                        <Link
                            href={toUrl(login())}
                            class="text-sm text-[#A1A09A] transition-colors hover:text-[#EDEDEC]"
                        >
                            Log in
                        </Link>
                        <Link
                            href={toUrl(register())}
                            class="rounded-lg border border-cyan-400/40 bg-cyan-400/10 px-4 py-1.5 text-sm text-cyan-400 transition-all hover:border-cyan-400/80 hover:bg-cyan-400/20"
                        >
                            Get in Touch
                        </Link>
                    {/if}
                </div>
            </div>
        </nav>
    {/if}

    <!-- ═══ HERO SECTION ═══ -->
    <section
        bind:this={heroRef}
        class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-6 pt-20"
    >
        <!-- Background grid -->
        <div
            class="pointer-events-none absolute inset-0 opacity-[0.03]"
            style="background-image: linear-gradient(#EDEDEC 1px, transparent 1px), linear-gradient(90deg, #EDEDEC 1px, transparent 1px); background-size: 60px 60px;"
        ></div>

        <!-- Gradient orbs -->
        <div class="pointer-events-none absolute -top-40 left-1/2 h-[500px] w-[500px] -translate-x-1/2 rounded-full bg-gradient-to-r from-cyan-500/10 to-violet-500/10 blur-[100px]"></div>
        <div class="pointer-events-none absolute bottom-0 right-1/4 h-[300px] w-[300px] rounded-full bg-emerald-500/5 blur-[80px]"></div>

        <div class="relative z-10 mx-auto max-w-4xl text-center">
            {#if heroVisible}
                <div in:fade={{ duration: 600 }}>
                    <!-- Greeting -->
                    <p
                        in:fly={{ y: 20, duration: 600, delay: 200 }}
                        class="mb-4 font-mono text-sm tracking-widest text-cyan-400"
                    >
                        HEY THERE, I'M
                    </p>

                    <!-- Name -->
                    <h1
                        in:fly={{ y: 30, duration: 700, delay: 350 }}
                        class="mb-2 bg-gradient-to-r from-white via-[#EDEDEC] to-[#A1A09A] bg-clip-text text-5xl font-bold tracking-tight text-transparent md:text-7xl"
                    >
                        Anak Agung<br />Gede Andre Kusuma
                    </h1>

                    <!-- Tagline -->
                    <div in:fly={{ y: 30, duration: 700, delay: 500 }} class="relative mb-8 mt-6">
                        <p class="text-lg text-[#A1A09A] md:text-2xl">
                            Fullstack Developer — <span class="text-cyan-400">Bali, Indonesia</span>
                        </p>
                        <div class="mt-3 flex flex-wrap justify-center gap-2">
                            {#each techBadges as badge, i}
                                <span
                                    in:fly={{ y: 10, duration: 400, delay: 600 + i * 80 }}
                                    class="rounded-full border border-white/10 bg-white/5 px-3 py-0.5 font-mono text-xs text-[#A1A09A]"
                                >
                                    {badge}
                                </span>
                            {/each}
                        </div>
                    </div>

                    <!-- CTAs -->
                    <div
                        in:fly={{ y: 20, duration: 600, delay: 800 }}
                        class="flex flex-wrap justify-center gap-4"
                    >
                        <a
                            href="#projects"
                            class="group relative rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 px-8 py-3 font-medium text-white shadow-lg shadow-cyan-500/20 transition-all hover:shadow-cyan-500/40 hover:-translate-y-0.5"
                        >
                            View My Work
                            <span class="ml-2 inline-block transition-transform group-hover:translate-x-1">→</span>
                        </a>
                        <a
                            href="#contact"
                            class="rounded-xl border border-white/20 bg-white/5 px-8 py-3 font-medium text-[#EDEDEC] backdrop-blur transition-all hover:border-white/40 hover:bg-white/10"
                        >
                            Let's Talk
                        </a>
                    </div>
                </div>
            {/if}
        </div>

        <!-- Scroll indicator -->
        <div class="absolute bottom-10 left-1/2 -translate-x-1/2">
            <div class="flex flex-col items-center gap-2">
                <span class="font-mono text-xs text-[#A1A09A]">scroll</span>
                <div class="h-12 w-5 rounded-full border border-white/20 p-1">
                    <div class="h-2 w-1 rounded-full bg-cyan-400 animate-bounce"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ TIMELINE SECTION ═══ -->
    <section class="relative py-32">
        <div class="absolute left-1/2 top-0 h-32 w-px bg-gradient-to-b from-transparent via-cyan-400/40 to-transparent"></div>

        <div class="mx-auto max-w-3xl px-6">
            <div bind:this={timelineRef} class="mb-16 text-center">
                <p class="mb-2 font-mono text-xs tracking-widest text-cyan-400">JOURNEY</p>
                <h2 class="text-3xl font-bold md:text-4xl">My Story</h2>
                <p class="mt-3 text-[#A1A09A]">
                    Born in Klungkung, Bali · Education: S1 Pend. Teknik Informatika, Undiksha
                </p>
            </div>

            <div class="relative">
                <!-- Center line -->
                <div class="absolute left-6 top-0 h-full w-px bg-gradient-to-b from-cyan-400/40 via-violet-400/40 to-transparent"></div>

                <div class="space-y-12">
                    {#each timelineEvents as event, i}
                        <div
                            data-timeline-index={i}
                            class="relative pl-16"
                        >
                            <!-- Dot -->
                            <div
                                class="absolute left-4 top-1.5 flex h-4 w-4 items-center justify-center rounded-full border-2 {timelineItems[i]
                                    ? 'border-cyan-400 bg-cyan-400 shadow-[0_0_12px_rgba(34,211,238,0.5)]'
                                    : 'border-white/20 bg-transparent'}"
                                style="transition: all 0.5s ease {i * 0.1}s;"
                            ></div>

                            <!-- Card -->
                            {#if timelineItems[i]}
                                <div
                                    in:fly={{ x: -20, duration: 500, delay: i * 80 }}
                                    class="group relative rounded-xl border border-white/10 bg-white/5 p-5 backdrop-blur-sm transition-all hover:border-white/20 hover:bg-white/[0.07]"
                                >
                                    <div class="absolute -left-10 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-[#0a0a0a] text-lg">
                                        {event.icon}
                                    </div>
                                    <span class="font-mono text-xs text-cyan-400">{event.year}</span>
                                    <h3 class="mt-1 font-semibold text-lg">{event.title}</h3>
                                    <p class="mt-1 text-sm leading-relaxed text-[#A1A09A]">{event.description}</p>
                                </div>
                            {/if}
                        </div>
                    {/each}
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ PHILOSOPHY SECTION ═══ -->
    <section
        bind:this={philosophyRef}
        class="relative py-32"
    >
        <!-- Section bg -->
        <div class="absolute inset-0 bg-gradient-to-b from-[#0a0a0a] via-[#0d0d14] to-[#0a0a0a]"></div>

        <div class="relative z-10 mx-auto max-w-5xl px-6">
            <div class="mb-16 text-center">
                <p class="mb-2 font-mono text-xs tracking-widest text-violet-400">PHILOSOPHY</p>
                <h2 class="text-3xl font-bold md:text-4xl">Vibe Engineering</h2>
                <p class="mx-auto mt-4 max-w-xl text-[#A1A09A]">
                    The art of shipping fast without compromising quality. AI as a multiplier, not a replacement.
                    Clean code, clear intent, constant growth.
                </p>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                {#if philosophyVisible}
                    {#each philosophyCards as card, i}
                        <div
                            in:fly={{ y: 30, duration: 600, delay: i * 120 }}
                            class="group relative overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm transition-all duration-500 hover:border-white/20 hover:bg-white/[0.07]"
                        >
                            <!-- Glow -->
                            <div
                                class="absolute -top-20 -right-20 h-40 w-40 rounded-full bg-gradient-to-r {card.color} opacity-0 blur-3xl transition-opacity duration-500 group-hover:opacity-20"
                            ></div>

                            <div class="relative">
                                <div class="mb-4 text-3xl">{card.icon}</div>
                                <h3 class="mb-2 font-semibold text-lg">{card.title}</h3>
                                <p class="text-sm leading-relaxed text-[#A1A09A]">{card.description}</p>
                            </div>

                            <!-- Bottom accent line -->
                            <div class="mt-6 h-px bg-gradient-to-r from-transparent {card.color} to-transparent opacity-50"></div>
                        </div>
                    {/each}
                {/if}
            </div>
        </div>
    </section>

    <!-- ═══ PROJECTS SECTION ═══ -->
    <section
        bind:this={projectsRef}
        id="projects"
        class="relative py-32"
    >
        <div class="mx-auto max-w-6xl px-6">
            <div class="mb-16 flex items-end justify-between">
                <div>
                    <p class="mb-2 font-mono text-xs tracking-widest text-emerald-400">WORK</p>
                    <h2 class="text-3xl font-bold md:text-4xl">Featured Projects</h2>
                </div>
                <a
                    href="#contact"
                    class="hidden text-sm text-[#A1A09A] transition-colors hover:text-cyan-400 md:block"
                >
                    View all projects →
                </a>
            </div>

            {#if projectsVisible}
                {#if projects.length === 0}
                    <!-- Empty state -->
                    <div
                        in:fade={{ duration: 400 }}
                        class="flex min-h-48 flex-col items-center justify-center rounded-2xl border border-dashed border-white/10 bg-white/[0.02] text-center"
                    >
                        <div class="mb-3 text-4xl">🚀</div>
                        <p class="font-medium">Projects coming soon</p>
                        <p class="mt-1 text-sm text-[#A1A09A]">
                            Log in to the admin panel to add your first project.
                        </p>
                    </div>
                {:else}
                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {#each projects as project, i}
                            <div
                                in:fly={{ y: 30, duration: 500, delay: i * 100 }}
                                class="group relative overflow-hidden rounded-2xl border border-white/10 bg-white/5 backdrop-blur-sm transition-all duration-500 hover:border-white/20 hover:bg-white/[0.07]"
                            >
                                <!-- Cover image -->
                                <div class="aspect-video w-full overflow-hidden bg-gradient-to-br from-cyan-900/30 to-violet-900/30">
                                    {#if project.cover_image}
                                        <img
                                            src={project.cover_image}
                                            alt={project.title}
                                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                        />
                                    {:else}
                                        <div class="flex h-full items-center justify-center">
                                            <div class="text-4xl font-bold text-white/10">{project.title.charAt(0)}</div>
                                        </div>
                                    {/if}
                                </div>

                                <div class="p-5">
                                    {#if project.is_featured}
                                        <span class="mb-2 inline-flex items-center gap-1 rounded-full bg-cyan-400/10 px-2 py-0.5 font-mono text-xs text-cyan-400">
                                            ★ Featured
                                        </span>
                                    {/if}

                                    <h3 class="mt-1 font-semibold text-lg">{project.title}</h3>
                                    <p class="mt-1 line-clamp-2 text-sm leading-relaxed text-[#A1A09A]">
                                        {project.description}
                                    </p>

                                    <!-- Tech stack -->
                                    {#if project.tech_stack && project.tech_stack.length > 0}
                                        <div class="mt-3 flex flex-wrap gap-1.5">
                                            {#each project.tech_stack.slice(0, 4) as tech}
                                                <span class="rounded-full bg-white/5 px-2 py-0.5 font-mono text-xs text-[#A1A09A]">
                                                    {tech}
                                                </span>
                                            {/each}
                                        </div>
                                    {/if}

                                    <!-- Links -->
                                    <div class="mt-4 flex gap-3">
                                        {#if project.live_url}
                                            <a
                                                href={project.live_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="flex items-center gap-1.5 text-sm text-cyan-400 transition-colors hover:text-cyan-300"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                                Live
                                            </a>
                                        {/if}
                                        {#if project.repo_url}
                                            <a
                                                href={project.repo_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="flex items-center gap-1.5 text-sm text-[#A1A09A] transition-colors hover:text-white"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
                                                </svg>
                                                Source
                                            </a>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        {/each}
                    </div>
                {/if}
            {/if}
        </div>
    </section>

    <!-- ═══ CONTACT / FOOTER ═══ -->
    <section
        id="contact"
        class="relative border-t border-white/5 py-32"
    >
        <div class="absolute inset-0 bg-gradient-to-t from-[#0a0a0a] to-transparent"></div>

        <div class="relative z-10 mx-auto max-w-2xl px-6 text-center">
            <p class="mb-2 font-mono text-xs tracking-widest text-cyan-400">CONTACT</p>
            <h2 class="text-3xl font-bold md:text-4xl">Let's Build Something Great</h2>
            <p class="mt-4 text-[#A1A09A]">
                Have a project in mind? Want to collaborate? Or just want to say hi?
                I'm always open to discussing new opportunities.
            </p>

            <a
                href="mailto:andre@example.com"
                class="mt-8 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-violet-600 px-8 py-3.5 font-medium text-white shadow-lg shadow-cyan-500/20 transition-all hover:-translate-y-0.5 hover:shadow-cyan-500/40"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Get in Touch
            </a>

            <div class="mt-12 flex justify-center gap-6">
                <!-- GitHub -->
                <a
                    href="#"
                    class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-[#A1A09A] transition-all hover:border-white/30 hover:bg-white/10 hover:text-white"
                    aria-label="GitHub"
                >
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
                    </svg>
                </a>
                <!-- LinkedIn -->
                <a
                    href="#"
                    class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-[#A1A09A] transition-all hover:border-white/30 hover:bg-white/10 hover:text-white"
                    aria-label="LinkedIn"
                >
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                </a>
                <!-- Twitter/X -->
                <a
                    href="#"
                    class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-[#A1A09A] transition-all hover:border-white/30 hover:bg-white/10 hover:text-white"
                    aria-label="Twitter"
                >
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                </a>
            </div>

            <p class="mt-16 font-mono text-xs text-[#3E3E3A]">
                Built with Laravel + Inertia + Svelte · {new Date().getFullYear()}
            </p>
        </div>
    </section>
</div>