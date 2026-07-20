
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

if (bootstrap.Modal?.jQueryInterface) {
    window.$.fn.modal = bootstrap.Modal.jQueryInterface;
    window.$.fn.modal.Constructor = bootstrap.Modal;
}

import bootbox from 'bootbox';
window.bootbox = bootbox;

import './bootstrap';
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

import { initAutenticacao } from './Autenticacao/autenticacao.js';
import { initBarraLateral } from './Painel/barra-lateral.js';

document.addEventListener('DOMContentLoaded', () => {
    initAutenticacao();
    initBarraLateral();
});