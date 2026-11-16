<script setup>
import AvatarMembro from '@/Components/EspacoCompartilhado/AvatarMembro.vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';

defineProps({
    membros: {
        type: Array,
        default: () => [],
    },
    podeGerenciar: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['remover']);
</script>

<template>
    <ul class="divide-y divide-ng-line">
        <li v-for="membro in membros" :key="membro.chave" class="flex items-center gap-3 px-4 py-3.5">
            <AvatarMembro
                :nome="membro.nome"
                :iniciais="membro.iniciais"
                :foto-url="membro.foto_url"
                :destacado="membro.eh_usuario_atual"
                tamanho="sm"
            />
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-ng-ink">
                    {{ membro.nome }}
                    <span v-if="membro.eh_usuario_atual" class="text-xs font-normal text-ng-ink-muted">(você)</span>
                </p>
                <p v-if="membro.entrou_em_formatado" class="text-xs text-ng-ink-muted">
                    Desde {{ membro.entrou_em_formatado }}
                </p>
            </div>
            <BadgeStatus
                :texto="membro.papel_rotulo ?? 'Membro'"
                :variante="membro.papel === 'administrador' ? 'brand' : 'zinc'"
                compacto
            />
            <BotaoIcone
                v-if="podeGerenciar && !membro.eh_usuario_atual && membro.url_remover"
                icone="person_remove"
                rotulo="Remover do espaço"
                variante="perigo"
                @click="emit('remover', membro)"
            />
        </li>
    </ul>
</template>
