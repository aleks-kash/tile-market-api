# tile-market-api

Symfony API-only backend skeleton for tile market services.

## Stack
- PHP 8.3+
- Symfony 7.4 (skeleton-style minimal setup)
- PostgreSQL
- Docker & docker-compose (php-fpm, nginx, postgres, manticore)

## Quick start
```bash
make build
make up
```

## API endpoints
- `GET /api/v1/price?factory=&collection=&article=`
- `GET /api/v1/orders/stats?page=1&limit=20&group_by=day`
- `POST /api/v1/soap`
- `GET /api/v1/orders/{id}`
- `GET /api/v1/orders/search?q=...`
