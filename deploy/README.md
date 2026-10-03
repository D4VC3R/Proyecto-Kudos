# Despliegue de la API de Kudos en el VPS

Guía para poner en marcha `api.kudos.dcerdan.es` y mantenerla. El frontend sigue en Vercel.

```
Navegador ─► kudos.dcerdan.es ─────► Vercel (React)
          └► api.kudos.dcerdan.es ─► Nginx del host (80/443, TLS)
                                        └► 127.0.0.1:8001 ─► nginx (Docker) ─► app (PHP-FPM)
                                                                 worker (cola) · postgres · redis
```

Solo el Nginx del host escucha en internet. Ningún contenedor publica puertos salvo el Nginx
interno, y solo en `127.0.0.1:8001`. Docker escribe sus propias reglas de iptables y se salta
ufw, así que un puerto publicado sin `127.0.0.1:` quedaría abierto aunque el firewall diga lo contrario.

| Fichero | Para qué sirve |
|---|---|
| `Backend/Dockerfile` | Imagen multietapa: `app` (PHP-FPM + código) y `web` (Nginx interno) |
| `Backend/compose.prod.yml` | Stack de producción: nginx, app, worker, postgres, redis |
| `Backend/.env.production.example` | Plantilla del `.env.production` (el real nunca se sube) |
| `Backend/docker/prod/` | `php.ini`, pool de PHP-FPM, Nginx interno y entrypoint |
| `deploy.sh` | Despliegue en una orden: pull, build, migraciones, arranque |
| `deploy/nginx/api.kudos.dcerdan.es.conf` | Bloque del Nginx del host (se copia a `/etc/nginx`) |
| `deploy/backup-db.sh` | Volcado diario de PostgreSQL con rotación |

Todas las órdenes de Compose llevan `-f compose.prod.yml --env-file .env.production`. Para no
escribirlo cada vez, añade este alias a `~/.bashrc` en el servidor (y `source ~/.bashrc`):

```bash
alias kudos-dc='docker compose -f ~/apps/kudos/Backend/compose.prod.yml --env-file ~/apps/kudos/Backend/.env.production'
```

---

## Primera puesta en marcha

### 1. DNS (panel de OVH)

| Registro | Tipo | Valor |
|---|---|---|
| `api.kudos` | A | IPv4 del VPS |
| `api.kudos` | AAAA | IPv6 del VPS (si tiene) |
| `kudos` | CNAME | El que indique Vercel al añadir el dominio (paso 9) |

Comprobar antes de seguir: `dig +short api.kudos.dcerdan.es` debe devolver la IP del VPS.
El registro CAA ya autoriza a Let's Encrypt.

### 2. Buzón de correo (panel de OVH)

Crea el buzón `no-reply@dcerdan.es` en la sección de correo del dominio y guarda su contraseña.
Laravel se autentica con él contra `ssl0.ovh.net:465` (SMTPS), y el remitente tiene que ser el
mismo buzón. Comprueba que la zona DNS tiene el SPF de OVH (`v=spf1 include:mx.ovh.com ~all`);
sin él, los correos de verificación acabarán en spam.

### 3. Código

```bash
mkdir -p ~/apps
git clone https://github.com/D4VC3R/Proyecto-Kudos.git ~/apps/kudos
cd ~/apps/kudos/Backend
```

### 4. `.env.production`

```bash
cp .env.production.example .env.production
chmod 600 .env.production          # solo tu usuario puede leerlo
# Contraseña aleatoria de PostgreSQL escrita directamente en el fichero, sin mostrarla en pantalla:
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$(openssl rand -hex 24)|" .env.production
nano .env.production
```

En `nano`, rellena `MAIL_PASSWORD` (el del buzón del paso 2), `SEED_ADMIN_EMAIL` y
`SEED_ADMIN_PASSWORD`. Para la contraseña del admin puedes usar `openssl rand -hex 16`, y
guárdala en tu gestor de contraseñas. Las contraseñas de desarrollo o de Railway no valen.

Después genera la `APP_KEY`. El primer `run` construye la imagen, así que tarda unos minutos:

```bash
docker compose -f compose.prod.yml --env-file .env.production run --rm --no-deps app php artisan key:generate --show
```

Copia la salida (`base64:...`) en `APP_KEY=` dentro de `.env.production`.

### 5. Desplegar y sembrar

```bash
cd ~/apps/kudos
./deploy.sh
```

La siembra crea unos 4.400 ítems y descarga sus imágenes, así que tarda bastante (ver
"Duración de la siembra" al final). Se lanza con `nohup` para que siga aunque se corte la
conexión SSH. Detrás de `nohup` va la orden completa, porque los alias de bash no se expanden
ahí:

```bash
cd ~/apps/kudos/Backend
nohup docker compose -f compose.prod.yml --env-file .env.production run --rm -T app php artisan db:seed --force > ~/kudos-seed.log 2>&1 &
tail -f ~/kudos-seed.log        # Ctrl+C solo deja de mirar; la siembra sigue
```

Los avisos "No se pudo descargar o procesar la imagen" son normales: algunas imágenes de las
fuentes superan los 5 MB o ya no existen, y el seeder las salta. Si la siembra se interrumpe
a mitad, se repite desde cero con `migrate:fresh --seed --force`. Los datos son desechables,
pero no lo hagas cuando ya haya usuarios reales.

Comprobación local, antes de tocar Nginx:

```bash
kudos-dc ps                                        # todo "Up", postgres/redis/nginx "healthy"
curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8001/up     # 200
sudo ss -tulpn | grep 8001                         # solo 127.0.0.1:8001
```

### 6. Nginx del host

