<?php

namespace TomShaw\GoogleApi;

use TomShaw\GoogleApi\Api\GoogleCalendar;
use TomShaw\GoogleApi\Api\GoogleMail;

class GoogleApiManager
{
    public function gmail(): GoogleMail
    {
        return new GoogleMail(app(GoogleClient::class));
    }

    public function calendar(): GoogleCalendar
    {
        return new GoogleCalendar(app(GoogleClient::class));
    }
}
