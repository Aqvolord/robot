<?php

class Robot
{
    // имя файла протокола в папке log
    private $logProtocolFileName;

	// список затронутых икселек для копирования обратно в сетевую папку
	private $changedFilesList = [];

	// вектор состояния для ИАС
	private $stateArr = null;
	// где храним
	private $stateFilename = '\\data\\laststate.json';

	// массив с аббревиатурами наименований отделений в ИАС для приведения их к наименованию икселек в сетевой папке
	private $abbrArr = [];
	// откуда грузим
	private $abbrFileName = '\\settings\\abbr.json';

	protected function configure()
	{
		global $mailerType, $mySettings, $listReadNotifications, $listDownloadNotifications;

        // Выбираем способ отправки почтовых сообщений
        if($mailerType === 'smtp')
        {
            global $strServer, $intPort, $strLogin, $strPassword;
            TOOLS::$mailer::makeSMTP($strServer, $intPort, $strLogin, $strPassword);
        }
        else
        {
            TOOLS::$mailer::makeOutlook();
        }

        $this->logProtocolFileName = $mySettings['protocolFolder'] . 'protocol_' . date('d-m-Y') . '.xlsx';
        TOOLS::$protocol->setProtocolFormat('xlsx')
            ->setProtocolPath($this->logProtocolFileName)
            ->setProtocolHeader([
				'№',
                'Дата\Время',
                'Отделение',
                '№ заявки',
                'Действие',
				'№ строки',
            ]);
        
        // чистим куки
        //WEB::$browser->clear_cookies("");

        // PROXY
        if (($mySettings['proxy'] !== false) && ($mySettings['proxy'] != '')) {
            WEB::$browser->enable_proxy("all connections", $mySettings['proxy']);
            debug_mess('Будем работать с прокси: ' . $mySettings['proxy']);
        } else {
            WEB::$browser->disable_proxy('', true);
            debug_mess('Прокси выключен');
        }

        // согласно настройке сворачиваем или разворачиваем
        if ($mySettings['minimize']) {
            debug_mess('Согласно настройке minimize, сворачиваемся в трэй...');
            WINDOW::$app->minimize_to_tray();
        } else {
            debug_mess('Согласно настройке minimize, развернемся на всю...');
            WINDOW::$app->maximize();
        }
        
        // OUTLOOK
        WEB::$outlook->kill();

        // BROWSER
        WEB::$browser->close_all_tabs();
		// отключаем отображение диалога сохранения файлов
        WEB::$browser->enable_download_file_dialog(false);
        // будем сохранять в папку из настроек
        WEB::$browser->set_default_download($mySettings['iasFolder']);
        
		// создаем папки
        SYSTEM::$folder->create($mySettings['local_folder']);
		SYSTEM::$folder->create($mySettings['protocolFolder']);
		SYSTEM::$folder->create($mySettings['iasFolder']);

		// вектор состояния
		$this->getVectorState();

		// протокол
		if (empty($this->stateArr['protocol_filename']))
			$this->stateArr['protocol_filename'] = TOOLS::$protocol->getProtocolPath();
		else TOOLS::$protocol->setProtocolPath($this->stateArr['protocol_filename']);

		// аббревиатуры отделений
		$res = $this->loadAbbr();
		return $res; // UGLY
	}

	// читаем json с аббревиатурами для работы в функции подбора нужной иксельки района
	private function loadAbbr() {
		if (!SYSTEM::$file_os->is_exist($this->abbrFileName)) {
			debug_mess('АББРЕВИАТУРЫ: нет файла с маппингом, выход');
			return false;
		}

		$this->abbrArr = json_decode(trim(SYSTEM::$textfile->read_file($this->abbrFileName, 30, 'UTF-8')), true);
		debug_mess('Прочитали маппинг аббревиатур: ' . print_r($this->abbrArr, true));
		return true;
	}

	// сохраняем в файл вектор состояния
	private function saveLastState() {
		SYSTEM::$textfile->write_file($this->stateFilename, json_encode($this->stateArr));
		debug_mess('Сохранили вектор состояния: ' . print_r($this->stateArr, true));
	}
	
	// актуальный позавчера start_dt
	private function getActualStartDt() {
		global $mySettings;

		$pozavch = strtotime('-2 days');
		$start_dt = date('d.m.Y ' . $mySettings['ias_time_start'], $pozavch);

		return $start_dt;
	}

	// актуальный вчера last_dt (с докрутками, например, если пропустили)
	private function getActualLastDt() {
		global $mySettings;
		
		// проверка и докрутка last date для ИАС, если пропустили прогон
		$current_vchera = strtotime('-1 days');
		$current_last_dt = date('d.m.Y ' . $mySettings['ias_time_start'], $current_vchera);

		return $current_last_dt;
	}

	// новый позавчера start_dt
	private function getNewStartDt() {
		global $mySettings;

		// новый позавчера будет сегодня вчера
		$pozavch = strtotime('-1 days');
		$start_dt = date('d.m.Y ' . $mySettings['ias_time_start'], $pozavch);

		return $start_dt;
	}

	// новый вчера last_dt (с докрутками, например, если пропустили)
	private function getNewLastDt() {
		global $mySettings;
		
		// новый вчера будет сегодня
		$current_vchera = time();
		$current_last_dt = date('d.m.Y ' . $mySettings['ias_time_start'], $current_vchera);

		return $current_last_dt;
	}

