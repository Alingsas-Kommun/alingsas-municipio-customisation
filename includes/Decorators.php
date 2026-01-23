<?php

namespace AlingsasCustomisation\Includes;

class Decorators
{
    public function __construct()
    {
        //add_filter('Municipio/DecoratePostObject', [$this, 'attachNoticeboardDecorator'], 10, 1);
        add_filter('template_redirect', [$this, 'maybeHidePostTitle'], 20);
    }

    public function maybeHidePostTitle()
    {
        if (is_singular() && get_field('hide_title')) {
            // Make sure decorator class is available (in case Composer autoload not active for plugin src)
            if (!class_exists('\\AlingsasCustomisation\\Decorators\\HideTitlePostObject')) {
                require_once __DIR__ . '/../src/Decorators/HideTitlePostObject.php';
            }

            // Decorate the PostObject so getTitle() returns empty, hiding the H1 in templates
            add_filter('Municipio/DecoratePostObject', function ($postObject) {
                if (method_exists($postObject, 'getId')) {
                    $postId = $postObject->getId();
                    if ($postId && get_field('hide_title', $postId)) {
                        $postObject = new \AlingsasCustomisation\Decorators\HideTitlePostObject($postObject, true);
                    }
                }
                return $postObject;
            }, 20);
        }
    }
}
