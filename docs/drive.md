# Google Drive Service

The Google Drive service adapter provides an interface for managing files and folders in Google Drive.

## Setup

First, ensure you have authorized your application with Google Drive scopes in your `config/google-api.php`:

```php
'service_scopes' => [
    Google\Service\Drive::DRIVE,
    // Or for read-only access:
    // Google\Service\Drive::DRIVE_READONLY,
],
```

## Initialization

```php
use TomShaw\GoogleApi\Facades\GoogleApi;

$drive = GoogleApi::drive();
```

## Available Methods

### listFiles

Lists files in Google Drive with optional parameters.

**Parameters:**
- `$optParams` (array, default: []) - Optional query parameters

**Returns:** `Google\Service\Drive\FileList`

**Common Optional Parameters:**
- `pageSize` (int) - Maximum number of files to return (default: 100, max: 1000)
- `q` (string) - Query string for filtering files
- `orderBy` (string) - Sort order (e.g., 'createdTime', 'modifiedTime', 'name')
- `fields` (string) - Specific fields to return

```php
// List all files
$files = $drive->listFiles();

foreach ($files->getFiles() as $file) {
    echo $file->getName() . ' (' . $file->getId() . ')' . "\n";
}

// List with parameters
$files = $drive->listFiles([
    'pageSize' => 10,
    'orderBy' => 'modifiedTime desc',
    'q' => "mimeType='application/pdf'",
]);
```

### getFile

Retrieves metadata for a specific file by ID.

**Parameters:**
- `$fileId` (string) - The ID of the file to retrieve
- `$optParams` (array, default: []) - Optional parameters

**Returns:** `Google\Service\Drive\DriveFile`

```php
$fileId = 'abc123xyz789';
$file = $drive->getFile($fileId);

echo 'Name: ' . $file->getName() . "\n";
echo 'MIME Type: ' . $file->getMimeType() . "\n";
echo 'Size: ' . $file->getSize() . " bytes\n";
echo 'Created: ' . $file->getCreatedTime() . "\n";
```

**Downloading file content:**

```php
$fileId = 'abc123xyz789';
$file = $drive->getFile($fileId, ['alt' => 'media']);

// Save to local storage
file_put_contents(storage_path('app/' . $file->getName()), $file);
```

### createFile

Creates a new file in Google Drive.

**Parameters:**
- `$name` (string) - The name of the file
- `$mimeType` (string) - The MIME type of the file
- `$content` (string) - The file content

**Returns:** `Google\Service\Drive\DriveFile`

```php
$name = 'test-document.txt';
$mimeType = 'text/plain';
$content = 'Hello, this is test content for Google Drive!';

$file = $drive->createFile($name, $mimeType, $content);

echo 'File created with ID: ' . $file->getId();
```

## Query Syntax

Google Drive supports a powerful query syntax for filtering files. Here are common examples:

### Filter by MIME Type

```php
// PDF files only
$files = $drive->listFiles([
    'q' => "mimeType='application/pdf'",
]);

// Google Docs only
$files = $drive->listFiles([
    'q' => "mimeType='application/vnd.google-apps.document'",
]);

// Images only
$files = $drive->listFiles([
    'q' => "mimeType contains 'image/'",
]);
```

### Filter by Name

```php
// Exact match
$files = $drive->listFiles([
    'q' => "name='invoice.pdf'",
]);

// Contains
$files = $drive->listFiles([
    'q' => "name contains 'invoice'",
]);
```

### Filter by Folder

```php
// Files in specific folder
$folderId = 'folder_id_here';
$files = $drive->listFiles([
    'q' => "'{$folderId}' in parents",
]);
```

### Filter by Date

```php
// Files modified after date
$files = $drive->listFiles([
    'q' => "modifiedTime > '2024-01-01T00:00:00'",
]);

// Files created today
$today = date('Y-m-d');
$files = $drive->listFiles([
    'q' => "createdTime > '{$today}T00:00:00'",
]);
```

