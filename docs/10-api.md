# 10 — API Architecture

The first application is a Laravel + Inertia + React monolith. No public API is shipped in this build. The backend is nevertheless structured so an API can be added without rewriting anything.

---

## 1. What makes this possible

Three decisions taken from the start:

1. **All business logic lives in Services and Actions.** A future API controller calls `ApproveMember` exactly as the web controller does. No logic has to be extracted or duplicated.
2. **All serialization goes through API Resources.** `DirectoryMemberResource` already applies privacy rules. The API reuses it, so privacy cannot drift between web and API — the most common failure mode when an API is bolted on later.
3. **All authorization goes through Policies and permission middleware,** which are transport-agnostic. The same policy protects a web route and an API route.

The exception handler is already API-aware:

```php
// bootstrap/app.php — existing
$exceptions->shouldRenderJsonWhen(
    fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
);
```

## 2. Versioning

Versioned from the first endpoint, so a mobile app never has to break.

```
routes/api.php  →  Route::prefix('v1')->group(...)
app/Http/Controllers/Api/V1/
app/Http/Resources/Api/V1/     (only where the shape must differ from web)
```

A `v2` is added as a new namespace beside `v1`, never as a mutation of it. `v1` keeps working until its consumers are retired.

## 3. Planned surface

| Group | Endpoints |
|---|---|
| Auth | `POST /auth/login`, `POST /auth/logout`, `GET /auth/me`, `POST /auth/refresh` |
| Me | `GET/PATCH /me/profile`, `GET /me/card`, `GET /me/events`, `GET /me/payments`, `GET /me/notifications` |
| Directory | `GET /directory` (search + filters), `GET /directory/{ulid}` |
| Members | `GET /members`, `GET /members/{ulid}` — admin scope |
| Batches | `GET /batches`, `GET /batches/{slug}` |
| Events | `GET /events`, `GET /events/{slug}`, `POST /events/{slug}/register` |
| Check-in | `POST /events/{slug}/checkin` — for a future scanner app |
| Community | `GET /posts`, `POST /posts`, `POST /posts/{ulid}/comments`, `POST /posts/{ulid}/react` |
| Content | `GET /news`, `GET /announcements`, `GET /gallery` |
| Public | `GET /verify/member/{ulid}` — unauthenticated, same six fields as the web page |

## 4. Authentication

**Laravel Sanctum** personal access tokens when the API is built. Chosen over Passport because there is no third-party OAuth client scenario — the only consumer is the association's own mobile app — and over raw JWT because Sanctum integrates with the existing guard and policy stack without a parallel auth system.

- Tokens carry abilities mapped from the user's permissions.
- Tokens are revocable per device from the member's security settings.
- `POST /auth/login` respects the existing Fortify throttle and returns a two-factor challenge when the user has 2FA enabled, rather than bypassing it.

Sanctum is **not** installed in this build. It becomes the fifth approved package when an API is actually needed.

## 5. Response shape

```json
{
    "data": { },
    "meta": { "current_page": 1, "per_page": 20, "total": 412 },
    "links": { "next": "…", "prev": null }
}
```

Errors use Laravel's standard shape: `422` with `{"message": "…", "errors": {"field": ["…"]}}`, `401`, `403`, `404`, `429`.

## 6. Rules for whoever builds it

1. **Reuse the web Resource classes.** Write an API-specific Resource only when the shape genuinely must differ. Never re-implement privacy filtering.
2. **Paginate everything.** Default 20, maximum 100. Never return an unbounded collection.
3. **Rate limit per token,** not per IP — a shared mobile network puts many members behind one address.
4. **Never expose integer IDs.** ULIDs only, matching the web routes.
5. **Never trust a client-supplied status, role, membership number or payment state.** They are set by Actions, not by request payloads.
6. **Version the response shape,** not just the URL — adding a field is safe, removing or retyping one is a breaking change requiring `v2`.
7. **Document with the code.** An OpenAPI spec generated from the routes and Resources, not hand-maintained prose.

## 7. Mobile app readiness

Everything a mobile app needs already exists server-side: ULID identifiers, QR tokens, privacy-filtered resources, database notifications (push is an additional channel on the same notification classes), and permission-scoped queries.

The realistic first mobile use case is a **volunteer check-in scanner** for the Golden Jubilee — the camera is on the phone, and the gate has poor desktop access. That needs exactly two endpoints: `POST /auth/login` and `POST /events/{slug}/checkin`. Both are trivial on top of the existing `CheckinService`, which is why check-in logic lives in a service rather than a controller.
