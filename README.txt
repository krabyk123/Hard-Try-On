HARD AI Try-On for Shop-Script

Что входит в эту редакцию:
- вывод блока примерки в карточке товара через frontend_product;
- подключение CSS/JS через frontend_head;
- frontend-роут для AJAX через lib/config/routing.php;
- JSON-контроллер генерации изображения;
- CSRF для frontend POST-запроса;
- поддержка Gemini / Custom endpoint;
- rate limit по IP в сутки;
- хранение пользовательских загрузок, результатов и scaled-копий в wa-data/public/shop/plugins/hardtryon/;
- основной лог tryon.log и отдельный debug.log в wa-data/protected/shop/plugins/hardtryon/;
- страница настроек плагина с диагностикой изображений товара и очисткой debug.log;
- CLI-очистка старых файлов.

Установка:
1. Скопируйте папку hardtryon в wa-apps/shop/plugins/
2. Очистите кэш Webasyst / Shop-Script.
3. Включите плагин в Shop-Script → Плагины.
4. Заполните настройки провайдера.

CLI:
php cli.php shop hardtryonPluginCleanup

Важно:
- после добавления или обновления routing.php очистите кэш маршрутизации;
- reference pose задаётся URL-адресом в настройках;
- debug.log пишется всегда для диагностики, tryon.log — только если включён enable_logs.
