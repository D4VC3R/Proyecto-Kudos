# Fuentes externas: qué APIs se usan y con qué condiciones

**Responsable:** integraciones.
- **Verificado** el 26-10-2026 contra la documentación y los términos oficiales.
- **Antes de integrar una fuente, vuelve a comprobar sus términos** (enlaces abajo). Si han cambiado, para y avisa al humano.

## 1. Reglas generales (OBLIGATORIO)

1. **Solo planes gratuitos para uso no comercial.** Nada de pruebas que caduquen ni de tarjeta de crédito.
2. **Nunca se llama a una API desde el frontend** ni al servir una página. Solo desde jobs y comandos del backend: importar, refrescar y buscar bajo demanda.
3. **User-Agent identificativo** en todas las peticiones, tomado de `config('catalog.user_agent')`. Es obligatorio en Wikimedia, MusicBrainz y OSM: sin él, Wikimedia limita a 10 peticiones por minuto.
4. **Limitación de peticiones por proveedor** (Redis `RateLimiter` o el middleware de job `RateLimited`), siempre por debajo del límite publicado.
5. **Reintentos con *backoff*** ante 429 y 5xx. Un 404 **no** se reintenta.
6. **Atribución visible** según la tabla de [../legal/cumplimiento.md](../legal/cumplimiento.md).

## 2. Fuentes aprobadas

