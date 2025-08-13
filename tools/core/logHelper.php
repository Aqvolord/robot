<?php

/**
 * Функция для логирования в Панель Отладки (stdout) и в файл (txt, html),
 * log_level игнорируется:
 *  - debug_mess(mixed $mess, bool $isBold = false)
 * 
 * Методы для логирования в Панель Отладки (stdout) и в файл (txt, html):
 *  - TOOLS::$log->debug(mixed $msg, string $componentName = '', bool $isBold = false)
 *  - TOOLS::$log->info(mixed $msg, string $componentName = '', bool $isBold = false)
 *  - TOOLS::$log->warn(mixed $msg, string $componentName = '', bool $isBold = false)
 *  - TOOLS::$log->error(mixed $msg, string $componentName = '', bool $isBold = false)
 * 
 * Если лог-сообщение не строка, то будет автоматически преобразовано
 * в строку (var_export)
 * 
 * Использующиеся глобальные переменные:
 *   - bool $dbg Логировать в Панель Отладки (stdout), если true
 *   - string|null $debug_file Логировать в файл, если указан путь к файлу
 *   - int $log_level Уровень логирования [1..4]
 *   - bool $debug_panel_translit_text Транслитерировать ли лог сообщение для Панели Отладки
 * 
 * Место вызова (компонент) используется для базовой идентификации
 * места, где была вызвана логирующая функция.
 * Что в место вызова (компонент) писать?
 *   - пустая строка: ""
 *   - класс и метод: "Giszhkh.login_as"
 *   - название функции: "giszhkh_login_if_needed"
 *   - внутренняя подветка в функции/методе: "Giszhkh.login_as.check_login_result"
 * 
 * Примеры использования:
 *   ```
 *   debug_mess("[{$xhe_host}] Робот начал работу", true);
 *   debug_mess("debug_mess: тест, log_level в debug_mess не учитывается, log_level={$log_level}");
 *   debug_mess("debug_mess: пример сообщения, которое краснеет из-за слова ошибка");
 *   debug_mess([1, "test", (new XHEBaseObject('test')), 'test_key' => 123,]);
 * 
 *   TOOLS::$log->debug("log_debug: тест, пустой компонент");
 *   TOOLS::$log->debug("log_debug: тест, тестовый компонент", "тестовый компонент");
 *   TOOLS::$log->info("log_info: тест, с компонентом main", "main");
 *   TOOLS::$log->warn("log_warn: тест", "Giszhkh.login_as");
 *   TOOLS::$log->error("log_error: тест", "HHRU.display");
 *   TOOLS::$log->error([1, "test", (new XHEBaseObject('test')), 'test_key' => 123,],
 *       "test2");
 *   ```
 */

/**
 * Логировать сообщение в Панель Отладки (stdout) и в файл (txt, html),
 * уровень логирования (log_level) не учитывается, место
 * вызова (компонент) не используется.
 * 
 * В лог-сообщение автоматически добавляется: метка времени, красный
 * цвет шрифта для текста (если есть в тексте лог-сообщения
 * подстрока `ошибка`)
 * 
 * Если лог сообщение не string, то автоматически преобразуется
 * в строку (var_export)
 * 
 * Использует глобальные переменные:
 *   - bool $dbg Логировать в Панель Отладки (stdout), если true
 *   - string|null $debug_file Логировать в файл, если указан путь к файлу
 *   - bool $debug_panel_translit_text Транслитерировать ли лог сообщение для Панели Отладки
 * 
 * @param mixed $msg Лог сообщение, автоматически преобразуется (var_export) в строку
 * @param bool $isBold Выделять "жирным" сообщение или нет (по-умолчанию - нет)
 * @return void
 */