	/*
	* получаем вектор значений, на котором остановились в ИАС
	* - если нет файла ветора состояния, то пишем в него дефолты
	* - иначе осуществляем "докрутку" последнего позавчера до текущего позавчера, если тачку мы теряли и сохраняем это в вектор
	*/
	private function getVectorState() {
		global $mySettings;

		$actual_start_dt = $this->getActualStartDt();
		$actual_last_dt = $this->getActualLastDt();

		// если нет вектора состояния, то пишем дефолтные значения
		if (!SYSTEM::$file_os->is_exist($this->stateFilename)) {
			debug_mess('getVectorState: вектор состояния отсутствует, поэтому пишем с дефолтными значениями');
			SYSTEM::$textfile->write_file($this->stateFilename, json_encode([
				'start_dt' => $actual_start_dt,
				'last_dt' => $actual_last_dt,
				'ias_filename' => '',
				'protocol_filename' => '',
				'last_row' => 0,
				'shouldContinue' => false,
			]));
		}

		$this->stateArr = json_decode(trim(SYSTEM::$textfile->read_file($this->stateFilename)), true);

		if (($this->stateArr['last_dt'] != $actual_last_dt) && (!$this->stateArr['shouldContinue'])) {
			debug_mess('getVectorState:ДОКРУТКА_LAST_DT: было ' . $this->stateArr['last_dt'] . ' стало ' . $actual_last_dt);
			$this->stateArr['last_dt'] =  $actual_last_dt;
			// таким образом, если робот не запустился по какой-то причине, то мы докручиваем last dt до нужного ласт вчера

			$this->saveLastState();

			// подуем на воду (перечитаем файл вектора состояния новосозданного), чтобы сразу, если есть ошибка, понять об этом
			$this->stateArr = json_decode(trim(SYSTEM::$textfile->read_file($this->stateFilename)), true);
		}

		debug_mess('Прочитали вектор состояния: ' . print_r($this->stateArr, true));
	}

	// апдейтим таймштампы для запуска по расписанию на следующий день, где позавчера сегодня будет вчера, а вчера будет сегодня (привет, "вчера сегодня будет завтра")
	private function updateDtState() {
		global $mySettings;

		// поскольку мы пишемся лишь после ОК или после "докрутки" last_dt до актуальной даты, то в этой функции нам следует работать с актуальными start_dt и last_dt
		//@deprecated
		/*
		list($mydt, $mytime) = explode(' ', $this->stateArr['start_dt']);
		list($my_d, $my_m, $my_y) = explode('.', $mydt);

		$this->stateArr['start_dt'] = date(
			'd.m.Y ' . $mySettings['ias_time_start'],
			strtotime('+1 days', strtotime("$my_y-$my_m-$my_d " . $mySettings['ias_time_start']))
		);
		debug_mess('ВЕКТОР СОСТОЯНИЯ: start_dt = ' . $this->stateArr['start_dt']);

		$this->stateArr['last_dt'] = date(
			'd.m.Y ' . $mySettings['ias_time_start'],
			strtotime('+2 days', strtotime("$my_y-$my_m-$my_d " . $mySettings['ias_time_start']))
		);
		debug_mess('ВЕКТОР СОСТОЯНИЯ: last_dt = ' . $this->stateArr['last_dt']);
		*/

		$this->stateArr['start_dt'] = $this->getNewStartDt();
		$this->stateArr['last_dt'] = $this->getNewLastDt();

		debug_mess('updateDtState:ВЕКТОР СОСТОЯНИЯ: ' . print_r($this->stateArr, true));
	}

