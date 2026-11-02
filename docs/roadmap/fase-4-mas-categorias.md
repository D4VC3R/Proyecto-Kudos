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

**Decisión (02-11-2026, del humano):** **IGDB** como proveedor principal, con la descripción en español en cascada: **Steam** (`short_description` en español) → Wikipedia ES → Wikidata → nada.

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
  - **Trampa:** si no hay texto en español, devuelve el inglés sin avisar (Cruelty Squad, Lethal Company). Solo se acepta si es distinta de la de `l=english`.
  - **Riesgo aceptado:** es texto promocional del editor, sin licencia explícita. Se mitiga con la descripción corta, atribuida y con enlace a la tienda.
  - Si el endpoint falla o cambia, la cascada pasa a Wikipedia ES y la importación sigue.
- **Prioridad Steam antes que Wikipedia:** decisión del humano, por coherencia con la sinopsis de TMDB en películas y series.

---

## F4-01 · Proveedor de videojuegos

**Depende:** F4-00, F1-05

**Pasos:**
1. `IgdbClient`: token de aplicación de Twitch (`client_credentials`) cacheado hasta que caduque; `POST https://api.igdb.com/v4/games` con Apicalypse; límite de 4 peticiones por segundo.
2. `IgdbGameProvider`: `name`, `first_release_date`, `cover.image_id`, `screenshots`, `genres.name`, `platforms.name`, `involved_companies` (desarrolladora), `total_rating_count` (→ `popularity`), `slug` y `external_games` (`external_game_source` y `uid`, para el *appid* de Steam). El `external_id` es el ID numérico. **Nunca** se guarda el `summary` en inglés.
3. `SteamStoreClient` (sin clave, `GET https://store.steampowered.com/api/appdetails?appids={appid}&l=spanish&cc=es`): *throttle* de 1 petición cada 1,5 s con `Clients/Concerns/ThrottlesRequests.php` (el límite conocido es de unas 200 cada 5 minutos por IP) y `RetryPolicy`. `{"<appid>": {"success": false}}` cuenta como 404.
4. Descripción, en `IgdbGameProvider::fetch()` y antes del `DescriptionResolver`: si el juego tiene *appid* de Steam, se pide `short_description` con `l=spanish` y con `l=english`. Solo se acepta si no está vacía y es **distinta** de la inglesa (si no, Steam ha devuelto el inglés). Se guarda con `description_source = steam` y `description_url` = `https://store.steampowered.com/app/{appid}/`. Un fallo de Steam se registra en el log y deja la descripción vacía, para que siga la cadena.
5. Si no hay descripción, el `DescriptionResolver` actual (sin cambios) sigue con Wikipedia ES y Wikidata. El QID se obtiene por P5794 (el valor es el *slug* de IGDB) y, si no hay, por P1733 (*appid* de Steam), con SPARQL o `haswbstatement`.
6. `item_sources`: IGDB es la fuente principal (`is_primary = true`) y, si hay *appid*, se añade `steam` (`external_id` = *appid*, `url` = la página de la tienda, `is_primary = false`).
7. Géneros y plataformas en español: mapa en `config/catalog.php` → `genre_translations.igdb`. Lo que no esté en el mapa conserva su nombre original.
8. Imágenes: `https://images.igdb.com/igdb/image/upload/t_cover_big/{image_id}.jpg` (póster) y `t_screenshot_big` (banner).
9. `fetchNew`: lanzamientos de los últimos 90 días y los próximos 90, más los más populares.
10. `attribute_schema` de `videojuegos`: `year`, `genres`, `platforms`, `developers`.
11. `ItemDetailResource::SOURCES`: añadir `igdb` y `steam`.
12. Activa la categoría cuando la importación funcione.

**Hecho cuando:**
- Hay tests con fixtures, incluidos un 404 de IGDB, un juego sin Wikipedia ES, y en Steam: descripción en español, descripción en inglés (se descarta), juego sin *appid*, `success: false` y fallo de red.
- Con las credenciales del humano, el resumen indica el porcentaje de juegos importados con descripción en español, desglosado por fuente (`steam`, `wikipedia_es`, `wikidata`, sin descripción).

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
