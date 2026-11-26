CONTAINER_USER="$(shell id -u):$(shell id -g)"

sniff:
	docker-compose run --rm php vendor/bin/phpcs

phpunit:
	docker-compose run --rm php vendor/bin/phpunit

coverage:
	docker-compose run --rm php vendor/bin/phpunit --coverage-html ./.coverage

mkdocs:
	docker-compose run --rm mkdocs mkdocs build --clean -f /workdir/mkdocs/.mkdocs.yml

mkapi:
	docker run --rm --user ${CONTAINER_USER} -v ${PWD}:/app --workdir /app --entrypoint /bin/doctum botsudo/action-doctum:v5 update bin/generate-api-docs

docs:
	copilot --prompt="Use the mongolid-documenter skill to review and update the library behavior documentation in docs/docs so it matches the current code in src/ and tests/. Report every file changed and the code that supports each change." --allow-tool='shell, read, write'

.PHONY: docs
