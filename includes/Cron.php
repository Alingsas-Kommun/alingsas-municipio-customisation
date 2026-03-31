<?php

namespace AlingsasCustomisation\Includes;

class Cron {
    public function __construct() {
        // Schedule cron jobs
        new Cron\Announcement();
        new Cron\News();
        new Cron\DeleteUnusedImages();
        new Cron\DeleteUnusedPdfs();
        new Cron\FindUnusedImages();
        new Cron\FindUnusedPdfs();
        new Cron\CheckUnusedPdfs();
        new Cron\MarkUnusedPdfs();
    }
}
