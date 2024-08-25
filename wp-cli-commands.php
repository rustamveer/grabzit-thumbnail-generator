<?php

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('grabzit_process_entries', function($args, $assoc_args) {
        $form_id = $args[0] ?? null;
        $batch_size = $args[1] ?? 3;

        if (!$form_id) {
            WP_CLI::error("Form ID is required.");
            return;
        }

        $processor = new Grabzit_Thumbnail_Generator();
        $processor->trigger_update_for_all_entries($form_id, $batch_size);

        WP_CLI::success("Processing completed for form ID {$form_id}.");
    });
}

