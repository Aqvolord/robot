<?php

class WndHelper
{
    // ждём пока не исчез заданной окно с текстом
    function waitExistsWindowByText($text, $main = true, $visibled = true, $wait = 300, $pause = 1000)
    {
        global $window;
        $a = 0;

        while ($window->get_by_text($text, true, $main, $visibled)->is_visible()) {
            usleep($pause);
            if ($a > $wait) {
                debug_mess("ОШИБКА: элемент с тектом $text всё ещё на странице! Ожидали $a секунд.");
                return false;
            }

            $a++;
        }

        return true;
    }

    // ждём на подгрузку нужного нам окна с текстом
    function waitOnWindowByText($text, $exactly = true, $main = true, $visibled = true, $wait = 300, $pause = 1000)
    {
        global $window;
        $a = 0;

        while (!$window->get_by_text($text, $exactly, $main, $visibled)->is_exist()) {
            usleep($pause);
            if ($a > $wait) {
                debug_mess("ОШИБКА: не дождались нужного окна c текстом $text!");
                return false;
            }

            $a++;
        }

        return true;
    }

    // ждём пока не изменится количество окон с заданным классом
    // $startCount - стартовое количество окон
    function waitOnWindowByClass($className, $startCount, $main = true, $visibled = true, $wait = 300, $pause = 1000)
    {
        global $window;
        $a = 0;

        $wnds = $window->get_all_by_class($className, true, $main, $visibled);
        while ($wnds->get_count() == $startCount) {
            usleep($pause);
            if ($a > $wait) {
                debug_mess("ОШИБКА: элемент с тектом $className всё ещё на странице! Ожидали $a секунд.");
                return false;
            }
            $wnds = $window->get_all_by_class($className, true, $main, $visibled);
            $a++;
        }

        return true;
    }

    // чистим старое и вставляем
    function wndClearAndPaste($wnd, $text)
    {
        global $wt, $timeout;

        $wnd->key("^(a)", true);
        $wnd->key("{DELETE}", true);
        usleep($timeout);
        $wnd->paste($text);
        usleep($timeout);
        if ($wnd->get_text() != $text)
            return false;

        return true;
    }

    // кликнуть по шаблонной картинке на рабочем столе
    function clickOnScreen($pathImage, $x = -1, $y = -1, $width = -1, $height = -1)
    {
        global $windows, $wt, $timeout, $pathImages, $desktopImg, $image, $mouse;
        // делаем скнишот всего рабочего стола
        $windows->screenshot($pathImages . $desktopImg, $x, $y, $width, $height, true);
        //sleep($wt);
        usleep($timeout);

        $pos = $image->get_pos_of_image($pathImages . $desktopImg, $pathImage);
        if ($pos->x == -1 or $pos->y == -1)
            return false;

        //print_r($pos );
        //$mouse->move_on_screen($pos->x+10, $pos->y+10);

        //  выполняем клик по найденным координатам
        $mouse->click_to_screen($pos->x + 10, $pos->y + 10);
        //sleep($wt);
        usleep($timeout);
        return true;
    }

    // сделать скриншот окна по стандартному пути
    function makeScreenshotWnd($wnd, $throughScreen = false, $nonClient = false)
    {
        global $pathScreen, $windows;

        // делаем скриншот окна
        if (!$throughScreen)
            $wnd->screenshot($pathScreen, -1, -1, -1, -1, true, $nonClient);
        else
            $windows->screenshot($pathScreen, $wnd->get_x($nonClient), $wnd->get_y($nonClient), $wnd->get_width($nonClient), $wnd->get_height($nonClient), true);

        return true;
    }

    // сделать скриншот окна по стандартному пути
    function makeScreenshotWndToFile($wnd, $path = "", $throughScreen = false, $nonClient = false)
    {
        global $pathScreen, $windows;

        if ($path == "")
            $path = $pathScreen;

        // делаем скриншот окна
        if (!$throughScreen)
            $wnd->screenshot($path, -1, -1, -1, -1, true, $nonClient);
        else
            $windows->screenshot($path, $wnd->get_x($nonClient), $wnd->get_y($nonClient), $wnd->get_width($nonClient), $wnd->get_height($nonClient), true);

        return true;
    }