function debug_mess($msg, bool $isBold = false): void
{
	global $dbg, $debug_file;
	global $textfile, $folder;
	global $debug_panel_translit_text;

	_logGlobalLogVarsInitIfNeeded();

	$currentDateTime = date("Y-m-d H:i:s");

	// расширение лог файла
	$tmp = explode('.', @trim($debug_file));
	$tmp = mb_strtolower(@trim(@$tmp[count($tmp) - 1]));
	$debugFileExt = $tmp; // "html" or "txt"
	if ($debugFileExt === "htm") { $debugFileExt = 'html'; }
	if ($debugFileExt === "log") { $debugFileExt = 'txt'; }
	if ( ($debugFileExt !== 'txt') and ($debugFileExt !== 'html') ) { $debugFileExt = 'txt'; }

	// Конвертим в строку лог сообщение
	if (!is_string($msg)) { $msg = trim(var_export($msg, true)); }
	else { $msg = trim($msg); }

	// Содержит ли лог сообщение флаг об ошибке
	$msgErrorFlag = false;
	$tmp = mb_strtolower($msg);
	if (mb_strpos($tmp, "ошибка") !== false) { $msgErrorFlag = true; }
	$tmp = null;

	// Многострочная лог строка?
	$newLineFlag = false;
	if (preg_match('/\R/', $msg) === 1) { $newLineFlag = true; }

	// log to stdout or debug panel
	if ( isset($dbg) and ($dbg === true) )
	{
		$str = "[{$currentDateTime}] ";
		if ($newLineFlag) { $str = $str . '<pre>' . $msg . '</pre>'; }
		else { $str = $str . $msg; }

		if ($msgErrorFlag) { $str = '<font color="red">' . $str . '</font>'; }

		if ($isBold) { $str = '<b>' . $str . '</b>'; }

		if ($debug_panel_translit_text) { $str = translitText($str); }
		$str = $str . PHP_EOL;
		echo $str;
		$str = null;
	}

	// log to file
	if ( isset($debug_file) and is_string($debug_file) and (trim($debug_file) !== '') )
	{
		$str = "[{$currentDateTime}] ";

		if ($debugFileExt === "html")
		{
			$tmp = htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
			if ($newLineFlag) { $str = $str . '<pre>' . $tmp . '</pre>'; }
			else { $str = $str . $tmp; }
			$tmp = null;
			if ($msgErrorFlag) { $str = '<font color="red">' . $str . '</font>'; }
			if ($isBold) { $str = "<b>" . $str . "</b>"; }
			$str = "<p>" . $str . "</p>" . PHP_EOL;
		}
		else { $str = $str . $msg . PHP_EOL; }

		$tmp = dirname($debug_file);
		if (!$folder->is_exist($tmp)) { $folder->create($tmp); }

		$textfile->add_string_to_file($debug_file, $str);
		$str = null;
	}
} // debug_mess

/**
 * Транслитирирует строку в латиницу ('Привет' => 'Privet')
 *
 * @param mixed|string $value
 * @return string Транслитирированная строка
 */
function translitText($value): string
{
    $converter = [
        'а' => 'a',    'б' => 'b',    'в' => 'v',    'г' => 'g',    'д' => 'd',
        'е' => 'e',    'ё' => 'e',    'ж' => 'zh',   'з' => 'z',    'и' => 'i',
        'й' => 'y',    'к' => 'k',    'л' => 'l',    'м' => 'm',    'н' => 'n',
        'о' => 'o',    'п' => 'p',    'р' => 'r',    'с' => 's',    'т' => 't',
        'у' => 'u',    'ф' => 'f',    'х' => 'h',    'ц' => 'c',    'ч' => 'ch',
        'ш' => 'sh',   'щ' => 'sch',  'ь' => '',     'ы' => 'y',    'ъ' => '',
        'э' => 'e',    'ю' => 'yu',   'я' => 'ya',
        'А' => 'A',    'Б' => 'B',    'В' => 'V',    'Г' => 'G',    'Д' => 'D',
        'Е' => 'E',    'Ё' => 'E',    'Ж' => 'Zh',   'З' => 'Z',    'И' => 'I',
        'Й' => 'Y',    'К' => 'K',    'Л' => 'L',    'М' => 'M',    'Н' => 'N',
        'О' => 'O',    'П' => 'P',    'Р' => 'R',    'С' => 'S',    'Т' => 'T',
        'У' => 'U',    'Ф' => 'F',    'Х' => 'H',    'Ц' => 'C',    'Ч' => 'Ch',
        'Ш' => 'Sh',   'Щ' => 'Sch',  'Ь' => '',     'Ы' => 'Y',    'Ъ' => '',
        'Э' => 'E',    'Ю' => 'Yu',   'Я' => 'Ya',
    ];
    return strtr(strval($value), $converter);
}

