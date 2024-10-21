<?php

namespace AlingsasCustomisation\Components\Event;

class Event extends \ComponentLibrary\Component\BaseController
{
    public function init()
    {
        //Extract array for eazy access (fetch only)
        extract($this->data);
    }
}
