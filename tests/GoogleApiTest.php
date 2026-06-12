<?php

declare(strict_types=1);

use Google\Service\Books;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\TestCase;
use TomShaw\GoogleApi\AccessToken;
use TomShaw\GoogleApi\Exceptions\GoogleClientException;
use TomShaw\GoogleApi\Exceptions\TokenNotFoundException;
use TomShaw\GoogleApi\GoogleApi;
use TomShaw\GoogleApi\GoogleClient;
use TomShaw\GoogleApi\Resources\GoogleCalendar;
use TomShaw\GoogleApi\Resources\GoogleMail;
use TomShaw\GoogleApi\Storage\DatabaseStorageAdapter;
use TomShaw\GoogleApi\Storage\SessionStorageAdapter;
use TomShaw\GoogleApi\Storage\StorageAdapterInterface;

test('instance check', function () {
    $this->assertTrue($this instanceof TestCase);
});

beforeEach(function () {
    file_put_contents(__DIR__.'/client_secrets.json', json_encode([
        'web' => [
            'client_id' => 'dummy_client_id',
            'client_secret' => 'dummy_client_secret',
            'redirect_uris' => ['http://localhost'],
        ],
    ]));

    Config::set('google-api', require realpath(__DIR__.DIRECTORY_SEPARATOR.'Mock'.DIRECTORY_SEPARATOR.'config.php'));

    $this->client = GoogleClient::make();
});

afterEach(function () {
    unlink(__DIR__.'/client_secrets.json');
});

it('returns null when no access token is set', function () {
    $this->client->deleteAccessToken();

    expect($this->client->getAccessToken())->toBeNull()
        ->and($this->client->isEmpty())->toBeTrue();
});

it('returns access token when token is set', function () {
    $this->client->setAccessToken(['access_token' => 'test_token']);

    $result = $this->client->getAccessToken();

    expect($result)->toBeInstanceOf(AccessToken::class)
        ->and($result->accessToken)->toBe('test_token')
        ->and($result->toArray())->toBe(['access_token' => 'test_token']);
});

it('sets and gets the storage adapter correctly', function () {
    $mockStorageAdapter = Mockery::mock(StorageAdapterInterface::class);

    $this->client->setStorage($mockStorageAdapter);

    $result = $this->client->getStorage();

    expect($result)->toBe($mockStorageAdapter);
});

it('returns GoogleMail instance', function () {

    Session::put(SessionStorageAdapter::SESSION_KEY, [
        'access_token' => 'test_token',
        'refresh_token' => 'dummy_refresh_token',
        'expires_in' => 3600,
        'scope' => 'https://www.googleapis.com/auth/calendar',
        'token_type' => 'Bearer',
        'created' => time(),
    ]);

    $result = GoogleApi::gmail();

    expect($result)->toBeInstanceOf(GoogleMail::class);
});

it('returns GoogleCalendar instance', function () {

    Session::put(SessionStorageAdapter::SESSION_KEY, [
        'access_token' => 'test_token',
        'refresh_token' => 'dummy_refresh_token',
        'expires_in' => 3600,
        'scope' => 'https://www.googleapis.com/auth/calendar',
        'token_type' => 'Bearer',
        'created' => time(),
    ]);

    $result = GoogleApi::calendar();

    expect($result)->toBeInstanceOf(GoogleCalendar::class);
});

it('returns the authorization url as a string', function () {
    $url = $this->client->createAuthUrl();

    expect($url)->toBeString()->toContain('accounts.google.com');
});

it('throws a token not found exception carrying the auth url when invoked without a token', function () {
    $this->client->deleteAccessToken();

    try {
        ($this->client)();

        $this->fail('Expected TokenNotFoundException was not thrown.');
    } catch (TokenNotFoundException $e) {
        expect($e->authUrl)->toBeString()->toContain('accounts.google.com');
    }
});

it('throws when validating an incomplete token payload', function () {
    $this->client->validate(['access_token' => 'abc']);
})->throws(GoogleClientException::class);

