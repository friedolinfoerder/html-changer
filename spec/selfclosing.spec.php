<?php

use html_changer\HtmlChanger;

describe('HtmlChanger', function() {

    it("can handle none self closing", function() {
        $input = "<div>";
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(1);
        expect($parts[0]->isSelfClosing())->toBe(false);
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle self closing", function() {
        $input = "<div />";
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(1);
        expect($parts[0]->isSelfClosing())->toBe(true);
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle void tags", function() {
        $input = "<img>";
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(1);
        expect($parts[0]->isSelfClosing())->toBe(true);
        expect($htmlChanger->html())->toBe($input);
    });

});