<?php
/**
 * 
 * Класс для работы с ЦРМ.
 * 
 * @author Никачев Максим <nikachev.m@tm.biz-apps.ru>
 * @require SimpleTabsHelper
 * 
**/

class Crm
{
	// максимальное время в секундах на ожидание элемента
    private $max_wait = 13;

	// номер таба с открытой CRM
	private $crmTabNumber = 1;
	// "на всякий случай" номер предыдущей вкладки
	private $crmPrevTabNumber = 1;

	// конструктор
    public function __construct() {;}

	// создать вкладку с CRM в табе с номером tabNumber
	public function createCrmTab() {
		global $mySettings, $wt;

		$currentTabsNumber = WEB::$browser->get_active_browser();
		sleep($wt * 2);

		TOOLS::$simpleTabsHelper->addTabSimple();
        WEB::$browser->navigate($mySettings['crm_url']);
        TOOLS::$simpleTabsHelper->waitTabSimple();

		$newTabsNumber = WEB::$browser->get_count() - 1;

		$this->crmTabNumber = $newTabsNumber;
		$this->crmPrevTabNumber = $currentTabsNumber;

		debug_mess('CRM:create():INFO: номер предыдущего таба ' . $this->crmPrevTabNumber);
		debug_mess('CRM:create():INFO: номер таба CRM ' . $this->crmTabNumber);
	}

	// функция открывает вкладку с CRM и сохраняет номер предыдущей
	public function openCrmTab() {
		global $wt;

		WEB::$browser->set_active_browser($this->crmTabNumber);
		sleep($wt * 3);
		debug_mess('CRM:openCrmTab():INFO: открыли таб CRM с №' . $this->crmTabNumber);
	}

	// функция открывает предыдущую вкладка, НЕ CRM
	public function openPrevTab() {
		global $wt;

		WEB::$browser->set_active_browser($this->crmPrevTabNumber);
		sleep($wt * 3);
		debug_mess('CRM:openPrevTab():INFO: открыли таб НЕ CRM с №' . $this->crmPrevTabNumber);
	}

	// вкладки имеют свойство закрываться и на их месте открываться новые,
	// поэтому функция для апдейта номера вкладки с CRM
	public function updateCrmTabNumber() {
		$this->crmTabNumber = WEB::$browser->get_active_browser();
		debug_mess('CRM:updateCrmTabNumber():INFO: номер таба CRM ' . $this->crmTabNumber);
	}

	// сохраним номер текущего таба НЕ CRM
	public function updatePrevTabNumber() {
		$this->crmPrevTabNumber = WEB::$browser->get_active_browser();
		debug_mess('CRM:updatePrevTabNumber():INFO: сохранили номер таба НЕ CRM ' . $this->crmPrevTabNumber);
	}

	// закрыть вкладку с CRM
	public function close ($activateTab = true) {
		global $wt;

		debug_mess('CRM:close():INFO: activateTab ' . (($activateTab)? 'ДА': 'НЕТ'));

		WEB::$browser->set_active_browser($this->crmTabNumber, $activateTab);
		sleep($wt * 2);

		debug_mess('CRM:close():INFO: activateTab закрыли вкладку');
	}