    public function run()
    {
        global $debug_file, $xhe_host, $mySettings, $wt, $wt_long, $restartRobotPath;

		$emessage = $mySettings['email_header_prefix'] . " начал работу";
        debug_mess("[{$xhe_host}] " . $emessage, true);
		send_process_email($mySettings['bsend_email_from'], $mySettings['bsend_email_to'], $mySettings['email_header_prefix'], $emessage, $mySettings['bsend_email_flag'], $mySettings['bsend_email_copy']);

		if (!$this->configure()) {
			$emessage = 'Робот закончил работу. Не смогли пройти предварительную настройку';
			debug_mess("[{$xhe_host}] $emessage", true);
			send_process_email($mySettings['bsend_email_from'], $mySettings['bsend_email_to'], $mySettings['email_header_prefix'], $emessage, $mySettings['bsend_email_flag'], $mySettings['bsend_email_copy']);
			return;
		}

		$login_sigma = new EncodedVariable($mySettings['ebs_ul_login_name']);
		$pwd_sigma = new EncodedVariable($mySettings['ebs_ul_password_name']);
		$ias_login = new EncodedVariable($mySettings['ias_login_name']);
		$ias_password = new EncodedVariable($mySettings['ias_password_name']);
		
		$strikedFont = [
			'name' => 'Calibri',
			'size' => 12,
			'is_bold' => 0,
			'is_italic' => 0,
			'is_underline' => 0,
			'is_striketrough' => 1,
			'is_shadow' => 0,
		];

		MODULES::$crm->createCrmTab();
        
		$wearecool = true;
        while (true) {
			if (MODULES::$crm->isAuth()) break;
			if (MODULES::$crm->login()) break;
			else {
				$wearecool = false;
				break;
			}
		}
		if (!$wearecool) {
			$emessage = 'CRM: не смогли авторизоваться. Останов';
			debug_mess("[{$xhe_host}] $emessage", true);
			send_process_email($mySettings['bsend_email_from'], $mySettings['bsend_email_to'], $mySettings['email_header_prefix'], $emessage, $mySettings['bsend_email_flag'], $mySettings['bsend_email_copy']);
			return;
        }

		$wearecool = true;
		for ($myi = 0; $myi < 5; $myi ++) {
			// ЕБС ЮЛ
			TOOLS::$simpleTabsHelper->addTabSimple();
			WEB::$browser->navigate($mySettings['ebs_ul_url']);
			TOOLS::$simpleTabsHelper->waitTabSimple();

			// Авторизация, если надо
			debug_mess("Выполняем логин в ЕБС, если надо");
			if(!wait_on_element_by_att(DOM::$image, "alt", "Отчетность")) {
				if(!login_sigma($login_sigma, $pwd_sigma)) {
					debug_mess("[ПРЕДУПРЕЖДЕНИЕ] Не смогли залогиниться в систему");
					TOOLS::$simpleTabsHelper->removeTabSimple();
					$wearecool = false;
					continue;
				}

				if(!wait_on_element_by_att(DOM::$image, "alt", "Отчетность")) {
					debug_mess("[ПРЕДУПРЕЖДЕНИЕ] Не смогли перейти в приёмку");
					TOOLS::$simpleTabsHelper->removeTabSimple();
					$wearecool = false;
					continue;
				}
			}
			debug_mess('ЕБС ЮЛ: уже авторизованы!');

			if(!wait_on_element_by_att(DOM::$image, "alt", "Отчетность", -1, 30)) {
				debug_mess("[ПРЕДУПРЕЖДЕНИЕ] Не смогли перейти в приёмку");
				TOOLS::$simpleTabsHelper->removeTabSimple();
				$wearecool = false;
				continue;
			}
			DOM::$image->click_by_alt("Отчетность");

			// проверяем авторизованы ли
			if(!wait_on_element_by_text(DOM::$anchor, "Аналитическая МЭС")) {
				debug_mess("[ПРЕДУПРЕЖДЕНИЕ] нет пункта Аналитическая МЭС (Не смогли авторизоваться)");
				TOOLS::$simpleTabsHelper->removeTabSimple();
				$wearecool = false;
				continue;
			}
			DOM::$anchor->click_by_inner_text("Аналитическая МЭС", false);
			sleep($wt);
			TOOLS::$simpleTabsHelper->waitTabSimple();
			
			if (wait_on_element_by_text(DOM::$btn, 'Войти') || wait_on_element_by_text(DOM::$span, 'Стартовая страница')) {
				debug_mess('ИАС: дождались!');
				break;
			}
			debug_mess('ИАС: не дождались формы авторизации!');
			TOOLS::$simpleTabsHelper->removeTabSimple(); // ИАС
			TOOLS::$simpleTabsHelper->removeTabSimple(); // ЕБС ЮЛ
			$wearecool = false;
		}

		if (!$wearecool) {
			$emessage = 'ЕБС: не смогли авторизоваться. Останов';
			debug_mess("[{$xhe_host}] $emessage", true);
			send_process_email($mySettings['bsend_email_from'], $mySettings['bsend_email_to'], $mySettings['email_header_prefix'], $emessage, $mySettings['bsend_email_flag'], $mySettings['bsend_email_copy']);
			return;
        }
		
		//UGLY
		$wearecool = false;
		for ($myi = 0; $myi < 5; $myi++) {
			// ИАС
			 if (!wait_on_element_by_text(DOM::$span, 'Стартовая страница')) {
				debug_mess('ИАС: не авторизованы!');
			
				DOM::$input->send_keyboard_input_by_name('j_username', $ias_login, "20:40");
				sleep($wt);

				DOM::$input->send_keyboard_input_by_name('j_password', $ias_password, "20:40");
				sleep($wt);
				DOM::$btn->click_by_inner_text("Войти", false);
				sleep($wt);
				
				if (!wait_on_element_by_text(DOM::$span, 'Стартовая страница')) {
					$emessage = 'ИАС: ОШИБКА данных авторизации! Робот закончил работу';
					debug_mess("[{$xhe_host}] $emessage", true);
					send_process_email($mySettings['bsend_email_from'], $mySettings['bsend_email_to'], $mySettings['email_header_prefix'], $emessage, $mySettings['bsend_email_flag'], $mySettings['bsend_email_copy']);
					return;
				}
				
				debug_mess('ИАС: авторизация успешна!');
			
				if (!wait_on_element_by_text(DOM::$span, 'Отчеты МЭС')) {
					$emessage = 'ИАС: не дождались Отчеты МЭС. Робот закончил работу';
					debug_mess("[{$xhe_host}] $emessage", true);
					send_process_email($mySettings['bsend_email_from'], $mySettings['bsend_email_to'], $mySettings['email_header_prefix'], $emessage, $mySettings['bsend_email_flag'], $mySettings['bsend_email_copy']);
					return;
				}
				debug_mess('ИАС: дождались Отчеты МЭС');
			}
			debug_mess('ИАС: уже авторизованы!');
			
			WEB::$browser->navigate('http://e.billing.interrao.ru:8888/analytics/saw.dll?dashboard&PortalPath=%2Fshared%2F%D0%9E%D0%B1%D1%89%D0%B8%D0%B5%20%D0%BE%D1%82%D1%87%D0%B5%D1%82%D1%8B%2F_portal%2F%D0%9C%D0%AD%D0%A1.%20%D0%A0%D0%B5%D0%B5%D1%81%D1%82%D1%80%20%D0%B7%D0%B0%D0%B4%D0%B0%D0%BD%D0%B8%D0%B9%20%D0%B4%D0%BB%D1%8F%20%D0%BE%D1%82%D1%87%D0%B5%D1%82%D0%B0%20%D0%BF%D0%BE%20%D0%AD%D0%A1%D0%9E');
			TOOLS::$simpleTabsHelper->waitTabSimple();
			debug_mess('ИАС: перешли по урлу сразу в нужный отчет');

			if (!wait_on_element_by_text(DOM::$label, 'Реестр заданий для отчета по СО')) {
				$emessage = 'ИАС: не дождались Реестр заданий для отчета по СО. Робот закончил работу';
				debug_mess("[{$xhe_host}] $emessage", true);
				send_process_email($mySettings['bsend_email_from'], $mySettings['bsend_email_to'], $mySettings['email_header_prefix'], $emessage, $mySettings['bsend_email_flag'], $mySettings['bsend_email_copy']);
				return;
			}

			if ($this->stateArr['shouldContinue']) {
				debug_mess('ИАС: продолжаем работу, выгрузку делать не будем...');
				$wearecool = true;
				break;
			}

			// ИАС начало ДТ
			DOM::$input->get_by_number(0)->set_value('');
			sleep($wt);
			DOM::$input->send_keyboard_input_by_number(0, $this->stateArr['start_dt'], '20:40');
			sleep($wt);
			debug_mess('ИАС: ввели поиск начало ' . $this->stateArr['start_dt']);
			
			// ИАС конец ДТ
			DOM::$input->get_by_number(1)->set_value('');
			sleep($wt);
			DOM::$input->send_keyboard_input_by_number(1, $this->stateArr['last_dt'], '20:40');
			sleep($wt);
			debug_mess('ИАС: ввели поиск конец ' . $this->stateArr['last_dt']);

			// поиск
			DOM::$button->get_by_id('gobtn')->send_mouse_click(7, 7);

			// нет заявок за указанный период
			if (wait_on_element_by_text(DOM::$td, 'Результаты отсутствуют')) {
				// результатов нет, но все равно обновим даты в векторе состояний
				$this->updateDtState();
				$this->saveLastState();

				$emessage = 'ИАС: Результаты отсутствуют! Робот закончил работу';
				debug_mess("[{$xhe_host}] $emessage", true);
				send_process_email($mySettings['bsend_email_from'], $mySettings['bsend_email_to'], $mySettings['email_header_prefix'], $emessage, $mySettings['bsend_email_flag'], $mySettings['bsend_email_copy']);
				return;
			}

			if (!wait_on_element_by_text(DOM::$label, 'Реестр заданий для отчета по СО', -1, 60)) {
				$emessage = 'ИАС: не дождались Реестр заданий для отчета по СО. Робот закончил работу';
				debug_mess("[{$xhe_host}] $emessage", true);
				send_process_email($mySettings['bsend_email_from'], $mySettings['bsend_email_to'], $mySettings['email_header_prefix'], $emessage, $mySettings['bsend_email_flag'], $mySettings['bsend_email_copy']);
				return;
			}

			// вектор состояния
			$this->stateArr['ias_filename'] = $this->iasDownload($this->stateArr['start_dt'], $this->stateArr['last_dt']);
			  
			if ($this->stateArr['ias_filename'] != false) {
				if ($mySettings['iasFolderEnabled']) {
					$toshared = $mySettings['iasFolderShared'] . SYSTEM::$file_os->get_name($this->stateArr['ias_filename']);
					SYSTEM::$file_os->copy($this->stateArr['ias_filename'], $toshared);
					debug_mess('ИАС: скопировали выгрузку в сетевую папку ' . $toshared);
				}

				$this->stateArr['shouldContinue'] = false;
				$this->saveLastState();

				$wearecool = true;
				break;
			} else {
				debug_mess('ИАС:ОШИБКА: не смогли скачать иксельку! ПОВТОР');
				continue;
			}
		}
        
        if (!$wearecool) {
			$emessage = 'ИАС: не смогли получить выгрузку. Робот закончил работу';
			debug_mess("[{$xhe_host}] $emessage", true);
			send_process_email($mySettings['bsend_email_from'], $mySettings['bsend_email_to'], $mySettings['email_header_prefix'], $emessage, $mySettings['bsend_email_flag'], $mySettings['bsend_email_copy']);
			return;
        }

		// копируем все локально из сетевой папки
		$this->copyAllXlsxFromShared();

		// стартовая строка ИАС
		$ias_startrow = 3;
		// стартовая строка в иксельке в локальной папке после копирования из сетевой
		$region_startrow = 4;

		SYSTEM::$excelfile->close($this->stateArr['ias_filename']);
		SYSTEM::$excelfile->open($this->stateArr['ias_filename']);
		$IASSHEET = SYSTEM::$excelfile->get_sheet($this->stateArr['ias_filename'], 0);

		$ias_maxrow = count($IASSHEET);
		if (!$ias_maxrow) {
			$emessage = 'ИАС:ОШИБКА: икселька пуста! Робот закончил работу';
			debug_mess("[{$xhe_host}] $emessage", true);
			send_process_email($mySettings['bsend_email_from'], $mySettings['bsend_email_to'], $mySettings['email_header_prefix'], $emessage, $mySettings['bsend_email_flag'], $mySettings['bsend_email_copy']);
			return;
		} else debug_mess('ИАС:Прочитали выгрузку, всего строк: ' . $ias_maxrow);

		if ($this->stateArr['shouldContinue']) {
			$ias_startrow = $this->stateArr['last_row'];
			debug_mess('ИАС: продолжаем со строки ' . $ias_startrow);
		}

		$this->stateArr['shouldContinue'] = true;

		// цикл по строкам из иксельки из ИАС
		for ($i = $ias_startrow; $i < $ias_maxrow; $i++) {
			debug_mess('ИАС: Обрабатываем строку ' . $i . ' из ' . $ias_maxrow);
			$this->stateArr['last_row'] = $i;
			$this->saveLastState();

			$ias_number = trim($IASSHEET[$i][2]);
			debug_mess('Номер заявки: ' . $ias_number);

			// зачеркнутость
			// +1, потому что из массива в рбот
			$mycellfont = SYSTEM::$excelfile->get_cell_font($this->stateArr['ias_filename'], 0, $i + 1, 3);
			$is_strikethrough = ($mycellfont->is_striketrough == '1');
			if ($is_strikethrough) debug_mess('Номер заявки зачеркнут');
			else debug_mess('Номер заявки не зачеркнут');

			$ias_region = trim($IASSHEET[$i][6]);
			debug_mess('Район: ' . $ias_region);

			$sharedtype = trim($IASSHEET[$i][1]);
			debug_mess('ИАС: тип строки: ' . $sharedtype);
			if (($sharedtype != 'Заявка на заключение договора') && ($sharedtype != 'Заключение договора')) {
				debug_mess('ИАС: тип не подходит, дальше...');
				continue;
			}
			
			// UPDATE 2024-04-23: теперь на входе аббревиатура
			$currentXlsx = $this->getXlsxByRegion($ias_region);
			if (($currentXlsx === false) || empty($currentXlsx)) {
				debug_mess('ИАС:ОШИБКА: не нашли иксельку по району');
				continue;
			}

			// нашли иксельку, добавим ее в список для залива обратно в сетевую
			if (!in_array($currentXlsx, $this->changedFilesList))
				$this->changedFilesList[] = $currentXlsx;

			debug_mess('ИАС:ОК: нашли иксельку по району ' . $currentXlsx);

			// читаем иксельку по району
			if (isset($CURRENTSHEET)) unset($CURRENTSHEET);
			SYSTEM::$excelfile->close($currentXlsx);
			SYSTEM::$excelfile->open($currentXlsx);
			$CURRENTSHEET = SYSTEM::$excelfile->get_sheet($currentXlsx, 0);
   
			$region_maxrow = count($CURRENTSHEET);
			debug_mess('SHARED: количество строк ' . $region_maxrow);

			// цикл по иксельке из сетевой папки
			debug_mess('SHARED: START');
			// номер строки для апдейта\записи
			$foundrow = -1;
			// действие: новая строка или изменение найденной
			$ias_action = '';
			// первая "пустая" строка без даты
			$first_free_row = -1;

			for ($j = $region_startrow; $j < $region_maxrow; $j++) {
				debug_mess('SHARED: Обрабатываем строку ' . $j . ' из ' . ($region_maxrow - 1));

				if (SYSTEM::$excel->is_row_hidden($currentXlsx, 0, $j)) {
					debug_mess('SHARED: строка скрыта, пропускаем...');
					continue;
				}

				if (!isset($CURRENTSHEET[$j])) {
					debug_mess('SHARED: ROW NULL');
					continue;
				}

				$givemetherowplz = $CURRENTSHEET[$j];
				$region_number = @trim($givemetherowplz[7]);
				$req_dt = @trim($givemetherowplz[2]);
				$req_number = str_replace(["\r", "\n"], '', @trim($givemetherowplz[3]));

				if (empty($req_dt)) {
					if ($first_free_row < 0) {
						$first_free_row = $j + 1;
						debug_mess("SHARED: нашли первую пустую строку: $first_free_row");
					}

					debug_mess("SHARED: дата заявки пуста! Дальше...");
					continue;
				}

				$checkone = mb_strtolower($ias_number);
				$checktwo = mb_strtolower($req_number);

				debug_mess("SHCHECK:$checkone:$checktwo");

				if ($checkone == $checktwo) {
					$ias_action = 'изменение строки';
					$foundrow = $j + 1;
					debug_mess('НАШЛИ существующую строку в иксельке и будем писать в нее: ' . $foundrow);
					break;
				}
			}

			// когда строка не подошла
			if ($foundrow < 0) {
			    $ias_action = 'новая строка';

				if ($first_free_row < 0) $foundrow = $region_maxrow + 1;
				else $foundrow = $first_free_row;
				
				debug_mess('НАШЛИ новую строку в иксельке и будем писать в нее: ' . $foundrow);
			}

			debug_mess("SHARED:ias_action:$ias_action");
			debug_mess("SHARED:foundrow:$foundrow");
			
			// данные строки в сетевой папке
			if (isset($crmdata)) unset($crmdata);
			$crmdata = [
				0 => '',
				1 => ($first_free_row >= 0)? ($foundrow - ($region_startrow + 1)): $this->getTableNumberByRowNumber($CURRENTSHEET, $foundrow, $ias_action, $region_startrow),
				2 => TOOLS::$dt->removeTimeFromDate($IASSHEET[$i][0]), // дата поступления
				3 => $IASSHEET[$i][2], // номер заявки
				4 => $IASSHEET[$i][3], // номер обращения
				5 => $IASSHEET[$i][4], // наименование клиента
				6 => $IASSHEET[$i][5], // количество объектов
				7 => $ias_region, // наименование отделения
				8 => TOOLS::$dt->removeTimeFromDate($IASSHEET[$i][7]), // дозапрос
				9 => '', // количество объектов
				10 => TOOLS::$dt->removeTimeFromDate($IASSHEET[$i][8]), // дата отказа
				11 => $IASSHEET[$i][9], // количество объектов для оферты скорее всего
				12 => TOOLS::$dt->removeTimeFromDate($IASSHEET[$i][11]), // дата оферты
				13 => '', // кол-во объектов
				14 => $IASSHEET[$i][12], // ПУР
				15 => '',
				16 => '',
				17 => '',
				18 => '',
				19 => '',
			];
			debug_mess('SHARED: первичное заполнение crdmdata: ' . print_r($crmdata, true));

			debug_mess('SHARED: требуется проверка данных строки в CRM...');
			MODULES::$crm->updatePrevTabNumber();
			MODULES::$crm->openCrmTab();

			// на нешли по номеру ИАС записи, переходим к следующей
			$searchRes = MODULES::$crm->searchByNumber($ias_number);
			if ($searchRes != 'ok') {
				debug_mess('SHARED: CRM: ' . $searchRes . ', к следующей записи ' . $ias_number);
				MODULES::$crm->openPrevTab();
				$ias_action = 'CRM:' . $searchRes;

				// НИКОГДА ТАК НЕ ДЕЛАЙТЕ!
				//TODO://FIXME://UGLY!!!!111
		goto saveExcelFile;
			}
			debug_mess('SHARED: CRM: успешно выполнили поиск!');

			MODULES::$crm->openTabByName('ДОПОЛНИТЕЛЬНЫЕ ПАРАМЕТРЫ');
			$crmdata[4] = MODULES::$crm->getValueByName('REQ_NUM_ORIGIN');
			debug_mess('CRM: получили значение Номер заявки в системе источнике: ' . $crmdata[4]);

			MODULES::$crm->openTabByName('ПАРАМЕТРЫ');
			$crmdata[5] = MODULES::$crm->getValueByInnerText2('Клиент');
			$crmdata[5] = str_replace(' ', "\r\n", $crmdata[5]);
			debug_mess('CRM: получили значение Клиент: ' . $crmdata[5]);

			if ($sharedtype == 'Заявка на заключение договора') {
				//MODULES::$crm->openTabByName('ПАРАМЕТРЫ');
				$crmdata[6] = MODULES::$crm->getNumberParams('адрес эо:');
				debug_mess('CRM: получили значение количества объектов "адрес эо": ' . $crmdata[6]);

				MODULES::$crm->openTabByName('ДОПОЛНИТЕЛЬНЫЕ ПАРАМЕТРЫ');
				$crmdata[7] = MODULES::$crm->getValueByName('CONTEXT_OBJECT_CODE_DIVISION');
				debug_mess('CRM: получили значение договорного подразделения: ' . $crmdata[7]);
				
				//MODULES::$crm->openTabByName('ДОПОЛНИТЕЛЬНЫЕ ПАРАМЕТРЫ');
				$crmdata[9] = MODULES::$crm->getNumberAddParams('приостановить в связи с неполным комплектом документов');
				debug_mess('CRM: получили кол-во объектов "приостановить в связи с неполным комплектом документов": ' . $crmdata[9]);

				if ($crmdata[9] > 0) {
					$crmdata[8] = MODULES::$crm->getValueByName('TERMINATION_LETTER_DATE_FACT');
					$crmdata[8] = TOOLS::$dt->removeTimeFromDate($crmdata[8]);
				} else $crmdata[8] = $crmdata[9] = '';
				debug_mess('CRM: получили дату дозапроса: ' . $crmdata[8]);

				$crmdata[11] = MODULES::$crm->getNumberAddParams('отказать');
				debug_mess('CRM: получили кол-во объектов "отказать": ' . $crmdata[11]);

				if ($crmdata[11] > 0) {
					$crmdata[10] = MODULES::$crm->getValueByName('TERMINATION_LETTER_DATE_FACT');
					$crmdata[10] = TOOLS::$dt->removeTimeFromDate($crmdata[10]);
				} else $crmdata[10] = $crmdata[11] = '';
				debug_mess('CRM: получили дату дозапроса: ' . $crmdata[10]);
			} elseif ($sharedtype == 'Заключение договора') {
				//MODULES::$crm->openTabByName('ПАРАМЕТРЫ');
				$crmdata[7] = MODULES::$crm->getContractDivision();
				debug_mess('CRM: получили значение договорного подразделения: ' . $crmdata[7]);

				//MODULES::$crm->openTabByName('ПАРАМЕТРЫ');
				$myquantity = MODULES::$crm->getNumberParams('договор:');
				debug_mess('CRM: получили значение количества объектов "договор": ' . $myquantity);
				if ($myquantity == 0) $myquantity = '';

				MODULES::$crm->openTabByName('ДОПОЛНИТЕЛЬНЫЕ ПАРАМЕТРЫ');
				$crmdata[12] = MODULES::$crm->getValueByName('OFFER_FACT_DT');
				$crmdata[12] = TOOLS::$dt->removeTimeFromDate($crmdata[12]);
				debug_mess('CRM: получили значение Фактическая дата направления оферты: ' . $crmdata[12]);

				if (!empty(trim($crmdata[12])))
					$crmdata[13] = $myquantity;

				//MODULES::$crm->openTabByName('ДОПОЛНИТЕЛЬНЫЕ ПАРАМЕТРЫ');
				$crmdata[16] = MODULES::$crm->getValueByName('CLNT_SIGN_OFFER_DT');
				$crmdata[16] = TOOLS::$dt->removeTimeFromDate($crmdata[16]);
				debug_mess('CRM: получили значение Дата подписания клиентом оферты: ' . $crmdata[16]);
				if (!empty(trim($crmdata[16])))
					$crmdata[17] = $myquantity;

				//MODULES::$crm->openTabByName('ДОПОЛНИТЕЛЬНЫЕ ПАРАМЕТРЫ');
				$crmdata[18] = MODULES::$crm->getValueByName('CLNT_SIGN_PUR_DT');
				debug_mess('CRM: получили значение Дата возврата ПУР/ПР в адрес МЭС: ' . $crmdata[18]);
				if (!empty(trim($crmdata[18])))
					$crmdata[19] = $myquantity;

				MODULES::$crm->openTabByName('ОБЩЕЕ');
				MODULES::$crm->openTabByName('ВЗАИМОДЕЙСТВИЯ');
				$crmdata[14] = MODULES::$crm->getSuperDate();
				debug_mess('CRM: получили значение Дата регистрации исходящего сообщения, в котором вложен документ с типом Протокол урегулирования разногласий: ' . $crmdata[14]);
				if (!empty(trim($crmdata[14])))
					$crmdata[15] = $myquantity;
			}

			// наименование района
			//if (empty($crmdata[7])) $crmdata[7] = $this->getRegionNameByAbbr($ias_region);
			//if ($crmdata[7] == false) $crmdata[7] = $ias_region;
			if (empty($crmdata[7])) $crmdata[7] = $ias_region;

			// НИКОГДА ТАК НЕ ДЕЛАЙТЕ!
			//TODO://FIXME://UGLY!!!!111
		saveExcelFile:
		
			debug_mess('SHARED: заносим данные из строки ИАС в строку ' . $foundrow);
			debug_mess(print_r($crmdata, true));
			/*if ($ias_action == 'новая строка')
				SYSTEM::$excelfile->add_row($currentXlsx, 0, $crmdata);
			else */SYSTEM::$excelfile->set_row($currentXlsx, 0, $foundrow, $crmdata);

			// зачеркнутость номера заявки
			if ($is_strikethrough) {
				SYSTEM::$excelfile->set_cell_font($currentXlsx, 0, $foundrow, 4, $strikedFont);
				debug_mess('SHARED: зачеркнули номер заявки, как в выгрузке из ИАС');
			}

			// форматируем границы последних строк
			$this->formatMeLastLines($foundrow, $region_maxrow, $currentXlsx);
			// другие форматы текущей
			$this->formatMeCurrentRow($foundrow, $currentXlsx);

			SYSTEM::$excelfile->save($currentXlsx);
			SYSTEM::$excelfile->close($currentXlsx);

			TOOLS::$protocol->addLog([
				date('Y-m-d H:i:s'),
                $ias_region,
                $ias_number,
                $ias_action,
				$foundrow,
			]);

			debug_mess('ИАС: ROW END');
		}

		SYSTEM::$excelfile->close($this->stateArr['ias_filename']);

		// копируем иксельки обратно в сетевую
		$this->copyAllXlsxToShared();
		
		// копируем протокол в сетевую папку
		if ($mySettings['protocolFolderEnabled']) {
			$protocolShared = $mySettings['protocolFolderShared'] . SYSTEM::$file_os->get_name($this->stateArr['protocol_filename']);
			SYSTEM::$file_os->copy($this->stateArr['protocol_filename'], $protocolShared);
			debug_mess('Протокол работы: ' . $protocolShared);
		}

		// составляем массив для отсылки из протокола и выгрузки из ИАС (если таковые есть и были данные для отработки) для отправки в конце работы робота
		$sendFilesArr = [];
		if (SYSTEM::$file_os->is_exist($this->stateArr['protocol_filename']))
			$sendFilesArr[] = $this->stateArr['protocol_filename'];
		if (SYSTEM::$file_os->is_exist($this->stateArr['ias_filename']))
			$sendFilesArr[] = $this->stateArr['ias_filename'];

		// вектор состояния, когда все ок
		$this->stateArr['shouldContinue'] = false;
		$this->stateArr['last_row'] = $ias_startrow;
		$this->stateArr['protocol_filename'] = '';
		$this->stateArr['ias_filename'] = '';
		$this->updateDtState();
		$this->saveLastState();

        TOOLS::$simpleTabsHelper->removeTabSimple();

		WEB::$browser->navigate("about:blank");

		$emessage = $mySettings['email_header_prefix'] . " закончил работу";
		if (!count($sendFilesArr))
			$emessage .= '. Выгрузка пуста или с ошибками. Файлов для отправки нет';

        debug_mess("[{$xhe_host}] $emessage", true);
		send_process_email($mySettings['bsend_email_from'], $mySettings['bsend_email_to'], $mySettings['email_header_prefix'], $emessage, $mySettings['bsend_email_flag'], $mySettings['bsend_email_copy'], $sendFilesArr);
    } // run

