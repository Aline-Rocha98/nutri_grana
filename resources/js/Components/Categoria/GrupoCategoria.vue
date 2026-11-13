<script setup>
import { computed } from 'vue';
import LinhaCategoria from '@/Components/Categoria/LinhaCategoria.vue';
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';

const expandido = defineModel('expandido', { type: Boolean, default: false });

const props = defineProps({
    categoria: {
        type: Object,
        required: true,
    },
    subcategorias: {
        type: Array,
        default: () => [],
    },
    recolhivel: {
        type: Boolean,
        default: false,
    },
    arquivada: {
        type: Boolean,
        default: false,
    },
});

const possuiSubcategorias = computed(() => props.subcategorias.length > 0);
const exibirSubcategorias = computed(() => possuiSubcategorias.value && (!props.recolhivel || expandido.value));

const descricaoCategoria = computed(() => {
    const quantidade = props.subcategorias.length;
    const rotuloQuantidade = quantidade === 1 ? '1 subcategoria' : `${quantidade} subcategorias`;

    return [props.categoria.tipo_rotulo, rotuloQuantidade].filter(Boolean).join(' • ');
});

function alternarExpansao() {
    expandido.value = !expandido.value;
}
</script>

<template>
    <div>
        <LinhaCategoria
            :nome="categoria.nome"
            :icone="categoria.icone"
            :cor="categoria.cor"
            :descricao="descricaoCategoria"
            :padrao="categoria.padrao === 'S'"
            :arquivada="arquivada"
        >
            <template #acoes>
                <slot name="acoes-categoria" :categoria="categoria" />
                <BotaoIcone
                    v-if="recolhivel && possuiSubcategorias"
                    icone="expand_more"
                    :rotulo="expandido ? 'Recolher subcategorias' : 'Expandir subcategorias'"
                    :class="expandido ? 'rotate-180' : ''"
                    :aria-expanded="expandido"
                    @click="alternarExpansao"
                />
            </template>
        </LinhaCategoria>

        <ul v-if="exibirSubcategorias" class="divide-y divide-ng-line border-t border-ng-line">
            <li v-for="subcategoria in subcategorias" :key="subcategoria.id">
                <LinhaCategoria
                    :nome="subcategoria.nome"
                    :icone="subcategoria.icone"
                    :cor="subcategoria.cor"
                    :descricao="subcategoria.arquivada === 'S' ? 'Subcategoria arquivada' : 'Subcategoria'"
                    :arquivada="arquivada || subcategoria.arquivada === 'S'"
                    subcategoria
                >
                    <template v-if="$slots['acoes-subcategoria']" #acoes>
                        <slot name="acoes-subcategoria" :subcategoria="subcategoria" :pai="categoria" />
                    </template>
                </LinhaCategoria>
            </li>
        </ul>
    </div>
</template>
