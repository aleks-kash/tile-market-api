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

Manticore exposes HTTP API on port `9308` and MySQL-compatible protocol on `9306` (for SQL-like client access).

## API Endpoints

### 1. Full-text Order Search
Search orders across all indexed fields (`client_name`, `client_surname`, `email`, `name`, `description`) powered by **Manticore Search**.

- **Method**: `GET`
- **Path**: `/api/v1/orders/search`
- **Query Parameters**:
  - `q` *(string, required)*: Search term (e.g. `Alexander`, `Sergei`, or `marca-corona`).
  - `page` *(int, optional, default: `1`)*: Page number.
  - `limit` *(int, optional, default: `20`)*: Items per page (min: 1, max: 100).
- **Example Request**:
  ```bash
  curl "http://localhost:8080/api/v1/orders/search?q=Alexander&page=1&limit=20"
  ```
- **Example Response (`200 OK`)**:
  ```json
  {
    "query": "Alexander",
    "page": 1,
    "limit": 20,
    "hits": [
      {
        "_id": 508,
        "_score": 2670,
        "_source": {
          "name": "Seeded Order #8",
          "client_name": "Alexander",
          "client_surname": "Dubois",
          "email": "alexander.dubois.8@example.com",
          "description": ""
        }
      }
    ],
    "total": 6
  }
  ```

---

### 2. Order Details by Hash
Retrieve full order details, associated articles, and delivery address by unique order hash.

- **Method**: `GET`
- **Path**: `/api/v1/orders/{hash}`
- **Path Parameters**:
  - `hash` *(string, required)*: Unique hash identifier of the order (regex: `[a-zA-Z0-9_-]+`).
- **Example Request**:
  ```bash
  curl "http://localhost:8080/api/v1/orders/test_hash_123"
  ```
- **Example Response (`200 OK`)**:
  ```json
  {
    "name": "Order #1001",
    "email": "sergei@example.com",
    "client_name": "Sergei",
    "client_surname": "Petrov",
    "hash": "test_hash_123",
    "token": "test_token_abc",
    "status": 3,
    "pay_type": 2,
    "locale": "ru",
    "currency": "EUR",
    "measure": "mq",
    "created_at": "2026-08-31T09:00:00+00:00",
    "articles": [
      {
        "id": 1,
        "article_id": 777,
        "amount": 10.0,
        "price": 99.95,
        "currency": "EUR",
        "measure": "mq"
      }
    ],
    "delivery": {
      "address": "Street 1",
      "building": "10",
      "city": "Rome",
      "index": "00100",
      "region": "Lazio",
      "country": "Italy"
    }
  }
  ```

---

### 3. Order Statistics Aggregation
Retrieve aggregated order statistics grouped dynamically by period (`day`, `month`, `year`) with pagination.

- **Method**: `GET`
- **Path**: `/api/v1/orders/stats`
- **Query Parameters**:
  - `group_by` *(string, optional, default: `day`)*: Period grouping (`day`, `month`, `year`).
  - `page` *(int, optional, default: `1`)*: Page number.
  - `limit` *(int, optional, default: `20`)*: Items per page.
- **Example Request**:
  ```bash
  curl "http://localhost:8080/api/v1/orders/stats?group_by=day&page=1&limit=20"
  ```
- **Example Response (`200 OK`)**:
  ```json
  {
    "group_by": "day",
    "data": [
      {
        "period": "2026-08-31",
        "orders_count": 15,
        "total_amount": 1450.50
      }
    ],
    "meta": {
      "page": 1,
      "limit": 20,
      "total": 1,
      "pages": 1
    }
  }
  ```

---

### 4. Tile Price Extraction
Scrape price for a specific tile article from a remote resource using DOM XPath and Regex fallback.

- **Method**: `GET`
- **Path**: `/api/v1/price`
- **Query Parameters**:
  - `factory` *(string, required)*: Factory name (e.g. `marca-corona`).
  - `collection` *(string, required)*: Collection name (e.g. `arteseta`).
  - `article` *(string, required)*: Article identifier.
- **Example Request**:
  ```bash
  curl "http://localhost:8080/api/v1/price?factory=marca-corona&collection=arteseta&article=i123"
  ```
- **Example Response (`200 OK`)**:
  ```json
  {
    "price": 45.90,
    "factory": "marca-corona",
    "collection": "arteseta",
    "article": "i123"
  }
  ```

---

### 5. SOAP Order Service
SOAP web service for order management and operations.

- **Methods**: `GET` / `POST`
- **Path**: `/api/v1/soap/orders`
- **WSDL Specification**:
  - `GET /api/v1/soap/orders?wsdl` — Returns AutoDiscovered WSDL XML document.
- **SOAP Requests**:
  - `POST /api/v1/soap/orders` with `Content-Type: text/xml; charset=UTF-8` payload.

