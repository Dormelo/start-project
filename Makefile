SHELL:=/bin/bash
MAKE_S = $(MAKE) -s
MAKEFLAGS += --silent --ignore

ifneq ($(shell docker --version > /dev/null 2>&1 ; echo $$?), 0)
    $(error ************  DOCKER NOT RUNNING  ************)
endif

# Executables (local)
DOCKER_COMP = docker compose

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec app
PHPRUN_CONT = $(DOCKER_COMP) run --rm app
FRONTEND_CONT = $(DOCKER_COMP) run --rm frontend
FRONTEND_OLD_CONT = $(DOCKER_COMP) run --rm frontend-old
ENCORE_CONT = $(DOCKER_COMP) run --rm encore

# Executables
PHP      = $(PHP_CONT) php
ENCORE   = $(ENCORE_CONT) npm
FRONTEND = $(FRONTEND_CONT) npm
FRONTEND_OLD = $(FRONTEND_OLD_CONT) npm
COMPOSER = $(PHPRUN_CONT) composer
SYMFONY  = $(PHP) bin/console

URL_WEBSITE = http://localhost:8080
URL_WEBSITE_OLD = http://localhost:8081
URL_ADMIN = http://localhost:8000/admin
URL_API = http://localhost:8000/api/doc
URL_ADMINER = http://localhost:8282/?pgsql=database&username=postgresdev&db=adopteunepouledb&ns=public&select=address

# Misc
.DEFAULT_GOAL = help
.PHONY        : help clean build up install down logs back.sh front.sh composer assets sf.npm vue.npm vendor sf cc test remove
.SILENT       : ready cancelled acl data.create data.drop data.fixtures data.reset

install: build up data.reset jwt tools git.hooks

reset: clean remove ## Hard reset project ⚠️

dependencies: assets vendor ## Install assets and vendors Frontend / Backend

reload:down up ## Reload project

ready:
	@echo -e "\033[1;42m"
	@echo -e "\033[1;42m"
	@echo -e '   Website: 		\e]8;;$(URL_WEBSITE)\a$(URL_WEBSITE)\e]8;;\a'
	@echo -e '   Website (old):	\e]8;;$(URL_WEBSITE_OLD)\a$(URL_WEBSITE_OLD)\e]8;;\a'
	@echo -e '   Admin:   		\e]8;;$(URL_ADMIN)\a$(URL_ADMIN)\e]8;;\a'  
	@echo -e '   API:     		\e]8;;$(URL_API)\a$(URL_API)\e]8;;\a'  
	@echo -e '   Adminer: 		\e]8;;$(URL_ADMINER)\ahttp://localhost:8282\e]8;;\a'  
	@echo -e "\033[1;00m"
	@echo -e "\033[1;00m"

cancelled:
	@echo -e "\033[1;41m  Action cancelled.  \033[0m"
	exit 1

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images
	@$(DOCKER_COMP) build --pull --no-cache
	$(MAKE_S) acl

up: ## Start the docker hub in detached mode (no logs)
	@$(DOCKER_COMP) up --detach
	$(MAKE_S) ready

down: ## Stop the docker hub
	@$(DOCKER_COMP) down --remove-orphans

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

back.sh: ## Connect to the Backend container
	@$(PHPRUN_CONT) bash

front.sh: ## Connect to the Frontend container
	@$(FRONTEND_CONT) sh

front.old.sh: ## Connect to the Frontend container
	@$(FRONTEND_OLD_CONT) sh

# Pass the parameter "c=" to add options to phpunit, example: make test c="--group e2e --stop-on-failure"
test: ## Start tests with phpunit (Pass the parameter "c=" to run a given command)
	@$(eval c ?=)
	@$(DOCKER_COMP) exec -e APP_ENV=test app bin/phpunit $(c)

remove: ## [PROMPT yN] Stop containers and remove containers, networks, volumes, and images created by up. ⚠️
	@while [ -z "$$CONTINUE" ]; do \
		read -r -p "Stop containers and remove containers, networks, volumes, and images created by up? [yN] " CONTINUE; \
	done ; \
	if [ $$CONTINUE == "y" ]; \
	then \
		docker compose down --remove-orphans --volumes; \
		echo -e "\033[1;42mContainers, networks, volumes, and images created by up removed\033[0m"; \
		docker compose rm --stop -v; \
		echo -e "\033[1;42mStopped service containers removed\033[0m"; \
		docker system prune --volumes; \
		echo -e "\033[1;42mUnused data removed\033[0m"; \
	else \
		$(MAKE_S) cancelled; \
	fi; \

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
# Pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
composer: ## Run composer (Pass the parameter "c=" to run a given command)
	@echo -e "\033[1;42mConnect to composer Backend\033[0m"
	@$(eval c ?=)
	@$(COMPOSER) $(c)
	$(MAKE_S) acl

