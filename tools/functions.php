<?php
// ждём пока есть на странице нужный нам элемента и он виден
function wait_exists_element_by_text_is_view($tag, $text, $frme=-1, $wait=120, $pause=1)
{
  $a=0;

  while($tag->is_exist_by_inner_text($text, true, $frme) && $tag->get_by_inner_text($text, true, $frme)->is_view_now())
  {   
     sleep($pause);
     if($a>$wait)
     {
       debug_mess("ОШИБКА: элемент с тектом $text всё ещё на странице! Ожидали $a секунд.");
       return false;   
     }

     $a++;
  }
   
  return true;   
}

// ждём на подгрузку нужного нам элемента
function wait_on_count_element_by_text($tag, $text, $cnt,  $frme=-1, $wait=10, $pause=1)
{
  $a=0;

	$btns = $tag->get_all_by_inner_text($text);
	$cnt1= count($btns->get_inner_text());

  while($cnt1<$cnt)
  {   
     sleep($pause);
     if($a>$wait)
     {
        debug_mess("ОШИБКА: не дождались нужного количества элементов c текстом $text!");
       return false;   
     }
     
    $btns = $tag->get_all_by_inner_text("Скачать все");
	$cnt1= count($btns->get_inner_text());

     $a++;
  }
   
  return true;   
}



// ждём на подгрузку нужного нам элемента
function wait_on_element_by_text($tag, $text, $frme=-1, $wait=10, $pause=1)
{
  $a=0;

  while(!$tag->is_exist_by_inner_text($text, false, $frme))
  {   
     sleep($pause);
     if($a>$wait)
     {
        debug_mess("ОШИБКА: не дождались нужного элемента c текстом $text!");
       return false;   
     }

     $a++;
  }
   
  return true;   
}
// ждём на подгрузку нужного нам элемента по атрибуту
function wait_on_element_by_att($tag, $att_name, $att_text, $frme=-1, $wait=10, $pause=1)
{
  $a=0;

  while(!$tag->is_exist_by_attribute($att_name, $att_text, false, $frme))
  {   
     sleep($pause);
     if($a>$wait)
     {
        debug_mess("ОШИБКА: не дождались нужного элемента c текстом $att_name, $att_text ");
       return false;   
     }

     $a++;
  }
   
  return true;   
}

// ждём на подгрузку нужного нам элемента
function wait_exists_element_by_text($tag, $text, $frme=-1, $wait=30, $pause=1)
{
  $a=0;

  while(!$tag->is_exist_by_inner_text($text, true, $frme))
  {   
     sleep($pause);
     if($a>$wait)
     {
       debug_mess("ОШИБКА: элемент с тектом $text всё ещё на странице! Ожидали $a секунд.");
       return false;   
     }

     $a++;
  }
   
  return true;   
}

// Удаление повторяющихся слов
function delete_duplicates_words($text)
{
    $text = implode(array_reverse(preg_split('//u', $text)));
    $text = preg_replace('/(\b[\pL0-9]++\b)(?=.*?\1)/siu', '', $text);
    $text = implode(array_reverse(preg_split('//u', $text)));
    return $text;
}

// получить номер фрейма по тексту в нём
function get_frame_number_by_text($tag, $text)
{
    for($i=0;$i<15;$i++)
    if($tag->is_exist_by_inner_text($text, false, $i))
      return $i;

 return -1;
}
// чистим всю информацию по браузеру
function сlear_browser_info()
{
   global $browser;
   $browser->close_all_tabs();
    // navigate to google  
   $browser->navigate("about:blank");
   sleep(1);
   $browser->clear_cookies("");
   sleep(1);
   $browser->clear_cache();
   sleep(1); 

   return true;
}

// получить строку по префиксам
function get_string($str1, $pr1, $pr2, &$ind_st = 0)
{
	//получаем стартовый индекс
	$ind1 = strpos($str1, $pr1, $ind_st);
	if($ind1 === false)
	{
		return "";
	}
	$ind1_1 = $ind1 + strlen($pr1);
	//получаем финишный индекс
	$ind2 = strpos($str1, $pr2, $ind1_1);
	if ($ind2 === false)
	{
		return "";
	}
	// получим результат
	$sres = substr($str1, $ind1 + strlen($pr1), $ind2 - $ind1_1);
	return trim($sres); 
}

