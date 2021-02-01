FROM php:8

RUN apt-get update && apt-get upgrade -y

RUN apt-get install -y --no-install-recommends wget unzip\
    && wget https://get.symfony.com/cli/installer -O - | bash\
    && mv /root/.symfony/bin/symfony /usr/local/bin/symfony\
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false

RUN echo "$(curl -sS https://composer.github.io/installer.sig) -" > composer-setup.php.sig \
    && curl -sS https://getcomposer.org/installer | tee composer-setup.php | sha384sum -c composer-setup.php.sig \
    && php composer-setup.php && rm composer-setup.php* \
    && chmod +x composer.phar && mv composer.phar /usr/local/bin/composer


WORKDIR /app
