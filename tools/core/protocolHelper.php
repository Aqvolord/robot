<?php

/**
 * Краткий протокол работы робота.
 * 
 * ```
 * $protocol = new ProtocolHelper();
 * $protocol
 *   ->setProtocolFormat('xlsx')
 *   ->setProtocolPath('C:\\Temp\\test_robot\\log\\protocol_2023-03-06_00-00-01.xlsx')
 *   ->setProtocolHeader([
 *       'Шапка таблицы, столбец 1',
 *       'Шапка таблицы, столбец 2',
 *   ]);
 * $protocol->logToProtocol([
 *   'Данные в столбец 1',
 *   'Данные в столбец 2',
 * ]);
 * $protocol->log([
 *   'Данные в столбец 1',
 *   'Данные в столбец 2',
 * ]);
 * ```
 */
class ProtocolHelper
{
	/**
	 * @var array[string] $arrProtocolHeader Шапка таблицы протокола,
	 * формируется для каждого робота индивидуально исходя из необходимых
	 * данных.
	 * 
	 * Пример:
	 * ```
	 *    private $arrProtocolHeader = [
	 *        'Дата\Время в работу',
	 *        'Дата\Время в завершения',
	 *        'Номер пакета',
	 *        'Дата пакета',
	 *        'Штрих Код документа',
	 *        'Номер документа',
	 *        'Дата документа',
	 *        'Статус выполения',
 	 *        'Комментарий'
	 *    ];
	 * ```
	 */
    private $arrProtocolHeader = [];

	/**
	 * @var string $strProtocolPath Путь к протокол-файлу
	 */
    private $strProtocolPath = '';

	/**
	 * @var string $strProtocolFormat Формат протокол-файла:
	 *   - xlsx
	 *   - csv
	 */
    private $strProtocolFormat = 'xlsx';

	/**
	 * @var array[string] $arrSupportedProtocolFormats Поддерживаемые форматы протокола
	 */
    private $arrSupportedProtocolFormats = ['xlsx', 'csv'];

    public function __construct()
    {
        if ($this->strProtocolFormat === "xlsx")
        {
            $this->strProtocolPath = WINDOW::$debug->get_cur_script_folder() . '\\log\\protocol_' . date('Y-m-d') . '.xlsx';
		}
        else
        {
            $this->strProtocolPath = WINDOW::$debug->get_cur_script_folder() . '\\log\\protocol_' . date('Y-m-d') . '.csv';
		}
    }

	/**
	 * @throw InvalidArgumentException
	 */
    public function setProtocolFormat($strValue)
    {
		if (!is_string($strValue)) { $strValue = (string) $strValue; }
		$strValue = mb_strtolower(trim($strValue));
		if (!in_array($strValue, $this->arrSupportedProtocolFormats))
		{
			throw new \InvalidArgumentException("strValue содержит неподдерживаемое значение: "
				. var_export($strValue, true) . '.' . PHP_EOL
				. "Поддерживаемые значения: " . var_export($this->arrSupportedProtocolFormats, true) );
		}
        $this->strProtocolFormat = $strValue;
        return $this;
    }

    public function getProtocolFormat()
    {
        return $this->strProtocolFormat;
    }

	/**
	 * @throw InvalidArgumentException
	 */
    public function setProtocolPath($strValue)
    {
		if (!is_string($strValue)) { $strValue = (string) $strValue; }
		$strValue = trim($strValue);
		if (mb_strlen($strValue) === 0) { throw new \InvalidArgumentException("strValue должен быть не пустым"); }
        $this->strProtocolPath = $strValue;
        return $this;
    }

    public function getProtocolPath()
    {
        return $this->strProtocolPath;
    }

	/**
	 * @throw InvalidArgumentException
	 */
	public function setProtocolHeader($arrHeader)
	{
		if (!is_array($arrHeader)) { throw new \InvalidArgumentException("arrHeader должен иметь тип array"); }
		if (!$arrHeader) { throw new \InvalidArgumentException("arrHeader должен быть не пустым"); }
		$this->arrProtocolHeader = $arrHeader;
		return $this;
	}

	public function getProtocolHeader()
	{
		return $this->arrProtocolHeader;
	}

