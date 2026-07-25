import { toast } from 'vue3-toastify';
import { router } from '@inertiajs/vue3';

const opcoesPadrao = {
    position: 'top-right',
    autoClose: 4000,
    hideProgressBar: true,
    closeOnClick: true,
    pauseOnHover: true,
    theme: 'colored',
    clearOnUrlChange: false,
};

function normalizarMensagem(mensagem) {
    if (!mensagem) {
        return null;
    }

    if (typeof mensagem === 'string') {
        return mensagem;
    }

    if (Array.isArray(mensagem)) {
        return mensagem.flat().join(' ');
    }

    if (typeof mensagem === 'object') {
        return Object.values(mensagem).flat().join(' ');
    }

    return String(mensagem);
}

export function notificarSucesso(mensagem) {
    const texto = normalizarMensagem(mensagem);
    if (!texto) {
        return;
    }

    toast.success(texto, opcoesPadrao);
}

export function notificarErro(mensagem) {
    const texto = normalizarMensagem(mensagem);
    if (!texto) {
        return;
    }

    toast.error(texto, opcoesPadrao);
}

export function notificarAviso(mensagem) {
    const texto = normalizarMensagem(mensagem);
    if (!texto) {
        return;
    }

    toast.warning(texto, opcoesPadrao);
}

export function notificarInfo(mensagem) {
    const texto = normalizarMensagem(mensagem);
    if (!texto) {
        return;
    }

    toast.info(texto, opcoesPadrao);
}

export function processarFlash(flash = {}) {
    if (flash.sucesso) {
        notificarSucesso(flash.sucesso);
    }

    if (flash.erro) {
        notificarErro(flash.erro);
    }

    if (flash.aviso) {
        notificarAviso(flash.aviso);
    }

    if (flash.info) {
        notificarInfo(flash.info);
    }
}

export function registrarFlashNotificacao() {
    router.on('success', (evento) => {
        processarFlash(evento.detail?.page?.props?.flash ?? {});
    });
}
