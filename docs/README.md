# Documentación de Kudos

**Objetivo:** *"Tu diario de pelis, series y juegos en español, y destinos que descubrir, con listas que lo conectan todo."* Tres categorías vivas (Pelis/Series, Juegos, Destinos) alimentadas por APIs gratuitas; votar como "¿Lo conoces? → valóralo / me interesa / no me interesa"; descubrimiento que no depende del volumen de usuarios.

**Restricciones:** coste cero y sin ánimo de lucro; contenido en español; un VPS de 4 GB compartido; un solo desarrollador; los datos actuales son de prueba y se pueden resetear.

## Arquitectura

Cómo está hecho el código y qué contrato expone la API.

- [backend/arquitectura.md](backend/arquitectura.md): capas, mapa por dominio, trampas conocidas y patrones.
- [backend/base-de-datos.md](backend/base-de-datos.md): esquema actual y migraciones de cada fase.
- [backend/testing.md](backend/testing.md): `TestCase`, estructura de los tests, convenciones y APIs falseadas.
- [frontend/arquitectura.md](frontend/arquitectura.md): stack, estructura, rutas, estilos y decisiones de UI.
- [../Backend/docs/api-contract.md](../Backend/docs/api-contract.md): contrato JSON de la API (respuestas, errores y reglas de votación).

## Puesta en marcha

- [infra/entorno-local.md](infra/entorno-local.md): Sail, base de datos de tests, frontend, comandos y problemas frecuentes.

## Producción

- [infra/produccion.md](infra/produccion.md): `compose.prod.yml`, variables, copias de seguridad y el *runbook* único del paso a Kudos 2.0 (§5).
- [../deploy/README.md](../deploy/README.md): Nginx del host y copias de seguridad del VPS.

## Integraciones

- [integraciones/fuentes-externas.md](integraciones/fuentes-externas.md): reglas y APIs permitidas, endpoints y cobertura.
- [integraciones/pipeline-catalogo.md](integraciones/pipeline-catalogo.md): módulo `app/Catalog`: contratos, importación y refresco.

## Producto y legal

- [producto/vision.md](producto/vision.md): decisiones de producto, funciones aplazadas y métricas.
- [legal/cumplimiento.md](legal/cumplimiento.md): atribuciones, datos de terceros, DSA y RGPD.
- [redesign/](redesign/plan.md): rediseño visual del frontend: [auditoría](redesign/auditoria.md) con capturas, [direcciones de diseño](redesign/direcciones.md) y [plan](redesign/plan.md) con las decisiones del humano.

## Proceso de desarrollo

- [guia-agentes.md](guia-agentes.md): reglas R1–R13 y el ciclo de trabajo con agentes.
- [roadmap/README.md](roadmap/README.md): tablero y fichas de las fases 0–5, histórico del rediseño.
- [../AGENTS.md](../AGENTS.md): instrucciones para agentes de IA.
