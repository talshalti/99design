<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 11/30/2014
 * Time: 8:55 PM
 */

/**********************************************************
    Default Values
***********************************************************/
define('DIRECT', false);
define('ROOT_DIR', realpath(dirname(__FILE__)));

/**********************************************************
    Database Settings
***********************************************************/
// Host
define('DB_HOST', "localhost");

// Username
define('DB_USER', "root");

// Password
define('DB_PASSWORD', "");

// Database Name
define('DB_NAME', "designdb");

/**********************************************************
    Define Log Contact
***********************************************************/
// Email to report errors
define('LOG_EMAIL', "astrazone@gmail.com");

// Define severity 3 - Error. See Logger.php for more info.
define('LOG_EMAIL_SEVERITY', 3);

// Define the email subject
define('LOG_EMAIL_SUBJECT', "Error Occurred on 99Design.co.il");

/**********************************************************
    Elastic Search Settings
***********************************************************/
// Root URL of the Elastic Search
define('ELASTIC_URL', "http://localhost:9200/");

define('ELASTIC_INDEX', "http://localhost:9200/99design/");