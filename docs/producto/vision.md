# Visión de producto: Kudos 2.0

**Responsable:** producto (el humano). Los agentes leen este documento para entender el porqué; no lo modifican sin permiso.

## 1. Diagnóstico del estado actual

Kudos nació para **descubrir, valorar y comentar cualquier cosa de interés**: un viajero descubre ciudades, un *gamer* descubre juegos… La ejecución actual no cumple ese propósito.

- **Votar es valorar ítems al azar.**
  - `NextCategoryItemService` elige 50 ítems aleatorios no votados. El usuario recibe sobre todo cosas que no conoce y no puede valorar con criterio.
  - La escala es de 0 a 10 con medios puntos y se maneja con arrastre de puntero: es lenta.
  - El ranking es una media simple: un ítem con un único 10 queda el primero.
  - Los kudos premian el volumen de votos: 5 kudos por voto. Eso incentiva votar a ciegas.
- **El catálogo es amplio pero está muerto.**
  - 9 categorías cargadas una sola vez desde snapshots JSON (`Backend/database/seed-data/`), sin identificadores externos. No se pueden refrescar ni mostrar novedades.
  - Libros, ciudades, políticos y artistas no tienen imágenes.
  - Muchas descripciones son plantillas: "Item importado.", "… importado desde Wikidata".
- **No hay capa personal ni social:**
  - no hay búsqueda global ni *wishlist*;
  - no hay listas ni recomendaciones;
  - los comentarios son planos;
  - la portada es una rejilla estática de categorías.

## 2. Enfoque nuevo

> **"Tu diario de pelis, series y juegos en español, y destinos que descubrir, con listas que lo conectan todo."**

Principios:
1. **Menos categorías, pero vivas y completas.** Una ficha sin imagen ni descripción no se muestra en la portada ni en la cola de votación.
2. **Cada interacción aporta al descubrimiento.** "No lo conozco" deja de ser un descarte y pasa a ser "me interesa" (*wishlist*) o "no me interesa".
3. **Funcionar con pocos usuarios.** Las recomendaciones salen del contenido (géneros, plataformas, país), de listas editoriales y de la popularidad externa (TMDB, IGDB), no del volumen de votos propio.
4. **El diferencial:**
   - todo en español;
   - listas que mezclan categorías;
   - más adelante, relaciones entre categorías sacadas de Wikidata (libro → película → lugar).

   Ojo: las recomendaciones entre categorías **no** son únicas (TasteDive y Likewise ya las hacen). No se debe vender como tal.
5. **Coste cero y legalidad.**
   - Solo APIs gratuitas, respetando sus términos.
   - Proyecto sin ánimo de lucro: sin publicidad y sin funciones de pago.

## 3. Decisiones tomadas (26-10-2026)

| Decisión | Detalle |
|---|---|
| **Categorías vivas** | **Películas** y **Series** (TMDB), **Videojuegos** (IGDB o RAWG, más la Wikipedia en español) y **Destinos**: ciudades y países fusionados (Wikidata, Wikipedia ES, Commons, OSM) |
| Categorías que salen | Libros, álbumes musicales y artistas musicales: no se reimportan, pero sus snapshots se conservan en `seed-data/` para recuperarlos más adelante. **Políticos se elimina** (atrae toxicidad y no aporta al descubrimiento) |
| Datos actuales | **Son de prueba** (los generó el seeder) **y se pueden resetear.** No hay que migrar votos, kudos ni imágenes. El catálogo se reconstruye importando desde los proveedores |
| Plazo | Sin fecha límite. Las fases se ejecutan en orden, unas 10–12 semanas a tiempo completo en total |
| Base de datos | Cambios mínimos: columnas nuevas en `items`, una sola tabla nueva de catálogo (`item_sources`) y se mantiene la tabla `votes` ampliando sus tipos. Ver [../backend/base-de-datos.md](../backend/base-de-datos.md) |
| Idioma | Solo español. No hay tabla de traducciones. No se usa traducción automática en el servidor (LibreTranslate no cabe en el VPS) |
| Escala de valoración | 5 niveles en la interfaz, guardados como 2/4/6/8/10 en la columna `score` actual, que sigue siendo de 0 a 10 |
| Ranking | Media bayesiana (`bayes_score`) con `m` configurable y bajo |
| Cara a cara | Solo como ranking **personal**, nunca global, hasta que haya volumen |

## 4. Funciones aplazadas y su umbral de activación

**No implementar** sin que el humano lo pida. Con decenas o cientos de usuarios, estas funciones saldrían vacías o con ruido.

| Función | Se activa cuando… |
|---|---|
| Filtrado colaborativo ítem-ítem ("a quien le gustó X también…") | el ítem tiene ≥ 5 usuarios que también han valorado otros ítems en común |
| % de compatibilidad entre usuarios | los dos usuarios comparten ≥ 10 ítems valorados |
| Tendencias, "Polémicos", feed de seguidos, follows | la actividad diaria sea suficiente (se decide con datos reales) |
| Elo global, reputación (`CheckMinimumKudos`), insignias, retos semanales, trivia | — (sin umbral fijado) |
| Edición comunitaria de fichas (estilo wiki) | De momento hay un botón "Sugerir corrección" que llega al admin |
| Libros, álbumes, anime | Cuando las tres categorías vivas funcionen bien. Su cobertura en español es floja |
| Modo oscuro | — (sin umbral fijado). Hay 157 clases `slate-*` fijas en unos 60 archivos |
| `item_relations` (relaciones entre categorías) | Después de la Fase 4 |

## 5. Resultados de la revisión que dieron forma al plan

El plan se contrastó con tres revisiones independientes.
- **APIs reales y sus términos:** ver [../integraciones/fuentes-externas.md](../integraciones/fuentes-externas.md).
- **Auditoría del código y del VPS:** de ahí salen la corrección del truncado de notas, la falta de tests y de scheduler, y que LibreTranslate no cabe.
- **Crítica de producto:** arranque sin usuarios, competencia, alcance, DSA, RGPD y SEO.

El plan original con 6 categorías, traducciones, Elo y reputación se estimó en **20–30 semanas** y requería hardware que no hay. Este plan revisado se estima en **10–12**.

## 6. Métricas para decidir los siguientes pasos

Para decidir cuándo activar las funciones aplazadas sin instalar analítica de terceros, se calculan directamente sobre la base de datos con un comando (Fase 2):
- usuarios activos a la semana (con al menos un voto o comentario);
- votos al día y su reparto por tipo (`vote`, `want`, `not_interested`);
- porcentaje de ítems con al menos 5 valoraciones;
- listas creadas y listas con al menos un "me gusta".
