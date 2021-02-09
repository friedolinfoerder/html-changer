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

    it("can overwrite sorting via priority (1)", function() {
        $input = "<div>Ändern oder unterstützen</div>";
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'ändern' => ['value' => 'Change', 'caseInsensitive' => true, 'priority' => 1],
            'Ändern oder unterstützen' => ['value' => 'Support', 'caseInsensitive' => true],
        ]]);
        $elements = $htmlChanger->parts(true);

        $elements[0]->replace(function($text, $value) {
            return '[' . $text . ']';
        });

        expect($htmlChanger->html())->toBe('<div>[Ändern] oder unterstützen</div>');
    });

    it("can overwrite sorting via priority (2)", function() {
        $input = "<div>Ändern oder unterstützen</div>";
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'unterstützen' => ['value' => 'Change', 'caseInsensitive' => true, 'priority' => 1],
            'Ändern oder unterstützen' => ['value' => 'Support', 'caseInsensitive' => true],
        ]]);
        $elements = $htmlChanger->parts(true);

        $elements[0]->replace(function($text, $value) {
            return '[' . $text . ']';
        });

        expect($htmlChanger->html())->toBe('<div>Ändern oder [unterstützen]</div>');
    });

    it("can use longer replacement with maxCount 1", function() {
        $input = "<div>Ändern oder unterstützen</div>";
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'ändern' => ['value' => 'Change', 'caseInsensitive' => true, 'maxCount' => 1, 'group' => '1'],
            'Ändern oder unterstützen' => ['value' => 'Support', 'caseInsensitive' => true, 'maxCount' => 1, 'group' => '1'],
        ]]);
        $elements = $htmlChanger->parts(true);

        $elements[0]->replace(function($text, $value) {
            return '[' . $text . ']';
        });

        expect($htmlChanger->html())->toBe('<div>[Ändern oder unterstützen]</div>');
    });

    it("can use word boundaries (1)", function() {
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

    it("can use word boundaries (2)", function() {
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

    it("can use word boundaries (3)", function() {
        $input = "<div>germanų</div>";
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'german' => ['value' => 'german', 'wordBoundary' => true],
        ]]);
        $elements = $htmlChanger->parts(true);

        $elements[0]->replace(function($text, $value) {
            return '[' . $text . ']';
        });

        expect($htmlChanger->html())->toBe('<div>germanų</div>');
    });

    it("can use word boundaries (4)", function() {
        $input = "<div>Some random ąčšū, čšū.</div>";
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'ąčšū' => ['value' => 'german', 'wordBoundary' => true],
            'čš' => ['value' => 'german', 'wordBoundary' => true],
            ]]);
        $elements = $htmlChanger->parts(true);

        $elements[0]->replace(function($text, $value) {
            return '[' . $text . ']';
        });

        expect($htmlChanger->html())->toBe('<div>Some random [ąčšū], čšū.</div>');
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

        expect($htmlChanger->html())->toBe('<div>und [oder] und</div>');
    });

    it("can handle max count with groups and multiple elements", function() {
        $input = "<div>und<span>oder</span>und</div>";
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'und' => ['value' => 'Test', 'maxCount' => 1, 'group' => 1],
            'oder' => ['value' => 'Test', 'maxCount' => 1, 'group' => 1],
        ]]);
        $elements = $htmlChanger->parts(true);

        $elements[0]->replace(function($text, $value) {
            return '[' . $text . ']';
        });

        expect($htmlChanger->html())->toBe('<div>[und]<span>oder</span>und</div>');
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

    it('can ignore all', function() {
        $input = '<div class="ignored">Test</div>';
        $htmlChanger = HtmlChanger::parse($input, ['ignore' => ['.ignored'], 'search' => [
            'Test' => ['value' => 'Test'],
        ]]);

        $htmlChanger->replace(function($text, $value) {
            return '[' . $text . ']';
        });

        expect($htmlChanger->html())->toBe('<div class="ignored">Test</div>');
    });

    // it('can replace persian words', function() {
    //     $input = 'اپلیکیشن';
    //     $htmlChanger = HtmlChanger::parse($input, ['search' => [
    //         'کیشن' => ['value' => 'Test', 'wordBoundary' => true],
    //     ]]);

    //     $htmlChanger->replace(function($text, $value) {
    //         return '[' . $text . ']';
    //     });

    //     expect($htmlChanger->html())->toBe('[اپلی[کیشن');
    // });

    it('can replace polish words', function() {
        $input = '<p>superwszechświatów</p><p>Jetzt das polnische Wort superwszechświat </p>';
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'superwszechświatów' => ['value' => 'Test'],
            'superwszechświat' => ['value' => 'Test'],
        ]]);

        $htmlChanger->replace(function($text, $value) {
            return '[' . $text . ']';
        });

        expect($htmlChanger->html())->toBe('<p>[superwszechświatów]</p><p>Jetzt das polnische Wort [superwszechświat] </p>');
    });

});