// получить строку по 1 префиксу до конца строки
function get_string_eol($str1, $pr1, &$ind_st = 0)
{
	//получаем стартовый индекс
	$ind1 = stripos($str1, $pr1, $ind_st);
	if($ind1 === false)
	{
		return "";
	}
	// получим результат
	$sres = substr($str1, $ind1 + strlen($pr1), null);
	return trim($sres); 
}

// проверим встречается ли подстрока в строке
function is_exists_str($str,$substr)
{
	$result = strpos($str, $substr);

	if ($result === FALSE) 
		return false;
	else
		return true;   
}
// /////////////////////////////////////// Работа с датами //////////////////////////////////
// получить дату в нужном формате
function get_date_from_rus($date_rus)
{
    //  24 декабря 2020 года
    $arr_date=explode(" ",$date_rus);
    // массив с месяцами
    $_monthsList = array("январь"=>".01.","февраль"=>".02.", 
    "март"=>".03." , "апрель"=>".04." ,  "май"=>".05." , "июнь"=> ".06.", 
    "июль"=>".07." , "август"=>".08." , "сентябрь"=>".09." ,
    "октябрь"=>".10." , "ноябрь"=>".11." , "декабрь"=>".12." );
 
    $date_rus = "01".$_monthsList[$arr_date[0]].$arr_date[1];
 
    return $date_rus;
}
// получить дату на русском 
function get_date_to_rus($date_rus)
{
    $arr_date=explode(".",$date_rus);

    $_monthsList = array("01"=>"Январь","02"=>"Февраль", 
    "03"=>"Март" , "04"=>"Апрель" ,  "05"=>"Май" ,"06"=>"Июнь", 
    "07"=>"Июль" , "08"=>"Август" , "09"=>"Сентябрь" ,
    "10"=>"Октябрь" , "11"=>"Ноябрь" , "12"=>"Декабрь" );
 
    $date_rus = $_monthsList[$arr_date[1]]." ".$arr_date[2];
 
    return $date_rus;
}
///////////////////////////// ожидание окон ///////////////////////

// ждём пока не исчез заданной окно с текстом
function wait_exists_window_by_text($text, $main=true, $visibled =true, $wait=300, $pause=1)
{
  global $window;
  $a=0;

  while($window->get_by_text($text,true,$main,$visibled)->is_visible())
  {   
     sleep($pause);
     if($a>$wait)
     {
       debug_mess("ОШИБКА: элемент с тектом $text всё ещё на странице! Ожидали $a секунд.");
       return false;   
     }

     $a++;
  }
   
  return true;   
}

// ждём на подгрузку нужного нам окна с текстом
function wait_on_window_by_text($text, $exactly=true, $main=true, $visibled =true, $wait=300, $pause=1)
{
  global $window;
  $a=0;

  while(!$window->get_by_text($text, $exactly, $main, $visibled)->is_exist())
  {   
     sleep($pause);
     if($a>$wait)
     {
       debug_mess("ОШИБКА: не дождались нужного окна c текстом $text!");
       return false;   
     }

     $a++;
  }
   
  return true;   
}

// ждём пока не изменится количество окон с заданным классом
// $start_count - стартовое количество окон
function wait_on_window_by_class($class_name, $start_count,$main=true, $visibled =true, $wait=300, $pause=1)
{
  global $window;
  $a=0;

  $wnds = $window->get_all_by_class($class_name,true,$main,$visibled);
  while($wnds->get_count()==$start_count)
  {   
     sleep($pause);
     if($a>$wait)
     {
       debug_mess("ОШИБКА: элемент с тектом $class_name всё ещё на странице! Ожидали $a секунд.");
       return false;   
     }
    $wnds = $window->get_all_by_class($class_name,true,$main,$visibled);
    $a++;
  }
   
  return true;   
}
// //////////////////////////// загрузка файла
// получить путь к скаченному файлу
function get_downloaded_path()
{
    global $browser,$wt; 

    sleep($wt);
    //  получить путь к скаченному файлу$browser->navigate("https://rbot-rpa.com/");


    $idd=$browser->get_last_download_id();
    // ожидаем на загрузку
    while (!$browser->is_download_complete($idd))
    {
       debug_mess("ожидаем пока скачается файл");
       sleep($wt);
    }
    // получить id
    $path  = $browser->get_download_info($idd,"save_to");
    
    debug_mess("путь к файлу $path");
    if(trim($path)=="")
    {
       debug_mess("ОШИБКА:get_download_path:Нет пути к файлу id:$idd");
       return "";
    }

    return $path; 
}
// //////////////////////////////////////// загрузка 
// ожидаем на старт загрузки
function wait_new_download_id($id_old, &$idd, $wt_=1, $wait=60)
{
	global $browser;
	
	$a=0;
	while($idd ==$id_old)
	{
		$idd = $browser->get_last_download_id();
		sleep($wt_);
	
		if($a>$wait)
		{
			debug_mess("ОШИБКА: не дождались смены id загрузки $id_old!");
			return false;   
		}
		
		$a++;
    } 
	return true;		
}
// //////////////////////////// работа с элементами

