<?php

/**
 * Пример:
 * ```
 *	TOOLS::$mailer::makeSMTP(
 *        $strServer,
 *        $intPort,
 *        $strLogin,
 *        $strPassword,
 *        $intSslOption,
 *        $strCertType,
 *        $intConnectTimeout,
 *        $strLogPath
 *	);
 *	TOOLS::$mailer::getMailer()
 *		->setFrom('test@example.ru')
 *		->setTo('test@example.ru;test2@example.ru')
 *		->setCopy('test3@example.ru;test4@example.ru')
 *		->setSubject('test subj')
 *		->setMessage('test message');
 *		->setAttachments([$filepath1, $filepath2]);
 *	$res = TOOLS::$mailer::getMailer()->sendText();
 *	TOOLS::$log->debug($res);
 * ```
 */
class MailerSMTP extends Mailer
{
    private $strServer;
    private $intPort;
    private $strLogin;
    private $strPassword;
    private $intSslOption;
    private $strCertType;
    private $intConnectTimeout;
    private $strLogPath;

    public function __construct(
        $strServer,
        $intPort,
        $strLogin,
        $strPassword,
        $intSslOption,
        $strCertType,
        $intConnectTimeout,
        $strLogPath
    )
    {
        $this->strServer = $strServer;
        $this->intPort = $intPort;
        $this->strLogin = $strLogin;
        $this->strPassword = $strPassword;
        $this->intSslOption = $intSslOption;
        $this->strCertType = $strCertType;
        $this->intConnectTimeout = $intConnectTimeout;
        $this->strLogPath = $strLogPath;
    }

    /**
     * Устанавливает соединение с smtp сервером
     *
     * @return bool
     **/
    protected function connect()
    {
        $bResult = WEB::$mail->smtp_connect(
            $this->strServer,
            $this->intPort,
            $this->strLogin,
            $this->strPassword,
            $this->intSslOption,
            $this->strCertType,
            $this->intConnectTimeout,
            $this->strLogPath
        );
        if (!$bResult) { TOOLS::$log->error("Не смогли подключиться к SMTP серверу", "MailerSMTP.connect"); }

        return $bResult;
    }

    /**
     * Отправка email-сообщения через SMTP, для публичного API
     * см. sendText/sendHtml и т.п.
     *
     * Здесь не учитывается boolIsTurnedOff.
     * 
     * @param $intMessageType Тип почтового сообщения (можно передавать int)
     * @return bool
     **/
    protected function send($intMessageType)
    {
        $this->checkRequiredParameters();

        $this->connect();

		$bResult = WEB::$mail->send_mail_via_smtp(
            $this->strFrom,
            $this->strTo,
            $this->strSubject,
            $this->strMessage,
            $intMessageType,
            $this->strCopy,
            $this->strHiddenCopy,
            $this->arrAttachments,
            $this->intSendTimeout,
            $this->strReplyTo
        );
        if (!$bResult) { TOOLS::$log->error("Не смогли отправить email", "MailerSMTP.send"); }

        $this->disconnect();

        $this->clear();

        return $bResult;
    }

    /**
     * Производит отключение от smtp сервера
     *
     * @return bool
     **/
    protected function disconnect(): bool
    {
        return WEB::$mail->smtp_disconnect();
    }
}
