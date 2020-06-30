<?php

use html_changer\HtmlChanger;
use html_changer\Text;

describe('HtmlChanger', function() {

    it("can handle only text", function() {
        $input = 'Text';
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(1);
        expect($parts[0]->type)->toBe('text');
        expect($parts[0])->toBeAnInstanceOf('html_changer\\Text');
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle rich text", function() {
        $input = 'Text with <b>html</b> elements.';
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(5);
        expect($parts[0])->toBeAnInstanceOf('html_changer\\Text');
        expect($parts[1])->toBeAnInstanceOf('html_changer\\OpeningTag');
        expect($parts[2])->toBeAnInstanceOf('html_changer\\Text');
        expect($parts[3])->toBeAnInstanceOf('html_changer\\EndingTag');
        expect($parts[4])->toBeAnInstanceOf('html_changer\\Text');
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle simple code", function() {
        $input = '<div>Text</div>';
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(3);
        expect($parts[0]->type)->toBe('tag');
        expect($parts[1]->type)->toBe('text');
        expect($parts[2]->type)->toBe('tag');
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle attributes with greater signs", function() {
        $input = '<div test="yes > no">Text</div>';
        $htmlChanger = HtmlChanger::parse($input);
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
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(3);
        expect($parts[0]->part)->toBe('Start');
        expect($parts[2]->part)->toBe('End');
        expect($htmlChanger->html())->toBe($input);
    });

    it("can output the tag name", function() {
        $input = '<div>Text</div>';
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(3);
        expect($parts[0]->name)->toBe('div');
        expect($parts[2]->name)->toBe('div');
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle scripts", function() {
        $code = 'if(x < y) x++;';
        $input = "<script>$code</script>";
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(3);
        expect($parts[1]->code)->toBe($code);
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle style", function() {
        $code = 'if(x < y) x++;';
        $input = "<script>$code</script>";
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(3);
        expect($parts[1]->code)->toBe($code);
        expect($htmlChanger->html())->toBe($input);
    });

    it("can handle empty input", function() {
        $input = "";
        $htmlChanger = HtmlChanger::parse($input);
        $parts = $htmlChanger->parts();

        expect(count($parts))->toBe(0);
        expect($htmlChanger->html())->toBe($input);
    });
});