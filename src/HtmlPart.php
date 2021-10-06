<?php

namespace html_changer;

interface HtmlPart {

    public function getType();
    public function getCode();
    public function getParent();

}