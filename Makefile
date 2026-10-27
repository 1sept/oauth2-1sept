RELEASE = oauth2-1sept

# Отдельного поля версии в проекте нет: текущая версия — последний тег,
# а релиз — это новый тег (Packagist берёт версию из тегов GitHub).
VERSION := $(shell git describe --tags --abbrev=0 2>/dev/null | sed 's/^v//')

default: help

help:
	@echo "Разработка:"
	@echo "  make test        - Прогнать проверки: тесты и статический анализ"
	@echo "  make phpunit     - Только тесты"
	@echo "  make phpstan     - Только статический анализ"
	@echo "  make lint        - Проверить стиль кода (php-cs-fixer)"
	@echo "  make lint-fix    - Исправить стиль кода"
	@echo "  make update      - Обновить зависимости и прогнать проверки"
	@echo ""
	@echo "Релиз (последний тег: $(VERSION)):"
	@echo "  make check          - Полный прогон перед релизом"
	@echo "  make release-patch  - Тег следующей patch-версии и push в origin"
	@echo "  make release-minor  - То же для minor"
	@echo "  make release-major  - То же для major"
	@echo "  make release-abort  - Убрать локальный тег, который не ушёл в origin"
	@echo ""
	@echo "Перед релизом нужна секция в CHANGELOG.md для новой версии."

test:
	composer test

phpunit:
	composer phpunit

phpstan:
	composer phpstan

lint:
	composer lint

lint-fix:
	composer lint-fix

update:
	composer update
	$(MAKE) test

check:
	composer validate --strict
	composer lint
	composer test

# -------------------------------------------------------------------- release

guard-clean:
	@test -z "$$(git status --porcelain)" || { \
		echo "refusing: в рабочем дереве есть незакоммиченные изменения"; \
		git status --short; exit 1; }

# Тег ставится на HEAD, поэтому всё, что должно попасть в релиз, должно быть закоммичено.
# CHANGELOG проверяется до тега: версия без секции — это версия без описания изменений.
# Push идёт с --follow-tags, чтобы коммиты и тег уехали вместе.
release-patch release-minor release-major: guard-clean check
	@level="$(patsubst release-%,%,$@)"; \
	cur="$(VERSION)"; \
	test -n "$$cur" || { echo "refusing: тегов нет, версию не от чего считать"; exit 1; }; \
	next=$$(echo "$$cur" | awk -F. -v l="$$level" '{ \
		if (l == "major") print $$1 + 1 ".0.0"; \
		else if (l == "minor") print $$1 "." $$2 + 1 ".0"; \
		else print $$1 "." $$2 "." $$3 + 1 }'); \
	tag="v$$next"; \
	git ls-remote --exit-code --tags origin "refs/tags/$$tag" >/dev/null 2>&1 \
		&& { echo "refusing: тег $$tag уже есть в origin"; exit 1; }; \
	git rev-parse -q --verify "refs/tags/$$tag" >/dev/null \
		&& { echo "refusing: тег $$tag уже есть локально"; exit 1; }; \
	grep -qE "^## $$next([^0-9]|$$)" CHANGELOG.md \
		|| { echo "refusing: в CHANGELOG.md нет секции «## $${next}»"; exit 1; }; \
	git tag -a "$$tag" -m "$$next" || exit 1; \
	git push --follow-tags || exit 1; \
	echo "released $(RELEASE) $$tag"

# Отменяет релиз, который не ушёл в origin: снимает локальный тег последней версии.
# Тег, который уже в origin, не трогаем — его нельзя переписать у тех, кто его забрал.
release-abort: guard-clean
	@test -n "$(VERSION)" || { echo "refusing: локальных тегов нет"; exit 1; }; \
	git ls-remote --exit-code --tags origin "refs/tags/v$(VERSION)" >/dev/null 2>&1 \
		&& { echo "refusing: v$(VERSION) уже есть в origin"; exit 1; }; \
	git tag -d "v$(VERSION)"

.PHONY: help test phpunit phpstan lint lint-fix update check guard-clean \
	release-patch release-minor release-major release-abort
