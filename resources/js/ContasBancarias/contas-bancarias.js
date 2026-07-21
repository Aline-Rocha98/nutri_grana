import select2 from 'select2';
import 'select2/dist/css/select2.min.css';

function anexarSelect2AoJquery() {
    if (! window.$ || window.$.fn.select2) {
        return;
    }

    const anexar = select2?.default ?? select2;

    if (typeof anexar === 'function') {
        // Assinatura CommonJS do Select2: function (root, jQuery)
        anexar(window, window.$);
    }
}

anexarSelect2AoJquery();

const SELETOR_TIPO = '#tipo-conta-bancaria';
const NOME_MODAL = 'formulario-conta-bancaria';

function obterSelectTipo() {
    return window.$(SELETOR_TIPO);
}

function destruirSelect2Tipo() {
    const $select = obterSelectTipo();

    if ($select.length && $select.hasClass('select2-hidden-accessible')) {
        $select.off('change.contasBancarias');
        $select.select2('destroy');
    }
}

function inicializarSelect2Tipo(valor = null) {
    anexarSelect2AoJquery();

    const $select = obterSelectTipo();

    if (! $select.length || ! window.$.fn.select2) {
        console.warn('Select2 não disponível para #tipo-conta-bancaria');
        return;
    }

    destruirSelect2Tipo();

    $select.select2({
        width: '100%',
        minimumResultsForSearch: Infinity,
        dropdownParent: window.$(document.body),
        placeholder: 'Selecione o tipo',
    });

    if (valor !== null && valor !== undefined && valor !== '') {
        $select.val(String(valor)).trigger('change.select2');
    }

    $select.on('change.contasBancarias', function () {
        this.dispatchEvent(new CustomEvent('tipo-conta-bancaria-alterado', {
            detail: { valor: window.$(this).val() },
            bubbles: true,
        }));
    });
}

export function initContasBancarias() {
    if (! obterSelectTipo().length) {
        return;
    }

    window.NutriGranaContasBancarias = {
        inicializarSelect2Tipo,
        destruirSelect2Tipo,
    };

    window.addEventListener('open-modal', (evento) => {
        if (evento.detail !== NOME_MODAL) {
            return;
        }

        window.setTimeout(() => {
            inicializarSelect2Tipo(obterSelectTipo().val());
        }, 80);
    });

    window.addEventListener('close-modal', (evento) => {
        if (evento.detail !== NOME_MODAL) {
            return;
        }

        destruirSelect2Tipo();
    });

    window.addEventListener('abrir-select2-conta', (evento) => {
        window.setTimeout(() => {
            inicializarSelect2Tipo(evento.detail?.valor ?? obterSelectTipo().val());
        }, 80);
    });
}
