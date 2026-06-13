# GoogleApi ☁️

[![tests](https://github.com/tomshaw/google-api/actions/workflows/run-tests.yml/badge.svg?branch=master)](https://github.com/tomshaw/google-api/actions/workflows/run-tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/tomshaw/google-api?style=flat&cacheSeconds=3600)](https://packagist.org/packages/tomshaw/google-api)
[![Total Downloads](https://img.shields.io/packagist/dt/tomshaw/google-api?style=flat&cacheSeconds=3600)](https://packagist.org/packages/tomshaw/google-api)
[![PHP Version](https://img.shields.io/packagist/dependency-v/tomshaw/google-api/php?style=flat&cacheSeconds=3600)](https://packagist.org/packages/tomshaw/google-api)
[![License](https://img.shields.io/packagist/l/tomshaw/google-api?style=flat&cacheSeconds=3600)](https://github.com/tomshaw/google-api/blob/master/LICENSE)

A Google OAuth 2.0 Laravel Service Client.

## Requirements

- PHP ^8.5
- Laravel ^13.0

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

- `prompt`: This is the type of prompt that will be presented to the user during the OAuth2.0 flow. The `Prompt::Consent` prompt asks the user to grant your application access to the scopes you're requesting.

- `approval_prompt`: This is another setting for the OAuth2.0 flow. The `ApprovalPrompt::Auto` setting means that the user will only be prompted for approval the first time they authenticate your application.

- `access_type`: This is set to `AccessType::Offline` to allow your application to access the user's data when the user is not present.

- `include_grant_scopes`: This is set to true to include the scopes from the initial authorization in the refresh token request.

- `service_scopes`: These are the scopes your application is requesting access to.

The `prompt`, `approval_prompt`, and `access_type` options accept the `TomShaw\GoogleApi\Enums\Prompt`, `ApprovalPrompt`, and `AccessType` enums (plain strings still work).

The Google API client uses these settings to handle the OAuth2.0 flow and interact with the Google APIs.

## Basic Usage

Authorizing the application and persisting the token.

> Google APIs must have OAuth 2.0 authorization credentials downloaded from the [Google Developer Console](https://console.cloud.google.com/apis). See the application configuration to specify the location of your credentials. 

```php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use TomShaw\GoogleApi\GoogleClient;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    public function index(GoogleClient $client)
    {
        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request, GoogleClient $client)
    {
        $accessToken = $client->fetchAccessTokenWithAuthCode($request->get('code'));

        $client->setAccessToken($accessToken);

        return redirect()->route('homepage');
    }
}
```

Once a token is stored, resolve any service through the `GoogleApi` facade. If no token is present a `TokenNotFoundException` is thrown carrying the authorization URL, so unauthorized users can be redirected into the OAuth flow:

```php
use TomShaw\GoogleApi\GoogleApi;
use TomShaw\GoogleApi\Exceptions\TokenNotFoundException;

try {
    $events = GoogleApi::calendar()->listEvents();
} catch (TokenNotFoundException $e) {
    return redirect()->away($e->authUrl);
}
```

### Access Tokens

`GoogleClient::getAccessToken()` returns a readonly `TomShaw\GoogleApi\AccessToken` value object (or `null` when no token is stored):

```php
$token = $client->getAccessToken();

$token->accessToken;        // string|null
$token->isExpired();        // bool
$token->hasRefreshToken();  // bool
$token->toArray();          // array<string, int|string>
```

### Acting on Behalf of a User

When using a user-scoped storage adapter such as the database adapter, tokens can be read and written for a specific user — useful in queued jobs and console commands where no one is authenticated:

```php
use TomShaw\GoogleApi\GoogleApi;

GoogleApi::forUser($user)
    ->gmail()
    ->from('billing@example.com', 'Billing')
    ->to($user->email, $user->name)
    ->subject('Your invoice')
    ->message('<p>Thanks!</p>')
    ->send();
```

### Using Any Google Service

The four bundled adapters cover Calendar, Gmail, Drive, and Books. Any other Google service can be instantiated with the authorized client:

```php
use Google\Service\Sheets;
use TomShaw\GoogleApi\GoogleApi;

$sheets = GoogleApi::service(Sheets::class);
```

## Storage Adapters

You can provide your own storage mechanism such as file or Redis by setting the `token_storage_adapter` configuration option.

> Storage adapters must implement the `StorageAdapterInterface`. Implement `UserScopedStorageAdapter` as well if your adapter should support `forUser()`.

```php
'token_storage_adapter' => TomShaw\GoogleApi\Storage\DatabaseStorageAdapter::class,
```

Two adapters ship with the package:

- `DatabaseStorageAdapter` (default) - persists tokens to the `google_tokens` table keyed by user, with the token columns encrypted at rest. Supports `forUser()`.
- `SessionStorageAdapter` - keeps the token in the current session.

## Services Adapters

This package includes example service adapters for Google Calendar, Gmail, Drive, and Books. Each adapter provides a fluent interface for interacting with the respective Google API.

- **[Google Calendar](docs/calendar.md)** - Manage calendar events (create, read, update, delete)
- **[Gmail](docs/gmail.md)** - Send emails with attachments, CC, BCC, and Laravel Mailable support
- **[Google Drive](docs/drive.md)** - List, retrieve, and upload files to Google Drive
- **[Google Books](docs/books.md)** - Search and retrieve book information from Google Books

## Changelog

For changes made to the project, see the [Changelog](CHANGELOG.md).

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). See [License File](LICENSE) for more information.
