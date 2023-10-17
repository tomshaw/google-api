<?php

namespace TomShaw\GoogleApi\Api;

use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use TomShaw\GoogleApi\GoogleClient;
use TomShaw\GoogleApi\Traits\WithDates;

final class GoogleCalendar
{
    use WithDates;

    protected Calendar $service;

    public function __construct(GoogleClient $client)
    {
        if (! $client->getAccessToken()) {
            $client->createAuthUrl();
        }

        $this->service = new Calendar($client->client);
    }

    public function listEvents(int $maxResults = 10, string $orderBy = 'startTime', bool $singleEvents = true)
    {
        $calendarId = config('google-api-service.calendar.owner.email');

        $options = [
            'maxResults' => $maxResults,
            'orderBy' => $orderBy,
            'singleEvents' => $singleEvents,
            'timeMin' => date('c'),
        ];

        $result = $this->service->events->listEvents($calendarId, $options);

        return $result->getItems();
    }

    public function addEvent($summary, $location, $from, $to, $description = '')
    {
        $event = new Event();
        $event->setSummary($summary);
        $event->setLocation($location);
        $event->setDescription($description);

        $start = new EventDateTime();
        $start->setDateTime($this->date3339($from));
        $event->setStart($start);

        $end = new EventDateTime();
        $end->setDateTime($this->date3339($to));
        $event->setEnd($end);

        $calendarId = config('google-api-service.calendar.owner.email');

        $insert = $this->service->events->insert($calendarId, $event);

        return $insert->id;
    }

    public function updateEvent($eventId, $summary, $location, $from, $to, $description = '')
    {
        $calendarId = config('google-api-service.calendar.owner.email');

        $event = new Event($this->service->events->get($calendarId, $eventId));
        $event->setSummary($summary);
        $event->setLocation($location);
        $event->setDescription($description);

        $start = new EventDateTime();
        $start->setDateTime($this->date3339($from));
        $event->setStart($start);

        $end = new EventDateTime();
        $end->setDateTime($this->date3339($to));
        $event->setEnd($end);

        $update = $this->service->events->update($calendarId, $eventId, $event);

        return $update;
    }

    public function deleteEvent($eventId)
    {
        $calendarId = config('google-api-service.calendar.owner.email');

        $this->service->events->delete($calendarId, $eventId);

        return $this;
    }
}
