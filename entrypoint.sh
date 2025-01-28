#!/bin/bash
/etc/init.d/php8.2-fpm start && \
/etc/init.d/cron start && \
/usr/bin/php8.2 artisan optimize && \
/usr/bin/php8.2 artisan view:cache && \
/usr/bin/php8.2 artisan event:cache && \
nginx -g 'daemon off;'
