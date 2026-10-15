#!/bin/sh
# Verifikasi: jalankan render halaman /motor memakai php8.3 bila punya pdo_sqlite.
if /usr/bin/php8.3 -m | grep -qi pdo_sqlite; then
  echo "php8.3 punya pdo_sqlite -> jalankan verifikasi halaman /motor"
  /usr/bin/php8.3 verify_motor_page.php
else
  echo "php8.3 juga tanpa pdo_sqlite. Modul terkait pada php8.3:"
  /usr/bin/php8.3 -m | grep -iE 'sqlite|pdo|mysql'
fi
