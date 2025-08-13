<?php

/**
 * Пример (SMTP):
 * ```
 *    TOOLS::$mailer::makeSMTP(
 *        $strServer,
 *        $intPort,
 *        $strLogin,
 *        $strPassword,
 *        $intSslOption,
 *        $strCertType,
 *        $intConnectTimeout,
 *        $strLogPath
 *    );
 *    TOOLS::$mailer::getMailer()
 *        ->setIsTurnedOff(true)
 *        ->setFrom('test@example.ru')
 *        ->setTo('test@example.ru;test2@example.ru')
 *        ->setCopy('test3@example.ru;test4@example.ru')
 *        ->setSubject('test subj')
 *        ->setMessage('test message');
 *        ->setAttachments([$filepath1, $filepath2]);
 *    $res = TOOLS::$mailer::getMailer()->sendText();
 *    TOOLS::$log->debug($res);
 * ```
 *
 * Пример (MS Outlook):
 * ```
 *    TOOLS::$mailer::makeOutlook();
 *    TOOLS::$mailer::getMailer()
 *        ->setIsTurnedOff(true)
 *        ->setFrom('test@example.ru')
 *        ->setTo('test@example.ru;test2@example.ru')
 *        ->setCopy('test3@example.ru;test4@example.ru')
 *        ->setSubject('test subj')
 *        ->setMessage('test message');
 *        ->setAttachments([$filepath1, $filepath2]);
 *    $res = TOOLS::$mailer::getMailer()->sendText();
 *    TOOLS::$log->debug($res);
 * ```
 */
class Mailer
{
    private static $objMailer = null;

    const TYPE_MESSAGE_HTML = 0;
    const TYPE_MESSAGE_RTF = 1;
    const TYPE_MESSAGE_TEXT = 2;

    protected $strTo = '';
    protected $strFrom = '';
    protected $strSubject = '';
    protected $strMessage = '';
    protected $strCopy = '';
    protected $strHiddenCopy = '';
    protected $arrAttachments = null;
    protected $intSendTimeout = 300;
    protected $strReplyTo = '';

    protected $boolIsTurnedOff = false;

    /**
     * Создает объект MailerOutlook для отправки сообщений через MS Outlook
     *
     * @return void
     **/
    public static function makeOutlook()
    {
        self::$objMailer = new MailerOutlook();
    }

    /**
     * Создает объект MailerSMTP для отправки сообщений через smtp
     *
     * @param  $strServer адрес smtp сервера (можно передавать string)
     * @param  $intPort порт smtp сервера (можно передавать int)
     * @param  $strLogin login для smtp сервера (можно передавать string)
     * @param  $strPassword пароль для smtp сервера (можно передавать string)
     * @param  $intSslOption (можно передавать int)
     * @param  $strCertType (можно передавать string)
     * @param  $intConnectTimeout (можно передавать int)
     * @param  $strLogPath (можно передавать string)
     * 
     * @see https://rbot-rpa.com/wiki/mail/smtp-connect/
     *
     * @return void
     **/
    public static function makeSMTP(
        $strServer,
        $intPort,
        $strLogin,
        $strPassword,
        $intSslOption = 1,
        $strCertType = "s, c, h, e",
        $intConnectTimeout = 3000,
        $strLogPath = ''
    )
    {
        self::$objMailer = new MailerSMTP($strServer, $intPort, $strLogin, $strPassword, $intSslOption, $strCertType, $intConnectTimeout, $strLogPath);
    }

    /**
     * Геттер для получения ранее созданного (makeSMTP/makeOutlook)
     * объекта MailerSMTP/MailerOutlook
     *
     * @return MailerSMTP|MailerOutlook
     **/
    public static function getMailer()
    {
        return self::$objMailer;
    }

    /**
     * Устанавливает email на который будет отправляться почтовое сообщение
     *
     * @param  $strValue Email на который будет отправляться почтовое сообщение (можно передавать string или array)
     *
     * @return this
     **/
    public function setTo($strValue)
    {
        if (is_array($strValue)) { $strValue = implode(';', $strValue); }

        $this->strTo = $strValue;
        return $this;
    }

    /**
     * Устанавливает email с которого будет происходить отправка почтового сообщения
     *
     * @param  $strValue Email с которого будет происходить отправка почтового сообщения (можно передавать string)
     *
     * @return this
     **/
    public function setFrom($strValue)
    {
        $this->strFrom = $strValue;
        return $this;
    }

    /**
     * Устанавливает тему почтового сообщения
     *
     * @param  $strValue Тема почтового сообщения (можно передавать string)
     *
     * @return this
     **/
    public function setSubject($strValue)
    {
        $this->strSubject = $strValue;
        return $this;
    }

