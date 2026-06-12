<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi\Resources;

use Google\Http\MediaFileUpload;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Google\Service\Gmail\Resource\UsersMessages;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Validator;
use Psr\Http\Message\RequestInterface;
use TomShaw\GoogleApi\Exceptions\GoogleApiException;
use TomShaw\GoogleApi\GoogleClient;

final class GoogleMail
{
    /**
     * Attachment totals above this threshold are sent via resumable
     * media upload instead of an inline base64url-encoded payload.
     */
    private const int STREAM_UPLOAD_THRESHOLD = 5 * 1024 * 1024;

    /**
     * Resumable upload chunk size; must be a multiple of 256 KiB.
     */
    private const int UPLOAD_CHUNK_SIZE = 4 * 256 * 1024;

    /**
     * 57 raw bytes encode to one 76-character base64 line (RFC 2045),
     * so reads in multiples of 57 keep lines aligned across chunks.
     */
    private const int ATTACHMENT_READ_BYTES = 57 * 1024;

    /**
     * Bytes the MIME temp stream may hold in memory before spilling to disk.
     */
    private const int STREAM_MEMORY_LIMIT = 2 * 1024 * 1024;

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
     *
     * Messages whose attachments exceed the streaming threshold are uploaded
     * with the Gmail resumable media upload; everything else is sent inline.
     */
    public function send(): Message
    {
        if (count($this->attachments)) {
            $this->validateAttachments($this->attachments);
        }

        $this->validateMessage();

        $stream = $this->buildMimeStream();

        try {
            if ($this->usesStreamedUpload()) {
                return $this->sendStreamed($stream);
            }

            return $this->sendInline($stream);
        } finally {
            fclose($stream);
        }
    }

    /**
     * Builds the email MIME message into a temporary stream, spilling to
     * disk once it outgrows the in-memory limit.
     *
     * @return resource
     */
    protected function buildMimeStream()
    {
        if ($this->fromEmail === null || $this->fromName === null || $this->toEmail === null || $this->toName === null || $this->subject === null || $this->message === null) {
            throw new GoogleApiException('Cannot build an email message before it passes validation.');
        }

        $stream = fopen('php://temp/maxmemory:'.self::STREAM_MEMORY_LIMIT, 'r+b');

        if ($stream === false) {
            throw new GoogleApiException('Unable to open a temporary stream for the email message.');
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

        fwrite($stream, $headers);

        if (! empty($this->attachments)) {
            fwrite($stream, "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n\r\n");

            foreach ($this->attachments as $attachment) {
                fwrite($stream, "--$boundary\r\n");
                fwrite($stream, 'Content-Type: '.mime_content_type($attachment).'; name="'.basename($attachment)."\"\r\n");
                fwrite($stream, "Content-Transfer-Encoding: base64\r\n\r\n");
                $this->writeBase64File($stream, $attachment);
                fwrite($stream, "\r\n");
            }

            fwrite($stream, "--$boundary\r\n");
        }

        fwrite($stream, "Content-Type: text/html; charset=utf-8\r\n");
        fwrite($stream, "Content-Transfer-Encoding: 8bit\r\n\r\n");
        fwrite($stream, "{$this->message}\r\n\r\n");

        if (! empty($this->attachments)) {
            fwrite($stream, "--$boundary--");
        }

        return $stream;
    }

    /**
     * Base64-encodes a file onto the MIME stream in line-aligned chunks.
     *
     * @param  resource  $stream
     */
    protected function writeBase64File($stream, string $path): void
    {
        $file = fopen($path, 'rb');

        if ($file === false) {
            throw new GoogleApiException("File $path could not be read");
        }

        try {
            while (! feof($file)) {
                $chunk = fread($file, self::ATTACHMENT_READ_BYTES);

                if ($chunk === false) {
                    throw new GoogleApiException("File $path could not be read");
                }

                if ($chunk === '') {
                    continue;
                }

                fwrite($stream, chunk_split(base64_encode($chunk), 76, "\r\n"));
            }
        } finally {
            fclose($file);
        }
    }

    /**
     * Sends the MIME stream as an inline base64url-encoded payload.
     *
     * @param  resource  $stream
     */
    protected function sendInline($stream): Message
    {
        rewind($stream);

        $contents = stream_get_contents($stream);

        if ($contents === false) {
            throw new GoogleApiException('Unable to read the email message stream.');
        }

        $msg = new Message;
        $msg->setRaw($this->encodeUrlSafeMessage($contents));

        return $this->userMessages()->send('me', $msg);
    }

    /**
     * Sends the MIME stream through the Gmail resumable media upload,
     * keeping at most one chunk in memory at a time.
     *
     * @param  resource  $stream
     */
    protected function sendStreamed($stream): Message
    {
        $client = $this->service->getClient();

        $client->setDefer(true);

        try {
            $request = $this->userMessages()->call('send', [['userId' => 'me', 'postBody' => new Message]], Message::class);

            if (! $request instanceof RequestInterface) {
                throw new GoogleApiException('Expected a deferred request for the streamed email upload.');
            }

            $media = new MediaFileUpload($client, $request, 'message/rfc822', '', true, self::UPLOAD_CHUNK_SIZE);
            $media->setFileSize($this->streamSize($stream));

            rewind($stream);

            $status = false;

            while ($status === false && ! feof($stream)) {
                $chunk = fread($stream, self::UPLOAD_CHUNK_SIZE);

                if ($chunk === false) {
                    throw new GoogleApiException('Unable to read the email message stream.');
                }

                $status = $media->nextChunk($chunk);
            }

            if (! $status instanceof Message) {
                throw new GoogleApiException('The streamed email upload did not complete.');
            }

            return $status;
        } finally {
            $client->setDefer(false);
        }
    }

    /**
     * Whether the attachments are large enough to warrant a streamed upload.
     */
    protected function usesStreamedUpload(): bool
    {
        return $this->attachmentsTotalSize() > self::STREAM_UPLOAD_THRESHOLD;
    }

    protected function attachmentsTotalSize(): int
    {
        $total = 0;

        foreach ($this->attachments as $attachment) {
            $size = filesize($attachment);

            if ($size !== false) {
                $total += $size;
            }
        }

        return $total;
    }

    /**
     * @param  resource  $stream
     */
    protected function streamSize($stream): int
    {
        $stats = fstat($stream);

        if ($stats === false) {
            throw new GoogleApiException('Unable to determine the email message size.');
        }

        return $stats['size'];
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
