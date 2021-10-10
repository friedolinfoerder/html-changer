<?php

namespace html_changer;

class Text implements HtmlPart {

    public $type = 'text';
    public $code = '';
    public $search = [];
    public $parent = null;

    public function getType() {
        return $this->type;
    }

    public function getCode() {
        return $this->code;
    }

    public function replace(callable $replacer) {
        usort($this->search, function($a, $b) {
            return $b[1][0] - $a[1][0];
        });
        foreach ($this->search as $value) {
            $newText = call_user_func($replacer, $value[0], $value[2]['value'], $this);
            $this->code = \substr($this->code, 0, $value[1][0]) . $newText . \substr($this->code, $value[1][0] + $value[1][1]);
        }

        $this->search = [];
    }

    public function getParent() {
        return $this->parent;
    }

    public function match($options, array $settings = []) {
        return $this->getParent()->match($options, $settings);
    }

}