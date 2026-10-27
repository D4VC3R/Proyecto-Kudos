# Proyecto Kudos

<p align="center">
  <img src="./Frontend/public/logo.svg" alt="Logo Kudos" width="200" />
</p>

Proyecto Kudos es una plataforma de valoraciones multitemática con elementos de gamificación donde los usuarios crean, puntúan y comentan elementos culturales, sociales o populares, generando clasificaciones o ‘ránkings’ divididos por temáticas mientras ganan puntos Kudos con cada interacción, que les hará escalar en la clasificación general de usuarios.

> **Kudos 2.0 en curso.** El proyecto está en pleno rediseño. El plan, las reglas de trabajo y el estado de cada tarea están en [docs/](docs/README.md) (tablero: [docs/roadmap/README.md](docs/roadmap/README.md)).

---

## 🚀 Tecnologías utilizadas

**Backend**: Laravel 12, PHP 8.5, PostgreSQL 18, Redis, Docker (Laravel Sail)  
  <img src="https://skillicons.dev/icons?i=laravel,php,postgres,redis,docker" alt="Backend Stack" />

**Frontend**: React 18, JavaScript, Tailwind CSS 3, Vite 6, pnpm  
  <img src="https://skillicons.dev/icons?i=react,js,tailwind,vite" alt="Frontend Stack" />

**Otros**: Git, GitHub  
  <img src="https://skillicons.dev/icons?i=git,github" alt="Dev Tools" />

---

## 📦 Librerías y herramientas

- **Backend**: Laravel Sanctum, Spatie Laravel Permission, Intervention Image. En desarrollo: Laravel Breeze, Laravel Sail, Laravel Pint y PHPUnit 11.
- **Frontend**: React Router, Axios, Zustand, TanStack Query (React Query), React Hook Form, Zod, Lucide Icons, Framer Motion, react-hot-toast, clsx y tailwind-merge.

---


## 🏗 Estructura del proyecto
```text
.
├── .github/workflows/      # docs.yml: documentación de la API con phpDocumentor (push a main)
├── Backend/                # API Laravel (Sail en desarrollo, compose.prod.yml en producción)
│   ├── app/
│   │   ├── Actions/
│   │   ├── Console/Commands/
│   │   ├── Contracts/
│   │   ├── Http/           # Controllers, Middleware, Requests, Resources
│   │   ├── Jobs/
│   │   ├── Models/
│   │   ├── Notifications/
│   │   ├── Policies/
│   │   ├── Providers/
│   │   ├── Rules/
│   │   └── Services/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   │   ├── factories/
│   │   ├── migrations/
│   │   ├── seed-data/      # snapshots JSON del catálogo antiguo
│   │   └── seeders/
│   ├── docker/
│   │   ├── 8.5/            # imagen de Sail (desarrollo)
│   │   ├── pgsql/          # crea la base de datos de tests `testing`
│   │   └── prod/           # php.ini, PHP-FPM, Nginx interno y entrypoint
│   ├── docs/               # contrato y referencia de la API
│   ├── public/
│   ├── routes/             # api.php, auth.php, console.php
│   ├── scripts/            # verify-test-db-guard.sh
│   ├── storage/
│   └── tests/
│       ├── Concerns/
│       ├── Feature/
│       ├── Fixtures/
│       └── Unit/
├── Frontend/               # React + Vite (desplegado en Vercel)
│   ├── public/
│   └── src/
│       ├── app/
│       ├── components/
│       ├── core/
│       ├── hooks/
│       ├── lib/
│       ├── pages/
│       ├── routes/
│       └── store/
├── deploy/                 # Nginx del host y copias de seguridad del VPS
├── deploy.sh               # despliegue en producción
└── docs/                   # documentación de Kudos 2.0
```

---

## 🛠 Puesta en marcha

No hace falta PHP, Composer ni Node en tu máquina, solo Docker con Compose v2. El backend se levanta con Laravel Sail desde `Backend/` y el frontend con `docker compose up --build` desde `Frontend/`.

El paso a paso está en [docs/infra/entorno-local.md](docs/infra/entorno-local.md). Para producción, consulta [deploy/README.md](deploy/README.md) y [docs/infra/produccion.md](docs/infra/produccion.md).

---

## 🧪 Tests

Los tests del backend son de PHPUnit y se ejecutan **siempre con Sail**, contra una base de datos PostgreSQL aparte llamada `testing`: nunca SQLite ni la base de datos de desarrollo (`tests/TestCase.php` aborta si la base de datos no es `testing`). Ninguno hace peticiones a APIs externas reales.

Desde `Backend/`, con los servicios levantados:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail test                          # todos los tests
./vendor/bin/sail test --filter=EmitVoteTest    # un archivo o un test
```

Si el volumen de PostgreSQL ya existía antes de añadir la base de datos `testing`, créala una vez:

```bash
./vendor/bin/sail exec pgsql sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -f /docker-entrypoint-initdb.d/10-create-testing-database.sql'
```

Más detalle en [docs/infra/entorno-local.md](docs/infra/entorno-local.md) §4 y [docs/backend/testing.md](docs/backend/testing.md). El frontend no tiene tests; se comprueba que compila con `docker compose run --rm kudos-frontend pnpm build` desde `Frontend/`. En GitHub, `.github/workflows/tests.yml` ejecuta estos tests contra PostgreSQL y compila el frontend en cada push y pull request ([docs/backend/testing.md](docs/backend/testing.md) §7).

---

## 📚 Documentación

- [docs/README.md](docs/README.md): índice de Kudos 2.0.
- [docs/guia-agentes.md](docs/guia-agentes.md): reglas de trabajo.
- [docs/roadmap/README.md](docs/roadmap/README.md): tablero de tareas.
- [Backend/README.md](Backend/README.md): arquitectura del backend.
- [Backend/docs/api-contract.md](Backend/docs/api-contract.md): contrato JSON de la API.
- [Backend/docs/api-reference.md](Backend/docs/api-reference.md): referencia de endpoints.
- [Frontend/README.md](Frontend/README.md): frontend.
- [deploy/README.md](deploy/README.md): despliegue en producción.
