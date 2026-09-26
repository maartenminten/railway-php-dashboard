FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client gzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite headers \
    && a2dismod mpm_event || true \
    && a2dismod mpm_worker || true \
    && a2enmod mpm_prefork

COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker-entrypoint.sh /usr/local/bin/railway-entrypoint
RUN chmod +x /usr/local/bin/railway-entrypoint

COPY app/ /var/www/app/
COPY public/ /var/www/html/
COPY sql/ /var/www/sql/
COPY scripts/ /var/www/scripts/

RUN chown -R www-data:www-data /var/www/html /var/www/app /var/www/sql /var/www/scripts

EXPOSE 8080
ENTRYPOINT ["railway-entrypoint"]
CMD ["apache2-foreground"]
