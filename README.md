Тестовое задание для бэкенд-разработчика

Оригинал постановки задачи тут: https://github.com/s-n-o-v/meat-manufacture-test/blob/main/laravel-backend-test-description.pdf

Создать простой REST API для управления заказами в приложении
«**Мясофактура**».


Инструкция по запуску:
1. Склонировать репозиторий https://github.com/s-n-o-v/meat-manufacture-test.git
2. Открыть терминал в папке с исходным кодом проекта
3. Выполнить команду 
```
docker-compose up -d --build
```
4. Выполнить миграции
```
docker exec -it meatmanufature_app php artisan migrate
```

5. Открыть Swagger-документацию по адресу http://localhost:8080/api/documentation#/
Есть небольшая вероятность, что сама коллекция не попала в репу и потребуется пересобрать. Тогда это можно сделать командой
```
docker exec -it meatmanufature_app php artisan l5-swagger:generate
```
6. Можно запустить тесты командой 
```
docker exec -it meatmanufature_app php artisan test
```

P.S. В корне проекта находится экспорт Postman-коллекции на которой тестировались ручки...

P.P.S. Каких-то особых чувств к использованию L5 как документации я не испытваю, это просто первое, что вывалилось в рекомендации) 