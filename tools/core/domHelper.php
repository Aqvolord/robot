<?php

// Вспомогательные объекты для работы с DOM
class DomHelper
{
    // ждём пока есть на странице нужный нам элемента и он виден $wait*$pause
    function waitExistsElementByTextIsView($tag, $text, $frme = -1, $wait = 120, $pause = 1)
    {
        $a = 0;

        while ($tag->is_exist_by_inner_text($text, true, $frme) && $tag->get_by_inner_text($text, true, $frme)->is_view_now()) {
            sleep($pause);
            if ($a > $wait) {
                debug_mess("ОШИБКА: элемент с тектом $text всё ещё на странице! Ожидали $a секунд.");
                return false;
            }

            $a++;
        }

        return true;
    }

// ждём на подгрузку нужного нам элемента
    function waitOnCountElementByText($tag, $text, $cnt, $frme = -1, $wait = 10, $pause = 1)
    {
        $a = 0;

        $btns = $tag->get_all_by_inner_text($text);
        $cnt1 = count($btns->get_inner_text());

        while ($cnt1 < $cnt) {
            sleep($pause);
            if ($a > $wait) {
                debug_mess("ОШИБКА: не дождались нужного количества элементов c текстом $text!");
                return false;
            }

            $btns = $tag->get_all_by_inner_text("Скачать все");
            $cnt1 = count($btns->get_inner_text());

            $a++;
        }

        return true;
    }

    // ждём на подгрузку нужного нам элемента
    function waitOnElementByText($tag, $text, $frme = -1, $wait = 10, $pause = 1)
    {
        $a = 0;

        while (!$tag->is_exist_by_inner_text($text, false, $frme)) {
            sleep($pause);
            if ($a > $wait) {
                debug_mess("ОШИБКА: не дождались нужного элемента c текстом $text!");
                return false;
            }

            $a++;
        }

        return true;
    }

    // ждём на подгрузку нужного нам элемента по атрибуту
    function waitOnElementByAtt($tag, $att_name, $att_text, $frme = -1, $wait = 10, $pause = 1)
    {
        $a = 0;

        while (!$tag->is_exist_by_attribute($att_name, $att_text, false, $frme)) {
            sleep($pause);
            if ($a > $wait) {
                debug_mess("ОШИБКА: не дождались нужного элемента c текстом $att_name, $att_text ");
                return false;
            }

            $a++;
        }

        return true;
    }

    // ждём на подгрузку нужного нам элемента
    function waitExistsElementByText($tag, $text, $frme = -1, $wait = 30, $pause = 1)
    {
        $a = 0;

        while ($tag->is_exist_by_inner_text($text, true, $frme)) {
            sleep($pause);
            if ($a > $wait) {
                debug_mess("ОШИБКА: элемент с тектом $text всё ещё на странице! Ожидали $a секунд.");
                return false;
            }

            $a++;
        }

        return true;
    }

    // получить номер фрейма по тексту в нём
    function getFrameNumberByText($tag, $text)
    {
        for ($i = 0; $i < 15; $i++)
            if ($tag->is_exist_by_inner_text($text, false, $i))
                return $i;

        return -1;
    }

    // получить первый видимый элемент по атрибуту
    function getIsViewNowByAtt($tag, $att_name, $att_text, $frme = -1)
    {
        $spns = $tag->get_all_by_attribute($att_name, $att_text, true, $frme);
        //print_r($spns->get_inner_text());
        foreach ($spns as $item) {
            if ($item->is_view_now())
                return $item;
        }

        return false;
    }
}
