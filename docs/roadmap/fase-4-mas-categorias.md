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

---

## F4-03 · Ficha de destinos con mapa

**Depende:** F4-02

**Pasos:**
1. `leaflet` y `react-leaflet`, con versiones exactas compatibles con React 18.
2. `PlaceMap`, cargado con `React.lazy` solo si existe `attributes.coordinates`. Tiles de OSM (`https://tile.openstreetmap.org/{z}/{x}/{y}.png`) con la atribución por defecto visible, sin geocodificación ni Nominatim.
3. `AttributeChips` muestra la población con `toLocaleString('es-ES')`.

**Hecho cuando:** el mapa sale en las fichas de destinos, no en las de otras categorías, y Leaflet va en un *chunk* separado.

---

## F4-04 · Atribuciones de las fuentes nuevas

**Depende:** F4-01, F4-02

**Pasos:**
- Pie de foto en la ficha: "Foto: {autor}, {licencia}, vía Wikimedia Commons", con enlaces.
- IGDB: atribución visible y en un lugar fijo (la FAQ pide "fair attribution", visible y no en un registro de cambios). Basta con el `data_source` de la ficha y una mención en el pie o en "Acerca de".
- Steam: "Descripción: Steam", con enlace a la página de la tienda, en cada ficha con `description_source = steam`. Sin `nofollow` en los enlaces a Valve (términos de la Steam Web API). No se usa el logo de Steam ni se sugiere respaldo de Valve.
- Actualiza [../legal/cumplimiento.md](../legal/cumplimiento.md) con la decisión de la F4-00.

**Hecho cuando:** las atribuciones se ven en escritorio y en móvil y están revisadas contra los términos.
