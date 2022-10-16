# Docker for development

This directory has docker related files for local development.

You can run GNU social on Docker by following commands on your local machine.

```
cd /path/to/repository
mkdir -p file/avatar
cp DOCUMENTATION/SYSTEM_ADMINISTRATORS/webserver_conf/htaccess.sample public/.htaccess

cd docker/development
docker compose up -d
```

After commands, you can open <https://localhost/installer.php> on your web browser. If you use Chromium/Google Chrome, you need to open <chrome://flags/#allow-insecure-localhost> and turn to [Enabled] for security alert.

In installer, you need to fill following database settings.

- Hostname: db
- Type: MariaDB
- Name: gnusocial
- DB username: root
- DB password: (empty)

You can remove container and named volume by following commands.

```
docker compose down
docker volume rm development_gnusocial-db
```

