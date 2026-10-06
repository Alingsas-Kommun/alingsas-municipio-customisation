<?php

namespace AlingsasCustomisation\Includes;

/**
 * Driftinformation posts show "Publicerad <date>, uppdaterad <date>" as their
 * archive date (e.g. on cards in the Posts module), next to the date icon.
 * Whether a date is shown at all is still controlled in the module settings.
 */
class DriftInformation
{
    private const POST_TYPE = 'driftinformation';

    public function __construct()
    {
        add_filter('Municipio/DecoratePostObject', [$this, 'decorate']);
    }

    public function decorate($postObject)
    {
        if (!is_object($postObject) || !method_exists($postObject, 'getPostType') || $postObject->getPostType() !== self::POST_TYPE) {
            return $postObject;
        }

        if (!class_exists('\\AlingsasCustomisation\\Decorators\\DriftInformationDatePostObject')) {
            require_once __DIR__ . '/../src/Decorators/DriftInformationDatePostObject.php';
        }

        return new \AlingsasCustomisation\Decorators\DriftInformationDatePostObject($postObject);
    }
}