### Combine Multiple Filters

```php
// PDF files in specific folder modified in last 7 days
$folderId = 'folder_id_here';
$lastWeek = date('Y-m-d', strtotime('-7 days'));

$files = $drive->listFiles([
    'q' => "'{$folderId}' in parents and mimeType='application/pdf' and modifiedTime > '{$lastWeek}T00:00:00'",
    'orderBy' => 'modifiedTime desc',
]);
```

## Complete Examples

### List Recent Files

```php
use TomShaw\GoogleApi\Facades\GoogleApi;

class DriveController extends Controller
{
    public function listRecentFiles()
    {
        $drive = GoogleApi::drive();

        $files = $drive->listFiles([
            'pageSize' => 20,
            'orderBy' => 'modifiedTime desc',
            'fields' => 'files(id, name, mimeType, modifiedTime, size)',
        ]);

        $fileList = collect($files->getFiles())->map(function ($file) {
            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mimeType' => $file->getMimeType(),
                'modified' => $file->getModifiedTime(),
                'size' => $file->getSize(),
            ];
        });

        return response()->json($fileList);
    }
}
```

### Upload File from Laravel Storage

```php
use TomShaw\GoogleApi\Facades\GoogleApi;
use Illuminate\Support\Facades\Storage;

class DriveController extends Controller
{
    public function uploadFile(Request $request)
    {
        $file = $request->file('upload');

        // Get file content
        $content = file_get_contents($file->getRealPath());

        // Upload to Google Drive
        $drive = GoogleApi::drive();
        $driveFile = $drive->createFile(
            $file->getClientOriginalName(),
            $file->getMimeType(),
            $content
        );

        return response()->json([
            'message' => 'File uploaded successfully',
            'file_id' => $driveFile->getId(),
            'file_name' => $driveFile->getName(),
        ]);
    }
}
```

### Search and Download Files

```php
use TomShaw\GoogleApi\Facades\GoogleApi;

class DriveController extends Controller
{
    public function findAndDownloadInvoices()
    {
        $drive = GoogleApi::drive();

        // Search for invoice PDFs
        $files = $drive->listFiles([
            'q' => "name contains 'invoice' and mimeType='application/pdf'",
            'orderBy' => 'createdTime desc',
            'pageSize' => 10,
        ]);

        $downloads = [];

        foreach ($files->getFiles() as $file) {
            // Get file content
            $fileContent = $drive->getFile($file->getId(), ['alt' => 'media']);

            // Save locally
            $localPath = storage_path('app/invoices/' . $file->getName());
            file_put_contents($localPath, $fileContent);

            $downloads[] = [
                'name' => $file->getName(),
                'path' => $localPath,
            ];
        }

        return response()->json([
            'message' => count($downloads) . ' invoices downloaded',
            'files' => $downloads,
        ]);
    }
}
```

## Common MIME Types

Here are common MIME types for Google Drive:

### Google Workspace Files
- Google Docs: `application/vnd.google-apps.document`
- Google Sheets: `application/vnd.google-apps.spreadsheet`
- Google Slides: `application/vnd.google-apps.presentation`
- Google Forms: `application/vnd.google-apps.form`
- Google Folder: `application/vnd.google-apps.folder`

### Common File Types
- PDF: `application/pdf`
- Plain Text: `text/plain`
- HTML: `text/html`
- CSV: `text/csv`
- JSON: `application/json`
- XML: `application/xml`
- ZIP: `application/zip`
- JPEG: `image/jpeg`
- PNG: `image/png`

## Notes

- File IDs are unique identifiers used for operations
- The `alt=media` parameter downloads file content instead of metadata
- Large files should be uploaded using resumable uploads (not currently supported in this adapter)
- Folder operations use the same file methods with MIME type `application/vnd.google-apps.folder`
- Deleted files can be recovered from trash unless permanently deleted
