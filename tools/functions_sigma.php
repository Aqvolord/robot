<?php

function ebsVygruzkaEmpty(): bool
{
    global $excelfile;
    global $path_excel_data, $col_list, $sheet;

    $result = true;

    $excelfile->close($path_excel_data);
    $excelfile->open($path_excel_data);

    $rows = $excelfile->get_rows_count($path_excel_data, $sheet);
    if ($rows < 2) { $rows = 2; }

    for ($i = 2; $i <= $rows; $i++)
    {
        $value = $excelfile->get_cell($path_excel_data, $sheet, $i, $col_list);
        $value = @trim($value);

        if ($value === '') { continue; }

        $result = false;
        break;
    }
    $excelfile->close($path_excel_data);

    return $result;
} // ebsVygruzkaEmpty

function showAllPageContent(string $title): void
{
	global $span;

	$tryCount = 3;
	for ($i=1; $i<=$tryCount; $i++, sleep(1))
	{
		$span->get_by_inner_text($title, true)->send_mouse_click(3, 3);
		sleep(1);

		if ($span->get_by_inner_text($title, true)->get_x() >= 0)
		{
			break;
		}
	}
}

// выполнить заход в систему
function login_sigma($login, $pwd)
{
    global $input, $btn, $anchor, $label, $wt;

	// заходим в систему
	if($input->is_exist_by_name("userName"))
	{
		debug_mess("login_sigma: выполняем вход в систему");
		$input->set_value_by_name("userName", $login);
        $inp = $label->get_by_inner_text("Пароль:")->get_next()->get_child_by_number(0);
        $inp ->set_value($pwd);
		DOM::$btn->click_by_number(2);
        sleep($wt);
	}
	
	while (true) {
		if (!wait_on_element_by_text(DOM::$span, 'Не все поля заполнены')) {
			debug_mess('login_sigma: ОК: прошли все диалоги про поля');
			return true;
		}
		debug_mess('login_sigma: встретили диалог, кликаем...');
		DOM::$div->get_by_inner_html('&nbsp;')->send_mouse_click(7, 7);
		sleep($wt);
		DOM::$btn->click_by_number(2);
		sleep($wt);
	}

    return true;
}

// ////////////// работа с фильтрами ////////////////////////////
// кликнуть на стрелочку в заголовке
function click_on_arrow_by_text($text)
{
    global $div; 

	$dv = $div->get_by_inner_text($text);
	$arrow = $dv->get_child_by_number(0);
	return $arrow->click(3,3);
}

// кликнуть на стрелочку в заголовке
function click_on_arrow_by_class($text)
{
    global $div; 

	$dv = $div->get_by_attribute("class",$text);
    //echo $dv->get_inner_text();
	$arrow = $dv->get_child_by_number(0);
	return $arrow->click();
}

// выбор фильтра
function select_filter_check($text,$filterText)
{
    global $anchor,$body,$wt;

	click_on_arrow_by_text($text);

	sleep($wt);
	$an = $anchor->get_by_inner_text("Фильтр");
    $str = $an->get_parent()->get_attribute("class");
    $bcheck_filt=strpos($str,"checked");
	$an->send_mouse_move(5,5);
	sleep($wt);
	$an1 = $anchor->get_by_inner_text($filterText);
    $str = $an1->get_parent()->get_attribute("class");
    $bcheck_sub=strpos($str,"checked");
    if($bcheck_filt && $bcheck_sub)
    {
       debug_mess("Фильтр уже выбран");
       return true;
    }
    else if($bcheck_filt && !$bcheck_sub)
    {
       debug_mess("ОШИБКА:select_filter_check:Другой Фильтр уже выбран!");
       return false;
    }
    else if(!$bcheck_filt && $bcheck_sub)
    {
       debug_mess("Кликаем на главный фильтр!");
       $an->send_mouse_click(15,5);
       return false;
    }
	$an1->send_mouse_click(15,5);

	//$an1->click(15,5);
	sleep($wt);
	$body->get_by_number(0)->send_mouse_click(15,5);
    
   return true;
}

// сбросить фильтр
function reset_filter($text, $as_class=false,$pause=1500)
{
	global $anchor,$input,$wt;

	// кликнуть на стрелку
	if($as_class)
	click_on_arrow_by_class($text);
	else
	click_on_arrow_by_text($text);

	usleep($pause);
	$an = $anchor->get_by_inner_text("Фильтр");
	$an->send_mouse_click(5,5);
	usleep($pause);
}

