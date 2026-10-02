<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import PostForm from '@/components/PostForm.svelte';
</script>

<AppHead title="Artikel Baru" />
<div class="mx-auto w-full max-w-4xl space-y-6 p-4">
    <div>
        <h1 class="text-2xl font-bold">Artikel baru</h1>
        <p class="text-sm text-muted-foreground">Tulis dan terbitkan devlog.</p>
    </div>
    <PostForm />
</div>
