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

    public function __construct(protected GoogleClient $client)
    {
        $this->service = new Calendar($client());
    }

    /**
     * Sets the calendar ID.
     *
     * @param  string  $calendarId  The calendar ID to set.
     * @return GoogleCalendar The current instance.
     */
    public function setCalendarId(string $calendarId): GoogleCalendar
    {
        $this->calendarId = $calendarId;

        return $this;
    }

    /**
     * Gets the calendar ID.
     *
     * @return string The calendar ID.
     */
    public function getCalendarId(): string
    {
        return $this->calendarId;
    }

    /**
     * Lists events from a calendar.
     *
     * @param  int  $maxResults  The maximum number of events to return. Default is 10.
     * @param  string  $orderBy  The order of the events returned in the result. Default is 'startTime'.
     * @param  bool  $singleEvents  Whether to expand recurring events into instances and only return single one-off events and instances of recurring events. Default is true.
     * @return Events Returns an Events object containing the list of events.
     *
     * @throws \Google\Exception Throws a Google Exception if the API request fails.
     */
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

    /**
     * Adds an event.
     *
     * @param  string  $summary  The summary of the event.
     * @param  string  $location  The location of the event.
     * @param  string  $from  The start time of the event.
     * @param  string  $to  The end time of the event.
     * @param  string  $description  The description of the event (optional).
     * @return Event The added event.
     */
    public function addEvent(string $summary, string $location, string $from, string $to, string $description = ''): Event
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

    /**
     * Updates an event.
     *
     * @param  string  $eventId  The ID of the event to update.
     * @param  string  $summary  The summary of the event.
     * @param  string  $location  The location of the event.
     * @param  string  $from  The start time of the event.
     * @param  string  $to  The end time of the event.
     * @param  string  $description  The description of the event (optional).
     * @return Event The updated event.
     */
    public function updateEvent(string $eventId, string $summary, string $location, string $from, string $to, string $description = ''): Event
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

    /**
     * Deletes an event.
     *
     * @param  string  $eventId  The ID of the event to delete.
     * @param  array  $optParams  Optional parameters.
     * @return mixed The response from the Calendar API.
     */
    public function deleteEvent(string $eventId, array $optParams = []): mixed
    {
        return $this->service->events->delete($this->calendarId, $eventId, $optParams);
    }
}
