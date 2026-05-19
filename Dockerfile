FROM php:8.5-cli-alpine

RUN apk update && apk add curl git zip unzip ngrep linux-headers ${PHPIZE_DEPS}

ADD https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh \
    /usr/local/bin/wait-for-it
RUN chmod +x /usr/local/bin/wait-for-it

RUN pecl install xdebug && docker-php-ext-enable xdebug \
    && pecl install mongodb && docker-php-ext-enable mongodb

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY ./ ./

CMD ["php", "-S", "0.0.0.0:80", "-t", "public", "public/index.php"]
