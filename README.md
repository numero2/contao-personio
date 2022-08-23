Contao Personio Bundle
=======================

About
--

Import job advertisements from [Personio](https://www.personio.de/) as news into Contao.

System requirements
--

* [Contao 4.9](https://github.com/contao/contao) (or newer)

Installation
--

* Install via Contao Manager or Composer (`composer require numero2/contao-personio-bundle`)
* Run a database update via the Contao-Installtool or using the [contao:migrate](https://docs.contao.org/dev/reference/commands/) command.

Hooks
--

By default the bundle only imports certain information from the Personio job advertisements. If you need more meta data you can import them on your own using the `parsePersonioPosition` hook:

```php
// src/EventListener/ParsePersonioPositionListener.php
namespace App\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\NewsModel;

/**
 * @Hook("parsePersonioPosition")
 */
class ParsePersonioPositionListener
{
    public function __invoke(NewsModel $news, object $position, bool $isUpdate): void
    {
        $news->something = $position->something;
    }
}
```