	/**
	 * Логирование в протокол через массив
	 * 
	 * @param array[] $arrData Данные, которые логируем в протокол
	 * @throw InvalidArgumentException
	 * @throw RuntimeException
	 * @return bool
	 */
    public function logToProtocol($arrData)
    {
		if (!is_array($arrData)) { throw new \InvalidArgumentException("arrData должен иметь тип array"); }
		if (!$arrData) { throw new \InvalidArgumentException("arrData должен быть не пустым"); }
		if (!$this->arrProtocolHeader) { throw new \RuntimeException("Пустая шапка таблицы протокола недопустима"); }

		foreach ($arrData as $k => $v)
		{
			if (is_string($v)) { continue; }
			$v = (string) $v;
			$arrData[$k] = $v;
		}

		$baseDir = dirname($this->strProtocolPath);
		if (!SYSTEM::$folder->is_exist($baseDir))
		{
			if (!SYSTEM::$folder->create($baseDir))
			{
				throw new \RuntimeException("Не смогли создать базовую дирректорию для файла с протоколом: {$this->strProtocolPath}");
			}
		}

		$result = false;
        if ($this->strProtocolFormat == "xlsx")
        {
			$result = $this->addRowToXlsx($arrData);
        }
        else
        {
			$result = $this->addRowToCsv($arrData);
        }
        return $result;
    }

	public function log($arrData) { return $this->logToProtocol($arrData); }

    private function addRowToXlsx($arrData)
    {
		if (!SYSTEM::$file_os->is_exist($this->strProtocolPath))
		{
			SYSTEM::$excelfile->create($this->strProtocolPath, date('d.m.Y'), $this->arrProtocolHeader);
		}

		if (!SYSTEM::$excelfile->is_opened($this->strProtocolPath))
		{
			SYSTEM::$excelfile->open($this->strProtocolPath);
		}

		$intRow = SYSTEM::$excelfile->get_rows_count($this->strProtocolPath, 0) + 1;

		$result = SYSTEM::$excelfile->set_row($this->strProtocolPath, 0, $intRow, $arrData);
		for ($i = 0; $i < count($this->arrProtocolHeader); $i++)
		{
			$col = $i + 1;
			SYSTEM::$excelfile->set_cell_type($this->strProtocolPath, 0, $intRow, $col, 'Text');
		}
		SYSTEM::$excelfile->autosize_col($this->strProtocolPath, 0);

		$result2 = SYSTEM::$excelfile->save($this->strProtocolPath);
		SYSTEM::$excelfile->close($this->strProtocolPath);

		return ($result and $result2);
	}

	// TODO: addRowToCsv: переписать нормально (что сейчасм будет, если в строке есть символ `;`?)
    private function addRowToCsv($arrData)
    {
		if (!SYSTEM::$file_os->is_exist($this->strProtocolPath))
		{
			SYSTEM::$textfile->write_file($this->strProtocolPath, implode(";", $this->arrProtocolHeader) . "\r\n");
		}
		return SYSTEM::$textfile->add_string_to_file($this->strProtocolPath, implode(";", $arrData) . "\r\n");
	}

	// добавить в лог
	public function addLog ($data) {
		$path = $this->getProtocolPath();

		if (!SYSTEM::$file_os->is_exist($path)) {
			SYSTEM::$excelfile->create($path, date('Y-m-d'));
			SYSTEM::$excelfile->close($path);
			SYSTEM::$excelfile->open($path);
			SYSTEM::$excelfile->set_row($path, 0, 1, $this->getProtocolHeader());
			SYSTEM::$excelfile->save($path);
		}

		SYSTEM::$excelfile->close($path);
		SYSTEM::$excelfile->open($path);

		$rowsnum = SYSTEM::$excelfile->get_rows_count($path, 0);
		if ($rowsnum <= 1) $row = 2;
		else $row = $rowsnum + 1;

		// array prepend pp number
		array_unshift($data, $row - 1);

		// выставляем тип ячеек в Текст, ибо теряются ведущие нули
		for ($i = 2; $i <= count($data); $i++) {
			SYSTEM::$excelfile->set_cell_type($path, 0, $row, $i, 'Text');
			SYSTEM::$excelfile->autosize_col($path, 0, $i);
		}

		SYSTEM::$excelfile->set_row($path, 0, $row, $data);

		SYSTEM::$excelfile->save($path);
		SYSTEM::$excelfile->close($path);

		return true;
	}
}
