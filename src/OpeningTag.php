<?php

namespace html_changer;

class OpeningTag implements HtmlPart {

    public $type = 'tag';
    public $name = '';
    public $part = 'Start';
    public $code = '';
    public $selfclosing = false;

    public $attributes = [];

    public function getType() {
        return $this->type;
    }

    public function getCode() {
        return $this->code;
    }

    public function getAttribute($name) {
        $name = strtolower($name);
        if(!array_key_exists($name, $this->attributes)) {
            return null;
        }
        return $this->attributes[$name];
    }

    public function isSelfClosing() {
        return $this->selfclosing;
    }

    public function is($selector) {
        $tagMatches = [];
        preg_match('/^[^.#\[\]]+/', $selector, $tagMatches);
        $tag = isset($tagMatches[0]) ? $tagMatches[0] : null;

        if($tag && $tag !== $this->name) {
            return false;
        }

        $idMatches = [];
        preg_match('/#([^.#\[\]]+)/', $selector, $idMatches);
        $id = isset($idMatches[1]) ? $idMatches[1] : null;

        if($id && $id !== $this->getAttribute('id')) {
            return false;
        }

        $classMatches = [];
        preg_match_all('/\.([^.#\[\]]+)/', $selector, $classMatches);
        $requiredClasses = $classMatches[1];

        if(count($requiredClasses) > 0) {
            $availableClassAttribute = $this->getAttribute('class');
            if(!$availableClassAttribute) {
                return false;
            }
            $availableClasses = preg_split('/\s+/', $availableClassAttribute);
            $availableClasses = array_flip($availableClasses);
            foreach ($requiredClasses as $class) {
                if(!array_key_exists($class, $availableClasses)) {
                    return false;
                }
            }
        }

        return true;
    }

}