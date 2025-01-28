#!/bin/bash
/etc/init.d/php8.2-fpm start && \
/etc/init.d/supervisor start && \
/usr/bin/supervisorctl reread && \
/usr/bin/supervisorctl update && \
/usr/bin/supervisorctl start api.sma.com:* && \
/usr/bin/supervisorctl start ldt.api.sma.com:* && \
/etc/init.d/cron start && \
yes | /usr/bin/php8.2 artisan migrate && \
#/usr/bin/php8.2 artisan storage:link && \
nginx -g 'daemon off;'

