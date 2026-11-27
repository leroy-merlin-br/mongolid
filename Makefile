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
ifneq ($(strip $(FROM)$(TO)),)
	@test -n "$(FROM)" -a -n "$(TO)" || { echo "Usage: make docs [FROM=<commit> TO=<commit>]"; exit 1; }
	@git rev-parse --verify --quiet "$(FROM)^{commit}" >/dev/null || { echo "Invalid FROM: $(FROM)"; exit 1; }
	@git rev-parse --verify --quiet "$(TO)^{commit}" >/dev/null || { echo "Invalid TO: $(TO)"; exit 1; }
	copilot --prompt="Use the mongolid-docs skill to review the commits between $(FROM) and $(TO). List them with 'git log --reverse --oneline $(FROM)..$(TO) -- src/ tests/', read the change of each commit with 'git show <commit> -- src/ tests/', and confirm the final state with 'git diff $(FROM)...$(TO) -- src/ tests/'. The code at $(TO) is the source of truth. Check whether any public behavior changed and update only the pages in docs/docs affected by those changes. If nothing in the docs needs to change, say so and do not edit any file. Report every file changed, the commits that motivated each change, and the code that supports it." --allow-tool='shell, read, write'
else
	copilot --prompt="Use the mongolid-docs skill to review and update the library behavior documentation in docs/docs so it matches the current code in src/ and tests/. Report every file changed and the code that supports each change." --allow-tool='shell, read, write'
endif

.PHONY: docs
