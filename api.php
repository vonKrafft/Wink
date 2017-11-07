<?php
/**
 * The API page
 *
 * @link https://github.com/vonKrafft/Wink/blob/master/api.php
 *
 * @package Wink
 */

if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
	header("HTTP/1.0 405 Method Not Allowed");
	echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">' . PHP_EOL;
	echo '<html><head>' . PHP_EOL;
	echo '<title>405 Method Not Allowed</title>' . PHP_EOL;
	echo '</head><body>' . PHP_EOL;
	echo '<h1>Method Not Allowed</h1>' . PHP_EOL;
	echo '<p>The requested method ' . $_SERVER['REQUEST_METHOD'] . ' is not allowed for the URL ' . $_SERVER['REQUEST_URI'] . ' on this server.</p>' . PHP_EOL;
	echo '</body></html>' . PHP_EOL;
	exit;
}

require_once('config.php');

/** Initialization **/
$wkdb = array();
$alert = init();

header('Content-Type: application/json');
echo json_encode($alert);
