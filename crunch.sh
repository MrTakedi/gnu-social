docker-compose down
docker stop `docker container ls -aq`
docker rm `docker container ls -aq`
docker rmi `docker image ls -aq`
docker system prune
docker system prune --volumes
docker container ls -a
docker container prune
