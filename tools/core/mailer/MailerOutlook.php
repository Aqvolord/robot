<?php

/**
 * Пример:
 * ```
 *	TOOLS::$mailer::makeOutlook();
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
class MailerOutlook extends Mailer
{
    /**
     * Отправка email-сообщения через MS Outlook, для публичного API
     * см. sendText/sendHtml и т.п.
     * 
     * Здесь не учитывается boolIsTurnedOff.
     *
     * @param $intMessageType Тип почтового сообщения (можно передавать int)
     * @return bool
     */
    protected function send($intMessageType)
    {
        $this->checkRequiredParameters();

        $bResult = WEB::$mail->send_mail_via_outlook(
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
        if (!$bResult) { TOOLS::$log->error("Не смогли отправить email", "MailerOutlook.send"); }

        $this->clear();

        return $bResult;
    }
}
