<?php
/*
Plugin Name: PootlePress Updater
Description: Updater for PootlePress Server
Version: 1.0.18
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
                            if (isset($pluginsInfo[$pluginSlug]['testedWP'])) {
                                $obj->tested = $pluginsInfo[$pluginSlug]['testedWP'];
                            }
//                  $obj->tested = '3.9.1';
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
                case 'upgrade-notice':
                    if (isset($_POST['plugin'])) {
                        $pluginSlug = $_POST['plugin'];
                        if (isset($pluginsInfo[$pluginSlug])) {
                            if (isset($pluginsInfo[$pluginSlug]['upgrade-notice'])) {
                                echo $pluginsInfo[$pluginSlug]['upgrade-notice'];
                                die();
                            } else {
                                echo "false";
                                die();
                            }
                        } else {
                            echo 'false';
                            die();
                        }
                    } else {
                        echo "false";
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

                    $ip = $_SERVER['REMOTE_ADDR'];
                    if (isset($ip) && $ip != '') {
                        pp_updater_increase_download_count($ip, $pluginSlug);
                    }

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

function pp_updater_increase_download_count($ip, $pluginSlug) {
    global $wpdb;
    $table = $wpdb->prefix . 'updater_stats';
    $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE ip = %s AND plugin = %s", $ip, $pluginSlug), ARRAY_A);

    if ($rows === null || $rows === false) {
        $createNewRow = true;
    } else {
        if (count($rows) > 0) {
            $createNewRow = false;
        } else {
            $createNewRow = true;
        }
    }

    if ($createNewRow) {
        $wpdb->insert($table, array('ip' => $ip, 'plugin' => $pluginSlug, 'download_count' => 1));
    } else {
        $row = $rows[0];
        $currentCount = (int)$row['download_count'];
        $wpdb->update($table, array('download_count' => $currentCount + 1), array('ip' => $ip, 'plugin' => $pluginSlug));
    }
}

register_activation_hook( __FILE__, 'pp_updater_activation');

function pp_updater_activation() {
    global $wpdb;

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $collate = '';

    if ( $wpdb->has_cap( 'collation' ) ) {
        if ( ! empty($wpdb->charset ) ) {
            $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
        }
        if ( ! empty($wpdb->collate ) ) {
            $collate .= " COLLATE $wpdb->collate";
        }
    }

    $table = "
	CREATE TABLE {$wpdb->prefix}updater_stats (
	  id INT NOT NULL auto_increment,
	  ip VARCHAR(50) NOT NULL,
	  plugin TEXT NOT NULL,
	  download_count INT NOT NULL,
	  PRIMARY KEY (id)
	) $collate;
	";
    dbDelta( $table );
}