	// авторизация
	public function login() {
		global $mySettings, $wt, $wt_long;

		if (!wait_on_element_by_text(DOM::$div, 'Войти')) {
			debug_mess('CRM: не дождались Войти! Перезапуск...');
			return false;
		}
		debug_mess('CRM: форма авторизации загружена!');

		$crm_login = new EncodedVariable($mySettings['crm_login_name']);
		$crm_password = new EncodedVariable($mySettings['crm_password_name']);
		
		DOM::$input->send_keyboard_input_by_attribute("placeholder","Логин", true, $crm_login,"20:40");
		sleep($wt);
		DOM::$input->send_keyboard_input_by_attribute("placeholder","Пароль", true, $crm_password,"20:40");
		sleep($wt);
		DOM::$span->click_by_inner_text("Войти", false);
		sleep($wt);
		 
		 // email 2FA
		 if (!wait_on_element_by_text(DOM::$span, 'Войти')) {
			debug_mess('CRM:2FA: не дождались войти! Перезапуск...');
			return false;
		}
		debug_mess('CRM: дождались формы 2FA!');
		
		$wearecool = false;
		for ($i = 1; $i <= $mySettings['totalmax']; $i++) {
			debug_mess('CRM:2FA: попытка ' . $i . ' из ' . $mySettings['totalmax']);
			$twosleep = rand(80, 90);
			debug_mess('Спим ' . $twosleep . 'с');
			sleep($twosleep);
			
			$incomeEmailFolder = '\\\\mes1_sigma.robot02@mosenergosbyt.ru\\Входящие';

			$messagesCount = WEB::$outlook->get_message_count($incomeEmailFolder);
			
			// берем последние три письма для поиска кода
			$twofa = null;
			for ($j = 0; $j < 3; $j++) {
			    debug_mess('CRM:2FA:ПОИСК 2ФА КОДА: свежее письмо - ' . $j);
				$incomeMessage = WEB::$outlook->get_message_by_number($incomeEmailFolder, $messagesCount - $j);
				 
				preg_match('/\d\d\d\d\d\d/', $incomeMessage->text_body, $incomeBodyMatches);
				if (!count($incomeBodyMatches)) {
					debug_mess('CRM:2FA:ПОИСК 2ФА КОДА: письмо не с кодом! Дальше ...');
					continue;
				}
				
				$twofa = $incomeBodyMatches[0];
				debug_mess('CRM:2FA:ПОИСК 2ФА КОДА: нашли код ' . $twofa);

				// код прочитали, удалим мессагу
				WEB::$outlook->delete_message_by_number($incomeEmailFolder, $messagesCount - $j);
				break;
			}
			
			// GENIOUS!think
			if (is_null($twofa)) {
				debug_mess('CRM:2FA: не нашли письмо с кодом!');
				debug_mess('CRM:2FA: Введем рандом и тогда точно свежее письмо будет с кодом ;)');
				$twofa = rand(123456, 987654);
				debug_mess('CRM:2FA:RANDOM: ' . $twofa);
			}

			DOM::$input->get_by_name('textfield-1040-inputEl')->set_value('');
			sleep($wt);
			DOM::$input->send_keyboard_input_by_attribute("name","textfield-1040-inputEl", true, $twofa,"20:40");
			sleep($wt*2);
			DOM::$span->click_by_inner_text("Войти", false);
			sleep($wt*3);
			 
			if (wait_on_element_by_text(DOM::$span, 'Обращения')) {
				debug_mess('CRM:2FA: OK!');
				$wearecool = true;
				break;
			}

			debug_mess('CRM:2FA: не тот код');
		}
		
		if (!$wearecool) {
			debug_mess('CRM:2FA: FAIL! Перезапуск...');
			return false;
		}
		
		debug_mess('CRM: авторизовались полностью!');
		return true;
	}

	// залогинены ли
	public function isAuth() {
		// проверка на диалог об обновлении системы
		if (wait_on_element_by_text(DOM::$div, 'Сообщение системы')) {
			DOM::$span->click_by_inner_text("OK", false);
			debug_mess('CRM: закрыли диалог Сообщение системы');
		}

		if (wait_on_element_by_text(DOM::$span, 'Обращения')) {
			debug_mess('CRM: авторизованы!');
			return true;
		}

		debug_mess('CRM: не авторизованы!');
		return false;
	}
	
	// закрыть диалог об изменении данных формы
	public function closeChangeDialog() {
	   global $wt;
	    
	   if (DOM::$div->is_exist_by_inner_text('Сообщение')) {
			$thisdiv = DOM::$div->get_by_attribute('class', 'x-tool-close', false);
			$thisdiv->focus();
			//$thisdiv->send_mouse_click(7, 7);
			DOM::$div->send_event_by_number($thisdiv->get_number(), 'onclick');
			sleep($wt);
			debug_mess('CRM:closeChangeDialog: закрыли окно о сохранении формы сообщения');
		}
	}
	