// получить первый видимый элемент по атрибуту
function get_is_view_now_by_att($tag, $att_name, $att_text, $frme=-1)
{
	$spns = $tag->get_all_by_attribute($att_name,$att_text,true,$frme);
	//print_r($spns->get_inner_text());
	foreach($spns as $item)
	{

		if($item->is_view_now())
			return $item;
	}

	return false;
}

// /////////////////////////////////// работа с окнами
// чистим старое и вставляем  
function wnd_clear_and_paste($wnd, $text)
{
    global $wt;

 	$wnd->key("^(a)", true);
	$wnd->key("{DELETE}", true);
	sleep($wt);
	$wnd->paste($text);
	sleep($wt);
    if($wnd->get_text()!=$text)
        return false;

    return true;
}

// ////////////////////// управление роботом
// остновить робота с выводом ошибки если она есть 
// и сообщение о пренудильной остановке
function stop_robot()
{
    global $error_text,$app;

    if($error_text!="")
       	debug_mess("ОШИБКА::$error_text");

	debug_mess("Останавливаем работу робота ....");
	$app->quit();
}

// / /////////////////// клики по скриншотам ///////////////////////////////

// кликнуть по шаблонной картинке на рабочем столе
function click_on_screen($path_image, $x=-1,$y=-1, $width=-1, $height=-1)
{
    global $windows, $wt, $path_images, $desktop_img,$image,$mouse; 
	// делаем скнишот всего рабочего стола
	$windows->screenshot($path_images.$desktop_img,$x,$y,$width,$height,true);
	sleep($wt);

	$pos = $image->get_pos_of_image($path_images.$desktop_img,$path_image);
	if($pos->x==-1 or $pos->y==-1)
		return false;

	//print_r($pos );
	//$mouse->move_on_screen($pos->x+10, $pos->y+10);

    //  выполняем клик по найденным координатам
	$mouse->click_to_screen($pos->x+10, $pos->y+10);    
    sleep($wt);
    return true;
}


// кликаем в окне получив координаты по шаблонному изображению в двух вариантах
function click_on_img_in_wnd($wnd, $img, $img_blue="", $b_double=false, $non_client=false, $through_screen=false)
{
    global $image, $path_screen,$wt,$windows; 
     
    // делаем скриншот окна
    if(!$through_screen)
        $wnd->screenshot($path_screen,-1,-1,-1,-1,true,$non_client);
    else
        $windows->screenshot($path_screen ,$wnd->get_x($non_client),$wnd->get_y($non_client),$wnd->get_width($non_client),$wnd->get_height($non_client),true);

    // ищем координаты
    $pos = $image->get_pos_of_image($path_screen,$img);
	// проверка на выбранную строку если нет координат
    if(($img_blue!="") and ($pos->x==-1 or $pos->y==-1))
	    $pos = $image->get_pos_of_image($path_screen,$img_blue);

    // нашли координаты кликаем по ним
	if($pos->x!=-1 or $pos->y!=-1)
	{
            $wnd->foreground();
            $wnd->focus();
			//print_r($pos);
			$wnd->mouse_click($pos->x+10, $pos->y+10);
			sleep($wt);

            if($b_double)
			    $wnd->mouse_double_click($pos->x+10, $pos->y+10);

	        return true;
	}
   
    return false;
}
// поиск по картинке с прокруткой
 function search_and_click_in_wnd($edt, $img,$blue_img="", $b_double=false, $x1=10,$y1=10)
{
	   global $wt, $path_images, $wnd_img, $image, $cryptography;
	  // для проверки изменения скриншота
	  $hash_old =-1; 
	   while(true)
	   {
		// делаем скриншот окна
		$edt->screenshot($path_images.$wnd_img,-1,-1,-1,-1,true);
		// проверяем изменился ли скнишот
		if($hash_old == $cryptography->hash_file($path_images.$wnd_img))
		{
			// вернуть в начало
			$edt ->key("^{HOME}", true);
			return false;
		}
		 else 
			$hash_old = $cryptography->hash_file($path_images.$wnd_img);
        // находим позицию
		$pos = $image->get_pos_of_image($path_images.$wnd_img,$path_images.$img);
		if(($blue_img!="") and ($pos->x==-1 or $pos->y==-1))
				$pos = $image->get_pos_of_image($path_images.$wnd_img,$path_images.$blue_img);

		if($pos->x!=-1 or $pos->y!=-1)
		{
				//print_r($pos);
				$edt->mouse_click($pos->x+$x1, $pos->y+$y1);
				sleep($wt);

				if($b_double)
					$edt->mouse_double_click($pos->x+$x1, $pos->y+$y1);
				
				return true;
		}

		$edt ->key(VK_PAGEDOWN, false);
		sleep($wt);
	}

	 return false;
}

