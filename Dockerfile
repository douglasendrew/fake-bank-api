FROM hyperf/hyperf:8.3-alpine-v3.19-swoole

LABEL maintainer="Douglas <douglas@fakebank.com>"

ARG app_env=local

ENV APP_ENV=${app_env} \
    SCAN_CACHEABLE=(true)

WORKDIR /opt/www

COPY . /opt/www

RUN composer install --no-dev --o

EXPOSE 9501

ENTRYPOINT ["php", "/opt/www/bin/hyperf.php", "start"]
