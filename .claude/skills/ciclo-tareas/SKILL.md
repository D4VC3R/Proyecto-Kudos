---
name: ciclo-tareas
description: Avanza el roadmap de Kudos 2.0 de forma automática. Cada tarea pasa por kudos-planificador (Opus, solo lectura), kudos-ejecutor (Sonnet), kudos-verificador (Opus, revisión independiente) y kudos-integrador (Sonnet), que hace los commits, abre el PR y lo fusiona con el CI en verde; si algo falla, se corrige y se vuelve a verificar. Úsala cuando el humano pida avanzar, empezar o continuar con las tareas del roadmap.
argument-hint: "[FX-YY | número de tareas]"
---

# Ciclo de tareas de Kudos 2.0

Tú eres el **orquestador**: no planificas, no implementas y no verificas; coordinas a los cuatro agentes y mantienes tu contexto pequeño. Flujo y reglas de fondo: `docs/guia-agentes.md` §2.

**Contexto pequeño:** el plan, el informe y las correcciones viajan en archivos de `.claude/ciclo/FX-YY/` (ignorada por git), no en los mensajes. A los agentes les pasas **rutas**, y ellos te devuelven resúmenes de pocas líneas. No leas esos archivos tú salvo que tengas que enseñarle algo al humano.

Argumento: un ID (`F0-02`) hace solo esa tarea; un número (`3`) hace como máximo esas tareas; sin argumento, sigue tarea tras tarea hasta una condición de parada.

## 0. Preparación (una vez por sesión)

Desde la raíz del repo:
1. `git switch rediseno-fase-0`, `git status --short` vacío y `git pull --ff-only origin rediseno-fase-0`. Si hay cambios sin commitear, para y pregunta.
2. `gh auth status` muestra la cuenta conectada. Si `gh` no está o no hay sesión, para y pide al humano que ejecute `gh auth login`.
3. Desde `Backend/`: `./vendor/bin/sail up -d` y comprueba que existe la base de datos `testing` (si no, entorno-local §4).

## 1. Elegir la tarea

En `docs/roadmap/README.md`, la primera `pendiente` con todas sus dependencias en `hecho`, sin saltar de fase. Su issue está en la columna **Issue**. Si es de `HUMANO`, o no queda ninguna disponible, para (§5).

## 2. Planificar

Calcula el rango de la ficha con `grep -n '^## F' docs/roadmap/<archivo de la fase>.md`: va desde su encabezado hasta la línea anterior al siguiente. Lanza `kudos-planificador` con el ID de la tarea, la issue, la ruta de la ficha y ese rango de líneas. Las dependencias ya las has comprobado tú, en el paso 1. Escribe el plan en `.claude/ciclo/FX-YY/plan.md` y te devuelve la ruta, la rama, un resumen corto y las decisiones para validar. Si devuelve `## BLOQUEO`, pregunta al humano con `AskUserQuestion`, pásale la respuesta en un planificador nuevo, y si sigue bloqueada, regístralo **con un PR**, como cualquier cambio (guía §4.1): desde `rediseno-fase-0`, `git switch -c fase-N/FX-YY-bloqueada`; márcala `bloqueada` en el tablero con el motivo en la ficha; un commit `Docs: FX-YY bloqueada, <motivo corto>`; `git push -u origin <rama>`; `gh pr create --base rediseno-fase-0` con el motivo y `Refs #<issue>`; con el CI en verde, `gh pr merge --merge`; `gh issue comment <issue>` con el motivo y la pregunta para el humano, y `gh issue edit <issue> --add-label humano`; vuelve a `rediseno-fase-0` con `git pull --ff-only origin rediseno-fase-0` y pasa a la siguiente.

## 3. Preparar la rama

Desde `rediseno-fase-0`: `git switch -c <rama del plan>` y cambia la tarea a `en curso` en el tablero (sin commit: entrará en el último commit del integrador). En GitHub: `gh issue edit <issue> --repo D4VC3R/Proyecto-Kudos --add-assignee @me` y `gh issue comment <issue> --repo D4VC3R/Proyecto-Kudos --body "En curso en la rama <rama>."`.

## 4. Ejecutar

Lanza `kudos-ejecutor` con `name: "ejecutor-FX-YY"`, el ID de la tarea, la rama y la ruta del plan (el ejecutor no ve esta conversación; no le copies el plan). Escribe su informe en `.claude/ciclo/FX-YY/informe.md` y te devuelve un resumen de 3 líneas.

