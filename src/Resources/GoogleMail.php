<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi\Resources;

use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Google\Service\Gmail\Resource\UsersMessages;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Validator;
use TomShaw\GoogleApi\Exceptions\GoogleApiException;
use TomShaw\GoogleApi\GoogleClient;

final class GoogleMail
{
    public private(set) Gmail $service;

    public ?string $toName = null;

    public ?string $toEmail = null;

    /** @var array<int, string> */
    public array $cc = [] {
        set => array_map(trim(...), $value);
    }

    /** @var array<int, string> */
    public array $bcc = [] {
        set => array_map(trim(...), $value);
    }

    public ?string $fromName = null;

    public ?string $fromEmail = null;

    public ?string $subject = null;

    public ?string $message = null;

    /** @var array<int, string> */
    public array $attachments = [];

    public function __construct(protected GoogleClient $client)
    {
        $this->service = new Gmail($client());
    }

    /**
     * Sets the 'from' name and email.
     */
    public function from(string $email, string $name): self
    {
        $this->fromEmail = $email;
        $this->fromName = $name;

        return $this;
    }

    /**
     * Sets the 'to' name and email.
     */
    public function to(string $email, string $name): self
    {
        $this->toEmail = $email;
        $this->toName = $name;

        return $this;
    }

    /**
     * Adds one or more carbon copy emails.
     *
     * @param  string|array<int, string>  $email
     */
    public function cc(string|array $email): self
    {
        $this->cc = array_merge($this->cc, (array) $email);

        return $this;
    }

    /**
     * Adds one or more blind carbon copy emails.
     *
     * @param  string|array<int, string>  $email
     */
    public function bcc(string|array $email): self
    {
        $this->bcc = array_merge($this->bcc, (array) $email);

        return $this;
    }

    /**
     * Sets the subject of the email.
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Sets the message of the email.
     */
    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Sets the message of the email using a Mailable instance.
     */
    public function mailable(Mailable $mailable): self
    {
        $this->message = $mailable->render();

        return $this;
    }

    /**
     * Adds a single email attachment.
     */
    public function attachment(string $path): self
    {
        $this->attachments[] = $path;

        return $this;
    }

    /**
     * Adds an array of email attachments.
     *
     * @param  array<int, string>  $paths
     */
    public function attachments(array $paths): self
    {
        $this->attachments = array_merge($this->attachments, $paths);

        return $this;
    }

    /**
     * Sends the email.
     */
    public function send(): Message
    {
        if (count($this->attachments)) {
            $this->validateAttachments($this->attachments);
        }

        $this->validateMessage();

        $message = $this->buildMessage();

        $msg = new Message;
        $msg->setRaw($this->encodeUrlSafeMessage($message));

        return $this->userMessages()->send('me', $msg);
    }

    /**
     * Builds the email message.
     */
    protected function buildMessage(): string
    {
        if ($this->fromEmail === null || $this->fromName === null || $this->toEmail === null || $this->toName === null || $this->subject === null || $this->message === null) {
            throw new GoogleApiException('Cannot build an email message before it passes validation.');
        }

        $boundary = bin2hex(random_bytes(16));

        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "To: {$this->toName} <{$this->toEmail}>\r\n";
        if (count($this->cc)) {
            $headers .= "CC: {$this->arrayToString($this->cc)}\r\n";
        }
        if (count($this->bcc)) {
            $headers .= "BCC: {$this->arrayToString($this->bcc)}\r\n";
        }
        $headers .= "Subject: {$this->subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";

        if (! empty($this->attachments)) {
            $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n\r\n";

            foreach ($this->attachments as $attachment) {
                $contents = file_get_contents($attachment);

                if ($contents === false) {
                    throw new GoogleApiException("File $attachment could not be read");
                }

                $attachmentData = chunk_split(base64_encode($contents), 76, "\r\n");

                $headers .= "--$boundary\r\n";
                $headers .= 'Content-Type: '.mime_content_type($attachment).'; name="'.basename($attachment)."\"\r\n";
                $headers .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $headers .= $attachmentData."\r\n";
            }

            $headers .= "--$boundary\r\n";
        }

        $headers .= "Content-Type: text/html; charset=utf-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $headers .= "{$this->message}\r\n\r\n";

        if (! empty($this->attachments)) {
            $headers .= "--$boundary--";
        }

        return $headers;
    }

    /**
     * Validates the email attachments.
     *
     * The maximum total size is set to 25MB, which is the limit for attachments sent through Gmail.
     *
     * @param  array<int, string>  $attachments
     *
     * @throws GoogleApiException If the attachments are invalid.
     */
    protected function validateAttachments(array $attachments): void
    {
        $totalSize = 0;
        $maxTotalSize = 25 * 1024 * 1024;

        foreach ($attachments as $attachment) {
            if (! file_exists($attachment)) {
                throw new GoogleApiException("File $attachment does not exist");
            }
            if (! is_readable($attachment)) {
                throw new GoogleApiException("File $attachment is not readable");
            }
            $totalSize += filesize($attachment);
        }
        if ($totalSize > $maxTotalSize) {
            throw new GoogleApiException('Total size of attachments exceeds the maximum size limit');
        }
    }

    /**
     * Encodes a message into a URL-safe format.
     */
    protected function encodeUrlSafeMessage(string $message): string
    {
        return rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
    }

    /**
     * Converts a list of emails to a comma separated string.
     *
     * @param  array<int, string>  $emails
     */
    protected function arrayToString(array $emails): string
    {
        return implode(', ', $emails);
    }

    /**
     * Validates the email message.
     */
    protected function validateMessage(): void
    {
        $validator = Validator::make([
            'fromEmail' => $this->fromEmail,
            'fromName' => $this->fromName,
            'toEmail' => $this->toEmail,
            'toName' => $this->toName,
            'subject' => $this->subject,
            'message' => $this->message,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
        ], [
            'fromEmail' => 'required|email',
            'fromName' => 'required',
            'toEmail' => 'required|email',
            'toName' => 'required',
            'subject' => 'required',
            'message' => 'required',
            'cc' => 'nullable|array',
            'cc.*' => 'email',
            'bcc' => 'nullable|array',
            'bcc.*' => 'email',
        ]);

        if ($validator->fails()) {
            throw new GoogleApiException($validator->errors()->first());
        }
    }

    protected function userMessages(): UsersMessages
    {
        $userMessages = $this->service->users_messages;

        if (! $userMessages instanceof UsersMessages) {
            throw new GoogleApiException('The Gmail user messages resource is unavailable.');
        }

        return $userMessages;
    }
}