```bash
sudo cp ~/apps/kudos/deploy/nginx/api.kudos.dcerdan.es.conf /etc/nginx/sites-available/api.kudos.dcerdan.es
sudo ln -s /etc/nginx/sites-available/api.kudos.dcerdan.es /etc/nginx/sites-enabled/
sudo nginx -t                    # valida la sintaxis ANTES de aplicar nada
sudo systemctl reload nginx      # reload, no restart: no corta las conexiones de dcerdan.es
curl -s -o /dev/null -w '%{http_code}\n' http://api.kudos.dcerdan.es/up    # 200
```

El bloque **sobrescribe** las cabeceras `X-Forwarded-*` en vez de añadir a las del cliente.
Este Nginx es la puerta de entrada, así que lo que mande el cliente en esas cabeceras no es de
fiar. El throttle del login se calcula por IP, y las URLs firmadas de verificación dependen de
`X-Forwarded-Proto`.

### 7. Certificado

```bash
sudo certbot --nginx -d api.kudos.dcerdan.es
curl -sI https://api.kudos.dcerdan.es/up | head -1      # HTTP/2 200
```

Certbot añade el bloque 443 y la redirección de HTTP a HTTPS **en el fichero de
`/etc/nginx`**, así que a partir de aquí ese fichero tiene más líneas que la copia del
repositorio. Es lo esperado: la copia del repo es el punto de partida, no se vuelve a copiar
encima.

### 8. Copias de seguridad

```bash
mkdir -p ~/backups/kudos
~/apps/kudos/deploy/backup-db.sh          # prueba manual: debe imprimir "OK ... .dump"
crontab -e
```

Añade esta línea (cada día a las 03:30; se conservan 7 días):

```
30 3 * * * $HOME/apps/kudos/deploy/backup-db.sh >> $HOME/backups/kudos/backup.log 2>&1
```

Restaurar un volcado (borra y recrea las tablas que contiene):

```bash
kudos-dc exec -T postgres sh -c 'pg_restore -U "$POSTGRES_USER" -d "$POSTGRES_DB" --clean --if-exists --no-owner' < ~/backups/kudos/kudos-AAAAMMDD-HHMMSS.dump
```

### 9. Vercel

1. **Settings → General → Root Directory**: `Frontend`. `Frontend/vercel.json` incluye un
   `ignoreCommand` que salta el build cuando un commit no toca `Frontend/`.
2. **Settings → Environment Variables** (Production):
   - `VITE_API_URL` = `https://api.kudos.dcerdan.es/api`
   - `VITE_STORAGE_URL` = `https://api.kudos.dcerdan.es/storage/`
3. **Deployments → Redeploy**. Las variables de Vite se incrustan al construir, así que no
   cambian nada hasta el siguiente build.
4. **Settings → Domains**: añade `kudos.dcerdan.es` y crea en OVH el CNAME que te indique.

CORS solo admite `FRONTEND_URL` (`https://kudos.dcerdan.es`). Si quieres probar antes desde la
URL `*.vercel.app`, añádela separada por coma (`FRONTEND_URL=https://kudos.dcerdan.es,https://xxx.vercel.app`)
y ejecuta `kudos-dc up -d`. El primer valor es el que se usa en los enlaces de los correos.

### 10. Comprobaciones finales

```bash
sudo ss -tulpn                    # nada nuevo en 0.0.0.0 salvo 22, 80 y 443
kudos-dc ps                       # todo arriba, nada reiniciándose
docker stats --no-stream          # dentro del presupuesto (~2 GB de límites)
free -h
curl -I https://api.kudos.dcerdan.es/up
curl -I https://api.kudos.dcerdan.es/.env       # 404
```

Desde el navegador, en `https://kudos.dcerdan.es`:

1. Regístrate con un email real: el correo de verificación debe llegar.
2. Verifica la cuenta, vota y sube un avatar.
3. Comprueba que la consola no muestra errores de CORS.

---

## Operación diaria

| Tarea | Orden |
|---|---|
| Desplegar una versión nueva | `cd ~/apps/kudos && ./deploy.sh` |
| Ver logs | `kudos-dc logs -f --tail=100 app worker nginx` |
| Estado | `kudos-dc ps` |
| Aplicar un cambio en `.env.production` | `kudos-dc up -d` (recrea los contenedores afectados) |
| Comando de artisan | `kudos-dc exec app php artisan <comando>` |
| Log de auditoría de moderación | `kudos-dc exec app sh -c 'tail -n 50 storage/logs/moderation/*.log'` |
| Reintentar jobs fallidos | `kudos-dc exec app php artisan queue:retry all` |

Nunca edites ficheros del repositorio en el servidor: el cambio se hace en local, se sube a
`main` y se despliega con `./deploy.sh` (que usa `git pull --ff-only` y falla si encuentra
cambios locales).

`POSTGRES_PASSWORD` solo se aplica la primera vez que se crea el volumen. Si cambias
`DB_PASSWORD` más adelante, hay que cambiarla también dentro de PostgreSQL (`ALTER USER`).

## Duración de la siembra

Medido en local (2026-10-03) con el stack de producción: **unos 17 minutos**. Crea 51 usuarios,
4.349 ítems, 54.558 votos y 30 propuestas, y deja unos 200 MB de imágenes en el volumen
`kudos_storage-public`. Casi todo el tiempo se va en `ItemSeeder`: descarga cada imagen y genera
sus variantes con GD. En el VPS (2 vCores) puede tardar algo más.

Consumo en reposo medido con `docker stats`: unos 170 MB en total (nginx 10, app 29, worker 33,
postgres 90, redis 7). Los límites de `compose.prod.yml` son techos, no reservas.
