# Fuentes externas

Verificado el 26-10-2026 con la documentación y los términos oficiales (IGDB, RAWG y Steam, el 02-11-2026). **Antes de integrar una fuente, vuelve a comprobar sus términos.** Si han cambiado, para y avisa al humano.

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
8. **Nunca** se guarda una sinopsis en inglés de TMDB o IGDB. Única excepción: la descripción de Steam en inglés, como **último recurso** de la cadena de videojuegos, después de Wikipedia ES y Wikidata (F4-00). En Steam, `l=spanish` devuelve el inglés sin avisar cuando no hay traducción: se detecta con `supported_languages` (§4).
9. Atribución visible según [../legal/cumplimiento.md](../legal/cumplimiento.md) §1.

## 2. Fuentes aprobadas

| Fuente | Uso | Acceso | Límites | Condiciones | Español | Fase |
|---|---|---|---|---|---|---|
| **TMDB** | Películas y series: ficha, imágenes, géneros, novedades | Gratis no comercial; `TMDB_API_TOKEN` (v4) | ~40 peticiones/s ([docs](https://developer.themoviedb.org/docs/rate-limiting)) | Máx. 6 meses almacenado; logo y aviso de no respaldo ([términos](https://www.themoviedb.org/api-terms-of-use)) | Excelente con `language=es-ES` | 1 |
| **Wikidata** | Cruce de IDs, etiquetas es, P625, P18, enlace a eswiki | Libre | SPARQL: 60 s por consulta, 5 en paralelo por IP ([manual](https://www.mediawiki.org/wiki/Wikidata_Query_Service/User_Manual)) | CC0 | La etiqueta `es` suele faltar en lo reciente: resuelve `es` → `mul` → `en` | 1 |
| **Wikipedia ES** | Descripción en español | Libre | 200 peticiones/min con User-Agent ([límites](https://www.mediawiki.org/wiki/Wikimedia_APIs/Rate_limits)) | CC BY-SA: atribución y enlace por ítem | Muy buena donde hay entrada | 1 |
| **Wikimedia Commons** | Fotos de lugares | Libre | Como Wikipedia | Licencia y autor **por archivo**, guardados y visibles | — | 4 |
| **IGDB** | Videojuegos: ficha, portada, capturas, plataformas, desarrolladoras, popularidad, novedades | Gratis no comercial con Twitch (`IGDB_CLIENT_ID`/`SECRET`) | 4 peticiones/s, 8 abiertas; usamos 3/s (la ventana del `RateLimiter` es fija y puede haber ráfagas) | Permite y prefiere cachear y almacenar; los datos se conservan si se acaba la relación; atribución visible y fija (FAQ de [api-docs.igdb.com](https://api-docs.igdb.com)) | Inglés: géneros con mapa propio; el texto sale de Steam o Wikipedia ES | 4 |
| **Steam Store** (`appdetails`) | Videojuegos: **solo** la descripción corta (en español; en inglés, como último recurso) | Libre, sin clave; endpoint **no documentado** | ~200 peticiones / 5 min por IP (dato de la comunidad); usamos 1 cada 2 s (150 cada 5 min, por debajo del límite; la ventana del `RateLimiter` es de segundos enteros) | Sin licencia explícita del texto (es del editor): solo `short_description`, atribuida y con enlace a la tienda; enlaces sin `nofollow` y sin sugerir respaldo de Valve ([términos de la Web API](https://steamcommunity.com/dev/apiterms)) | Bueno en los juegos traducidos; si no, devuelve inglés sin avisar | 4 |
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
| RAWG | Solo en inglés y sin nada que no tenga IGDB; exige un enlace en **cada página** con sus datos y prohíbe la redistribución y la publicación sin consentimiento escrito (§4.3 y §6.2 de sus [términos](https://rawg.io/tos_api)). Descartada en la F4-00 |
| Steam como fuente principal de videojuegos | Sin exclusivos de consola (Nintendo), `platforms` solo de PC, sin listado limpio de novedades y endpoint no documentado. Solo se usa para la descripción (F4-00) |

## 4. Endpoints

**TMDB** (`https://api.themoviedb.org/3`, `Authorization: Bearer {TMDB_API_TOKEN}`):
- Detalle: `GET /movie/{id}` y `GET /tv/{id}`, con `?language=es-ES&append_to_response=external_ids,credits` (las series solo necesitan `external_ids`: creadores y cadenas vienen en el propio detalle). `external_ids.wikidata_id` da el QID.
- Novedades y populares: `/movie/now_playing`, `/movie/upcoming`, `/movie/popular`, `/tv/on_the_air` y `/tv/popular`, con `language=es-ES&region=ES`.
- Búsqueda: `/search/movie` y `/search/tv`, con `?query=…&language=es-ES`.
- Géneros: `/genre/movie/list` y `/genre/tv/list`, con `?language=es-ES`.
- Imágenes: `https://image.tmdb.org/t/p/w780{poster_path}` y `/w1280{backdrop_path}`.
- Si `overview` es-ES está vacío, se usa la cadena de la descripción. **Nunca** se guarda la sinopsis en inglés.

**IGDB** (`POST https://api.igdb.com/v4/{endpoint}` con Apicalypse; cabeceras `Client-ID` y `Authorization: Bearer {token}`):
- Token: `POST https://id.twitch.tv/oauth2/token` con `client_id`, `client_secret` y `grant_type=client_credentials` en el **cuerpo** del formulario (nunca en la URL), cacheado hasta un minuto antes de que caduque; ante un 401 se renueva y se repite una vez.
- `games`: `fields name,slug,first_release_date,cover.image_id,screenshots.image_id,genres.name,platforms.name,involved_companies.company.name,involved_companies.developer,total_rating_count,external_games.external_game_source,external_games.uid;`.
- Steam en `external_games`: el `uid` de la entrada cuyo `external_game_source` es Steam, que es el ID **1** en `/external_game_sources` (el campo `category` está deprecado). Confirmado con la API real el 02-11-2026.
- Qué juegos entran: `game_type = (0,4,8,9,10)` (juego principal, expansión independiente, remake, remaster y juego ampliado; IDs de `/game_types`, confirmados con la API real el 02-11-2026) y `version_parent = null` (fuera DLC, mods, packs, ediciones y ports).
- `fetchNew`: dos listados intercalados por páginas de 50. Novedades: `first_release_date` entre hoy − 90 días y hoy + 90 días y `(hypes > 0 | total_rating_count > 0)`, `sort hypes desc`. Populares: `total_rating_count > 0`, `sort total_rating_count desc`.
- QID de Wikidata: Action API con `list=search&srsearch=haswbstatement:P5794={slug}` y, si no hay resultado único, `haswbstatement:P1733={appid}` (probado con Hades el 02-11-2026: ambas dan Q59756366).
- Imágenes: `https://images.igdb.com/igdb/image/upload/t_cover_big/{image_id}.jpg` y `t_screenshot_big`.

**Steam Store** (sin clave):
- `GET https://store.steampowered.com/api/appdetails?appids={appid}&l=spanish&cc=es` → `{"<appid>": {"success": true, "data": {…}}}`; `success: false` equivale a un 404. Un solo *appid* por petición.
- Solo se usa `data.short_description` (texto plano, puede traer entidades HTML). Una sola petición por juego: si `data.supported_languages` (HTML, con `l=spanish` en español) no contiene "Español", el texto está en inglés y solo sirve de último recurso.
- Ficha pública: `https://store.steampowered.com/app/{appid}/`. La URL de la descripción de respaldo en inglés lleva `?l=english` (la tienda en inglés, donde está ese texto).

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

Estimación a partir de unas 12 muestras; solo los videojuegos tienen una medición real (debajo de la tabla).

| Categoría | Populares | Cola larga |
|---|---|---|
| Películas y series (TMDB es-ES) | ~90 % | ~60 % |
| Videojuegos (Steam es + Wikipedia ES) | ~90 % | 40–60 % (solo Wikipedia: < 15 %) |
| Destinos: ciudades del mundo / municipios españoles | ~90 % / ~100 % | — |
| Libros y álbumes (aplazados) | 40–60 % | < 10 % |

**Videojuegos, medido** (F4-01, 02-11-2026, `kudos_db` local, `catalog:fetch-new --category=videojuegos --limit=40`): **92,5 %** con descripción en español. Por fuente: `steam` en español 29 (72,5 %), `wikipedia_es` 4 (10 %), `wikidata` 4 (10 %), `steam` en inglés 0 y sin descripción 3 (7,5 %); 39 de 40 con QID y 31 con *appid* de Steam. Con ese límite los 40 salen del listado de novedades (lanzamientos recientes y próximos), no de los populares. Uno de los 29 de Steam ("Aniimo") anuncia español en `supported_languages` pero su texto corto está en inglés: es la limitación conocida de la F4-01.

Lo que no tiene descripción en español se importa igualmente, pero su `quality_score` nunca llega al umbral (`catalog.min_quality_for_discovery`), tenga imagen o atributos, así que queda fuera de la portada y de la cola (F1-04).

## 6. Pendiente de verificar

- Si vuelven los libros o el anime: la cuota de Google Books y los términos de AniList.
