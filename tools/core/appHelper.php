<?php

class AppHelper
{
	public function quit()
	{
		global $xhe_host, $appQuitType;

		debug_mess("[{$xhe_host}] Робот закончил работу", true);

		if ($appQuitType === 'exitapp') { WINDOW::$app->exitapp(); }
		elseif ($appQuitType === 'restart_and_quit') { WINDOW::$app->restart(); }
		else { WINDOW::$app->quit(); }
	}

    function checkRbotRestart()
    {
        $port = $this->extractCurrentRbotAppPort();

        if (!is_numeric($port)) {
            TOOLS::$log->error("checkRbotRestart: ошибка определения rbot app port");
            return false;
        }
        if ($port <= 0) {
            TOOLS::$log->error("checkRbotRestart: ошибка определения rbot app port (2)");
            return false;
        }

        $file = getcwd() . "\\res\\restart_{$port}.txt";

        if (file_exists($file)) {
            $tmp = "checkRbotRestart: рестарт робота";
            TOOLS::$log->info($tmp);
            @unlink($file);
            throw new Exception($tmp);
        }

        return true;
    }

    function extractCurrentRbotAppPort()
    {
        global $xhe_host;
        $tmp = explode(':', $xhe_host); // "127.0.0.1:7010" => ["127.0.0.1", "7010"]
        $tmp = $tmp[1]; // "7010"
        $result = intval($tmp);
        return $result;
    }

    function checkPause()
    {
        global $wt;

        $port = $this->extractCurrentRbotAppPort();

        if (!is_numeric($port)) {
            TOOLS::$log->error("checkPause: ошибка определения rbot app port");
            return false;
        }
        if ($port <= 0) {
            TOOLS::$log->error("checkPause: ошибка определения rbot app port (2)");
            return false;
        }

        $file = getcwd() . "\\res\\pause_{$port}.txt";

        while (file_exists($file)) {
            TOOLS::$log->debug("Пауза, ждём: {$file} ...");
            sleep($wt);
        }

        return true;
    }
}
