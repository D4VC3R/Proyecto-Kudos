# Fuentes externas

Verificado el 26-10-2026 con la documentación y los términos oficiales. **Antes de integrar una fuente, vuelve a comprobar sus términos.** Si han cambiado, para y avisa al humano.

## 1. Reglas (OBLIGATORIAS)

1. Solo planes gratuitos para uso no comercial: sin pruebas que caduquen ni tarjeta.
2. Nunca se llama a una API desde el frontend ni al servir una página; solo desde jobs y comandos (importar, refrescar, buscar bajo demanda).
3. User-Agent identificativo en todas las peticiones (`config('catalog.user_agent')`). Sin él, Wikimedia baja a 10 peticiones por minuto.
4. Limitación de peticiones por proveedor (`RateLimiter` de Redis o el middleware de job `RateLimited`), siempre por debajo del límite publicado.
5. Reintentos con *backoff* ante 429 y 5xx. **Un 404 no se reintenta.**
6. **TMDB:**
   - nada se guarda más de 6 meses sin refrescar (incluidas las imágenes);
   - nunca se guarda el payload en bruto;
   - si se revoca la clave, se borran sus datos.
7. **Prohibido** usar portadas de iTunes/Apple.
8. Atribución visible según [../legal/cumplimiento.md](../legal/cumplimiento.md) §1.

## 2. Fuentes aprobadas

