FROM node:22-alpine

ENV PNPM_HOME="/pnpm"
ENV PATH="$PNPM_HOME:$PATH"
RUN corepack enable

# Establecemos el directorio
WORKDIR /app

# CREAMOS la carpeta primero y luego asignamos la propiedad al usuario "node" de forma limpia
RUN mkdir -p /pnpm && chown -R node:node /app /pnpm

# A PARTIR DE AQUÍ: Bajamos los privilegios al usuario seguro "node"
USER node

COPY --chown=node:node package.json pnpm-lock.yaml* pnpm-workspace.yaml* ./

# Instalamos dependencias
RUN pnpm install --frozen-lockfile

# Copiamos el resto del código
COPY --chown=node:node . .

EXPOSE 5174

# Ejecución limpia
CMD ["pnpm", "run", "dev"]