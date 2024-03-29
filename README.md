# GoogleApi ☁️

![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/tomshaw/google-api/run-tests.yml?branch=master&style=flat-square&label=tests)
![issues](https://img.shields.io/github/issues/tomshaw/google-api?style=flat&logo=appveyor)
![forks](https://img.shields.io/github/forks/tomshaw/google-api?style=flat&logo=appveyor)
![stars](https://img.shields.io/github/stars/tomshaw/google-api?style=flat&logo=appveyor)
[![GitHub license](https://img.shields.io/github/license/tomshaw/google-api)](https://github.com/tomshaw/google-api/blob/master/LICENSE)

A simple to use Laravel Google API Client.

## Installation

You can install the package via composer:

```bash
composer require tomshaw/google-api
```

Publish configuration files

```
php artisan vendor:publish --provider="TomShaw\GoogleApi\Providers\GoogleApiServiceProvider" --tag=config
```

Run the migration if you wish to use database token storage

```
php artisan migrate
```

## Basic Usage

Authorizing the application and persisting the token.

> Note: Google APIs must have OAuth 2.0 authorization credentials downloaded from the [Google Developer Console](https://console.cloud.google.com/apis). See the application configuration to specify the downloaded location of your credentials. 

```php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use TomShaw\GoogleApi\GoogleClient;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    public function index(GoogleClient $client)
    {
        return $client->createAuthUrl();
    }

    public function callback(Request $request, GoogleClient $client)
    {
        $authCode = $request->get('code');

        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        if ($accessToken) {
            $client->setAccessToken($accessToken);
        }

        return redirect()->route('homepage');
    }
}
```

Using the client to fetch calendar events.

```php
    public function mount(GoogleClient $client)
    {
        $calendar = GoogleApi::calendar($client)->listEvents();
        $events = $calendar->getItems();
    }
```

Using the client to send emails.

> Note: The following example uses a Mailable class that renders a blade template. 

```php
    public function mount(GoogleClient $client)
    {
        $model = Order::with(['user'])->where('id', $orderId)->first();

        $mailer = GoogleApi::gmail($client);
        $mailer->to($model->user->email, $model->user->name);
        $mailer->subject('Thank you for your order.');
        $mailer->mailable(new OrderMailable($model, 'template-name'));
        $mailer->send();
    }
```

## Unused services

There are over 200 Google API's. To avoid shipping these dependencies you should specify the services you want to use in your `composer.json`:

```json
{
    "scripts": {
        "pre-autoload-dump": "Google\\Task\\Composer::cleanup"
    },
    "extra": {
        "google/apiclient-services": [
            "Drive",
            "YouTube"
        ]
    }
}
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). See [License File](LICENSE) for more information.
