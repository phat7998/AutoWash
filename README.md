# AutoWash
docker build -t autowash-test .

docker run --rm -p 8000:8000 -v "${PWD}:/app" -v /app/vendor autowash-test php -S 0.0.0.0:8000 -t public

http://localhost:8000