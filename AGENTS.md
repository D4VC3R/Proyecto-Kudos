# Instrucciones para agentes de IA

Repositorio en pleno rediseño ("Kudos 2.0"). Antes de tocar nada:

1. [docs/guia-agentes.md](docs/guia-agentes.md): reglas R1–R12, definición de "hecho" y flujo de trabajo. **Obligatoria.**
2. [docs/roadmap/README.md](docs/roadmap/README.md): elige la tarea y lee **solo** su ficha y las secciones que cite.
3. Para avanzar en el roadmap de forma automática, usa la skill `/ciclo-tareas`: planificador (Opus), ejecutor (Sonnet) y verificador (Opus). Ver la guía §2.

Las reglas esenciales (el detalle está en la guía):
- Rama base: `rediseno-fase-0`. Nunca hagas push ni merge a `main` sin permiso.
- El backend se ejecuta siempre con Sail (`sail test`, `sail artisan …`), nunca con el PHP del host.
- Los tests van sobre PostgreSQL (`testing`), nunca sobre SQLite, y ninguno llama a una API externa real.
- Prohibido sin permiso: tocar producción o el VPS, ejecutar `deploy.sh` o usar APIs de pago.
- Idioma: el código en inglés; los comentarios, los textos de usuario y los commits en español (`Área: descripción`).
