FROM php:7.4-apache
RUN apt update && apt install -y \
      ssl-cert \
      libicu-dev \
      libjpeg-dev zlib1g-dev libpng-dev libwebp-dev
RUN docker-php-ext-configure gd --with-jpeg --with-webp
RUN docker-php-ext-install bcmath exif gettext gd intl mysqli
RUN a2enmod rewrite ssl
RUN a2ensite default-ssl