    // кликаем в окне получив координаты по шаблонному изображению в двух вариантах
    function clickOnImgInWnd($wnd, $img, $imgBlue = "", $bDouble = false, $nonClient = false, $throughScreen = false, $precision = 0.8)
    {
        global $image, $timeout, $pathScreen, $wt, $windows, $mouse;

        // делаем скриншот окна
        if (!$throughScreen)
            $wnd->screenshot($pathScreen, -1, -1, -1, -1, true, $nonClient);
        else
            $windows->screenshot($pathScreen, $wnd->get_x($nonClient), $wnd->get_y($nonClient), $wnd->get_width($nonClient), $wnd->get_height($nonClient), true);

        // ищем координаты
        $pos = $image->get_pos_of_image($pathScreen, $img, $precision);
        // проверка на выбранную строку если нет координат
        if (($imgBlue != "") and ($pos->x == -1 or $pos->y == -1))
            $pos = $image->get_pos_of_image($pathScreen, $imgBlue);

        // нашли координаты кликаем по ним
        if ($pos->x != -1 or $pos->y != -1) {
            $wnd->foreground();
            $wnd->focus();

            if ($throughScreen) {
                if ($bDouble) {
                    $mouse->click_to_screen($wnd->get_x($nonClient) + $pos->x + 5, $wnd->get_y($nonClient) + $pos->y + 5);
                    $mouse->click_to_screen($wnd->get_x($nonClient) + $pos->x + 5, $wnd->get_y($nonClient) + $pos->y + 5);
                } else {
                    $mouse->move_on_screen($wnd->get_x($nonClient) + $pos->x + 5, $wnd->get_y($nonClient) + $pos->y + 5);
                    $mouse->click_to_screen($wnd->get_x($nonClient) + $pos->x + 5, $wnd->get_y($nonClient) + $pos->y + 5);
                }
                //sleep($wt);
                usleep($timeout);
            } else {
                $wnd->mouse_move($pos->x + 5, $pos->y + 5);
                if ($bDouble)
                    $wnd->mouse_double_click($pos->x + 5, $pos->y + 5);
                else
                    $wnd->mouse_click($pos->x + 5, $pos->y + 5);
                //sleep($wt);
                usleep($timeout);
            }
            return true;
        }

        return false;
    }

    // кликаем в окне получив координаты с учетом координат по x и y
    function clickOnImgInWndXY($wnd, $img, $x1 = 5, $y1 = 5, $bDouble = false, $throughScreen = false, $nonClient = false)
    {
        global $image, $pathScreen, $wt, $timeout, $windows, $mouse;

        // делаем скриншот окна
        if (!$throughScreen)
            $wnd->screenshot($pathScreen, -1, -1, -1, -1, true, $nonClient);
        else
            $windows->screenshot($pathScreen, $wnd->get_x($nonClient), $wnd->get_y($nonClient), $wnd->get_width($nonClient), $wnd->get_height($nonClient), true);

        // ищем координаты
        $pos = $image->get_pos_of_image($pathScreen, $img);

        // нашли координаты кликаем по ним
        if ($pos->x != -1 or $pos->y != -1) {
            //$wnd->foreground();
            //$wnd->focus();

            if ($throughScreen) {
                if ($bDouble) {
                    $mouse->click_to_screen($wnd->get_x($nonClient) + $pos->x + $x1, $wnd->get_y($nonClient) + $pos->y + $y1);
                    $mouse->click_to_screen($wnd->get_x($nonClient) + $pos->x + $x1, $wnd->get_y($nonClient) + $pos->y + $y1);
                } else {
                    $mouse->move_on_screen($wnd->get_x($nonClient) + $pos->x + $x1, $wnd->get_y($nonClient) + $pos->y + $y1);
                    $mouse->click_to_screen($wnd->get_x($nonClient) + $pos->x + $x1, $wnd->get_y($nonClient) + $pos->y + $y1);
                }
                //sleep($wt);
                usleep($timeout);
            } else {
                $wnd->mouse_move($pos->x + $x1, $pos->y + $y1);
                if ($bDouble)
                    $wnd->mouse_double_click($pos->x + $x1, $pos->y + $y1);
                else
                    $wnd->mouse_click($pos->x + $x1, $pos->y + $y1);

                // sleep($wt);
                usleep($timeout);
            }
            return true;
        }

        return false;
    }

