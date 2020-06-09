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

    it("can use longer replacement", function() {
        $input = "<div>Ändern oder unterstützen</div>";
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'ändern' => ['value' => 'Change', 'caseInsensitive' => true],
            'Ändern oder unterstützen' => ['value' => 'Support', 'caseInsensitive' => true],
        ]]);
        $elements = $htmlChanger->parts(true);

        $elements[0]->replace(function($text, $value) {
            return '[' . $text . ']';
        });

        expect($htmlChanger->html())->toBe('<div>[Ändern oder unterstützen]</div>');
    });

    it("can use word boundaries", function() {
        $input = "<div>Ändern oder unterstützen</div>";
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'er' => ['value' => 'Test'],
        ]]);
        $elements = $htmlChanger->parts(true);

        $elements[0]->replace(function($text, $value) {
            return '[' . $text . ']';
        });

        expect($htmlChanger->html())->toBe('<div>Ändern oder unterstützen</div>');
    });

    it("can use word boundaries", function() {
        $input = "<div>Ändern oder unterstützen</div>";
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'er' => ['value' => 'Test', 'wordBoundary' => false],
        ]]);
        $elements = $htmlChanger->parts(true);

        $elements[0]->replace(function($text, $value) {
            return '[' . $text . ']';
        });

        expect($htmlChanger->html())->toBe('<div>Änd[er]n od[er] unt[er]stützen</div>');
    });

    it("can handle max count", function() {
        $input = "<div>und und und</div>";
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'und' => ['value' => 'Test', 'maxCount' => 1],
        ]]);
        $elements = $htmlChanger->parts(true);

        $elements[0]->replace(function($text, $value) {
            return '[' . $text . ']';
        });

        expect($htmlChanger->html())->toBe('<div>[und] und und</div>');
    });

    it("can handle max count with groups", function() {
        $input = "<div>und oder und</div>";
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'und' => ['value' => 'Test', 'maxCount' => 1, 'group' => 1],
            'oder' => ['value' => 'Test', 'maxCount' => 1, 'group' => 1],
        ]]);
        $elements = $htmlChanger->parts(true);

        $elements[0]->replace(function($text, $value) {
            return '[' . $text . ']';
        });

        expect($htmlChanger->html())->toBe('<div>[und] oder und</div>');
    });

    it('can ignore certain elements', function() {
        $input = "<div>Das ist ein Test</div>";
        $htmlChanger = HtmlChanger::parse($input, ['ignore' => ['div'], 'search' => [
            'Test' => ['value' => 'Test'],
        ]]);
        $elements = $htmlChanger->parts(true);

        $elements[0]->replace(function($text, $value) {
            return '[' . $text . ']';
        });

        expect($htmlChanger->html())->toBe('<div>Das ist ein Test</div>');
    });

    it('can ignore nested elements', function() {
        $input = '<div>Das ist ein Test im <div class="ignored">Test</div></div>';
        $htmlChanger = HtmlChanger::parse($input, ['ignore' => ['.ignored'], 'search' => [
            'Test' => ['value' => 'Test'],
        ]]);

        $htmlChanger->replace(function($text, $value) {
            return '[' . $text . ']';
        });

        expect($htmlChanger->html())->toBe('<div>Das ist ein [Test] im <div class="ignored">Test</div></div>');
    });

});