![Apparatus](assets/img/hero.png)

# Apparatus Plugin

Apparatus is the core framework plugin for the Golem15 WinterCMS stack. It provides dependency injection, background job management, route resolution, flash notifications, form widgets, backend asset injection, and a suite of developer utilities. Requires PHP 8.4+.

## Features

- **Dependency Injector** - Automatic dependency injection for CMS components
- **Route Resolver** - Programmatic CMS page/component URL resolution
- **Background Job Manager** - Queue jobs with progress tracking in the backend
- **Background Import Manager** - Large CSV imports via queue workers
- **Flash Notifications** - Configurable notification system with multiple themes (Noty.js)
- **Confirm Modal** - Custom styled AJAX confirmation dialogs
- **Infinite Scroll** - Automatic pagination with deduplication
- **Backend Injector** - Dynamic CSS/JS/AJAX handler injection into backend controllers
- **ListToggle** - Clickable boolean toggle column for backend lists
- **KnobWidget** - Dial/knob form widget for number selection
- **Request Sender** - cURL-based HTTP client with auth support
- **Blog URL Validation Middleware** - SEO-compliant blog URL enforcement
- **Protected File Downloads** - Backend-authenticated file access
- **Translation API** - Dynamic translation key retrieval endpoint
- **Translation Scanner** - Auto-scans component templates for translatable strings
- **Twig Filters** - `ucfirst` and `human_date`
- **Mail Template Management** - Export, import, and reset mail templates via CLI
- **Pipeline** - Middleware-style operation chaining utility
- **Resolver Facade** - Quick access to route resolution from anywhere

## Installation

Apparatus is included as a git submodule in the Golem15 starter stack. It is a dependency of most other Golem15 plugins.

```bash
composer require golem15/apparatus-framework
```

### Dependencies

- `keios/laravel-apparatus` - Scenario-based workflow engine
- `intervention/image` - Image manipulation (v3, auto-discovered)
- `hashids/hashids` - Short unique ID generation

---

## Components

### Flash Messages

Provides automatic handling of all exceptions and flash messages with configurable notification themes.

Add the component to your layout:

```twig
[apparatusFlashMessages]
==
{% component "apparatusFlashMessages" %}
```

Configure the notification engine in **Settings > Apparatus > Notifications**:
- Layout/position
- Animation style (powered by Animate.css)
- Theme: tailwind, bootstrap-v3, bootstrap-v4, metroui, mint, nest, relax, semanticui, sunset, queststream
- Timeout, max visible count, modal behavior

### Confirm Modal

Drop-in replacement for the default browser confirm dialog on AJAX requests:

```twig
[confirmModal]
==
{% component "confirmModal" %}
```

### Infinite Scroll

Automatic infinite scroll for paginated lists with built-in deduplication:

```twig
[infiniteScroll]
==
{% component "infiniteScroll" %}
```

---

## Dependency Injector

Automatically injects dependencies into CMS components that implement `NeedsDependencies`. Any method prefixed with `inject` will be resolved from the service container.

```php
<?php namespace Acme\Plugin\Components;

use Cms\Classes\ComponentBase;
use Golem15\Apparatus\Contracts\NeedsDependencies;
use Acme\Plugin\Repositories\ProductRepository;

class ProductList extends ComponentBase implements NeedsDependencies
{
    protected ProductRepository $products;

    public function injectProductRepository(ProductRepository $products): void
    {
        $this->products = $products;
    }

    public function onRun(): void
    {
        $this->page['products'] = $this->products->getAll();
    }
}
```

---

## Route Resolver

Programmatically resolve CMS page URLs by their component names. Useful for generating links without hardcoding URLs.

