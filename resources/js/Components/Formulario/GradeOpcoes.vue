<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import CampoBase from '@/Components/Formulario/CampoBase.vue';
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    rotulo: {
        type: String,
        default: '',
    },
    obrigatorio: {
        type: Boolean,
        default: false,
    },
    dica: {
        type: String,
        default: '',
    },
    erro: {
        type: String,
        default: '',
    },
    opcoes: {
        type: Array,
        default: () => [],
    },
    colunas: {
        type: Number,
        default: 4,
    },
    carrossel: {
        type: Boolean,
        default: false,
    },
    rotuloAnterior: {
        type: String,
        default: 'Anterior',
    },
    rotuloProximo: {
        type: String,
        default: 'Próximo',
    },
});

const emit = defineEmits(['update:modelValue']);

const classesColunas = {
    2: 'grid grid-cols-2',
    3: 'grid grid-cols-2 sm:grid-cols-3',
    4: 'grid grid-cols-2 sm:grid-cols-4',
};

const trilhoCarrossel = ref(null);
const podeRolarParaAnterior = ref(false);
const podeRolarParaProximo = ref(false);
let observadorTamanho = null;

function atualizarSetasCarrossel() {
    const trilho = trilhoCarrossel.value;

    if (!trilho) {
        return;
    }

    podeRolarParaAnterior.value = trilho.scrollLeft > 0;
    podeRolarParaProximo.value = trilho.scrollLeft + trilho.clientWidth < trilho.scrollWidth - 1;
}

function rolarCarrossel(direcao) {
    const trilho = trilhoCarrossel.value;

    if (!trilho) {
        return;
    }

    trilho.scrollBy({ left: direcao * trilho.clientWidth * 0.8, behavior: 'smooth' });
}

onMounted(() => {
    if (!props.carrossel || !trilhoCarrossel.value) {
        return;
    }

    atualizarSetasCarrossel();
    observadorTamanho = new ResizeObserver(atualizarSetasCarrossel);
    observadorTamanho.observe(trilhoCarrossel.value);
});

onBeforeUnmount(() => {
    observadorTamanho?.disconnect();
});

watch(
    () => props.opcoes,
    () => nextTick(atualizarSetasCarrossel),
);
</script>

<template>
    <CampoBase :rotulo="rotulo" :obrigatorio="obrigatorio" :dica="dica" :erro="erro">
        <template v-if="carrossel && (podeRolarParaAnterior || podeRolarParaProximo)" #rotulo-extra>
            <div class="flex items-center">
                <BotaoIcone
                    icone="chevron_left"
                    :rotulo="rotuloAnterior"
                    :desabilitado="!podeRolarParaAnterior"
                    @click="rolarCarrossel(-1)"
                />
                <BotaoIcone
                    icone="chevron_right"
                    :rotulo="rotuloProximo"
                    :desabilitado="!podeRolarParaProximo"
                    @click="rolarCarrossel(1)"
                />
            </div>
        </template>

        <div
            ref="trilhoCarrossel"
            class="gap-2"
            :class="carrossel
                ? 'ng-scroll flex snap-x snap-mandatory overflow-x-auto scroll-smooth pb-1'
                : (classesColunas[colunas] ?? classesColunas[4])"
            role="radiogroup"
            @scroll.passive="atualizarSetasCarrossel"
        >
            <button
                v-for="opcao in opcoes"
                :key="opcao.valor"
                type="button"
                role="radio"
                :aria-checked="modelValue === opcao.valor"
                class="ng-selecionavel relative flex flex-col items-center justify-center gap-1.5 rounded-xl px-3 py-3"
                :class="[
                    { 'ng-selecionavel--ativo': modelValue === opcao.valor },
                    carrossel ? 'w-28 shrink-0 snap-start' : '',
                ]"
                @click="emit('update:modelValue', opcao.valor)"
            >
                <span
                    v-if="modelValue === opcao.valor"
                    class="material-symbols-outlined absolute right-1.5 top-1.5 text-[14px] text-ng-brand"
                >
                    check
                </span>
                <slot name="icone" :opcao="opcao" :ativo="modelValue === opcao.valor" />
                <span class="whitespace-nowrap text-xs font-medium text-ng-ink-secondary">{{ opcao.rotulo }}</span>
            </button>
        </div>
    </CampoBase>
</template>
