sniff:
	docker-compose run --rm php vendor/bin/phpcs ./src --standard='./coding_standard.xml' -n

phpunit:
	docker-compose run --rm php vendor/bin/phpunit

coverage:
	docker-compose run --rm php vendor/bin/phpunit --coverage-html ./.coverage

mkdocs:
	docker-compose run --rm mkdocs mkdocs build --clean

mkapi:
	docker-compose run --rm php vendor/bin/sami.php update sami.php; exit 0
