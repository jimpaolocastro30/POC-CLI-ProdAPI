#!/usr/bin/env bash
# Start local development environment (no GCP required).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [[ "${1:-}" == "--down" ]]; then
  docker compose down
  echo "Development environment stopped."
  exit 0
fi

if [[ ! -f .env ]]; then
  cp .env.example .env
  echo "Created .env from .env.example"
fi

if [[ "${1:-}" == "--rebuild" ]]; then
  docker compose build --no-cache app
fi

echo "Starting development environment..."
docker compose up -d

echo "Waiting for http://localhost:8080/up ..."
for i in $(seq 1 60); do
  if curl -sf http://localhost:8080/up >/dev/null 2>&1; then
    echo ""
    echo "Development API is running!"
    echo "  Health:  http://localhost:8080/up"
    echo "  API:     http://localhost:8080/api"
    echo "  Login:   admin@inventory.local / password"
    exit 0
  fi
  sleep 3
  printf "."
done

echo ""
echo "API not ready yet. Check: docker compose logs app"
