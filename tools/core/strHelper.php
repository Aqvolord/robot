<?php

class StrHelper
{
    // получить строку по префиксам
    function getStringRev($str1, $pr1, $pr2, &$ind_st = 0)
    {
        //получаем стартовый индекс
        $ind1 = strpos($str1, $pr1, $ind_st);
        if ($ind1 === false) {
            return "";
        }
        $ind1_1 = $ind1 + strlen($pr1);

        $sres = substr($str1, 0, $ind1_1);
        //получаем финишный индекс
        $ind2 = strrpos($sres, $pr2);
        if ($ind2 === false) {
            return "";
        }
        // $ind1 + strlen($pr1), $ind2 - $ind1_1
        // получим результат
        $sres = substr($sres, $ind2 + strlen($pr2), strlen($sres) - $ind2);
        return trim($sres);
    }

    // получить текст по префиксам
    function getText($items, $pref1, $pref2 = " ")
    {
        foreach ($items as $item) {
            $innerText = trim($item);
            if ($innerText == "") {
                continue;
            }
            if (mb_strpos($innerText, $pref1) !== false) {
                $fileName = trim($this->getString($innerText, $pref1, $pref2));
                if ($fileName != "") {
                    return $fileName;
                }
            }
        }
        return false;
    }

    // получить строку по префиксам
    function getString($str1, $pr1, $pr2, &$ind_st = 0)
    {
        //получаем стартовый индекс
        $ind1 = strpos($str1, $pr1, $ind_st);
        if ($ind1 === false) {
            return "";
        }
        $ind1_1 = $ind1 + strlen($pr1);
        //получаем финишный индекс
        $ind2 = strpos($str1, $pr2, $ind1_1);
        if ($ind2 === false) {
            return "";
        }
        // получим результат
        $sres = substr($str1, $ind1 + strlen($pr1), $ind2 - $ind1_1);
        return trim($sres);
    }

    // удаляем все символы слева до первой точки,
    // первую точку тоже удаляем
    function clearBefore1stDot(string $str)
    {
        if (mb_strpos($str, '.') === false) {
            return trim($str);
        }

        $els = explode('.', trim($str));

        unset($els[0]);
        $els = array_values($els);

        $result = implode('.', $els);
        $result = trim($result);

        return $result;
    }

    // удаляем все символы слева до 2й точки,
    // 2ю точку тоже удаляем
    function clearBefore2stDot(string $str)
    {
        if (mb_strpos($str, '.') === false) {
            return trim($str);
        }

        $els = explode('.', trim($str));

        $result = false;
        // одна точка
        if (count($els) === 2) {
            unset($els[0]);
            $els = array_values($els);

            $result = implode('.', $els);
            $result = trim($result);
        } // 2+ точки
        elseif (count($els) > 2) {
            unset($els[0]);
            unset($els[1]);
            $els = array_values($els);

            $result = implode('.', $els);
            $result = trim($result);
        }

        return $result;
    }

    // удаляем все символы слева до первого `пробела`,
    // первый пробел тоже удаляем
    function clearBefore1stSpace(string $str)
    {
        $str = trim($str);

        if (mb_strpos($str, ' ') === false) {
            return $str;
        }

        $els = explode(' ', $str);

        unset($els[0]);
        $els = array_values($els);

        $result = implode(' ', $els);
        $result = trim($result);

        return $result;
    }
}
