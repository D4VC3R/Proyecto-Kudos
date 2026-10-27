# Backend: estrategia de tests

**Responsable:** backend/QA. **Estado actual: no hay ningún test.** La tarea F0-01 crea la infraestructura y la F0-02 la batería base.

## 1. Configuración

- **Motor:** PostgreSQL, base de datos `testing`, en el mismo servicio `pgsql` de Sail. **Nunca SQLite**, porque las migraciones usan `CREATE EXTENSION`, `jsonb` y GIN.
- **`phpunit.xml`** (lo deja así la tarea F0-01):

  ```xml
  <env name="DB_CONNECTION" value="pgsql" force="true"/>
  <env name="DB_DATABASE" value="testing" force="true"/>
  <env name="CACHE_STORE" value="array"/>
  <env name="QUEUE_CONNECTION" value="sync"/>
  <env name="MAIL_MAILER" value="array"/>
  <env name="SESSION_DRIVER" value="array"/>
  ```

  `DB_HOST`, `DB_USERNAME` y `DB_PASSWORD` se heredan de `.env` (`pgsql` / `sail` / `password`).
- **Ejecución:** `sail test` (ver [../infra/entorno-local.md](../infra/entorno-local.md)).

## 2. Estructura

```
Backend/tests/
├── TestCase.php                 # clase base con la protección de la base de datos y helpers
├── Feature/
│   ├── Votes/                   # EmitVoteTest, ChangeVoteTest, DeleteVoteTest, NextItemTest…
│   ├── Ranking/
│   ├── Items/                   # búsqueda, listado, detalle
│   ├── Kudos/
│   ├── Catalog/                 # importación, refresco, búsqueda bajo demanda (Fase 1)
│   └── …
├── Unit/                        # cálculos puros: BayesianScore, QualityScore, mapeadores de proveedores
└── Fixtures/
    └── catalog/                 # respuestas JSON reales (anonimizadas o recortadas) de TMDB, Wikidata y Wikipedia
```

## 3. `tests/TestCase.php` (obligatorio)

```php
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Protección: RefreshDatabase borra la base de datos. Nunca contra la de desarrollo o producción.
        $database = config('database.connections.' . config('database.default') . '.database');
        if ($database !== 'testing') {
            throw new \RuntimeException("Los tests deben ejecutarse contra la base de datos 'testing', no '{$database}'.");
        }
    }
}
```

**Helpers recomendados**, como métodos o traits en `tests/Concerns/`:
- `seedRolesAndPermissions()`: llama a `PermissionSeeder` y `RoleSeeder`. Las rutas de admin y algunas *policies* los necesitan.
- `actingAsUser(array $attrs = [])`: crea un usuario verificado y sin ban y hace `Sanctum::actingAs`.
- `actingAsAdmin()`.

## 4. Convenciones

- **Tests de feature contra endpoints HTTP reales** (`$this->postJson('/api/votes', …)`), con `RefreshDatabase`. Comprueban:
  - el código de estado;
  - la forma del JSON (`assertJsonPath`, `assertJsonStructure`);
  - el estado de la base de datos (`assertDatabaseHas`).
- **Tests unitarios** solo para la lógica pura: fórmulas, mapeadores y normalizadores.
- **Nombres:** `test_<comportamiento_en_snake_case>` o el atributo `#[Test]`, con una descripción clara. Por ejemplo: `test_cambiar_un_voto_mantiene_los_medios_puntos`.
- **Factories:** existen `CategoryFactory`, `ItemFactory`, `UserFactory`, `VoteFactory`, `ProposalFactory` y `ProfileFactory`. Amplíalas con *states* (`->rated(7.5)`, `->want()`) en lugar de repetir arrays.
- **Tiempo:** si se depende de él, usar `$this->travelTo(...)` o `Carbon::setTestNow`.
- **Caché:** `Cache::flush()` cuando se pruebe `NextCategoryItemService`, porque cachea la cola.

## 5. APIs externas: siempre falseadas

**Ningún test puede hacer peticiones reales.** Al principio de cada test o en `setUp`:

```php
Http::preventStrayRequests();
Http::fake([
    'api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json')),
    'www.wikidata.org/*'              => Http::response($this->fixture('catalog/wikidata/Q83495.json')),
    'es.wikipedia.org/*'              => Http::response($this->fixture('catalog/wikipedia/matrix-summary.json')),
    'image.tmdb.org/*'                => Http::response($this->fakePng(), 200, ['Content-Type' => 'image/png']),
]);
```

- **Fixtures:**
  - capturadas una vez a mano con `curl` y recortadas a lo necesario;
  - **sin tokens** en las URLs ni en el contenido.
- **Casos de error que hay que probar** en cada proveedor: 404, 429 (límite de peticiones), un campo vacío (por ejemplo, sinopsis en español vacía) y un *timeout*.
- **Imágenes:** `Storage::fake('public')` y `Storage::fake('local')`.

## 6. Cobertura mínima por fase

| Fase | Debe quedar cubierto |
|---|---|
| 0 | Emitir, cambiar y borrar voto (medias exactas con medios puntos), voto duplicado, *skip*, primer voto da kudos e idempotencia, tope diario de kudos, ranking bayesiano, cola sin ítems de baja calidad, búsqueda global, `kudos:audit-consistency` sin desajustes tras los flujos |
| 1 | Importar desde TMDB (ficha en español, `item_sources`, sin duplicados al reimportar), cadena de prioridades de la descripción, cálculo de `quality_score`, refresco (actualiza `last_synced_at`; un 404 desactiva el ítem), búsqueda e importación bajo demanda (autorización y *throttle*) |
| 2 | Tipos `want` y `not_interested`, normalización de `skip`, transición de `want` a valoración (da kudos una sola vez), *wishlist*, cola ponderada (excluye lo votado y lo de baja calidad), similares, listas editoriales en la portada |
| 3 | CRUD de listas y visibilidad (una lista privada da 404 a terceros), reseñas y respuestas (un solo nivel), reportes y su resolución con aviso, borrado de cuenta (cascada y anonimización), notificaciones |
| 4 | Mapeadores de IGDB o RAWG y de Wikidata para destinos, atribución y licencia de imágenes de Commons |