// выбор фильтра
function select_filter($text, $dt, $as_class=false,$pause=1500,$cur_cnt=0,$cnt=3)
{
    global $anchor,$input,$wt,$li,$app;

    // кликнуть на стрелку
    if($as_class)
	   click_on_arrow_by_class($text);
    else
       click_on_arrow_by_text($text);

    usleep($pause);
	$an = $anchor->get_by_inner_text("Фильтр");
	$an->send_mouse_move(5,5);
	usleep($pause);
    // получаем через меню
	$ls = $li->get_all_by_attribute("class","x-menu-list-item", false);
    usleep($pause);
    //print_r($ls->get_number());
	$b_found=false;
	foreach($ls as $li_item)
	{
		if($li_item->is_view_now() and $li_item->get_child_by_number(1)->get_tag()=="INPUT")
		{
        	$in = $li_item->get_child_by_number(1);
			$in->set_value("");  
			usleep($pause);
			$in->set_value($dt);  
            usleep($pause);
			$in->set_value($dt);  
			if($in->get_value()!=$dt)  
			{  
				$in->set_value($dt);  
                usleep($pause);
				$in->set_value($dt);  
				//usleep(300);
				//$in->send_input($dt);  
			}
			$b_found=true;
			$in->click();
		
		}  
	} 

    // проверка нашли ли 
    if(!$b_found)
    {
        if($cur_cnt>$cnt)
        {
           debug_mess("ОШИБКА: не нашли поле фильтра"); 
           return false;
        }

        $cur_cnt=$cur_cnt+1;

        $anchor->click_by_inner_text("Фильтр");
		return select_filter($text, $dt, $as_class,$pause,$cur_cnt++);
    } 
    usleep($pause);
	$anchor->click_by_inner_text("Фильтр");
    usleep($pause);
}

// выбрать фильтр у заданного поля
function select_filter_old($text, $dt, $as_class=false,$pause=1500)
{
    global $anchor,$input,$wt,$li,$app;

    // кликнуть на стрелку
    if($as_class)
	   click_on_arrow_by_class($text);
    else
       click_on_arrow_by_text($text);

    usleep($pause);
	$an = $anchor->get_by_inner_text("Фильтр");
	$an->send_mouse_move(5,5);
	usleep($pause);
    // получаем через меню
	$ls = $li->get_all_by_attribute("class","x-menu-list-item", false);
    usleep($pause);
    //print_r($ls->get_number());
	$b_found=false;
	foreach($ls as $li_item)
	{
		if($li_item->is_view_now() and $li_item->get_child_by_number(1)->get_tag()=="INPUT")
		{
        	$in = $li_item->get_child_by_number(1);
			$in->set_value("");  
			usleep($pause);
			$in->set_value($dt);  
            usleep($pause);
			$in->set_value($dt);  
			if($in->get_value()!=$dt)  
			{  
				$in->set_value($dt);  
                usleep($pause);
				$in->set_value($dt);  
				//usleep(300);
				//$in->send_input($dt);  
			}
			$b_found=true;
			$in->click();
		
		}  
	} 
    // 
    if(!$b_found)
    {
        debug_mess("ОШИБКА: не нашли поле фильтра");
		return false;
    } 
    usleep($pause);
	$anchor->click_by_inner_text("Фильтр");
    usleep($pause);
}
// ///////////////////////////////////////////////////////////////

// получить значения колокни по общему родителю
function get_column_value_by_parent($name, $num=0, $all=false, $parent_num=7)
{
    global $td;
	// нашли по тексту
	$hd_item = $td->get_by_inner_text($name);
	// класс заголовка
	$class_hd = $hd_item->get_attribute("class");
	// получили id общего родителя
	$prt_id = $hd_item->get_parent($parent_num)->get_attribute("id");

	//debug_mess($prt_id);
	// класс ячейки
	$class_col = str_replace("hd","col", $class_hd);
	// !!! проверить если в таблице несколько рядов
	$values = $td->get_all_by_attribute("class",$class_col); 

	$arr=array();
	foreach($values as $col_item)
	{
       $cur_rpt_id = $col_item->get_parent($parent_num)->get_attribute("id");
       //echo "$cur_rpt_id == $prt_id";
	   if(/*$col_item->is_view_now() and*/ $cur_rpt_id==$prt_id)
	   {
			$arr[] = $col_item->get_inner_text();
	   }
	}

    // проверяем результат
    if(count($arr)<=0)
    {
      debug_mess("ОШИБКА:get_column_value: Не получили ни одного значения в колонке ");
      return false; 
    }

    // получить все значения ввиде массива
    if($all)
       return $arr;

    return $arr[$num];
}
// получить значение из таблицы по имени заголовка
function get_column_value($name, $num_row=0)
{
    global $td;
    // получить класс заголовка
	$class_hd = $td->get_attribute_by_inner_text($name, true, "class");
    // класс ячейки
	$class_col = str_replace("hd","col", $class_hd);
	// !!! проверить если в таблице несколько рядов
	$value = $td->get_all_inner_texts_by_attribute("class",$class_col)[$num_row];

    if($value=="")
      return false;

    return $value;	
}

