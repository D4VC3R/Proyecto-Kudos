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
  `DB_HOST`, `DB_USERNAME` y `DB_PASSWORD` se heredan de `.env` (el servicio `pgsql` y las credenciales con las que se creó el volumen).
- Cómo crear la base de datos y ejecutar los tests: [../infra/entorno-local.md](../infra/entorno-local.md) §4.

## 2. Estructura

```
Backend/tests/
├── TestCase.php     # protección de la base de datos (§3) y Http::preventStrayRequests() (§5)
├── Concerns/        # helpers: seedRolesAndPermissions, actingAsUser, actingAsAdmin, CreatesItems
├── Feature/         # Votes/, Ranking/, Items/, Kudos/, Catalog/…
├── Unit/            # lógica pura: fórmulas, mapeadores, normalizadores
└── Fixtures/catalog/  # respuestas reales recortadas de TMDB, Wikidata y Wikipedia
```

## 3. `tests/TestCase.php` (obligatorio, R3)

La protección tiene que comprobarse **antes** de que `RefreshDatabase` (o `DatabaseMigrations`, o `DatabaseTruncation`) borre la base de datos, y en un punto que ningún trait pueda tapar:

- En `setUp()` llegaría tarde: `RefreshDatabase` se ejecuta dentro de `parent::setUp()`.
- En `beforeRefreshingDatabase()` **no funciona**: en PHP, el método de un trait tiene prioridad sobre el heredado, así que el `beforeRefreshingDatabase()` vacío de `RefreshDatabase`, que usa cada test, taparía el de `TestCase` (o daría un error fatal de firma si se declara con `: void`).
- Por eso va en `setUpTraits()`, que se ejecuta con la configuración ya cargada y justo antes de los traits de base de datos.

```php
abstract class TestCase extends BaseTestCase
{
    use InteractsWithUsers;

    protected function setUpTraits()
    {
        $this->ensureTestingDatabase();

        return parent::setUpTraits();
    }

    protected function ensureTestingDatabase(): void
    {
        $database = config('database.connections.' . config('database.default') . '.database');
        if ($database !== 'testing') {
            throw new \RuntimeException("Los tests deben ejecutarse contra la base de datos 'testing', no '{$database}'.");
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }
}
```

El `setUp()` llama a `parent::setUp()` primero (donde corre `setUpTraits()`), así que no cambia el orden de la protección de la base de datos; `verify-test-db-guard.sh` lo comprueba.

`tests/Feature/DatabaseProtectionTest.php` comprueba que la protección sigue funcionando. La prueba de extremo a extremo es `Backend/scripts/verify-test-db-guard.sh`: crea una base de datos señuelo, ejecuta los tests forzándola y comprueba que abortan sin tocarla. Ejecútalo siempre que cambies `TestCase.php`, `phpunit.xml` o los traits de base de datos.

**Helpers** (`tests/Concerns/InteractsWithUsers.php`, incluido en `TestCase`):
- `seedRolesAndPermissions()`: ejecuta `PermissionSeeder` y `RoleSeeder` (una vez por test); las rutas de admin y algunas *policies* los necesitan. Crea los roles con el guard `web` aunque `Sanctum::actingAs()` haya cambiado el guard por defecto a `sanctum`.
- `actingAsUser(array $attrs = [])`: crea un usuario verificado y sin ban, hace `Sanctum::actingAs` y lo devuelve. No le asigna rol, igual que el registro de la app.
- `actingAsAdmin(array $attrs = [])`: igual, con el rol `admin` (siembra los roles si hace falta).

**Helpers de catálogo** (`tests/Concerns/CreatesItems.php`, **no** está en `TestCase`: cada clase lo usa con `use CreatesItems;`):
- `createCategory(string $slug = 'peliculas', string $name = 'Películas')`: crea una categoría con `name` y `slug` explícitos.
- `createItem(Category $category, array $attrs = [])`: crea un ítem activo y sin votos en la categoría (y un usuario si no hay ninguno, porque `ItemFactory` lo necesita). Los `$attrs` pisan los valores por defecto.

