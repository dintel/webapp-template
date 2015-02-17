<?php
namespace Application\Mail;

use Zend\Mail\Message;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;
use Zend\Mail\Transport\Sendmail as SendmailTransport;
use Zend\View\Model\ViewModel;

class Mail
{
    private $renderer;
    private $from;
    private $transport;

    public function __construct($renderer, $from)
    {
        $this->renderer = $renderer;
        $this->from = $from;
        $this->transport = new SendmailTransport();
    }

    public function send($template, $subject, $recipients, $model)
    {
        $content = $this->renderer->render("email/$template", $model);

        $html = new MimePart($content);
        $html->type = "text/html";
        $body = new MimeMessage();
        $body->setParts([$html]);

        $mail = new Message();
        $mail->setBody($body);
        $mail->setFrom($this->from);
        $mail->setTo($recipients);
        $mail->setSubject($subject);

        $this->transport->send($mail);
    }
}