    // кликаем в окне получив координаты с учетом координат по x и y
    function rightClickOnImgInWndXY($wnd, $img, $x1 = 5, $y1 = 5, $bDouble = false, $throughScreen = false, $nonClient = false)
    {
        global $image, $pathScreen, $wt, $timeout, $windows, $mouse;

        // делаем скриншот окна
        if (!$throughScreen)
            $wnd->screenshot($pathScreen, -1, -1, -1, -1, true, $nonClient);
        else
            $windows->screenshot($pathScreen, $wnd->get_x($nonClient), $wnd->get_y($nonClient), $wnd->get_width($nonClient), $wnd->get_height($nonClient), true);

        // ищем координаты
        $pos = $image->get_pos_of_image($pathScreen, $img);

        // нашли координаты кликаем по ним
        if ($pos->x != -1 or $pos->y != -1) {
            //$wnd->foreground();
            //$wnd->focus();

            if ($throughScreen) {
                if ($bDouble) {
                    $mouse->right_button_click_to_screen($wnd->get_x($nonClient) + $pos->x + $x1, $wnd->get_y($nonClient) + $pos->y + $y1);
                    $mouse->right_button_click_to_screen($wnd->get_x($nonClient) + $pos->x + $x1, $wnd->get_y($nonClient) + $pos->y + $y1);
                    //	$mouse->click_to_screen($wnd->get_x($nonClient)+$pos->x+$x1, $wnd->get_y($nonClient)+$pos->y+$y1);
                    //  $mouse->click_to_screen($wnd->get_x($nonClient)+$pos->x+$x1, $wnd->get_y($nonClient)+$pos->y+$y1);
                } else {
                    $mouse->move_on_screen($wnd->get_x($nonClient) + $pos->x + $x1, $wnd->get_y($nonClient) + $pos->y + $y1);
                    $mouse->right_button_click_to_screen($wnd->get_x($nonClient) + $pos->x + $x1, $wnd->get_y($nonClient) + $pos->y + $y1);
                }
                //sleep($wt);
                usleep($timeout);

            } else {
                //print_r($pos);
                $wnd->mouse_move($pos->x + $x1, $pos->y + $y1);
                if ($bDouble) {
                    $wnd->mouse_right_click($pos->x + $x1, $pos->y + $y1);
                    $wnd->mouse_right_click($pos->x + $x1, $pos->y + $y1);
                } else
                    $wnd->mouse_right_click($pos->x + $x1, $pos->y + $y1);

                //sleep($wt);
                usleep($timeout);
            }
            return true;
        }

        return false;
    }

    // поиск по картинке с прокруткой
    function searchAndClickInWnd($edt, $img, $blueImg = "", $bDouble = false, $x1 = 10, $y1 = 10, $throughScreen = false, $nonClient = false)
    {
        global $wt, $timeout, $pathImages, $wndImg, $image, $windows, $cryptography;
        // для проверки изменения скриншота
        $hash_old = -1;
        // ctrl+home
        SYSTEM::$keyboard->press_key_by_code("36", true);

        while (true) {
            //echo $edt->get_x($nonClient)."|".$edt->get_y($nonClient)."|".$edt->get_width($nonClient)."|".$edt->get_height($nonClient)."<br>";
            // делаем скриншот окна
            if (!$throughScreen)
                $edt->screenshot($pathImages . $wndImg, -1, -1, -1, -1, true, $nonClient);
            else
                $windows->screenshot($pathImages . $wndImg, $edt->get_x($nonClient), $edt->get_y($nonClient), $edt->get_width($nonClient), $edt->get_height($nonClient), true);

            // делаем скриншот окна
            //$edt->screenshot($pathImages.$wndImg,-1,-1,-1,-1,true);

            // проверяем изменился ли скнишот
            if ($hash_old == $cryptography->hash_file($pathImages . $wndImg)) {
                // вернуть в начало
                //$edt ->key("^{HOME}", true);
                SYSTEM::$keyboard->press_key_by_code("36", true);
                return false;
            } else
                $hash_old = $cryptography->hash_file($pathImages . $wndImg);
            // находим позицию
            $pos = $image->get_pos_of_image($pathImages . $wndImg, $img);
            if (($blueImg != "") and ($pos->x == -1 or $pos->y == -1))
                $pos = $image->get_pos_of_image($pathImages . $wndImg, $blueImg);

            if ($pos->x != -1 or $pos->y != -1) {
                //print_r($pos);
                $edt->mouse_click($pos->x + $x1, $pos->y + $y1);
                //sleep($wt);
                usleep($timeout);;

                if ($bDouble)
                    $edt->mouse_double_click($pos->x + $x1, $pos->y + $y1);

                return true;
            }

            //$edt ->key(VK_PAGEDOWN, false);
            SYSTEM::$keyboard->press_key_by_code("34");
            //sleep($wt);
            usleep($timeout);
        }

        return false;
    }