// получить нужные данные из файла в массив для дальнейше работы
function get_nsd_data()
{
    global $excelfile,$file_path,$column_dog,$clmn_dog,
           $column_pd,$clmn_pd,$column_period,$clmn_period,
           $column_invoice,$clmn_invoice,$arr_nsd_data,
           $column_consum,$clmn_consum, $column_sum, $clmn_sum,$merge_fileld ;

    // открыть файл
    $excelfile->open($file_path);
	// получить общее количество рядов и колонок
	$rows_cnt = $excelfile->get_rows_count($file_path,0);
	$col_cnt= $excelfile->get_cols_count($file_path,0);

	debug_mess("всего в файле рядов $rows_cnt и колонок $col_cnt");
    
    $merge_range ="";
	// найдём нужные номера колонок с данными
	for($ii=1;$ii<=$rows_cnt;$ii++)
	{
		for($jj=1;$jj<=$col_cnt;$jj++)
		{
			$value = trim($excelfile->get_cell($file_path,0,$ii,$jj));
            //debug_mess($jj." ".$value);
            if($value==$merge_fileld)
            {
               $merge_range = $excelfile->get_merged_cell_range($file_path,0,$ii,$jj);
                // print_r($merge_range);
            }
			// echo "ряд: $ii колонка $jj значение $value <br>";
			if($value==$column_dog)
			{
				// получаем стартовый ряд
				$start_row=$ii+3;
				// получаем номер колонки
				$clmn_dog = $jj; 
			}

			if($value==$column_period)
			{
				// получаем номер колонки
				$clmn_period = $jj; 
			}

			if($value==$column_invoice)
			{
				// получаем номер колонки
				$clmn_invoice = $jj; 
			}

			if($value==$column_pd)
			{
				// получаем номер колонки
				$clmn_pd = $jj; 
			}
            // расход
			if($value==$column_consum and $jj>=$merge_range->Left and $jj<=$merge_range->Right)
			{
				// получаем номер колонки
				$clmn_consum = $jj; 
			}

            // сумма для проверки
			if($value==$column_sum and $jj>=$merge_range->Left and $jj<=$merge_range->Right)
			{
				// получаем номер колонки
				$clmn_sum = $jj; 
			}

			if($clmn_dog!=-1 and $clmn_period!=-1 and $clmn_invoice!=-1 and $clmn_pd!=-1 and $clmn_consum!=-1 and $clmn_sum!=-1)
			   break;
		   
		}
	}
	// проверяем нашли ли мы в файле номер договора
	if($start_row==-1 and $column_num==-1)
	{
	   debug_mess("ОШИБКА: не смогли определить в файле ряд с данными и колонку договора");
	   $app->quit();
	}

	debug_mess("начинаем собирать данные с ряда $start_row");

	// получить значение договора или несколько значений
	for($ii=$start_row;$ii<=$rows_cnt;$ii++)
	{

		// проверить если формат файла с ещё одним заголовком
		if(strpos($excelfile->get_cell($file_path,0,$ii,$clmn_dog),"Номер договора")!==false)
		  continue;

		$data_nsd = new NSDData();
		//  номер догорова
		$data_nsd->num_dogovor = $excelfile->get_cell($file_path,0,$ii,$clmn_dog);
		// номер пд 
		$data_nsd->num_pd= $excelfile->get_cell($file_path,0,$ii,$clmn_pd);
		//  «Период(ы), за который(е) необходимо провести перерасчет»
		$data_nsd->period= $excelfile->get_cell($file_path,0,$ii,$clmn_period);
        $data_nsd->period=str_replace("0:00:00","",$data_nsd->period);
  
		// Вид счет фактуры (СФ, КСФ)
		$data_nsd->invoice_type= $excelfile->get_cell($file_path,0,$ii,$clmn_invoice);
		// Дата документа - получить последний день месяца
		$data_nsd->doc_date= date('t.m.Y',strtotime($data_nsd->period));

        // После перерасчета
        // Объем э/э, кВт.ч
        $data_nsd->consum = $excelfile->get_cell($file_path,0,$ii,$clmn_consum);
        // Сумма с НДС,  руб.
        $data_nsd->sum= $excelfile->get_cell($file_path,0,$ii,$clmn_sum);
		//debug_mess($data_nsd->display());
		// в массив с данными
		$arr_nsd_data[] =$data_nsd;
	}
    // закрыть файл
    $excelfile->close($file_path);
}

// выбрать отражение в документах
function set_ref_in_documents($text)
{
    global $input,$div;

    $value = $input->get_value_by_name("calculateMode");
    // проверяем текущее значение
    if($value==$text)
       return true;

	$input->click_by_value($value);
    // получем значения
	$dvs = $div->get_all_by_attribute("class","x-combo-list-item");

	foreach($dvs as $dv)
	{
		$inntext = str_replace("&nbsp;","",$dv->get_inner_html());
		if($inntext=="")
			continue;
        // кликнуть по нужному тексту
		if($inntext==$text)	
		{
			$dv->send_mouse_move(5,5);
			$dv->send_mouse_click(5,5);
		}
	}

	if($input->get_value_by_name("calculateMode")!=$text)
       return false;

     return true;
}

//  получить номер фд 
function get_fd_number($doc_date)
{
    global $anchor, $wt, $btn, $browser, $div, $wt_long;
	$ar_date = explode(".",$doc_date);
	$dt= $ar_date[2]."-".$ar_date[1];

	// получить фд
	$anchor->click_by_inner_text("Фин. документы");

	sleep($wt);
	// вариант с № пд
	$btn->click_by_inner_text("Перейти к ...",);

	$anchor->click_by_inner_text("ЛК: Счета-фактуры");
    // ожидание
    if(!wait_on_element_by_text($btn, "Вывести"))
    {
        debug_mess("ОШИБКА:get_fd_number: не дождались кнопку Вывести");
        return false;
    }
	$btn->click_by_inner_text("Вывести");
    sleep($wt_long);

    // ожидаем пока на странице есть элемент с текстом Загрузка...
    wait_exists_element_by_text($div, "Загрузка...");
    // ожидание
    if(!wait_on_element_by_att($div, "class", "x-grid3-cell-inner x-grid3-col-17"))
    {
        debug_mess("ОШИБКА:get_fd_number: не дождались загрузки ячейки таблицы");
        return false;
    }

	// выбираем фильтр для Период потребления
	select_filter("x-grid3-hd-inner x-grid3-hd-17", $dt, true);

	//$value = get_column_value("Номер ФД");
    $value = get_column_value_by_parent("Номер ФД");

	if(!$value)
	  return false;
	
    $browser->close_all_tabs();

    return $value;
}

