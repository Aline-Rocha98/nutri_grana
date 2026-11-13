<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    itens: {
        type: Array,
        default: () => [],
    },
    rotulo: {
        type: String,
        default: 'Trilha de navegação',
    },
});
</script>

<template>
    <nav v-if="itens.length" :aria-label="rotulo" class="min-w-0">
        <ol class="flex min-w-0 items-center gap-2 text-xs">
            <li
                v-for="(item, indice) in itens"
                :key="`${indice}-${item.rotulo}`"
                class="flex min-w-0 items-center gap-2"
            >
                <span v-if="indice > 0" class="text-ng-ink-subtle" aria-hidden="true">/</span>

                <span
                    v-if="indice === itens.length - 1"
                    class="truncate font-semibold text-ng-ink"
                    aria-current="page"
                >
                    {{ item.rotulo }}
                </span>
                <Link
                    v-else-if="item.url"
                    :href="item.url"
                    class="truncate text-ng-ink-muted transition hover:text-ng-ink"
                >
                    {{ item.rotulo }}
                </Link>
                <span v-else class="truncate text-ng-ink-muted">
                    {{ item.rotulo }}
                </span>
            </li>
        </ol>
    </nav>
</template>
