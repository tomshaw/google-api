<?php

namespace TomShaw\GoogleApi\Api;

use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Container\Container;
use Illuminate\Mail\Mailable;
use TomShaw\GoogleApi\Exceptions\GoogleApiException;
use TomShaw\GoogleApi\GoogleClient;

final class GoogleMail
{
    protected $service;

    protected $toName = 'Receiver Name';

    protected $toEmail;

    protected $ccList;

    protected $fromName = 'Sender Name';

    protected $fromEmail;

    protected $subject;

    protected $message;

    public function __construct(GoogleClient $client)
    {
        if (! $client->getAccessToken()) {
            $client->createAuthUrl();
        }

        $this->service = new Gmail($client->getClient());

        $this->setFromName(config('google-api.service.gmail.sender.name'));
        $this->setFromEmail(config('google-api.service.gmail.sender.email'));
    }

    public function setToName($toName): GoogleMail
    {
        $this->toName = $toName;

        return $this;
    }

    public function getToName(): string
    {
        return $this->toName;
    }

    public function setToEmail($toEmail): GoogleMail
    {
        $this->toEmail = $toEmail;

        return $this;
    }

    public function setCC($ccList): mixed
    {
        $this->ccList = $ccList;

        return $this;
    }

    public function getCC(): string
    {
        $ccPeople = $this->ccList;
        $ccArray = preg_split('/[\s,;]+/', $ccPeople);

        $emails = [];

        foreach ($ccArray as $email) {
            $emails[] = trim($email);
        }

        return implode(', ', $emails);
    }

    public function getToEmail(): string
    {
        return $this->toEmail;
    }

    public function setFromName($fromName): GoogleMail
    {
        $this->fromName = $fromName;

        return $this;
    }

    public function getFromName(): string
    {
        return $this->fromName;
    }

    public function setFromEmail($fromEmail): GoogleMail
    {
        $this->fromEmail = $fromEmail;

        return $this;
    }

    public function getFromEmail(): string
    {
        return $this->fromEmail;
    }

    public function setSubject($subject): GoogleMail
    {
        $this->subject = $subject;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setMessage($message): GoogleMail
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function to($email, $name = ''): GoogleMail
    {
        $this->setToEmail($email);
        $this->setToName($name);

        return $this;
    }

    public function from($email, $name = ''): GoogleMail
    {
        $this->setFromEmail($email);
        $this->setFromName($name);

        return $this;
    }

    public function subject($subject): GoogleMail
    {
        $this->setSubject($subject);

        return $this;
    }

    public function message($message): GoogleMail
    {
        $this->setMessage($message);

        return $this;
    }

    public function mailable(Mailable $mailable): GoogleMail
    {
        /** @phpstan-ignore-next-line */
        $content = $mailable->content();

        $message = Container::getInstance()->make('mailer')->render($content->view, $content->with);

        $this->setMessage($message);

        return $this;
    }

    public function send(): Message
    {
        $fromEmail = ($this->getFromEmail()) ? $this->getFromEmail() : false;
        $fromName = ($this->getFromName()) ? $this->getFromName() : 'Sender Name';

        $toEmail = ($this->toEmail) ? $this->toEmail : false;
        $toName = ($this->toName) ? $this->toName : 'Receiver Name';

        $subject = $this->getSubject();
        $message = $this->getMessage();

        if (! $fromEmail) {
            throw new GoogleApiException('Missing sender email address.');
        }

        if (! $toEmail) {
            throw new GoogleApiException('Missing receiver email address.');
        }

        if (! $subject) {
            throw new GoogleApiException('Missing email subject.');
        }

        if (! $message) {
            throw new GoogleApiException('Missing email message.');
        }

        $message = $this->buildMessage($fromEmail, $fromName, $toEmail, $toName, $subject, $message);

        // The message needs to be encoded in Base64URL
        $mime = rtrim(strtr(base64_encode($message), '+/', '-_'), '=');

        $msg = new Message();
        $msg->setRaw($mime);

        return $this->sendEmail($msg);
    }

    protected function buildMessage($fromEmail, $fromName, $toEmail, $toName, $subject, $message): string
    {
        $str = "From: $fromName <$fromEmail>\r\n";
        $str .= "To: $toName <$toEmail>\r\n";
        $str .= "CC: {$this->getCC()}\r\n";
        $str .= 'Subject: =?utf-8?B?'.base64_encode($subject)."?=\r\n";
        $str .= "MIME-Version: 1.0\r\n";
        $str .= "Content-Type: text/html; charset=utf-8\r\n";
        //$str .= "Content-Transfer-Encoding: base64" . "\r\n\r\n";
        $str .= 'Content-Transfer-Encoding: 8bit'."\r\n\r\n";
        $str .= $message;

        return $str;
    }

    /**
     * The special value **me** can be used to indicate the authenticated user.
     */
    protected function sendEmail(Message $msg): Message
    {
        return $this->service->users_messages->send('me', $msg);
    }
}
