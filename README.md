# Apparatus Plugin #

Apparatus is a set of tools for OctoberCMS. Requires PHP7.1

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
use Keios\Apparatus\Contracts\NeedsDependencies;
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
        $jobManager = \App::make('Keios\Apparatus\Classes\JobManager');
        $jobManager->dispatch($job);
        
    }
}
```

If you do not want to clutter your controller (eg if you have job that is run every few minutes), you can use 

```
$jobManager->isSimpleJob(true);
```  

before dispatching - this will remove **successful** job from DB at the end.

### ListToggle

Column widget based on Inetis ListSwitch MIT OctoberCMS Plugin. Rewritten to by typesafe and faster.

Use "listtoggle" instead of "switch" and you will get clickable column field that will allow you to switch between true and false for boolean fields.


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

    $requestSender = new \Keios\Apparatus\Classes\RequestSender();
    
    $curlResponse = $requestSender->sendPostRequest($data, 'http://example.com/api/_mybooks/add');
    
    }
}
```
 

### Scenario Processor ###

-- early alpha --