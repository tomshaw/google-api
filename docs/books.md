# Google Books Service

The Google Books service adapter provides an interface for searching and retrieving information from the Google Books database.

## Setup

First, ensure you have authorized your application with Google Books scopes in your `config/google-api.php`:

```php
'service_scopes' => [
    Google\Service\Books::BOOKS,
],
```

**Note:** Most Google Books API operations work without authentication for public data. Authentication is primarily required for accessing user-specific features like bookshelves.

## Initialization

```php
use TomShaw\GoogleApi\Facades\GoogleApi;

$books = GoogleApi::books();
```

## Available Methods

### get

Retrieves information about a specific book volume by its ID.

**Parameters:**
- `$volumeId` (string) - The unique identifier for the book volume
- `$optParams` (array, default: []) - Optional parameters

**Returns:** `Google\Service\Books\Volume`

```php
$volumeId = 'zyTCAlFPjgYC'; // Example: "The Google Story"
$volume = $books->get($volumeId);

echo 'Title: ' . $volume->getVolumeInfo()->getTitle() . "\n";
echo 'Authors: ' . implode(', ', $volume->getVolumeInfo()->getAuthors()) . "\n";
echo 'Publisher: ' . $volume->getVolumeInfo()->getPublisher() . "\n";
echo 'Published Date: ' . $volume->getVolumeInfo()->getPublishedDate() . "\n";
echo 'Description: ' . $volume->getVolumeInfo()->getDescription() . "\n";
```

### listVolumes

Searches for books using a query string.

**Parameters:**
- `$query` (string) - The search query
- `$optParams` (array, default: []) - Optional parameters

**Returns:** `Google\Service\Books\Volumes`

**Common Optional Parameters:**
- `maxResults` (int) - Maximum number of results to return (default: 10, max: 40)
- `startIndex` (int) - Index of first result to return (for pagination)
- `orderBy` (string) - Sort order ('relevance' or 'newest')
- `filter` (string) - Filter results (e.g., 'partial', 'full', 'free-ebooks', 'paid-ebooks')
- `printType` (string) - Restrict to 'all', 'books', or 'magazines'
- `langRestrict` (string) - Restrict results to language code (e.g., 'en', 'fr')

```php
// Basic search
$results = $books->listVolumes('Laravel programming');

foreach ($results->getItems() as $volume) {
    echo $volume->getVolumeInfo()->getTitle() . "\n";
}

// Search with parameters
$results = $books->listVolumes('PHP', [
    'maxResults' => 20,
    'orderBy' => 'newest',
    'filter' => 'ebooks',
    'langRestrict' => 'en',
]);
```

## Search Query Syntax

Google Books supports special search operators:

### Search by Field

```php
// By title
$results = $books->listVolumes('intitle:Laravel');

// By author
$results = $books->listVolumes('inauthor:Taylor Otwell');

// By publisher
$results = $books->listVolumes('inpublisher:O\'Reilly');

// By subject
$results = $books->listVolumes('subject:programming');

// By ISBN
$results = $books->listVolumes('isbn:9781234567890');
```

### Combine Search Terms

```php
// Multiple fields
$results = $books->listVolumes('intitle:Laravel inauthor:Taylor');

// Exact phrase
$results = $books->listVolumes('"Web Development"');

// OR operator
$results = $books->listVolumes('Laravel OR Symfony');

// Exclude terms
$results = $books->listVolumes('PHP -WordPress');
```

## Working with Volume Data

### Accessing Book Information

```php
$volume = $books->get($volumeId);
$info = $volume->getVolumeInfo();

// Basic information
$title = $info->getTitle();
$subtitle = $info->getSubtitle();
$authors = $info->getAuthors(); // Array
$publisher = $info->getPublisher();
$publishedDate = $info->getPublishedDate();
$description = $info->getDescription();

// Identifiers
$isbn10 = null;
$isbn13 = null;
foreach ($info->getIndustryIdentifiers() as $identifier) {
    if ($identifier->getType() === 'ISBN_10') {
        $isbn10 = $identifier->getIdentifier();
    }
    if ($identifier->getType() === 'ISBN_13') {
        $isbn13 = $identifier->getIdentifier();
    }
}

// Categories and ratings
$categories = $info->getCategories(); // Array
$averageRating = $info->getAverageRating();
$ratingsCount = $info->getRatingsCount();

// Images
$thumbnail = $info->getImageLinks()?->getThumbnail();
$smallThumbnail = $info->getImageLinks()?->getSmallThumbnail();

// Page count and language
$pageCount = $info->getPageCount();
$language = $info->getLanguage();
```

### Accessing Sale Information

