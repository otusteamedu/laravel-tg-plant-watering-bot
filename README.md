# Laravel Telegram Bot for Plant Watering
## Запуск
* Установить Composer — https://getcomposer.org/
* Установить Docker — https://www.docker.com/products/docker-desktop/
* Скопировать файл `.env.example` в `.env` и задать нужные значения (как минимум, для бота)
* Выполнить следующие команды

```shell
composer install

./vendor/bin/sail up -d
```

### Получение сообщений от Telegram
#### Через Webhook
Будет работать только при наличии домена и TLS-сертификата
```shell
./vendor/bin/sail artisan telegram:webhook --setup
```
#### Через Polling
```shell
./vendor/bin/sail artisan app:get-telegram-updates
```

### Обработка фоновых задач
```shell
./vendor/bin/sail artisan queue:work
```

### Обработка ответов от RPC
```shell
./vendor/bin/sail artisan app:subscribe-to-rpc-messages
```

## Arduino
Работоспособность проверялась на NodeMCU 0.9 на базе ESP8266.
### Запуск
* Скачать Arduino IDE
* Создать проект с файлами из каталога `arduino`
* Скопировать `secrets.h.example` -> `secrets.h`, указать свои значения для подключения к WiFi
  * NodeMCU 0.9 работает только с 2.4 ГГц
  * Логин/пароль MQTT по умолчанию — user/1234


# Контакты
По всем вопросам — [@brzhkv](https://t.me/brzhkv)
