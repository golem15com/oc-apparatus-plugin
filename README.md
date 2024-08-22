# Apparatus Plugin #

Apparatus is a set of tools for OctoberCMS. Requires PHP8.3

### Functionalities ###

* Dependency injector
* Flash notifications
* List switch replacement
* Background Job Manager
* Simple request sender
* Backend assets and ajax injector
* Event-based business logic scenario processor for painless application behavior management & modification.

### Notifications ###

- Put `[apparatusFlashMessages]` component into your layout and call it with `{% component "apparatusFlashMessages" %}` after jquery.

- All exceptions and Flash messages will be now covered by Apparatus. You can configure notification design in Settings and in your CSS files.

### Dependency Injector ###

Here is an example of using with a OctoberCMS component:

```php
<?php namespace Acme\Plugin\Components;

use Cms\Classes\ComponentBase;
use Golem15\Apparatus\Contracts\NeedsDependencies;
use Acme\Plugin\Classes\WebsiteRepository;

class AcmeComponent extends ComponentBase implements NeedsDependencies
{

    public function componentDetails()
    {
        return [
            'name'        => 'Acme Component',
            'description' => 'No description...',
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    protected $recordRepository;

    public function injectWebsiteRepository(WebsiteRepository $websiteRepository)
    {
        $this->recordRepository = $websiteRepository;
    }

    public function onRun()
    {
        $this->page['records'] = $this->recordRepository->getAll();
    }
}
```

### Background Job Manager

With Apparatus JobManager you can easily push jobs to queue and see their progress in backend.

Check jobs\SendRequestJob.php for exemplary job structure.

When you have your file, you can deploy it from within controller like:

```php
<?php

class SomeComponent {
    public function onDeployJob(){

        $job = new MyJobClass();
        $jobManager = \App::make('Golem15\Apparatus\Classes\JobManager');
        $jobManager->dispatch($job, 'Requests sending');

    }
}
```

If you do not want to clutter your controller (eg if you have job that is run every few minutes), you can use

```
$jobManager->isSimpleJob(true);
```

before dispatching - this will remove **successful** job from DB at the end.

### Background Import Manager

**Under development!**

Sometimes you need to import large amount of data, like thousands of rows. While [October ImportExport](https://octobercms.com/docs/backend/import-export) behavior works quite splendid, when working with large CSV you need to increase your php.ini and webserver timeouts.

Apparatus provides solution for that. Replace:

```
'Backend.Behaviors.ImportExportController'
```

with

```
'Golem15.Apparatus.Behaviors.BackgroundImportExportController'
```

and in your Import model use:

```
public function importData($results, $sessionKey = null)
{
    $job = new CsvImportJob($result, 'Acme\Plugin\Models\YourModel', true, 20);
    $jobManager = \App::make(JobManager::class);
    $jobId = $jobManager->dispatch($job, 'Rates import');

    return $jobId;
}
```

CsvImport job takes following arguments:

- results array
- your main model name
- updateExisting boolean flag (under development)
- chunk size (we insert data in chunks during import to make it faster)

You can also replace default CsvImportJob with your own job class.

Now instead of normal Import behavior popups, you will be redirected to Apparatus Job screen:

![import](https://i.viamage.com/jz/screen-2018-04-28-15-29-06.png)

![job](https://i.viamage.com/jz/screen-2018-04-28-15-15-26.png)

![job_complete](https://i.viamage.com/jz/screen-2018-04-28-15-29-55.png)

**Remember to replace Sync driver for your queue with something else and start queue worker. We recommend Redis.**

Find more about queue configuration [here](https://octobercms.com/docs/services/queues#running-the-queue-listener).

[Movie with example](http://uploads.golem15.eu/video/import-2018-04-29_11.08.25.mp4)

### ListToggle

Column widget based on Inetis ListSwitch MIT OctoberCMS Plugin. Rewritten to by typesafe and faster.

Use "listtoggle" instead of "switch" and you will get clickable column field that will allow you to switch between true and false for boolean fields.


### KnobWidget

Nice widget for selecting number with knob.

![knob](https://i.viamage.com/jz/screen-2018-05-17-11-27-27.png)

Example yaml:

```
    my_number:
      knobLabel: Label that will appear to the right (not above)
      knobComment: Comment that will appear to the right (not below)
      type: knob
      min: 1 # minimum value
      default: 2 # default value
      max: 30 # max value
      angleOffset: -125 # starting point angle
      angleArc: 250  # whole knob angle

```

### Request Sender

Simple curl request sender. DELETE / PUT / GET will be added soon.

```php
<?php
class SomeClass {
  public function addBook(){
    $data = [
     'name' => 'My book',
     'author' => 'Me',
     'bought_at' => '2018-05-05 12:30'
    ];

    $requestSender = new \Golem15\Apparatus\Classes\RequestSender();

    $curlResponse = $requestSender->sendPostRequest($data, 'http://example.com/api/_mybooks/add');

    }
}
```


### Scenario Processor ###

-- early alpha --
