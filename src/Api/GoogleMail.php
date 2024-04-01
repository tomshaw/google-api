<?php

namespace TomShaw\GoogleApi\Api;

use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Validator;
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

    protected array $attachments = [];

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
     * Add a single email attachment.
     *
     * @param  string  $path  The path to the attachment.
     * @return GoogleMail The current instance.
     */
    public function attachment(string $path): self
    {
        $this->attachments[] = $path;

        return $this;
    }

    /**
     * Add an array of email attachments.
     *
     * @param  array  $paths  The path to the attachment.
     * @return GoogleMail The current instance.
     */
    public function attachments(array $paths): self
    {
        $this->attachments = $paths;

        return $this;
    }

    /**
     * Sends the email.
     *
     * @return Message The message object.
     */
    public function send(): Message
    {
        if (count($this->attachments)) {
            $this->validateAttachments($this->attachments);
        }

        $validated = $this->validateMessage();

        $message = $this->buildMessage($validated);

        $msg = new Message();
        $msg->setRaw($this->encodeUrlSafeMessage($message));

        return $this->service->users_messages->send('me', $msg);
    }

    /**
     * Builds the email message.
     *
     * @param  array  $validated  The validated data.
     * @return string The email message.
     */
    protected function buildMessage(array $validated): string
    {
        $boundary = md5(time());

        $headers = "From: {$validated['fromName']} <{$validated['fromEmail']}>\r\n";
        $headers .= "To: {$validated['toName']} <{$validated['toEmail']}>\r\n";
        if (count($this->getCC())) {
            $headers .= "CC: {$this->getCCString()}\r\n";
        }
        $headers .= "Subject: {$validated['subject']}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n\r\n";

        foreach ($this->attachments as $attachment) {
            $attachmentData = base64_encode(file_get_contents($attachment));

            $headers .= "--$boundary\r\n";
            $headers .= 'Content-Type: '.mime_content_type($attachment).'; name="'.basename($attachment)."\"\r\n";
            $headers .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $headers .= $attachmentData."\r\n\r\n";
        }

        $headers .= "--$boundary\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $headers .= "{$validated['message']}\r\n\r\n";

        $headers .= "--$boundary--";

        return $headers;
    }

    /**
     * Validates the email attachments.
     *
     * The maximum total size is set to 25MB, which is the limit for attachments sent through Gmail.
     *
     * @param  array  $attachments  The attachments to validate.
     *
     * @throws \Exception If the attachments are invalid.
     */
    protected function validateAttachments(array $attachments): void
    {
        $totalSize = 0;
        $maxTotalSize = 25 * 1024 * 1024; // 25 MB

        foreach ($attachments as $attachment) {
            if (! file_exists($attachment)) {
                throw new \Exception("File $attachment does not exist");
            }
            if (! is_readable($attachment)) {
                throw new \Exception("File $attachment is not readable");
            }
            $totalSize += filesize($attachment);
        }
        if ($totalSize > $maxTotalSize) {
            throw new \Exception('Total size of attachments exceeds the maximum size limit');
        }
    }

    /**
     * Encodes a message into a URL-safe format.
     *
     * @param  string  $message  The message to encode.
     * @return string The encoded message.
     */
    protected function encodeUrlSafeMessage(string $message): string
    {
        return rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
    }

    /**
     * Validates the email message.
     *
     * @return array The validated data.
     */
    protected function validateMessage(): array
    {
        $validator = Validator::make([
            'fromEmail' => $this->getFromEmail(),
            'fromName' => $this->getFromName(),
            'toEmail' => $this->getToEmail(),
            'toName' => $this->getToName(),
            'subject' => $this->getSubject(),
            'message' => $this->getMessage(),
        ], [
            'fromEmail' => 'required|email',
            'fromName' => 'required',
            'toEmail' => 'required|email',
            'toName' => 'required',
            'subject' => 'required',
            'message' => 'required',
        ]);

        if ($validator->fails()) {
            throw new GoogleApiException($validator->errors()->first());
        }

        return $validator->validated();
    }
}
