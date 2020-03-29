<?php

use html_changer\HtmlChanger;

describe('HtmlChanger', function() {

    it("can build tree", function() {
        $input = "<div></div>";
        $htmlChanger = HtmlChanger::parse($input);
        $elements = $htmlChanger->tree();

        expect($elements)->toBeA('array')->toHaveLength(1);
        expect($elements[0]->startNode->type)->toBe('tag');
        expect($elements[0]->startNode->part)->toBe('Start');
        expect($elements[0]->children)->toBeEmpty();
        expect($elements[0]->endNode->type)->toBe('tag');
        expect($elements[0]->endNode->part)->toBe('End');

        expect($htmlChanger->parts(true))->toBeEmpty();
    });

    it("can return text elements", function() {
        $input = "<div>hello</div><span>world</span>";
        $htmlChanger = HtmlChanger::parse($input);

        $textParts = $htmlChanger->parts(true);
        expect($textParts)->toHaveLength(2);
        expect($textParts[0]->type)->toBe('text');
        expect($textParts[0]->code)->toBe('hello');
        expect($textParts[1]->type)->toBe('text');
        expect($textParts[1]->code)->toBe('world');
    });

    it("can return text elements in hierachical tree", function() {
        $input = "<div>h<span>el</span>lo</div>";
        $htmlChanger = HtmlChanger::parse($input);

        $textParts = $htmlChanger->parts(true);
        expect($textParts)->toHaveLength(3);
        expect($textParts[0]->type)->toBe('text');
        expect($textParts[0]->code)->toBe('h');
        expect($textParts[1]->type)->toBe('text');
        expect($textParts[1]->code)->toBe('el');
        expect($textParts[2]->type)->toBe('text');
        expect($textParts[2]->code)->toBe('lo');
    });

    it("can return text without excluded elements", function() {
        $input = "<div>h<span class='not'>el</span>lo</div>";
        $htmlChanger = HtmlChanger::parse($input);

        $textParts = $htmlChanger->parts(true, ['.not']);
        expect($textParts)->toHaveLength(2);
        expect($textParts[0]->type)->toBe('text');
        expect($textParts[0]->code)->toBe('h');
        expect($textParts[1]->type)->toBe('text');
        expect($textParts[1]->code)->toBe('lo');
    });

});