// получить дату на русском в формате Месяц ГГГГ 
function get_date_ru()
{
    $month = get_ru_month(date("m"));

    return $month." ".date("Y");
}

// получить месяц на русском
function get_ru_month($m)
{
    $_monthsList = array(
	"01"=>"Январь","02"=>"Февраль","03"=>"Март",
	"04"=>"Апрель","05"=>"Май", "06"=>"Июнь",
	"07"=>"Июль","08"=>"Август","09"=>"Сентябрь",
	"10"=>"Октябрь","11"=>"Ноябрь","12"=>"Декабрь");
	 
	$month = $_monthsList[$m];
     
    return $month;
}

// задать дату 
function set_date($data_period)
{
    global $input, $btn;
   // 23.	В поле «Начисления за период» выбрать необходимый период
   // получить из даты период в нужном формате
    $arr_dt = explode(".",$data_period);
	$period = trim(get_ru_month($arr_dt[1]))." ".trim($arr_dt[2]);
	// получить поле ввода даты
    $inps = $input->get_all_by_value(get_date_ru());
    //if($inps[1]!==false)
    $bfound=false;
	foreach($inps as $in)
	{
		if($in->is_view_now())
		{
			//echo $in->get_id();      
			$inps[1]->event("onclick");
			usleep(300);
			$inps[1]->set_value($period);
			usleep(300);
			$inps[1]->event("onkeyup");
			usleep(300);
			$inps[1]->event("onchange");
			usleep(300);

            if($inps[1]->get_value()!==$period)
            {
				$inps[1]->event("onclick");
				usleep(300);
				$inps[1]->set_value($period);
				usleep(300);
				$inps[1]->event("onkeyup");
				usleep(300);
				$inps[1]->event("onchange");
				usleep(300);
            }
			// задать период
			$btn->get_by_inner_text("Загрузить список")->send_mouse_click(5,5);

            $bfound=true;
		}
	}

    if(!$bfound)
	{
	   debug_mess("ОШИБКА:шаг 28: нет даты в поле Начисления за период");
	   return false;
	}
    
    return true;
}

// получить значение из таблицы по тексту
// text - из колонки Характеристика
// результат - из колонки Значение
function get_table_value($text)
{
	global $td;

	//Нужная ячейка в столбце Характеристика
	$elTd = $td->get_by_inner_text($text);
	// Нужная ячейка в столбце Значение
	$elTdValueCell = $elTd->get_next();
	$oldValue = @trim($elTdValueCell->get_inner_text());
	return $oldValue;
}

// Задать значение в таблице по предыдущему значению
// text - из колонки Характеристика
// value - в колонку Значение
function input_table($text, $value, $isGap = false): bool
{
	global $td, $input;

	$value = @trim($value);
	if($value === "") { return false; }

	$result = false;
	$tryCount = 10;
	for ($i = 1; $i <= $tryCount; $i++, sleep(mt_rand(3, 10)))
	{
		debug_mess("Задаём значение (попытка #{$i}) '{$text}': {$value}");

		// Нужная ячейка в столбце Характеристика
		$elTd = $td->get_by_inner_text($text);
		// Нужная ячейка в столбце Значение
		$elTdValueCell = $elTd->get_next();

		$oldValue = @trim($elTdValueCell->get_inner_text());
		debug_mess("Старое значение '{$text}': " . var_export($oldValue, true));

		// скроллим, чтобы строка была видна
		$elTd->meta_click();
		sleep(1);

		// Делаем input редактируемым
		$elTdValueCell->event('onclick');
		sleep(1);

		// получаем активный input внутри elTdValueCell
		$elInputTmp = $input->get_by_attribute('class', 'x-form-focus', false);
		if ( (!$elInputTmp->is_exist()) or
			($elInputTmp->get_tag() !== 'INPUT') or
			($elInputTmp->get_x() <= 0) )
		{
			debug_mess("Не смогли получить доступ к inputTmp для ввода значения");
			continue;
		}

		// Получаем input attr id
		$inputId = $elInputTmp->get_id();
		debug_mess("[ОТЛАДКА] input attr id (столбец начение): "
			. var_export($inputId, true));
		if ( ($inputId === false) or
			(@trim($inputId) === '') or
			(@trim($inputId) === '-1') )
		{
			debug_mess("Не смогли получить аттрибут id в input");
			continue;
		}

		// Получаем нужный input по attr id
		$elInput = $input->get_by_id($inputId, true);
		if ( (!$elInput->is_exist()) or
			($elInput->get_tag() !== 'INPUT') or
			($elInput->get_x() <= 0) )
		{
			debug_mess("Не смогли получить доступ к input (по attr id) для ввода значения");
			continue;
		}

		if($isGap == true and strlen(get_table_value($text)) > 2)
		{
			debug_mess("В поле уже есть значение");
			$result = true;
			break;
		}

		// вводим значение
		$elInput->set_value($value);
		$elInput->event('onchange');
		sleep(1);

		// делаем инпут не вводимым
		$elTd->click();
		sleep(1);

		// проверяем ввелось ли значение
		$valueTest = get_table_value($text);
		if ($value !== $valueTest)
		{
			debug_mess("Не можем подтвердить, что значение ввелось:"
				. PHP_EOL . var_export($value, true)
				. PHP_EOL . var_export($valueTest, true) );
			continue;
		}

		debug_mess("Значение ввелось");
		$result = true;
		break;
	} // for

	return $result;
} // input_table


