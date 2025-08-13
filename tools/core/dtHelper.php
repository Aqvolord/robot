<?php
class DtHelper
{
	/* проверяем на формат даты "расширенный" или "обычный" */
    public function getTimeFromDateString ($dt): int {
        if (empty($dt)) return time();

        if (strpos($dt, 'T') !== false)
            return strtotime($dt);
        
		if (strpos($dt, ' ') === false) {
			list($dt_day, $dt_month, $dt_year) = explode('.', $dt);
			return strtotime("$dt_year-$dt_month-$dt_day 00:00:00");
		}

        list($tmpdate, $tmptime) = explode(' ', $dt);
        list($dt_day, $dt_month, $dt_year) = explode('.', $tmpdate);

		if (empty($tmptime))
			return strtotime("$dt_year-$dt_month-$dt_day 00:00:00");

        $doubles_count = substr_count($tmptime, ':');
        $time_hour = date('H');
        $time_minute = date('i');
        $time_second = date('s');

        switch ($doubles_count) {
            case 1:
                list($time_hour, $time_minute) = explode(':', $tmptime);
                $time_second = 0;
                break;
            
            case 2:
                list($time_hour, $time_minute, $time_second) = explode(':', $tmptime);
                break;
            
            default: break;
        }
        

        return strtotime("$dt_year-$dt_month-$dt_day $time_hour:$time_minute:$time_second");
    }

    /*
	функция возвращает последнее время сообщения
	*/
	function getLastDt (string $filename): string {
		return (!SYSTEM::$file_os->is_exist($filename))?
			date('Y-m-d') . 'T00:00:00.000+0300':
			SYSTEM::$textfile->read_file($filename);
	}

    /**
     * функция уберает время из даты формата д.м.г чч:мм:сс
     */
    public function removeTimeFromDate ($dtstr) {
        $return_str = '';
        $dtstr = trim(str_replace(['  ', '   '], ' ', $dtstr));

        if (empty($dtstr)) {
            $return_str = '';
        } elseif (strpos($dtstr, ' ') === false) {
			list($dt_day, $dt_month, $dt_year) = explode('.', $dtstr);
			$return_str = "$dt_day.$dt_month.$dt_year";
		} else {
            list($mydt, $mytime) = explode(' ', $dtstr);
            list($dt_day, $dt_month, $dt_year) = explode('.', $mydt);

            $return_str = "$dt_day.$dt_month.$dt_year";
        }

        debug_mess("DT:removeTimeFromDate: $dtstr = $return_str");
        return $return_str;
    }
}
?>