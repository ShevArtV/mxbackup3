<?php

namespace MxBackup\Platform\Modx3;

use MODX\Revolution\Mail\modMail;
use MODX\Revolution\Mail\modPHPMailer;
use MODX\Revolution\modX;
use MxBackup\Core\Contract\MailerInterface;

final class Mailer implements MailerInterface
{
    private $modx;

    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    public function send(array $recipients, $subject, $body, $attachment = null)
    {
        // В MODX 3 почтовый сервис по-прежнему поднимается через getService,
        // но класс передаётся полным именем (так же делает modUser::sendEmail).
        $mail = $this->modx->getService('mail', modPHPMailer::class);
        if (!$mail) {
            return false;
        }

        $mail->set(modMail::MAIL_BODY, nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')));
        $mail->set(modMail::MAIL_FROM, $this->modx->getOption('emailsender'));
        $mail->set(modMail::MAIL_FROM_NAME, $this->modx->getOption('site_name'));
        $mail->set(modMail::MAIL_SUBJECT, $subject);
        foreach ($recipients as $recipient) {
            $mail->address('to', $recipient);
        }
        if ($attachment) {
            $mail->attach($attachment);
        }
        $mail->setHTML(true);
        $sent = (bool)$mail->send();
        $mail->reset();
        return $sent;
    }
}
