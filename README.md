# TalentFlow

TalentFlow es un **ATS (Applicant Tracking System)** para centralizar posiciones, candidatos, postulaciones, entrevistas y ofertas. Está construido como una **API REST en Laravel 12** consumida por una **SPA en Vue 3 + TypeScript**, con autenticación Sanctum, aislamiento por empresa y un motor de matching explicable.

---

## Características principales

- **Autenticación** con tokens personales de Laravel Sanctum (`Bearer`).
- **Aislamiento multiempresa**: los usuarios asociados a una empresa solo ven sus datos; los usuarios globales (`company_id = null`) ven todo.
- **Gestión de posiciones, candidatos, empresas, habilidades y postulaciones**, con filtros y paginación real.
- **Pipeline de selección** tipo kanban con **drag & drop nativo**, transiciones válidas hacia adelante y hacia atrás, y auditoría de cada cambio de etapa.
- **Ranking de candidatos compatibles** con score ponderado, factores explicables, candidatos ya postulados marcados como «En proceso» y enlace directo al pipeline.
- **Entrevistas** programables desde la agenda y desde el pipeline, con feedback por evaluador.
- **Ofertas** con ciclo de vida `draft → sent → accepted/declined` y efectos automáticos sobre la postulación.
- **Timeline unificado** por postulación: cambios de etapa, entrevistas, feedbacks, ofertas y notas.
- **Dashboard** con KPIs, distribución por etapas, próximas entrevistas y posiciones recientes.
- **UX consistente**: estados de carga/vacío/error, reintentos, paginación y notificaciones tipo toast.
- **Calidad automatizada**: Pint, PHPUnit, `vue-tsc` y build de Vite, tanto en local como en GitHub Actions.

---

## Stack tecnológico

| Capa | Tecnología | Versión |
| --- | --- | --- |
| Backend | PHP | `^8.2` |
| Backend | Laravel | `^12.0` |
| Autenticación | Laravel Sanctum | `^4.0` |
| Frontend | Vue 3 + Vue Router | `^3.5` / `^4.6` |
| Frontend | TypeScript + vue-tsc | `^5.9` / `^3.3` |
| Frontend | Vite + plugin Vue + plugin Laravel | `^7.0` / `^6.0` / `^2.0` |
| Estilos | Tailwind CSS + PostCSS + Autoprefixer | `^3.4` |
| Cliente HTTP | Axios | `^1.20` |
| Documentación | Redoc standalone (servido por Vite) | `^2.5` |
| Base de datos | SQLite por defecto (PostgreSQL/MySQL soportados por configuración) | — |
| Tests | PHPUnit | `^11.5` |
| Estilo PHP | Laravel Pint | `^1.24` |

---

## Arquitectura

```text
Navegador (SPA Vue 3)
        │  Authorization: Bearer <token>
        ▼
/api/v1
        │
        ├─ middleware api.version  → header X-API-Version: 1
        ├─ throttle:api            → 60 req/min por usuario o IP
        └─ auth:sanctum            → excepto POST /auth/login (throttle:login, 5 req/min por IP)
        ▼
FormRequest (validación)
        ▼
Controller Api\V1
        ▼
Service (ApplicationService / MatchingService)
        ▼
Eloquent + Enums + TenantScope
        ▼
Base de datos
```

Puntos de diseño relevantes:

- Las validaciones viven en `app/Http/Requests/Api/V1` (FormRequests); no están duplicadas en controladores.
- Las respuestas se normalizan con `app/Http/Resources/Api/V1` (`JsonResource::withoutWrapping()` está activo).
- `app/Support/TenantScope.php` aplica el filtro de empresa y resuelve `company_id` a partir del usuario autenticado, nunca del payload.
- `app/Enums/UserRole.php` suma autorización por rol sobre el aislamiento de empresa; los Gates se registran en `app/Providers/AppServiceProvider.php`.
- `app/Enums` define etapas, estados de postulación y estados de oferta; las transiciones inválidas se rechazan con `InvalidTransitionException` (422).
- El frontend usa `resources/js/api/client.ts`: guarda el token en `localStorage`, lo envía en cada petición y redirige a `/login` cuando recibe un 401.
- La SPA se monta en `resources/views/welcome.blade.php`; `routes/web.php` actúa como catch-all para las rutas del router de Vue.

