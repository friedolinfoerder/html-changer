<?php

namespace html_changer;

class OpeningTag implements HtmlPart {

    public $type = 'tag';
    public $name = '';
    public $part = 'Start';
    public $code = '';

    public $attributes = [];

    public function getType() {
        return $this->type;
    }

    public function getCode() {
        return $this->code;
    }

    public function getAttribute($name) {
        if(!array_key_exists($name, $this->attributes)) {
            return null;
        }
        return $this->attributes[$name];
    }

}