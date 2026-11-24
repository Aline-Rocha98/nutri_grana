#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

export COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.yml:docker-compose.prod.yml}"

echo "==> Deploy NutriGrana em $(pwd)"
echo "==> Usando COMPOSE_FILE=${COMPOSE_FILE}"

if [[ ! -f .env ]]; then
  echo "Arquivo .env não encontrado. Crie a partir de .env.production.example antes do deploy."
  exit 1
fi

if [[ -d .git ]]; then
  BRANCH="$(git rev-parse --abbrev-ref HEAD)"
  echo "==> Atualizando código (branch: ${BRANCH})"
  git fetch --prune origin
  git pull --ff-only origin "${BRANCH}"
fi

echo "==> Construindo imagem da aplicação"
docker compose build app

echo "==> Subindo containers"
docker compose up -d

echo "==> Aguardando app responder"
for _ in $(seq 1 30); do
  if docker compose exec -T app php -v >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

echo "==> Migrations"
docker compose exec -T app php artisan migrate --force

echo "==> Storage link"
docker compose exec -T app php artisan storage:link || true

echo "==> Caches de produção"
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

echo "==> Deploy concluído"
docker compose ps