## 5. Verificar y corregir

Lanza `kudos-verificador` con `name: "verificador-FX-YY"`, el ID de la tarea, la issue, la rama y las rutas del plan y del informe.

- `VEREDICTO: APROBADO` → el verificador ha marcado `hecho` y ha dejado `pr.md` y `revision.md`. Sigue en el paso 5.1.
- `VEREDICTO: CORRECCIONES` → el verificador las deja en `.claude/ciclo/FX-YY/correcciones-N.md`. Pásale esa ruta al ejecutor con `SendMessage` (`to: "ejecutor-FX-YY"`). Cuando responda, avisa al verificador con `SendMessage` (`to: "verificador-FX-YY"`) de que `informe.md` está actualizado. Máximo **3 rondas**, contando también las del CI; a la cuarta, para y pregunta al humano señalándole el último informe y las últimas correcciones.
- `VEREDICTO: BLOQUEADA` → pregunta al humano y actúa como en el paso 2.

### 5.1. Integrar

Lanza `kudos-integrador` con `name: "integrador-FX-YY"`, el ID de la tarea, la rama, la issue y las rutas de `plan.md`, `pr.md` y `revision.md`. Arranca con contexto limpio: la parte mecánica (commits, PR, CI, merge) no la paga el contexto del verificador.

- `INTEGRACIÓN: FUSIONADA` → compruébalo:
  - `gh pr view <n.º> --repo D4VC3R/Proyecto-Kudos --json state,mergeCommit` da `MERGED`;
  - `gh issue view <issue> --repo D4VC3R/Proyecto-Kudos --json state` da `CLOSED`;
  - `git log --oneline -5` muestra el merge;
  - `git branch --show-current` es `rediseno-fase-0`.
- `INTEGRACIÓN: CI ROJO` → el CI ha fallado:
  1. Pásale al verificador la ruta de `ci-N.log` con `SendMessage`. Te devolverá `correcciones-N.md`, con su sección `## Commit`.
  2. El ejecutor las aplica y el verificador lo comprueba, como en el paso 5.
  3. Con `VEREDICTO: APROBADO`, pásale al integrador la ruta de las correcciones con `SendMessage` (`to: "integrador-FX-YY"`). Hace el commit encima y vuelve a esperar al CI.
- `INTEGRACIÓN: BLOQUEADA` → si es un error de GitHub, reintenta pasados unos minutos con `SendMessage` al mismo integrador. Si no, pregunta al humano.

## 6. Cerrar la tarea y seguir

1. Escribe al humano un resumen de 5–10 líneas: tarea, enlace del PR, resultado de los tests y del CI (las cifras reales del verificador y del integrador), commits, desviaciones y decisiones para validar.
2. **Compactación:** cada agente arranca con contexto limpio, y de cada tarea solo guardas ese resumen; no arrastres planes ni informes viejos a la siguiente. Tú no puedes ejecutar `/compact` (solo lo puede lanzar el humano); la compactación automática actúa cuando hace falta y no se pierde nada, porque el estado vive en el tablero, en git y en `.claude/ciclo/`. Tras una compactación, vuelve al paso 1 leyendo el tablero.
3. Vuelve al paso 1 con la siguiente tarea.

## Condiciones de parada (avisa al humano y termina)

- Tarea de `HUMANO`, o no queda ninguna tarea disponible en la fase actual (pasar de fase requiere que el humano lo pida).
- Una decisión de la guía §6, o algo prohibido por R10/R11.
- 3 rondas de correcciones sin aprobar.
- Un fallo del entorno que no se arregla solo (Docker caído, base de datos `testing` inaccesible…).
- Se ha alcanzado el número de tareas del argumento.

**Límite de uso:** si un agente se corta por el límite de uso (*rate limit*), no lances otro: cuando se restablezca el límite, retómalo con `SendMessage` y conservará lo que ya había leído. La excepción es que hayan cambiado sus instrucciones mientras tanto; entonces lanza uno nuevo.

En `rediseno-fase-0` nunca se hace un commit directo: todo entra por PR (guía R11). Tú solo haces `push` y abres un PR para registrar una tarea bloqueada (§2); el de las tareas aprobadas lo hace el integrador. Nadie hace nada contra `main`, producción o el VPS.