// найти окно с заданным текстом
function find_wnd_by_text($title, $text)
{
    global $wt, $wt_long,$window, $path_screen, $tesseractOCR;

	$wnds = $window->get_all_by_text($title, true, true, true);
	foreach($wnds as $wd_message)
	{
		$wd_message ->foreground();
		$wd_message ->focus();

		$wd_message->screenshot($path_screen ,-1,-1,-1,-1,true, true);
		sleep($wt);
		//$image->get_pos_of_image(
		$reg_pos = $tesseractOCR->get_region_by_text($path_screen,$text);

		if($reg_pos!==null)
			return$wd_message;
	}

	return false;
}
////////////////////////////// работа с excel

// получить стартовый ряд в файле
function get_start_index($excel_path)
{
    global $wt, $excelfile,$sheet;

    $excelfile->open($excel_path);
	// работаем с excel 
	$row_count = $excelfile->get_rows_count($excel_path,$sheet);

	if($row_count ==0)
    {
        debug_mess("Файл пустой");
        $excelfile->close($excel_path);
        return false;
    }

		// работаем с excel 
		$row_count = $excelfile->get_rows_count($excel_path,$sheet);

		debug_mess("Количество рядов в $excel_path файле $row_count");

		// пройтись по всем лс
		for($rIndex = 1; $rIndex <= $row_count; $rIndex++) //foreach ($rows as $row)
		{
            // делаем проверку
			$personal_account = trim($excelfile->get_cell($excel_path,$sheet,$rIndex,"A"));
            if($personal_account =="")
                continue;

            if(ctype_digit($personal_account))
            {
               debug_mess("Стартовый индекс в файле $rIndex");
               $excelfile->close($excel_path);
               return $rIndex ;
            }
        }

    $excelfile->close($excel_path);
    debug_mess("ОШИБКА: Не смогли определить стартовый индекс в файле $excel_path");

    return false;
}
// копировать ряд из одного файла в другой
function excelfile_copy_row($path1,$path2,$row,$sheet1=0,$sheet2=0)
{
    global $excelfile;
    $row_arr=$excelfile->get_row($path1,$sheet1,$row);
    $row = $excelfile->get_rows_count($path2,$sheet2)+1;
    $excelfile->set_row($path2,$sheet2,$row,$row_arr);
    $excelfile->autosize_col($path2,$sheet2);

    return true;
}
// ////////////////////////////////////////////// работа с почтой
// отправить письма в процессе выполнения робота
function send_process_email($email,$to, $subject, $text, $bsend_email, $copy="",$arr=null)
{
    global $mail;
	// отправляем почту в конце работы
	if($bsend_email)
	{
		debug_mess("отправялем письмо...");
		$mail->send_mail_via_outlook($email,$to,$subject, $text,2,$copy,"",$arr); 
	}
}

// ожидаем полявление файла
function wait_on_file($path, $wait=120, $pause=1)
{
    global $file_os;
	$a=0;

	while(!$file_os->is_exist($path))
	{
		sleep($pause);
		if($a>$wait)
		{
			debug_mess("ОШИБКА: не дождались появление файла по заданному пути $path!");
			return false;   
		}

		$a++;

	}

	return true;   
}
?>