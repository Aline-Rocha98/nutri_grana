<script setup>
import AvatarMembro from '@/Components/EspacoCompartilhado/AvatarMembro.vue';
import EsqueletoCarregamento from '@/Components/Ui/EsqueletoCarregamento.vue';

defineProps({
    atividades: {
        type: Array,
        default: null,
    },
});
</script>

<template>
    <div>
        <EsqueletoCarregamento v-if="atividades === null" class="p-4" :quantidade="4" altura="h-12" />

        <p v-else-if="atividades.length === 0" class="px-6 py-10 text-center text-sm text-ng-ink-muted">
            Nenhuma atividade registrada neste espaço.
        </p>

        <ol v-else class="divide-y divide-ng-line">
            <li v-for="atividade in atividades" :key="atividade.id" class="flex items-start gap-3 px-4 py-3.5">
                <AvatarMembro
                    :nome="atividade.usuario_nome"
                    :iniciais="atividade.usuario_iniciais"
                    :foto-url="atividade.usuario_foto_url"
                    tamanho="sm"
                />
                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-1.5 text-sm text-ng-ink">
                        <span class="material-symbols-outlined text-[16px] text-ng-brand">{{ atividade.icone }}</span>
                        <span class="font-semibold">{{ atividade.usuario_nome }}</span>
                        <span class="text-ng-ink-muted">{{ atividade.acao_rotulo }}</span>
                    </p>
                    <p v-if="atividade.detalhe" class="truncate text-xs text-ng-ink-muted">{{ atividade.detalhe }}</p>
                </div>
                <span class="shrink-0 font-mono text-[11px] text-ng-ink-subtle">{{ atividade.data_formatada }}</span>
            </li>
        </ol>
    </div>
</template>