class LogHelper
{
	/**
	 * Логировать сообщение (ОТЛАДКА) в Панель Отладки (stdout) и в
	 * файл (txt, html), уровень логирования (log_level) учитывается
	 * 
	 * Необходимый log_level для этой функции: 1 или меньше
	 * 
	 * В лог-сообщение автоматически добавляется: метка времени, ОТЛАДКА,
	 * название компонента, красный цвет шрифта для текста (если
	 * есть в тексте лог-сообщения подстрока `ошибка`)
	 * 
	 * Если лог сообщение не string, то автоматически преобразуется
	 * в строку (var_export)
	 * 
	 * Место вызова (компонент) используется для базовой идентификации
	 * места, где была вызвана логирующая функция.
	 * Что в место вызова (компонент) писать?
	 *   - пустая строка: ""
	 *   - класс и метод: "Giszhkh.login_as"
	 *   - название функции: "giszhkh_login_if_needed"
	 *   - внутренняя подветка в функции/методе: "Giszhkh.login_as.check_login_result"
	 * 
	 * Использует глобальные переменные:
	 *   - bool $dbg Логировать в Панель Отладки (stdout), если true
	 *   - string|null $debug_file Логировать в файл, если указан путь к файлу
	 *   - int $log_level Необходимый уровень логирования
     *   - bool $debug_panel_translit_text Транслитерировать ли лог сообщение для Панели Отладки
	 * 
	 * @param mixed $msg Лог сообщение, автоматически преобразуется (var_export) в строку
	 * @param string $componentName (по-умолчанию: "") Место вызова (компонент) (например:
	 *   "" (пустая строка), "main", "Giszhkh.login_as", "test123")
	 * @param bool $isBold Выделять "жирным" сообщение или нет (по-умолчанию - нет)
	 * @return void
	 */
	public function debug($msg, string $componentName = '', bool $isBold = false): void
	{
		global $dbg, $debug_file, $log_level;

		$currentLogLevel = 1;

		_logGlobalLogVarsInitIfNeeded();

		$this->_logLog($log_level, $currentLogLevel, $dbg, $componentName, $msg, $debug_file, $isBold);
	}

	/**
	 * Логировать сообщение (ИНФО) в Панель Отладки (stdout) и в
	 * файл (txt, html), уровень логирования (log_level) учитывается
	 * 
	 * Необходимый log_level для этой функции: 2 или меньше
	 * 
	 * В лог-сообщение автоматически добавляется: метка времени, ИНФО,
	 * название компонента, красный цвет шрифта для текста (если
	 * есть в тексте лог-сообщения подстрока `ошибка`)
	 * 
	 * Если лог сообщение не string, то автоматически преобразуется
	 * в строку (var_export)
	 * 
	 * Место вызова (компонент) используется для базовой идентификации
	 * места, где была вызвана логирующая функция.
	 * Что в место вызова (компонент) писать?
	 *   - пустая строка: ""
	 *   - класс и метод: "Giszhkh.login_as"
	 *   - название функции: "giszhkh_login_if_needed"
	 *   - внутренняя подветка в функции/методе: "Giszhkh.login_as.check_login_result"
	 * 
	 * Использует глобальные переменные:
	 *   - bool $dbg Логировать в Панель Отладки (stdout), если true
	 *   - string|null $debug_file Логировать в файл, если указан путь к файлу
	 *   - int $log_level Необходимый уровень логирования
     *   - bool $debug_panel_translit_text Транслитерировать ли лог сообщение для Панели Отладки
	 * 
	 * @param mixed $msg Лог сообщение, автоматически преобразуется (var_export) в строку
	 * @param string $componentName (по-умолчанию: "") Место вызова (компонент) (например:
	 *   "" (пустая строка), "main", "Giszhkh.login_as", "test123")
	 * @param bool $isBold Выделять "жирным" сообщение или нет (по-умолчанию - нет)
	 * @return void
	 */
	public function info($msg, string $componentName = '', bool $isBold = false): void
	{
		global $dbg, $debug_file, $log_level;

		$currentLogLevel = 2;

		_logGlobalLogVarsInitIfNeeded();

		$this->_logLog($log_level, $currentLogLevel, $dbg, $componentName, $msg, $debug_file, $isBold);
	}