```php
use Golem15\Apparatus\Facades\Resolver;

// Get the URL of a page containing a specific component
$url = Resolver::resolveRouteTo('blogPost');
// e.g. "/blog/:category/:post"

// Get URL without dynamic parameters
$url = Resolver::resolveRouteWithoutParamsTo('blogPost');
// e.g. "/blog"

// Get URL with a specific parameter value filled in
$url = Resolver::resolveParameterizedRouteTo('blogPost', 'slug', 'my-article');
// e.g. "/blog/tech/my-article"

// Resolve a Page object from a URL
$page = Resolver::resolvePageForUrl('/blog/tech/my-article');
```

Also available via the service container:

```php
$resolver = app('apparatus.route.resolver');
```

---

## Background Job Manager

Push jobs to the queue and monitor their progress in the backend under **Apparatus > Jobs**.

### Creating a Job

Your job class must implement `ApparatusQueueJob`:

```php
use Golem15\Apparatus\Contracts\ApparatusQueueJob;
use Golem15\Apparatus\Classes\JobManager;

class MyDataImportJob implements ApparatusQueueJob, \Illuminate\Contracts\Queue\ShouldQueue
{
    use \Illuminate\Bus\Queueable, \Illuminate\Queue\InteractsWithQueue;

    protected int $jobId;

    public function assignJobId(int $jobId): void
    {
        $this->jobId = $jobId;
    }

    public function handle(): void
    {
        $jobManager = app(JobManager::class);
        $jobManager->startJob($this->jobId);

        // Do work, update progress...
        $jobManager->updateJobState($this->jobId, $current, $total);

        $jobManager->completeJob($this->jobId);
    }
}
```

### Dispatching a Job

```php
$job = new MyDataImportJob();
$jobManager = app(\Golem15\Apparatus\Classes\JobManager::class);
$jobManager->dispatch($job, 'Data import');
```

For jobs that run frequently and don't need to be tracked in the UI:

```php
$jobManager->isSimpleJob(true); // removes successful job records from DB
$jobManager->dispatch($job, 'Periodic sync');
```

---

## Background Import Manager

Drop-in replacement for WinterCMS's ImportExportController that processes large CSV files via queue workers instead of blocking the HTTP request.

Replace the behavior in your controller:

```php
// Instead of:
'Backend.Behaviors.ImportExportController'

// Use:
'Golem15.Apparatus.Behaviors.BackgroundImportExportController'
```

In your Import model:

```php
use Golem15\Apparatus\Classes\JobManager;
use Golem15\Apparatus\Jobs\CsvImportJob;

public function importData($results, $sessionKey = null)
{
    $job = new CsvImportJob($results, 'Acme\Plugin\Models\Product', true, 20);
    $jobManager = app(JobManager::class);

    return $jobManager->dispatch($job, 'Product import');
}
```

`CsvImportJob` parameters:
- `$results` - Array of data rows
- `$modelClass` - Target model class name
- `$updateExisting` - Whether to update existing records
- `$chunkSize` - Number of rows per insert batch

After dispatching, users are redirected to the Apparatus Jobs screen to monitor progress.

**Important:** You must configure a real queue driver (e.g., Redis, database) instead of `sync` and run a queue worker.

---

## ListToggle

Clickable boolean toggle column for backend lists. Based on Inetis ListSwitch, rewritten for type safety and performance.

Use `listtoggle` as the column type in your `columns.yaml`:

```yaml
is_active:
    label: Active
    type: listtoggle
```

Options:
- Custom icon and text labels for on/off states
- Read-only mode
- Custom title text

---

## KnobWidget

A dial/knob form widget for selecting numeric values. Uses the jQuery Knob library.

```yaml
my_number:
    type: knob
    knobLabel: Priority Level
    knobComment: Set the priority for this item
    min: 1
    max: 30
    default: 5
    angleOffset: -125
    angleArc: 250
```

Configurable properties: `min`, `max`, `default`, `step`, `width`, `height`, `angleOffset`, `angleArc`, `thickness`, `lineCap`, `fgColor`, `bgColor`, `inputColor`, `displayInput`, `readOnly`.

