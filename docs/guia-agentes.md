# Guía para agentes

## 1. Reglas (OBLIGATORIAS)

| # | Regla |
|---|---|
| R1 | Los documentos describen la intención; **el código es la verdad**. Si se contradicen, sigue el código, corrige el documento y anótalo en el resumen. |
| R2 | Todo PHP y Artisan se ejecuta **dentro de Sail**: `sail artisan …`, `sail test` (en la shell de un agente no hay alias: `./vendor/bin/sail …` desde `Backend/`). Nunca `php` en el host. Entorno: [infra/entorno-local.md](infra/entorno-local.md). |
| R3 | Los tests van contra PostgreSQL `testing`. Nunca SQLite. No quites la protección de `tests/TestCase.php`. |
| R4 | Ningún test hace peticiones reales: `Http::preventStrayRequests()` + `Http::fake()` con fixtures ([backend/testing.md](backend/testing.md) §5). |
| R5 | Migraciones siempre **nuevas** y con un `down()` que funcione. No edites las existentes. |
| R6 | Cambio mínimo: no refactorices código ajeno a la tarea. **No ejecutes Pint** sobre archivos que no hayas tocado. Imita el estilo del archivo, también la indentación (hay archivos con tabuladores y otros con espacios). |
| R7 | No rompas el contrato JSON del frontend: los campos se **añaden**, nunca se renombran ni se quitan. |
| R8 | No borres tests. Si uno deja de tener sentido, explícalo en el resumen. No desactives validaciones, *policies* ni middlewares para que algo pase. |
| R9 | Sin secretos en el repo. Cada variable nueva va vacía en `Backend/.env.example` y `Backend/.env.production.example`, y se documenta en [infra/produccion.md](infra/produccion.md) §3. |
| R10 | **Prohibido sin permiso explícito del humano:** conectarse al VPS, ejecutar `deploy.sh` o cualquier comando contra producción; usar APIs de pago o pruebas que caduquen; incumplir los términos de una API ([integraciones/fuentes-externas.md](integraciones/fuentes-externas.md) §1); enviar correos reales, publicar o crear cuentas en servicios externos; implementar funciones **aplazadas** ([producto/vision.md](producto/vision.md) §3). |
| R11 | Git: el único `push` permitido es el de la rama de la tarea (`fase-N/…`) para abrir su PR contra `rediseno-fase-0`, y lo hace el verificador (§4.1). Nunca `push` a `rediseno-fase-0` ni a `main`, nunca `--force` ni `--no-verify`, y nunca se fusiona nada en `main`. |
| R12 | Si la tarea es inviable o mucho mayor de lo descrito, o una verificación no se puede hacer en tu entorno: **para y pregunta**. Nunca des por hecha una tarea sin verificar. |

**Excepciones puntuales aprobadas:** a R5, el esquema literal en las dos migraciones de activación (D6, solo en la F5-06), y a R6, un único commit de Pint (D4, solo en la F5-15). Ver [roadmap/fase-5-refactor.md](roadmap/fase-5-refactor.md), F5-00. No se extienden a otras tareas.

## 2. Flujo de una tarea

**Automatizado:** la skill `/ciclo-tareas` ([../.claude/skills/ciclo-tareas/SKILL.md](../.claude/skills/ciclo-tareas/SKILL.md)) recorre el roadmap tarea a tarea con tres agentes ([../.claude/agents/](../.claude/agents/)):

| Fase | Agente | Hace los pasos |
|---|---|---|
| Inicio | `kudos-planificador` (Opus, esfuerzo alto, solo lectura salvo su plan) | 1–2: elige la tarea, lee y escribe un plan detallado |
| Ejecución | `kudos-ejecutor` (Sonnet, esfuerzo alto) | 5–7: implementa el plan, verifica y documenta, sin commits |
| Fin | `kudos-verificador` (Opus, esfuerzo alto) | 6–9: verifica de forma independiente; si todo está bien, marca `hecho`, hace los commits, abre el PR contra `rediseno-fase-0` y lo fusiona con el CI en verde (§4.1); si no, devuelve correcciones al ejecutor (máximo 3 rondas) |

El orquestador (la sesión principal) crea la rama y marca `en curso` (pasos 3–4), y para y pregunta al humano ante cualquier punto de §6, R10, R11 o R12. Cada agente arranca con contexto limpio: entre tareas solo pasa un resumen. El plan, el informe del ejecutor y las correcciones se pasan como archivos en `.claude/ciclo/FX-YY/` (ignorada por git), y los agentes solo devuelven al orquestador resúmenes cortos, para que su contexto no crezca con cada tarea.

Los pasos, hagas la tarea a mano o con los agentes:

