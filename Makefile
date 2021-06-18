CONTAINER_USER="$(shell id -u):$(shell id -g)"

sniff:
	docker-compose run --rm php vendor/bin/phpcs

phpunit:
	docker-compose run --rm php vendor/bin/phpunit

coverage:
	docker-compose run --rm php vendor/bin/phpunit --coverage-html ./.coverage

mkdocs:
	docker-compose run --rm mkdocs mkdocs build --clean

mkapi:
	docker run --rm --user ${CONTAINER_USER} -v ${PWD}:/app --workdir /app --entrypoint /bin/doctum botsudo/action-doctum:v5 update bin/generate-api-docs
