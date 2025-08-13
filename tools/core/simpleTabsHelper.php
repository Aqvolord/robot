<?php
class SimpleTabsHelper
{
	/*
	функция добавляет новую вкладку, как я хочу
	*/
	public function addTabSimple (bool $switchOnChange = true, bool $sleep = true): void {
		global $browser, $wt;

        $tabsCount = $browser->get_count() + 1;
        $browser->set_count($tabsCount);

        if ($sleep)
            sleep($wt);
        
		$browser->set_active_browser(($switchOnChange)? $tabsCount: ($tabsCount - 1));
        
        debug_mess('ВКЛАДКИ: +1');
	}

	/*
	функция ждет загрузки страницы на табе, как я хочу
	*/
	public function waitTabSimple (int $maxSeconds = 60, bool $showTicks = false): bool {
		global $browser;
		$ticks = 0;

		while (true) {
			if ($browser->get_ready_state() == 'complete') {
				debug_mess('ВКЛАДКИ: контент загружен!');
				return true;
			}
			
			if ($ticks > $maxSeconds) break;

			$ticks++;
			sleep(1);

			if ($showTicks)
				debug_mess('ВКЛАДКИ: прошло ' . $ticks . 'с');
		}

		$mes = 'ВКЛАДКИ: не дождались полной загрузки контента :(';
		if ($showTicks)
			$mes .= ' Ждали ' . $ticks . 'с';
		debug_mess($mes);

		return false;
	}

	/*
	функция убирает одну вкладку, как я хочу
	*/
	public function removeTabSimple (bool $sleep = false): void {
		global $browser, $wt;

        $tabsCount = $browser->get_count() - 1;
        $browser->set_count($tabsCount);

        if ($sleep)
            sleep($wt);
        
        debug_mess('ВКЛАДКИ: -1');
	}
}
?>