| Fuente | Uso | Acceso | Límites | Condiciones | Español | Fase |
|---|---|---|---|---|---|---|
| **TMDB** | Películas y series: ficha, imágenes, géneros, novedades | Gratis no comercial; `TMDB_API_TOKEN` (v4) | ~40 peticiones/s ([docs](https://developer.themoviedb.org/docs/rate-limiting)) | Máx. 6 meses almacenado; logo y aviso de no respaldo ([términos](https://www.themoviedb.org/api-terms-of-use)) | Excelente con `language=es-ES` | 1 |
| **Wikidata** | Cruce de IDs, etiquetas es, P625, P18, enlace a eswiki | Libre | SPARQL: 60 s por consulta, 5 en paralelo por IP ([manual](https://www.mediawiki.org/wiki/Wikidata_Query_Service/User_Manual)) | CC0 | La etiqueta `es` suele faltar en lo reciente: resuelve `es` → `mul` → `en` | 1 |
| **Wikipedia ES** | Descripción en español | Libre | 200 peticiones/min con User-Agent ([límites](https://www.mediawiki.org/wiki/Wikimedia_APIs/Rate_limits)) | CC BY-SA: atribución y enlace por ítem | Muy buena donde hay entrada | 1 |
| **Wikimedia Commons** | Fotos de lugares | Libre | Como Wikipedia | Licencia y autor **por archivo**, guardados y visibles | — | 4 |
| **IGDB** (preferida) | Videojuegos | Gratis no comercial con Twitch (`IGDB_CLIENT_ID`/`SECRET`) | 4 peticiones/s, 8 abiertas | Términos de caché **sin verificar** (F4-00) | Inglés: el texto sale de Wikipedia ES | 4 |
| **RAWG** (alternativa) | Videojuegos | Clave gratuita "non-commercial projects only" | 20.000 peticiones/mes | Enlace a RAWG en cada página con sus datos; prohíbe redistribuir; almacenamiento en zona gris ([tos](https://rawg.io/tos_api)) | Inglés | 4 |
| **Tiles de OSM** | Mapa de destinos (Leaflet) | Libre | Sin uso masivo ni descarga offline | "© OpenStreetMap contributors" ([política](https://operations.osmfoundation.org/policies/tiles/)) | — | 4 |

## 3. Descartadas

| Fuente | Motivo |
|---|---|
| iTunes / Apple | Su arte solo puede usarse para promocionar la tienda. Las portadas de álbumes del snapshot actual lo incumplen y se eliminan con el reset de la F1 |
| RestCountries | La v3.1 está deprecada; la v5 pide clave (500 peticiones al mes). Los países salen de Wikidata |
| Nominatim | 4 peticiones/min para scripts e innecesario: las coordenadas salen de P625 |
| LibreTranslate en el servidor | Necesita 2–4 GB de RAM |
| Jikan | *Scraper* no oficial. Si vuelve el anime, AniList (30 peticiones/min) |
| Google Books / Open Library | Solo si vuelven los libros. Open Library prohíbe la descarga masiva por API; Google Books tiene una cuota no publicada |
| Commons para portadas | Commons borra las portadas con copyright; solo sirve para lugares y dominio público |

## 4. Endpoints

**TMDB** (`https://api.themoviedb.org/3`, `Authorization: Bearer {TMDB_API_TOKEN}`):
- Detalle: `GET /movie/{id}` y `GET /tv/{id}`, con `?language=es-ES&append_to_response=external_ids,credits` (las series solo necesitan `external_ids`: creadores y cadenas vienen en el propio detalle). `external_ids.wikidata_id` da el QID.
- Novedades y populares: `/movie/now_playing`, `/movie/upcoming`, `/movie/popular`, `/tv/on_the_air` y `/tv/popular`, con `language=es-ES&region=ES`.
- Búsqueda: `/search/movie` y `/search/tv`, con `?query=…&language=es-ES`.
- Géneros: `/genre/movie/list` y `/genre/tv/list`, con `?language=es-ES`.
- Imágenes: `https://image.tmdb.org/t/p/w780{poster_path}` y `/w1280{backdrop_path}`.
- Si `overview` es-ES está vacío, se usa la cadena de la descripción. **Nunca** se guarda la sinopsis en inglés.

**Wikidata:**
- `https://www.wikidata.org/wiki/Special:EntityData/{QID}.json`, o `wbgetentities` con `ids={QID}&props=labels|descriptions|sitelinks|claims&languages=es|mul|en`.
- Enlace a eswiki: usa **siempre** `sitelinks.eswiki.title`; nunca lo adivines (`Dredge_(videojuego)` da 404, la entrada real es `Dredge`).

| Propiedad | Significado |
|---|---|
| P4947 / P4983 | ID de TMDB (película / serie) |
| P5794 | IGDB (*slug*) |
| P9968 | RAWG |
| P1733 | Steam |
| P625 | Coordenadas |
| P18 | Imagen |
| P17 | País |
| P1082 | Población |
| P31 | Instancia de |
| P131 | Situado en |
| P144 | Basado en |

**Wikipedia ES:**
- Principal: `https://es.wikipedia.org/api/rest_v1/page/summary/{título}` → `extract` y `content_urls.desktop.page`.
- Respaldo (RESTBase se está deprecando): `https://es.wikipedia.org/w/api.php?action=query&prop=extracts&exintro=1&explaintext=1&redirects=1&format=json&titles={título}`.
- Encapsula los dos en `WikipediaClient`.
- En los municipios, la miniatura del resumen suele ser la **bandera**: la foto se toma de P18.

## 5. Cobertura esperada en español

Estimación a partir de unas 12 muestras; no está medida.

| Categoría | Populares | Cola larga |
|---|---|---|
| Películas y series (TMDB es-ES) | ~90 % | ~60 % |
| Videojuegos (vía Wikipedia ES) | 70–85 % | < 15 % |
| Destinos: ciudades del mundo / municipios españoles | ~90 % / ~100 % | — |
| Libros y álbumes (aplazados) | 40–60 % | < 10 % |

Lo que no tiene descripción en español se importa igualmente, pero con un `quality_score` bajo, y queda fuera de la portada y de la cola.

## 6. Pendiente de verificar

- Términos de caché de IGDB (F4-00).
- Si vuelven los libros o el anime: la cuota de Google Books y los términos de AniList.
