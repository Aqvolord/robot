<?php

// Автозагрузка библиотек из Composer
$composerLibsAutoloadTmp = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerLibsAutoloadTmp)) { require_once($composerLibsAutoloadTmp); }

// classic
require_once(__DIR__ . "/functions.php");
require_once(__DIR__ . "/functions_sigma.php");

// Классы-помошники
require_once(__DIR__ . "/core/settingsHelper.php");
require_once(__DIR__ . "/core/logHelper.php");
require_once(__DIR__ . "/core/protocolHelper.php");
require_once(__DIR__ . "/core/domHelper.php");
require_once(__DIR__ . "/core/strHelper.php");
require_once(__DIR__ . "/core/systemHelper.php");
require_once(__DIR__ . "/core/rerunHelper.php");
require_once(__DIR__ . "/core/wndHelper.php");
require_once(__DIR__ . "/core/appHelper.php");
require_once(__DIR__ . "/core/dtHelper.php");
require_once(__DIR__ . "/core/simpleTabsHelper.php");
require_once(__DIR__ . "/core/notificationsList.php");

// RPAbot Mail Sender
require_once(__DIR__ . "/core/Mailer/Mailer.php");
require_once(__DIR__ . "/core/Mailer/MailerOutlook.php");
require_once(__DIR__ . "/core/Mailer/MailerSMTP.php");

// Автозагрузка стандартных модулей
$modulesFolderPath = __DIR__ . '/core/modules/';
if (file_exists($modulesFolderPath))
{
    $arrFilesModules = [];
    foreach (new DirectoryIterator($modulesFolderPath) as $item)
    {
        if ($item->isFile() && (mb_strtolower($item->getExtension()) === 'php'))
        {
            $arrFilesModules[] = $item->getFilename();
        }
    }
	foreach ($arrFilesModules as $moduleFile)
	{
		require_once($modulesFolderPath . $moduleFile);
	}
}

// Robot app
require_once(__DIR__ . "/robot.php");

// Стандартные помощники
$settingsHelper = new SettingsHelper();
$logHelper = new LogHelper();
$protocolHelper = new ProtocolHelper();
$domHelper = new DomHelper();
$strHelper = new StrHelper();
$systemHelper = new SystemHelper();
$rerunHelper = new RerunHelper();
$wndHelper = new WndHelper();
$appHelper = new AppHelper();
$dtHelper = new DtHelper();
$simpleTabsHelper = new SimpleTabsHelper();
$mailer = new Mailer();
$robot = new Robot();

// Дополнительные стандартные модули
// ЕСИА
$esia = null;
if (class_exists('Esia')) { $esia = new Esia(); }
// ЕПГУ, Госуслуги
$gosuslugi = null;
if (class_exists('Gosuslugi')) { $gosuslugi = new Gosuslugi(); }
// ГИС ЖКХ
$giszhkh = null;
if (class_exists('Giszhkh')) { $giszhkh = new Giszhkh(); }
// Сигма Биллинг
$sigmaBilling = null;
if (class_exists('SigmaBilling')) { $sigmaBilling = new SigmaBilling(); }
// ФССП
$fssp = null;
if (class_exists('Fssp')) { $fssp = new Fssp(); }
// АИСГород
$aisGorod = null;
if (class_exists('AISGorod')) { $aisGorod = new AISGorod(); }
// ДУСИА
$dusia = null;
if (class_exists('Dusia')) { $dusia = new Dusia(); }
// CRM
$crm = null;
if (class_exists('Crm')) { $crm = new Crm(); }

// Для статического доступа
require_once(__DIR__ . "/core/tools.php");
require_once(__DIR__ . "/core/settings.php");
require_once(__DIR__ . "/core/modules.php");
