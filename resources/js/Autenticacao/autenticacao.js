import { notificacaoBootbox, notificacaoBootboxDialog } from '../Funcoes/funcoes.js';

const CLASSES_INVALIDAS = ['border-red-500', 'ring-red-500'];
const CLASSES_VALIDAS = ['border-gray-200'];
const SENHA_MINIMA = 8;

function obterTokenCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content;
}

function obterMensagemEmailJaCadastrado() {
    return window.authMessages?.emailJaCadastrado
        ?? 'Já existe uma conta cadastrada com este e-mail.';
}

function emailJaCadastrado(erros) {
    const mensagem = erros.email?.[0] ?? '';

    return mensagem.includes('Já existe uma conta')
        || mensagem.includes('unique')
        || mensagem === obterMensagemEmailJaCadastrado();
}

function alternarCarregamento(formulario, carregando) {
    const botao = formulario.querySelector('[type="submit"], .btn-auth-submit');
    if (!botao) return;

    botao.disabled = carregando;
    botao.dataset.textoOriginal ??= botao.textContent;
    botao.textContent = carregando ? 'Aguarde...' : botao.dataset.textoOriginal;
}

function marcarCampoInvalido(campo, invalido) {
    if (!campo) return;

    campo.classList.remove(...CLASSES_VALIDAS, ...CLASSES_INVALIDAS);
    campo.classList.add(...(invalido ? CLASSES_INVALIDAS : CLASSES_VALIDAS));
}

function campoEstaVazio(campo) {
    if (campo.type === 'checkbox') {
        return !campo.checked;
    }

    return !campo.value.trim();
}

function validarRegrasSenha(campo) {
    const senha = document.getElementById('password');
    const confirmacao = document.getElementById('password_confirmation');

    if (campo.name === 'password') {
        return campo.value.length >= SENHA_MINIMA;
    }

    if (campo.name === 'password_confirmation') {
        return campo.value.length >= SENHA_MINIMA
            && campo.value === senha?.value;
    }

    return true;
}

function validarCampo(campo) {
    const obrigatorio = campo.hasAttribute('data-required');
    let invalido = obrigatorio && campoEstaVazio(campo);

    if (!invalido && (campo.name === 'password' || campo.name === 'password_confirmation')) {
        invalido = !validarRegrasSenha(campo);
    }

    marcarCampoInvalido(campo, invalido);
    return !invalido;
}

function validarFormulario(formulario) {
    let valido = true;

    formulario.querySelectorAll('[data-required]').forEach((campo) => {
        if (!validarCampo(campo)) {
            valido = false;
        }
    });

    return valido;
}

function iniciarValidacaoBlur(formulario) {
    formulario.querySelectorAll('[data-required]').forEach((campo) => {
        campo.addEventListener('blur', () => validarCampo(campo));
        campo.addEventListener('input', () => {
            if (campo.classList.contains('border-red-500')) {
                validarCampo(campo);
            }
        });
    });
}

function tratarErrosValidacao(formulario, erros = {}) {
    formulario.querySelectorAll('[data-required], input, select, textarea').forEach((campo) => {
        if (campo.name && !erros[campo.name]) {
            marcarCampoInvalido(campo, false);
        }
    });

    Object.keys(erros).forEach((nomeCampo) => {
        marcarCampoInvalido(formulario.querySelector(`[name="${nomeCampo}"]`), true);
    });
}

function configurarFormularioAuthAjax(seletorFormulario, opcoes = {}) {
    const formulario = document.querySelector(seletorFormulario);
    if (!formulario) return;

    formulario.setAttribute('novalidate', 'novalidate');
    iniciarValidacaoBlur(formulario);

    formulario.addEventListener('submit', function (evento) {
        evento.preventDefault();

        if (!validarFormulario(formulario)) {
            notificacaoBootbox('Atenção', 'Preencha os campos obrigatórios.', 'warn');
            return;
        }

        alternarCarregamento(formulario, true);

        window.$.ajax({
            url: formulario.action,
            method: formulario.method || 'POST',
            data: new FormData(formulario),
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': obterTokenCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            success: function (dados) {
                alternarCarregamento(formulario, false);

                if (opcoes.aoSucesso) {
                    opcoes.aoSucesso(dados);
                    return;
                }

                window.location.href = dados.redirect || window.authRoutes?.home || '/home';
            },
            error: function (xhr) {
                alternarCarregamento(formulario, false);

                const resposta = xhr.responseJSON || {};
                const erros = resposta.errors || {};

                if (emailJaCadastrado(erros)) {
                    const mensagem = obterMensagemEmailJaCadastrado();

                    notificacaoBootboxDialog(
                        'E-mail já cadastrado',
                        mensagem,
                        'warn',
                        {
                            recuperar: {
                                label: 'Recuperar senha',
                                className: 'btn-primary',
                                callback: function () {
                                    window.location.href = window.authRoutes.recover;
                                },
                            },
                            login: {
                                label: 'Fazer login',
                                className: 'btn-success',
                                callback: function () {
                                    window.location.href = window.authRoutes.login;
                                },
                            },
                            cancelar: {
                                label: 'Fechar',
                                className: 'btn-secondary',
                            },
                        }
                    );
                    return;
                }

                tratarErrosValidacao(formulario, erros);

                const mensagem =
                    resposta.message ||
                    Object.values(erros).flat()[0] ||
                    'Não foi possível concluir a operação.';

                notificacaoBootbox('Erro', mensagem, 'error');
            },
        });
    });
}

export function initAutenticacao() {
    configurarFormularioAuthAjax('#form-login', {
        aoSucesso: (dados) => {
                    window.location.href = dados.redirect || window.authRoutes?.home || '/home';
        },
    });

    configurarFormularioAuthAjax('#form-register', {
        aoSucesso: (dados) => {
            notificacaoBootbox(
                'Conta criada',
                dados.message || 'Cadastro realizado com sucesso!',
                'success',
                () => {
                    window.location.href = dados.redirect || window.authRoutes?.home || '/home';
                }
            );
        },
    });

    configurarFormularioAuthAjax('#form-recuperar-senha', {
        aoSucesso: (dados) => {
            notificacaoBootbox(
                'Link enviado',
                dados.message || window.authMessages?.linkRecuperacaoEnviado || 'Enviamos o link de redefinição para seu e-mail.',
                'success',
                () => {
                    window.location.href = window.authRoutes?.login || '/login';
                }
            );
        },
    });
}
