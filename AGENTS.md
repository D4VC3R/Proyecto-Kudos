# Instrucciones para agentes de IA

Este repositorio está en pleno rediseño ("Kudos 2.0"). Antes de hacer cualquier cambio:

1. Lee [docs/README.md](docs/README.md), el índice y el resumen.
2. Lee [docs/guia-agentes.md](docs/guia-agentes.md): reglas obligatorias, definición de "hecho", git y prohibiciones.
3. Elige la tarea en [docs/roadmap/README.md](docs/roadmap/README.md) y lee su ficha.

Lo esencial:
- **El backend se ejecuta siempre con Laravel Sail.** Usa `sail test` y `sail artisan …`, nunca el PHP del host.
- **Tests:**
  - corren sobre PostgreSQL, nunca SQLite;
  - ningún test llama a APIs externas reales.
- **Prohibido sin permiso del humano:**
  - tocar producción o el VPS, o ejecutar `deploy.sh`;
  - hacer push o merge a `main`;
  - usar APIs de pago.
- **Idioma:** el código en inglés; los comentarios, mensajes de usuario y commits en español (formato `Área: descripción`).
