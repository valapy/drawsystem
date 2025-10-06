#!/bin/bash

set -e

echo "🔧 Configurando Laravel..."

# Esperar montaje de volúmenes
sleep 2

# Crear directorios necesarios
mkdir -p /var/www/html/storage/framework/{sessions,views,cache,testing}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/storage/app/public

# Permisos de directorios
chmod -R 775 /var/www/html/storage 2>/dev/null || true
chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true

echo "✓ Laravel configurado correctamente"
echo "👤 Corriendo como usuario: $(whoami)"
echo "📂 Permisos de storage: $(stat -c '%U:%G' /var/www/html/storage 2>/dev/null || echo 'N/A')"

# Ejecutar comando pasado (supervisord)
exec "$@"
