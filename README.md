# amoCRM
Интеграция с amoCRM. В процессе разработки...

Доступные на данный момент функции:
1. Прямые запросы на API amoCRM (для тех, кто любит все контролировать самостоятельно)
2. Создание/обновление контактов в amoCRM
3. Чтение созданых контактов в amoCRM
4. Создание/обновление сделок в amoCRM
5. Добавления текстового примечания к контакту/сделке
6. Создание задач (независимые или прикрепленные к контакту/сделке)
7. Укорачивание ссылок с отслеживанием перехода в интерфейсе amoCRM 
8. Обратная интеграция (добавление/удаление тегов, подписка/отписка от воронки, запуск триггеров, обновление переменных) по событиям в воронках сделок amoCRM
9. ...


Шаблон с примерами "Внешних запросов"
https://messenger.smartsender.com/t/lDJQ1df9Y2YBSpYJ5WWleoa7FHBb2mbYKMZ0SmCn


Инструкция:
1. Скачать тут архив и загрузить на хостинг
2. В настройках amoCRM создать новую "Внешнюю" интеграцию http://joxi.ru/KAxkZWLiv9Ze32
  - "https://exemple.com" - любая ссылка переадресации (необходимо будет указать в настройках п.3), используется для получения/обновления токенов доступа
  - Все остальные данные на Ваше усмотрение (не забудьте установить галочку "Предоставить доступ")
3. На хостинге открыть файл "config.php"  и указать там следующие данные (внутри кавычек) используя данные в amoCRM (скрин http://joxi.ru/J2bz0KqCgn0DD2 )
  - $amo_key - п.2 на скриншоте;
  - $amo_id - п.3 на скриншоте;
  - $amo_code - п.4 на скриншоте (действителен 20 мин. Если не успеете авторизовать скрипт, скопируйте новый код авторизации);
  - $amo_url - п.1 на скриншоте;
  - $amo_uri - ссылка переадресации, указаная при создании интеграции в первом поле;
  - $ss_token - токен проекта на Smart Sender (будет использоватся в будущем для обратной интеграции);
4. Сохранить файл.
5. Открыть браузером (как обычную страницу) файл "connect.php". Должно отобразится сообщение об успешной авторизации http://joxi.ru/a2XN1JXiljwvar
6. Импортировать шаблон
7. Использовать примеры из шаблона в Ваших воронках


В процесе работы интеграцией будут дополнительно созданы файлы "access.json" и "users.json". Не удаляйте эти файлы без уважительных на то причин.

Файл "access.json" сожержит токены доступа к Вашему amoCRM. Никому не передавайте этот файл.

Файл "users.json" содержит информацию о созданых в amoCRM контактах из Smart Sender. Благодаря этому файлу контакты обновляются, а не создаются дублирующие. Также этот файл используется для прикрепления сделки к контакту и для привязки укороченой ссылки.
