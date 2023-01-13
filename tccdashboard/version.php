<?php

/**
 * @mainpage
 * This is the ... package, an LInE free system to enrich activities in Moodle.
 * It is created by...
 *
 * iAssign's goal is to increase interactivity in activities related to specific subjects (such as Geometry, Functions, Programming,...)
 * in a flexible way.
 *
 * @Maria Eduarda Corradini Tolino 
 *
 * @version v 1.0 2022/12/07
 * @since 2022/10/01
 *
 * <b>License</b>
 *  - http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

$plugin->component = 'block_tccdashboard'; // Full name of the plugin (used for diagnostics)
$plugin->release = '0.1 (Build: 2022100100'; //
$plugin->version = 2022113000;     // The current module version (Date: YYYYMMDDXX)
$plugin->requires = 2012112900;    // Requires this Moodle version
$plugin->maturity = MATURITY_STABLE; // How stable the plugin is: MATURITY_ALPHA, MATURITY_BETA, MATURITY_RC, MATURITY_STABLE (Moodle 2.0 and above)
$plugin->dependencies = array('block_tccdashboard' => 2022113000); // current xxx 2022113000
