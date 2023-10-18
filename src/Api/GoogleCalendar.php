<?php

namespace TomShaw\GoogleApi\Api;

use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\Events;
use TomShaw\GoogleApi\GoogleClient;
use TomShaw\GoogleApi\Traits\WithDates;

final class GoogleCalendar
{
    use WithDates;

    protected Calendar $service;

    protected string $calendarId;

    public function __construct(
        protected GoogleClient $client
    ) {
        $client->initialize();

        $this->service = new Calendar($client->client);

        $this->calendarId = config('google-api.service.config.calendar.id');
    }

    public function listEvents(int $maxResults = 10, string $orderBy = 'startTime', bool $singleEvents = true): Events
    {
        $options = [
            'maxResults' => $maxResults,
            'orderBy' => $orderBy,
            'singleEvents' => $singleEvents,
            'timeMin' => date('c'),
        ];

        return $this->service->events->listEvents($this->calendarId, $options);
    }

    public function addEvent(mixed $summary, mixed $location, mixed $from, mixed $to, string $description = ''): Event
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

        return $this->service->events->insert($this->calendarId, $event);
    }

    public function updateEvent(mixed $eventId, mixed $summary, mixed $location, mixed $from, mixed $to, string $description = ''): Event
    {
        $event = new Event($this->service->events->get($this->calendarId, $eventId));
        $event->setSummary($summary);
        $event->setLocation($location);
        $event->setDescription($description);

        $start = new EventDateTime();
        $start->setDateTime($this->date3339($from));
        $event->setStart($start);

        $end = new EventDateTime();
        $end->setDateTime($this->date3339($to));
        $event->setEnd($end);

        return $this->service->events->update($this->calendarId, $eventId, $event);
    }

    public function deleteEvent($eventId): mixed
    {
        return $this->service->events->delete($this->calendarId, $eventId);
    }
}
