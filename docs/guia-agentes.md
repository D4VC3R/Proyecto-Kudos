# Guía para agentes (y personas) que ejecuten el rediseño

Lee este documento entero antes de empezar cualquier tarea. Las reglas marcadas como **OBLIGATORIO** no son negociables.

## 1. Cómo trabajar una tarea

1. **Elige la tarea.** Abre [roadmap/README.md](roadmap/README.md) y elige la primera tarea en estado `pendiente` cuyas dependencias estén todas en `hecho`. No saltes fases salvo que el humano lo pida.
2. **Lee lo necesario.**
   - La ficha de la tarea, dentro de `roadmap/fase-N-*.md`.
   - Los documentos de referencia que la ficha cite.
   - **El código actual de los archivos afectados.** Los documentos describen la intención; el código es la verdad. Si se contradicen, sigue el código, anota la discrepancia en el resumen final y corrige el documento.
3. **Marca la tarea como `en curso`** en el tablero.
4. **Crea una rama** desde `main` actualizado, salvo que el humano indique otra base. Nombre: `fase-N/FN-XX-descripcion-corta`, por ejemplo `fase-0/F0-03-recalculo-medias`.
5. **Implementa con el cambio mínimo** que cumpla los criterios de aceptación. No "aproveches" para refactorizar código ajeno a la tarea.
6. **Verifica.** Ejecuta las comprobaciones de la ficha y la definición de hecho (§3).
7. **Actualiza la documentación:**
   - estado `hecho` en el tablero;
   - notas de implementación en la ficha: decisiones tomadas y desviaciones respecto al plan;
   - los documentos de referencia que hayan quedado desactualizados.
8. **Haz commit siguiendo §4.** No hagas push ni abras un PR sin permiso del humano.
9. **Escribe un resumen final** con:
   - qué se ha hecho;
   - cómo se ha verificado, con la salida real de los tests;
   - qué ha quedado pendiente;
   - qué decisiones necesitan validación humana.

Si una tarea resulta inviable o mucho mayor de lo descrito, **para y pregunta** al humano en lugar de improvisar un rediseño.

## 2. Entorno

- **Linux con Docker.** El backend se ejecuta con Laravel Sail y el frontend con su propio `docker compose`. Instrucciones en [infra/entorno-local.md](infra/entorno-local.md).
- **Todo comando PHP o Artisan va dentro del contenedor.** El PHP del host puede ser de otra versión o no existir. Se usa `./vendor/bin/sail artisan …` (o el alias `sail`), nunca `php artisan` en el host.

## 3. Definición de "hecho" (OBLIGATORIO)

Una tarea solo está hecha si se cumple todo lo siguiente:

- [ ] Se cumplen **todos** los criterios de aceptación de la ficha.
- [ ] **Si toca el backend:**
  - `sail test` pasa en verde contra PostgreSQL (nunca SQLite);
  - hay tests nuevos que cubren el cambio. Un bug corregido lleva un test que fallaba antes del arreglo.
- [ ] **Si toca el frontend:** `pnpm build` termina sin errores (se ejecuta en el contenedor del frontend; ver el documento de entorno).
- [ ] **Si hay migraciones:**
  - son **nuevas**: no se editan las migraciones ya existentes;
  - su `down()` funciona;
  - `sail artisan migrate:fresh --seed` funciona en local.
- [ ] **Ningún test llama a una API externa real.** Se usa `Http::fake()` con fixtures; ver [backend/testing.md](backend/testing.md).
- [ ] No se añaden secretos al repositorio. Las variables nuevas se documentan en `.env.example`, en `.env.production.example` y en [infra/produccion.md](infra/produccion.md).
- [ ] El tablero y la ficha están actualizados.

## 4. Git

- **Mensajes de commit en español**, con el formato `Área: descripción en imperativo o nominal`, como los existentes:
  - `Seguridad: PHP 8.5.12 en las imágenes (corrige PHP2Shell)`
  - `General: interruptor para la verificación de email (apagada por defecto)`
  - `Despliegue: imagen y stack de producción para el VPS`
