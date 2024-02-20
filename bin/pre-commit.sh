#!/bin/sh

set -e

XDEBUG_MODE=off

PROJECT_DIR="$(dirname ${0})/../../"

__docker_cmd_runner() {
  docker-compose exec -T -e XDEBUG_MODE=off php "$@"
}

CHANGED_FILES=$(git -C "$PROJECT_DIR" diff --staged --relative --name-only --diff-filter=ACMR)

if [ -z "$CHANGED_FILES" ]; then
  echo "Empty changed files list"
  exit 0;
fi

echo '\nRun PHPCS Beautifier:\n'

__docker_cmd_runner which ./vendor/bin/phpcbf
if [ $? -eq 1 ]; then
  echo "\033[41mPlease install PHPCS\033[0m"
  exit 1
fi

PHPCBF_OUTPUT=$(__docker_cmd_runner ./vendor/bin/phpcbf $CHANGED_FILES)

case $? in
  0)
    echo "\033[32mNothing found that could be fixed \033[0m"
    ;;
  1)
    echo "\033[32mPHPCBF fixed all fixable errors \033[0m"
    ;;
  2)
    echo "\033[43mSomething failed to fix. Please change your files \033[0m"
    exit 1
    ;;
esac

BEAUTIFIED_FILES=$(echo "$PHPCBF_OUTPUT" | grep 'src\|tests\|app' |  awk -v pr_dir="$PROJECT_DIR" '{print pr_dir$1}' | tr -s '\r\n' ' ');

if [ ! -z "$BEAUTIFIED_FILES" ]; then
  git add $BEAUTIFIED_FILES
fi

__docker_cmd_runner php bin/hook hook -c config/pre-commit-hook.yaml -p /app ${CHANGED_FILES}

