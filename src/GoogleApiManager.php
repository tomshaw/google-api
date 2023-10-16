<?php

namespace TomShaw\GoogleApi;

use TomShaw\GoogleApi\Api\GoogleCalendar;
use TomShaw\GoogleApi\Api\GoogleMail;

class GoogleApiManager
{
    public function gmail(GoogleClient $client): GoogleMail
    {
        return new GoogleMail($client);
    }

    public function calendar(GoogleClient $client): GoogleCalendar
    {
        return new GoogleCalendar($client);
    }
}