---

## Backend Injector

Inject CSS, JS, and AJAX handlers into any backend controller from your plugin's boot method:

```php
$injector = app('apparatus.backend.injector');

$injector->addCss('/plugins/acme/plugin/assets/css/custom.css');
$injector->addJs('/plugins/acme/plugin/assets/js/custom.js');
$injector->addAjaxHandler('onCustomAction', function() {
    // handler logic
});
```

---

## Request Sender

cURL-based HTTP client supporting multiple methods and authentication:

```php
use Golem15\Apparatus\Classes\RequestSender;

// Basic usage
$sender = new RequestSender();
$response = $sender->sendPostRequest(['key' => 'value'], 'https://api.example.com/endpoint');

// With Bearer token authentication
$sender = new RequestSender('your-api-token');
$response = $sender->sendGetRequest('https://api.example.com/data');

// All available methods
$sender->sendPostRequest($data, $url, $asJson = false);
$sender->sendPutRequest($data, $url);
$sender->sendPatchRequest($data, $url);
$sender->sendGetRequest($url);
$sender->sendPostRequestWithFile($data, $url, $filePath);
$sender->downloadFile($url, $destinationPath);
```

---

## Blog URL Validation Middleware

SEO middleware that validates blog URLs against the Winter.Blog category/post structure:

- Returns **404** for non-existing categories
- **301 redirects** posts accessed under an incorrect category to the correct URL

Configure in `config/blog.php` or via environment variables:

```env
BLOG_URL_VALIDATION_ENABLED=true
BLOG_URL_VALIDATION_ROUTES=blog
```

---

## Twig Filters

### `ucfirst`

Capitalizes the first letter of a string:

```twig
{{ variable|ucfirst }}
```

### `human_date`

Formats dates into human-readable strings:

```twig
{{ post.created_at|human_date }}
```

Output examples: "Today at 14:30", "Tomorrow at 09:00", "Monday at 11:15", "In 2 weeks", "In 3 months".

---

## Console Commands

| Command | Description |
|---------|-------------|
| `apparatus:optimize` | Optimizes the `deferred_bindings` table (varchar to integer conversion) |
| `apparatus:mail-export` | Export mail templates, layouts, and partials to filesystem (`--force` to overwrite) |
| `apparatus:mail-import` | Import mail templates from filesystem back to database |
| `apparatus:mail-reset` | Reset all mail templates to their defaults |
| `apparatus:fakejob` | Dispatch a test job (sleeps for N seconds) for queue testing |
| `g15:sane-git` | Set skip-worktree on git submodules (`--insane` to revert) |
| `queue:clear` | Clear all queued jobs by connection/queue name |

---

## Routes

| Method | URL | Description |
|--------|-----|-------------|
| `GET` | `/storage/app/uploads/protected/{slug}` | Protected file download (requires backend authentication) |
| `POST` | `/_translapi` | Dynamic translation key retrieval API |

---

## Permissions

| Permission | Description |
|------------|-------------|
| `golem15.apparatus.access_settings` | Access Apparatus notification settings |
| `golem15.apparatus.access_jobs` | Access the Jobs backend interface |

---

## Localization

Apparatus ships with translations for:
- English (en)
- French (fr)
- Polish (pl)

### Translation Scanner

Apparatus extends the Winter.Translate theme scanner to automatically discover translatable strings in component partial templates (`plugins/*/*/components/*/*.htm`), ensuring they appear in the translation interface without manual registration.

---

## Pipeline Utility

A middleware-style pipeline for chaining operations:

```php
use Golem15\Apparatus\Classes\Pipeline;

$result = (new Pipeline(app()))
    ->send($data)
    ->through([
        FirstProcessor::class,
        SecondProcessor::class,
    ])
    ->thenReturn();
```

Each processor implements the `Pipe` contract with a `handle($payload, Closure $next)` method.

---

## License

MIT
