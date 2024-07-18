FROM php:7.4-fpm

RUN apt-get update && apt-get install -y \
    nginx \
    wget \
    sendmail \
    libzip-dev \
    zlib1g-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \
    zip

RUN apt install -y netcat

RUN mkdir -p /run/nginx

RUN docker-php-ext-install mysqli 

RUN docker-php-ext-install pdo pdo_mysql  

RUN docker-php-ext-install gd zip

COPY docker/nginx.conf /etc/nginx/nginx.conf

RUN mkdir -p /rq
COPY . /rq

RUN sh -c "wget http://getcomposer.org/composer.phar && chmod a+x composer.phar && mv composer.phar /usr/local/bin/composer"
RUN cd /rq && \
    /usr/local/bin/composer install --no-dev

RUN cd /rq && \
    /usr/local/bin/composer update

CMD php artisan passport:install

RUN chown -R www-data: /rq

RUN chmod -R 777 /rq/storage

RUN chmod -R 777 /rq/bootstrap

CMD sh /rq/docker/startup.sh
