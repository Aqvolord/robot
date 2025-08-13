<?php

/**
 * Пример:
 * ```
 * TOOLS::$rerun->setIndFilePath(WINDOW::$debug->get_cur_script_folder() . 'data\\lastInd.txt');
 * TOOLS::$rerun->saveIndToFile(10);
 * $startInd = TOOLS::$rerun->getNextIndFromFile();
 * TOOLS::$rerun->removeIndFile();
 * ```
 */
class RerunHelper
{
    private $strIndFilePath;

    public function __construct()
    {
        $this->strIndFilePath = WINDOW::$debug->get_cur_script_folder() . 'data\\lastInd.txt';
    }

    /**
     * Получает путь к файлу
     *
     * @return string
     **/
    public function getIndFilePath()
    {
        return $this->strIndFilePath;
    }

    /**
     * Задает путь к файлу, в котором хранятся данные для возобновления работы робота
     *
     * @param  $strPath Путь к файлу, в котором хранятся данные для возобновления работы робота (можно передавать строку)
     *
     * @return this
     **/
    public function setIndFilePath($strPath)
    {
        $this->strIndFilePath = $strPath;
        return $this;
    }

    /**
     * Проверяет существует ли указанный файл
     *
     * @return boolean
     **/
    private function checkFileExists()
    {
        if (empty($this->strIndFilePath)) {
            return false;
        }

        return SYSTEM::$file_os->is_exist($this->strIndFilePath);
    }

    /**
     * Возвращает следующую позицию индекса для старта робота
     *
     * @return int|boolean
     **/
    public function getNextIndFromFile()
    {
        if (!$this->checkFileExists()) {
            return false;
        }

        $startInd = SYSTEM::$textfile->read_file($this->strIndFilePath);
        $startInd = (int)$startInd;
        $startInd = $startInd + 1;

        return $startInd;
    }

    /**
     * Сохраняет в файл место, на котором остановился робот
     *
     * @param  $intData Место, на котором остановился робот (можно передавать int)
     *
     * @return boolean
     **/
    public function saveIndToFile($intData)
    {
        $intData = (int)$intData;

        return SYSTEM::$textfile->write_file($this->strIndFilePath, $intData);
    }

    /**
     * Удаляет заданный файл
     *
     * @return boolean
     **/
    public function removeIndFile()
    {
        if (empty($this->strIndFilePath)) {
            return false;
        }

        return SYSTEM::$file_os->delete($this->strIndFilePath);
    }
}