	// получить наименования отдела по аббревиатуре
	private function getRegionNameByAbbr ($abbr) {
		$abbr = trim(str_replace(' ', '', mb_strtolower($abbr, 'UTF-8')));

		foreach ($this->abbrArr as $key => $value) {
			$value = trim(str_replace(' ', '', mb_strtolower($value, 'UTF-8')));

			if (strpos($value, $abbr) !== false) {
				debug_mess('getAbbrByRegionName: нашли значение аббревиатуры ' . $value);
				return $key;
			}
		}

		debug_mess('getAbbrByRegionName: не нашли значение для аббревиатуры ' . $abbr);
		return false;
	}

	// поиск иксельки для заполнения по номеру округа
	private function getXlsxByRegion ($region) {
		global $mySettings;

		$orig_region = $region;
		$selected_region = $this->getRegionNameByAbbr($region);
		if (empty($selected_region))
			return false;

		debug_mess('getXlsxByRegion: аббр-отдел: ' . $orig_region . '-' . $selected_region);
		$region = $selected_region;

		$region = trim(str_replace(' ', '', mb_strtolower($region, 'UTF-8')));
		
		if (empty($region)) {
			debug_mess('getXlsxByRegion: значение района пусто!');
			return false;
		}

		$filesList = explode('<br>', SYSTEM::$folder->get_all_items($mySettings['local_folder']));
		foreach ($filesList as $currentFile) {
			if (mb_strtolower(SYSTEM::$file_os->get_ext($currentFile), 'UTF-8') != 'xlsx') continue;

			$tmpName = str_replace(' ', '', mb_strtolower(SYSTEM::$file_os->get_name($currentFile), 'UTF-8'));
			if (strpos($tmpName, $region) !== false) {
				debug_mess('getXlsxByRegion: нашли район по названиям иксельки: ' . $currentFile);
			    return $currentFile;
			}

			// попробуем еще поискать без ГО и ТО
			// @deprecated
			/*$withoutGoTo = str_replace(' ', '', str_replace(['го', 'то'], '', $region));
			if (strpos($tmpName, $withoutGoTo) !== false) {
				debug_mess('INFO: нашли район по названиям иксельки: ' . $currentFile);
			    return $currentFile;
			}*/
		}

		return false;
	}

