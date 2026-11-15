# Laravel Forge: Site > Deployments > Deploy Script. Paste everything below this line.
# Tick "Quick deploy" on the site to deploy on every push.

cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH

export PHP_BIN=$FORGE_PHP
export COMPOSER_BIN="$FORGE_COMPOSER"
bash deploy/deploy.sh all

# Forge reloads PHP-FPM so opcache picks up the new code.
( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