    // найти окно с заданным текстом
    function findWndByText($title, $text)
    {
        global $wt, $timeout, $window, $pathScreen, $tesseractOCR;

        $wnds = $window->get_all_by_text($title, false, true, true);
        foreach ($wnds as $wd_message) {
            $wd_message->foreground();
            $wd_message->focus();

            $wd_message->screenshot($pathScreen, -1, -1, -1, -1, true, true);
            //sleep($wt);
            usleep($timeout);
            //$image->get_pos_of_image(
            $reg_pos = $tesseractOCR->get_region_by_text($pathScreen, $text);

            if ($reg_pos !== null)
                return $wd_message;
        }

        return false;
    }

    /*
    Закрываем информационное окно если оно есть сразу после старта
    */
    function closeWndByText($wndTitle, $exactly = true, $mained = false, $visible = true)
    {
        if (!isset($wndTitle) or $wndTitle == "")
            return false;

        TOOLS::$log->info("Закрываем окно с текстом $wndTitle");

        $info_wnd = WINDOW::$window->get_by_text($wndTitle, $exactly, $mained, $visible);

        if ($info_wnd->is_exist())
            $info_wnd->close();
        else
            TOOLS::$log->debug("Нет окна с тестом $wndTitle");

        return true;
    }

    // ожидание появления нужного окна
    function waitOnImageInWnd($wnd, $img, $throughScreen = false, $nonClient = false, $wait = 300, $pause = 1000)
    {
        global $image, $pathScreen, $wt, $wt_long, $windows, $mouse;

        $a = 0;

        // делаем скриншот окна
        $this->makeScreenshotWnd($wnd, $throughScreen, $nonClient);
        // находим позицию
        $pos = DOM::$image->get_pos_of_image($pathScreen, $img);
        while ($pos->x == -1 or $pos->y == -1) {
            //sleep($pause);
            //sleep($wt);
            usleep($pause);

            if ($a > $wait) {
                TOOLS::$log->error("Не дождались рисунка  $img в заданном окне! Ожидали $a секунд.");
                return false;
            }

            // делаем скриншот окна
            $this->makeScreenshotWnd($wnd, $throughScreen, $nonClient);

            // находим позицию
            $pos = DOM::$image->get_pos_of_image($pathScreen, $img);

            $a = ($a + $pause / 1000000);
        }

        return true;
    }

    /*
    кликнуть мышью в заданной позиции
    */
    function clickInPos($wnd, $pos, $x1 = 5, $y1 = 5, $throughScreen = false, $nonClient = false, $bDouble = false)
    {
        global $wt, $timeout, $mouse;

        if ($throughScreen) {
            $mouse->move_on_screen($wnd->get_x($nonClient) + $pos->x + $x1, $wnd->get_y($nonClient) + $pos->y + $y1);
            $mouse->click_to_screen($wnd->get_x($nonClient) + $pos->x + $x1, $wnd->get_y($nonClient) + $pos->y + $y1);
            //sleep($wt);
            usleep($timeout);

            if ($bDouble) {
                $mouse->click_to_screen($wnd->get_x($nonClient) + $pos->x + $x1, $wnd->get_y($nonClient) + $pos->y + $y1);
                $mouse->click_to_screen($wnd->get_x($nonClient) + $pos->x + $x1, $wnd->get_y($nonClient) + $pos->y + $y1);
            }
        } else {
            $wnd->mouse_move($pos->x + $x1, $pos->y + 5);
            $wnd->mouse_click($pos->x + $x1, $pos->y + 5);
            //sleep($wt);
            usleep($timeout);

            if ($bDouble)
                $wnd->mouse_double_click($pos->x + $x1, $pos->y + 5);
        }

        return true;
    }

