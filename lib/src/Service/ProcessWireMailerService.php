<?php


namespace Symprowire\Service;


use ErrorException;
use ProcessWire\Wire;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\RawMessage;
use Symprowire\Interfaces\ProcessWireMailerServiceInterface;

class ProcessWireMailerService extends Wire implements ProcessWireMailerServiceInterface
{
    private $mailer;

    protected string $subject;

    public function __construct() {
        $this->mailer = wire('mail');
    }

    public function setSubject(string $subject) {
        $this->subject = $subject;
    }

    /**
     * @throws ErrorException
     */
    public function send(RawMessage $message, Envelope $envelope = null): void
    {
        if(!$this->subject) throw new ErrorException('missing subject');
        $mail = $this->mailer->new();
        $mail->subject($this->subject)
            ->to($envelope->getRecipients())
            ->from($envelope->getSender())
            ->body($message->toString())
            ->bodyHTML($message->toString());
        $mail->send();
    }
}
