# Docker for localhost

This directory has docker related files for local development.

You can run GNU social on Docker by following commands on your local machine.

```
ROOT=/path/to/repository
cd $ROOT
mkdir -p file/avater
cp DOCUMENTATION/SYSTEM_ADMINISTRATORS/webserver_conf/htaccess.sample public/.htaccess

cd docker/localhost
docker-compose build
docker-compose up -d
```

After run, you can open <https://localhost/installer.php> on your web browser. If you use Chromium/Google Chrome, you need to open <chrome://flags/#allow-insecure-localhost> and turn to [Enabled] for security alert.

In installer, you need to fill following database settings.

- Hostname: db
- Type: MariaDB
- Name: gnusocial
- DB username: root
- DB password: (empty)

You can remove container following command (DB is also removed due to anonymous volume).

```
docker-compose down
```

