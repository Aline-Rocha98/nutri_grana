const TIPO_CONFIG = {
    error:   { icone: 'error', cor: 'text-red-500',    bg: 'bg-red-50' },
    success: { icone: 'check_circle', cor: 'text-[#1fa67e]',  bg: 'bg-green-50' },
    info:    { icone: 'info', cor: 'text-blue-500',   bg: 'bg-blue-50' },
    warn:    { icone: 'warning', cor: 'text-amber-500',  bg: 'bg-amber-50' },
};

export function formatarMsgConfirmBootbox(titulo, mensagem, tipo = 'info') {
    const cfg = TIPO_CONFIG[tipo] ?? TIPO_CONFIG.info;

    return `
        <div class="flex flex-col items-center text-center px-2 py-1">
            <div class="flex items-center justify-center gap-2">
                <span class="material-symbols-outlined ${cfg.cor} text-3xl">
                    ${cfg.icone}
                </span>
                <h3 class="text-lg font-semibold text-gray-800">${titulo}</h3>
            </div>
            ${mensagem ? `<p class="mt-2 text-sm text-gray-600 break-words">${mensagem}</p>` : ''}
        </div>
    `;
}

export function notificacaoBootbox(titulo, mensagem, tipo = 'info', acao) {
    window.bootbox.alert({
        message: formatarMsgConfirmBootbox(titulo, mensagem, tipo),
        className: 'nutrigrana-bootbox',
        centerVertical: true,
        backdrop: true,
        callback: function () {
            if (typeof acao === 'function') {
                acao();
            }
        },
    });
}

export function notificacaoBootboxDialog(titulo, mensagem, tipo = 'info', botoes = {}) {
    window.bootbox.dialog({
        message: formatarMsgConfirmBootbox(titulo, mensagem, tipo),
        className: 'nutrigrana-bootbox',
        centerVertical: true,
        backdrop: true,
        buttons: botoes,
    });
}