<?php

use html_changer\HtmlChanger;

describe('HtmlChanger', function() {

    it("can use match in replace call", function() {
        $input = '<div id="start"><span>Test</span></div>';
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'Test' => ['value' => 'Test'],
        ]]);
        $htmlChanger->replace(function($text, $value, $node) {
            if($node->match('#start')) {
                return "[$text]";
            } else {
                return $text;
            }
        });

        expect($htmlChanger->html())->toBe('<div id="start"><span>[Test]</span></div>');
    });

    it("can use match with negation", function() {
        $input = '<div id="start"><span>Test</span></div>';
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'Test' => ['value' => 'Test'],
        ]]);
        $htmlChanger->replace(function($text, $value, $node) {
            if($node->match('!#start')) {
                return "[$text]";
            } else {
                return "/$text/";
            }
        });

        expect($htmlChanger->html())->toBe('<div id="start"><span>/Test/</span></div>');
    });

    it("can use match with array", function() {
        $input = '<div id="start"><span>Test</span></div>';
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'Test' => ['value' => 'Test'],
        ]]);
        $htmlChanger->replace(function($text, $value, $node) {
            if($node->match(['#start', 'span'])) {
                return "[$text]";
            } else {
                return $text;
            }
        });

        expect($htmlChanger->html())->toBe('<div id="start"><span>[Test]</span></div>');
    });

    it("can use match with multidimensional array", function() {
        $input = '<div id="start"><span>Test</span></div>';
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'Test' => ['value' => 'Test'],
        ]]);
        $htmlChanger->replace(function($text, $value, $node) {
            if($node->match(['#start', ['.not', 'span']])) {
                return "[$text]";
            } else {
                return $text;
            }
        });

        expect($htmlChanger->html())->toBe('<div id="start"><span>[Test]</span></div>');
    });

    it("can use match with multiple setting", function() {
        $input = '<div id="start"><span>Test</span></div>';
        $htmlChanger = HtmlChanger::parse($input, ['search' => [
            'Test' => ['value' => 'Test'],
        ]]);
        $htmlChanger->replace(function($text, $value, $node) {
            if($node->match(['#start', ['.not', 'span']], ['multiple' => true])) {
                return "[$text]";
            } else {
                return $text;
            }
        });

        expect($htmlChanger->html())->toBe('<div id="start"><span>[Test]</span></div>');
    });

});