	// закрыть результаты поиска
	public function closeSearchResults() {
		global $wt;

		$this->closeChangeDialog();
		if (!DOM::$span->is_exist_by_inner_text('Закрыть')) return;

		$closeButtonLayers=DOM::$span->get_all_by_attribute('class', 'x-btn-wrap-green-button-small', false);
		foreach ($closeButtonLayers as $cbl) {
		    //$cbl->send_mouse_click(5, 5);
		    //$cbl->click();
		    DOM::$span->send_event_by_number($cbl->get_number(), 'onclick');
		    sleep($wt);
		    $this->closeChangeDialog();
		}

		debug_mess('CRM: закрыли окно');
	}
	
	// открыть поиск по номеру
	public function searchByNumber ($number) {
	    global $wt, $wt_long;

		$this->closeSearchResults();

	    debug_mess('CRM:searchByNumber: получаем данные по номеру ' . $number);

		debug_mess('CRM:searchByNumber: кликаем Обращения');
		DOM::$div->get_by_inner_text("Обращения")->send_mouse_click(10, 10);
		sleep($wt_long);
		
		debug_mess('CRM:searchByNumber: кликаем Очистить');
		DOM::$span->get_by_inner_text("Очистить")->send_mouse_click(10, 10);
		sleep($wt * 2);
		
		debug_mess('CRM:searchByNumber: вводим номер');
		DOM::$input->get_by_name('REQ_NUM')->send_input($number, '20:40');
		sleep($wt * 2);
		
		debug_mess('CRM:searchByNumber: кликаем Поиск');
		DOM::$span->click_by_inner_text("Поиск", false);
		sleep($wt * 2);
		
		if (!$this->dataLoaded()) return 'не дождались поиска';

		$myrows = DOM::$tr->get_all_by_attribute('class', 'x-grid-row');
		$mycount = count($myrows);
		if (!$mycount) {
			debug_mess('CRM:searchByNumber: нет результатов по номеру!');
			return 'нет результатов по номеру';
		}
		
		debug_mess('CRM:searchByNumber: есть результаты в количестве ' . $mycount . ' шт');
		
		$myrows[0]->send_mouse_double_click(10, 10);
		sleep($wt * 2);
		if (!wait_on_element_by_text(DOM::$span, 'Закрыть')) {
			debug_mess('CRM:searchByNumber: не дождались открытия карточки!');
			return 'не дождались открытия карточки';
		}
		
		return 'ok';
	}
	
	// получить значение с таба инпута по его name
	public function getValueByName ($vname) {
	    return trim(DOM::$input->get_by_name($vname)->get_value());
	}
	
	// получить значение с таба значения по его inner_text
	public function getValueByInnerText ($vname) {
	    return trim(DOM::$span->get_by_inner_text($vname)->get_parent()->get_next()->get_inner_text());
	}
	
	public function getValueByInnerText2 ($vname) {
	    $myarr = DOM::$span->get_all_by_inner_text($vname, true);
		foreach ($myarr as $currentElem) {
			$tmp_id = trim($currentElem->get_id());
			preg_match("/HyperlinkComboBox-\d{2,10}-labelTextEl/", $tmp_id, $pregres);
			if (!count($pregres)) continue;
			$tmp_id = abs((int) filter_var($pregres[0], FILTER_SANITIZE_NUMBER_INT));
			return trim(DOM::$input->get_value_by_attribute('id', "HyperlinkComboBox-$tmp_id-inputEl"));
		}
	    return '';
	}
	
	// открыть вкладку CRM по имени в карточке
	public function openTabByName ($tabname) {
		global $wt;

		DOM::$span->send_event_by_inner_text($tabname, false, 'onclick');
		//DOM::$span->click_by_inner_text($tabname, false);
		sleep($wt * 2);
	}

	// получить договорное подразделение
	public function getContractDivision ($searchStr = 'Договорное подразделение:') {
		$myrow = DOM::$div->get_by_attribute('class', 'rightObjectDataBlock', true);
		if ($myrow == false) {
			debug_mess('CRM:getContractDivision: не нашли договорное подразделение! (1)');
			return '';
		}

		$mycount = $myrow->get_child_count();
		for ($i = 0; $i < $mycount; $i++) {
			$tmp = $myrow->get_child_by_number($i)->get_inner_text();
			if (strpos($tmp, $searchStr) !== false)
				return trim(str_replace($searchStr, '', $tmp));
		}

		debug_mess('CRM:getContractDivision: не нашли договорное подразделение! (2)');
		return '';
	}