// внести данные в таблицу Задание: Контроль работы ССП.
function enter_data($list_id, $fssp_data)
{
	global $td, $div, $span, $btn;
	global $wt;
	global $wtEbsEditTableMax, $numsIspPrRaznieAction, $b_use_filter_field;
	global $app;

	debug_mess("Ищем в таблице ЕБС строчку с исп листом $list_id");

    // ожидаем на результаты в таблице
	debug_mess("[ОТЛАДКА] ожидаем на результаты в таблице $wtEbsEditTableMax сек");
    if(!wait_on_element_by_att($div, "class","x-grid3-row", -1, $wtEbsEditTableMax))
    {
        debug_mess("ОШИБКА: Нет результатов в таблице!");
        return false;
    }

	// использовать фильтрацию по настройке
	if($b_use_filter_field)
    {
	    select_filter_check("Тип задания","Контроль работы ССП");
        sleep($wt);

		// ожидаем пока на странице есть элемент с текстом Загрузка...
		wait_exists_element_by_text($div, "Загрузка...");
    }
	sleep($wt);

	// кликнуть на номер дела
	$td_item = $td->get_by_inner_text($list_id);

	$td_item1=$td_item->get_parent();
	$td_item1->send_mouse_click(5,5);
	sleep(1);
	$td_item1->send_mouse_double_click(5,5);

	// ожидаем пока на странице есть элемент с текстом Загрузка...
	sleep($wt);
	wait_exists_element_by_text($div, "Загрузка...");
	sleep($wt);

	// ждем пока подгрузятся данные в "Событие / Характеристика/ 2. Контроль работы ССП"
	$text = "Номер исполнительного производства";
    if(!wait_on_element_by_text($td,$text))
    {
        debug_mess("ОШИБКА: не дождались загрузки таблицы с открытым в ней исп листом");
        // закрыть таблицу
	    close_table();
        return false;
    }

	$mainTitle = $span
		->get_by_inner_text('Задание: Контроль работы ССП. Договор №', false)
		->get_inner_text();

	debug_mess("Вводим данные в ЕБС, исп лист {$resolution_item->bus_number}: " . $mainTitle);

	// Номер исполнительного производства
	$text = "Номер исполнительного производства";
	$isp_num = get_table_value($text);
	if( ($isp_num!="") && ($isp_num!=$fssp_data->num_iskp) )
	{
		debug_mess("разные номера дела для листа $isp_num и ".$fssp_data->num_iskp);
		if ($numsIspPrRaznieAction === 1) {}
		elseif ($numsIspPrRaznieAction === 2)
		{
			close_table();
			return false;
		}
	}

	//slashka 2023-09-27
	$fssp_data->num_iskp = str_replace([' ', '№', '#'], '', $fssp_data->num_iskp);

	if (!input_table($text, $fssp_data->num_iskp))
	{
		close_table();
		return false;
	}
	showAllPageContent($mainTitle);

	// Дата постановления о возбуждении исполнительн. производства
	$text = "Дата постановления о возбуждении исполнительн. производства";
	$date_iskp = get_table_value($text);
	if( ($date_iskp!="") && ($date_iskp!=$fssp_data->date_iskp) )
	{
		debug_mess("Разные Даты постановления о возбуждении $date_iskp и ".$fssp_data->date_iskp);
	}
	if (!input_table($text, $fssp_data->date_iskp))
	{
		close_table();
		return false;
	}
	showAllPageContent($mainTitle);

	// ФИО пристава
	$text = "ФИО пристава";
	$fio_bail = get_table_value($text);
	if( ($fio_bail !="") && ($fio_bail !=$fssp_data->fio_bail) )
	{
		debug_mess("Разные ФИО пристава $fio_bail  и ".$fssp_data->fio_bail );
	}
	if (!input_table($text, $fssp_data->fio_bail))
	{
		close_table();
		return false;
	}
	showAllPageContent($mainTitle);

	//Дата постановления об окончании исполнительного производства
	$text = "Дата постановления об окончании исполнительного производства";
	$date_end_iskp = get_table_value($text);
	if( ($date_end_iskp!="") && ($date_end_iskp!=$fssp_data->date_end_iskp) )
	{
		debug_mess("Разные Дата постановления об окончании $date_end_iskp  и ".$fssp_data->date_end_iskp );
	}
	if (!input_table($text, $fssp_data->date_end_iskp))
	{
		close_table();
		return false;
	}
	showAllPageContent($mainTitle);

	//Сведения об исполнительном производстве
	$text = "Сведения об исполнительном производстве";
	$SOIPRes = false;
	if($fssp_data->date_end_iskp=="") { $SOIPRes = input_table($text, "в процессе"); }
	else { $SOIPRes = input_table($text, "окончено"); }
	sleep($wt);
	if (!$SOIPRes)
	{
		close_table();
		return false;
	}
	showAllPageContent($mainTitle);

	// сохранить и закрыть
	save_table();
	close_table();

	return true;
} // enter_data

