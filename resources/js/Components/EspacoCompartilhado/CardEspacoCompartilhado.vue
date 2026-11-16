<script setup>
import { Link } from '@inertiajs/vue3';
import PilhaAvataresMembros from '@/Components/EspacoCompartilhado/PilhaAvataresMembros.vue';

defineProps({
    espaco: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="espaco.url_painel"
        class="ng-card group flex min-h-[10rem] flex-col justify-between rounded-2xl p-5 transition hover:border-ng-brand"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <span class="ng-icone-destaque h-10 w-10 rounded-xl">
                    <span class="material-symbols-outlined text-[20px]">group</span>
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-ng-ink">{{ espaco.nome }}</p>
                    <p class="text-xs text-ng-ink-muted">
                        {{ espaco.quantidade_membros }} {{ espaco.quantidade_membros === 1 ? 'membro' : 'membros' }}
                    </p>
                </div>
            </div>
            <span class="material-symbols-outlined text-[18px] text-ng-ink-subtle transition group-hover:text-ng-brand">
                arrow_forward
            </span>
        </div>

        <PilhaAvataresMembros :membros="espaco.membros" class="mt-4" />
    </Link>
</template>
