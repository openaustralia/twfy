# PLATFORM=linux/arm64,linux/amd64
PLATFORM=linux/amd64

all:
	@echo "Available targets:"
	@echo "  docker-build   Build the Docker image for the application"
	@echo "  docker-run     Run the Docker container for the application"
	@echo "  lint           Run linting on the www directory"

docker-build:
	docker buildx build \
		--build-arg VCS_REF=`git rev-parse --short HEAD` \
		--build-arg VCS_URL=`git config --get remote.origin.url | sed 's#git@github.com:#https://github.com/#'` \
		--build-arg BUILD_DATE=`date -u +"%Y-%m-%dT%H:%M:%SZ"` \
		--no-cache \
		--load \
		--platform=$(PLATFORM) \
		-t twfy-app \
		.

docker-run:
	docker compose up -d

docker: docker-build docker-run

lint:
	./vendor/bin/phpcs --report=summary www scripts

lint-ci:
	./vendor/bin/phpcs www scripts --report=json --report-file=phpcs-report.json