// сохранить таблицу
function save_table($num=2)
{
    global $btn,$wt,$div;

	debug_mess("[ОТЛАДКА] save_table");

    $bt = $btn->get_all_by_inner_text("Сохранить")[$num];
    if(!$bt)
       return false;

    $btn->get_all_by_inner_text("Сохранить")[$num]->focus();
    sleep($wt);
    $btn->get_all_by_inner_text("Сохранить")[$num]->send_mouse_click(5,5);
    //if(!$bt)
	//$btn->get_all_by_inner_text("Сохранить")[$num]->click(5,5);
    sleep($wt);
	// ожидаем пока на странице есть элемент с текстом Загрузка...
    wait_exists_element_by_text($div, "Сохранение...");
    sleep($wt);
	wait_exists_element_by_text($div, "Загрузка...");    
    sleep($wt);
    // проверить есть ли сообщение
	if($div->is_exist_by_inner_text("Ошибка заполнения",false))
	{
		$btn->get_by_inner_text("OK")->send_mouse_click();
		$btn->get_by_inner_text("OK")->click();
	}
    sleep($wt);

    return true;
}

// нажать на кнопку закрыть
function close_table($num=3)
{
    global $btn,$wt,$div;

	debug_mess("[ОТЛАДКА] close_table");

    $bt = $btn->get_all_by_inner_text("Закрыть")[$num];
    if(!$bt)
       return false;

    $btn->get_all_by_inner_text("Закрыть")[$num]->focus();
    sleep($wt);
	$btn->get_all_by_inner_text("Закрыть")[$num]->send_mouse_click(5,5);
    $bt = $btn->get_all_by_inner_text("Закрыть")[$num];
    if($bt!==false)
       $btn->get_all_by_inner_text("Закрыть")[$num]->click(5,5);

	// ожидаем пока на странице есть элемент с текстом Загрузка...
	wait_exists_element_by_text($div, "Загрузка...");
	sleep($wt);
	if($btn->is_exist_by_inner_text("Да"))
	   $btn->click_by_inner_text("Да");
	sleep($wt);
	if($btn->is_exist_by_inner_text("OK"))
	   $btn->click_by_inner_text("OK");
	sleep($wt);
	wait_exists_element_by_text($div, "Сохранение...");
	sleep($wt);
	wait_exists_element_by_text($div, "Загрузка...");

    return true;
}

// загрузить файл из системы с данными всей таблицы
function download_file()
{
    global $btn, $browser, $file_os;
    global $libreoffice, $excel;
    global $xlsConverterType, $wt, $path_excel_data, $libreOfficeInstallFolder;

    if ($xlsConverterType === 'libreoffice') { $libreoffice->set_install_folder($libreOfficeInstallFolder); }

   // проверям кнопку скачать
   if(!$btn->is_exist_by_inner_text("В Excel"))
   {
       debug_mess("ОШИБКА: Нет кнопки скачать в Excel");
       return false;
   }

	debug_mess("Скачиваем файл ...");

	// нажать на кнопку выгрузить в excel
	$btn->click_by_inner_text("В Excel");
	sleep($wt);

	// получить id загрузки
	$idd = $browser->get_last_download_id();
	while(!$browser->is_download_complete($idd))
	{
		debug_mess("Скачиваем файл (ожидаем пока загрузка завершится) ...");
		sleep($wt);
	}

	// получаем путь к скаченному
	$path = $browser->get_download_info($idd, "save_to");

	// ждём появления файла
    sleep($wt);
	if(!$file_os->wait_for_exist($path))
    {
        debug_mess("ОШИБКА: ждём появления файла: не дождались появления файла: {$path}");
        return false;
    }
    debug_mess("Скачали успешно: {$path}");

	// конвертируем
    debug_mess("Конвертируем ({$xlsConverterType}) ..."
        . PHP_EOL . "Из: '{$path}'."
        . PHP_EOL . "В: '{$path_excel_data}'.");
    if ($xlsConverterType === 'libreoffice')
    {
        $libreoffice->convert($path, $path_excel_data);
    }
    else
    {
        $excel->convert($path, $path_excel_data);
    }

	// ждём появления файла
    debug_mess("Ждём результата конвертирования ...");
	return $file_os->wait_for_exist($path_excel_data);
} // download_file


// Добавлено для робота 46

// ДЛЯ ПОЧТЫ 
// Проверка наличия заданий 
function enter_data_resolution()
{
	global $wt, $long_wt, $wtEbsEditTableMax;
	global $wtEbsEditTableMax, $numsIspPrRaznieAction, $b_use_filter_field;
	global $div;
	global $resolution_item;
	debug_mess("Ищем в таблице ЕБС строчку с исп листом {$resolution_item->bus_number}");

    // ожидаем на результаты в таблице
	debug_mess("[ОТЛАДКА] ожидаем на результаты в таблице $wtEbsEditTableMax сек");
    if(!wait_on_element_by_att($div, "class","x-grid3-row", -1, $wtEbsEditTableMax))
    {
        debug_mess("ОШИБКА: Нет результатов в таблице!");
        return false;
    }

	// использовать фильтрацию по настройке
	if($b_use_filter_field)
    {
	    select_filter_check("Тип задания","Контроль работы ССП");
        sleep($wt);

		// ожидаем пока на странице есть элемент с текстом Загрузка...
		wait_exists_element_by_text($div, "Загрузка...");
    }
	sleep($wt);
	return true;
}

