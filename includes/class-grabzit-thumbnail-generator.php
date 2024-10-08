<?php

use GrabzIt\GrabzItClient;
use GrabzIt\GrabzItException;

class Grabzit_Thumbnail_Generator
{
    private $api_key;
    private $api_secret;

    public function __construct()
    {
        $this->api_key = defined('GRABZIT_API_KEY') ? GRABZIT_API_KEY : '';
        $this->api_secret = defined('GRABZIT_API_SECRET') ? GRABZIT_API_SECRET : '';
    }
    
    public function init()
    {
        add_action('frm_after_create_entry', array($this, 'generate_thumbnail_and_save'), 10, 2);
        add_action('frm_after_update_entry', array($this, 'generate_thumbnail_and_save'), 10, 2);
    }

    public function generate_thumbnail_and_save($entry_id, $form_id)
    {
        $video_field_id = 196;  // video field ID
        $thumbnail_field_id = 637;  // thumbnail field ID

        $entry = FrmEntry::getOne($entry_id, true);
        $file_id = isset($entry->metas[$video_field_id]) ? $entry->metas[$video_field_id] : '';
        $video_url = wp_get_attachment_url($file_id);

        // send email with the video URL and IDs
        $this->send_meta_value_email($video_url, $entry_id, $video_field_id);

        if ($video_url) {
            $this->log("Video URL: $video_url");

            $thumbnail_path = $this->generate_thumbnail($video_url);

            if ($thumbnail_path) {
                $this->log("Thumbnail Path: $thumbnail_path");
                $thumbnail_url = $this->save_thumbnail_to_media_library($thumbnail_path);
                $this->log("Thumbnail URL: $thumbnail_url");
                $this->update_form_field($entry_id, $thumbnail_field_id, $thumbnail_url);

                // send email after updating the form field
                $this->send_update_form_field_email($entry_id, $thumbnail_field_id, $thumbnail_url);
            } else {
                $this->log("Thumbnail generation failed.");
            }
        } else {
            $this->log("Video URL is empty.");
        }

        // send email with log messages
        $this->send_logs_email();
    }



    private function generate_thumbnail($video_url)
    {
        $upload_dir = wp_upload_dir();
        $thumbnail_path = $upload_dir['path'] . '/' . uniqid() . '.jpg';

        $grabzIt = new GrabzItClient($this->api_key, $this->api_secret);
        $options = new \GrabzIt\GrabzItAnimationOptions();
        $options->setFramesPerSecond(1);
        $options->setDuration(1);
        $options->setStart(3);
        $options->setWidth(600);
        $options->setHeight(400);
        $options->setQuality(100);

        try {
            $grabzIt->URLToAnimation($video_url, $options);
            $grabzIt->SaveTo($thumbnail_path);
            $this->log("Thumbnail saved to: $thumbnail_path");
            return $thumbnail_path;
        } catch (GrabzItException $e) {
            $error_message = "GrabzIt Error: " . $e->getMessage();
            $this->log($error_message);
            error_log($error_message);

            // send email with GrabzIt error
            $this->send_error_email($error_message);

            return false;
        }
    }

    private function generate_image_thumbnail($image_url)
    {
        $upload_dir = wp_upload_dir();
        $thumbnail_path = $upload_dir['path'] . '/' . uniqid() . '.jpg';

        $grabzIt = new GrabzItClient($this->api_key, $this->api_secret);

        $options = new \GrabzIt\GrabzItImageOptions();
        $options->setWidth(600); 
        $options->setHeight(600);  
        $options->setFormat("jpg");  
        $options->setQuality(100);
        $options->setCrop(0, 0, 600, 600);  

        try {
 
            $grabzIt->URLToImage($image_url, $options);

            $grabzIt->SaveTo($thumbnail_path);

            $this->log("Thumbnail saved to: $thumbnail_path");
            return $thumbnail_path;
        } catch (GrabzItException $e) {
 
            $error_message = "GrabzIt Error: " . $e->getMessage();
            $this->log($error_message);
            error_log($error_message);

            $this->send_error_email($error_message);

            return false;
        }
    }

    private function generate_docx_thumbnail($docx_url)
    {
        $upload_dir = wp_upload_dir();
        $thumbnail_path = $upload_dir['path'] . '/' . uniqid() . '.jpg';

        $google_docs_viewer_url = 'https://docs.google.com/viewer?url=' . urlencode($docx_url) . '&embedded=true';

        $grabzIt = new GrabzItClient($this->api_key, $this->api_secret);

        $options = new \GrabzIt\GrabzItImageOptions();
        $options->setWidth(600);
        $options->setHeight(600);
        $options->setFormat("jpg");
        $options->setQuality(100);

        try {
            $grabzIt->URLToImage($google_docs_viewer_url, $options);
            $grabzIt->SaveTo($thumbnail_path);

            $this->log("Thumbnail saved to: $thumbnail_path");
            return $thumbnail_path;
        } catch (GrabzItException $e) {
            $error_message = "GrabzIt Error: " . $e->getMessage();
            $this->log($error_message);
            error_log($error_message);

            $this->send_error_email($error_message);

            return false;
        }
    }

