const CHAVE_STORAGE_EXPANSAO = 'barraLateralExpandida';

function obterTokenCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content;
}

function configurarSair(barra) {
    const botao = barra.querySelector('[data-acao="sair"]');
    if (!botao) {
        return;
    }

    botao.addEventListener('click', () => {
        const urlLogout = botao.dataset.urlLogout;
        if (!urlLogout || botao.disabled) {
            return;
        }

        botao.disabled = true;

        window.$.ajax({
            url: urlLogout,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': obterTokenCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            success(dados) {
                window.location.href = dados.redirect || '/login';
            },
            error() {
                botao.disabled = false;
            },
        });
    });
}

function aplicarExpansao(barra, expandido) {
    barra.classList.toggle('barra-lateral--expandida', expandido);
    barra.classList.toggle('barra-lateral--recolhida', !expandido);

    barra.querySelectorAll('[data-visivel-expandido]').forEach((elemento) => {
        elemento.hidden = !expandido;
    });

    const iconeAlternar = barra.querySelector('[data-icone-alternar]');
    if (iconeAlternar) {
        iconeAlternar.textContent = expandido ? 'chevron_left' : 'chevron_right';
    }
}

function aplicarGrupo(barra, idGrupo, aberto) {
    const submenu = barra.querySelector(`[data-submenu="${idGrupo}"]`);
    const chevron = barra.querySelector(`[data-grupo-chevron="${idGrupo}"]`);

    if (submenu) {
        submenu.classList.toggle('barra-lateral__submenu--aberto', aberto);
        submenu.hidden = !aberto;
    }

    if (chevron) {
        chevron.classList.toggle('barra-lateral__chevron--aberto', aberto);
    }
}

function aplicarGrupos(barra, gruposAbertos, expandido) {
    Object.entries(gruposAbertos).forEach(([idGrupo, aberto]) => {
        aplicarGrupo(barra, idGrupo, expandido && aberto);
    });
}

export function initBarraLateral() {
    const barra = document.getElementById('barra-lateral');
    if (!barra) {
        return;
    }

    const expandido = localStorage.getItem(CHAVE_STORAGE_EXPANSAO) !== 'false';
    let gruposAbertos = {};

    try {
        gruposAbertos = JSON.parse(barra.dataset.gruposAbertos || '{}');
    } catch {
        gruposAbertos = {};
    }

    aplicarExpansao(barra, expandido);
    aplicarGrupos(barra, gruposAbertos, expandido);

    barra.querySelector('[data-acao="alternar-expansao"]')?.addEventListener('click', () => {
        const novoEstado = !barra.classList.contains('barra-lateral--expandida');
        localStorage.setItem(CHAVE_STORAGE_EXPANSAO, novoEstado ? 'true' : 'false');
        aplicarExpansao(barra, novoEstado);
        aplicarGrupos(barra, gruposAbertos, novoEstado);
    });

    barra.querySelectorAll('[data-acao="alternar-grupo"]').forEach((botao) => {
        botao.addEventListener('click', () => {
            const idGrupo = botao.dataset.grupoId;
            if (!idGrupo) {
                return;
            }

            gruposAbertos[idGrupo] = !gruposAbertos[idGrupo];
            const expandidoAtual = barra.classList.contains('barra-lateral--expandida');
            aplicarGrupo(barra, idGrupo, expandidoAtual && gruposAbertos[idGrupo]);
        });
    });

    configurarSair(barra);
}
