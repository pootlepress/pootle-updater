<?php
/*
Plugin Name: PootlePress Updater
Description: Updater for PootlePress Server
Version: 1.0.0
Author: PootlePress
Author URI: http://pootlepress.com/
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

require_once('plugins-info.php');

add_action('init', 'pootlepress_updater', 10000);

function pootlepress_updater() {

    if (isset($_GET['updater']) && $_GET['updater'] == '1') {

        global $pluginsInfo;
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'version':

                    if (isset($_POST['plugin'])) {
                        $pluginSlug = $_POST['plugin'];
                        if (isset($pluginsInfo[$pluginSlug])) {
                            echo $pluginsInfo[$pluginSlug]['latest-version'];
                            die();
                        } else {
                            echo 'false';
                            die();
                        }
                    } else {
                        echo "false";
                        die();
                    }
                    break;
                case 'info':
                    if (isset($_POST['plugin'])) {
                        $pluginSlug = $_POST['plugin'];
                        if (isset($pluginsInfo[$pluginSlug])) {
                            $obj = new stdClass();
                            $obj->slug = $pluginSlug;
                            $obj->plugin_name = 'plugin.php';
                            $obj->name = $pluginsInfo[$pluginSlug]['name'];
                            $obj->new_version = $pluginsInfo[$pluginSlug]['latest-version'];
//                $obj->requires = '3.0';
//                $obj->tested = '3.9.1';
//                $obj->downloaded = 12540;
//                $obj->last_updated = '2014-07-12';
                            $obj->sections = array(
                                'description' => $pluginsInfo[$pluginSlug]['description'],
//                'another_section' => 'This is another section',
//                'changelog' => 'Some new features'
                            );

                            $obj->download_link = 'http://www.pootlepress.com/?updater=1&plugin=' . urlencode($pluginSlug);
//                            $obj->download_link = 'http://pootle.localhost/wordpress/?updater=1&plugin=' . urlencode($pluginSlug);
                            echo serialize($obj);
                            die();
                        }
                    } else {
                        echo 'false';
                        die();
                    }
                    break;
                case 'license':
                    echo 'false';
                    die();
                    break;
            }
        } else {
            if (isset($_GET['plugin'])) {
                $pluginSlug = $_GET['plugin'];
                if (isset($pluginsInfo[$pluginSlug])) {

                    $productID = $pluginsInfo[$pluginSlug]['product-id'];
                    $product = new WC_Product($productID);

                    $fileInfo = $product->get_file();

                    $relativePath = wp_make_link_relative($fileInfo['file']);

                    $filePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;

                    header('Cache-Control: public');
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/zip');
                    readfile($filePath);
                    die();
                } else {
                    die();
                }

            } else {
                die();
            }

        }

    }
}