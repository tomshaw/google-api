<?php

use Google\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use TomShaw\GoogleApi\Resources\GoogleCalendar;
use TomShaw\GoogleApi\Resources\GoogleMail;
use TomShaw\GoogleApi\GoogleApi;
use TomShaw\GoogleApi\GoogleClient;
use TomShaw\GoogleApi\Storage\SessionStorageAdapter;
use TomShaw\GoogleApi\Storage\StorageAdapterInterface;

test('instance check', function () {
    $this->assertTrue($this instanceof \PHPUnit\Framework\TestCase);
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

    $this->client = new GoogleClient(new Client());
});

afterEach(function () {
    unlink(__DIR__.'/client_secrets.json');
});

it('returns null when no access token is set', function () {
    $this->client->deleteAccessToken();

    $result = $this->client->getAccessToken();

    expect($result->isEmpty())->toBeTrue();
});

it('returns access token when token is set', function () {
    $this->client->setAccessToken(['access_token' => 'test_token']);

    $result = $this->client->getAccessToken()->toArray();

    expect($result)->toBeArray()->and($result['access_token'])->toBe('test_token');
});

it('sets and gets the storage adapter correctly', function () {
    $mockStorageAdapter = \Mockery::mock(StorageAdapterInterface::class);

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
