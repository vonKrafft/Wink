<?php
/**
 * The base configuration for Wink
 *
 * This file contains the following configurations:
 *
 * * Settings
 * * Secret keys
 * * Application initialization
 *
 * @link https://github.com/vonKrafft/Wink/blob/master/config.php
 *
 * @package Wink
 */

# ---------------------------------------------------------------------------
# Settings
# ---------------------------------------------------------------------------

/** Base Site URL **/
define('BASE_URL', '');

/** Title of the site (displayed at the top left) **/
define('SITE_TITLE', 'Wink');

/** Directory in which to store the data **/
define('DATA_DIR', dirname(__FILE__) . '/data');

/** Number of links per page **/
define('POSTS_PER_PAGE', 25);

# ---------------------------------------------------------------------------
# Secret keys
# ---------------------------------------------------------------------------

/**#@+
 * Authentication Unique Keys.
 *
 * These keys are the only authentication mechanism. When a valid key is
 * placed in GET parameter, the publication form is visible to the user.
 * When a link is published, the key is verified.
 * The syntax is: array ('key_value' => 'username')
 *
 * @since 1.0
 */
$apikeys = array(
	'put a unique token here' => 'username_here',
);
/**#@-*/

# ---------------------------------------------------------------------------
# Application initialization
# ---------------------------------------------------------------------------

/** Functions and definitions **/
include_once('app.php');

/** Initialization **/
$wkdb = array();
$alert = init();
