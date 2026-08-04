<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const pagina = usePage();
const menu = computed(() => pagina.props.menu);

const CHAVE_STORAGE = 'barraLateralExpandida';
const expandida = ref(localStorage.getItem(CHAVE_STORAGE) !== 'false');
const gruposAbertos = reactive({ ...(menu.value?.gruposAbertos ?? {}) });

watch(
    () => menu.value?.gruposAbertos,
    (valor) => {
        Object.assign(gruposAbertos, valor ?? {});
    },
    { deep: true },
);

const classeBarra = computed(() =>
    expandida.value ? 'barra-lateral--expandida' : 'barra-lateral--recolhida',
);

function alternarExpansao() {
    expandida.value = !expandida.value;
    localStorage.setItem(CHAVE_STORAGE, expandida.value ? 'true' : 'false');
}

function alternarGrupo(idGrupo) {
    gruposAbertos[idGrupo] = !gruposAbertos[idGrupo];
}

function sair() {
    const url = menu.value?.urlLogout;
    if (!url) {
        return;
    }

    router.post(url);
}
</script>

<template>
    <aside
        v-if="menu"
        id="barra-lateral"
        class="barra-lateral relative flex shrink-0 flex-col rounded-[2rem] bg-[#151a18] text-gray-300 transition-all duration-300 min-h-[calc(100vh-1.5rem)]"
        :class="classeBarra"
    >
        <button
            type="button"
            class="absolute -right-3 top-8 z-20 flex h-7 w-7 items-center justify-center rounded-full bg-[#1fa67e] text-white shadow-lg hover:bg-[#188f6b] transition"
            aria-label="Alternar barra lateral"
            @click="alternarExpansao"
        >
            <span class="material-icons text-base leading-none">
                {{ expandida ? 'chevron_left' : 'chevron_right' }}
            </span>
        </button>

        <div class="px-4 pt-5 pb-4 border-b border-white/10">
            <div
                class="barra-lateral__cabecalho-perfil flex items-center gap-3"
                :class="{ 'justify-center': !expandida }"
            >
                <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[#1fa67e]/20 text-sm font-bold text-[#1fa67e]">
                    <img
                        v-if="menu.perfil.foto_url"
                        :src="menu.perfil.foto_url"
                        alt="Foto de perfil"
                        class="h-full w-full object-cover"
                    >
                    <span v-else>{{ menu.perfil.iniciais }}</span>
                </div>
                <div v-show="expandida" class="barra-lateral__texto min-w-0">
                    <p class="text-xs text-gray-500">Olá 👋</p>
                    <p class="truncate text-sm font-semibold text-white">{{ menu.perfil.nome }}</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            <template v-for="item in menu.itens" :key="item.id">
                <Link
                    v-if="item.tipo === 'link'"
                    :href="item.url"
                    :title="item.rotulo"
                    class="barra-lateral__item group relative flex items-center gap-3 rounded-xl px-3 py-2.5 transition"
                    :class="item.ativo ? 'bg-white/10 text-white' : 'hover:bg-white/5 hover:text-white'"
                >
                    <span
                        v-if="item.ativo"
                        class="absolute left-0 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full bg-[#1fa67e]"
                    />
                    <span
                        class="material-icons shrink-0 text-xl text-gray-400 group-hover:text-[#1fa67e]"
                        :class="{ 'text-[#1fa67e]': item.ativo }"
                    >
                        {{ item.iconeMaterial }}
                    </span>
                    <span
                        v-show="expandida"
                        class="barra-lateral__texto truncate text-sm font-medium"
                    >
                        {{ item.rotulo }}
                    </span>
                </Link>

                <div v-else class="space-y-1">
                    <button
                        type="button"
                        :title="item.rotulo"
                        class="barra-lateral__item group relative flex w-full items-center gap-3 rounded-xl px-3 py-2.5 transition"
                        :class="item.ativo ? 'bg-white/10 text-white' : 'hover:bg-white/5 hover:text-white'"
                        @click="alternarGrupo(item.id)"
                    >
                        <span
                            v-if="item.ativo"
                            class="absolute left-0 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full bg-[#1fa67e]"
                        />
                        <span
                            class="material-icons shrink-0 text-xl text-gray-400 group-hover:text-[#1fa67e]"
                            :class="{ 'text-[#1fa67e]': item.ativo }"
                        >
                            {{ item.iconeMaterial }}
                        </span>
                        <span
                            v-show="expandida"
                            class="barra-lateral__texto flex-1 truncate text-left text-sm font-medium"
                        >
                            {{ item.rotulo }}
                        </span>
                        <span
                            v-show="expandida"
                            class="material-icons barra-lateral__chevron shrink-0 text-base transition-transform"
                            :class="{ 'barra-lateral__chevron--aberto': gruposAbertos[item.id] }"
                        >
                            expand_more
                        </span>
                    </button>

                    <div
                        v-show="expandida && gruposAbertos[item.id]"
                        class="barra-lateral__submenu barra-lateral__submenu--aberto ml-5 border-l border-white/10 pl-3 space-y-1"
                    >
                        <Link
                            v-for="filho in item.filhos"
                            :key="filho.rota"
                            :href="filho.url"
                            class="relative flex items-center gap-2 rounded-lg py-2 pl-3 pr-2 text-sm transition"
                            :class="filho.ativo ? 'bg-white/10 text-white font-medium' : 'text-gray-400 hover:bg-white/5 hover:text-white'"
                        >
                            <span class="absolute -left-3 top-1/2 h-px w-3 bg-white/10" />
                            <span class="truncate">{{ filho.rotulo }}</span>
                            <span
                                v-if="filho.ativo"
                                class="material-icons ml-auto shrink-0 text-sm text-[#1fa67e]"
                            >
                                chevron_right
                            </span>
                        </Link>
                    </div>
                </div>
            </template>
        </nav>

        <div class="border-t border-white/10 p-3">
            <button
                type="button"
                title="Sair"
                class="barra-lateral__item flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-gray-400 transition hover:bg-red-500/10 hover:text-red-300"
                @click="sair"
            >
                <span class="material-icons shrink-0 text-xl">logout</span>
                <span v-show="expandida" class="barra-lateral__texto">Sair</span>
            </button>
        </div>
    </aside>
</template>
