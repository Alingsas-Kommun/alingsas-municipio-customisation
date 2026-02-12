<?php

namespace AlingsasCustomisation\Includes;

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
    }
}
