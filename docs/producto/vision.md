# Visión de producto: Kudos 2.0

Es responsabilidad del humano. Los agentes lo leen para entender el porqué, pero **no lo modifican** sin permiso.

> **"Tu diario de pelis, series y juegos en español, y destinos que descubrir, con listas que lo conectan todo."**

## 1. Problema y enfoque

**Problema:**
- Votar es valorar ítems al azar, con una escala lenta.
- El ranking es una media simple, y los kudos premian votar a ciegas.
- El catálogo son 9 categorías importadas una sola vez, sin IDs externos y con muchas fichas sin imagen o con descripciones de plantilla.
- No hay búsqueda, *wishlist*, listas, recomendaciones ni una portada viva.

**Principios:**
1. **Menos categorías, pero vivas y completas.** Una ficha sin imagen ni descripción no sale en la portada ni en la cola.
2. **Cada interacción aporta.** "No lo conozco" pasa a ser "me interesa" (*wishlist*) o "no me interesa".
3. **Funciona con pocos usuarios.** Las recomendaciones salen del contenido (géneros, plataformas, país), de listas editoriales y de la popularidad externa (TMDB, IGDB), no de los votos propios.
4. **Diferencial:** todo en español, listas que mezclan categorías y, más adelante, relaciones entre categorías a partir de Wikidata. Las recomendaciones entre categorías **no** son únicas (TasteDive y Likewise ya las hacen): no se venden como tal.
5. **Coste cero y legalidad:** APIs gratuitas respetando sus términos; sin publicidad ni pagos.

## 2. Decisiones tomadas (26-10-2026)

| Tema | Decisión |
|---|---|
| Categorías vivas | Películas y Series (TMDB); Videojuegos (IGDB o RAWG + Wikipedia ES); Destinos = ciudades y países fusionados (Wikidata, Wikipedia ES, Commons, OSM) |
| Categorías que salen | Libros, álbumes y artistas: no se reimportan, pero sus snapshots siguen en `seed-data/`. Políticos se elimina (atrae toxicidad) |
| Datos actuales | Son de prueba y se pueden resetear: no se migran votos, kudos ni imágenes |
| Plazo | Sin fecha límite; las fases van en orden |
| Base de datos | Cambios mínimos: columnas nuevas, `item_sources` y ampliar `votes.type` ([../backend/base-de-datos.md](../backend/base-de-datos.md)) |
| Idioma | Solo español, sin tabla de traducciones ni traducción automática en el servidor |
| Escala | 5 niveles en la interfaz, guardados como 2/4/6/8/10 en `score` (que sigue siendo 0–10) |
| Ranking | Media bayesiana con `m` configurable y bajo |
| Cara a cara | Solo como ranking **personal**, nunca global, hasta que haya volumen |

## 3. Aplazado: no implementar sin orden del humano

| Función | Se activa cuando… |
|---|---|
| Filtrado colaborativo ítem-ítem | el ítem tiene ≥ 5 usuarios que también han valorado otros ítems en común |
| % de compatibilidad entre usuarios | los dos comparten ≥ 10 ítems valorados |
| Tendencias, "Polémicos", follows y feed | la actividad diaria lo justifique (con datos reales) |
| Elo global, reputación (`CheckMinimumKudos`), insignias, retos, trivia | sin umbral |
| Edición comunitaria tipo wiki | de momento, solo un botón "Sugerir corrección" que llega al admin |
| Libros, álbumes, anime | las tres categorías vivas funcionen bien (su cobertura en español es floja) |
| Modo oscuro | sin umbral ([../frontend/arquitectura.md](../frontend/arquitectura.md) §3) |
| `item_relations` | después de la Fase 4 |

## 4. Métricas (comando `kudos:metrics`, F2-11)

- Usuarios activos por semana (al menos un voto o comentario).
- Votos al día y su reparto por tipo (`vote`, `want`, `not_interested`).
- Porcentaje de ítems con al menos 5 valoraciones.
- Listas creadas y listas con al menos un "me gusta".
