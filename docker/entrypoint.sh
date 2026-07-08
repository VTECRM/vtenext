#!/bin/bash
set -e

# Ensure writable directories exist
mkdir -p /var/www/html/cache \
         /var/www/html/logs \
         /var/www/html/upload \
         /var/www/html/storage

chown -R www-data:www-data \
    /var/www/html/cache \
    /var/www/html/logs \
    /var/www/html/upload \
    /var/www/html/storage

chmod -R 775 \
    /var/www/html/cache \
    /var/www/html/logs \
    /var/www/html/upload \
    /var/www/html/storage

# Copy .htaccess if not present
if [ -f /var/www/html/.htaccess.txt ] && [ ! -f /var/www/html/.htaccess ]; then
    cp /var/www/html/.htaccess.txt /var/www/html/.htaccess
fi

# Write config.db.php from environment variables if still contains placeholders
if grep -q '_DBC_TYPE_' /var/www/html/config.db.php 2>/dev/null; then
    echo "Writing config.db.php from environment variables..."
    cat > /var/www/html/config.db.php << EOF
<?php
  \$dbconfig['db_server']   = '${DB_HOST:-db}';
  \$dbconfig['db_port']     = '${DB_PORT:-3306}';
  \$dbconfig['db_sockpath'] = '';
  \$dbconfig['db_username'] = '${DB_USER:-vtenext}';
  \$dbconfig['db_password'] = '${DB_PASSWORD:-vtenext}';
  \$dbconfig['db_name']     = '${DB_NAME:-vtenext}';
  \$dbconfig['db_type']     = 'mysql';
  \$dbconfig['db_bundled']  = 'false';
  \$vtconfig['adminPwd']            = '${VTENEXT_ADMIN_PASS:-admin}';
  \$vtconfig['standarduserPwd']     = '${VTENEXT_USER_PASS:-user}';
  \$vtconfig['adminEmail']          = '${VTENEXT_ADMIN_EMAIL:-admin@vtenext.local}';
  \$vtconfig['standarduserEmail']   = '${VTENEXT_USER_EMAIL:-user@vtenext.local}';
  \$vtconfig['demoData']            = 'false';
  \$vtconfig['currencyName']        = 'Euro(\xe2\x82\xac)';
  \$vtconfig['quickbuild']          = 'false';
EOF
    chown www-data:www-data /var/www/html/config.db.php
fi

exec "$@"
