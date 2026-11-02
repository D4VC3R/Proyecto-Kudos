# Fase 4: más categorías (Videojuegos y Destinos)

**Objetivo:** activar `videojuegos` y `destinos` reutilizando el pipeline de la Fase 1.

**Lee para toda la fase:** [../integraciones/pipeline-catalogo.md](../integraciones/pipeline-catalogo.md), [../integraciones/fuentes-externas.md](../integraciones/fuentes-externas.md) §2 y §4, y [../legal/cumplimiento.md](../legal/cumplimiento.md) §1.

---

## F4-00 · Elegir el proveedor de videojuegos (LEGAL, HUMANO)

**Depende:** —

**Pasos:**
1. Revisa los términos **actuales** de IGDB (https://api-docs.igdb.com y los términos de desarrollador de Twitch): almacenamiento y caché de datos e imágenes, y atribución.
2. Compáralos con los de RAWG (https://rawg.io/tos_api): exige un enlace en cada página y el almacenamiento es una zona gris.
3. El humano decide. Por defecto, IGDB si permite cachear.

**Decisión (02-11-2026, del humano):** **IGDB** como proveedor principal, con la descripción en cascada: **Steam en español** (`short_description`) → Wikipedia ES → Wikidata → **Steam en inglés** → nada.

- **IGDB, verificado el 02-11-2026 en la FAQ de https://api-docs.igdb.com:**
  - "free for non-commercial usage under the terms of the Twitch Developer Service Agreement";
  - "Am I allowed to store/cache the data locally? Yes. In fact, we prefer if you store and serve the data to your end users";
  - si termina la relación, los datos se conservan;
  - la atribución solo se exige en la asociación comercial, pero se muestra igualmente (F4-04);
  - 4 peticiones/s y 8 abiertas; las imágenes retiradas duran 30 días más en su CDN.
- **Idioma:** IGDB solo tiene `summary` y `storyline` en inglés (`game_localizations` trae nombres y portadas regionales, no sinopsis). Por eso la descripción sale de otras fuentes y los géneros se traducen con un mapa.
- **RAWG, descartada:** mismo problema de idioma que IGDB, enlace obligatorio en **cada página** (también tarjetas y listados) y prohíbe usar el servicio "for the purposes of further distribution" (§4.3) y copiar o publicar contenido sin consentimiento escrito (§6.2).
- **Steam como fuente principal, descartada:**
  - deja fuera los exclusivos de Nintendo (Zelda, Mario, Pokémon, Animal Crossing…), que en España están entre lo más popular;
  - `platforms` solo distingue Windows, Mac y Linux;
  - no hay un listado limpio de novedades (`GetAppList` mezcla DLC, bandas sonoras y *shovelware*);
  - `appdetails` es un endpoint **no documentado**.
- **Steam solo para la descripción:** la `short_description` con `l=spanish` sale en buen español en los juegos traducidos (probado con Hades, Balatro, Vampire Survivors, RE4, TMNT y PEAK).
  - **Trampa:** si no hay texto en español, devuelve el inglés sin avisar (Cruelty Squad, Lethal Company). Con **una sola petición** se sabe por `supported_languages`: si no contiene "Español", el texto se toma como inglés.
  - **Inglés como último recurso (decisión del humano):** un texto de Steam en inglés no se descarta, pero solo se usa si Wikipedia ES y Wikidata no dan nada. Es preferible a dejar la ficha sin descripción.
  - **Riesgo aceptado:** es texto promocional del editor, sin licencia explícita. Se mitiga con la descripción corta, atribuida y con enlace a la tienda.
  - Si el endpoint falla o cambia, la cascada pasa a Wikipedia ES y la importación sigue.
- **Prioridad Steam antes que Wikipedia:** decisión del humano, por coherencia con la sinopsis de TMDB en películas y series.

---

## F4-01 · Proveedor de videojuegos

**Depende:** F4-00, F1-05

**Pasos:**
1. `IgdbClient`: token de aplicación de Twitch (`client_credentials`) cacheado hasta que caduque; `POST https://api.igdb.com/v4/games` con Apicalypse; límite de 4 peticiones por segundo.
2. `IgdbGameProvider`: `name`, `first_release_date`, `cover.image_id`, `screenshots`, `genres.name`, `platforms.name`, `involved_companies` (desarrolladora), `total_rating_count` (→ `popularity`), `slug` y `external_games` (`external_game_source` y `uid`, para el *appid* de Steam). El `external_id` es el ID numérico. **Nunca** se guarda el `summary` en inglés.
3. `SteamStoreClient` (sin clave, `GET https://store.steampowered.com/api/appdetails?appids={appid}&l=spanish&cc=es`, **una sola petición por juego**): *throttle* de 1 petición cada 1,5 s con `Clients/Concerns/ThrottlesRequests.php` (el límite conocido es de unas 200 cada 5 minutos por IP) y `RetryPolicy`. `{"<appid>": {"success": false}}` cuenta como 404.
4. Descripción, en `IgdbGameProvider::fetch()` y antes del `DescriptionResolver`: si el juego tiene *appid* de Steam, se pide `appdetails` con `l=spanish`. Si `short_description` no está vacía:
   - si `supported_languages` contiene "Español", es la descripción del proveedor (`description_source = steam`, `description_url` = `https://store.steampowered.com/app/{appid}/`);
   - si no, Steam ha devuelto el inglés: se guarda como **descripción de respaldo** en un campo nuevo de `CatalogItemData` (texto y URL), no como descripción.

   Un fallo de Steam se registra en el log y deja ambas vacías, para que siga la cadena. Limitación conocida: un juego traducido al español cuya ficha de la tienda siga en inglés se tomará como español.
5. Si no hay descripción, el `DescriptionResolver` sigue con Wikipedia ES y Wikidata, como ahora, y **añade un último paso**: antes de devolver `null`, usa la descripción de respaldo de Steam con `description_source = steam`. El QID se obtiene por P5794 (el valor es el *slug* de IGDB) y, si no hay, por P1733 (*appid* de Steam), con SPARQL o `haswbstatement`.
6. `item_sources`: IGDB es la fuente principal (`is_primary = true`) y, si hay *appid*, se añade `steam` (`external_id` = *appid*, `url` = la página de la tienda, `is_primary = false`).
7. Géneros y plataformas en español: mapa en `config/catalog.php` → `genre_translations.igdb`. Lo que no esté en el mapa conserva su nombre original.
8. Imágenes: `https://images.igdb.com/igdb/image/upload/t_cover_big/{image_id}.jpg` (póster) y `t_screenshot_big` (banner).
9. `fetchNew`: lanzamientos de los últimos 90 días y los próximos 90, más los más populares.
10. `attribute_schema` de `videojuegos`: `year`, `genres`, `platforms`, `developers`.
11. `ItemDetailResource::SOURCES`: añadir `igdb` y `steam`.
12. Activa la categoría cuando la importación funcione.

**Hecho cuando:**
- Hay tests con fixtures, incluidos un 404 de IGDB, un juego sin Wikipedia ES, y en Steam: descripción en español; descripción en inglés con Wikipedia ES (gana Wikipedia) y sin Wikipedia ni Wikidata (se usa el inglés); juego sin *appid*; `success: false` y fallo de red. Hay que comprobar con fixtures que se hace **una sola** petición a Steam por juego.
- Con las credenciales del humano, el resumen indica el porcentaje de juegos importados con descripción en español, desglosado por fuente (`steam` en español, `wikipedia_es`, `wikidata`, `steam` en inglés, sin descripción).

**Notas (F4-01):**
- **Archivos nuevos** (`Backend/`): `app/Catalog/Clients/IgdbClient.php`, `app/Catalog/Clients/SteamStoreClient.php`, `app/Catalog/Providers/IgdbGameProvider.php`, `database/migrations/2026_11_04_000000_activate_videojuegos_category.php`, las fixtures `tests/Fixtures/catalog/{igdb,steam}/…`, `wikidata/search-*.json`, `wikidata/Q60745929-*.json` y `wikipedia/summary-hades-videojuego.json`, y los tests `IgdbClientTest`, `SteamStoreClientTest`, `IgdbGameProviderTest`, `IgdbImportTest` y `ActivateVideojuegosCategoryMigrationTest`.
- **Archivos modificados:** `config/catalog.php` (categoría `videojuegos`, `providers.igdb`, `providers.steam`, `genre_translations` y `platform_translations`), `.env.example` y `.env.production.example` (`IGDB_CLIENT_ID` e `IGDB_CLIENT_SECRET` vacías), `CatalogItemData` (cuatro campos nuevos al final, con valor por defecto), `WikidataClient::findByStatement()`, `DescriptionResolver` (último paso con el respaldo y `Desarrollo`/`Plataformas` en la plantilla de Wikidata), `CatalogImporter` (fuentes secundarias), `ItemDetailResource::SOURCES` (`igdb` y `steam`) y `CategorySeeder` (`videojuegos` activa con su esquema). Documentación: `pipeline-catalogo.md`, `fuentes-externas.md`, `base-de-datos.md`, `entorno-local.md` y `produccion.md` (§5 y nueva §6).
- **Desviaciones de la ficha:**
  - **Límites:** Steam a 1 petición cada **2 s** (no 1,5 s) e IGDB a **3/s** (no 4/s). `ThrottlesRequests` solo admite ventanas de segundos enteros, 1 cada 1,5 s son justo 200 cada 5 min (el límite conocido) y la ventana fija permite ráfagas de 2× en la frontera.
  - **Plataformas:** el mapa de plataformas va en `platform_translations.igdb`, una clave hermana de `genre_translations.igdb`, para no mezclar mapas.
  - **QID:** se resuelve en `IgdbGameProvider::fetch()` (no en el `DescriptionResolver`) con `haswbstatement` de la Action API de Wikidata (no SPARQL): P5794 con el *slug* de IGDB y, si no, P1733 con el *appid*. El importador también necesita el QID (`items.wikidata_id`, fusión y fuente `wikidata`). La búsqueda se cachea 7 días, también "no existe".
  - **URL del respaldo en inglés:** `https://store.steampowered.com/app/{appid}/?l=english`, la tienda en inglés, donde está ese texto; así se distingue en la base del español sin una columna nueva.
  - **Qué juegos entran:** `game_type` 0, 4, 8, 9 y 10 y `version_parent = null`; las novedades exigen `(hypes > 0 | total_rating_count > 0)` para no importar *shovelware*.
  - **Activación:** además del seeder, una migración de datos activa `videojuegos` en las bases ya sembradas (producción la tenía inactiva y el panel no permite activarla).
  - **Tests existentes actualizados (R8, ninguno borrado):** `CategorySeederTest` (`videojuegos` ahora activa con su esquema y sale en `/api/categories`; el test se renombró a `test_videojuegos_activa_con_su_esquema_y_destinos_inactiva`), `DatabaseSeederTest` (con `--all` ahora también se importa `videojuegos`: fija las credenciales de prueba y falsea Twitch e IGDB; la lista de categorías incluye `videojuegos`), `CatalogProviderRegistryTest` (`categorySlugs()` incluye `videojuegos`) y `ImportOneCommandTest` (el proveedor desconocido pasa de `igdb`, que ya existe, a `rawg`). Se amplían `CatalogItemDataTest`, `DescriptionResolverTest`, `CatalogImporterTest`, `ItemCatalogApiTest` y `CatalogConfigTest`.
- **Limitaciones conocidas:**
  - un juego traducido cuya ficha de la tienda siga en inglés se toma como español;
  - la descripción de Steam en español se conserva si Steam deja de responder (regla `$keep` del importador: solo se borra la del propio proveedor, `igdb`);
  - la fuente `steam` no se borra si IGDB deja de listar el *appid*.
- **Verificación real (paso 20 del plan, 02-11-2026, solo `kudos_db` local).** El primer intento falló con `400 invalid client` porque el `IGDB_CLIENT_ID` de `Backend/.env` estaba truncado; el humano lo corrigió y el verificador repitió la verificación:
  - IDs confirmados con la API: `/external_game_sources` id 1 = "Steam"; `/game_types` 0 = Main Game, 4 = Standalone Expansion, 8 = Remake, 9 = Remaster y 10 = Expanded Game (los excluidos: 1 DLC, 2 Expansion, 3 Bundle, 5 Mod, 6 Episode, 7 Season, 11 Port, 12 Fork, 13 Pack / Addon y 14 Update).
  - `catalog:fetch-new --category=videojuegos --limit=40` (unos 100 s): 40 juegos, 39 con QID y 31 fuentes `steam`. Desglose: `steam` en español 29 (72,5 %), `wikipedia_es` 4 (10 %), `wikidata` 4 (10 %), `steam` en inglés 0 y sin descripción 3 (7,5 %): **92,5 % con descripción en español**. Un solo aviso en el log: `cirrussearch-too-busy-error` de Wikidata en una búsqueda por P5794 (el QID se encontró después por P1733).
  - Observaciones: con un límite de 40 o 50 (`CATALOG_IMPORT_LIMIT` en local) todos los juegos salen de la primera página del listado de novedades (50 filas), así que los populares solo entran con límites mayores (en producción, 300). "Aniimo" anuncia español en Steam pero su texto corto está en inglés (la limitación conocida). Un juego con *appid* (884020) recibe `success: false` de Steam con `cc=es` y queda sin descripción.
  - `/api/categories` (puerto 8095) devuelve `videojuegos` con `year`, `genres`, `platforms` y `developers`, y la ficha de un juego atribuye los datos a IGDB y la descripción a Steam.

---

## F4-02 · Proveedor de Destinos

**Depende:** F1-05

**Alcance inicial:** todos los estados soberanos, las ciudades de más de 300.000 habitantes y las capitales de provincia españolas. Solo entran las que tengan *sitelink* `eswiki` y foto (P18).

**Pasos:**
1. `WikidataPlaceProvider`: SPARQL **paginado por país** (límite de 60 s por consulta). `provider = wikidata` y `external_id = QID`; aquí Wikidata es el proveedor principal.
2. `attributes`:
   - `kind` (`país` / `ciudad`, a partir de P31);
   - `country` (P17, etiqueta es);
   - `population` (P1082);
   - `coordinates` (P625, como `{lat, lng}`).
3. Foto: P18 → `https://commons.wikimedia.org/wiki/Special:FilePath/{archivo}?width=1280`. Licencia y autor salen de la API de Commons (`action=query&prop=imageinfo&iiprop=extmetadata`: `LicenseShortName`, `Artist` y `DescriptionUrl`) y se guardan en `images[].license`, `author` y `source_url`. Limpia el HTML de `Artist`.
4. Descripción: Wikipedia ES con el *resolver* existente.
5. `fetchNew` solo sirve para la carga inicial y para ampliarla. El refresco es trimestral (Wikidata no tiene límite de antigüedad).
6. `attribute_schema` de `destinos`: `kind`, `country`, `population` y `coordinates` (`show_in_card: false`).
7. Activa la categoría.

**Hecho cuando:** hay tests con fixtures de SPARQL y Commons, y ninguna foto queda sin licencia ni autor (si faltan, no se guarda).

**Notas (F4-02):**
- **Archivos nuevos** (`Backend/`): `app/Catalog/Clients/CommonsClient.php`, `app/Catalog/Providers/WikidataPlaceProvider.php`, `database/migrations/2026_11_05_000000_activate_destinos_category.php`, las fixtures `tests/Fixtures/catalog/commons/imageinfo-*.json`, `wikidata/{sparql-*,labels-*,Q2807-*,Q29-espana}.json` y `wikipedia/summary-madrid.json`, y los tests `CommonsClientTest`, `WikidataPlaceProviderTest`, `WikidataPlaceImportTest` y `ActivateDestinosCategoryMigrationTest`.
- **Archivos modificados:** `config/catalog.php` (categoría `destinos` con `manual_fetch_new`, `on_demand` y `refresh_older_than_days`, y el bloque `places`), `WikidataClient` (`label()` y `labelOf()`), `CatalogImporter::syncSources()`, `CatalogProviderRegistry::supportsOnDemand()`, `ValidatesCatalogCategory`, `CategoryResource`, `FetchNewCommand`, `RefreshCommand`, `RemoteImageDownloader`, `CategorySeeder` y los tests existentes (ver más abajo). Sin variables de entorno nuevas (R9) y sin tocar el frontend.
- **Desviaciones de la ficha:**
  - **`DescriptionUrl`:** no es un campo de `extmetadata`; la página del archivo sale de `imageinfo[0].descriptionurl`, que se pide con `iiprop=extmetadata|url`.
  - **Fuera de `--all` y con el listado en el proceso:** `catalog:fetch-new --all` omite destinos (`manual_fetch_new`) y con `--category=destinos` el listado se hace con `dispatchSync` en el propio comando. El listado (una consulta SPARQL por país) tarda minutos y el worker de producción corta los jobs a los 60 s; solo las importaciones (`ImportCatalogItemJob`) van a la cola. La ficha dice que `fetchNew` solo sirve para la carga inicial y para ampliarla.
  - **Sin importación bajo demanda:** `/api/catalog/search` e `/import` responden 422 para destinos y `has_catalog_provider` es false (`supportsOnDemand()`, `on_demand = false`); así no sale en el selector de `/buscar` ni se puede importar una entidad cualquiera como "destino". El campo no cambia de forma (R7).
  - **Refresco a los 90 días:** `catalog.categories.{slug}.refresh_older_than_days` fija el umbral por categoría en `catalog:refresh` (destinos, 90; el resto sigue en 150). `--older-than` lo fija para todas.
  - **`CatalogImporter` con proveedor `wikidata`:** `syncSources()` siempre añadía la fuente `wikidata` como secundaria y borraba las `wikidata` con otro `external_id`. Con Wikidata de proveedor, el índice único `(provider, external_id)` hace que sea la misma fila que la principal (quedaba con `is_primary = false`) y, si el QID era de otro ítem, se borraba. Ahora no se toca la fuente de Wikidata cuando el proveedor es Wikidata. Test que fallaba antes del arreglo: `test_un_proveedor_wikidata_deja_su_fuente_como_unica_y_principal`.
  - **User-Agent en `RemoteImageDownloader`:** el HEAD y el GET de las imágenes ahora llevan el User-Agent del catálogo; Wikimedia devuelve 403 a los genéricos (comprobado con la API real).
  - **Cabeceras en minúsculas (hallazgo de la verificación real):** `upload.wikimedia.org` responde por HTTP/2 con `content-type` en minúsculas y `RemoteImageDownloader::assertImageHeaders()` buscaba `Content-Type`, así que rechazaba todas las fotos ("no es un formato de imagen permitido"). Ahora busca sin distinguir mayúsculas. Test que fallaba antes: `test_acepta_cabeceras_en_minusculas_como_las_de_http2`.
  - **Consultas SPARQL corregidas en la verificación real:** (1) las capitales de provincia incluían provincias históricas (La Habana, Santa Fe de Nuevo México, Chinchilla de Montearagón…): se exige `?item wdt:P17 wd:Q29`; (2) la consulta de ciudades con `FILTER EXISTS { P31/P279* Q515 }` agotaba los 60 s en España y Francia, y en Wikidata muchas ciudades (Madrid, Valencia, Sevilla…) son "municipio" y no "ciudad": ahora usa `P31/P279*` de Q515 **o** Q15284 (municipio), con `hint:Query hint:optimizer "None"` y la agregación en una subconsulta. Versión final (tras la verificación): primero `wdt:P1082` con `hint:Prior hint:rangeSafe true` y el umbral **decimal** (`> 300000.0`), y después `wdt:P17`; `P31/P279*` de Q515 o Q15284, sin P576, con P18 y `eswiki`. Dos causas: empezar por `wdt:P17` agotaba los 60 s en EE. UU. y Alemania (millones de ítems con ese país: 504 a los 65 s), y con el umbral entero el rango de `rangeSafe` no usa el índice (los valores de P1082 son `xsd:decimal`; Andorra tardaba 25 s con 0 filas, y con el decimal 1,4 s). Tiempos de la consulta final (02-11-2026):

    | País | Filas | Tiempo (verificador) | Tiempo (ejecutor) |
    |---|---|---|---|
    | Andorra (Q228) | 0 | 1,4 s | 1,1 s |
    | Francia (Q142) | 9 | 1,1 s | — |
    | Rusia (Q159) | 55 | 1,3 s | — |
    | India (Q668) | 117 | 2,0 s | — |
    | Japón (Q17) | 86 | 2,0 s | — |
    | Alemania (Q183) | 24 | 2,3 s | 4,7 s |
    | España (Q29) | 13 | 2,4 s | 1,6 s |
    | México (Q96) | 78 | 4,4 s | — |
    | Brasil (Q155) | 96 | 4,8 s | — |
    | Indonesia (Q252) | 45 | 5,1 s | — |
    | EE. UU. (Q30) | 301 | 5,4 s | 4,0 s |
    | China (Q148) | 323 | 9,6 s | 12,5 s |
    | Argentina (Q414) | 38 | — | 9,2 s |
    | Países Bajos (Q55) | 4 | — | 1,8 s |

    Listado completo, uno tras otro como lo haría el comando (verificador): 199 consultas (los 197 estados soberanos más Q55 y Q35), unos 13 minutos sin pausas; 194 respondieron 200, con 1.920 ciudades; mediana 2,7 s, p90 8,5 s, p99 17,3 s; picos de carga en países sin ciudades (Luxemburgo 40 s, Maldivas 26 s); 4 errores 502 instantáneos y 1 error 429 al final por la cuota de WDQS (de ahí la pausa). Alternativas medidas y descartadas: una consulta global (3,9 s, 14.126 filas) exige filtrar la clase en PHP porque `P31/P279*` sobre 18.000 candidatos agota los 60 s, y añadir `eswiki` también, así que haría falta `wbgetentities` por lotes; la consulta por país sigue la ficha ("paginado por país") y es un cambio de una línea.
  - **Todos los estados soberanos se recorren:** la consulta de países ya no exige P18 ni `eswiki`; devuelve `hasImage` y `hasEswiki`, y el país solo entra con los dos, pero sus ciudades se piden siempre. Antes se perdían las 126 ciudades de 32 estados sin P18 (Argentina, Colombia, Malasia, Noruega, Suiza…). Las ciudades de Países Bajos y Dinamarca se piden también con P17 = Q55 y Q35 (`CITY_COUNTRY_ALIASES`).
  - **Plan B y cuota de WDQS:** tras cada consulta de ciudades se espera lo mismo que ha tardado (WDQS da 60 s de proceso por minuto y cliente; sin la pausa llegó un 429). La consulta de un país que falla se repite una vez al final, tras un minuto de pausa. Si vuelve a fallar, queda un `Log::error` con los QID de los países: en producción, con `LOG_CHANNEL=stderr`, sale en la terminal del comando. Entonces hay que volver a lanzar `catalog:fetch-new --category=destinos`, que reimporta sin duplicar.
- **Criterios de la consulta:** países: P31 Q3624078 (estado soberano), sin P31 Q3024240 (país histórico) ni P576 (el país entra solo con P18 y eswiki; sus ciudades se piden siempre); ciudades: P17 = país, P1082 > 300 000, P31/P279* Q515 o Q15284, sin P576, con P18 y eswiki; capitales: P36 de las P31 Q162620 cuyo P17 es España, con P18 y eswiki. `kind` es `país` si P31 incluye Q3624078 o Q6256 y `ciudad` en el resto.
- **Popularidad = población** (P1082): los países encabezan "populares" y la cola. La población es la declaración preferida o, si no, la de la fecha P585 más reciente.
- **Orden de `fetchNew`:** España primero (el país, sus capitales de provincia y sus ciudades) y después el resto por población, cada uno con sus ciudades; sin repetir QIDs, respeta el límite sin consultas de más y, si una consulta falla, sigue con el siguiente país y la repite una vez al final (plan B).
- **Fotos:** solo se guardan con licencia y autor (`Artist` sin HTML, recortado a 250 caracteres); si Commons no los da, el destino se importa sin foto y queda un aviso. Si Commons o Wikidata fallan, la importación falla y se reintenta (no se pisan datos ni fotos). El frontend solo usa `images[0]`: basta una foto `poster`. `destinos` sigue sin portada de categoría (`image = null`).
- **Tests existentes adaptados (R8, ninguno borrado):** `CatalogProviderRegistryTest::test_lista_las_categorias_configuradas` (ahora incluye `destinos`), `CategorySeederTest` (`test_videojuegos_activa_con_su_esquema_y_destinos_inactiva` pasa a `test_videojuegos_y_destinos_activas_con_su_esquema`, y `test_las_categorias_inactivas_no_salen_en_la_api` desactiva `destinos` tras sembrar para seguir probando lo que dice su nombre) y `DatabaseSeederTest` (la lista de `/api/categories` incluye `destinos`; `--all` la omite).
- **Limitaciones conocidas:**
  - una ciudad con un valor antiguo de población por encima del umbral puede entrar;
  - un destino que deja de cumplir el alcance no se desactiva al refrescar (solo si la entidad desaparece);
  - una descripción `wikidata` se borra si en un refresco falla la consulta del *resolver* (regla `$keep`, porque la fuente coincide con la del proveedor);
  - el alcance de las capitales españolas solo cubre las provincias con P36, P18 y eswiki (en la verificación, unas 40 de 52; Zaragoza, Murcia y Palma entran por la consulta de ciudades), y la clase incluye todavía alguna provincia histórica con capital en España (Chinchilla de Montearagón);
  - la foto de P18 no siempre es una foto: la de España es un mapa de localización (SVG) y la de algún país puede ser una bandera;
  - cada consulta de ciudades tarda entre 1 y 10 s, con picos de hasta 40 s por la carga de WDQS. Si una falla dos veces, ese país se queda sin ciudades hasta que se vuelve a lanzar la carga: `catalog:refresh` no lista ítems nuevos;
  - 32 estados soberanos (Argentina, Colombia, Noruega, Suiza…) no entran como destino porque en Wikidata no tienen P18; sus ciudades sí entran. Somalilandia (no reconocida) queda fuera.
- **Verificación real (paso 16, 02-11-2026, solo `kudos_db` local, APIs sin clave):**
  - `sail artisan migrate` aplicó la migración (4 categorías activas) y `catalog:fetch-new --category=destinos --limit=40` (unos 2 min) importó 40 destinos; `catalog:import wikidata Q90` y `Q142` añadieron París y Francia. Tras corregir las consultas y el `RemoteImageDownloader`, se vació la categoría en `kudos_db` (solo había destinos de la verificación) y se repitió, y después con `--limit=60` (unos 3 min, incluida la consulta de ciudades de China).
  - Resultado final: 62 destinos (3 `país` y 59 `ciudad`), todos con `description_source = wikipedia_es` (100 %), con coordenadas y con foto de Commons; 0 imágenes sin `license` o `author`; cada ítem con una sola fuente, `wikidata`, `is_primary = true`; ninguno sin `wikidata_id`.
  - `GET /api/categories` (puerto 8095) incluye `destinos` con `attribute_schema` (`kind`, `country`, `population`, `coordinates`) y `has_catalog_provider: false`; `peliculas`, `series` y `videojuegos` siguen en true.
  - Antes de corregir `RemoteImageDownloader`, los 41 destinos se importaron **sin foto** (todos los `DownloadItemImagesJob` fallaban por la cabecera en minúsculas): lo que justifica el cambio.

---

## F4-03 · Ficha de destinos con mapa

**Depende:** F4-02

**Pasos:**
1. `leaflet` y `react-leaflet`, con versiones exactas compatibles con React 18.
2. `PlaceMap`, cargado con `React.lazy` solo si existe `attributes.coordinates`. Tiles de OSM (`https://tile.openstreetmap.org/{z}/{x}/{y}.png`) con la atribución por defecto visible, sin geocodificación ni Nominatim.
3. `AttributeChips` muestra la población con `toLocaleString('es-ES')`.

**Hecho cuando:** el mapa sale en las fichas de destinos, no en las de otras categorías, y Leaflet va en un *chunk* separado.

**Notas (F4-03):**
- **Archivos nuevos** (`Frontend/src/`): `lib/places.js` (`PLACE_MAP_ZOOM`, `getPlaceCoordinates`, `getPlaceMapZoom`), `components/items/PlaceMap.jsx` (Leaflet con tiles de OSM; el único archivo que importa Leaflet) y `components/items/ItemPlaceMap.jsx` (sección "Ubicación" con `React.lazy`, `Suspense` y una barrera de errores propia: si el chunk no carga, la ficha sigue sin el mapa).
- **Archivos modificados:** `package.json` y `pnpm-lock.yaml`, `lib/attributes.js` (`formatPopulation` y `KEY_FORMATTERS`) y `components/items/ItemDetailColumn.jsx` (el mapa va bajo la imagen, solo con coordenadas válidas). Sin tocar el backend ni `vite.config.js` (R7: el JSON no cambia).
- **Versiones exactas:** `leaflet` 1.9.4 y `react-leaflet` 4.2.1 (sin `^`). La 5 de `react-leaflet` exige React 19 y el proyecto usa React 18.3. Al regenerar el lockfile, pnpm también ha anotado `axios@1.16.1(debug@4.4.3)` (peer opcional de `follow-redirects`); no cambia ninguna versión.
- **Punto del mapa:** `CircleMarker` en lugar del marcador por defecto, porque este depende de imágenes de Leaflet que el *bundler* no resuelve.
- **`isolate`:** el contenedor del mapa lo lleva porque los *panes* y controles de Leaflet usan `z-index` de 400 a 1000 y taparían la cabecera (`z-50`) al hacer *scroll*.
- **Rueda del ratón:** `scrollWheelZoom` desactivado, para que siga desplazando la ficha; el zoom con `+`/`−` y el arrastre funcionan.
- **Zoom inicial:** 5 para `kind = país` y 11 para el resto (ciudades).
- **Población:** se formatea **por clave** (`KEY_FORMATTERS.population`), no para todo el tipo `integer`, porque `year`, `seasons` y `episodes` también lo son y un año saldría "2.024" según el navegador. Con `toLocaleString('es-ES')` los números de 4 cifras no llevan punto ("1234") y los de 5 o más sí ("3.506.730").
- **Atribución:** la del control por defecto de Leaflet, visible ("Leaflet | © OpenStreetMap contributors", con enlace a `openstreetmap.org/copyright`). Sin geocodificación ni Nominatim: las únicas peticiones son los tiles `tile.openstreetmap.org/{z}/{x}/{y}.png`.
- **Chunk de Leaflet (salida de `pnpm build`):** `dist/assets/PlaceMap-CljMU2Mf.js` 153,97 kB (45,12 kB gzip) y `PlaceMap-Dgihpmma.css` 15,04 kB (6,38 kB gzip), aparte de `index-BYdby8jW.js` (383,75 kB). Comprobado con `grep`: `tile.openstreetmap.org` y `_leaflet_id` solo están en `PlaceMap-*.js`, `index-*.js` no contiene ninguno y `leaflet-container` solo está en `PlaceMap-*.css`.
- **Prueba en el navegador** (build de producción con `vite preview`, Chrome 124 + `puppeteer-core`, escritorio 1440x900 y móvil 360x740, sin sesión, API local real de solo lectura): ciudad (Madrid) con mapa, "Ubicación", tiles a zoom 11, atribución visible con enlace y chip "Población" `3.506.730`; país con tiles a zoom 5; película sin mapa, sin `PlaceMap-*.js`, sin CSS de Leaflet y sin tiles; cabecera por encima del mapa al hacer *scroll*; sin `pageerror` ni desbordamiento horizontal. Los tiles se interceptaron y se respondieron con un PNG local: ninguna petición real a OSM (R4). Resultado: 0 fallos en ambos tamaños.
- **A validar por el humano (diseño menor):** título "Ubicación" bajo la imagen, círculo azul como punto y zoom 5/11.

---

## F4-04 · Atribuciones de las fuentes nuevas

**Depende:** F4-01, F4-02

**Pasos:**
- Pie de foto en la ficha: "Foto: {autor}, {licencia}, vía Wikimedia Commons", con enlaces.
- IGDB: atribución visible y en un lugar fijo (la FAQ pide "fair attribution", visible y no en un registro de cambios). Basta con el `data_source` de la ficha y una mención en el pie o en "Acerca de".
- Steam: "Descripción: Steam", con enlace a la página de la tienda, en cada ficha con `description_source = steam`. Sin `nofollow` en los enlaces a Valve (términos de la Steam Web API). No se usa el logo de Steam ni se sugiere respaldo de Valve.
- Actualiza [../legal/cumplimiento.md](../legal/cumplimiento.md) con la decisión de la F4-00.

**Hecho cuando:** las atribuciones se ven en escritorio y en móvil y están revisadas contra los términos.
