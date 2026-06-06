# 💻 Proyecto Kudos - Frontend

<img src="https://skillicons.dev/icons?i=react,vite,tailwind" alt="Frontend Stack" />

> Esqueleto base para empezar a desarrollar el frontend de forma modular y desacoplada, consumiendo la API de `Backend/`.

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

## ⚙️ Variables de Entorno

Copia el archivo de ejemplo `.env.example` a `.env` y ajusta los valores si hace falta. Para desarrollo local, las siguientes variables por defecto deberían ser suficientes para conectar con el backend:

```env
VITE_API_URL=http://localhost:8095/api
VITE_STORAGE_URL=http://localhost:8095/api/storage
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