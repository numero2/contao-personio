Contao Personio Bundle
=======================

About
--

Import job advertisements from [Personio](https://www.personio.de/) as news into Contao.

System requirements
--

* [Contao 4.13](https://github.com/contao/contao) (or newer)

Installation
--

* Install via Contao Manager or Composer (`composer require numero2/contao-personio-bundle`)
* Run a database update via the Contao-Installtool or using the [contao:migrate](https://docs.contao.org/dev/reference/commands/) command.

Events
--

By default the bundle only imports certain information from Personio that can be matches to the structure of Contao's news. If you need more data you can import them on your own using the `personio_import_advertisement` event.

> [!IMPORTANT]
> This example shows how to import additional job information from Personio.<br>
> **Note:** You must first define any custom fields in your own `contao/dca/tl_news.php` as they are not part of Contao's core.

```php
// src/EventListener/PersonioParseListener.php
namespace App\EventListener;

use numero2\PersonioBundle\API\PersonioCatalogTypes;
use numero2\PersonioBundle\Event\PersonioEvents;
use numero2\PersonioBundle\Event\PersonioParseEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;


#[AsEventListener(PersonioEvents::IMPORT_ADVERTISEMENT)]
class PersonioParseListener {

    public function __invoke( PersonioParseEvent $event ): void {

        $position = $event->getPosition();
        $news = $event->getNews();
        $isUpdate = $event->isUpdate();

        // add some additional data
        $news->job_name = $position->name??'';
        $news->job_schedule = $position->schedule??'';
        $news->job_office = $position->office??'';
    }
}
```