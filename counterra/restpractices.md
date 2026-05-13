# REST Practices (Counterra)

## Summary: before vs after

### Before
- Most endpoints wrapped responses like:
  ```json
  {"status":"success","data":{...},"message":"..."}
  ```
- `POST` and `PUT` generally returned `200` with a success message instead of returning the created/updated resource.
- `DELETE` returned a JSON success message instead of `204 No Content`.
- Composite endpoint existed as `GET /cities/{id}/setup` returning a custom shape.

### After (what changed)
- **Success responses return the resource directly** (or an array of resources) with standard HTTP status codes.
- `POST` returns **`201 Created`** + **`Location`** header + created resource body.
- `PUT` returns the **updated resource** (HTTP `200`).
- `DELETE` returns **`204 No Content`** (empty body).
- Added resource endpoints:
  - `GET /cities/{id}`
  - `GET /positions/{id}`
- City “full setup” is now REST-shaped as an **included relationship**:
  - `GET /cities/{id}?include=positions,candidates`

## Current API contract (after)

Base path: `/acm/counterra/api`

### Auth
- `POST /login`
  - **200** →
    ```json
    {"token":"...","user":{"id":1,"username":"admin"}}
    ```
  - **400/401** →
    ```json
    {"message":"..."}
    ```

### Cities
- `GET /cities` → **200**
  ```json
  [{"id":1,"name":"X","councilor_limit":5}]
  ```

- `GET /cities/{id}` → **200**
  ```json
  {"id":1,"name":"X","councilor_limit":5}
  ```

- `GET /cities/{id}?include=positions,candidates` → **200**
  ```json
  {
    "id": 1,
    "name": "X",
    "councilor_limit": 5,
    "positions": [
      {"id": 10, "city_id": 1, "title": "Mayor", "max_votes": 1, "candidates": [{"id": 99, "name": "..."}]}
    ]
  }
  ```

- `POST /cities` → **201** + `Location: /acm/counterra/api/cities/{id}`
  ```json
  {"id":1,"name":"X","councilor_limit":5}
  ```

- `PUT /cities/{id}` → **200**
  ```json
  {"id":1,"name":"X","councilor_limit":6}
  ```

- `DELETE /cities/{id}` → **204** (no body)

### Positions
- `GET /positions` → **200**
  ```json
  [{"id":1,"city_id":1,"title":"Mayor","max_votes":1,"city_name":"X"}]
  ```

- `GET /positions/{id}` → **200**
  ```json
  {"id":1,"city_id":1,"title":"Mayor","max_votes":1,"city_name":"X"}
  ```

- `POST /positions` → **201** + `Location: /acm/counterra/api/positions/{id}`
  ```json
  {"id":1,"city_id":1,"title":"Mayor","max_votes":1,"city_name":"X"}
  ```

- `PUT /positions/{id}` → **200**
  ```json
  {"id":1,"city_id":1,"title":"Mayor","max_votes":2,"city_name":"X"}
  ```

- `DELETE /positions/{id}` → **204** (no body)

## Error format (after)

- Validation errors return **`422 Unprocessable Entity`**:
  ```json
  {
    "message": "Validation failed",
    "errors": {
      "name": "Name is required"
    }
  }
  ```

- Not found returns **`404 Not Found`**:
  ```json
  {"message":"City not found"}
  ```

- Auth failure returns **`401 Unauthorized`**:
  ```json
  {"message":"Invalid credentials"}
  ```

## REST rules to follow (especially when building new endpoints)

### 1) Use nouns for resources (not verbs)
- Good: `GET /cities`, `POST /positions`
- Avoid: `POST /createCity`, `GET /getPositions`

### 2) Use HTTP methods for actions
- `GET` = read (no side effects)
- `POST` = create
- `PUT` = replace/update (idempotent)
- `PATCH` = partial update
- `DELETE` = delete

### 3) Use correct status codes
- `200 OK` → successful read/update that returns a body
- `201 Created` → successful create (**include `Location` header**)
- `204 No Content` → successful delete (or update with no body)
- `400 Bad Request` → malformed request (e.g., invalid JSON)
- `401 Unauthorized` → not logged in / bad credentials
- `403 Forbidden` → logged in but not allowed
- `404 Not Found` → resource doesn’t exist
- `422 Unprocessable Entity` → validation failed (missing/invalid fields)

### 4) Return the resource (don’t wrap with `status/data`)
- Let the **HTTP status code** indicate success/failure.
- Return a consistent error object (e.g., `{ "message": "..." }`).

### 5) Relationships: prefer `include` for nested data
Especially when you need “one endpoint that returns everything”:
- Prefer: `GET /cities/{id}?include=positions,candidates`
- Avoid custom action paths like `/cities/{id}/setup` unless you’re intentionally doing RPC-style endpoints.

### 6) Keep request/response schemas stable
- Frontends should not depend on extra wrapper fields.
- Add fields in a backward-compatible way when possible.

### 7) Auth: keep it predictable
- Login is typically a **token endpoint** (not a normal CRUD resource).
- After login, protect endpoints using `Authorization: Bearer <token>` when you add real auth.

---

Implementation notes:
- Backend changes are in the controllers under `counterra/api/src/Controllers` and routes in `counterra/api/public/index.php`.
- Frontend parsing was updated in `counterra/web/src/stores/*` to match the new REST responses.