### Matriz de roles

`TenantScope` sigue decidiendo **qué filas** puede ver cada usuario; los Gates deciden **qué acciones** puede ejecutar. El rol se persiste en `users.role` y se expone en `/auth/me` y `/auth/login`.

| Acción | `global_admin` | `company_admin` | `member` |
| --- | --- | --- | --- |
| Lectura de dashboard, companies, positions, candidates, applications, interviews, offers, skills y matching | Sí | Sí, en su empresa | Sí, en su empresa |
| Crear empresas | Sí | No | No |
| Crear/editar posiciones, candidatos, aplicaciones, entrevistas y ofertas | Sí | Sí, en su empresa | No |
| Cambiar etapa de aplicación y estado de oferta | Sí | Sí, en su empresa | No |
| Crear skills en el catálogo global | Sí | No | No |
| Enviar feedback de entrevista | Sí | Sí, en su empresa | Solo si es el entrevistador asignado |

Los FormRequests usan `can(<ability>)` y el feedback usa `Gate::authorize('submit-interview-feedback', $interview)`; las respuestas 403 quedan documentadas en `docs/openapi.json`.

---

## Estructura del proyecto

```text
talent-flow/
├── app/
│   ├── Enums/                       # ApplicationStage, ApplicationStatus, OfferStatus, UserRole
│   ├── Exceptions/                  # InvalidTransitionException
│   ├── Http/
│   │   ├── Controllers/Api/V1/      # Auth, Dashboard, Companies, Positions, Candidates,
│   │   │                            # Applications, Skills, Interviews, Offers, Matching...
│   │   ├── Middleware/              # AddApiVersionHeader
│   │   ├── Requests/Api/V1/         # FormRequests de validación
│   │   └── Resources/Api/V1/        # Resources de respuesta
│   ├── Console/Commands/            # docs:openapi (generador OpenAPI)
│   ├── Models/
│   ├── Services/                    # ApplicationService, MatchingService
│   ├── Support/                     # DomainValues, TenantScope
│   └── Providers/AppServiceProvider.php
├── bootstrap/app.php                # throttle api, versión de API, respuestas JSON
├── config/matching.php              # pesos y reglas del scoring
├── config/openapi.php               # catálogo de schemas y contratos por operación
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/                     # datos de demostración (ver advertencia)
├── docs/openapi.json                # especificación generada de la API v1
├── resources/
│   ├── css/app.css
│   ├── js/
│   │   ├── api/                     # client, auth, token, pagination, notifications
│   │   ├── api-docs.ts              # arranque de Redoc
│   │   ├── components/              # StatePanel, PaginationControls, ToastHost, NotificationBell, InterviewFormModal
│   │   ├── composables/             # usePaginatedList, useNotifications, usePersistentNotifications
│   │   ├── router/index.ts
│   │   ├── types/ats.ts
│   │   ├── types/notifications.ts
│   │   ├── types/redoc-standalone.d.ts
│   │   └── views/                   # Dashboard, Positions, Pipeline, Candidates, Companies,
│   │                                # Interviews, Offers, Timeline, Login
│   └── views/
│       ├── api-docs.blade.php       # página de documentación interactiva
│       └── welcome.blade.php        # montaje de la SPA
├── routes/
│   ├── api.php                      # API v1
│   └── web.php                      # /api-docs, /api-docs.json y catch-all SPA
├── tests/
│   ├── Concerns/CreatesAtsData.php
│   ├── Feature/Api/                 # contrato, auth, tenant, lifecycle, matching, calidad, notificaciones
│   ├── Feature/Console/             # generación de OpenAPI
│   └── Feature/ApiDocumentationTest.php
├── .github/workflows/quality.yml
├── composer.json
├── package.json
├── pint.json
├── tsconfig.json
├── vite.config.js
├── tailwind.config.js
└── README.md
```

