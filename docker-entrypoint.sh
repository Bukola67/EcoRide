#!/bin/sh
set -eu

echo "=== railway-entrypoint: nettoyage MPM ==="
echo "=== MPM actifs avant correction ==="
ls -la /etc/apache2/mods-enabled/ | grep mpm || echo "(aucun MPM listé)"

rm -f /etc/apache2/mods-enabled/mpm_event.load \
      /etc/apache2/mods-enabled/mpm_event.conf \
      /etc/apache2/mods-enabled/mpm_worker.load \
      /etc/apache2/mods-enabled/mpm_worker.conf \
      /etc/apache2/mods-enabled/mpm_prefork.load \
      /etc/apache2/mods-enabled/mpm_prefork.conf

ln -sf /etc/apache2/mods-available/mpm_prefork.load \
       /etc/apache2/mods-enabled/mpm_prefork.load

ln -sf /etc/apache2/mods-available/mpm_prefork.conf \
       /etc/apache2/mods-enabled/mpm_prefork.conf

echo "=== MPM actifs après correction ==="
ls -la /etc/apache2/mods-enabled/ | grep mpm

echo "=== Validation configuration Apache ==="
apache2ctl -t

echo "=== Démarrage Apache ==="
exec /usr/local/bin/docker-php-entrypoint apache2-foreground