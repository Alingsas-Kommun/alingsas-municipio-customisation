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
                'value' => is_scalar($jobStartDate) ? (string) $jobStartDate : wp_json_encode($jobStartDate),
            ];
        }


        $workHours = $post->getSchemaProperty('workHours');
        if ($workHours !== null && $workHours !== '') {
            $viewData['informationList'][] = [
                'label' => __('Anställningsform', 'municipio-customisation'),
                'value' => is_scalar($workHours) ? (string) $workHours : wp_json_encode($workHours),
            ];
        }

        $jobDuration = $post->getSchemaProperty('jobDuration');
        if ($jobDuration !== null && $jobDuration !== '') {
            $viewData['informationList'][] = [
                'label' => __('Anställningsperiod', 'municipio-customisation'),
                'value' => is_scalar($jobDuration) ? (string) $jobDuration : wp_json_encode($jobDuration),
            ];
        }

        return $viewData;
    }
}
