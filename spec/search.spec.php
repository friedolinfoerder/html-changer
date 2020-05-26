<?php

use html_changer\HtmlChanger;

describe('HtmlChanger', function() {

    it("can search", function() {
        $input = "<div>Hello world</div>";
        $htmlChanger = HtmlChanger::parse($input, ['search' => ['Hello' => ['value' => 42]]]);
        $elements = $htmlChanger->parts();

        expect($elements)->toBeA('array')->toHaveLength(3);
        expect($elements[1]->type)->toBe('text');
        expect($elements[1]->search)->toBeA('array');
        expect($elements[1]->search)->toHaveLength(1);
    });

    it("can replace", function() {
        $input = "<div>Hello world</div>";
        $htmlChanger = HtmlChanger::parse($input, ['search' => ['world' => ['value' => 42]]]);
        $elements = $htmlChanger->parts(true);

        $elements[0]->replace(function($text, $value) {
            return $value;
        });

        expect($htmlChanger->html())->toBe('<div>Hello 42</div>');
    });

    it("can replace two", function() {
        $input = "<div>Ändern oder unterstützen</div>";
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'ändern' => ['value' => 'Change', 'caseInsensitive' => true],
            'unterstützen' => ['value' => 'Support', 'caseInsensitive' => true],
        ]]);
        $elements = $htmlChanger->parts(true);

        $elements[0]->replace(function($text, $value) {
            return $text . '/' . $value;
        });

        expect($htmlChanger->html())->toBe('<div>Ändern/Change oder unterstützen/Support</div>');
    });

});