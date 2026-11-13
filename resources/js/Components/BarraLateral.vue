<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const pagina = usePage();
const menu = computed(() => pagina.props.menu);

const CHAVE_STORAGE = 'barraLateralExpandida';
const expandida = ref(localStorage.getItem(CHAVE_STORAGE) !== 'false');
const gruposAbertos = reactive({ ...(menu.value?.gruposAbertos ?? {}) });
const fotoSidebarComErro = ref(false);

watch(
    () => menu.value?.perfil?.foto_url,
    () => {
        fotoSidebarComErro.value = false;
    },
);

watch(
    () => menu.value?.gruposAbertos,
    (valor) => {
        Object.assign(gruposAbertos, valor ?? {});
    },
    { deep: true },
);

const mostrarFotoSidebar = computed(
    () => Boolean(menu.value?.perfil?.foto_url) && !fotoSidebarComErro.value,
);

const urlPerfil = computed(() => pagina.props.rotas?.usuarioPerfil ?? '#');

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

function classeItemPrincipal(ativo) {
    return ativo ? 'text-ng-brand' : 'text-zinc-400 hover:bg-white/5 hover:text-white';
}

function classeSubitem(ativo) {
    return ativo
        ? 'ng-selecionavel--ativo font-medium text-ng-brand'
        : 'text-zinc-500 hover:bg-white/5 hover:text-white';
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
        class="barra-lateral ng-sidebar sticky top-0 z-40 flex h-screen shrink-0 flex-col transition-all duration-300 ease-smooth"
        :class="classeBarra"
    >
        <button
            type="button"
            class="absolute -right-3 top-5 z-20 flex h-6 w-6 items-center justify-center rounded-full border border-ng-line-strong bg-ng-sidebar text-zinc-400 shadow-sm transition hover:border-ng-brand hover:text-ng-brand"
            aria-label="Alternar barra lateral"
            @click="alternarExpansao"
        >
            <span class="material-icons text-sm leading-none">
                {{ expandida ? 'chevron_left' : 'chevron_right' }}
            </span>
        </button>

        <div class="flex h-16 shrink-0 items-center border-b border-ng-line px-4">
            <Link
                :href="urlPerfil"
                class="barra-lateral__cabecalho-perfil flex min-w-0 flex-1 items-center gap-3 rounded-xl px-1 py-1 transition hover:bg-white/5"
                title="Meu Perfil"
            >
                <div class="relative shrink-0">
                    <div class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-[#1fa67e]/20 text-xs font-bold text-[#1fa67e]">
                        <img
                            v-if="mostrarFotoSidebar"
                            :src="menu.perfil.foto_url"
                            alt=""
                            class="h-full w-full object-cover"
                            @error="fotoSidebarComErro = true"
                        >
                        <span v-else>{{ menu.perfil.iniciais }}</span>
                    </div>
                    <span
                        class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-ng-sidebar bg-[#1fa67e]"
                        aria-hidden="true"
                    />
                </div>
                <div v-show="expandida" class="barra-lateral__texto min-w-0">
                    <p class="text-[11px] leading-tight text-zinc-500">Olá</p>
                    <p class="truncate text-sm font-semibold leading-tight text-white">{{ menu.perfil.nome }}</p>
                </div>
            </Link>
        </div>

        <nav class="ng-scroll flex-1 space-y-1 overflow-y-auto px-3 py-4">
            <template v-for="item in menu.itens" :key="item.id">
                <Link
                    v-if="item.tipo === 'link'"
                    :href="item.url"
                    :title="item.rotulo"
                    class="barra-lateral__item group flex items-center gap-3 rounded-lg px-3 py-2 transition"
                    :class="classeItemPrincipal(item.ativo)"
                >
                    <span class="material-icons shrink-0 text-lg">
                        {{ item.iconeMaterial }}
                    </span>
                    <span
                        v-show="expandida"
                        class="barra-lateral__texto truncate text-[13px] font-medium"
                    >
                        {{ item.rotulo }}
                    </span>
                </Link>

                <div v-else class="space-y-1">
                    <button
                        type="button"
                        :title="item.rotulo"
                        class="barra-lateral__item group flex w-full items-center gap-3 rounded-lg px-3 py-2 transition"
                        :class="classeItemPrincipal(item.ativo)"
                        @click="alternarGrupo(item.id)"
                    >
                        <span class="material-icons shrink-0 text-lg">
                            {{ item.iconeMaterial }}
                        </span>
                        <span
                            v-show="expandida"
                            class="barra-lateral__texto flex-1 truncate text-left text-[13px] font-medium"
                        >
                            {{ item.rotulo }}
                        </span>
                        <span
                            v-show="expandida"
                            class="material-icons barra-lateral__chevron shrink-0 text-base text-zinc-500 transition-transform"
                            :class="{ 'barra-lateral__chevron--aberto': gruposAbertos[item.id] }"
                        >
                            expand_more
                        </span>
                    </button>

                    <div
                        v-show="expandida && gruposAbertos[item.id]"
                        class="barra-lateral__submenu barra-lateral__submenu--aberto ml-5 space-y-1 border-l border-ng-line pl-3"
                    >
                        <Link
                            v-for="filho in item.filhos"
                            :key="filho.url"
                            :href="filho.url"
                            class="flex items-center rounded-lg border border-transparent px-3 py-1.5 text-[13px] transition"
                            :class="classeSubitem(filho.ativo)"
                        >
                            <span class="truncate">{{ filho.rotulo }}</span>
                        </Link>
                    </div>
                </div>
            </template>
        </nav>

        <div class="shrink-0 border-t border-ng-line p-3">
            <button
                type="button"
                title="Sair do sistema"
                class="barra-lateral__item flex w-full items-center gap-3 rounded-lg px-3 py-2 text-[13px] text-zinc-500 transition hover:bg-red-500/10 hover:text-red-300"
                @click="sair"
            >
                <span class="material-icons shrink-0 text-lg">logout</span>
                <span v-show="expandida" class="barra-lateral__texto">Sair do sistema</span>
            </button>
        </div>
    </aside>
</template>
