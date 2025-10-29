# Google Calendar Service

The Google Calendar service adapter provides a fluent interface for managing calendar events using the Google Calendar API.

## Setup

First, ensure you have authorized your application with Google Calendar scopes in your `config/google-api.php`:

```php
'service_scopes' => [
    Google\Service\Calendar::CALENDAR,
],
```

## Initialization

```php
use TomShaw\GoogleApi\Facades\GoogleApi;

$calendar = GoogleApi::calendar();
```

## Setting the Calendar ID

Before performing operations, you must set the calendar ID (typically the user's email address):

```php
$calendar->setCalendarId('user@example.com');
```

## Available Methods

### listEvents

Lists upcoming events from the calendar.

**Parameters:**
- `$maxResults` (int, default: 10) - Maximum number of events to return
- `$orderBy` (string, default: 'startTime') - Order to return events
- `$singleEvents` (bool, default: true) - Whether to expand recurring events

**Returns:** `Google\Service\Calendar\Events`

```php
$events = $calendar->setCalendarId('user@example.com')
    ->listEvents(20, 'startTime', true);

foreach ($events->getItems() as $event) {
    echo $event->getSummary() . "\n";
}
```

### getEvent

Retrieves a specific calendar event by ID.

**Parameters:**
- `$eventId` (string) - The ID of the event to retrieve

**Returns:** `Google\Service\Calendar\Event`

```php
$event = $calendar->setCalendarId('user@example.com')
    ->getEvent('event_id_here');

echo $event->getSummary();
echo $event->getStart()->getDateTime();
```

### addEvent

Creates a new calendar event.

**Parameters:**
- `$summary` (string) - The event title/summary
- `$location` (string|null) - Event location (optional)
- `$description` (string|null) - Event description (optional)
- `$from` (Carbon) - Event start time
- `$to` (Carbon) - Event end time

**Returns:** `Google\Service\Calendar\Event`

```php
use Carbon\Carbon;

$calendar = GoogleApi::calendar()->setCalendarId('user@example.com');

$summary = 'Team Meeting';
$location = '123 Conference Room, Office Building';
$description = 'Quarterly review meeting';

$from = Carbon::now()->timezone('America/Chicago')->addDay()->setTime(13, 0);
$to = $from->copy()->addHour();

$event = $calendar->addEvent($summary, $location, $description, $from, $to);

// Save the event ID for future updates
$eventId = $event->getId();
```

### updateEvent

Updates an existing calendar event.

**Parameters:**
- `$eventId` (string) - The ID of the event to update
- `$summary` (string) - The new event title/summary
- `$location` (string|null) - New event location (optional)
- `$description` (string|null) - New event description (optional)
- `$from` (Carbon) - New event start time
- `$to` (Carbon) - New event end time

**Returns:** `Google\Service\Calendar\Event`

```php
use Carbon\Carbon;

$calendar = GoogleApi::calendar()->setCalendarId('user@example.com');

$eventId = 'event_id_here';
$summary = 'Updated Team Meeting';
$location = '456 Different Room';
$description = 'Rescheduled quarterly review';

$from = Carbon::now()->timezone('America/Chicago')->addDays(2)->setTime(14, 0);
$to = $from->copy()->addHours(2);

$updatedEvent = $calendar->updateEvent($eventId, $summary, $location, $description, $from, $to);
```

### deleteEvent

Deletes a calendar event.

**Parameters:**
- `$eventId` (string) - The ID of the event to delete
- `$optParams` (array, default: []) - Optional parameters for the delete request

**Returns:** Mixed (API response)

```php
$calendar = GoogleApi::calendar()->setCalendarId('user@example.com');

$calendar->deleteEvent('event_id_here');
```

## Complete Example

```php
use TomShaw\GoogleApi\Facades\GoogleApi;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function createEvent()
    {
        $calendar = GoogleApi::calendar()->setCalendarId('user@example.com');

        $summary = 'Project Deadline';
        $location = 'Remote';
        $description = 'Final submission for Q4 project';

        $from = Carbon::now()->timezone('America/Chicago')->addWeek()->setTime(17, 0);
        $to = $from->copy()->addHour();

        $event = $calendar->addEvent($summary, $location, $description, $from, $to);

        return response()->json([
            'event_id' => $event->getId(),
            'html_link' => $event->getHtmlLink(),
        ]);
    }

    public function listUpcomingEvents()
    {
        $calendar = GoogleApi::calendar()->setCalendarId('user@example.com');

        $events = $calendar->listEvents(10);

        $upcomingEvents = collect($events->getItems())->map(function ($event) {
            return [
                'id' => $event->getId(),
                'summary' => $event->getSummary(),
                'start' => $event->getStart()->getDateTime(),
                'end' => $event->getEnd()->getDateTime(),
            ];
        });

        return response()->json($upcomingEvents);
    }
}
```

## Notes

- All date/time parameters use Carbon instances for timezone-aware operations
- Event IDs should be stored for future updates or deletions
- The calendar ID is typically the user's email address for their primary calendar
- Recurring events can be expanded into individual instances using the `singleEvents` parameter
