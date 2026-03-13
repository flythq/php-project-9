PORT ?= 8000

start:
	@echo "Starting PHP server on port ${PORT}..."
    PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

start-local:
	php -S 0.0.0.0:$(PORT) -t public public/index.php

install:
	composer install

lint:
	composer exec --verbose phpcs -- --standard=PSR12 public src tests
	composer exec --verbose phpstan -- analyze -c phpstan.neon

