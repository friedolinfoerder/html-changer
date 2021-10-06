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
        'Text' => [
            'search' => [],
        ],
    ];

    private $currentState;
    private $html;
    private $parts = [];
    private $chars = [];
    private $index;

    private $groups = [];

    private $tree = null;
    private $parent = null;
    private $ignore = [];
    private $ignoreStack = [];

    private $stack = [];

    /**
     * Search for exact matches
     * 
     * The array is in the following format:
     * [
     *  "keyword" => $options
     * ]
     */
    private $searchExact = [];

    /**
     * Search for case insensitive matches
     * 
     * The array is in the following format:
     * [
     *  "keyword" => $options
     * ]
     */
    private $searchCaseInsensitive = [];

    private $windowLengths = [];

    public function __construct($html, array $options = [])
    {
        $this->tree = (object)[
            'startNode' => null,
            'endNode' => null,
            'children' => [],
            'parent' => null,
        ];
        $this->parent = $this->tree;
        if(array_key_exists('ignore', $options)) {
            $this->ignore = $options['ignore'];
        }
        if(array_key_exists('only', $options)) {
            $this->only = $options['only'];
        }
        if(array_key_exists('search', $options)) {
            $searchCaseInsensitive = [];
            $searchExact = [];
            foreach($options['search'] as $key => $value) {
                $value = array_merge([
                    'group' => $key, 
                    'maxCount' => -1, 
                    'caseInsensitive' => false, 
                    'priority' => 0,
                ], $value);
                if($value['caseInsensitive']) {
                    $searchCaseInsensitive[\mb_strtolower($key)] = $value;
                } else {
                    $searchExact[$key] = $value;
                }
            }
            $this->searchCaseInsensitive = $searchCaseInsensitive;
            $this->searchExact = $searchExact;
        }
        $this->setWindowLengths();
        $this->useState(static::$STATE_TEXT);
        $this->html = $html;
        $this->setChars($html);
        $this->iterateOverCode($html);
    }

    /**
     * Parse html code and return HtmlChanger instance
     *
     * @param string $html
     * @param array $options 
     *                  ['search']
     * @return Html5Changer
     */
    public static function parse($html, array $options = [])
    {
        return new static($html, $options);
    }

    private function setWindowLengths()
    {
        $windows = [];
        foreach ($this->searchExact as $key => $value) {
            $length = \strlen($key);
            $windows[$length] = true;
        }
        foreach ($this->searchCaseInsensitive as $key => $value) {
            $length = \strlen($key);
            $windows[$length] = true;
        }
        $windows = array_keys($windows);
        \rsort($windows);
        $this->windowLengths = $windows;
    }

    private function getChar($relativePosition = 0)
    {
        $index = $this->index + $relativePosition;
        return isset($this->chars[$index]) ? $this->chars[$index] : null;
    }

    private function nextchar($string, &$pointer)
    {
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
            $str = substr($string, $pointer, $bytes);
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
        $lastPart = end($this->parts);
        if(!empty($lastPart) && $lastPart->type === 'text') {
            $this->finishText();
        }

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
        if(!empty($this->parts)) {
            $part = end($this->parts);
            if($part instanceof EndingTag) {
                $this->parent->endNode = $part;
                if($this->parent->parent) {
                    $this->parent = $this->parent->parent;
                }
                \array_pop($this->ignoreStack);
            } else {
                $obj = (object)[
                    'startNode' => $part,
                    'children' => [],
                    'parent' => $this->parent,
                ];
                $this->parent->children[] = $obj;
                if($part instanceof OpeningTag && !$part->isSelfClosing()) {
                    $this->parent = $obj;
                    if(empty($this->ignoreStack)) {
                        foreach($this->ignore as $element) {
                            if($part->is($element)) {
                                $this->ignoreStack[] = $part;
                                break;
                            }
                        }
                    } else {
                        $this->ignoreStack[] = $part;
                    }
                }
            }
        }
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
            $this->finishText();
            $this->useState(static::$STATE_TAG);
            $this->handleChar($char);
            return;
        }
        $this->consumeChar($char);
        // find keywords in text
        if(!empty($this->ignoreStack)) {
            return;
        }
        $part = end($this->parts);
        $searchResult = null;

        foreach ($this->windowLengths as $len) {
            $partLength = strlen($part->code);
            if($partLength < $len) {
                continue;
            }
            $searchTerm = substr($part->code, -$len);
            $searchObject = null;

            
            // search in exact list
            if(array_key_exists($searchTerm, $this->searchExact)) {
                $searchObject = $this->searchExact[$searchTerm];
                $searchResult = [$searchTerm, [$partLength - $len, $len], $searchObject];
            } else {
                // search in case insensitive list
                $searchTermLower = \mb_strtolower($searchTerm);
                if(array_key_exists($searchTermLower, $this->searchCaseInsensitive)) {
                    $searchObject = $this->searchCaseInsensitive[$searchTermLower];
                    $searchResult = [$searchTerm, [$partLength - $len, $len], $searchObject];
                }
            }
        
            if($searchResult) {
                $ignoreWordBoundary = array_key_exists('wordBoundary', $searchObject) && $searchObject['wordBoundary'] === false;
                
                if(!$ignoreWordBoundary) {
                    $followingChar = mb_strtolower($this->getChar(1));
                    $wordBounder = empty($followingChar) || preg_match("/^\W$/u", $followingChar);
                    
                    if(!$wordBounder) {
                        $searchObject = null;
                        $searchResult = null;
                        continue;
                    }
                    
                    $previousChar = $partLength - $len > 0 ? mb_strtolower($part->code[$partLength-$len-1]) : null;
                    $wordBounder = empty($previousChar) || preg_match("/^\W$/u", $previousChar);
                    
                    if(!$wordBounder) {
                        $searchObject = null;
                        $searchResult = null;
                        continue;
                    }
                }
                
                // has word boundary on both sides
                $group = $searchObject['group'];
                if($searchObject['maxCount'] > 0) {
                    if(!array_key_exists($group, $this->groups)) {
                        $this->groups[$group] = 0;
                    }
                    $this->groups[$group] += 1;
                }
                // add search result to current state
                $part->search[] = $searchResult;
                $searchObject = null;
                $searchResult = null;
            }
        }
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

    private function finishText() {
        $part = end($this->parts);
        $text = new Text();
        $text->parent = end($this->stack);
        $text->code = $part->code;

        // find overlays and use longer text
        usort($part->search, function($a, $b) {
            $prioritySort = $b[2]['priority'] - $a[2]['priority'];
            if($prioritySort != 0) {
                return $prioritySort;
            }
            return $b[1][1] - $a[1][1];
        });

        $part->search = array_reverse($part->search);
        foreach($part->search as $index => $textBlock) {
            $group = $textBlock[2]['group'];
            $maxCount = $textBlock[2]['maxCount'];
            if($maxCount === -1) {
                continue;
            }
            if($this->groups[$group] > $maxCount) {
                $this->groups[$group]--;
                unset($part->search[$index]);
            }
        }
        $part->search = array_reverse($part->search);

        $search = [];
        foreach($part->search as $index => $textBlock) {
            $collission = false;

            // check if there is an collission with other part
            for ($i=0; $i < $index; $i++) { 
                $otherTextBlock = $part->search[$i];
                if($textBlock[1][0] < $otherTextBlock[1][0] + $otherTextBlock[1][1] && $textBlock[1][0] + $textBlock[1][1] > $otherTextBlock[1][0]) {
                    $collission = true;
                    break;
                }
            }

            if(!$collission) {
                $search[] = $textBlock;
            }
        }


        $text->search = $search;
        $this->parts[count($this->parts)-1] = $text;

        // remove groups
        foreach ($this->searchExact as $term => $searchConfig) {
            if($searchConfig['maxCount'] === -1) {
                continue;
            }
            $groupName = $searchConfig['group'];
            $group = array_key_exists($groupName, $this->groups) ? $this->groups[$groupName] : 0;
            if($group >= $searchConfig['maxCount']) {
                unset($this->searchExact[$term]);
            }
        }
        foreach ($this->searchCaseInsensitive as $term => $searchConfig) {
            if($searchConfig['maxCount'] === -1) {
                continue;
            }
            $groupName = $searchConfig['group'];
            $group = array_key_exists($groupName, $this->groups) ? $this->groups[$groupName] : 0;
            if($group >= $searchConfig['maxCount']) {
                unset($this->searchCaseInsensitive[$term]);
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
            if(!$tag->isSelfClosing()) {
                $tag->parent = end($this->stack);
                $this->stack[] = $tag;
            }
        } else {
            $tag = new EndingTag();
            array_pop($this->stack);
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

    public function replace(callable $callable) {
        $parts = $this->parts(true);
        foreach($parts as $part) {
            $part->replace($callable);
        }
        return $this;
    }

    public function html()
    {
        return join('', array_map(function($part) {
            return $part->code;
        }, $this->parts));
    }
}
