<?php

namespace AlingsasCustomisation\Includes;

/**
 * Miscellaneous WordPress hooks.
 */
class Hooks {
    public function __construct() {
        add_action('admin_notices', function () {
            $screen = get_current_screen();
            if (!$screen || $screen->post_type !== 'anslagstavla' || $screen->base !== 'post') {
                return;
            }

            echo '<div class="notice notice-warning" style="border-left-color: #d63638; background: #fcf0f1;">
                <p style="font-size: 14px;">
                    <strong>⚠️ Viktigt:</strong> Anslaget ska namnges enligt principen <strong>[instans], protokoll DD månad ÅÅÅÅ</strong>.<br>
                    Exempel: <strong>Kommunstyrelsen, protokoll 16 januari 2026</strong>
                </p>
            </div>';
        });

        add_filter('Municipio/Template/lediga-jobb/single/viewData', [$this, 'appendExtraDataToJobPosting'], 10, 1);
    }

    /**
     * Add JobPosting schema fields to the sidebar information list (SingularJobPosting).
     *
     * @param array<string, mixed> $viewData Municipio single view data.
     * @return array<string, mixed>
     */
    public function appendExtraDataToJobPosting(array $viewData): array {
        $post = $viewData['post'] ?? null;
        if (!is_object($post) || !method_exists($post, 'getSchemaProperty')) {
            return $viewData;
        }

        $readMoreUrl = $post->getSchemaProperty('readMoreUrl');
        if ($readMoreUrl !== null && $readMoreUrl !== '') {
            $viewData['readMoreUrl'] = $readMoreUrl;
        }

        $jobStartDate = $post->getSchemaProperty('jobStartDate');
        if ($jobStartDate !== null && $jobStartDate !== '') {
            $viewData['informationList'][] = [
                'label' => __('Anställningsstart', 'municipio-customisation'),
                'value' => $this->formatJobInformationValue($jobStartDate),
            ];
        }

        $workHours = $post->getSchemaProperty('workHours');
        if ($workHours !== null && $workHours !== '') {
            $viewData['informationList'][] = [
                'label' => __('Anställningsform', 'municipio-customisation'),
                'value' => $this->formatJobInformationValue($workHours),
            ];
        }

        $jobDuration = $post->getSchemaProperty('jobDuration');
        if ($jobDuration !== null && $jobDuration !== '') {
            $viewData['informationList'][] = [
                'label' => __('Anställningsperiod', 'municipio-customisation'),
                'value' => $this->formatJobInformationValue($jobDuration),
            ];
        }

        return $viewData;
    }

    /**
     * Format a job schema value for the sidebar information list.
     *
     * DateTime values are rendered as Y-m-d. Strings are returned unchanged.
     *
     * @param mixed $value Schema property from the job posting.
     */
    private function formatJobInformationValue(mixed $value): string {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = wp_json_encode($value);

        return is_string($encoded) ? $encoded : '';
    }
}
