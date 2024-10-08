<?php
/*
Plugin Name: Grabz.it Thumbnail Generator
Plugin URI: https://lampp.io/
Description: A plugin to generate video thumbnails using Grabz.it and save them in a Formidable Forms field.
Version: 2.0
Author: Rustamveer Singh
Author URI: https://www.linkedin.com/in/rustamveer
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}


require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

require_once plugin_dir_path(__FILE__) . 'includes/class-grabzit-thumbnail-generator.php';

if (defined('WP_CLI') && WP_CLI) {
    require_once plugin_dir_path(__FILE__) . 'wp-cli-commands.php';
}

function run_grabzit_thumbnail_generator()
{
    $grabzit_thumbnail_generator = new Grabzit_Thumbnail_Generator();
    $grabzit_thumbnail_generator->init();
}
add_action('plugins_loaded', 'run_grabzit_thumbnail_generator');

function lampp_rv_send_uploaded_file_url_on_publish( $post_id ) {
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    $post_type = get_post_type( $post_id );
    if ( 'dlp_document' !== $post_type ) {
        return;
    }

    $attachment_id = get_post_meta( $post_id, '_dlp_attached_file_id', true );

    if ( $attachment_id ) {
        $file_url = wp_get_attachment_url( $attachment_id );

        if ( $file_url ) {
            $to = 'rustam@lampp.io';
            $subject = 'New Document Uploaded via plugin';
            $message = 'A new document has been uploaded. The file URL is: ' . $file_url;
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail( $to, $subject, $message, $headers );

            $file_extension = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));
            if ($file_extension == 'png' || $file_extension == 'jpg' || $file_extension == 'jpeg') {
                $thumbnail_id = lampp_rv_set_post_thumbnail($post_id, $file_url);
                if ($thumbnail_id) {
                    update_post_meta($post_id, '_docgallery_thumbnail_id', $thumbnail_id);
                }
            } elseif ($file_extension == 'docx') {
                $grabzit_thumbnail_generator = new Grabzit_Thumbnail_Generator();
                $thumbnail_path = $grabzit_thumbnail_generator->create_docx_thumbnail($file_url);

                if ($thumbnail_path) {
                    $thumbnail_id = lampp_rv_set_post_thumbnail($post_id, $thumbnail_path);
                    if ($thumbnail_id) {
                        update_post_meta($post_id, '_docgallery_thumbnail_id', $thumbnail_id);
                    }
                }
            } elseif ($file_extension == 'mp4') {
                $grabzit_thumbnail_generator = new Grabzit_Thumbnail_Generator();
                $thumbnail_path = $grabzit_thumbnail_generator->create_thumbnail($file_url);

                if ($thumbnail_path) {
                    $thumbnail_id = lampp_rv_set_post_thumbnail($post_id, $thumbnail_path);
                    if ($thumbnail_id) {
                        update_post_meta($post_id, '_docgallery_thumbnail_id', $thumbnail_id);
                    }
                }
            } elseif ($file_extension == 'pdf') {
                $grabzit_thumbnail_generator = new Grabzit_Thumbnail_Generator();
                $thumbnail_path = $grabzit_thumbnail_generator->create_pdf_thumbnail($file_url);

                if ($thumbnail_path) {
                    $thumbnail_id = lampp_rv_set_post_thumbnail($post_id, $thumbnail_path);
                    if ($thumbnail_id) {
                        update_post_meta($post_id, '_docgallery_thumbnail_id', $thumbnail_id);
                    }
                }
            }
        }
    }
}
add_action( 'save_post', 'lampp_rv_send_uploaded_file_url_on_publish' );

function lampp_rv_set_post_thumbnail( $post_id, $thumbnail_path ) {
    $upload_dir = wp_upload_dir();
    $thumbnail_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $thumbnail_path);

    $attachment = array(
        'guid'           => $thumbnail_url, 
        'post_mime_type' => 'image/jpeg',
        'post_title'     => basename($thumbnail_path),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    $attach_id = wp_insert_attachment( $attachment, $thumbnail_path, $post_id );

    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    $attach_data = wp_generate_attachment_metadata( $attach_id, $thumbnail_path );
    wp_update_attachment_metadata( $attach_id, $attach_data );

    set_post_thumbnail($post_id, $attach_id);

    return $attach_id;
}
