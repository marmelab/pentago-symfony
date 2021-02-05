FROM php:8-fpm

RUN apt-get update && apt-get upgrade -y

RUN apt-get install -y zlib1g-dev libpq-dev git libicu-dev libxml2-dev libzip-dev wget unzip\
    && wget https://get.symfony.com/cli/installer -O - | bash\
    && mv /root/.symfony/bin/symfony /usr/local/bin/symfony\
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && docker-php-ext-install zip xml \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false

RUN echo "$(curl -sS https://composer.github.io/installer.sig) -" > composer-setup.php.sig \
    && curl -sS https://getcomposer.org/installer | tee composer-setup.php | sha384sum -c composer-setup.php.sig \
    && php composer-setup.php && rm composer-setup.php* \
    && chmod +x composer.phar && mv composer.phar /usr/local/bin/composer

WORKDIR /app
