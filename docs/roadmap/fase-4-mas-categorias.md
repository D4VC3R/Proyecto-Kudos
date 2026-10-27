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

**Decisión:** _pendiente_

---

## F4-01 · Proveedor de videojuegos

**Depende:** F4-00, F1-05

**Pasos (IGDB):**
1. `IgdbClient`: token de aplicación de Twitch (`client_credentials`) cacheado hasta que caduque; `POST https://api.igdb.com/v4/games` con Apicalypse; límite de 4 peticiones por segundo.
2. `IgdbGameProvider`: `name`, `first_release_date`, `cover.image_id`, `screenshots`, `genres.name`, `platforms.name`, `involved_companies` (desarrolladora), `total_rating_count` (→ `popularity`) y `slug`. El `external_id` es el ID numérico.
3. QID de Wikidata por P5794 (el valor es el *slug* de IGDB), con SPARQL o `haswbstatement`, para obtener el texto de la Wikipedia ES con el `DescriptionResolver`.
4. Géneros y plataformas en español: mapa en `config/catalog.php` → `genre_translations.igdb`. Lo que no esté en el mapa conserva su nombre original.
5. Imágenes: `https://images.igdb.com/igdb/image/upload/t_cover_big/{image_id}.jpg` (póster) y `t_screenshot_big` (banner).
6. `fetchNew`: lanzamientos de los últimos 90 días y los próximos 90, más los más populares.
7. `attribute_schema` de `videojuegos`: `year`, `genres`, `platforms`, `developers`.
8. Activa la categoría cuando la importación funcione.

**Con RAWG:** cambian el cliente y el mapeo (en Wikidata, P9968) y se añade el enlace obligatorio (F4-04).

**Hecho cuando:**
- Hay tests con fixtures, incluidos un juego sin Wikipedia ES y un 404.
- Con las credenciales del humano, el resumen indica el porcentaje de juegos importados con descripción en español.

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
- Atribución del proveedor de juegos según sus términos. Con RAWG, un enlace en **cada página** que muestre sus datos, incluidas las tarjetas y los listados.
- Actualiza [../legal/cumplimiento.md](../legal/cumplimiento.md) con la decisión de la F4-00.

**Hecho cuando:** las atribuciones se ven en escritorio y en móvil y están revisadas contra los términos.
