<?php


namespace Symprowire\Service;


use ErrorException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\RawMessage;
use Symprowire\Interfaces\ProcessWireMailerServiceInterface;
use Symprowire\Interfaces\ProcessWireServiceInterface;

class ProcessWireMailerService implements ProcessWireMailerServiceInterface
{
    private $mailer;

    protected string $subject;

    public function __construct(ProcessWireServiceInterface $processWire) {
        $this->mailer = $processWire->get('mail');
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
