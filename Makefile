sniff:
	vendor/bin/phpcs ./src --standard=PSR2

phpunit:
	vendor/bin/phpunit

coverage:
	phpunit --coverage-html ./cover ./src
