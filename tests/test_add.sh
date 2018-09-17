#!/bin/bash

set -e

docker run -v $(pwd)/..:/app \
    --rm \
    -v $(pwd):/aenthill \
    -v "/var/run/docker.sock:/var/run/docker.sock" \
    -e PHEROMONE_ID=750083dad70da58f437a9168c944cf51 \
    -e PHEROMONE_IMAGE_NAME=theaentmachine/aent-bootstrap:snapshot \
    -e PHEROMONE_FROM_CONTAINER_ID= \
    -e PHEROMONE_CONTAINER_PROJECT_DIR=/aenthill \
    -e PHEROMONE_HOST_PROJECT_DIR=$(pwd) \
    -e PHEROMONE_LOG_LEVEL=DEBUG \
    -ti \
    theaentmachine/base-php-aent:0.0.24 \
    php /app/src/aent.php ADD