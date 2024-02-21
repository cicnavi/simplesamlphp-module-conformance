ARG PHP_VERSION="8.3"
ARG COMPOSER_VERSION="2"
ARG SSP_VERSION="2.1.3"

FROM mlocati/php-extension-installer AS extension_installer
FROM composer/composer:${COMPOSER_VERSION} as composer

FROM php:${PHP_VERSION}${PHP_VERSION:+-}fpm AS base

ARG DEBIAN_FRONTEND=noninteractive

# use production php.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# install PHP extensions and their dependencies
COPY --from=extension_installer /usr/bin/install-php-extensions /usr/bin/
RUN install-php-extensions gmp intl pdo_mysql zip

FROM base AS ssp_builder

ARG SSP_VERSION

ARG DEBIAN_FRONTEND=noninteractive
ARG COMPOSER_ALLOW_SUPERUSER=1

# add composer
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN apt update -y \
    && apt install -y --no-install-recommends git wget zip

# install SimpleSAMLphp
RUN cd /var \
    # TODO: git clone your module here etc., adapt as needed
    && wget https://github.com/simplesamlphp/simplesamlphp/releases/download/v${SSP_VERSION}/simplesamlphp-${SSP_VERSION}.tar.gz -O simplesamlphp.tar.gz \
    && tar -xzf simplesamlphp.tar.gz \
    && mv simplesamlphp-${SSP_VERSION} simplesamlphp \
    && cd simplesamlphp \
    && /usr/bin/composer install --no-interaction --no-dev --no-progress \
    && cp config/config.php.dist config/config.php \
    && cp config/authsources.php.dist config/authsources.php \
    && cp metadata/saml20-idp-hosted.php.dist metadata/saml20-idp-hosted.php \
    && cp metadata/saml20-idp-remote.php.dist metadata/saml20-idp-remote.php \
    && cp metadata/saml20-sp-remote.php.dist metadata/saml20-sp-remote.php \
    && mkdir -p /tmp/cache/simplesamlphp

# install conformance module
RUN cd /var/simplesamlphp \
    && composer config minimum-stability dev \
    && composer config repositories.0 git https://github.com/cicnavi/simplesamlphp-module-conformance.git \
    && composer require --no-progress cicnavi/simplesamlphp-module-conformance:dev-wip

# modify config
RUN sed -i "s/'session.cookie.secure' => true,/'session.cookie.secure' => false,/g" /var/simplesamlphp/config/config.php \
    && sed -i "s/'cachedir' => '\/var\/cache\/simplesamlphp',/'cachedir' => '\/tmp\/cache\/simplesamlphp',/g" /var/simplesamlphp/config/config.php \
    && sed -i "s/'auth.adminpassword' => '123',/'auth.adminpassword' => 'admin',/g" /var/simplesamlphp/config/config.php

# finalize

FROM base

COPY --from=ssp_builder /var/simplesamlphp/ /var/simplesamlphp/

EXPOSE 9000

WORKDIR /var/simplesamlphp
