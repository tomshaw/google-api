<?php

namespace TomShaw\GoogleApi\Resources;

use Carbon\Carbon;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\Events;
use TomShaw\GoogleApi\GoogleClient;

final class GoogleCalendar
{
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
     * Lists events from the Google Calendar.
     *
     * @param  int  $maxResults  The maximum number of events to return.
     * @param  string  $orderBy  The order in which to return the events.
     * @param  bool  $singleEvents  Whether to return single events or recurring events.
     * @return Events The list of events.
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
     * Gets a specific event from the Google Calendar.
     *
     * @param  string  $eventId  The ID of the event to retrieve.
     * @return Event The requested event.
     */
    public function getEvent(string $eventId): Event
    {
        return $this->service->events->get($this->calendarId, $eventId);
    }

    /**
     * Adds a new event to the Google Calendar.
     *
     * @param  string  $summary  The summary of the event.
     * @param  string|null  $location  The new location for the event.
     * @param  string|null  $description  The new description for the event.
     * @param  Carbon  $from  The start time of the event.
     * @param  Carbon  $to  The end time of the event.
     * @return Event The newly created event.
     */
    public function addEvent(string $summary, ?string $location, ?string $description, Carbon $from, Carbon $to): Event
    {
        $event = new Event;
        $event->setSummary($summary);

        if ($location) {
            $event->setLocation($location);
        }

        if ($description) {
            $event->setDescription($description);
        }

        $start = new EventDateTime;
        $start->setDateTime($from->toRfc3339String());
        $event->setStart($start);

        $end = new EventDateTime;
        $end->setDateTime($to->toRfc3339String());
        $event->setEnd($end);

        return $this->service->events->insert($this->calendarId, $event);
    }

    /**
     * Updates an existing Google Calendar event.
     *
     * @param  string  $eventId  The ID of the event to update.
     * @param  string  $summary  The new summary for the event.
     * @param  string|null  $location  The new location for the event.
     * @param  string|null  $description  The new description for the event.
     * @param  Carbon  $from  The new start time for the event.
     * @param  Carbon  $to  The new end time for the event.
     * @return Event The updated event.
     */
    public function updateEvent(string $eventId, string $summary, ?string $location, ?string $description, Carbon $from, Carbon $to): Event
    {
        $event = new Event($this->service->events->get($this->calendarId, $eventId));
        $event->setSummary($summary);

        if ($location) {
            $event->setLocation($location);
        }

        if ($description) {
            $event->setDescription($description);
        }

        $start = new EventDateTime;
        $start->setDateTime($from->toRfc3339String());
        $event->setStart($start);

        $end = new EventDateTime;
        $end->setDateTime($to->toRfc3339String());
        $event->setEnd($end);

        return $this->service->events->update($this->calendarId, $eventId, $event);
    }

    /**
     * Deletes an event from the Google Calendar.
     *
     * @param  string  $eventId  The ID of the event to delete.
     * @param  array  $optParams  Optional parameters for the delete request.
     * @return mixed The response from the Google Calendar API.
     */
    public function deleteEvent(string $eventId, array $optParams = []): mixed
    {
        return $this->service->events->delete($this->calendarId, $eventId, $optParams);
    }
}
