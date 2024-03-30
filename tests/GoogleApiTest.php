<?php

use Google\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use TomShaw\GoogleApi\Api\GoogleCalendar;
use TomShaw\GoogleApi\Api\GoogleMail;
use TomShaw\GoogleApi\GoogleApi;
use TomShaw\GoogleApi\GoogleClient;

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

    $this->client = new Client();
    $this->googleClient = new GoogleClient($this->client);
});

afterEach(function () {
    unlink(__DIR__.'/client_secrets.json');
});

it('returns null when no access token is set', function () {
    Config::set('google-api.token_storage', 'session');
    Session::forget(GoogleClient::SESSION_KEY);

    $result = $this->googleClient->getAccessToken();

    expect($result)->toBeNull();
});

it('returns session token when token is set in session', function () {
    Config::set('google-api.token_storage', 'session');
    Session::put(GoogleClient::SESSION_KEY, ['access_token' => 'test_token']);

    $result = $this->googleClient->getAccessToken();

    expect($result->toArray())->toBeArray()->and($result['access_token'])->toBe('test_token');
});

it('sets access token in session', function () {
    Config::set('google-api.token_storage', 'session');
    $this->googleClient->setAccessToken(['access_token' => 'test_token']);

    $token = Session::get(GoogleClient::SESSION_KEY);

    expect($token)->toBeArray()->and($token['access_token'])->toBe('test_token');
});

it('returns GoogleMail instance', function () {
    Session::put(GoogleClient::SESSION_KEY, [
        'access_token' => 'test_token',
        'refresh_token' => 'dummy_refresh_token',
        'expires_in' => 3600,
        'scope' => 'https://www.googleapis.com/auth/calendar',
        'token_type' => 'Bearer',
        'created' => time(),
    ]);

    $result = GoogleApi::gmail($this->googleClient);

    expect($result)->toBeInstanceOf(GoogleMail::class);
});

it('returns GoogleCalendar instance', function () {
    Session::put(GoogleClient::SESSION_KEY, [
        'access_token' => 'test_token',
        'refresh_token' => 'dummy_refresh_token',
        'expires_in' => 3600,
        'scope' => 'https://www.googleapis.com/auth/calendar',
        'token_type' => 'Bearer',
        'created' => time(),
    ]);

    $result = GoogleApi::calendar($this->googleClient);

    expect($result)->toBeInstanceOf(GoogleCalendar::class);
});
