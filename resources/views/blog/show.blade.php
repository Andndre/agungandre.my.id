<x-blog-layout :title="$post->title" :description="$post->excerpt">
    <div class="studio-shell py-12 sm:py-20">
        <div class="mx-auto max-w-3xl">
            <a href="{{ route('blog.index') }}" class="studio-link"><x-public-icon name="arrow-left" />All writing</a>
            <article class="mt-10">
                <div class="flex flex-wrap gap-4 text-sm text-muted-foreground">
                    <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->format('F j, Y') }}</time>
                    <span>{{ $post->reading_time }} min read</span>
                </div>
                <h1 class="mt-5 wrap-break-word text-4xl leading-tight font-semibold tracking-tight sm:text-5xl">{{ $post->title }}</h1>
                <p class="mt-6 text-lg leading-relaxed text-muted-foreground">{{ $post->excerpt }}</p>
                <div class="studio-prose prose mt-12 max-w-none prose-img:rounded-xl prose-pre:overflow-x-auto">
                    {{-- HTML is produced exclusively by the existing PostMarkdown renderer. --}}
                    {!! $contentHtml !!}
                </div>
            </article>
        </div>
    </div>
</x-blog-layout>
