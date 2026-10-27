# Backend: tests

## 1. Configuración

- PostgreSQL, base de datos `testing`, en el mismo servicio `pgsql` de Sail. **Nunca SQLite**: las migraciones usan `CREATE EXTENSION`, `jsonb` y GIN.
- `phpunit.xml` (lo deja así la F0-01):
  ```xml
  <env name="DB_CONNECTION" value="pgsql" force="true"/>
  <env name="DB_DATABASE" value="testing" force="true"/>
  <env name="CACHE_STORE" value="array"/>
  <env name="QUEUE_CONNECTION" value="sync"/>
  <env name="MAIL_MAILER" value="array"/>
  <env name="SESSION_DRIVER" value="array"/>
  ```
  `DB_HOST`, `DB_USERNAME` y `DB_PASSWORD` se heredan de `.env` (`pgsql` / `sail` / `password`).
- Cómo crear la base de datos y ejecutar los tests: [../infra/entorno-local.md](../infra/entorno-local.md) §4.

## 2. Estructura

```
Backend/tests/
├── TestCase.php     # protección de la base de datos (§3)
├── Concerns/        # helpers: seedRolesAndPermissions, actingAsUser, actingAsAdmin
├── Feature/         # Votes/, Ranking/, Items/, Kudos/, Catalog/…
├── Unit/            # lógica pura: fórmulas, mapeadores, normalizadores
└── Fixtures/catalog/  # respuestas reales recortadas de TMDB, Wikidata y Wikipedia
```

## 3. `tests/TestCase.php` (obligatorio, R3)

`RefreshDatabase` se ejecuta **dentro** de `parent::setUp()`. Por eso la protección va en su hook `beforeRefreshingDatabase()`: si estuviera en `setUp()`, comprobaría cuando la base de datos ya se ha borrado.

```php
abstract class TestCase extends BaseTestCase
{
    /**
     * Protección: RefreshDatabase borra la base de datos. Nunca contra la de desarrollo o producción.
     */
    protected function beforeRefreshingDatabase(): void
    {
        $database = config('database.connections.' . config('database.default') . '.database');
        if ($database !== 'testing') {
            throw new \RuntimeException("Los tests deben ejecutarse contra la base de datos 'testing', no '{$database}'.");
        }
    }
}
```

**Helpers** (`tests/Concerns/`):
- `seedRolesAndPermissions()`: ejecuta `PermissionSeeder` y `RoleSeeder`; las rutas de admin y algunas *policies* los necesitan.
- `actingAsUser(array $attrs = [])`: crea un usuario verificado y sin ban y hace `Sanctum::actingAs`.
- `actingAsAdmin()`.

## 4. Convenciones

- **Feature:** contra endpoints reales (`$this->postJson('/api/votes', …)`) con `RefreshDatabase`. Comprueba el estado HTTP, el JSON (`assertJsonPath`, `assertJsonStructure`) y la base de datos (`assertDatabaseHas`).
- **Unit:** solo lógica pura.
- **Nombres:** `test_<comportamiento_en_snake_case>` o `#[Test]`. Por ejemplo, `test_cambiar_un_voto_mantiene_los_medios_puntos`.
- **Factories:** existen `Category`, `Item`, `User`, `Vote`, `Proposal` y `Profile`. `UserFactory` crea usuarios verificados por defecto. Añade *states* (`->rated(7.5)`, `->want()`) en lugar de repetir arrays.
- **Tiempo:** `$this->travelTo(...)` o `Carbon::setTestNow`.
- **Caché:** `Cache::flush()` al probar `NextCategoryItemService`.

## 5. APIs externas: siempre falseadas (R4)

```php
Http::preventStrayRequests();
Http::fake([
    'api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json')),
    'www.wikidata.org/*'              => Http::response($this->fixture('catalog/wikidata/Q83495.json')),
    'es.wikipedia.org/*'              => Http::response($this->fixture('catalog/wikipedia/matrix-summary.json')),
    'image.tmdb.org/*'                => Http::response($this->fakePng(), 200, ['Content-Type' => 'image/png']),
]);
```

- **Fixtures:** capturadas una vez a mano con `curl` y recortadas. **Sin tokens**, ni en las URLs ni en el contenido.
- **Casos de error en cada proveedor:** 404, 429, campo vacío (por ejemplo, sinopsis es-ES vacía) y *timeout*.
- **Imágenes:** `Storage::fake('public')` y `Storage::fake('local')`. `RemoteImageDownloader` hace primero un `HEAD` y luego un `GET`; el *fake* responde a ambos.
- **DNS:** `RemoteImageDownloader::assertSafeUrl()` llama a `gethostbyname()` y `Http::fake()` no lo evita. La F1-04 lo extrae a un colaborador inyectable; en los tests, falséalo. Si no, los tests dependen de la red.

## 6. Cobertura mínima por fase

| Fase | Cubierto |
|---|---|
| 0 | Emitir, cambiar y borrar voto con medias exactas con medios puntos; duplicado; `skip`; kudos del primer voto e idempotencia; tope diario; ranking bayesiano; cola sin baja calidad; búsqueda global; `kudos:audit-consistency` limpio |
| 1 | Importar de TMDB (ficha es-ES, `item_sources`, reimportar sin duplicar); cadena de la descripción; `quality_score`; refresco (`last_synced_at`; un 404 desactiva); búsqueda e importación bajo demanda (autorización y *throttle*) |
| 2 | `want`/`not_interested`; normalización de `skip`; de `want` a valoración (kudos una sola vez); *wishlist*; cola ponderada; similares; listas editoriales en la portada |
| 3 | CRUD de listas y visibilidad (privada → 404); reseñas y respuestas (un nivel); reportes y su resolución con aviso; borrado de cuenta (cascada y anonimización); notificaciones |
| 4 | Mapeadores de IGDB/RAWG y de destinos en Wikidata; licencia de las imágenes de Commons |
