# =============================================================================
# Sangho SDK PHP — Makefile
# =============================================================================
# Usage : make <target>
# Prérequis : PHP 8.2+, composer, git
# =============================================================================

.DEFAULT_GOAL := help
.PHONY: help install test test-filter test-coverage test-integration \
        lint format analyse check build clean \
        version-patch version-minor version-major changelog \
        publish release-patch release-minor release-major \
        _bump _git-tag-and-push _packagist-ping info

# Détection automatique Windows vs Unix
ifeq ($(OS),Windows_NT)
    PHP      := php
    COMPOSER := composer
    PHPUNIT  := vendor\bin\phpunit.bat
    PHPCS    := vendor\bin\phpcs.bat
    PHPCBF   := vendor\bin\phpcbf.bat
    PHPSTAN  := vendor\bin\phpstan.bat
    RM       := rmdir /s /q
    SEP      := \\
else
    PHP      := php
    COMPOSER := composer
    PHPUNIT  := vendor/bin/phpunit
    PHPCS    := vendor/bin/phpcs
    PHPCBF   := vendor/bin/phpcbf
    PHPSTAN  := vendor/bin/phpstan
    RM       := rm -rf
    SEP      := /
endif

RESET  := \033[0m
BOLD   := \033[1m
GREEN  := \033[32m
YELLOW := \033[33m
CYAN   := \033[36m

VERSION  := $(shell $(PHP) -r "echo json_decode(file_get_contents('composer.json'))->version;" 2>/dev/null || echo "0.0.0")

# -----------------------------------------------------------------------------
# AIDE
# -----------------------------------------------------------------------------
help: ## Affiche cette aide
	@echo ""
	@echo "$(BOLD)$(CYAN)Sangho SDK PHP v$(VERSION)$(RESET)"
	@echo "$(CYAN)━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━$(RESET)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(RESET) %s\n", $$1, $$2}'
	@echo ""

# -----------------------------------------------------------------------------
# INSTALLATION
# -----------------------------------------------------------------------------
install: ## Installe les dépendances
	@echo "$(CYAN)→ Installation des dépendances...$(RESET)"
	$(COMPOSER) install

# -----------------------------------------------------------------------------
# TESTS
# -----------------------------------------------------------------------------
test: ## Lance les tests unitaires
	@echo "$(CYAN)→ Tests...$(RESET)"
	$(PHPUNIT) --testsuite "Sangho PHP SDK" --exclude-group integration

test-filter: ## Lance un test précis (make test-filter FILTER=testX)
	$(PHPUNIT) --filter=$(FILTER)

test-coverage: ## Lance les tests avec rapport de couverture
	@echo "$(CYAN)→ Tests + couverture...$(RESET)"
	$(PHPUNIT) --exclude-group integration --coverage-html coverage/ --coverage-text
	@echo "$(GREEN)✓ Rapport généré dans ./coverage$(RESET)"

test-integration: ## Tests intégration sandbox (nécessite SANGHO_TEST_SECRET_KEY)
	@echo "$(CYAN)→ Tests intégration sandbox...$(RESET)"
	$(PHPUNIT) --group integration
	@echo "$(GREEN)✓ Tests intégration OK$(RESET)"

# -----------------------------------------------------------------------------
# QUALITÉ DU CODE
# -----------------------------------------------------------------------------
lint: ## Lint du code source (PSR-12)
	@echo "$(CYAN)→ Lint...$(RESET)"
	$(PHPCS) --standard=PSR12 lib/ tests/

format: ## Corrige automatiquement le style (PSR-12)
	@echo "$(CYAN)→ Format...$(RESET)"
	$(PHPCBF) --standard=PSR12 lib/ tests/
	@echo "$(GREEN)✓ Style corrigé$(RESET)"

analyse: ## Analyse statique (PHPStan niveau 8, cf. phpstan.neon + baseline)
	@echo "$(CYAN)→ Analyse statique...$(RESET)"
	$(PHPSTAN) analyse
	@echo "$(GREEN)✓ Analyse OK$(RESET)"

check: lint analyse ## Lint + analyse statique (pipeline qualité)
	@echo "$(GREEN)✓ Qualité OK$(RESET)"

# -----------------------------------------------------------------------------
# BUILD
# -----------------------------------------------------------------------------
build: clean install ## Build de l'archive distribuable
	@echo "$(CYAN)→ Build en cours...$(RESET)"
	$(COMPOSER) archive --format=zip --dir=dist
	@echo "$(GREEN)✓ Build terminé$(RESET)"
	@ls -lh dist/ 2>/dev/null || true

# -----------------------------------------------------------------------------
# NETTOYAGE
# -----------------------------------------------------------------------------
clean: ## Supprime vendor/, dist/, coverage/ et les caches
ifeq ($(OS),Windows_NT)
	if exist vendor  $(RM) vendor
	if exist dist    $(RM) dist
	if exist coverage $(RM) coverage
	if exist composer.lock del composer.lock
	if exist .phpunit.result.cache del .phpunit.result.cache
else
	$(RM) vendor/ dist/ coverage/ composer.lock .phpunit.result.cache .phpunit.cache
endif

# -----------------------------------------------------------------------------
# VERSIONING (Semantic Versioning)
# -----------------------------------------------------------------------------
version-patch: check test ## Bump patch version (1.1.0 → 1.1.1)
	@$(MAKE) _bump PART=patch

version-minor: check test ## Bump minor version (1.1.0 → 1.2.0)
	@$(MAKE) _bump PART=minor

version-major: check test ## Bump major version (1.1.0 → 2.0.0)
	@$(MAKE) _bump PART=major

_bump: ## (Interne) Bump la clé "version" de composer.json + HttpClient::SDK_VERSION
	@echo "$(CYAN)→ Bump $(PART)...$(RESET)"
	@$(PHP) -r "\
	\$$composer = json_decode(file_get_contents('composer.json'), true); \
	[\$$major, \$$minor, \$$patch] = array_map('intval', explode('.', \$$composer['version'])); \
	\$$new = match ('$(PART)') { \
	    'major' => [\$$major + 1, 0, 0], \
	    'minor' => [\$$major, \$$minor + 1, 0], \
	    default => [\$$major, \$$minor, \$$patch + 1], \
	}; \
	\$$newVersion = implode('.', \$$new); \
	\$$composer['version'] = \$$newVersion; \
	file_put_contents('composer.json', json_encode(\$$composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . \"\n\"); \
	\$$httpClient = file_get_contents('lib/HttpClient.php'); \
	\$$httpClient = preg_replace('/SDK_VERSION = \'[^\']+\'/', \"SDK_VERSION = '{\$$newVersion}'\", \$$httpClient); \
	file_put_contents('lib/HttpClient.php', \$$httpClient); \
	echo \$$newVersion;"
	@echo "$(GREEN)✓ Nouvelle version : $(shell $(PHP) -r "echo json_decode(file_get_contents('composer.json'))->version;")$(RESET)"

changelog: ## Rappelle de documenter la release dans CHANGELOG.md
	@echo "$(YELLOW)⚠ Ajoutez une entrée dans CHANGELOG.md avant de release.$(RESET)"

# -----------------------------------------------------------------------------
# PUBLICATION Packagist
# -----------------------------------------------------------------------------
# Packagist n'a pas de commande "publish" : un package déjà enregistré se
# resynchronise automatiquement via le webhook GitHub à chaque push de tag.
# `make publish` ne fait qu'accélérer cette synchronisation via l'API
# Packagist (utile en CI, où on ne veut pas attendre le webhook).
publish: ## Force la resynchronisation Packagist via son API (webhook = automatique sinon)
	@$(MAKE) _packagist-ping

_packagist-ping:
	@echo "$(CYAN)→ Ping Packagist...$(RESET)"
	@curl -s -XPOST -H "content-type:application/json" \
		"https://packagist.org/api/update-package?username=$(PACKAGIST_USERNAME)&apiToken=$(PACKAGIST_API_TOKEN)" \
		-d '{"repository":{"url":"https://github.com/sangho-sdks/sangho.php"}}' \
		&& echo "$(GREEN)✓ Packagist notifié$(RESET)"

# -----------------------------------------------------------------------------
# RELEASE COMPLÈTE (versioning + git tag + publish)
# -----------------------------------------------------------------------------
release-patch: ## Release patch complète (bump + tag + publish)
	$(MAKE) version-patch
	$(MAKE) _git-tag-and-push
	$(MAKE) publish

release-minor: ## Release minor complète (bump + tag + publish)
	$(MAKE) version-minor
	$(MAKE) _git-tag-and-push
	$(MAKE) publish

release-major: ## Release major complète (bump + tag + publish)
	$(MAKE) version-major
	$(MAKE) _git-tag-and-push
	$(MAKE) publish

_git-tag-and-push:
	$(eval NEW_VERSION := $(shell $(PHP) -r "echo json_decode(file_get_contents('composer.json'))->version;"))
	@echo "$(CYAN)→ Git commit + tag v$(NEW_VERSION)...$(RESET)"
	git add composer.json lib/HttpClient.php CHANGELOG.md
	git commit -m "chore: release v$(NEW_VERSION)"
	git tag -a "v$(NEW_VERSION)" -m "Release v$(NEW_VERSION)"
	git push origin main --tags
	@echo "$(GREEN)✓ Tag v$(NEW_VERSION) poussé$(RESET)"

# -----------------------------------------------------------------------------
# INFOS
# -----------------------------------------------------------------------------
info: ## Affiche les infos du SDK
	@echo "$(BOLD)Package :$(RESET) sangho/sangho"
	@echo "$(BOLD)Version :$(RESET) $(VERSION)"
	@echo "$(BOLD)PHP     :$(RESET) $(shell $(PHP) --version | head -n1)"
	@echo "$(BOLD)Composer:$(RESET) $(shell $(COMPOSER) --version 2>/dev/null)"