| Fuente | Uso en Kudos | Acceso | Límites | Almacenamiento y atribución | Español | Fase |
|---|---|---|---|---|---|---|
| **TMDB** | Películas y series: ficha, imágenes, géneros, novedades | Gratis para uso no comercial; token v4 (`TMDB_API_TOKEN`) | ~40 peticiones/s ([docs](https://developer.themoviedb.org/docs/rate-limiting)) | **Prohibido guardar datos más de 6 meses**; borrar todo si termina la licencia. Logo de TMDB y aviso de no respaldo obligatorios ([términos](https://www.themoviedb.org/api-terms-of-use)) | Excelente con `language=es-ES` | 1 |
| **Wikidata** | Cruce de IDs, etiquetas en español, coordenadas (P625), foto (P18), enlace a la Wikipedia en español | Libre | SPARQL: 60 s por consulta, 5 consultas en paralelo por IP ([manual](https://www.mediawiki.org/wiki/Wikidata_Query_Service/User_Manual)) | CC0, sin restricciones | Etiqueta `es` frecuente, pero **a menudo falta en ítems recientes**: resolver como `es`, luego `mul`, luego `en` | 1 |
| **Wikipedia ES** | Descripción en español (resumen de la entrada) | Libre | 200 peticiones/min **con** User-Agent; 10/min sin él ([límites](https://www.mediawiki.org/wiki/Wikimedia_APIs/Rate_limits)) | **CC BY-SA**: atribución y enlace por ítem; las obras derivadas se comparten igual | Muy buena donde existe la entrada | 1 |
| **Wikimedia Commons** | Fotos de **lugares** (Destinos) | Libre | Como la Wikipedia | Licencia y autor **por archivo**, que hay que guardar y mostrar | — | 4 |
| **IGDB** (preferida para juegos) | Metadatos y portadas de videojuegos | Gratis para uso no comercial con una cuenta de Twitch (`IGDB_CLIENT_ID`/`SECRET`) | 4 peticiones/s, 8 abiertas a la vez | **Términos de caché no verificados** (la documentación devolvió 403): verificar antes de la F4 | Solo inglés, así que el texto sale de la Wikipedia ES | 4 |
| **RAWG** (alternativa) | Igual que IGDB | Clave gratuita "non-commercial projects only" | 20.000 peticiones/mes | **Enlace a RAWG en cada página que use sus datos.** Prohíbe redistribuir; el almacenamiento es zona gris ([tos](https://rawg.io/tos_api)) | Inglés | 4 |
| **OpenStreetMap tiles** | Mapa en la ficha de destinos (Leaflet) | Libre | Nada de uso masivo ni descarga offline | Atribución "© OpenStreetMap contributors" visible ([política](https://operations.osmfoundation.org/policies/tiles/)) | — | 4 |

## 3. Fuentes descartadas o restringidas

| Fuente | Motivo |
|---|---|
| **iTunes Search / Apple** | Su arte gráfico solo puede usarse para promocionar la tienda, junto a un *badge* oficial. **Las portadas de álbumes del snapshot actual lo incumplen**: se eliminan con el reset de la Fase 1 |
| **RestCountries** | La v3.1 está deprecada y devuelve error; la v5 necesita clave y da 500 peticiones al mes. Los países salen de Wikidata |
| **Nominatim** | Los scripts programados están limitados a 4 peticiones por minuto. Innecesario: las coordenadas salen de Wikidata P625 |
| **LibreTranslate** (en el servidor) | Necesita 2–4 GB de RAM y no cabe en el VPS. Si algún día se recuperan libros o álbumes, se ejecutaría en el PC del desarrollador por lotes |
| **Jikan** | Es un *scraper* no oficial de MyAnimeList. Si vuelve el anime, se usaría AniList, con un límite actual de 30 peticiones por minuto |
| **Google Books / Open Library** | Solo si se recuperan los libros. Open Library prohíbe la descarga masiva por API (hay que usar sus *dumps*). Google Books requiere clave y tiene una cuota diaria no publicada |
| **Commons para portadas** | Las portadas de obras con copyright no están en Commons, que las borra. Commons solo sirve para lugares y obras de dominio público |

## 4. Endpoints de referencia

### TMDB (base `https://api.themoviedb.org/3`, cabecera `Authorization: Bearer {TMDB_API_TOKEN}`)
- **Detalle:**
  - `GET /movie/{id}?language=es-ES&append_to_response=external_ids,credits`
  - `GET /tv/{id}?language=es-ES&append_to_response=external_ids,credits`
  - `external_ids.wikidata_id` da el QID directamente.
- **Novedades y populares:** `/movie/now_playing`, `/movie/upcoming`, `/movie/popular`, `/tv/on_the_air`, `/tv/popular`. Con `language=es-ES&region=ES`.
- **Búsqueda:** `/search/movie?query=…&language=es-ES` y `/search/tv?query=…&language=es-ES`.
- **Géneros en español:** `/genre/movie/list?language=es-ES` y `/genre/tv/list?language=es-ES`.
- **Imágenes:** `https://image.tmdb.org/t/p/w780{poster_path}` (póster) y `/w1280{backdrop_path}` (banner).
- **Si `overview` en es-ES está vacío:** se recurre a la cadena de prioridades. **No** se guarda la sinopsis en inglés.

### Wikidata
- **Entidad:** `https://www.wikidata.org/wiki/Special:EntityData/{QID}.json`, o `wbgetentities` con `ids={QID}&props=labels|descriptions|sitelinks|claims&languages=es|mul|en`.
- **Propiedades útiles:**

  | Propiedad | Significado |
  |---|---|
  | P4947 | ID de película en TMDB |
  | P4983 | ID de serie en TMDB |
  | P5794 | IGDB (el valor es el *slug*) |
  | P9968 | RAWG |
  | P1733 | Steam |
  | P625 | Coordenadas |
  | P18 | Imagen |
  | P17 | País |
  | P1082 | Población |
  | P31 | Instancia de |
  | P131 | Situado en |
  | P144 | Basado en |

- **Enlace a la Wikipedia en español:** `sitelinks.eswiki.title`. **Usa siempre este título; nunca lo adivines.** Por ejemplo, `Dredge_(videojuego)` da 404 y la entrada real es `Dredge`.

### Wikipedia ES
- **Principal:** `https://es.wikipedia.org/api/rest_v1/page/summary/{título}`. Devuelve `extract` y `content_urls.desktop.page`.
- **Respaldo** (RESTBase está en proceso de deprecación): `https://es.wikipedia.org/w/api.php?action=query&prop=extracts&exintro=1&explaintext=1&redirects=1&format=json&titles={título}`.
- **Encapsula los dos en un cliente** (`WikipediaClient`) para poder cambiar de uno a otro sin tocar el resto del código.
- **Ojo:** en los municipios, la miniatura del resumen suele ser la **bandera**. Para la foto usa Wikidata P18.

## 5. Cobertura esperada en español sin traducción automática

Estimación a partir de unas 12 muestras; no está medida.

| Categoría | Populares | Cola larga |
|---|---|---|
| Películas y series (TMDB es-ES) | ~90 % | ~60 % |
| Videojuegos (vía Wikipedia ES) | 70–85 % | < 15 % |
| Destinos: ciudades del mundo | ~90 % | — |
| Destinos: municipios españoles | ~100 % | — |
| Libros y álbumes (aplazados) | 40–60 % | < 10 % |

Los ítems sin descripción en español no se descartan, pero quedan con un `quality_score` bajo y **fuera de la portada y de la cola de votación**.

## 6. Pendiente de verificar antes de su fase

- Términos de IGDB sobre caché y almacenamiento (F4). Decidir entre IGDB y RAWG con esa información.
- Si se recuperan libros o el anime: la cuota de Google Books y los términos de AniList.
