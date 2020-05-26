<?php

namespace html_changer;

class Text implements HtmlPart {

    public $type = 'text';
    public $code = '';
    public $search = [];

    public function getType() {
        return $this->type;
    }

    public function getCode() {
        return $this->code;
    }

    public function replace(callable $replacer) {
        $list = array_reverse($this->search);
        foreach ($list as $value) {
            $newText = $replacer($value[0], $value[2]);
            $this->code = \substr($this->code, 0, $value[1][0]) . $newText . \substr($this->code, $value[1][0] + $value[1][1]);
        }

        $this->search = [];
    }

}