	/**
	 * Логировать сообщение (ПРЕДУПРЕЖДЕНИЕ) в Панель Отладки (stdout) и в
	 * файл (txt, html), уровень логирования (log_level) учитывается
	 * 
	 * Необходимый log_level для этой функции: 3 или меньше
	 * 
	 * В лог-сообщение автоматически добавляется: метка времени, ПРЕДУПРЕЖДЕНИЕ,
	 * название компонента, красный цвет шрифта для текста (если
	 * есть в тексте лог-сообщения подстрока `ошибка`)
	 * 
	 * Если лог сообщение не string, то автоматически преобразуется
	 * в строку (var_export)
	 * 
	 * Место вызова (компонент) используется для базовой идентификации
	 * места, где была вызвана логирующая функция.
	 * Что в место вызова (компонент) писать?
	 *   - пустая строка: ""
	 *   - класс и метод: "Giszhkh.login_as"
	 *   - название функции: "giszhkh_login_if_needed"
	 *   - внутренняя подветка в функции/методе: "Giszhkh.login_as.check_login_result"
	 * 
	 * Использует глобальные переменные:
	 *   - bool $dbg Логировать в Панель Отладки (stdout), если true
	 *   - string|null $debug_file Логировать в файл, если указан путь к файлу
	 *   - int $log_level Необходимый уровень логирования
     *   - bool $debug_panel_translit_text Транслитерировать ли лог сообщение для Панели Отладки
	 * 
	 * @param mixed $msg Лог сообщение, автоматически преобразуется (var_export) в строку
	 * @param string $componentName (по-умолчанию: "") Место вызова (компонент) (например:
	 *   "" (пустая строка), "main", "Giszhkh.login_as", "test123")
	 * @param bool $isBold Выделять "жирным" сообщение или нет (по-умолчанию - нет)
	 * @return void
	 */
	public function warn($msg, string $componentName = '', bool $isBold = false): void
	{
		global $dbg, $debug_file, $log_level;

		$currentLogLevel = 3;

		_logGlobalLogVarsInitIfNeeded();

		$this->_logLog($log_level, $currentLogLevel, $dbg, $componentName, $msg, $debug_file, $isBold);
	}

