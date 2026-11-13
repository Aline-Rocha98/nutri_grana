<script setup>
defineProps({
    links: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['navegar']);

function classesDoLink(link) {
    if (link.active) {
        return 'bg-ng-brand text-white';
    }

    return link.url
        ? 'text-ng-ink-muted hover:bg-ng-brand-soft hover:text-ng-ink'
        : 'cursor-not-allowed text-ng-ink-subtle';
}

function navegarParaLink(link) {
    if (link.url) {
        emit('navegar', link.url);
    }
}
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap items-center justify-center gap-1">
        <button
            v-for="(link, indice) in links"
            :key="indice"
            type="button"
            class="min-w-[2rem] rounded-lg px-2 py-1 font-mono text-xs transition"
            :class="classesDoLink(link)"
            :disabled="!link.url"
            @click="navegarParaLink(link)"
            v-html="link.label"
        />
    </nav>
</template>
