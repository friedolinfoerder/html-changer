<?php

namespace html_changer;


class HtmlChanger
{
    private static $STATE_TEXT = 'Text';
    private static $STATE_TAG = 'Tag';
    private static $STATE_SCRIPT = 'Script';
    private static $STATE_STYLE = 'Style';

    private static $VOID_TAGS = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
    ];

    private $defaultData = [
        'Script' => [
            'attributeChar' => null,
        ],
        'Style' => [
            'attributeChar' => null,
        ],
        'Tag' => [
            'name' => '',
            'part' => 'Start',
            'attributeChar' => null,
            'attributeKey' => '',
            'attributeValue' => '',
            'attributes' => [],
            'selfclosing' => false
        ],
    ];

    private $currentState;
    private $html;
    private $parts = [];
    private $chars = [];
    private $index;

    public function __construct($html)
    {
        $this->useState(static::$STATE_TEXT);
        $this->html = $html;
        $this->setChars($html);
        $this->iterateOverCode($html);
    }

    /**
     * Parse html code and return HtmlChanger instance
     *
     * @param $html
     * @return Html5Changer
     */
    public static function parse($html)
    {
        return new static($html);
    }

    private function getChar($relativePosition = 0)
    {
        $index = $this->index + $relativePosition;
        return isset($this->chars[$index]) ? $this->chars[$index] : null;
    }

    private function nextchar($string, &$pointer){
        if(!isset($string[$pointer])) return false;
        $char = ord($string[$pointer]);
        if($char < 128){
            return $string[$pointer++];
        }else{
            if($char < 224){
                $bytes = 2;
            }elseif($char < 240){
                $bytes = 3;
            }elseif($char < 248){
                $bytes = 4;
            }elseif($char == 252){
                $bytes = 5;
            }else{
                $bytes = 6;
            }
            $str =  substr($string, $pointer, $bytes);
            $pointer += $bytes;
            return $str;
        }
    }

    private function setChars($html)
    {
        // old method (very memory consuming)
        // $this->chars = preg_split('//u', $html, -1, PREG_SPLIT_NO_EMPTY);

        // use more efficient method
        $pointer = 0;
        while(($chr = $this->nextchar($html, $pointer)) !== false) {
            $this->chars[] = $chr;
        }
    }

    private function iterateOverCode($html)
    {
        // loop over chars and parse html
        foreach ($this->chars as $index => $char) {
            $this->index = $index;
            $this->handleChar($char);
        }
        // we don't need the chars array anymore
        $this->chars = null;

        // filter out empty parts
        $this->parts = array_values(array_filter($this->parts, function($part) {
            return $part->code !== '';
        }));

        // fluent interface
        return $this;
    }

    private function handleChar($char)
    {
        $this->{'handleCharInState' . $this->currentState}($char);
    }

    private function consumeChar($char)
    {
        $part = end($this->parts);
        $part->code .= $char;
        $part->length += 1;
    }

    private function useState($state)
    {
        $this->currentState = $state;
        $this->parts[] = (object)array_merge([
            'type' => strtolower($this->currentState),
            'code' => '',
            'length' => 0,
            'state' => 'start',
        ], isset($this->defaultData[$state]) ? $this->defaultData[$state] : []);
    }

    private function handleCharInStateStyle($char)
    {
        return $this->handleCharInStateScript($char);
    }

    private function handleCharInStateScript($char)
    {
        $part = end($this->parts);

        if($part->attributeChar) {
            if($char === $part->attributeChar) {
                $part->attributeChar = null;
            }
        } elseif ($char === '"' || $char === "'") {
            $part->attributeChar = $char;
        } elseif($char === '<'  && $this->getChar(1) === '/') {
            $this->useState(static::$STATE_TAG);
            $this->handleChar($char);
            return;
        }
        $this->consumeChar($char);
    }

    private function handleCharInStateText($char)
    {
        if($char === '<') {
            $this->useState(static::$STATE_TAG);
            $this->handleChar($char);
            return;
        }
        $this->consumeChar($char);
    }

    private function handleCharInStateTag($char)
    {
        $part = end($this->parts);
        $this->consumeChar($char);
        if($part->length === 1) {
            return;
        }
        if($part->length === 2) {
            $part->state = 'name';
            if($char === '/') {
                $part->part = 'End';
                return;
            } else {
                $part->part = 'Start';
            }
        }

        if($part->state === 'name') {
            if($char !== ' ' && $char !== '>') {
                $part->name .= $char;
                return;
            } else {
                $part->state = 'attributes.key';
            }
        }

        if($part->state === 'attributes.key') {
            if($char === '>') {
                $this->finishTag();
                return;
            }
            if($char === '=') {
                $part->state = 'attributes.value';
                return;
            } 
            if($char === '/') {
                $part->selfclosing = true;
            }
            if($char === '/' || $char !== ' ' && $this->getChar(-1) === ' ') {
                $this->finishAttribute();
            }
            $part->attributeKey .= $char;
            return;
        }

        if($part->state === 'attributes.value') {
            if(!$part->attributeChar) {
                if($char === ' ') {
                    return;
                }
                if ($char === '"' || $char === "'") {
                    $part->attributeChar = $char;
                    return;
                } else {
                    $part->attributeChar = ' ';
                }
            }
            if($part->attributeChar) {
                if($char === $part->attributeChar) {
                    $part->attributeChar = null;
                    
                    $this->finishAttribute();
                    return;
                }
                if($part->attributeChar === ' ' && $char === '>') {
                    $this->finishAttribute();
                    $this->finishTag();
                    return;
                }
                $part->attributeValue .= $char;
                return;
            } 
        }
    }

    private function finishAttribute() {
        $part = end($this->parts);
        $key = strtolower(trim($part->attributeKey));
        if(!empty($key)) {
            $part->attributes[$key] = $part->attributeValue;
        }
        $part->attributeKey = '';
        $part->attributeValue = '';
        $part->state = 'attributes.key';
    }

    private function finishTag() {
        $part = end($this->parts);
        // use class for tag
        if($part->part === 'Start') {
            $tag = new OpeningTag();
            $tag->selfclosing = $part->selfclosing || in_array(strtolower($part->name), static::$VOID_TAGS);
        } else {
            $tag = new EndingTag();
        }
        $tag->name = strtolower($part->name);
        $tag->code = $part->code;
        $tag->attributes = $part->attributes;
        $this->parts[count($this->parts)-1] = $tag;

        if(strtolower($part->name) === 'script' && $part->part === 'Start') {
            $this->useState(static::$STATE_SCRIPT);
        } elseif(strtolower($part->name) === 'style' && $part->part === 'Start') {
            $this->useState(static::$STATE_STYLE);
        } else {
            $this->useState(static::$STATE_TEXT);
        }
    }

    // PUBLIC METHODS

    public function parts($onlyText = false, array $excludeElements = null)
    {
        if(!empty($excludeElements)) {
            $nodes = [];
            $addNodes = function($children) use(&$nodes, $excludeElements, $onlyText, &$addNodes) {
                foreach($children as $child) {
                    $ignore = false;
                    $type = $child->startNode->type;
                    if($type === 'tag') {
                        foreach($excludeElements as $element) {
                            if($child->startNode->is($element)) {
                                $ignore = true;
                                break;
                            }
                        }
                    }
                    if($ignore) {
                        continue;
                    }
                    if(!$onlyText || $child->startNode->type === 'text') {
                        $nodes[] = $child->startNode;
                    }
                    $addNodes($child->children);
                    if(!$onlyText && $child->endNode) {
                        $nodes[] = $child->endNode;
                    }
                }
            };
            $addNodes($this->tree());
            return $nodes;
        }
        if($onlyText) {
            return array_values(array_filter($this->parts, function($part) {
                return $part->type === 'text';
            }));
        }
        return $this->parts;
    }

    public function tree()
    {
        $tree = (object)[
            'startNode' => null,
            'endNode' => null,
            'children' => [],
            'parent' => null,
        ];
        $parent = $tree;
        foreach ($this->parts as $key => $part) {
            if($part instanceof EndingTag) {
                $parent->endNode = $part;
                if($parent->parent) {
                    $parent = $parent->parent;
                }
                continue;
            }
            $obj = (object)[
                'startNode' => $part,
                'children' => [],
                'parent' => $parent,
            ];
            $parent->children[] = $obj;
            if($part instanceof OpeningTag && !$part->isSelfClosing()) {
                $parent = $obj;
                continue;
            }
        }
        return $tree->children;
    }

    public function html()
    {
        return join('', array_map(function($part) {
            return $part->code;
        }, $this->parts));
    }
}
