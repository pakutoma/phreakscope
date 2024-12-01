<?php
declare(strict_types=1);

use logic\ProfileEndpoint;

require_once(__DIR__ . '/vendor/autoload.php');

$seconds = $_GET['seconds'] ?? "";

$profileEndpoint = new ProfileEndpoint();
$profileEndpoint->run($seconds);