	/**
	 * Логировать сообщение (ОШИБКА) в Панель Отладки (stdout) и в
	 * файл (txt, html), уровень логирования (log_level) учитывается
	 * 
	 * Необходимый log_level для этой функции: 4 или меньше
	 * 
	 * В лог-сообщение автоматически добавляется: метка времени, ОШИБКА,
	 * название компонента, красный цвет шрифта для текста
	 * 
	 * Если лог сообщение не string, то автоматически преобразуется
	 * в строку (var_export)
	 * 
	 * Место вызова (компонент) используется для базовой идентификации
	 * места, где была вызвана логирующая функция.
	 * Что в место вызова (компонент) писать?
	 *   - пустая строка: ""
	 *   - класс и метод: "Giszhkh.login_as"
	 *   - название функции: "giszhkh_login_if_needed"
	 *   - внутренняя подветка в функции/методе: "Giszhkh.login_as.check_login_result"
	 * 
	 * Использует глобальные переменные:
	 *   - bool $dbg Логировать в Панель Отладки (stdout), если true
	 *   - string|null $debug_file Логировать в файл, если указан путь к файлу
	 *   - int $log_level Необходимый уровень логирования
     *   - bool $debug_panel_translit_text Транслитерировать ли лог сообщение для Панели Отладки
	 * 
	 * @param mixed $msg Лог сообщение, автоматически преобразуется (var_export) в строку
	 * @param string $componentName (по-умолчанию: "") Место вызова (компонент) (например:
	 *   "" (пустая строка), "main", "Giszhkh.login_as", "test123")
	 * @param bool $isBold Выделять "жирным" сообщение или нет (по-умолчанию - нет)
	 * @return void
	 */
	public function error($msg, string $componentName = '', bool $isBold = false): void
	{
		global $dbg, $debug_file, $log_level;

		$currentLogLevel = 4;

		_logGlobalLogVarsInitIfNeeded();

		$this->_logLog($log_level, $currentLogLevel, $dbg, $componentName, $msg, $debug_file, $isBold);
	}

	/**
	 * [Служебная функция] Базовая пре-обработка лог сообщения и отправка
	 * на печать (Панель Отладки, файл), если надо
	 */
	private function _logLog(int $logLevel, int $currentLogLevel, bool $logToStdout,
		string $componentName, $text, string $logFile, bool $isBold): void
	{
		// Список уровней логирования
		$levelNames = [
			1 => 'ОТЛАДКА',
			2 => 'ИНФО',
			3 => 'ПРЕДУПРЕЖДЕНИЕ',
			4 => 'ОШИБКА',
		];

		// уровень логирования валидный?
		if (!array_key_exists($currentLogLevel, $levelNames)) { $currentLogLevel = 1; }
		$levelName = $levelNames[$currentLogLevel];

		// Логируем только если надо
		if ($currentLogLevel < $logLevel) { return; }

		// метка времени
		$currentDt = date("Y-m-d H:i:s");

		// Конвертим в строку лог сообщение
		if (!is_string($text)) { $text = var_export($text, true); }
		$text = trim($text);

		// лог сообщение - ошибка?
		$isErrorText = false;
		if ($currentLogLevel === 4) { $isErrorText = true; }
		if (!$isErrorText)
		{
			$tmp = mb_strtolower($text);
			if (mb_strpos($tmp, "ошибка") !== false) { $isErrorText = true; }
			$tmp = null;
		}

		$componentName = trim($componentName);

		// log to debug panel (stdout)
		if ($logToStdout)
		{
			$this->_logToDebugPanel($currentDt, $levelName, $isBold, $isErrorText, $componentName, $text);
		}

		// log to file
		if ( (!is_null($logFile)) and (@trim($logFile) === '') ) { $logFile = null; }
		if ($logFile)
		{
			$this->_logToFile($currentDt, $levelName, $isBold, $isErrorText, $componentName, $text, $logFile);
		}
	} // _logLog

	/**
	 * [Служебная функция] Отправляет лог-сообщение в
	 * Панель Отладки (stdout) и доп обработка лог-сообщения
	 */
	private function _logToDebugPanel(string $dt, string $levelName, bool $isBold,
		bool $isErrorText, string $componentName, string $text): void
	{
		global $debug_panel_translit_text;

		// Многострочная лог строка?
		$newLineFlag = false;
		if (preg_match('/\R/', $text) === 1) { $newLineFlag = true; }

		$str = '';
		if ($newLineFlag) { $str = '<pre>' . $text . '</pre>'; }
		else { $str = $text; }

		$str = "[{$dt}][{$levelName}][{$componentName}] {$str}";

		if ($isBold) { $str = '<b>' . $str . '</b>'; }
		if ($isErrorText) { $str = '<font color="red">' . $str . '</font>'; }

		if ($debug_panel_translit_text) { $str = translitText($str); }
		$str = $str . PHP_EOL;

		echo $str;
	} // _logToDebugPanel

