# Ploi: Site > Deployments > Deploy script. Paste everything below this line.
# Enable "Deploy on push" under Repository to deploy on every push.

cd {SITE_DIRECTORY}
git pull origin {BRANCH}

export PHP_BIN={PHP}
bash deploy/deploy.sh all

{RELOAD_PHP_FPM}