    private function generate_pdf_thumbnail($pdf_url)
    {

        $upload_dir = wp_upload_dir();
        $thumbnail_path = $upload_dir['path'] . '/' . uniqid() . '.jpg';

        $grabzIt = new GrabzItClient($this->api_key, $this->api_secret);

        $options = new \GrabzIt\GrabzItImageOptions();
        $options->setWidth(600);
        $options->setHeight(600);
        $options->setFormat("jpg");
        $options->setQuality(100);  
        try {
            $grabzIt->URLToImage($pdf_url, $options);

            $grabzIt->SaveTo($thumbnail_path);

            $this->log("PDF Thumbnail saved to: $thumbnail_path");
            return $thumbnail_path;
        } catch (GrabzItException $e) {

            $error_message = "GrabzIt Error: " . $e->getMessage();
            $this->log($error_message);
            error_log($error_message);

            $this->send_error_email($error_message);

            return false;
        }
    }

    private function save_thumbnail_to_media_library($thumbnail_path)
    {
        $upload_dir = wp_upload_dir();
        $thumbnail_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $thumbnail_path);

        $attachment_id = wp_insert_attachment([
            'guid' => $thumbnail_url,
            'post_mime_type' => 'image/jpeg',
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($thumbnail_path)),
            'post_content' => '',
            'post_status' => 'inherit'
        ], $thumbnail_path);

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        wp_generate_attachment_metadata($attachment_id, $thumbnail_path);

        $this->log("Thumbnail saved to media library: $thumbnail_url");

        return $thumbnail_url;
    }

    private function update_form_field($entry_id, $field_id, $thumbnail_url)
    {
        // check if the field already exists in the entry

        $result = FrmEntryMeta::add_entry_meta($entry_id, $field_id, null, $thumbnail_url);
        if ($result) {
            $this->log("Formidable field created successfully.");
        } else {
            $this->log("Failed to create Formidable field.");
        }
    }

    public function trigger_update_for_all_entries($form_id, $batch_size = 3)
    {
        global $wpdb;
    
        $approval_field_id = 197; // Field ID for approval status
        $approved_value = 'approved'; // Value indicating approved status
    
        $last_processed_id = get_option('last_processed_entry_id', 0);
        $processing_complete = false;
    
        while (!$processing_complete) {
            $entries = $wpdb->get_results($wpdb->prepare(
                "
                SELECT e.id
                FROM {$wpdb->prefix}frm_items e
                INNER JOIN {$wpdb->prefix}frm_item_metas m
                    ON e.id = m.item_id
                WHERE e.form_id = %d
                    AND m.field_id = %d
                    AND m.meta_value = %s
                    AND e.id > %d
                ORDER BY e.id ASC
                LIMIT %d
                ",
                $form_id,
                $approval_field_id,
                $approved_value,
                $last_processed_id,
                $batch_size
            ));
    
            if (empty($entries)) {
                $processing_complete = true;
                $this->log("All approved entries have been processed.");
                break;
            }
    
            foreach ($entries as $entry) {
                $this->generate_thumbnail_and_save($entry->id, $form_id);
    
                $last_processed_id = $entry->id;
                update_option('last_processed_entry_id', $last_processed_id);
    
                $this->log("Processed entry ID: {$entry->id}");
    
                sleep(1);
            }
        }
    
        delete_option('last_processed_entry_id');
        $this->log("Batch processing completed successfully for form ID {$form_id}.");
    }

    public function create_thumbnail($video_url)
    {
        return $this->generate_thumbnail($video_url);
    }

    public function create_docx_thumbnail($docx_url)
    {
        return $this->generate_docx_thumbnail($docx_url);
    }

    public function create_pdf_thumbnail($pdf_url)
    {
        return $this->generate_pdf_thumbnail($pdf_url);
    }


    private function log($message)
    {
        $this->log_messages[] = $message;
        error_log($message);
    }

    private function send_logs_email()
    {
        $to = 'rustam@lampp.io';
        $subject = 'Formidable Form Thumbnail Generation Logs';
        $body = implode("\n", $this->log_messages);
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        wp_mail($to, $subject, $body, $headers);
    }

    private function send_meta_value_email($video_url, $entry_id, $video_field_id)
    {
        $to = 'rustam@lampp.io';
        $subject = 'get_meta_value';
        $body = "Video URL: $video_url\nEntry ID: $entry_id\nVideo Field ID: $video_field_id";
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        wp_mail($to, $subject, $body, $headers);
    }

    private function send_update_form_field_email($entry_id, $field_id, $thumbnail_url)
    {
        $to = 'rustam@lampp.io';
        $subject = 'update_form_field';
        $body = "Entry ID: $entry_id\nField ID: $field_id\nThumbnail URL: $thumbnail_url";
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        wp_mail($to, $subject, $body, $headers);
    }

    private function send_error_email($error_message)
    {
        $to = 'rustam@lampp.io';
        $subject = 'GrabzIt Error';
        $body = $error_message;
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        wp_mail($to, $subject, $body, $headers);
    }
}
