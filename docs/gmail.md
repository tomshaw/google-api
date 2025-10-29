# Google Mail (Gmail) Service

The Google Mail service adapter provides a fluent interface for sending emails through Gmail API with support for attachments, CC, BCC, and Laravel Mailables.

## Setup

First, ensure you have authorized your application with Gmail scopes in your `config/google-api.php`:

```php
'service_scopes' => [
    Google\Service\Gmail::GMAIL_SEND,
],
```

## Initialization

```php
use TomShaw\GoogleApi\Facades\GoogleApi;

$gmail = GoogleApi::gmail();
```

## Available Methods

### Setting Sender Information

#### from(string $email, string $name)

Sets both the sender's email and name.

```php
$gmail->from('sender@example.com', 'John Doe');
```

#### setFromEmail(string $fromEmail)

Sets only the sender's email address.

```php
$gmail->setFromEmail('sender@example.com');
```

#### setFromName(string $fromName)

Sets only the sender's name.

```php
$gmail->setFromName('John Doe');
```

### Setting Recipient Information

#### to(string $email, string $name)

Sets both the recipient's email and name.

```php
$gmail->to('recipient@example.com', 'Jane Smith');
```

#### setToEmail(string $toEmail)

Sets only the recipient's email address.

```php
$gmail->setToEmail('recipient@example.com');
```

#### setToName(string $toName)

Sets only the recipient's name.

```php
$gmail->setToName('Jane Smith');
```

### Setting CC and BCC

#### cc(string|array $email)

Adds carbon copy recipient(s).

```php
// Single recipient
$gmail->cc('cc@example.com');

// Multiple recipients
$gmail->cc(['cc1@example.com', 'cc2@example.com']);
```

#### bcc(string|array $email)

Adds blind carbon copy recipient(s).

```php
// Single recipient
$gmail->bcc('bcc@example.com');

// Multiple recipients
$gmail->bcc(['bcc1@example.com', 'bcc2@example.com']);
```

### Setting Message Content

#### subject(string $subject)

Sets the email subject.

```php
$gmail->subject('Welcome to our service');
```

#### message(string $message)

Sets the email body content (supports HTML).

```php
$gmail->message('<h1>Welcome!</h1><p>Thank you for signing up.</p>');
```

#### mailable(Mailable $mailable)

Sets the message content using a Laravel Mailable.

```php
use App\Mail\OrderConfirmation;

$gmail->mailable(new OrderConfirmation($order));
```

### Attachments

#### attachment(string $path)

Adds a single file attachment.

```php
$gmail->attachment(storage_path('app/public/invoice.pdf'));
```

#### attachments(array $paths)

Adds multiple file attachments.

```php
$attachments = [
    storage_path('app/public/invoice.pdf'),
    storage_path('app/public/receipt.pdf'),
];

$gmail->attachments($attachments);
```

**Note:** Maximum total attachment size is 25MB (Gmail limit).

### Sending Email

#### send()

Sends the email and returns the message object.

**Returns:** `Google\Service\Gmail\Message`

```php
$message = $gmail->send();
```

## Basic Example

```php
use TomShaw\GoogleApi\Facades\GoogleApi;

$gmail = GoogleApi::gmail();

$gmail->from('sender@example.com', 'Company Name')
    ->to('customer@example.com', 'Customer Name')
    ->subject('Order Confirmation')
    ->message('<h1>Thank you for your order!</h1>')
    ->send();
```

## Using Laravel Mailables

```php
use TomShaw\GoogleApi\Facades\GoogleApi;
use App\Mail\OrderMailable;

$order = Order::find($orderId);

$gmail = GoogleApi::gmail();
$gmail->from('sales@example.com', 'Sales Team');
$gmail->to($order->user->email, $order->user->name);
$gmail->cc('manager@example.com');
$gmail->subject('Your Order #' . $order->id);
$gmail->mailable(new OrderMailable($order));
$gmail->send();
```

## With Attachments

```php
use TomShaw\GoogleApi\Facades\GoogleApi;

$attachments = [
    storage_path('app/invoices/invoice-123.pdf'),
    storage_path('app/receipts/receipt-123.pdf'),
];

$gmail = GoogleApi::gmail();
$gmail->from('billing@example.com', 'Billing Department');
$gmail->to('customer@example.com', 'Customer Name');
$gmail->subject('Your Invoice and Receipt');
$gmail->message('<p>Please find attached your invoice and receipt.</p>');
$gmail->attachments($attachments);
$gmail->send();
```

## Complete Example with Error Handling

```php
use TomShaw\GoogleApi\Facades\GoogleApi;
use TomShaw\GoogleApi\Exceptions\GoogleApiException;
use App\Mail\WelcomeEmail;

class EmailController extends Controller
{
    public function sendWelcome(User $user)
    {
        try {
            $gmail = GoogleApi::gmail();

            $gmail->from(config('mail.from.address'), config('mail.from.name'))
                ->to($user->email, $user->name)
                ->bcc('admin@example.com')
                ->subject('Welcome to ' . config('app.name'))
                ->mailable(new WelcomeEmail($user))
                ->send();

            return response()->json(['message' => 'Email sent successfully']);
        } catch (GoogleApiException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send email'], 500);
        }
    }

    public function sendInvoiceEmail(Order $order)
    {
        $attachments = [
            storage_path("app/invoices/invoice-{$order->id}.pdf"),
        ];

        try {
            $gmail = GoogleApi::gmail();

            $gmail->from('billing@example.com', 'Billing Department')
                ->to($order->user->email, $order->user->name)
                ->cc('sales@example.com')
                ->subject("Invoice for Order #{$order->id}")
                ->message($this->buildInvoiceEmailBody($order))
                ->attachments($attachments)
                ->send();

            $order->update(['invoice_sent_at' => now()]);

            return response()->json(['message' => 'Invoice sent']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function buildInvoiceEmailBody(Order $order): string
    {
        return "
            <h2>Invoice for Order #{$order->id}</h2>
            <p>Dear {$order->user->name},</p>
            <p>Please find attached your invoice for order #{$order->id}.</p>
            <p>Total Amount: \${$order->total}</p>
            <p>Thank you for your business!</p>
        ";
    }
}
```

## Validation

The service automatically validates:
- Required fields (from, to, subject, message)
- Email address formats
- CC and BCC email formats
- Attachment file existence and readability
- Total attachment size (25MB limit)

Validation errors throw `GoogleApiException` with descriptive messages.

## Notes

- All emails are sent as HTML by default
- The sender email must be authorized in your Google account
- Attachments are automatically base64 encoded
- Multiple CC and BCC recipients are supported
- Laravel Mailables are rendered before sending
