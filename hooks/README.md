PHP Codesniffer Pre-Commit Hook for GIT

Author: Soenke Ruempler <soenke@ruempler.eu>
Website: http://github.com/s0enke/git-hooks

REQUIREMENTS

 * Bash
 * PHP CodeSniffer: http://pear.php.net/package/PHP_CodeSniffer/redirected


FEATURES

 * Check Coding style and forbid the commit if violations are found
 * Configuration file for Coding Standard, Path to PHPCS, Ignore List
 * Shows output in a 'less' pipe following the smart git principles


USAGE

 * Set drupal_coder as main installed path to use Drupal standards
   `./bin/phpcs --config-set installed_paths ../../drupal/coder/coder_sniffer`
 * Put the symlink to your .git/hooks directory
   ```
   cd .git/hooks
   ln -s ../../hooks/pre-commit pre-commit
   ```
 * If you want to override configuration of pre-commit hook
   rename "config.default" file and edit it.
 * Ensure that the script is executable. 
