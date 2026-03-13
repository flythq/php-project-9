PORT ?= 8888

start:
    PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public public/index.php

start-local:
	php -S 0.0.0.0:$(PORT) -t public public/index.php

install:
	composer install

lint:
	composer exec --verbose phpcs -- --standard=PSR12 public src tests
	composer exec --verbose phpstan -- analyze -c phpstan.neon

