# GoogleApi ☁️

![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/tomshaw/google-api/run-tests.yml?branch=master&style=flat-square&label=tests)
![issues](https://img.shields.io/github/issues/tomshaw/google-api?style=flat&logo=appveyor)
![forks](https://img.shields.io/github/forks/tomshaw/google-api?style=flat&logo=appveyor)
![stars](https://img.shields.io/github/stars/tomshaw/google-api?style=flat&logo=appveyor)
[![GitHub license](https://img.shields.io/github/license/tomshaw/google-api)](https://github.com/tomshaw/google-api/blob/master/LICENSE)

A Laravel Service Google API Client.

## Installation

You can install the package via composer:

```bash
composer require tomshaw/google-api
```

If you're facing a timeout error then increase the timeout for composer:

```json
{
    "config": {
        "process-timeout": 600
    }
}
```

Next publish the configuration file:

```
php artisan vendor:publish --provider="TomShaw\GoogleApi\Providers\GoogleApiServiceProvider" --tag=config
```

Run the migration if you wish to use database token storage adapter:

```
php artisan migrate
```

To avoid shipping all 200 Google API's you should specify the services you wish to use in your `composer.json`:

```json
{
    "scripts": {
        "pre-autoload-dump": "Google\\Task\\Composer::cleanup"
    },
    "extra": {
        "google/apiclient-services": [
            "Gmail",
            "Calendar"
        ]
    }
}
```

## Configuration

Here's a brief explanation of the application configuration file used to set up a Google API client:

- `token_storage_adapter`: Sets the default token storage adapter to use. Developers can implement their own custom solution.

- `auth_config`: This is the path to the JSON file that contains your Google API client credentials. 

- `application_name`: This is the name of your application.

- `prompt`: This is the type of prompt that will be presented to the user during the OAuth2.0 flow. The 'consent' prompt asks the user to grant your application access to the scopes you're requesting.

- `approval_prompt`: This is another setting for the OAuth2.0 flow. The 'auto' setting means that the user will only be prompted for approval the first time they authenticate your application.

- `access_type`: This is set to 'offline' to allow your application to access the user's data when the user is not present.

- `include_grant_scopes`: This is set to true to include the scopes from the initial authorization in the refresh token request.

- `service_scopes`: These are the scopes your application is requesting access to.

The Google API client uses these settings to handle the OAuth2.0 flow and interact with the Google APIs.

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

## Storage Adapters

You can provide your own storage mechanism such as file or Redis by setting the `token_storage_adapter` configuration option.

> Storage adapters must implement the `StorageAdapterInterface`.

```php
'token_storage_adapter' => TomShaw\GoogleApi\Storage\DatabaseTokenStorage::class,
```

## Services Adapters

This packages includes a Google `Calendar` and `Gmail` adapter classes. Feel free to send a pull request if you would like to add your own. 

> Using the client to create calendar events.

```php
    public function add(GoogleClient $client)
    {
        $calendar = GoogleApi::calendar($client);

        $calendar->setCalendarId('email@example.com');

        $summary = 'Test Event';
        $location = '123 Test St, Test City, TS';
        $description = 'This is a test event.';

        $from = Carbon::now()->timezone('America/Chicago')->addDay()->startOfDay()->addHours(13);
        $to = $from->copy()->addHour();

        $event = $calendar->addEvent($summary, $location, $from, $to, $description);

        // save event id for further use.
    }
```

> Using the client to send emails.

```php
    public function mount(GoogleClient $client)
    {
        $model = Order::with(['user'])->where('id', $orderId)->first();

        $gmail = GoogleApi::gmail($client);
        $gmail->from('email@example.com', 'Company Name');
        $gmail->to($model->user->email, $model->user->name);
        $gmail->subject('Thank you for your order.');
        $gmail->mailable(new OrderMailable($model, 'template-name'));
        $gmail->send();
    }
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). See [License File](LICENSE) for more information.
