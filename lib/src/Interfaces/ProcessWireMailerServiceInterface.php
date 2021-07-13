<?php


namespace Symprowire\Interfaces;


use Symfony\Component\Mailer\MailerInterface;

interface ProcessWireMailerServiceInterface extends MailerInterface
{
    public function setSubject(string $subject);
}
