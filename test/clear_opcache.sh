sudo systemctl reload php8.3-fpm
sudo -u www-data php -r "opcache_reset(); echo 'OPcache cleared successfully';"