	// скопировать все иксельки из сетевой папки в локальную
	private function copyAllXlsxFromShared() {
		global $mySettings, $robotFolder;

		SYSTEM::$folder->clear($mySettings['local_folder']);
		debug_mess('ИНФО: удалили все скачанные в прошлый раз локальные иксельки');

		$filesList = explode('<br>', SYSTEM::$folder->get_all_items($mySettings['network_folder_test']));

		foreach ($filesList as $currentFile) {
		    $myext = strtolower(SYSTEM::$file_os->get_ext($currentFile));
			$FROM = $currentFile;
			$TO = $robotFolder . $mySettings['local_folder'] . SYSTEM::$file_os->get_name($currentFile);

			if (($myext != 'xlsx') || (strpos($currentFile, '~') !== false)) continue;
			SYSTEM::$file_os->copy($FROM, $TO);
			debug_mess('Скопировали иксельку из сетевой папки в локальную ' . $currentFile);
		}
	}

	// скопировать все иксельки из из локальной в сетевую
	private function copyAllXlsxToShared() {
		global $mySettings, $robotFolder;

		foreach ($this->changedFilesList as $currentFile) {
			if (strpos($currentFile, '!') !== false) {
				debug_mess('ИАС: файл реестра содержит восклицательный знак, пропускаем...');
				continue;
			}

			$FROM = $currentFile;
			$TO = $mySettings['network_folder_test'] . SYSTEM::$file_os->get_name($currentFile);

			SYSTEM::$file_os->copy($FROM, $TO);
			debug_mess('Скопировали иксельку из локальной папки в сетевую ' . $currentFile);
		}
	}

