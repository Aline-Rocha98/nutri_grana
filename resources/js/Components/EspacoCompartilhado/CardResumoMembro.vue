<script setup>
import AvatarMembro from '@/Components/EspacoCompartilhado/AvatarMembro.vue';
import BarraProgresso from '@/Components/BarraProgresso.vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';

defineProps({
    resumo: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <article class="ng-card flex flex-col gap-4 rounded-2xl p-5">
        <div class="flex items-center gap-3">
            <AvatarMembro
                :nome="resumo.nome"
                :iniciais="resumo.iniciais"
                :foto-url="resumo.foto_url"
                :destacado="resumo.eh_usuario_atual"
            />
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-ng-ink">
                    {{ resumo.nome }}
                    <span v-if="resumo.eh_usuario_atual" class="text-xs font-normal text-ng-ink-muted">(você)</span>
                </p>
                <p class="font-mono text-xs text-ng-ink-muted">R$ {{ resumo.total }} sob responsabilidade</p>
            </div>
            <BadgeStatus v-if="resumo.papel_rotulo" :texto="resumo.papel_rotulo" variante="zinc" compacto />
        </div>

        <div class="grid grid-cols-2 gap-3 text-xs">
            <div>
                <p class="text-ng-ink-muted">Pago</p>
                <p class="font-mono font-semibold text-ng-brand">R$ {{ resumo.pago }}</p>
            </div>
            <div class="text-right">
                <p class="text-ng-ink-muted">Pendente</p>
                <p class="font-mono font-semibold text-orange-500">R$ {{ resumo.pendente }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <BarraProgresso class="flex-1" :percentual="resumo.percentual_pago" cor="#1fa67e" />
            <span class="shrink-0 font-mono text-[10px] text-ng-brand">{{ Math.round(resumo.percentual_pago) }}% pago</span>
        </div>
    </article>
</template>
