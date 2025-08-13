<?php
/*
TODO:
1. TOOLS::excel->...
2. Добавить несколько шаблонов на все случаи жизни
3. Сделать файлы test.php для быстрой разработки роботов
*/

//
// NOTE: Пользовательские настройки робота находятся в:
// Плагины / Код / Редактор / Редактирование настроек
//

// --- --- --- --- ---
// Настройки робота (базовые/внутренние): начало
// --- --- --- --- ---

// RPAbot: IP адрес и номер порта.
// Обычно, можно не редактировать.
$xhe_host = "127.0.0.1:7012";

// RPAbot: пароль для подключения (если указан пароль) к РПАботу.
// Если без пароля, то оставить пустую строку "".
$server_password = "";

// RPAbot: рабочая дирректория робота (PHP).
// Рабочая дирректория для РПАбот получается из `WINDOW::$debug->get_cur_script_folder()`
// (это всегда дирректория, где запускаемый файл).
// Не редактировать.
chdir(__DIR__);

// Подключение RPAbot PHP-шаблона.
// Обычно, можно не редактировать.
// Примеры:
// Полный путь: "C:\\BizApps\\RPAbot Studio\\Templates\\init.php"
// Полный путь: "C:/BizApps/rpabot_studio_beta/Templates/init.php"
// Относительный путь: "../../Templates/init.php"
// Относительный путь: "../Templates/init.php"
require_once("../../Templates/init.php");
$bUTF8Ver = true;

echo "Старт работы";

// Часовой пояс
// Europe/Moscow: Москва
// Asia/Yekaterinburg: Екатеринбург
date_default_timezone_set('Europe/Moscow');

// Логировать в Панель Отладки (stdout)
// true: логировать
// false: не логировать
$dbg = true;

// наши таймауты
$wt = 1;
$wt_long = 5;

// для рестарта из robot.php
$restartRobotPath = WINDOW::$debug->get_cur_script_path();
$robotFolder = WINDOW::$debug->get_cur_script_folder();

// Лог в файл
// null: не логировать в файл
// 'путь к файлу': Расширение файла д. б. txt или html, пример:
//    "log/log_" . date("Y-m-d") . ".txt";
$debug_file = WINDOW::$debug->get_cur_script_folder() . "log\\log_" . date("Y-m-d") . ".txt";

// Делать ли автоматическую транслитерацию текста для Панели Отладки?
// Для лог-файла эта настройка не учитывается.
// Полезно для случаев когда в Панели Отладки проблемы с отображением/кодировкой/локализацией.
// true: да
// false: нет
$debug_panel_translit_text = false;

// Тип остановки робота
// quit: просто остановить робота, не закрывать RPAbot платформу (обычно, удобно для отладки)
// exitapp: остановить робота и выключить RPAbot платформу
// restart_and_quit: остановить робота и перезапустить RPAbot платформу
$appQuitType = "quit";

// Путь к файлу настроек
$settingsFilePath = WINDOW::$debug->get_cur_script_folder() . "settings\\settings.json";

// --- --- --- --- ---
// Настройки робота (базовые/внутренние): конец
// --- --- --- --- ---

// --- --- --- --- ---
// Дополнительные модули для работы робота
// --- --- --- --- ---

require_once(__DIR__ . "/tools/robotInit.php");

// --- --- --- --- ---
// Дополнительные преднастройки для робота и RPAbot платформы
// --- --- --- --- ---

// Название шаблона настроек, автоматически извлекается из settings.json
$settingsName = false;
// мой массив с настройками (true - значит записать в переменную)
$mySettings = true;
// Обрабатываем настройки из settings/settings.json

SETTINGS::$settings->selfConfigure($settingsName, $settingsFilePath, $mySettings);

// Логируем PHP-ошибки в отдельный файл (log/PHP_ERRORS_LOG.txt)
startHandlePhpErrors();

// Убираем ограничения по памяти для PHP
ini_set('memory_limit', -1);

// Активируем (true) функционал для common_dom.wait_element_exist_by_*
$bWaitElementExistBeforeAction = true;

// Отключаем логирование сетевых запросов РПАботом
$raw->enable_all_streams(false);
$raw->clear_disabled_response_urls_array();
$raw->clear_disabled_request_urls_array();

// RPAbot unicode mode
$app->set_script_as_unicode(true);
// учитывать регистр символов по-умолчанию
$app->set_params_object_search(true);
// показать иконку в трее
$app->show_tray_icon(true);

// --- --- --- --- ---
// Robocode
// --- --- --- --- ---
TOOLS::$robot->run();

if ($appQuitType === 'exitapp') { $app->exitapp(); } else { $app->quit(); }