	// получить количество объектов на вкладке параметры
	public function getNumberParams ($searchStr = 'адрес эо:') {
		$searchCounter = 0; // собственно, количество "объектов"

		$myobjects = DOM::$div->get_all_by_attribute('class', 'leftObjectDataBlock', true);
		$objectsCount = count($myobjects);

		for ($i = 0; $i < $objectsCount; $i++) {
			$tmp = trim(strtolower($myobjects[$i]->get_child_by_number(0)->get_inner_text()));
			if (strpos($tmp, $searchStr) !== false)
				$searchCounter++;
		}

		return $searchCounter;
	}

	// получить количество объектов на вкладке дополнительные параметры
	public function getNumberAddParams ($searchStr = 'приостановить в связи с неполным комплектом документов') {
		$searchCounter = 0; // собственно, количество "объектов"

		// находим табличку
		$myheaders = DOM::$div->get_all_by_attribute('class', 'x-grid-item-container', true);
		$headersCount = count($myheaders);

		for ($i = 0; $i < $headersCount; $i++) {
			// находим инпуты в табличке $i, откуда чекаем инфу
			$myinputs = $myheaders[$i]->get_all_child_by_attribute('tagName', 'INPUT', true, true);
			$inputsCount = count($myinputs);

			for ($j = 0; $j < $inputsCount; $j++) {
				$tmp = trim(strtolower($myinputs[$j]->get_value()));
				if (strpos($tmp, $searchStr) !== false)
					$searchCounter++;
			}
		}

		return $searchCounter;
	}

	// получить дату регистрации исходящего сообщения с протоколом урегулирования разногласий
	public function getSuperDate() {
		global $wt;

		$myrows = DOM::$table->get_all_by_attribute('class', 'x-grid-item');
		$rowsNum = count($myrows);

		for ($i = 0; $i < $rowsNum; $i++) {
			$rowStr = $myrows[$i]->get_inner_text();

			// нам нужны только исходящие
			if (strpos($rowStr, 'Исходящие') === false)
				continue;

			$rowArr = explode("\n", $rowStr);

			// проваливаемся в исходящее сообщение
			$myrows[$i]->send_mouse_double_click(10, 10);
			if (!wait_on_element_by_text(DOM::$div, 'Объекты обращения', -1, 60)) {
				debug_mess('CRM:getSuperDate: не дождались открытия исходящего сообщения!');
				continue;
			}
			
			if (!$this->dataLoaded()) {
				$this->closeSearchResults();
				continue;
			}

			$attachmentsArr = DOM::$li->get_all_by_attribute('data-qtip', 'Протокол урегулирования разногласий');
			$this->closeSearchResults();

			// есть дока, возвращаем время исходящего сообщения
			if (count($attachmentsArr))
				return $rowArr[2];
		}

		// ничего не нашли
		return '';
	}
	
	// функция, ожидающая попапа Загрузка с проверкой на наличие результатов поиска
	function dataLoaded ($maxSeconds = 300) {
	    debug_mess('CRM:dataLoaded: ждем окончанияя загрузки результатов поиска...');
	     
		$mytext1 = "Загрузка...";
		$mytext2 = "Не найдено записей, удовлетворяющих условию.";
		      
		$myelem1 = DOM::$div->get_by_inner_text($mytext1, true);
		$myelem2 = DOM::$div->get_by_inner_text($mytext2, true);

		for ($z = 0; $z < $maxSeconds; $z++) {
			sleep(1);
			if (
				!DOM::$div->is_exist_by_inner_text($mytext1, true) ||
				!$myelem1->is_view_now() ||
				!$myelem1->is_visibled() ||
				!$myelem1->get_x()
			) {
				debug_mess("CRM:dataLoaded: дождались загрузки!");

				if (
					DOM::$div->is_exist_by_inner_text($mytext2, true) &&
					$myelem2->is_view_now() &&
					$myelem1->get_x()
				) {
					debug_mess("CRM:dataLoaded: Не найдено записей, удовлетворяющих условию!");
					return false;
				}
				
				return true;
			}
		}
		
		debug_mess("CRM:dataLoaded: НЕ дождались загрузки!");
		return false;
	}
}
?>