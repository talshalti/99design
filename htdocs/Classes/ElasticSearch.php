<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 11/30/2014
 * Time: 9:43 PM
 */

/**********************************************************
    Includes
***********************************************************/
require_once ROOT_DIR . "config.php";
require_once ROOT_DIR . "vendor/autoload.php";
require_once ROOT_DIR . "Classes/HTTPInterface.php";

/**********************************************************
    Class Definition
***********************************************************/

class ElasticSearch {
    // Logger class
    private $m_Logger;

    // HTTP Interface
    private $m_HttpInterface;

    public function __construct()
    {
        // Initialize the logger
        $this->m_Logger = new Katzgrau\KLogger\Logger(ROOT_DIR . '/logs', Psr\Log\LogLevel::WARNING);

        // Initialize the HTTP Interface
        $this->m_HttpInterface = new HTTPInterface();
    }

    /*
     * Clears the Elastic search index
     *
     * @return Curl Result
    */
    public function Reset()
    {
        return $this->m_HttpInterface->Delete("_all");
    }

    /*
     * Rebuild the index from the current database
     *
     * @return Curl Result
    */
    public function RebuildIndex()
    {

    }

    public function Search()
    {
        
    }

    // ReGenerate the database from DB

}