---

## Modelo de datos

| Entidad | Función |
| --- | --- |
| `Company` | Empresa propietaria de posiciones y usuarios. |
| `User` | Usuario autenticable; `company_id` nullable define si es global. |
| `Position` | Vacante con estado, seniority, salario, ubicación/modalidad y requisitos. |
| `Candidate` | Candidato con experiencia, expectativa salarial, ubicación y habilidades. |
| `Application` | Postulación de un candidato a una posición; guarda etapa y estado. |
| `Skill` / `candidate_skills` / `position_requirements` | Habilidades con nivel y años de experiencia. |
| `Interview` / `InterviewFeedback` | Entrevistas programadas y evaluaciones por entrevistador. |
| `ApplicationStageLog` | Auditoría de cada transición de etapa. |
| `Offer` | Oferta económica asociada a una postulación. |
| `personal_access_tokens` | Tokens Sanctum emitidos por el login. |

---

## Flujo de selección

Etapas (`ApplicationStage`): `new`, `screening`, `technical_interview`, `final_interview`, `offer`, `hired`, `rejected`.

Transiciones permitidas (incluye retrocesos controlados):

```text
new                 → screening | rejected
screening           → new | technical_interview | rejected
technical_interview → screening | final_interview | rejected
final_interview     → technical_interview | offer | rejected
offer               → final_interview | hired | rejected
hired               → offer
rejected            → new
```

Cada cambio pasa por `ApplicationService::transitionStage()`, que valida la transición, bloquea la fila, escribe un `ApplicationStageLog` y sincroniza `status` (`active`, `hired` o `rejected`). Los eventos relevantes (postulación, cambio de etapa, entrevistas, feedback y ofertas) generan notificaciones persistentes en base de datos y se envían por mail en modo `log`. El frontend replica el mismo mapa en `PipelineKanbanView.vue` para impedir soltar tarjetas en columnas inválidas.

El ranking de una posición (`PositionDetailView.vue`) muestra candidatos del bucket de talento y también los ya postulados, con `application_id` y `application_stage`. El badge «En proceso» navega a `/pipeline?position=<id>&application=<id>`; el pipeline preselecciona la vacante, resalta la tarjeta y permite limpiar la selección.

Ofertas (`OfferStatus`): `draft → sent → accepted | declined`. Aceptar una oferta contrata la postulación; rechazarla la marca como `rejected` y exige motivo. El estado final es terminal.

---

## Matching explicable

`config/matching.php` define los pesos del score:

| Factor | Peso | Criterio resumido |
| --- | ---: | --- |
| Requisitos obligatorios | 45 | Cobertura de skills obligatorias y nivel declarado. |
| Requisitos deseables | 20 | Cobertura de skills opcionales. |
| Seniority | 15 | Años de experiencia frente a la banda `jr/ssr/sr/lead` de la posición. |
| Expectativa salarial | 10 | Comparación con el rango de la vacante (tolerancia del 20 %). |
| Ubicación / modalidad | 10 | Remoto, coincidencia de ubicación, dato vacío o divergente. |

`MatchingService` devuelve `overall_score`, `meets_all_mandatory`, detalle por habilidad y un array `factors` con `label`, `score`, `weight` y `points`, de modo que la UI puede explicar el resultado. Las recomendaciones priorizan candidatos sin postularse y, dentro de cada grupo, ordenan por score; también incluyen a los ya postulados con `already_applied: true`.

---

## API REST v1

Base URL: `http://127.0.0.1:8000/api/v1`.

