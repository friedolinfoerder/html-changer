<?php

use html_changer\HtmlChanger;

describe('HtmlChanger', function() {

    it("can handle tag selector", function() {
        $input = "<div>";
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(1);
        expect($parts[0]->is('div'))->toBe(true);
        expect($parts[0]->is('a'))->toBe(false);
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle class selector", function() {
        $input = "<div class='this is a test'>";
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(1);
        expect($parts[0]->is('.this'))->toBe(true);
        expect($parts[0]->is('.is'))->toBe(true);
        expect($parts[0]->is('.a'))->toBe(true);
        expect($parts[0]->is('.test'))->toBe(true);
        expect($parts[0]->is('.wrong'))->toBe(false);
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle id selector", function() {
        $input = "<div id='super'>";
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(1);
        expect($parts[0]->is('#super'))->toBe(true);
        expect($parts[0]->is('#wrong'))->toBe(false);
        expect($htmlChanger->html())->toBe($input);
    });

});