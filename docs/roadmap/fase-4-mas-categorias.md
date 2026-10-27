# Fase 4: más categorías (Videojuegos y Destinos)

**Objetivo:** activar las categorías `videojuegos` y `destinos` reutilizando el pipeline de la Fase 1.

**Lecturas comunes:**
- [../integraciones/fuentes-externas.md](../integraciones/fuentes-externas.md)
- [../integraciones/pipeline-catalogo.md](../integraciones/pipeline-catalogo.md)
- [../legal/cumplimiento.md](../legal/cumplimiento.md)

---

## F4-00 · Elegir proveedor de videojuegos

- **Responsabilidad:** LEGAL, HUMANO
- **Depende de:** —

**Pasos:**
1. Revisar los términos **actuales** de IGDB (https://api-docs.igdb.com, y los términos de desarrollador de Twitch), sobre todo el almacenamiento y caché local de datos e imágenes, y la atribución.
2. Compararlos con RAWG (https://rawg.io/tos_api), que obliga a enlazar a RAWG en cada página y es una zona gris para el almacenamiento.
3. Anotar aquí la decisión y el motivo. **La toma el humano.**

**Recomendación por defecto:** IGDB, si permite cachear.

**Decisión:** _pendiente_

---

## F4-01 · Proveedor de videojuegos

- **Responsabilidad:** INT
- **Depende de:** F4-00, F1-05

**Pasos (versión IGDB):**
1. **`IgdbClient`:**
   - token de aplicación de Twitch (`client_credentials`), cacheado hasta que caduque;
   - `POST https://api.igdb.com/v4/games` con consultas Apicalypse;
   - límite de 4 peticiones por segundo.
2. **`IgdbGameProvider`:**
   - campos: `name`, `first_release_date`, `cover.image_id`, `screenshots`, `genres.name`, `platforms.name`, `involved_companies` (desarrolladora), `total_rating_count` (para `popularity`) y `slug`;
   - `external_id`: el ID numérico.
3. **Wikidata:** se busca el QID por la propiedad P5794 (el valor es el *slug* de IGDB) con SPARQL o `haswbstatement`. Así se obtiene el texto de la Wikipedia ES con el `DescriptionResolver` existente.
4. **Géneros y plataformas en español:** mapa manual en `config/catalog.php` → `genre_translations.igdb`. Si una clave no está en el mapa, se deja el nombre original.
5. **Imágenes:** `https://images.igdb.com/igdb/image/upload/t_cover_big/{image_id}.jpg` (póster) y `t_screenshot_big` (banner).
6. **`fetchNew`:** lanzamientos de los últimos 90 días y los próximos 90, más los más populares.
7. **`attribute_schema` de `videojuegos`:** `year`, `genres`, `platforms`, `developers`.
8. **Activar la categoría** (`is_active = true`) cuando la importación funcione.

**Si se elige RAWG,** se cambian el cliente y el mapeo (la propiedad de Wikidata es P9968) y se añade el enlace obligatorio en la interfaz (F4-04).

**Criterios de aceptación:**
- Tests con fixtures (incluidos un juego sin entrada en la Wikipedia en español y un 404).
- Verificación manual con credenciales reales, que pone el humano: porcentaje de juegos importados con descripción en español.

---

## F4-02 · Proveedor de Destinos

- **Responsabilidad:** INT
- **Depende de:** F1-05

**Alcance inicial:** todos los estados soberanos, las ciudades de más de 300.000 habitantes y las capitales de provincia españolas. Solo entran las que tengan *sitelink* `eswiki` y foto (P18).

**Pasos:**
1. **`WikidataPlaceProvider`:**
   - el catálogo se obtiene con consultas SPARQL **paginadas por país**, para respetar el límite de 60 s por consulta;
   - `external_id` es el QID y `provider` es `wikidata`. Aquí el proveedor principal es el propio Wikidata.
2. **`attributes`:**
   - `kind` (`país` / `ciudad`, a partir de P31);
   - `country` (P17, la etiqueta en español);
   - `population` (P1082);
   - `coordinates` (P625, como `{lat, lng}`).
3. **Foto:**
   - P18 → `https://commons.wikimedia.org/wiki/Special:FilePath/{archivo}?width=1280`;
   - licencia y autor desde la API de Commons (`action=query&prop=imageinfo&iiprop=extmetadata`, campos `LicenseShortName`, `Artist` y `DescriptionUrl`). Se guardan en `images[].license`, `author` y `source_url`;
   - el campo `Artist` puede traer HTML: se limpia.
4. **Descripción:** Wikipedia ES (el *resolver* existente).
5. **Sin novedades:** `fetchNew` solo se usa para la carga inicial y para ampliarla. El refresco es trimestral, y Wikidata no tiene límite de antigüedad.
6. **`attribute_schema` de `destinos`:** `kind`, `country`, `population` y `coordinates` (`show_in_card: false`).
7. **Activar la categoría.**

**Criterios de aceptación:**
- Tests con fixtures SPARQL y de Commons.
- Ninguna foto se queda sin licencia ni autor (si faltan, la foto no se guarda).

---

## F4-03 · Ficha de destinos con mapa

- **Responsabilidad:** FE
- **Depende de:** F4-02

**Pasos:**
1. **Dependencias:** `leaflet` y `react-leaflet`, con versiones exactas compatibles con React 18.
2. **Componente `PlaceMap`:**
   - se carga en diferido (`React.lazy`) solo si `attributes.coordinates` existe;
   - tiles de OSM `https://tile.openstreetmap.org/{z}/{x}/{y}.png` con la atribución por defecto visible;
   - sin geocodificación ni Nominatim.
3. **`AttributeChips`:** muestra la población formateada (`toLocaleString('es-ES')`).

**Criterios de aceptación:**
- El mapa aparece en las fichas de destinos y no se carga en las demás categorías: el *chunk* de Leaflet está separado.
- `pnpm build` termina sin errores.

---

## F4-04 · Atribuciones de las nuevas fuentes

- **Responsabilidad:** FE, LEGAL
- **Depende de:** F4-01, F4-02

**Pasos:**
- **Pie de foto en la ficha:** "Foto: {autor}, {licencia}, vía Wikimedia Commons", con enlaces.
- **Atribución del proveedor de juegos:** la que exija el elegido. Con RAWG, un enlace en **cada página** que muestre sus datos, también en listados y tarjetas.
- **Actualizar** [../legal/cumplimiento.md](../legal/cumplimiento.md) con la decisión de la F4-00.

**Criterios de aceptación:** las atribuciones son visibles en escritorio y en móvil y están revisadas contra los términos.