## 4. Convenciones

- **Feature:** contra endpoints reales (`$this->postJson('/api/votes', …)`) con `RefreshDatabase`. Comprueba el estado HTTP, el JSON (`assertJsonPath`, `assertJsonStructure`) y la base de datos (`assertDatabaseHas`).
- **Unit:** solo lógica pura.
- **Nombres:** `test_<comportamiento_en_snake_case>` o `#[Test]`. Por ejemplo, `test_cambiar_un_voto_mantiene_los_medios_puntos`.
- **Factories:** existen `Category`, `Item`, `User`, `Vote`, `Proposal` y `Profile`. `UserFactory` crea usuarios verificados por defecto. `VoteFactory` ya tiene `->rated(float)` (nota decimal, tipo `vote`; `->withScore(int)` trunca los medios puntos). Añade *states* (`->want()`, …) en lugar de repetir arrays.
- **Trampa de las factories:** `ItemFactory::definition()` elige categoría (`Category::inRandomOrder()`) y creador (`User::inRandomOrder()`), y `VoteFactory::definition()` elige usuario e ítem activo, **siempre**, aunque pases *overrides*. Antes de crear un ítem o un voto tiene que existir en la base de datos al menos un usuario (y una categoría, o un ítem activo). `CategoryFactory` elige el nombre de una lista corta que se reinicia, así que pasa siempre `name` y `slug` explícitos o dos categorías del mismo test pueden chocar. Usa `CreatesItems`.
- **Tiempo:** `$this->travelTo(...)` o `Carbon::setTestNow`.
- **Caché:** `Cache::flush()` al probar `NextCategoryItemService`.

## 5. APIs externas: siempre falseadas (R4)

```php
// Http::preventStrayRequests() ya lo activa tests/TestCase.php.
Http::fake([
    'api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json')),
    'www.wikidata.org/*'              => Http::response($this->fixture('catalog/wikidata/Q83495.json')),
    'es.wikipedia.org/*'              => Http::response($this->fixture('catalog/wikipedia/matrix-summary.json')),
    'image.tmdb.org/*'                => Http::response($this->fakePng(), 200, ['Content-Type' => 'image/png']),
]);
```

- **Protección global:** `tests/TestCase.php::setUp()` activa `Http::preventStrayRequests()` para toda clase que extienda `Tests\TestCase`; cada test solo declara sus `Http::fake()`. La llamada en el `setUp()` de cada clase es redundante pero inocua. Los tests `Unit` que extienden `PHPUnit\Framework\TestCase` no arrancan la app ni pueden usar `Http` (si lo necesitan, que extiendan `Tests\TestCase`). `tests/Feature/StrayRequestsTest.php` comprueba la protección.
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

## 7. CI (GitHub Actions)

`.github/workflows/tests.yml` se ejecuta en cada `push` y `pull_request`, con dos jobs:

- **`backend`:**
  - Servicio `postgres:18-alpine` con la base de datos `testing` (usuario `sail` y contraseña `password`, solo del CI).
  - PHP 8.5 con `shivammathur/setup-php`.
  - `DB_HOST=127.0.0.1`, `DB_USERNAME` y `DB_PASSWORD` van en el `env` del job y pisan los de `.env` (en local salen de `.env`, ver §1).
  - Sin Redis: `phpunit.xml` pone caché y sesión en array.
  - No crea `storage/framework/*`: aunque no está versionado, `AppServiceProvider::ensureFrameworkDirectoriesExist()` lo crea al arrancar la app (ya lo hace el `package:discover` de `composer install`).
  - Ejecuta `php artisan test`.
  - `composer install` ya no avisa de que `composer.lock` esté desfasado (F0-19).
- **`frontend`:** Node 22, pnpm fijado en `Frontend/package.json` (`packageManager`, 12.15.0; el workflow lee la versión de ahí y lo instala con `npm install -g`) y `pnpm install --frozen-lockfile && pnpm build`.

Sin secretos. La protección de `tests/TestCase.php` (§3) también actúa en CI.
