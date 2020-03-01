HTML Changer
============

With this tiny library you can parse html code and change it's output. Other than many other similar modules, this library do not change the existing code while parsing. So you can only change the parts you need to change, while leaving all other code as is.

Installation
------------

```sh 
composer require friedolinfoerder/html-changer
```

Usage
-----

```php
use html_changer\HtmlChanger

// parse html
$htmlChanger = new HtmlChanger($html);

// print (original) html code
print $htmlChanger->html();
```