    /**
     * Устанавливает тело почтового сообщения
     *
     * @param  $strValue Тело почтового сообщения (можно передавать string)
     *
     * @return this
     **/
    public function setMessage($strValue)
    {
        $this->strMessage = $strValue;
        return $this;
    }

    /**
     * Устанавливает CC для почтового сообщения
     *
     * @param  $strValue Сс для почтового сообщения (можно передавать string или array)
     *
     * @return this
     **/
    public function setCopy($strValue)
    {
        if (is_array($strValue)) { $strValue = implode(';', $strValue); }

        $this->strCopy = $strValue;
        return $this;
    }

    /**
     * Устанавливает BCC для почтового сообщения
     *
     * @param  $strValue Bcc для почтового сообщения (можно передавать string или array)
     *
     * @return this
     **/
    public function setHiddenCopy($strValue)
    {
        if (is_array($strValue)) { $strValue = implode(';', $strValue); }

        $this->strHiddenCopy = $strValue;
        return $this;
    }

    /**
     * Прикрепляет файлы к почтовому сообщению
     *
     * @param  $arrValue Массив с файлами, которые нужно прикрепить к почтовому сообщению (можно передавать один файл в виде строки)
     *
     * @return this
     **/
    public function setAttachments($arrValue)
    {
        if (!is_array($arrValue)) { $arrValue = [strval($arrValue)]; }

        $this->arrAttachments = $arrValue;
        return $this;
    }

    /**
     * Send email timeout
     *
     * @param  $intValue Timeout (можно передавать int)
     *
     * @return this
     **/
    public function setSendTimeout($intValue)
    {
        $this->intSendTimeout = $intValue;
        return $this;
    }

    /**
     * Установить Reply-To
     *
     * @param  $strValue (можно передавать string)
     *
     * @return this
     **/
    public function setReplyTo($strValue)
    {
        $this->strReplyTo = $strValue;
        return $this;
    }

    /**
     * Получает флаг, который показывает включена ли отправка почты, или нет
     *
     * @return boolean
     **/
    public function getIsTurnedOff()
    {
        return $this->boolIsTurnedOff;
    }

    /**
     * Выключает или включает отправку почты
     *
     * @param  $boolValue (можно передавать boolean)
     *
     * @return this
     **/
    public function setIsTurnedOff($boolValue)
    {
		if (!is_bool($boolValue)) { $boolValue = (bool) $boolValue; }

        $this->boolIsTurnedOff = $boolValue;
        return $this;
    }

    /**
     * Отправляет сообщение с типом Plain/Text (учитывается boolIsTurnedOff)
     *
     * @return bool
     **/
    public function sendText()
    {
        if ($this->boolIsTurnedOff) { return true; }

        return $this->send(self::TYPE_MESSAGE_TEXT);
    }

    /**
     * Отправляет сообщение с типом Html (учитывается boolIsTurnedOff)
     *
     * @return bool
     **/
    public function sendHtml()
    {
        if ($this->boolIsTurnedOff) { return true; }

        return $this->send(self::TYPE_MESSAGE_HTML);
    }

    /**
     * Отправляет сообщение с типом Rtf (учитывается boolIsTurnedOff)
     *
     * @return bool
     **/
    public function sendRtf()
    {
        if ($this->boolIsTurnedOff) { return true; }

        return $this->send(self::TYPE_MESSAGE_RTF);
    }

    /**
     * Производит очистку данных после отправки сообщения
     *
     * @return void
     **/
    protected function clear()
    {
        $defValues = get_class_vars(get_class($this));

        $this->strFrom = $defValues['strFrom'];
        $this->strTo = $defValues['strTo'];
        $this->strSubject = $defValues['strSubject'];
        $this->strMessage = $defValues['strMessage'];
        $this->strCopy = $defValues['strCopy'];
        $this->strHiddenCopy = $defValues['strHiddenCopy'];
        $this->arrAttachments = $defValues['arrAttachments'];
        $this->intSendTimeout = $defValues['intSendTimeout'];
        $this->strReplyTo = $defValues['strReplyTo'];
    }

    /**
     * Производит проверку обязательных для отправки почтового сообщения данных
     *
     * @return bool
     **/
    protected function checkRequiredParameters()
    {
        if (empty($this->strFrom) || empty($this->strTo) || empty($this->strSubject)) {
            TOOLS::$log->error("Обязательные параметры не заполнены (from/to/subject)", "Mailer.checkRequiredParameters");
            return false;
        }
        return true;
    }
}
