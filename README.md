# Apparatus Plugin #

Apparatus is a set of tools for OctoberCMS.

### Functionalities ###

* Dependency injector
* Flash notifications
* Event-based business logic scenario processor for painless application behavior management & modification. 

### Notifications ###

- Put [apparatusFlashMessages] component into your layout and call it with {% apparatusFlashMessages %} after jquery.
- All exceptions and Flash messages will be now covered by Apparatus. You can configure notification design in Settings

### Dependency Injector ###

Here is an example of using with a OctoberCMS component:

```
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

### Scenario Processor ###

-- early alpha --