- Todas las rutas responden JSON y añaden el header `X-API-Version: 1`.
- Salvo `POST /auth/login`, requieren `Authorization: Bearer <token>`.
- Throttle: `api` 60 req/min por usuario/IP; `login` 5 req/min por IP.
- Errores de validación: `422` con campo `errors`; transiciones inválidas: `422` con `message`.

Contratos de respuesta:

```json
// Listados paginados
{
  "data": {
    "data": [],
    "links": { "first": "...", "last": "...", "prev": null, "next": null },
    "meta": { "current_page": 1, "per_page": 15, "total": 0 }
  }
}
```

```json
// Login y /auth/me
{
  "data": {
    "user": { "id": 1, "name": "..." },
    "token": "tf_...",
    "token_type": "Bearer"
  }
}
```

```json
// Dashboard (JsonResource sin wrapper)
{
  "kpis": { "open_positions": 0, "total_candidates": 0, "active_applications": 0, "upcoming_interviews": 0 },
  "stage_distribution": {},
  "upcoming_interviews": [],
  "recent_positions": []
}
```

Endpoints disponibles:

| Grupo | Método y ruta |
| --- | --- |
| Auth | `POST /auth/login`, `POST /auth/logout`, `GET /auth/me` |
| Dashboard | `GET /dashboard` |
| Empresas | `GET|POST /companies`, `GET /companies/{company}` |
| Posiciones | `GET|POST /positions`, `GET /positions/{position}` |
| Candidatos | `GET|POST /candidates`, `GET /candidates/{candidate}` |
| Postulaciones | `GET|POST /applications`, `GET /applications/{application}` |
| Habilidades | `GET|POST /skills` |
| Entrevistas | `GET|POST /interviews`, `GET|PATCH /interviews/{interview}`, `POST /interviews/{interview}/feedback` |
| Ofertas | `GET|POST /offers`, `GET /offers/{offer}`, `PATCH /offers/{offer}/status` |
| Pipeline | `PATCH /applications/{application}/stage`, `GET /applications/{application}/timeline` |
| Sincronización | `POST /positions/{position}/requirements`, `POST /candidates/{candidate}/skills` |
| Matching | `GET /positions/{position}/recommendations`, `GET /positions/{position}/candidates/{candidate}/match` |

Ejemplo de petición:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"...","password":"..."}'
```

---

## Documentación OpenAPI

La especificación de la API v1 se genera automáticamente a partir de las rutas registradas:

```bash
composer docs:generate   # equivalente a php artisan docs:openapi
```

El comando escribe `docs/openapi.json` e incluye paths, operaciones, parámetros de ruta y query, esquemas concretos de request/response, esquema `bearerAuth`, header `X-API-Version` y respuestas 200/201/401/403/404/422/429. El catálogo de esquemas y el mapeo operación → esquema viven en `config/openapi.php`. Para generarlo en otra ubicación:

```bash
php artisan docs:openapi --output=ruta/archivo.json
```

Los esquemas cubren FormRequests, Resources y envelopes reales de la API: `data` para recursos individuales, `Paginated*Schema` para listados, `MessageAnd*ResponseSchema` para respuestas con mensaje, timeline con discriminador por `type`, comparación de match, recomendaciones y dashboard.

La documentación interactiva está disponible en **`/api-docs`**: una vista Blade carga Redoc de forma local (sin CDN) desde el bundle `redoc.standalone.js` copiado por Vite y consume la especificación servida en **`/api-docs.json`**.

---

## Rutas de la SPA

`/login`, `/`, `/positions`, `/positions/create`, `/positions/:id`, `/pipeline`, `/candidates`, `/candidates/:id`, `/companies`, `/interviews`, `/offers`, `/applications/:id/timeline`.

La documentación interactiva `/api-docs` se sirve fuera del router de Vue; su especificación se expone en `/api-docs.json`.

---

## Configuración y ejecución local

### Requisitos

- PHP 8.2+ con extensiones `mbstring`, `dom`, `fileinfo`, `pdo_sqlite` (o `pdo_pgsql` si usas PostgreSQL).
- Composer.
- Node.js 20+ y npm (CI usa Node 20).
- SQLite (por defecto) o PostgreSQL/MySQL si se configura explícitamente.

### Opción rápida

```bash
composer setup
composer dev
```

`composer setup` instala dependencias PHP, crea `.env` si no existe, genera la clave, migra, instala dependencias JS y compila los assets. `composer dev` levanta `php artisan serve` y Vite en paralelo. **No ejecuta seeders**: hazlo manualmente si quieres datos de demostración.

### Paso a paso

```bash
composer install
cp .env.example .env          # Windows: Copy-Item .env.example .env
php artisan key:generate
```

Si usas SQLite y el archivo no existe:

```bash
touch database/database.sqlite   # Windows: New-Item database\database.sqlite
```

Si prefieres PostgreSQL, ajusta `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=talent_flow
DB_USERNAME=postgres
DB_PASSWORD=
```

Continúa con:

```bash
php artisan migrate
php artisan db:seed             # opcional, solo en entorno local
npm install
npm run dev                     # Vite (en otra terminal: php artisan serve)
```

Alternativa única para servidor + Vite: `npm run dev:all`.

Para producción: `npm run build` y sirve el proyecto con `php artisan serve` o tu servidor web habitual. La SPA estará en `http://127.0.0.1:8000`.

