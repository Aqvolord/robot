<?php
/**
 * 
 * Класс для работы со списком айдишек уведомлений на ЕПГУ.
 * 
 * @author Никачев Максим <nikachev.m@tm.biz-apps.ru>
 * @link https://ru.wikipedia.org/wiki/%D0%94%D0%B2%D0%BE%D0%B8%D1%87%D0%BD%D1%8B%D0%B9_%D0%BF%D0%BE%D0%B8%D1%81%D0%BA
 * @require BC_MATH
 * 
**/

class NotificationsList
{
	// локальный таймаут для работы с файлом
	const LIST_FILE_TIMEOUT = 123;

	// путь к файлу со списком
	private $listPath;
	// имя файла со списком
	private $listFileName;

	public function __construct ($listFileName, $sortOnInit = true, $dedupe = true) {
		global $textfile, $file_os, $debug;

		$this->listFileName = $listFileName;
		debug_mess('Начали инициализацию списка уведомлений ' . $listFileName);

		$this->listPath = $debug->get_cur_script_folder() . 'res\\' . $listFileName;

		if ($file_os->is_exist($this->listPath)) {
			debug_mess('Количество айдишек ' . $this->getLinesNumber());
			if ($dedupe) {
				debug_mess('Убираем дубликаты...');
				$this->dedupe();
			}

			if ($sortOnInit) {
				debug_mess('Сортируем список...');
				$this->sort();
			}
		}

		debug_mess('OK:Заканочили инициализацию списка уведомлений');
	}

	// путь до файла
	public function getListFullPath() {
		return $this->listPath;
	}

	// количество строк
	public function getLinesNumber() {
		global $textfile;

		return $textfile->get_lines_count($this->listPath, self::LIST_FILE_TIMEOUT);
	}

	// прочесть строку под номером с нуля
	public function readLine ($num) {
		global $textfile;

		return $textfile->get_line_from_file($this->listPath, false, $num, self::LIST_FILE_TIMEOUT);
	}

	// удалить строку под номером с нуля
	public function deleteLine ($num) {
		global $textfile;

		$res = $textfile->delete_line_from_file($this->listPath, $num, self::LIST_FILE_TIMEOUT);
		if (!$res) debug_mess("ОШИБКА! NotificationsList: ошибка удаления id из файла!");

		return $res;
	}

	// поиск методом дихотомии
	private function binarySearch ($id) {
		$topIdx = $this->getLinesNumber() - 1;
		$bottomIdx = 0;

		while($topIdx >= $bottomIdx) {
			$currentIdx = floor($bottomIdx + (($topIdx - $bottomIdx) / 2));
			$tmp_id = $this->readLine($currentIdx);
			$cmpres = bccomp($tmp_id, $id);

			if ($cmpres < 0) $bottomIdx = $currentIdx + 1;
			elseif ($cmpres > 0) $topIdx = $currentIdx - 1;
			else return true;
		}

		return false;
	}

	// есть ли такой айди в списке
	public function inList ($id, $inarray = true) {
		global $textfile;

		if ($this->getLinesNumber() < 1)
			return false;
		
		if ($inarray) {
			$idArr = explode("\r\n", $textfile->read_file($this->listPath, self::LIST_FILE_TIMEOUT));
			return in_array($id, $idArr);
		}
		
		return $this->binarySearch($id);
	}

	// добавить айди в список
	public function add ($id) {
		global $textfile;

		if (empty($id)) {
			debug_mess("ОШИБКА! NotificationsList: добавляемый id пуст!");
			return false;
		}
		
		$tofile = "\r\n$id";
		if (($this->getLinesNumber() <= 1) && empty(trim($this->readLine(0))))
			$tofile = $id;

		$res = $textfile->add_string_to_file($this->listPath, $tofile, self::LIST_FILE_TIMEOUT);

		if (!$res) debug_mess("ОШИБКА! NotificationsList: не смогли добавить id: $id");
		else debug_mess("OK:NotificationsList: добавили id: $id");

		return $res;
	}

	// сортировка
	public function sort() {
		global $textfile, $file_os;

		$tmpPath = $this->listPath . '.tmp';

		$res = $textfile->sort($this->listPath, $tmpPath, self::LIST_FILE_TIMEOUT);
		if (!$res || !$file_os->is_exist($tmpPath)) debug_mess("ОШИБКА! NotificationsList: не смогли отсортировать список");

		$file_os->delete($this->listPath);
		$file_os->rename($tmpPath, $this->listPath);
		
		return true;
	}

	// убрать дубликаты
	public function dedupe() {
		global $textfile, $file_os;

		$tmpPath = $this->listPath . '.tmp';

		$res = $textfile->dedupe($this->listPath, $tmpPath, self::LIST_FILE_TIMEOUT);
		if (!$res || !$file_os->is_exist($tmpPath)) debug_mess("ОШИБКА! NotificationsList: не смогли убрать дубликаты");

		$file_os->delete($this->listPath);
		$file_os->rename($tmpPath, $this->listPath);
		
		return true;
	}
}