	/**
	 * [Служебная функция] Отправляет лог-сообщение в файл и
	 * доп обработка лог-сообщения
	 */
	private function _logToFile(string $dt, string $levelName, bool $isBold,
		bool $isErrorText, string $componentName, string $text, string $logFile): void
	{
		global $textfile, $folder;

		if (is_null($logFile)) { return; }

		// расширение лог файла
		$tmp = explode('.', @trim($logFile));
		$tmp = mb_strtolower(@trim(@$tmp[count($tmp) - 1]));
		$debugFileExt = $tmp; // "html" or "txt"
		if ($debugFileExt === "htm") { $debugFileExt = 'html'; }
		if ($debugFileExt === "log") { $debugFileExt = 'txt'; }
		if ( ($debugFileExt !== 'txt') and ($debugFileExt !== 'html') ) { $debugFileExt = 'txt'; }

		// Многострочная лог строка?
		$newLineFlag = false;
		if (preg_match('/\R/', $text) === 1) { $newLineFlag = true; }

		$str = '';

		if ($debugFileExt === "html")
		{
			$str = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
			if ($newLineFlag) { $str = '<pre>' . $str . '</pre>'; }
		}
		else { $str = $text; }

		$str = "[{$dt}][{$levelName}][{$componentName}] {$str}";

		if ($isBold and ($debugFileExt === "html") ) { $str = '<b>' . $str . '</b>'; }
		if ($isErrorText and ($debugFileExt === "html") ) { $str = '<font color="red">' . $str . '</font>'; }
		if ($debugFileExt === "html") { $str = '<p>' . $str . '</p>'; }

		$str = $str . PHP_EOL;

		$tmp = dirname($logFile);
		if (!$folder->is_exist($tmp)) { $folder->create($tmp); }

		$textfile->add_string_to_file($logFile, $str);
	} // _logToFile
} // Log

/**
 * [Служебная функция] Инициализирует необходимые глобальные переменные
 * значениями по-умолчанию
 */
function _logGlobalLogVarsInitIfNeeded(): void
{
	global $dbg, $debug_file, $log_level, $debug_panel_translit_text;

	if (!isset($dbg)) { $dbg = true; }
	if (!isset($debug_file)) { $debug_file = null; }
	if (!isset($log_level)) { $log_level = 1; }
	if (!isset($debug_panel_translit_text)) { $debug_panel_translit_text = false; }
}

// Логировать в указанный файл PHP'шные ошибки/предупреждения/нотисы/необрабытываемые исключения
// TODO: доработать, портировать нужные куски из monolog.ErrorHandler?
function startHandlePhpErrors(): void
{
	global $debug_file;

	_logGlobalLogVarsInitIfNeeded();

	$xhePort = WINDOW::$app->get_port();

	$logfile = null;
	if (is_null($debug_file)) { $logfile = getcwd() . "\\log\\PHP_ERRORS_LOG_{$xhePort}.txt"; }
	else
	{
		$tmp = explode(".", $debug_file);
		$tmp[count($tmp)-1] = "PHP_ERRORS_LOG_{$xhePort}.txt";
		$logfile = implode(".", $tmp);
	}

	$tmp = dirname($logfile);
	if (!SYSTEM::$folder->is_exist($tmp)) { SYSTEM::$folder->create($tmp); }

	error_reporting(E_ALL); // Error/Exception engine, always use E_ALL
	ini_set('ignore_repeated_errors', true); // always use TRUE
	ini_set('display_errors', true); // Error/Exception display, use FALSE only in production environment or real server. Use TRUE in development environment
	ini_set('log_errors', true); // Error/Exception file logging engine.
	ini_set('error_log', $logfile); // Logging file path
} // startHandlePhpErrors
