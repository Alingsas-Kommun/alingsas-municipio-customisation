<?php

namespace AlingsasCustomisation\Decorators;

use Municipio\PostObject\PostObjectInterface;
use Municipio\PostObject\Decorators\AbstractPostObjectDecorator;

/**
 * HideTitlePostObject
 *
 * Decorator that conditionally hides the post title (returns empty string) based on a flag.
 */
class HideTitlePostObject extends AbstractPostObjectDecorator implements PostObjectInterface {
    /** @var bool */
    private bool $shouldHide;

    public function __construct(PostObjectInterface $postObject, bool $shouldHide) {
        parent::__construct($postObject);
        $this->shouldHide = $shouldHide;
    }

    /**
     * Return empty title if we should hide it.
     */
    public function getTitle(): string {
        if ($this->shouldHide) {
            return '';
        }

        return $this->postObject->getTitle();
    }
}
