<?php

namespace AlingsasCustomisation\Includes;

class Cron {
    public function __construct() {
        // Schedule cron jobs
        new Cron\Announcement();
        new Cron\DeleteUnusedImages();
        new Cron\FindUnusedImages();
    }
}
