# 💻 Proyecto Kudos - Frontend

<img src="https://skillicons.dev/icons?i=react,vite,tailwind" alt="Frontend Stack" />

> SPA de Kudos (React 18 + Vite 6) que consume la API de `Backend/`; se despliega en Vercel.

---

## 📦 ¿Qué incluye?

El proyecto está configurado con las siguientes herramientas para mantener un código limpio, estructurado y escalable:

- **Core:** ⚛️ React (JSX) + ⚡ Vite + 🎨 Tailwind CSS
- **Enrutamiento:** 🛣️ React Router DOM
- **Peticiones HTTP:** 🌐 Axios *(Configurado de forma centralizada en `src/core/axiosClient.js`)*
- **Estado y Caché:** 🐻 Zustand + 🔄 React Query
- **Formularios y Validación:** 📝 React Hook Form + 🛡️ Zod
- **UI y Experiencia:** ✨ Lucide Icons + 🎬 Framer Motion + 🔔 react-hot-toast

---

## 🧰 Scripts

- `pnpm dev`: servidor de desarrollo de Vite.
- `pnpm build`: compila la aplicación para producción.
- `pnpm preview`: sirve la compilación en local (puerto 4174).
- `pnpm test:seo`: tests de Node de la función SEO de `api/`.
- `pnpm check:classes`: comprueba que solo se usan clases Tailwind que existen.
- `scripts/visual-check.sh`: verificación visual automática con Playwright dentro de Docker (no instala nada en el host). Con el frontend en `localhost:5174`, para cada ruta, ancho y tema hace una captura a página completa y comprueba errores de JS y de consola, desbordamiento horizontal, un solo `h1`, contraste del texto (4,5:1; 3:1 en texto grande) y el anillo de foco de los 15 primeros Tab (≥ 3:1). Se lanza desde la raíz del repo:

  ```bash
  Frontend/scripts/visual-check.sh --routes / --widths 360
  Frontend/scripts/visual-check.sh --routes /admin,/admin/users --auth --widths 1440
  Frontend/scripts/visual-check.sh --routes rutas.json --themes all --out ./capturas
  ```

  - `--routes` (obligatorio): lista separada por comas o un `.json` con un array de rutas, cada una `"/x"` o `{ "path": "/x", "auth": true, "name": "opcional", "click": "selector" }`. `click` es el selector de Playwright que se pulsa tras cargar la página (para medir lo que no tiene URL, como una pestaña); también acepta un array de selectores, que se pulsan en orden (un menú y luego un modal). `mocks` es un array de `{ "url", "method"?, "status"?, "json" }` que responde esas peticiones sin llegar al backend, y `clock` una fecha ISO que fija `Date`: `{ "path": "/", "auth": true, "name": "recompensa", "clock": "2099-01-01T12:00:00", "mocks": [{ "url": "**/api/profile/daily-claim", "method": "POST", "json": { "data": { "status": "claimed", "streak": 7, "kudos": 1250 }, "meta": { "kudos_awarded": 30, "base_kudos": 15, "multiplier": 2, "is_new_record": true, "is_critical": true, "server_date": "2099-01-01" } } }], "click": "[role=\"dialog\"] h3" }`. Otro ejemplo, con `click` como cadena: `{ "path": "/profile", "auth": true, "name": "perfil-votos", "click": "role=tab[name=\"Historial de Votos\"]" }`. El Tab empieza en el elemento pulsado.
  - `--widths` (por defecto `360,768,1440`), `--height` (`900`), `--base` (`http://localhost:5174`).
  - `--themes`: `current` (por defecto), `all` (4 paletas × claro y oscuro) o una lista como `azulejo-dark,original-light`. Aplica `localStorage['kudos.theme']` antes de cargar.
  - `--auth`: las rutas de la lista van con sesión. Inicia sesión por la interfaz con el usuario del *seeder* local: `visual-check.sh` lee `SEED_ADMIN_EMAIL` y `SEED_ADMIN_PASSWORD` de `Backend/.env` (se sustituyen con `KUDOS_VISUAL_EMAIL` y `KUDOS_VISUAL_PASSWORD`).
  - `--out`: carpeta de salida (por defecto `.claude/ciclo/visual-check/`) con las capturas `ruta-ancho-tema[-sesion].png`, `report.json` (incluye `focus.order`, el orden del Tab) y `storageState.json`. Este último lleva el token de la sesión: no se commitea (`.claude/ciclo/` está ignorada).
  - Salida por consola: una tabla resumen y el detalle de cada fila que falla. Código de salida: `0` todo bien, `1` alguna comprobación falla, `2` error de la herramienta (argumentos, *login* o frontend caído).
  - El contraste no mide los textos sobre una imagen, un degradado o una capa superpuesta (los cuenta como `skipped` en `report.json`).

---

## 🗂 Estructura

- `src/`: la SPA (páginas, componentes, hooks y rutas).
- `api/`: función SEO de Vercel.
- `tests/seo/`: tests de la función SEO.
- `scripts/`: utilidades de desarrollo (`check-tailwind-classes.mjs`, `visual-check.sh`).

Detalle en [../docs/frontend/arquitectura.md](../docs/frontend/arquitectura.md). Cómo ejecutar los comandos con Docker: [../docs/infra/entorno-local.md](../docs/infra/entorno-local.md) §5.

---

## ⚙️ Variables de Entorno

Copia el archivo de ejemplo `.env.example` a `.env` y ajusta los valores si hace falta. Para desarrollo local, las siguientes variables por defecto deberían ser suficientes para conectar con el backend:

```env
VITE_API_URL=http://localhost:8095/api
VITE_STORAGE_URL=http://localhost:8095/storage/
```

---

## 🐳 Docker (Desarrollo)

Para levantar el entorno local de forma aislada, sitúate en el directorio raíz del `Frontend/` y ejecuta:

```bash
docker compose up --build
```

📍 Vite quedará compilando y disponible en: **[http://localhost:5174](http://localhost:5174)**

---

## 🔌 Nota de Integración con Backend

> ⚠️ **IMPORTANTE:** Para evitar que las peticiones al servidor sean bloqueadas por políticas **CORS**, es necesario registrar la URL del cliente en el servidor.

En el directorio `Backend/`, abre el archivo `.env` y asegúrate de definir la ruta exacta del frontend:

```env
FRONTEND_URL=http://localhost:5174
```

*(Recuerda reiniciar el servidor o el contenedor del backend si ya estaba levantado para que aplique esta nueva configuración).*