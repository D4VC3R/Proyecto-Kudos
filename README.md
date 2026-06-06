# Proyecto Kudos

![Estado del build](https://img.shields.io/badge/build-passing-brightgreen)
![Versión](https://img.shields.io/badge/version-1.0.0-blue)
![Licencia](https://img.shields.io/badge/license-MIT-green)

<p align="center">
  <img src="./Frontend/public/logo.svg" alt="Logo Kudos" width="200" />
</p>

Proyecto Kudos es una plataforma de valoraciones multitemática con elementos de gamificación donde los usuarios crean, puntúan y comentan elementos culturales, sociales o populares, generando clasificaciones o ‘ránkings’ divididos por temáticas mientras ganan puntos Kudos con cada interacción, que les hará escalar en la clasificación general de usuarios.

---

## 🚀 Tecnologías utilizadas

**Backend**: Laravel, PHP, MySQL, Redis, Docker  
  <img src="https://skillicons.dev/icons?i=laravel,php,mysql,redis,docker" alt="Backend Stack" />

**Frontend**: React, JavaScript, Tailwind CSS, Vite  
  <img src="https://skillicons.dev/icons?i=react,js,tailwind,vite" alt="Frontend Stack" />

**Otros**: Git, GitHub, Postman  
  <img src="https://skillicons.dev/icons?i=git,github,postman" alt="Dev Tools" />

---

## 📦 Librerías y herramientas

- **Backend**: Laravel Sanctum, Spatie Media Library, Laravel Telescope, Intervention Image, Laravel Breeze
- **Frontend**: React Router, Axios, Zustand, React Query, React Hook Form, Zod, Lucide Icons, Framer Motion, react-hot-toast
---


## 🏗 Estructura del proyecto
```text
/
.
├── Backend
│ ├── app
│ │ ├── Actions
│ │ ├── Contracts
│ │ ├── Http
│ │ ├── Jobs
│ │ ├── Models
│ │ ├── Policies
│ │ ├── Providers
│ │ ├── Rules
│ │ └── Services
│ ├── bootstrap
│ │ └── cache
│ ├── config
│ ├── database
│ │ ├── factories
│ │ ├── migrations
│ │ ├── seed-data
│ │ └── seeders
│ ├── docker
│ │ ├── 8.5
│ │ └── pgsql
│ ├── docs
│ ├── public
│ ├── routes
│ ├── storage
│ │ ├── app
│ │ ├── framework
│ │ ├── logs
│ │ └── media-library
│ └── tests
│     └── Feature
└── Frontend
    ├── public
    └── src
        ├── app
        ├── components
        ├── core
        ├── hooks
        ├── lib
        ├── pages
        ├── routes
        └── store
```

