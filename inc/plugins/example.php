<?php

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

// cache templates - this is important when it comes to performance
// THIS_SCRIPT is defined by some of the MyBB scripts, including index.php
if (defined('THIS_SCRIPT')) {
    global $templatelist;

    if (isset($templatelist)) {
        $templatelist .= ',';
    }

    if (THIS_SCRIPT == 'index.php') {
        $templatelist .= 'hello_index, hello_message';
    } elseif (THIS_SCRIPT == 'showthread.php') {
        $templatelist .= 'hello_post, hello_message';
    }
}

// Author information
function example_info()
{
    global $lang;
    $lang->load('example');

    // Variables - You don't need to touch.
    // This way you won't have to continuously update for every plugin.
    $codename = str_replace('.php', '', basename(__FILE__));
    $website_of_origin = "https://wowemu.org";

    return array(
        "name" => $codename,
        "description" => $lang->example_desc,
        "website" => $website_of_origin,
        "author" => "Azayaka",
        "authorsite" => $website_of_origin,
        "version" => "1.0",
        "guid" => "", // Deprecated. Don't have.
        "codename" => $codename, // For finding on mybb plugin store.
        "compatibility" => "18*"
    );
}

function example_install()
{
    global $db;

    // Create a new table 'example_table' if it doesn't exist
    if (!$db->table_exists('example_table')) {
        $collation = $db->build_create_table_collation();
        $db->write_query("
            CREATE TABLE " . TABLE_PREFIX . "example_table (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                column1 VARCHAR(255) NOT NULL,
                column2 TEXT,
                PRIMARY KEY (id)
            ) ENGINE=MyISAM{$collation};
        ");
    }
}

/*
 * _is_installed():
 *   Called on the plugin management page to establish if a plugin is already installed or not.
 *   This should return TRUE if the plugin is installed (by checking tables, fields etc) or FALSE
 *   if the plugin is not installed.
 */
function example_is_installed()
{
    global $db;

    // If the table exists then it means the plugin is installed because we only drop it on uninstallation
    return $db->table_exists('example_table');
}

/*
 * _uninstall():
 *    Called whenever a plugin is to be uninstalled. This should remove ALL traces of the plugin
 *    from the installation (tables etc). If it does not exist, the uninstall button is not shown.
 */
function example_uninstall()
{
    global $db, $mybb;

    if ($mybb->request_method != 'post') {
        global $page, $lang;
        $lang->load('example');

        $page->output_confirm_action('index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=example',
            $lang->example_uninstall_message, $lang->example_uninstall);
    }

    // Delete template groups.
    $db->delete_query('templategroups', "prefix='example'");

    // Delete templates belonging to template groups.
    $db->delete_query('templates', "title='example' OR title LIKE 'example_%'");

    // Delete settings group
    $db->delete_query('settinggroups', "name='example'");

    // Remove the settings
    $db->delete_query('settings', "name IN ('example_display1','example_display2')");

    // This is required so it updates the settings.php file as well
	// and not only the database - they must be synchronized!
    rebuild_settings();

    // Drop tables if desired
    if (!isset($mybb->input['no'])) {
        $db->drop_table('example_table');
    }
}

function example_activate()
{
    global $db;

    // Add new settings group
    $settingGroup = array(
        'name' => 'example',
        'title' => 'Example Plugin Settings',
        'description' => 'Settings for the example plugin',
        'disporder' => 1,
        'isdefault' => 0
    );
    $gid = $db->insert_query('settinggroups', $settingGroup);

    // Add new settings
    $settings = array(
        array(
            'name' => 'example_display1',
            'title' => 'Display Option 1',
            'description' => 'Enable or disable display option 1',
            'optionscode' => 'onoff',
            'value' => 1,
            'disporder' => 1,
            'gid' => intval($gid)
        ),
        array(
            'name' => 'example_display2',
            'title' => 'Display Option 2',
            'description' => 'Enable or disable display option 2',
            'optionscode' => 'onoff',
            'value' => 0,
            'disporder' => 2,
            'gid' => intval($gid)
        )
    );
    $db->insert_query_multiple('settings', $settings);

    // Rebuild settings
    rebuild_settings();
}

function example_deactivate()
{
    global $db;

    // Delete settings group
    $db->delete_query('settinggroups', "name='example'");

    // Remove the settings
    $db->delete_query('settings', "name IN ('example_display1','example_display2')");

    // Rebuild settings
    rebuild_settings();
}