1. En [roadmap/README.md](roadmap/README.md), elige la primera tarea `pendiente` con todas sus dependencias en `hecho`. No saltes de fase salvo que el humano lo pida.
2. Lee su ficha, las secciones que cite en **Lee** y el código actual de los **Archivos**.
3. Marca la tarea `en curso` en el tablero.
4. `git switch rediseno-fase-0 && git pull --ff-only origin rediseno-fase-0`, y crea `fase-N/FN-XX-descripcion-corta` (ej. `fase-0/F0-03-recalculo-medias`). Cuando la tarea esté hecha, entra en `rediseno-fase-0` mediante un PR (§4.1). A `main` solo se fusiona por fases, con aprobación y lo hace el humano.
5. Implementa.
6. Verifica la definición de hecho (§3).
7. Actualiza la documentación: estado `hecho` en el tablero, una sección **Notas** en la ficha con las decisiones y desviaciones, y los documentos de referencia que hayan quedado desfasados.
8. Haz commit (§4), abre el PR y fusiónalo cuando el CI esté en verde (§4.1).
9. Escribe el resumen final: qué se ha hecho, cómo se ha verificado (pegando la salida real de los tests), qué queda pendiente y qué decisiones necesitan validación humana.

Si la tarea no puede avanzar, márcala `bloqueada` y explica el motivo en su ficha.

## 3. Definición de "hecho"

- [ ] Se cumplen todos los puntos de **Hecho cuando** de la ficha.
- [ ] **Backend tocado:** `sail test` en verde y tests nuevos que cubren el cambio. Un bug corregido lleva un test que fallaba antes del arreglo.
- [ ] **Frontend tocado:** `docker compose run --rm kudos-frontend pnpm build` (desde `Frontend/`) termina sin errores, y has hecho una prueba manual del flujo tocado.
- [ ] **Migraciones:** el `down()` funciona y `migrate:fresh` funciona, probados **contra `testing`** (desde `Backend/`: `docker compose exec -T -e DB_DATABASE=testing kudos-app php artisan migrate:fresh`, `… migrate:rollback --step=N`, `… migrate`). `--seed` solo cuando la ficha lo pida.
- [ ] Se cumplen R4 y R9.
- [ ] El tablero y la ficha están actualizados.

## 4. Commits

- Formato `Área: descripción`, en español. Por ejemplo: `Votos: medias exactas con AVG`.
- Áreas: `Votos`, `Catálogo`, `Ranking`, `Tests`, `Frontend`, `Infra`, `Despliegue`, `Legal`, `Docs`, `Seguridad`, `General`.
- Un commit por unidad lógica, con `Tarea: FX-YY` en el cuerpo.

### 4.1. Pull requests

Desde la F5-02, cada tarea entra en `rediseno-fase-0` mediante un PR en GitHub, para que la revisión y el CI queden a la vista. Las tareas anteriores se fusionaron en local y sus ramas no se suben.

- **Título:** como un commit, `Área: FX-YY, descripción`.
- **Descripción:** `## Qué cambia`, `## Cómo se ha verificado` (la salida real de los tests y del build), `## Notas` (desviaciones y decisiones para validar) y la línea `Tarea: FX-YY · Ficha: …`.
- **Fusión:** solo con el workflow `Tests` en verde, como merge commit (`gh pr merge --merge`, con el asunto `Área: fusiona FX-YY (…)`), nunca *squash*, *rebase* ni `--admin`. GitHub borra la rama remota al fusionar.
- **CI en rojo:** se corrige con commits nuevos en la misma rama, sin reescribir el historial ni abrir otro PR.
- El PR final `rediseno-fase-0 → main` (F5-16) lo fusiona el humano.

## 5. Convenciones

**Backend** (detalle en [backend/arquitectura.md](backend/arquitectura.md)):
- Controller → Form Request → Action (una escritura transaccional) o Service (lógica reutilizable) → Model. Sin repositorios.
- Toda respuesta JSON pasa por `app/Http/Resources`. Los errores siguen el formato `{"error": {"code", "message", "details?"}}` de `bootstrap/app.php`.
- Los parámetros ajustables (pesos, umbrales, recompensas) van en `config/*.php`, con un comentario. `env()` solo se usa dentro de `config/`.
- Nombres en inglés. Comentarios, docblocks y mensajes al usuario, en español.

**Frontend** (detalle en [frontend/arquitectura.md](frontend/arquitectura.md)):
- Cada página tiene un hook `hooks/pages/useXPage.js` que devuelve `{ state, actions }`. Las llamadas a la API van solo en `hooks/<dominio>/`, con React Query.
- Solo se usan clases Tailwind que existan; lo nuevo se define en `tailwind.config.js`. `clsx`/`tailwind-merge` se importan directamente (no hay `cn()`).
- Textos de la interfaz en español, tuteando.

**Dependencias nuevas:** solo si la ficha las prevé o lo justificas en el resumen; con versión fijada y sin servicios de pago.

## 6. Pregunta al humano antes de…

- decidir algo de producto que no esté documentado: textos legales, pesos por encima de los valores por defecto, diseño visual nuevo;
- cambiar un contrato de la API que la ficha no prevea;
- integrar una API cuyos términos hayan cambiado respecto a lo documentado.
