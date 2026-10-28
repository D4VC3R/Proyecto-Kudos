---
name: ciclo-tareas
description: Avanza el roadmap de Kudos 2.0 de forma automática. Cada tarea pasa por kudos-planificador (Opus, solo lectura), kudos-ejecutor (Sonnet) y kudos-verificador (Opus), que hace el commit y la fusión; si algo falla, se corrige y se vuelve a verificar. Úsala cuando el humano pida avanzar, empezar o continuar con las tareas del roadmap.
argument-hint: "[FX-YY | número de tareas]"
---

# Ciclo de tareas de Kudos 2.0

Tú eres el **orquestador**: no planificas, no implementas y no verificas; coordinas a los tres agentes y mantienes tu contexto pequeño. Flujo y reglas de fondo: `docs/guia-agentes.md` §2.

**Contexto pequeño:** el plan, el informe y las correcciones viajan en archivos de `.claude/ciclo/FX-YY/` (ignorada por git), no en los mensajes. A los agentes les pasas **rutas**, y ellos te devuelven resúmenes de pocas líneas. No leas esos archivos tú salvo que tengas que enseñarle algo al humano.

Argumento: un ID (`F0-02`) hace solo esa tarea; un número (`3`) hace como máximo esas tareas; sin argumento, sigue tarea tras tarea hasta una condición de parada.

## 0. Preparación (una vez por sesión)

Desde la raíz del repo:
1. `git switch rediseno-fase-0` y `git status --short` vacío. Si hay cambios sin commitear, para y pregunta.
2. Desde `Backend/`: `./vendor/bin/sail up -d` y comprueba que existe la base de datos `testing` (si no, entorno-local §4).

## 1. Elegir la tarea

En `docs/roadmap/README.md`, la primera `pendiente` con todas sus dependencias en `hecho`, sin saltar de fase. Si es de `HUMANO`, o no queda ninguna disponible, para (§5).

## 2. Planificar

Lanza `kudos-planificador` con: el ID de la tarea y la ruta de su ficha. Escribe el plan en `.claude/ciclo/FX-YY/plan.md` y te devuelve la ruta, la rama, un resumen corto y las decisiones para validar. Si devuelve `## BLOQUEO`, pregunta al humano con `AskUserQuestion`, pásale la respuesta en un planificador nuevo, y si sigue bloqueada márcala `bloqueada` en el tablero con el motivo en la ficha, haz commit (`Docs: FX-YY bloqueada`) y pasa a la siguiente.

## 3. Preparar la rama

Desde `rediseno-fase-0`: `git switch -c <rama del plan>` y cambia la tarea a `en curso` en el tablero (sin commit: entrará en el commit del verificador).

## 4. Ejecutar

Lanza `kudos-ejecutor` con `name: "ejecutor-FX-YY"`, el ID de la tarea, la rama y la ruta del plan (el ejecutor no ve esta conversación; no le copies el plan). Escribe su informe en `.claude/ciclo/FX-YY/informe.md` y te devuelve un resumen de 3 líneas.

## 5. Verificar y corregir

Lanza `kudos-verificador` con `name: "verificador-FX-YY"`, el ID de la tarea, la rama y las rutas del plan y del informe.

- `VEREDICTO: APROBADO` → el verificador ya ha hecho los commits y la fusión. Compruébalo con `git log --oneline -5` y `git branch --show-current` (debe ser `rediseno-fase-0`).
- `VEREDICTO: CORRECCIONES` → el verificador las deja en `.claude/ciclo/FX-YY/correcciones-N.md`. Pásale esa ruta al ejecutor con `SendMessage` (`to: "ejecutor-FX-YY"`) y, cuando responda, avisa al verificador con `SendMessage` (`to: "verificador-FX-YY"`) de que `informe.md` está actualizado. Máximo **3 rondas**; a la cuarta, para y pregunta al humano señalándole el último informe y las últimas correcciones.
- `VEREDICTO: BLOQUEADA` → pregunta al humano y actúa como en el paso 2.

## 6. Cerrar la tarea y seguir

1. Escribe al humano un resumen de 5–10 líneas: tarea, resultado de los tests (las cifras reales del verificador), commits, desviaciones y decisiones para validar.
2. **Compactación:** cada agente arranca con contexto limpio, y de cada tarea solo guardas ese resumen; no arrastres planes ni informes viejos a la siguiente. Tú no puedes ejecutar `/compact` (solo lo puede lanzar el humano); la compactación automática actúa cuando hace falta y no se pierde nada, porque el estado vive en el tablero, en git y en `.claude/ciclo/`. Tras una compactación, vuelve al paso 1 leyendo el tablero.
3. Vuelve al paso 1 con la siguiente tarea.

## Condiciones de parada (avisa al humano y termina)

- Tarea de `HUMANO`, o no queda ninguna tarea disponible en la fase actual (pasar de fase requiere que el humano lo pida).
- Una decisión de la guía §6, o algo prohibido por R10/R11.
- 3 rondas de correcciones sin aprobar.
- Un fallo del entorno que no se arregla solo (Docker caído, base de datos `testing` inaccesible…).
- Se ha alcanzado el número de tareas del argumento.

Nunca hagas `push`, ni nada contra `main`, producción o el VPS.
