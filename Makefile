sniff:
	vendor/bin/phpcs ./src --standard=PSR2

phpunit:
	vendor/bin/phpunit

coverage:
	vendor/bin/phpunit --coverage-html ./.coverage
