# Frontend Kudos (React + Vite)

Esqueleto base para empezar a desarrollar el frontend desacoplado consumiendo la API de `Backend/`.

## Incluye

- React con JSX
- Vite
- Tailwind CSS
- React Router DOM
- Cliente HTTP con Axios (`src/lib/apiClient.js`)
- React Query + react-hot-toast`

## Variables de entorno

Copia `.env.example` a `.env` y ajusta si hace falta:

```bash
cp .env .env
```

Variable actual:

- `VITE_API_URL=http://localhost:8095/api`

## Docker (desarrollo)

Desde `Frontend/`:

```bash
docker compose up --build
```

Vite queda disponible en:

- `http://localhost:5174`

## Nota de integracion con backend

Para evitar bloqueo por CORS, en `Backend/.env` define:

```env
FRONTEND_URL=http://localhost:5174
```

Luego reinicia el backend si estaba levantado.

