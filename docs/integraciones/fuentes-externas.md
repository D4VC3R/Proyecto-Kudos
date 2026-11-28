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
| iTunes / Apple | Su arte solo puede usarse para promocionar la tienda. Las portadas de álbumes del antiguo volcado lo incumplían; el volcado se borró en la F5-08 |
| RestCountries | La v3.1 está deprecada; la v5 pide clave (500 peticiones al mes). Los países salen de Wikidata |
| Nominatim | 4 peticiones/min para scripts e innecesario: las coordenadas salen de P625 |
| LibreTranslate en el servidor | Necesita 2–4 GB de RAM |
| Jikan | *Scraper* no oficial. Si vuelve el anime, AniList (30 peticiones/min) |
| Google Books / Open Library | Solo si vuelven los libros. Open Library prohíbe la descarga masiva por API; Google Books tiene una cuota no publicada |
| Commons para portadas | Commons borra las portadas con copyright; solo sirve para lugares y dominio público |
| RAWG | Solo en inglés y sin nada que no tenga IGDB; exige un enlace en **cada página** con sus datos y prohíbe la redistribución y la publicación sin consentimiento escrito (§4.3 y §6.2 de sus [términos](https://rawg.io/tos_api)). Descartada en la F4-00 |
| Steam como fuente principal de videojuegos | Sin exclusivos de consola (Nintendo), `platforms` solo de PC, sin listado limpio de novedades y endpoint no documentado. Solo se usa para la descripción (F4-00) |
| Portadas de categoría (carteles y capturas de Amazon/IMDb, Empire, IGN) | Sin licencia para redistribuirlas. Portadas de categoría: ninguna (D1, F5-08); el frontend pinta el icono |

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
- Imágenes: `https://images.igdb.com/igdb/image/upload/t_1080p/{image_id}.jpg` para la portada (~810×1080) y para la captura (1920×1080). Se pide `t_1080p` porque la columna del póster de la ficha (360 px) necesita ≥ 540 px y 720 a densidad 2: `t_cover_big` (264) se veía pixelada y `t_cover_big_2x` (528) o `t_720p` (540) no llegan. `MediaManager` reduce después a `thumb` (400) y `banner` (1280) y nunca amplía.

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
| P36 | Capital |
| P576 | Fecha de disolución |
| P18 | Imagen |
| P17 | País |
| P1082 | Población |
| P31 | Instancia de |
| P131 | Situado en |
| P144 | Basado en |

**Destinos (Wikidata y Commons, F4-02):**
- Alcance: estados soberanos, ciudades de más de 300.000 habitantes y capitales de provincia españolas, todos con *sitelink* `eswiki` y foto (P18).
- Tres consultas SPARQL (`https://query.wikidata.org/sparql`, límite de 60 s por consulta, de ahí que se pida por país):
  - **países:** todos los estados soberanos vigentes (`?item wdt:P31 wd:Q3624078`, sin `P31 wd:Q3024240` (país histórico) ni `P576` (disuelto)), con los indicadores `hasImage` (P18) y `hasEswiki`, y la población (P1082) opcional, ordenados por población. El país entra solo con los dos, pero sus ciudades se piden siempre (32 de los 197 estados no tienen P18); a Países Bajos (Q29999) y Dinamarca (Q756617) se añaden los alias Q55 y Q35, que son los P17 de sus ciudades;
  - **ciudades de un país:** `?item wdt:P1082` con `hint:Prior hint:rangeSafe true` y `FILTER(?population > 300000.0)` **primero**, y después `?item wdt:P17 wd:{país}`; `P31/P279*` de ciudad (Q515) o municipio (Q15284), sin `P576`, con P18 y eswiki. Va con `hint:Query hint:optimizer "None"` (el orden es deliberado) y la agregación en una subconsulta. Empezar por `wdt:P17` agota los 60 s en EE. UU. o Alemania (millones de ítems), y el umbral debe ser decimal: P1082 es `xsd:decimal` y con un entero el rango no usa el índice (Andorra, sin ciudades, tardaba 25 s; con el decimal, 1,4 s). En Wikidata muchas ciudades (Madrid, Valencia…) son "municipio" y no "ciudad";
  - **capitales de provincia españolas:** `?province wdt:P31 wd:Q162620` y `wdt:P36 ?item`, con `?item wdt:P17 wd:Q29` (la clase incluye provincias históricas: La Habana, Santa Fe de Nuevo México…), P18 y eswiki.
- `fetchNew`: la consulta de países (una sola); España primero (el país, sus capitales de provincia y sus ciudades) y después el resto por población, con una consulta de ciudades por país, que solo se hace si el límite no se ha alcanzado. Tras cada consulta de ciudades se espera lo que ha tardado (WDQS da 60 s de proceso por minuto y cliente; sin pausa llega un 429). Si falla la consulta de un país se deja un aviso y se sigue con el siguiente; las que han fallado se repiten una vez al final, tras un minuto de pausa, y si vuelven a fallar queda un `Log::error` con los QID de los países (hay que volver a lanzar la carga).
- Ficha: `wbgetentities` con `labels|descriptions|sitelinks|claims` (P31, P17, P1082 con la declaración preferida o, si no, la de la fecha P585 más reciente, P625 y P18). La etiqueta del país (P17) se pide con `props=labels` y se cachea 7 días.
- **Commons** (`https://commons.wikimedia.org/w/api.php`, mismo límite compartido de Wikimedia y mismo User-Agent): `action=query&prop=imageinfo&iiprop=extmetadata|url&iiextmetadatafilter=LicenseShortName|Artist&titles=File:…&formatversion=2`. `LicenseShortName` es la licencia; `Artist` trae HTML (se limpia y se recorta a 250 caracteres); la página del archivo es `imageinfo[0].descriptionurl` (no es un campo de `extmetadata`). **Sin licencia o sin autor no se guarda la foto.**
- La foto se descarga de `https://commons.wikimedia.org/wiki/Special:FilePath/{archivo}?width=1280` (redirige a `upload.wikimedia.org`, que responde por HTTP/2 con las cabeceras en minúsculas y bloquea los User-Agent genéricos).

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
| Destinos: ciudades del mundo / municipios españoles | ~90 % / ~100 % (medido: 100 %, ver debajo) | — |
| Libros y álbumes (aplazados) | 40–60 % | < 10 % |

**Videojuegos, medido** (F4-01, 02-11-2026, `kudos_db` local, `catalog:fetch-new --category=videojuegos --limit=40`): **92,5 %** con descripción en español. Por fuente: `steam` en español 29 (72,5 %), `wikipedia_es` 4 (10 %), `wikidata` 4 (10 %), `steam` en inglés 0 y sin descripción 3 (7,5 %); 39 de 40 con QID y 31 con *appid* de Steam. Con ese límite los 40 salen del listado de novedades (lanzamientos recientes y próximos), no de los populares. Uno de los 29 de Steam ("Aniimo") anuncia español en `supported_languages` pero su texto corto está en inglés: es la limitación conocida de la F4-01.

**Destinos, medido** (F4-02, 02-11-2026, `kudos_db` local, `catalog:fetch-new --category=destinos --limit=60`): 62 destinos (3 países y 59 ciudades), todos con descripción de `wikipedia_es` (100 %: el alcance exige *sitelink* `eswiki`), coordenadas y foto de Commons con licencia y autor. La carga de 60 destinos (listado incluido) tardó unos 3 minutos. El listado completo (consulta de ciudades de los 197 estados soberanos más Q55 y Q35, con la consulta final, 02-11-2026): unas 200 consultas, con una mediana de 2,7 s, un p90 de 8,5 s, un p99 de 17,3 s y picos de hasta 40 s por la carga de WDQS (China 9,6–12,5 s, EE. UU. 4–5 s, España 1,6–2,4 s); con la pausa tras cada consulta dura unos 25 minutos y da unos 1.920 ciudades. Se estiman unos 2.100 destinos (165 países, unas 1.920 ciudades y las capitales de provincia que no son ya ciudad).

Lo que no tiene descripción en español se importa igualmente, pero su `quality_score` nunca llega al umbral (`catalog.min_quality_for_discovery`), tenga imagen o atributos, así que queda fuera de la portada y de la cola (F1-04).

## 6. Pendiente de verificar

- Si vuelven los libros o el anime: la cuota de Google Books y los términos de AniList.
