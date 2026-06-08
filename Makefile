install:
	composer install

validate:
	composer validate

PORT ?= 8000
start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src public
	composer exec --verbose phpstan analyse --memory-limit=512M src public

lint-fix:
	composer exec --verbose phpcbf -- --standard=PSR12 src public