FROM php:5.6

RUN apt-get update -q \
  && apt-get install wget unzip git -y --no-install-recommends

COPY composer-install.sh composer-install.sh

RUN ./composer-install.sh \
  && mv composer.phar /usr/local/bin/composer

COPY . /code

WORKDIR /code

RUN composer install
