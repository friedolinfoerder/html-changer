<?php

use html_changer\HtmlChanger;

describe('HtmlChanger', function() {

    it("can handle standalone attribute", function() {
        $input = '<div class />';
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(1);
        expect($parts[0]->type)->toBe('tag');
        expect($parts[0]->getAttribute('class'))->toBe('');
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle simple attribute", function() {
        $input = '<div class="test" />';
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(1);
        expect($parts[0]->type)->toBe('tag');
        expect($parts[0]->getAttribute('class'))->toBe('test');
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle attribute with whitespaces", function() {
        $input = '<div class = "test" />';
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(1);
        expect($parts[0]->type)->toBe('tag');
        expect($parts[0]->getAttribute('class'))->toBe('test');
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle attribute without whitespaces", function() {
        $input = '<div class=test>';
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(1);
        expect($parts[0]->type)->toBe('tag');
        expect($parts[0]->getAttribute('class'))->toBe('test');
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle multiple attributes", function() {
        $input = '<div id=2 CLASS="test" data-input=\'34\'>';
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(1);
        expect($parts[0]->type)->toBe('tag');
        expect($parts[0]->getAttribute('ID'))->toBe('2');
        expect($parts[0]->getAttribute('class'))->toBe('test');
        expect($parts[0]->getAttribute('data-input'))->toBe('34');
        expect($htmlChanger->html())->toBe($input);
    });

});