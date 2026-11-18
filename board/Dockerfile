FROM php:8.0-apache

RUN apt-get update && apt-get install -y libcurl4-openssl-dev && \
    docker-php-ext-install mysqli curl && \
    docker-php-ext-enable mysqli curl

RUN echo "PassEnv CHALLENGE_SECRET" > /etc/apache2/conf-enabled/whz-env.conf

COPY ./src /var/www/html/

EXPOSE 80
