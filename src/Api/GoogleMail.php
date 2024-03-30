<?php

namespace TomShaw\GoogleApi\Api;

use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Mail\Mailable;
use TomShaw\GoogleApi\Exceptions\GoogleApiException;
use TomShaw\GoogleApi\GoogleClient;

/**
 * Class GoogleMail
 */
final class GoogleMail
{
    protected Gmail $service;

    protected ?string $toName;

    protected ?string $toEmail;

    protected array $ccList = [];

    protected ?string $fromName;

    protected ?string $fromEmail;

    protected ?string $subject;

    protected ?string $message;

    public function __construct(protected GoogleClient $client)
    {
        $this->service = new Gmail($client());
    }

    /**
     * Sets the 'to' name.
     *
     * @param  string  $toName  The name to set.
     * @return GoogleMail The current instance.
     */
    public function setToName(string $toName): GoogleMail
    {
        $this->toName = $toName;

        return $this;
    }

    /**
     * Gets the 'to' name.
     *
     * @return string The 'to' name.
     */
    public function getToName(): string
    {
        return $this->toName;
    }

    /**
     * Sets the 'to' email.
     *
     * @param  string  $toEmail  The email to set.
     * @return GoogleMail The current instance.
     */
    public function setToEmail(string $toEmail): GoogleMail
    {
        $this->toEmail = $toEmail;

        return $this;
    }

    /**
     * Gets the 'to' email.
     *
     * @return string The 'to' email.
     */
    public function getToEmail(): string
    {
        return $this->toEmail;
    }

    /**
     * Sets the CC list.
     *
     * @param  array  $ccList  The CC list to set.
     * @return GoogleMail The current instance.
     */
    public function setCC(array $ccList = []): GoogleMail
    {
        $this->ccList = $ccList;

        return $this;
    }

    /**
     * Gets the CC list.
     *
     * @return array The CC list.
     */
    public function getCC(): array
    {
        return $this->ccList;
    }

    /**
     * Gets the CC list as a string.
     *
     * @return string The CC list as a string.
     */
    public function getCCString(): string
    {
        $emails = [];

        foreach ($this->ccList as $email) {
            $emails[] = trim($email);
        }

        return implode(', ', $emails);
    }

    public function setFrom(string $email, string $name): GoogleMail
    {
        $this->setFromEmail($email);
        $this->setFromName($name);

        return $this;
    }

    /**
     * Sets the 'from' name.
     *
     * @param  string  $fromName  The name to set.
     * @return GoogleMail The current instance.
     */
    public function setFromName(string $fromName): GoogleMail
    {
        $this->fromName = $fromName;

        return $this;
    }

    /**
     * Gets the 'from' name.
     *
     * @return string The 'from' name.
     */
    public function getFromName(): string
    {
        return $this->fromName;
    }

    /**
     * Sets the 'from' email.
     *
     * @param  string  $fromEmail  The email to set.
     * @return GoogleMail The current instance.
     */
    public function setFromEmail(string $fromEmail): GoogleMail
    {
        $this->fromEmail = $fromEmail;

        return $this;
    }

    /**
     * Gets the 'from' email.
     *
     * @return string The 'from' email.
     */
    public function getFromEmail(): string
    {
        return $this->fromEmail;
    }

    /**
     * Sets the subject of the email.
     *
     * @param  string  $subject  The subject to set.
     * @return GoogleMail The current instance.
     */
    public function setSubject(string $subject): GoogleMail
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Gets the subject of the email.
     *
     * @return string The subject of the email.
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Sets the message of the email.
     *
     * @param  string  $message  The message to set.
     * @return GoogleMail The current instance.
     */
    public function setMessage(string $message): GoogleMail
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Gets the message of the email.
     *
     * @return string The message of the email.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Sets both 'to' name and email in one method call.
     *
     * @param  string  $email  The 'to' email to set.
     * @param  string  $name  The 'to' name to set.
     * @return GoogleMail The current instance.
     */
    public function to(string $email, string $name): GoogleMail
    {
        $this->setToEmail($email);
        $this->setToName($name);

        return $this;
    }

    /**
     * Sets both 'from' name and email in one method call.
     *
     * @param  string  $email  The 'from' email to set.
     * @param  string  $name  The 'from' name to set.
     * @return GoogleMail The current instance.
     */
    public function from(string $email, string $name): GoogleMail
    {
        $this->setFromEmail($email);
        $this->setFromName($name);

        return $this;
    }

    /**
     * Sets the subject of the email.
     *
     * @param  mixed  $subject  The subject to set.
     * @return GoogleMail The current instance.
     */
    public function subject($subject): GoogleMail
    {
        $this->setSubject($subject);

        return $this;
    }

    /**
     * Sets the message of the email.
     *
     * @param  mixed  $message  The message to set.
     * @return GoogleMail The current instance.
     */
    public function message($message): GoogleMail
    {
        $this->setMessage($message);

        return $this;
    }

    /**
     * Sets the message of the email using a Mailable instance.
     *
     * @param  Mailable  $mailable  The Mailable instance.
     * @return GoogleMail The current instance.
     */
    public function mailable(Mailable $mailable): GoogleMail
    {
        $message = $mailable->render();

        $this->setMessage($message);

        return $this;
    }

    /**
     * Sends an email message.
     *
     * @return Message Returns the sent message.
     *
     * @throws GoogleApiException If any of the required fields (From name and email, To name and email, subject, message) are missing.
     */
    public function send(): Message
    {
        $fromEmail = $this->getFromEmail();
        $fromName = $this->getFromName();

        $toEmail = $this->getToEmail();
        $toName = $this->getToName();

        $ccListString = $this->getCCString();

        $subject = $this->getSubject();
        $message = $this->getMessage();

        if (! $fromEmail || ! $fromName) {
            throw new GoogleApiException('Both from name and email are required.');
        }

        if (! $toEmail || ! $toName) {
            throw new GoogleApiException('Both to name and email are required.');
        }

        if (! $subject) {
            throw new GoogleApiException('An email subject is required.');
        }

        if (! $message) {
            throw new GoogleApiException('The email message is required.');
        }

        $message = $this->buildMessage($fromEmail, $fromName, $toEmail, $toName, $ccListString, $subject, $message);

        $msg = new Message();
        $msg->setRaw($message);

        return $this->service->users_messages->send('me', $msg);
    }

    protected function buildMessage(string $fromEmail, string $fromName, string $toEmail, string $toName, string $ccListString, string $subject, string $message): string
    {
        $headers = "From: $fromName <$fromEmail>\r\n";
        $headers .= "To: $toName <$toEmail>\r\n";
        if (count($this->getCC())) {
            $headers .= "CC: {$ccListString}\r\n";
        }
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";
        $headers .= 'Content-Transfer-Encoding: 8bit'."\r\n\r\n";
        $headers .= $message;

        return base64_encode($headers);
    }

    protected function buildMessageBinary(string $fromEmail, string $fromName, string $toEmail, string $toName, string $ccListString, string $subject, string $message, string $contentType): string
    {
        $headers = "From: $fromName <$fromEmail>\r\n";
        $headers .= "To: $toName <$toEmail>\r\n";
        if (count($this->getCC())) {
            $headers .= "CC: {$ccListString}\r\n";
        }
        $headers .= 'Subject: =?utf-8?B?'.base64_encode($subject)."?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: $contentType\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n\r\n";

        $encodedMessage = base64_encode($message);

        return $headers.$encodedMessage;
    }

    protected function encodeUrlSafeMessage(string $message): string
    {
        return rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
    }
}
