# Deploy na Hostinger VPS (Docker + GitHub Actions)

Stack de produção: **Docker Compose** (`app` Apache/PHP 8.2 + `db` MySQL 8) e deploy automático quando a branch `main` recebe merge/push.

## Pré-requisitos na VPS

1. VPS Hostinger com acesso SSH (usuário com Docker).
2. Instalar Docker Engine + Docker Compose plugin.
3. Domínio apontando para o IP da VPS (A record).
4. (Recomendado) Proxy reverso com HTTPS (Nginx/Caddy/Traefik) na porta 80/443 apontando para `127.0.0.1:8000`.

## Setup inicial (uma vez)

### 1. Clonar o repositório

```bash
sudo mkdir -p /var/www
sudo chown "$USER":"$USER" /var/www
cd /var/www
git clone git@github.com:Aline-Pereira98/nutri_grana.git
cd nutri_grana
git checkout main
```

### 2. Criar `.env` de produção

```bash
cp .env.production.example .env
# Gere a APP_KEY localmente ou na VPS (sem commitá-la):
docker run --rm -v "$PWD":/app -w /app php:8.2-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)), PHP_EOL;"
```

Edite o `.env` e preencha:

| Variável | Produção |
|----------|----------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://seu-dominio.com` |
| `APP_KEY` | chave gerada (`base64:...`) |
| `DB_*` | usuário/senha fortes; `DB_HOST=db` (rede Docker) |
| `SESSION_SECURE_COOKIE` | `true` (HTTPS) |
| `MAIL_MAILER` | `log` (e-mail desabilitado por enquanto) |
| `LOG_LEVEL` | `error` |

Não versionar o `.env`. Ele fica só na VPS.

### 3. Primeiro deploy manual

```bash
bash scripts/deploy.sh
```

Isso faz build da imagem, sobe `app` + `db`, roda `migrate --force`, `storage:link` e caches.

Confirme:

```bash
COMPOSE_FILE=docker-compose.yml:docker-compose.prod.yml docker compose ps
curl -I http://127.0.0.1:8000
```

### 4. HTTPS / domínio

Aponte o proxy reverso para a porta publicada do app (`8000:80` no Compose). Exemplo mínimo Nginx:

```nginx
server {
    listen 80;
    server_name seu-dominio.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name seu-dominio.com;

    # ssl_certificate / ssl_certificate_key ...

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

## Deploy automático (GitHub → VPS)

Fluxo do projeto: `feature/*` → PR `release` → PR `main`. Ao mergear/aprovar na `main`, o workflow [`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml) conecta via SSH e executa `scripts/deploy.sh`.

### Secrets no GitHub

Em **Settings → Secrets and variables → Actions** (e environment `production`):

| Secret | Exemplo |
|--------|---------|
| `VPS_HOST` | IP ou hostname da VPS |
| `VPS_USER` | usuário SSH (ex.: `root` ou `deploy`) |
| `VPS_SSH_KEY` | chave privada SSH (PEM completo) |
| `VPS_PORT` | `22` |
| `DEPLOY_PATH` | `/var/www/nutri_grana` |

Crie também o environment **production** em **Settings → Environments**.

### Chave SSH

Na sua máquina:

```bash
ssh-keygen -t ed25519 -C "github-deploy-nutrigrana" -f ./deploy_nutrigrana -N ""
```

- Conteúdo de `deploy_nutrigrana` → secret `VPS_SSH_KEY`
- Conteúdo de `deploy_nutrigrana.pub` → `~/.ssh/authorized_keys` do usuário na VPS

Restrinja a chave se possível (`command=` / `from=`).

### Testar

1. Merge de um PR na `main` **ou** Actions → **Deploy to Hostinger VPS** → Run workflow.
2. Acompanhe o job; na VPS: `COMPOSE_FILE=docker-compose.yml:docker-compose.prod.yml docker compose logs -f app`.

## O que o `scripts/deploy.sh` faz

1. `git pull --ff-only` na branch atual
2. `docker compose build app` (usa só `docker-compose.yml`, sem override local)
3. `docker compose up -d`
4. `php artisan migrate --force`
5. `php artisan storage:link`
6. `config:cache`, `route:cache`, `view:cache`

Volumes persistentes: MySQL (`dbdata` em `docker-compose.yml`) e uploads públicos (`app_public_storage` em `docker-compose.prod.yml`). O deploy usa `COMPOSE_FILE=docker-compose.yml:docker-compose.prod.yml` para não carregar o override local de desenvolvimento.

## E-mail (temporariamente desligado)

- `MAIL_MAILER=log`
- Rotas de “esqueci a senha” e código por e-mail comentadas
- Perfil altera senha com **senha atual + nova senha** (`PUT /password`)

Quando contratar SMTP: configurar `MAIL_*`, descomentar rotas em `routes/auth.php` / `routes/web.php`, restaurar UI e o `Mail::send` em `UsuarioService`.

## Checklist rápido

- [ ] Docker instalado na VPS
- [ ] Repo clonado em `DEPLOY_PATH`
- [ ] `.env` criado a partir de `.env.production.example` com `APP_KEY` e senhas fortes
- [ ] `bash scripts/deploy.sh` rodou com sucesso uma vez
- [ ] Domínio + HTTPS apontando para `:8000`
- [ ] Secrets do GitHub preenchidos
- [ ] Merge na `main` dispara o deploy
- [ ] Login e troca de senha no perfil testados