it('builds mail fluently through public properties', function () {
    Session::put(SessionStorageAdapter::SESSION_KEY, [
        'access_token' => 'test_token',
        'refresh_token' => 'dummy_refresh_token',
        'expires_in' => 3600,
        'scope' => 'https://www.googleapis.com/auth/gmail.send',
        'token_type' => 'Bearer',
        'created' => time(),
    ]);

    $mail = GoogleApi::gmail()
        ->from('from@example.com', 'From Name')
        ->to('to@example.com', 'To Name')
        ->cc([' one@example.com '])
        ->bcc('two@example.com')
        ->subject('Hello')
        ->message('<p>Hi</p>')
        ->attachment('/tmp/a.txt')
        ->attachments(['/tmp/b.txt']);

    expect($mail->fromEmail)->toBe('from@example.com')
        ->and($mail->toName)->toBe('To Name')
        ->and($mail->cc)->toBe(['one@example.com'])
        ->and($mail->bcc)->toBe(['two@example.com'])
        ->and($mail->subject)->toBe('Hello')
        ->and($mail->attachments)->toBe(['/tmp/a.txt', '/tmp/b.txt']);
});

it('builds an access token from an array and back', function () {
    $token = AccessToken::fromArray([
        'access_token' => 'abc',
        'refresh_token' => 'def',
        'expires_in' => '3600',
        'scope' => 'scope',
        'token_type' => 'Bearer',
        'created' => 100,
    ]);

    expect($token->expiresIn)->toBe(3600)
        ->and($token->created)->toBe(100)
        ->and($token->hasRefreshToken())->toBeTrue()
        ->and($token->toArray())->toBe([
            'access_token' => 'abc',
            'refresh_token' => 'def',
            'expires_in' => 3600,
            'scope' => 'scope',
            'token_type' => 'Bearer',
            'created' => 100,
        ]);
});

it('detects access token expiry', function () {
    $expired = new AccessToken(accessToken: 'abc', expiresIn: 3600, created: time() - 7200);
    $active = new AccessToken(accessToken: 'abc', expiresIn: 3600, created: time());
    $unknown = new AccessToken(accessToken: 'abc');

    expect($expired->isExpired())->toBeTrue()
        ->and($active->isExpired())->toBeFalse()
        ->and($unknown->isExpired())->toBeTrue()
        ->and($unknown->hasRefreshToken())->toBeFalse();
});

it('returns any google service instance via the manager', function () {
    Session::put(SessionStorageAdapter::SESSION_KEY, [
        'access_token' => 'test_token',
        'refresh_token' => 'dummy_refresh_token',
        'expires_in' => 3600,
        'scope' => 'https://www.googleapis.com/auth/books',
        'token_type' => 'Bearer',
        'created' => time(),
    ]);

    $result = GoogleApi::service(Books::class);

    expect($result)->toBeInstanceOf(Books::class);
});

it('throws when the storage adapter does not support user scoping', function () {
    $this->client->forUser(1);
})->throws(GoogleClientException::class, 'does not support user scoping');

it('scopes the database storage adapter to a given user', function () {
    config()->set('database.connections.testing.foreign_key_constraints', false);

    $this->artisan('migrate', ['--database' => 'testing'])->run();

    $token = [
        'access_token' => 'user-42-token',
        'refresh_token' => 'refresh',
        'expires_in' => 3600,
        'scope' => 'scope',
        'token_type' => 'Bearer',
        'created' => time(),
    ];

    (new DatabaseStorageAdapter)->forUser(42)->set($token);

    expect((new DatabaseStorageAdapter)->forUser(7)->get())->toBeNull();

    $stored = (new DatabaseStorageAdapter)->forUser(42)->get();

    expect($stored)->not->toBeNull()
        ->and($stored->access_token)->toBe('user-42-token')
        ->and($stored->expires_in)->toBe(3600);
});