    /*
    кликнуть по кнопку maximize, close и т.д. в заголовке окна по скриншоту заголовка
    */
    // Взят из робота series_adjustment
    // TODO: добавить параметры $throughScreen=false, $nonClient=false
    function clickInTitleWndByTitleNameScreenshot($wnd, $imgTitle, $img, $maxDiff = 20)
    {
        global $pathScreen;

        // TODO: добавить использование параметров $throughScreen=false, $nonClient=false
        // сделать активным
        $this->clickOnImgInWndXY($wnd, $imgTitle, 110, 5, false, true);

        // сделать скришот
        $this->makeScreenshotWnd($wnd, true, false);

        // ищем координаты
        $pos_wnd = DOM::$image->get_pos_of_image($pathScreen, $imgTitle);

        //TOOLS::$log->debug(print_r($pos_wnd ,true));

        $poses = DOM::$image->get_all_pos_of_image($pathScreen, $img);
        //TOOLS::$log->debug(print_r($poses,true));

        foreach ($poses as $pos) {
            $diff = $pos->y - $pos_wnd->y;
            if ($diff > -$maxDiff and $diff < $maxDiff) {
                $this->clickInPos($wnd, $pos, 5, 5, true);
                return true;
            }
        }

        return false;
    }

    /*
        кликнуть по кнопку maximize, close и т.д. в заголовке окна по скриншоту заголовка
    */
    // Взят из робота moving_goods
    // TODO: добавить параметры $throughScreen=false, $nonClient=false
    function clickInTitleWndByTitleNameScreenshotTwo($wnd, $imgTitle, $img, $maxDiff = 20, $maxDiffX = -1)
    {
        global $wt, $wtLong, $wtMaxElements, $pathScreen;

        // TODO: добавить использование параметров $through_screen=false, $non_client=false
        // сделать активным
        $this->clickOnImgInWndXY($wnd, $imgTitle, 110, 5, false, true);
        // сделать скришот
        $this->makeScreenshotWnd($wnd, true, false);

        // ищем координаты
        $pos_wnd = DOM::$image->get_pos_of_image($pathScreen, $imgTitle);

        //TOOLS::$log->debug(print_r($pos_wnd ,true));

        $poses = DOM::$image->get_all_pos_of_image($pathScreen, $img);
        //TOOLS::$log->debug(print_r($poses,true));

        if (count($poses) > 1) {
            // сортируем массив, находим ближайшее
            uasort($poses, function ($a, $b) {
                return sqrt(($a->x) ^ 2 + ($a->y) ^ 2) - sqrt(($b->x) ^ 2 + ($b->y) ^ 2);
            });
        }

        foreach ($poses as $pos) {
            $diffX = intval($pos->x) - intval($pos_wnd->x);
            $diffY = intval($pos->y) - intval($pos_wnd->y);

            if ($diffY > -$maxDiff and $diffY < $maxDiff) {
                if ($maxDiffX > -1) {
                    if ($diffX < -$maxDiffX or $diffX > $maxDiffX) {
                        continue;
                    }
                }
                $this->clickInPos($wnd, $pos, 5, 5, true);
                return true;
            }
        }

        return false;
    }

    /*
        проверить если такое окно с заданным скриншотом
    */
    function isExistImageInWnd($wnd, $img, $throughScreen = false, $nonClient = false)
    {
        global $pathScreen;

        // делаем скриншот окна
        $this->makeScreenshotWnd($wnd, $throughScreen, $nonClient);
        // находим позицию
        $pos = DOM::$image->get_pos_of_image($pathScreen, $img);

        if ($pos->x == -1 or $pos->y == -1)
            return false;

        return true;
    }

    /*
       Получить текст через буфер обмена и клавиатуру
    */
    // Взят из робота series_adjustment
    function copyByKeyboard()
    {
        global $wt;

        SYSTEM::$clipboard->put_text("");
        SYSTEM::$keyboard->press_key_by_code("65", true);
        sleep($wt);
        SYSTEM::$keyboard->press_key_by_code("67", true);
        sleep($wt);
        $text = SYSTEM::$clipboard->get_text();
        sleep($wt);

        return $text;
    }

