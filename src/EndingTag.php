<?php

namespace html_changer;

class EndingTag implements HtmlPart {

    public $type = 'tag';
    public $name = '';
    public $part = 'End';
    public $code = '';

    public function getType() {
        return $this->type;
    }

    public function getCode() {
        return $this->code;
    }

}