	// загрузка иксельки с ИАС
	private function iasDownload ($ias_start, $ias_end) {
		global $wt, $mySettings;

		$ias_start = str_replace(':', '-', $ias_start);
		$ias_end = str_replace(':', '-', $ias_end);
		  
		$outfilename = $mySettings['iasFolder'] . '78_DWNLD_' . $ias_start . '_' . $ias_end . '.xlsx';

		$tmpElem = DOM::$anchor->get_by_name("ReportLinkMenu");
		$tmpElem->focus();
		sleep($wt);
		$tmpElem->send_mouse_click(5, 5);
		sleep($wt);

		$tmpElem = DOM::$td->get_by_inner_text("Данные");
		$tmpElem->focus();
		sleep($wt);
		$tmpElem->send_mouse_click(5, 5);
		sleep($wt);
		DOM::$td->click_by_inner_text("Excel", false);

		//DOM::$td->click_by_inner_text("Excel", false);
		$tmpElem = DOM::$td->get_by_inner_text("Excel");
		$tmpElem->focus();
		sleep($wt);
		
		// download here
		WINDOW::$window->execute_open_file('Сохранение', $outfilename, '&Сохранить', false, true);
		sleep($wt * 2);

		$tmpElem->click();
		sleep($wt);

		if (!wait_on_element_by_text(DOM::$div, 'Процесс экспорта завершен.', -1, 30)) {//FIXME: timer
		    debug_mess('ИАС:ОШИБКА: не смогли скачать иксельку!');
			return false;
		}

		DOM::$anchor->click_by_name("OK");

		$path = WEB::$browser->wait_download_and_get_file_path(3000);
		debug_mess('ИАС: скачали выгрузку в ' . $path);
		SYSTEM::$file_os->move($path, $outfilename);
		debug_mess('ИАС: переместили выгрузку в ' . $outfilename);

		return $outfilename;
	}