    /*
       Получить текст через буфер обмена и клавиатуру
    */
    // Взят из робота moving_goods
    function copyByKeyboardTwo()
    {
        global $wt, $timeout;

        TOOLS::$log->debug('Содержимое clipboard до копирования:');
        TOOLS::$log->debug('[ ' . SYSTEM::$clipboard->get_text() . ' ]');

        $try_of_clipboard_clear = 0;
        while (!SYSTEM::$clipboard->put_text("") && $try_of_clipboard_clear < 5) {
            sleep(1);
            $try_of_clipboard_clear++;
        }
        if (trim(SYSTEM::$clipboard->get_text()) !== "") {
            TOOLS::$log->error("Не удается очистить буфер обмена! Требуется перезапуск студии.");
            sleep(60);
        }

        if (!SYSTEM::$keyboard->press_key_by_code("65", true)) { // ctrl+A
            TOOLS::$log->error("Не удается сэмулировать нажатие Ctrl+A! Требуется перезапуск студии.");
            sleep(60);
        }
        usleep(300000);
        if (!SYSTEM::$keyboard->press_key_by_code("67", true)) { // ctrl+C
            TOOLS::$log->error("Не удается сэмулировать нажатие Ctrl+C! Требуется перезапуск студии.");
            sleep(60);
        }
        usleep(300000);
        $text = SYSTEM::$clipboard->get_text();
        usleep(300000);

        TOOLS::$log->debug('Содержимое clipboard после копирования:');
        TOOLS::$log->debug('[ ' . SYSTEM::$clipboard->get_text() . ' ]');
        return $text;
    }

    /*
        Вставить текст через буфер обмена и клавиатуру
    */
    // Взят из робота series_adjustment
    function pasteByKeyboard($text)
    {
        global $wt;

        SYSTEM::$keyboard->press_key_by_code("65", true);
        sleep($wt);
        SYSTEM::$keyboard->press_key_by_code("46");
        sleep($wt);
        SYSTEM::$clipboard->put_text($text);
        sleep($wt);
        SYSTEM::$keyboard->press_key_by_code("86", true);
        sleep($wt);

        return true;
    }

    /*
        Вставить текст через буфер обмена и клавиатуру
    */
    // Взят из робота moving_goods
    function pasteByKeyboardTwo($text, $additionalTime = 0)
    {
        global $wt, $timeout;

        SYSTEM::$keyboard->press_key_by_code("65", true);  //ctrl+A
        //sleep($wt);
        usleep($timeout / 4 + $additionalTime);
        SYSTEM::$keyboard->press_key_by_code("46"); // Del
        //sleep($wt);
        usleep($timeout / 2 + $additionalTime);
        SYSTEM::$clipboard->put_text($text); // put $text to clipboard
        usleep($timeout / 2 + $additionalTime);
        SYSTEM::$keyboard->press_key_by_code("86", true); // Ctrl+V
        //sleep($wt);
        usleep($timeout / 4 + $additionalTime);

        // Проверка
        SYSTEM::$clipboard->put_text(""); // очищаем clipboard
        usleep($timeout / 2 + $additionalTime);
        SYSTEM::$keyboard->press_key_by_code("65", true);  //ctrl+A
        usleep($timeout / 4 + $additionalTime);
        SYSTEM::$keyboard->press_key_by_code("67", true);  //ctrl+C
        usleep($timeout / 2 + $additionalTime);
        $result = SYSTEM::$clipboard->get_text();
        if (trim($result) <> trim($text)) {
            TOOLS::$log->error("Неверно сработала функций вставки данных. В буфере обмена - скопированное из ячейки [" . SYSTEM::$clipboard->get_text() . "], а должно быть [ $text ]. Повторяем. ");
            usleep($timeout);
            $additionalTime = $additionalTime + 1000000; // + 1sec
            if ($additionalTime > 3000000) {
                TOOLS::$log->error("Не удалось вставить значение [ $text ]");
                return false;
            }
            $this->pasteByKeyboard($text, $additionalTime);
        }

        TOOLS::$log->debug("Вставка значения [ $text ] прошла успешно.");
        return true;
    }

    /*
       Получить выбранный текст через буфер обмена и клавиатуру
    */
    function copySelectedByKeyboard()
    {
        global $wt, $timeout;

        SYSTEM::$clipboard->put_text("");
        usleep($timeout);
        SYSTEM::$keyboard->press_key_by_code("67", true);
        usleep($timeout);
        $text = SYSTEM::$clipboard->get_text();
        usleep($timeout);

        return $text;
    }
}