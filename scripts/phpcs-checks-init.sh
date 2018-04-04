#!/bin/sh
# Makes settings to check new changes by phpcs in pre-commit git hook
GIT_ROOT=$(git rev-parse --show-toplevel)
GIT_HOOKS_DIR=$GIT_ROOT/.git/hooks

# Setup Drupal coding standards as default.
$GIT_ROOT/bin/phpcs --config-set installed_paths ../../drupal/coder/coder_sniffer

# Make a symlink into .git/hooks folder to pre-commit
if [ ! -d "$GIT_HOOKS_DIR" ]; then
  mkdir $GIT_HOOKS_DIR
fi
sh -c "cd $GIT_HOOKS_DIR && ln -s -f ../../hooks/pre-commit.sh pre-commit"
