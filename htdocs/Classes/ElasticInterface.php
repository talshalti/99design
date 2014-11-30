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
require_once ROOT_DIR . "/config.php";
require_once ROOT_DIR . "vendor/autoload.php";


/**********************************************************
    Class Definition
***********************************************************/

class ElasticInterface {
    /**
     * Path to the log file
     * @var string
     */
    private $logFilePath = null;

    // Logger class to
    private $Logger;

    // Available Types, others are discarded
    private $AvailableTypes = ["PUT", "GET", "POST", "DELETE", "HEAD"];

    public function __construct()
    {
        // Initialize the logger
        $this->Logger = new Katzgrau\KLogger\Logger(ROOT_DIR . '/logs', Psr\Log\LogLevel::WARNING);
    }

    /* Submit an array to Elastic Search
     *
     * @param $Url      Url to submit the data to
     * @param $Type     String of the CURL request type
     * @param $Array    The data that will be JSONed and submitted
     *
     * @return          Curl results
     *
    */
    public function Submit($Url, $Type, $Array)
    {
        $result = false;

        if (!in_array($Type, $this->AvailableTypes))
        {
            $this->Logger->warning("Bad type provided in Elastic Interface. Type : " . $Type);
        }
        else if (empty($Array))
        {
            $this->Logger->warning("Empty Array Provided in Elastic Interface");
        }
        else
        {
            $curl = curl_init(ELASTIC_URL . $Url);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT, '3');

            // Set request method to Type
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $Type);

            // Set query data here with CURLOPT_POSTFIELDS
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($Array));

            $result = curl_exec($curl);
            curl_close($curl);
        }

        return $result;
    }

    /* Submit a Bulk array to Elastic Search
     *
     * @param $Url               Url to submit the data to
     * @param $ArrayOfObjects    The data that will be JSONed joined and submitted
     *
     * @return          Curl results
     *
    */
    public function SubmitBulk($Url, $ArrayOfObjects)
    {
        $result = false;

        if (empty($ArrayOfObjects))
        {
            $this->Logger->warning("Empty Array Provided in Elastic Interface BulkSubmit");
        }
        else
        {
            $curl = curl_init(ELASTIC_URL . $Url);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT, '60');

            // Set request method to Type
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");

            // Generate the payload
            $payload = "";

            foreach ($ArrayOfObjects as $Object)
            {
                if (empty($Object))
                {
                    $this->Logger->warning("Empty Object Provided in Elastic Interface Bulk Generate");
                }
                else
                {
                    $payload .= json_encode($Object);
                    $payload .= "\n"; // Add separator for elastic search.
                }
            }

            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

            $result = curl_exec($curl);
            curl_close($curl);
        }

        return $result;
    }
}