- **Áreas sugeridas:** `Votos`, `Catálogo`, `Ranking`, `Tests`, `Frontend`, `Infra`, `Despliegue`, `Legal`, `Docs`.
- **Un commit por unidad lógica.** Incluye el ID de la tarea en el cuerpo del mensaje, por ejemplo `Tarea: F0-03`.
- **Nunca:** `push --force`, reescribir el historial de `main`, `--no-verify` ni hacer merge a `main` sin aprobación.

## 5. Convenciones de código

**Backend** (detalle en [backend/arquitectura.md](backend/arquitectura.md)):
- **Arquitectura:**
  - Controller → Form Request → Action (una escritura transaccional) / Service (lógica reutilizable) → Model.
  - No se usan repositorios.
- **Nombres:** en inglés en el código; comentarios y docblocks en español.
- **Formato:**
  - **Imita el estilo del archivo que edites**, también la indentación: hay archivos con tabuladores y otros con espacios.
  - **No ejecutes Pint sobre archivos que no hayas modificado** ni sobre el repositorio completo: generaría diffs enormes.
- **Respuestas JSON:**
  - siempre a través de `app/Http/Resources`;
  - los errores siguen el formato `{"error": {"code", "message", "details?"}}` definido en `bootstrap/app.php`.
- **Configuración:**
  - los parámetros ajustables (pesos, umbrales, recompensas) van en `config/*.php`, nunca como números mágicos;
  - `env()` solo se llama dentro de `config/`.
- **Mensajes al usuario** (validación, errores) en español.

**Frontend** (detalle en [frontend/arquitectura.md](frontend/arquitectura.md)):
- **Organización:**
  - cada página tiene un hook `hooks/pages/useXPage.js` que devuelve `{ state, actions }`;
  - las consultas y mutaciones van en `hooks/<dominio>/` con React Query.
- **Estilos:**
  - Tailwind con clases que existan: no inventes utilidades sin definirlas en `tailwind.config.js`;
  - para unir clases condicionales usa `clsx`/`tailwind-merge` a través de las utilidades existentes en `lib/`.
- **Textos de la interfaz** en español.

**Dependencias nuevas:**
- solo si la ficha lo prevé o está justificado en el resumen final;
- con la versión fijada;
- sin servicios de pago.

## 6. Prohibido sin permiso explícito del humano

- **Producción:**
  - conectarse al VPS o ejecutar `deploy.sh`;
  - ejecutar cualquier comando contra producción, incluido `migrate:fresh`.

  Para producción se escriben *runbooks* que ejecuta el humano.
- **APIs:**
  - usar APIs de pago o planes de prueba que caduquen;
  - incumplir los términos de una API ([legal/cumplimiento.md](legal/cumplimiento.md)). En particular, no guardar datos de TMDB más de 6 meses y no usar las portadas de iTunes.
- **Comunicaciones:** enviar correos reales, publicar en servicios externos o crear cuentas en ellos.
- **Seguridad:** desactivar validaciones, *policies* o middlewares de seguridad para "hacer pasar" un test.
- **Tests:** borrar tests existentes. Si uno deja de tener sentido, explica por qué en el resumen.
- **Alcance:** implementar funciones de la lista de **aplazados** de [producto/vision.md](producto/vision.md).

## 7. Cuándo preguntar al humano

- Cuando haya que decidir algo de producto que no esté en los documentos (textos legales, pesos de recomendación más allá de los valores por defecto, diseño visual nuevo).
- Antes de cambiar un contrato de la API usado por el frontend, si la ficha no lo prevé.
- Si los términos de uso de una API han cambiado respecto a lo documentado.
- Si una verificación no puede hacerse en el entorno disponible. En ese caso, nunca se da la tarea por hecha.
