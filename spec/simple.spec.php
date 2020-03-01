<?php

use html_changer\Html5Changer;

describe('HtmlChanger', function() {

    it("can handle simple code", function() {
        $input = '<div>Text</div>';
        $htmlChanger = Html5Changer::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(3);
        expect($parts[0]->type)->toBe('tag');
        expect($parts[1]->type)->toBe('text');
        expect($parts[2]->type)->toBe('tag');
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle attributes with greater signs", function() {
        $input = '<div test="yes > no">Text</div>';
        $htmlChanger = Html5Changer::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(3);
        expect($parts[0]->type)->toBe('tag');
        expect($parts[0]->code)->toBe('<div test="yes > no">');
        expect($parts[1]->type)->toBe('text');
        expect($parts[2]->type)->toBe('tag');
        expect($htmlChanger->html())->toBe($input);
    });

    it("can distinct between start and end tag", function() {
        $input = '<div>Text</div>';
        $htmlChanger = Html5Changer::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(3);
        expect($parts[0]->part)->toBe('Start');
        expect($parts[2]->part)->toBe('End');
        expect($htmlChanger->html())->toBe($input);
    });

    it("can output the tag name", function() {
        $input = '<div>Text</div>';
        $htmlChanger = Html5Changer::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(3);
        expect($parts[0]->name)->toBe('div');
        expect($parts[2]->name)->toBe('div');
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle scripts", function() {
        $code = 'if(x < y) x++;';
        $input = "<script>$code</script>";
        $htmlChanger = Html5Changer::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(3);
        expect($parts[1]->code)->toBe($code);
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle style", function() {
        $code = 'if(x < y) x++;';
        $input = "<script>$code</script>";
        $htmlChanger = Html5Changer::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(3);
        expect($parts[1]->code)->toBe($code);
        expect($htmlChanger->html())->toBe($input);
    });

});