// внести данные в таблицу Задание: Контроль работы ССП.
function send_data($resolution_item, $resolution)
{ //ДЛЯ ПОЧТЫ конец
	global $td, $div, $span, $btn, $browser, $table, $input, $window, $label;
	global $wt, $wt_long;
	global $wtEbsEditTableMax, $numsIspPrRaznieAction, $b_use_filter_field;
	global $app;

	// кликнуть на номер дела
	$td_item = $td->get_by_inner_text($resolution_item->bus_number);

	$td_item1=$td_item->get_parent();
	$td_item1->send_mouse_click(5,5);
	sleep(1);
	$td_item1->send_mouse_double_click(5,5);

	// ожидаем пока на странице есть элемент с текстом Загрузка...
	sleep($wt);
	wait_exists_element_by_text($div, "Загрузка...");
	sleep($wt);

	$text = 'Сведения об исполнительном производстве';
	if (!input_table($text, 'Нет', true))
	{
		close_table();
		return false;
	}
	

	// ждем пока подгрузятся данные в "Событие / Характеристика/ 2. Контроль работы ССП"
	$text = "Номер исполнительного производства";
    if(!wait_on_element_by_text($td,$text))
    {
        debug_mess("ОШИБКА: не дождались загрузки таблицы с открытым в ней исп листом");
        // закрыть таблицу
	    close_table();
        return false;
    }

	$mainTitle = $span
		->get_by_inner_text('Задание: Контроль работы ССП. Договор №', false)
		->get_inner_text();

	debug_mess("Вводим данные в ЕБС, исп лист {$resolution_item->bus_number}: " . $mainTitle);

	// Номер исполнительного производства
	$text = 'Номер исполнительного производства';

	//slashka 2023-09-27
	$resolution_item->perform_number = str_replace([' ', '№', '#'], '', $resolution_item->perform_number);

	if($resolution_item->perform_number !="")
	{
		if (!input_table($text, $resolution_item->perform_number))
		{
			close_table();
			return false;
		}
		showAllPageContent($mainTitle);
	}

	// Дата постановления о возбуждении или прекращении исполнительн. производства
	if($resolution_item->type == 'init')
	{
		$text = "Дата постановления о возбуждении исполнительн. производства";
	}else{
		$text = "Дата постановления об окончании исполнительного производства";
	}
	
	//$date_iskp = get_table_value($text);
	if($resolution_item->res_date !="")
	{
		if (!input_table($text, $resolution_item->res_date))
		{
			close_table();
			return false;
		}
		showAllPageContent($mainTitle);
	}
	
	// Ввод основания для окончания производства
	$text = 'Основание для окончания исполнительного производства';

	if($resolution_item->type != 'init' && $resolution_item->reason_short != '')
	{
		if (!input_table($text, $resolution_item->reason_short))
			{
				debug_mess('Не сработал ввод');
				close_table();
				return false;
			}
	}

	// ФИО пристава
	if($resolution_item->type == 'init')
	{
		$text = "ФИО пристава";
		$officer = get_table_value($text);
		debug_mess("$officer это пристав");
		if(isset($resolution_item->officer) && $resolution_item->officer != '')
		{
			debug_mess('Вводим пристава');
			if (!input_table($text, $resolution_item->officer))
			{
				debug_messege('Не сработал ввод');
				close_table();
				return false;
			}
		}
	}
	showAllPageContent($mainTitle);
	// сохранить
	save_table();
	sleep($wt);
	$span->get_by_inner_text("Характеристики процесса", false)->meta_click();
	sleep($wt_long);
	$span->get_by_inner_text("Характеристики процесса", false)->send_mouse_double_click(4,4);
	sleep($wt);

	// ожидаем пока на странице есть элемент с текстом Загрузка...
	wait_exists_element_by_text($div, "Загрузка...");
	sleep($wt);

	if(!$span->get_by_inner_text("Внешние документы", false)->send_mouse_double_click(4,4))
	{
	
		debug_mess("Не удается перейти к внешним документам");
		return false;
	}

	sleep($wt);
	// ожидаем пока на странице есть элемент с текстом Загрузка...
	wait_exists_element_by_text($div, "Загрузка...");

	if(!$div->get_by_inner_text('1. Характеристики процесса', false)->is_exist())
	{
		debug_mess("Не удается перейти к внешним документам (проверка по заголовку характерстики)");
		return false;
	}
	
	$doclist = $span->get_all_by_inner_text('Внешние документы', true);
	$base_vn = $doclist[1];
	if(!$base_vn)
	{
		debug_mess('Не удалось перейти к внешним документам');
		return false;
	}

	$cover = $base_vn->get_parent(5);
	echo($cover->get_number());
	$buttons = $btn->get_all_by_inner_text('Создать', true);
	$create_button = $buttons[1];

	echo ($create_button->get_number());
	$create_button->send_mouse_click(2, 2);

	if($span->is_exist_by_inner_text('Добавить новый документ?', false))
	{
		$btn->click_by_inner_text("Да", false);
	}

	// ожидаем пока на странице есть элемент с текстом Сохранение характеристики...
	wait_exists_element_by_text($div, "Сохранение характеристики...");
	wait_exists_element_by_text($div, "Загрузка...");
	sleep($wt_long);

	// Нажимаем на новую строку
	$table_part = $create_button->get_parent(15)->get_next();
	$table_for_click = $table_part->get_all_child_by_attribute('tag', 'TABLE', false, true);
	
	$cicle_end = false;
	foreach($table_for_click as $ii=>$table_item)
	{
		sleep($wt);
		debug_mess("Номер таблицы для клика: ".$table_item->get_number());
		if($ii==0) continue;
		$table_for_click[$ii]->send_key('down');
		sleep($wt);
		if(strlen($table_item->get_inner_text())<15)
		{
			$table_item->send_mouse_double_click(2,2);
			$cicle_end = true;
			//$btn->click_by_inner_text('Редактировать')->send_mouse_click(2,2);
			break;
		}
	}

	if(!$cicle_end)
	{
		debug_mess('Не удалось создать строку или найти пустую');
		return false;
	}
	wait_exists_element_by_text($div, "Загрузка...");
	
	$span->get_by_inner_text('Документ: Прикрепленный документ', false)->send_mouse_double_click();
	
	// Вводим дату документа
	$tds = $td->get_all_by_inner_text('Дата документа');
	$cell_date = $tds[count($tds)-1]->get_next();
	$cell_date->send_mouse_click(2,2);
	sleep($wt);
	$elInputTmp = $input->get_by_attribute('class', 'x-form-focus', false);
	if((!$elInputTmp->is_exist()) or
		($elInputTmp->get_tag() !== 'INPUT') or
		($elInputTmp->get_x() <= 0) )
	{
		debug_mess("Не смогли получить доступ к inputTmp для ввода значения");
	}
	$elInputTmp->send_input($resolution_item->res_date);
	sleep($wt);

	// Ввод номера документа
	if(isset($resolution_item->matter_number))
		$matter_num  = $resolution_item->matter_number;
	else $matter_num = $resolution_item->perform_number;

	debug_mess("Вводим номер дела ".$matter_num);
	$cell_num = $td->get_by_inner_text('№ документа')->get_next();
	$cell_num->send_mouse_double_click(2,2);
	sleep($wt);
	$elInputTmp = $input->get_by_attribute('class', 'x-form-focus', false);
	if((!$elInputTmp->is_exist()) or
		($elInputTmp->get_tag() !== 'INPUT') or
		($elInputTmp->get_x() <= 0) )
	{
		debug_mess("Не смогли получить доступ к inputTmp для ввода значения");
		debug_mess($elInputTmp->is_exist()?"Элемент найден":"Элемент не найден");
		debug_mess("Тэг это ".$elInputTmp->get_tag());
		debug_mess("Координата ".$elInputTmp->get_x());
	}
	$elInputTmp->send_input($matter_num);
	sleep($wt);

	// Прикрепляем документ
	$btn->get_by_inner_text('Прикрепить')->click();
	if(!$window->execute_open_file('ф', $resolution, 'Открыть'))
	{
		debug_mess('Не удалось прикрепить файл');
		return false;
	}
	$input_document = $label->get_by_inner_text('Документ:')->get_next();
	$input_document->send_mouse_double_click($input_document->get_width() - 4,4);

	$input_document = $label->get_by_inner_text('Имя документа:')->get_next()->get_child_by_attribute('tag', 'INPUT', true, true);
	$input_document->set_value($resolution_item->text_type());
	sleep($wt);

	$span->get_by_inner_text("Форма загрузки данных")->send_mouse_double_click();
	$btn->get_by_inner_text('Загрузить')->meta_click();
	sleep($wt);

	wait_exists_element_by_text($div, "Файл загружается на сервер...");
	
	$span->get_by_inner_text('Документ: Прикрепленный документ (Скан) (', false)->meta_click();
	sleep($wt);

	save_table();
	close_table();
	save_table();
	close_by_header_span('Внешние документы');
	sleep($wt);
	return true;
} // enter_data

// Закрывает окно с выбранным заголовком
// Второй аргумент - кнопка сохранения если указан
function close_by_header_span($span_text, $save_text = false)
{
	global $span, $btn;
	$window_header = $span->get_by_inner_text($span_text, false);
	//$window_header->meta_click();
	//$window_header->send_mouse_double_click(2,2);
	debug_mess("Номер объекта заголовка ".$window_header->get_number());
	sleep(2);
	if($save_text)
	{
		$btns = $btn->get_all_by_inner_text($save_text, false);
		$btns[count($btns)-1]->meta_click();
		$btns[count($btns)-1]->send_mouse_double_click(2,2);
	}
	$close_button = $window_header->get_prev()->get_prev()->get_prev()->get_prev();
	$close_button->meta_click();
	if($span->get_by_inner_text('Сохранить изменения?')->is_exist())
	{
		debug_mess('Предупреждение обработано');
		$btns = $btn->get_all_by_inner_text('Да', true);
		$btns[count($btns)-1]->meta_click();
		$btns[count($btns)-1]->send_mouse_double_click(2,2);
	}
	return $close_button->is_exist();
}