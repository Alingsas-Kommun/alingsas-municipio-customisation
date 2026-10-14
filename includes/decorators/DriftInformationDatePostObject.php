<?php

namespace AlingsasCustomisation\Decorators;

use Municipio\PostObject\Decorators\AbstractPostObjectDecorator;

/**
 * Makes the archive date of a post read "Publicerad <date>, uppdaterad <date>".
 *
 * The date component only takes one timestamp and a PHP date format, so the whole
 * text is built up front and handed over as a fully escaped format string, which
 * date_i18n() prints literally.
 */
class DriftInformationDatePostObject extends AbstractPostObjectDecorator
{
    public function getArchiveDateTimestamp(): ?int
    {
        return $this->getTimestamp('date');
    }

    public function getArchiveDateFormat(): string
    {
        $dateFormat = get_option('date_format') ?: 'Y-m-d';
        $published  = wp_date($dateFormat, $this->getTimestamp('date'));
        $modified   = wp_date($dateFormat, $this->getTimestamp('modified'));

        $text = $published === $modified
            ? sprintf(__('Publicerad %s', 'municipio-customisation'), $published)
            : sprintf(__('Publicerad %s, uppdaterad %s', 'municipio-customisation'), $published, $modified);

        return preg_replace('/./su', '\\\\$0', $text);
    }

    private function getTimestamp(string $field): ?int
    {
        return get_post_timestamp($this->getId(), $field) ?: null;
    }
}
