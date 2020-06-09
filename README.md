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

// search and replace text
$htmlChanger = new HtmlChanger($text, [
    'search' => [
        'test' => [
            'value' => 'TEST', 
            'caseInsensitive' => false, // default false
            'wordBoundary' => true, // default true
            'group' => 1, // default is the key (here 'test') 
            'maxCount' => 3, // default -1, means no rescriction
        ]
    ],
    'ignore' => [
        'b',
        'h1',
        'h2',
        'a',
        '.ignored',
        '#ad',
    ]
]);
$htmlChanger->replace(function ($text, $value) {
    return $text . '/' . $value;
});

// print html code
print $htmlChanger->html();
```