vendor: ## Install vendors according to the current composer.lock file
vendor: c=install --prefer-dist --no-progress --no-interaction
vendor: composer acl

## —— Node 🧁 ——————————————————————————————————————————————————————————————
# Pass the parameter "c=" to run a given command, example: make sf.npm c='install jquery'
sf.npm: ## Run node to the Symfony project (Pass the parameter "c=" to run a given command)
	@echo -e "\033[1;42mConnect to node Backend\033[0m"
	@$(eval c ?=)
	@$(ENCORE) $(c)
	$(MAKE_S) acl

# Pass the parameter "c=" to run a given command, example: make vue.npm c='install @vue/cli'
vue.npm: ## Run node to the Vue project (Pass the parameter "c=" to run a given command)
	@echo -e "\033[1;42mConnect to node Frontend\033[0m"
	@$(eval c ?=)
	@$(FRONTEND) $(c)
	$(MAKE_S) acl

vue.old.npm: ## Run node to the Vue project (Pass the parameter "c=" to run a given command)
	@echo -e "\033[1;42mConnect to node Frontend Old\033[0m"
	@$(eval c ?=)
	@$(FRONTEND_OLD) $(c)
	$(MAKE_S) acl

assets: ## Install assets to the Symfony/Vue Project
assets: c=install
assets: sf.npm
assets: vue.npm
assets: vue.old.npm

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
# Pass the parameter "c=" to run a given command, example: make sf c=about
sf: ## List all Symfony commands (Pass the parameter "c=" to run a given command)
	@$(eval c ?=)
	@$(SYMFONY) $(c)

cc: c=c:c ## Clear the cache
cc: sf

# Pass the parameter "c=" to run a given command, example: make jwt c=--overwrite
jwt: ## Generate public/private keys for use in your application. (Pass the parameter "c=" to run a given command)
	@$(eval c ?=)
	@$(SYMFONY) lexik:jwt:generate-keypair $(c)

## —— Doctrine 💾 ———————————————————————————————————————————————————————————————
data.reset: ## Reset Database and load fixtures
	@while [ -z "$$CONTINUE" ]; do \
		read -r -p "Drop, Create new database with fixtures ? [yN] " CONTINUE; \
	done ; \
	if [ $$CONTINUE == "y" ]; \
	then \
		$(MAKE_S) data.drop; \
		$(MAKE_S) data.create; \
		$(MAKE_S) data.fixtures; \
	else \
		$(MAKE_S) cancelled; \
	fi; \

data.create: ## Create Database
	@$(SYMFONY) doctrine:database:create --if-not-exists
	@$(SYMFONY) doctrine:migrations:migrate --no-interaction

data.fixtures: ## Purge fixtures
	@$(SYMFONY) doctrine:fixtures:load --no-interaction

data.drop: ## Drop Database
	@$(SYMFONY) doctrine:database:drop --if-exists --force

## —— Tools 🛠️ ———————————————————————————————————————————————————————————————

tools: symfony/tools/* ## Install all tools
	$(PHPRUN_CONT) bash -c 'for tool in /app/tools/*; do echo -e "\033[1;43mComposer: Install $${tool}\033[0m"; composer install --working-dir=$${tool}; done'
	
cs: ## Run coding standard and shows which files would have been modified.
	$(PHP) bin/php-cs-fixer fix --dry-run

cs.fix: ## Run command tries to fix as much coding standards
	$(PHP) bin/php-cs-fixer fix

phpstan: ## Run code quality
	$(PHP) vendor/bin/phpstan analyse

git.hooks: ## activate git hooks
	git config core.hooksPath .git_hooks

## —— Miscellaneous 💡 ———————————————————————————————————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

clean: ## [PROMPT yN] Remove build, vendor & node_modules folders ⚠️
	@while [ -z "$$CONTINUE" ]; do \
		read -r -p "Remove build, vendor & node_modules folders? [yN] " CONTINUE; \
	done ; \
	if [ $$CONTINUE == "y" ]; \
	then \
		$(MAKE_S) acl; \
		rm -rf vue/public/build vue/node_modules vue_old/public/build vue_old/node_modules symfony/vendor symfony/node_modules symfony/public/build symfony/public/bundles; \
		echo -e "\033[1;42m  Build, vendor & node_modules removed  \033[0m"; \
	else \
		$(MAKE_S) cancelled; \
	fi; \

acl: ## To set yourself as owner of the project files that were created by the docker container
	$(DOCKER_COMP) run --rm app chown -R $(shell id -u):$(shell id -g) .
	$(DOCKER_COMP) run --rm frontend chown -R $(shell id -u):$(shell id -g) .
	$(DOCKER_COMP) run --rm frontend-old chown -R $(shell id -u):$(shell id -g) .