	// функция возвращает номер пп по номеру строки на листе
	private function getTableNumberByRowNumber ($CURRENTSHEET, $foundrow, $ias_action, $region_startrow) {
		$region_maxrow = count($CURRENTSHEET);
		$return_value = -1;

		if ($ias_action != 'новая строка') {
			// -1, потому что 4 строки в шапке, а это 5 первая для цикла
			$return_value = $foundrow - ($region_startrow + 1);
		} else {
			// количество строк +1 за минусом шапки в 4 строки
			$return_value = ($region_maxrow + 1) - ($region_startrow + 1);
		}

		debug_mess('getTableNumberByRowNumber: $foundrow:№ п\п = ' . $foundrow . ':' . $return_value);
		return $return_value;
	}

	// функция правильной разлиновки (форматирования границ) каждой новой добавленной строки в иксельку
	private function formatMeLastLines ($foundrow, $maxrownum, $xlsxpath) {
		$prevrow = $foundrow - 1;

		// новая строка прям новая-новая
		if ($foundrow > $maxrownum) {
			// сперва с границ предыдущей последней строки уберем жироту
			for ($i = 2; $i < 25; $i++) {
				SYSTEM::$excelfile->set_cell_border($xlsxpath, 0, $prevrow, $i, 0, 13,"all");
			}

			// и для последней строки эту жироту и добавим
			for ($i = 2; $i < 25; $i++) {
				SYSTEM::$excelfile->set_cell_border($xlsxpath, 0, $foundrow, $i, 0, 13, "all");
				SYSTEM::$excelfile->set_cell_border($xlsxpath, 0, $foundrow, $i, 0, 6, "bottom");
			}

			// и по бокам строки жирота тоже
			SYSTEM::$excelfile->set_cell_border($xlsxpath, 0, $foundrow, 2, 0, 6, "left");
			SYSTEM::$excelfile->set_cell_border($xlsxpath, 0, $foundrow, 24, 0, 6,"right");
		}
	}

	// разные форматы строки: высота и т.д.
	private function formatMeCurrentRow ($foundrow, $xlsxpath) {
		// выставляем высоту
		SYSTEM::$excelfile->set_row_height($xlsxpath, 0, $foundrow, 66);
		// autoheight
		//SYSTEM::$excelfile->autosize_row($xlsxpath, 0, $foundrow);
		// автосайз по ширине отделения
		SYSTEM::$excelfile->autosize_col($xlsxpath, 0, 8);
		// автосайз по ширине номера заявки в СУВК
		SYSTEM::$excelfile->autosize_col($xlsxpath, 0, 4);
		// автосайз по ширине номера следующего через слэши (есть отделения, где перепутаны столбцы)
		SYSTEM::$excelfile->autosize_col($xlsxpath, 0, 5);
	}
}