```php
$saleInfo = $volume->getSaleInfo();

// Check if book is for sale
$isForSale = $saleInfo->getSaleability() === 'FOR_SALE';
$country = $saleInfo->getCountry();

if ($isForSale) {
    $listPrice = $saleInfo->getListPrice();
    $retailPrice = $saleInfo->getRetailPrice();

    echo "List Price: {$listPrice->getAmount()} {$listPrice->getCurrencyCode()}\n";
    echo "Retail Price: {$retailPrice->getAmount()} {$retailPrice->getCurrencyCode()}\n";
    echo "Buy Link: {$saleInfo->getBuyLink()}\n";
}
```

## Complete Examples

### Search Books and Display Results

```php
use TomShaw\GoogleApi\Facades\GoogleApi;

class BookController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q', 'Laravel');

        $books = GoogleApi::books();
        $results = $books->listVolumes($query, [
            'maxResults' => 20,
            'orderBy' => 'relevance',
            'langRestrict' => 'en',
        ]);

        $bookList = [];

        foreach ($results->getItems() as $volume) {
            $info = $volume->getVolumeInfo();

            $bookList[] = [
                'id' => $volume->getId(),
                'title' => $info->getTitle(),
                'authors' => $info->getAuthors() ?? [],
                'publisher' => $info->getPublisher(),
                'publishedDate' => $info->getPublishedDate(),
                'description' => $info->getDescription(),
                'thumbnail' => $info->getImageLinks()?->getThumbnail(),
                'pageCount' => $info->getPageCount(),
                'averageRating' => $info->getAverageRating(),
            ];
        }

        return response()->json([
            'total' => $results->getTotalItems(),
            'books' => $bookList,
        ]);
    }
}
```

### Get Book Details by ISBN

```php
use TomShaw\GoogleApi\Facades\GoogleApi;

class BookController extends Controller
{
    public function getByIsbn($isbn)
    {
        $books = GoogleApi::books();

        // Search by ISBN
        $results = $books->listVolumes("isbn:{$isbn}", [
            'maxResults' => 1,
        ]);

        if ($results->getTotalItems() === 0) {
            return response()->json(['error' => 'Book not found'], 404);
        }

        $volume = $results->getItems()[0];
        $info = $volume->getVolumeInfo();
        $saleInfo = $volume->getSaleInfo();

        return response()->json([
            'id' => $volume->getId(),
            'title' => $info->getTitle(),
            'subtitle' => $info->getSubtitle(),
            'authors' => $info->getAuthors() ?? [],
            'publisher' => $info->getPublisher(),
            'publishedDate' => $info->getPublishedDate(),
            'description' => $info->getDescription(),
            'pageCount' => $info->getPageCount(),
            'categories' => $info->getCategories() ?? [],
            'language' => $info->getLanguage(),
            'thumbnail' => $info->getImageLinks()?->getThumbnail(),
            'previewLink' => $info->getPreviewLink(),
            'infoLink' => $info->getInfoLink(),
            'averageRating' => $info->getAverageRating(),
            'ratingsCount' => $info->getRatingsCount(),
            'isForSale' => $saleInfo->getSaleability() === 'FOR_SALE',
        ]);
    }
}
```

### Build a Book Search API with Pagination

```php
use TomShaw\GoogleApi\Facades\GoogleApi;

class BookSearchController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:40',
        ]);

        $query = $request->input('q');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        $startIndex = ($page - 1) * $perPage;

        $books = GoogleApi::books();

        $results = $books->listVolumes($query, [
            'maxResults' => $perPage,
            'startIndex' => $startIndex,
            'orderBy' => $request->input('order', 'relevance'),
            'filter' => $request->input('filter'), // e.g., 'ebooks'
            'langRestrict' => $request->input('lang', 'en'),
        ]);

        $items = collect($results->getItems() ?? [])->map(function ($volume) {
            $info = $volume->getVolumeInfo();
            return [
                'id' => $volume->getId(),
                'title' => $info->getTitle(),
                'authors' => $info->getAuthors() ?? [],
                'thumbnail' => $info->getImageLinks()?->getSmallThumbnail(),
                'publishedDate' => $info->getPublishedDate(),
            ];
        });

        return response()->json([
            'query' => $query,
            'total' => $results->getTotalItems(),
            'page' => $page,
            'per_page' => $perPage,
            'data' => $items,
        ]);
    }
}
```

## Filter Options

### Available Filters

- `partial` - Returns results where at least parts of the text are previewable
- `full` - Only returns results where all of the text is viewable
- `free-ebooks` - Only returns free Google eBooks
- `paid-ebooks` - Only returns Google eBooks with a price
- `ebooks` - Only returns Google eBooks (free or paid)

### Print Type Options

- `all` - Returns all print types (default)
- `books` - Returns only books
- `magazines` - Returns only magazines

## Notes

- Volume IDs are stable and can be stored for later retrieval
- Free books and previews are available without authentication
- Rate limits apply to the Google Books API
- Not all books have complete metadata (check for null values)
- Thumbnail URLs are provided by Google and may change
- Search results are ranked by relevance by default
- Maximum 40 results per request (use pagination for more)