> **Advertencia**: `database/seeders/DatabaseSeeder.php` contiene usuarios y datos de demostración con contraseñas fijas. Úsalo solo en local, cambia esas credenciales antes de exponer el entorno y no lo incluyas en commits.

### Variables relevantes

```env
APP_NAME=TalentFlow
APP_URL=http://localhost
DB_CONNECTION=sqlite
SANCTUM_TOKEN_EXPIRATION=1440
SANCTUM_TOKEN_PREFIX=tf_
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=log
```

---

## Calidad y tests

```bash
composer lint       # Pint --test
composer test       # php artisan test (config:clear + PHPUnit)
npm run typecheck   # vue-tsc --noEmit
npm run build       # Vite build
composer quality    # los cuatro anteriores en secuencia
```

La cobertura incluye contrato de API y envelope, autenticación y logout, aislamiento por empresa, ciclo de vida de postulaciones (incluidos retrocesos y timeline), matching ponderado y flags de postulación, dashboard, sincronización de skills/requisitos, entrevistas y feedback, ofertas, generación de la especificación OpenAPI y las rutas que sirven la documentación interactiva.

El workflow `.github/workflows/quality.yml` ejecuta los mismos chequeos en `push` a `main` y en pull requests, con PHP 8.2 y Node 20.

### Problemas comunes

- **Pantalla en blanco o assets 404**: ejecuta `npm install && npm run build`, o deja `npm run dev` activo durante el desarrollo.
- **401 en la API**: el token expira a los 1440 minutos; vuelve a iniciar sesión. La SPA limpia el token y redirige a `/login`.
- **Sin datos al iniciar**: comprueba `DB_CONNECTION` en `.env` y ejecuta `php artisan migrate` (y `db:seed` solo si es local).
- **Error de clave**: `php artisan key:generate`.

---

## Estado actual`r`n`r`nYa implementado y documentado arriba: autenticación Sanctum, autorización por roles con Gates, dashboard con KPIs y distribución por etapa/estado, filtros avanzados en listados, reportes básicos por rango de fechas, workflow de etapas y ofertas, matching con filtros, notificaciones persistentes (base de datos + mail log) con inbox en la UI, pipeline interactivo, especificación OpenAPI con schemas concretos y documentación interactiva en `/api-docs`.`r`n`r`n---`r`n`r`n## Autor

**Jonathan Mendola** — Backend Developer especializado en PHP y Laravel.

- GitHub: https://github.com/JonathanEme
- Portfolio: https://jonathaneme.github.io/
