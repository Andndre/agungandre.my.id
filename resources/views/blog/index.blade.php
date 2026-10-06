<x-blog-layout title="Writing" description="Notes on building interfaces, systems, and the things I learn along the way.">
    <div class="studio-shell py-16 sm:py-24">
        <p class="section-kicker">Notes from the work</p>
        <h1 class="section-title mt-5">Writing &amp; experiments.</h1>
        <p class="mt-5 max-w-2xl text-lg leading-relaxed text-muted-foreground">Thoughts on projects, code, and the things I learn along the way.</p>
        @if ($posts->isEmpty())
            <div class="studio-empty mt-12">
                <h2 class="text-xl font-semibold">Notes are on the way.</h2>
                <p class="text-muted-foreground">New articles will appear here when they are ready.</p>
            </div>
        @else
            <div class="mt-12 grid gap-6 md:grid-cols-2">
                @foreach ($posts as $post)
                    <article class="cms-panel flex flex-col">
                        <div class="flex flex-wrap gap-3 text-xs text-muted-foreground">
                            <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->format('M j, Y') }}</time>
                            <span>{{ $post->reading_time }} min read</span>
                        </div>
                        <h2 class="mt-5 wrap-break-word text-2xl font-semibold">
                            <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-primary">{{ $post->title }}</a>
                        </h2>
                        <p class="mt-4 line-clamp-3 text-muted-foreground">{{ $post->excerpt }}</p>
                        <a href="{{ route('blog.show', $post->slug) }}" class="studio-link mt-auto pt-6">Read article<x-public-icon /></a>
                    </article>
                @endforeach
            </div>
            <nav class="mt-10 flex justify-between gap-4" aria-label="Writing pagination">
                @if ($posts->previousPageUrl())
                    <a class="studio-button quiet" href="{{ $posts->previousPageUrl() }}"><x-public-icon name="arrow-left" />Previous</a>
                @else
                    <span></span>
                @endif
                @if ($posts->nextPageUrl())
                    <a class="studio-button quiet" href="{{ $posts->nextPageUrl() }}">Next<x-public-icon name="arrow-right" /></a>
                @endif
            </nav>
        @endif
    </div>
</x-blog-layout>
