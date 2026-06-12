<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi;

use Google\Service;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Traits\Macroable;
use TomShaw\GoogleApi\Resources\GoogleBooks;
use TomShaw\GoogleApi\Resources\GoogleCalendar;
use TomShaw\GoogleApi\Resources\GoogleDrive;
use TomShaw\GoogleApi\Resources\GoogleMail;

class GoogleApiManager
{
    use Macroable;

    protected ?GoogleClient $client = null;

    /**
     * Return a manager whose token storage is scoped to the given user.
     */
    public function forUser(Authenticatable|int|string $user): static
    {
        $manager = clone $this;
        $manager->client = $this->client()->forUser($user);

        return $manager;
    }

    public function books(): GoogleBooks
    {
        return new GoogleBooks($this->client());
    }

    public function drive(): GoogleDrive
    {
        return new GoogleDrive($this->client());
    }

    public function gmail(): GoogleMail
    {
        return new GoogleMail($this->client());
    }

    public function calendar(): GoogleCalendar
    {
        return new GoogleCalendar($this->client());
    }

    /**
     * Instantiate any Google service class with the authorized client.
     *
     * @template TService of Service
     *
     * @param  class-string<TService>  $service
     * @return TService
     */
    public function service(string $service): Service
    {
        return new $service(($this->client())());
    }

    protected function client(): GoogleClient
    {
        return $this->client ?? app(GoogleClient::class);
    }
}
