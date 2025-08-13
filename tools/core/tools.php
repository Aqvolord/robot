<?php

// For static call of all TOOLS objects example:
// TOOLS::$log->info("Кукареку!", "main");
class TOOLS
{
	public static $dom = null; 
	public static $robot = null;
	public static $log = null;
	public static $mailer = null;
    public static $protocol = null;
    public static $str = null;
    public static $system = null;
    public static $app = null;
    public static $rerun = null;
    public static $wnd = null;
    public static $dt = null;
    public static $simpleTabsHelper = null;
}

// initialization
TOOLS::$dom = $domHelper;
TOOLS::$robot = $robot;
TOOLS::$log = $logHelper;
TOOLS::$mailer = $mailer;
TOOLS::$protocol = $protocolHelper;
TOOLS::$str = $strHelper;
TOOLS::$system = $systemHelper;
TOOLS::$app = $appHelper;
TOOLS::$rerun = $rerunHelper;
TOOLS::$wnd = $wndHelper;
TOOLS::$dt = $dtHelper;
TOOLS::$simpleTabsHelper = $simpleTabsHelper;