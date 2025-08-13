<?php

/**
 * Пример:
 * ```
 *	if(isset($settingsFilePath) && file_exists($settingsFilePath)) {
 *      SETTINGS::$settings->addSettingsFromFile($settingsFilePath);
 *  } elseif (isset($settingsJson)) {
 *      SETTINGS::$settings->addSettingsFromJson($settingsJson);
 *  }
 *
 *  if(isset($settingsName) && !empty($settingsName)) {
 *      $settings = SETTINGS::$settings->setSettings($settingsName);
 *  }
 * ```
 */
class SettingsHelper
{
    /**
     * Задает значения для настроек и записывает данные настроек в глобальный массив
     *
     * @param $strSettingsName Название шаблона настроек (можно передавать string)
     * @return bool
     */
    public function setSettings($strSettingsName) {
        global $settingseditor;

        if(!$this->isExistSettings($strSettingsName)) {
            return false;
        }

        $settingsJson = $settingseditor->set_settings($strSettingsName);

        $settingsData = json_decode($settingsJson, true);

        if(!isset($settingsData['IsSettingsInput'])
            || ($settingsData['IsSettingsInput'] === false)) {
            return false;
        }

        $this->saveSettingsToGlobals($settingsData);

        return true;
    }

    /**
     * Записывает переданный массив настроек в глобальный массив $GLOBALS
     *
     * @param $arrSettingsData Массив настроек (можно передавать array)
     */
    public function saveSettingsToGlobals($arrSettingsData) {
        foreach ($arrSettingsData as $settingName => $settingValue){
            if($settingName == 'IsSettingsInput') {
                continue;
            }

            if(!isset($GLOBALS[$settingName])) {
                $GLOBALS[$settingName] = $settingValue;
            }
        }
    }

    /**
     * Добавить настройку из JSON файла (если настройка существует, она заменяется на новую)
     *
     * @param $strFilePath Путь к файлу с настройками (можно передавать string)
     * @return bool
     */
    public function addSettingsFromFile($strFilePath) {
        global $settingseditor;

        return $settingseditor->add_setting_from_file($strFilePath);
    }

    /**
     * Добавить настройку из строки JSON
     *
     * @param $jsonData Строка JSON (можно передавать JSON)
     * @return bool
     */
    public function addSettingsFromJson($jsonData) {
        global $settingseditor;

        return $settingseditor->add_setting_from_json($jsonData);
    }

    /**
     * Проеряет существует ли шаблон настройки по указанному имени
     *
     * @param $strSettingsName Название шаблона настроек (можно передавать string)
     * @return bool
     */
    public function isExistSettings($strSettingsName) {
        global $settingseditor;

        return $settingseditor->is_exist($strSettingsName);
    }

    // slash.2023-07-06 - добавил возвращение тупо массива json
	public function selfConfigure(&$settingsName, $settingsFilePath, &$settingsArray = false)
	{
		if(!isset($settingsFilePath) || !SYSTEM::$file_os->is_exist($settingsFilePath))
		{
			debug_mess("[ОШИБКА] Не удалось найти файл с настройками! Укажите правильный путь к файлу."
				. PHP_EOL . var_export($settingsFilePath, true) );
			TOOLS::$app->quit();
		}

		$settingsFileData = SYSTEM::$textfile->read_file($settingsFilePath, 60, 'UTF-8');
		if(!$settingsFileData)
		{
			debug_mess("[ОШИБКА] Не удалось прочитать файл с настройками и определить название шаблона настроек.");
			TOOLS::$app->quit();
		}

		$settingsFileData = @json_decode($settingsFileData, true);

        // slash feature
        if ($settingsArray) {
            $settingsArray = $settingsFileData;
            return;
        }

		if(isset($settingsFileData['Name'])) { $settingsName = $settingsFileData['Name']; }

		if(!$settingsName)
		{
			debug_mess("[ОШИБКА] Не удалось определить название шаблона настроек."
				. PHP_EOL . var_export($settingsFileData, true) );
			TOOLS::$app->quit();
		}

		if(!SETTINGS::$settings->addSettingsFromFile($settingsFilePath))
		{
			debug_mess("[ОШИБКА] Не удалось загрузить настройки из файла."
				. PHP_EOL . var_export($settingsFilePath, true) );
			TOOLS::$app->quit();
		}

		if(!SETTINGS::$settings->setSettings($settingsName))
		{
			debug_mess("[ОШИБКА] Не удалось получить список настроек.");
			TOOLS::$app->quit();
		}
	}
}
