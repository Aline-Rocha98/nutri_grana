const TIPO_CONFIG = {
    error:   { icon: '✕', cor: 'text-red-500',    bg: 'bg-red-50' },
    success: { icon: '✓', cor: 'text-[#1fa67e]',  bg: 'bg-green-50' },
    info:    { icon: 'i', cor: 'text-blue-500',   bg: 'bg-blue-50' },
    warn:    { icon: '!', cor: 'text-amber-500',  bg: 'bg-amber-50' },
};

export function formatarMsgConfirmBootbox(titulo, mensagem, tipo = 'info') {
    const cfg = TIPO_CONFIG[tipo] ?? TIPO_CONFIG.info;

    return `
        <div class="text-center px-2 py-1">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full ${cfg.bg}">
                <span class="text-xl font-bold ${cfg.cor}">${cfg.icon}</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">${titulo}</h3>
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