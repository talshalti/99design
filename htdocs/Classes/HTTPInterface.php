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

class HTTPInterface {
    // Logger class
    private $m_Logger;

    public function __construct()
    {
        // Initialize the logger
        $this->m_Logger = new Katzgrau\KLogger\Logger(ROOT_DIR . '/logs', Psr\Log\LogLevel::WARNING);
    }

    /* Checks whether the variable is associative
     *
     * @param $var      Variable to check
     *
     * @return          True if var is an associative array
     *                  False otherwise
     *
    */
    private function is_assoc($var)
    {
        return is_array($var) && array_diff_key($var,array_keys(array_keys($var)));
    }

    /* Submit a post request to URL with data Array
     *
     * @param $Url      The path to submit to
     * @param $Array    Data to submit
     *
     * @return          CURL Response
     *
    */
    public function Post($Url, $Array)
    {
        return $this->Submit($Url, "POST", $Array);
    }

    /* Submit a GET request to URL
     *
     * @param $Url      The path to submit to
     *
     * @return          CURL Response
     *
    */
    public function Get($Url)
    {
        return $this->Submit($Url, "GET");
    }

    /* Submit a DELETE request to URL
     *
     * @param $Url      The path to submit to
     *
     * @return          CURL Response
     *
    */
    public function Delete($Url)
    {
        return $this->Submit($Url, "DELETE");
    }

    /* Generate the correct payload according to the Array type
     *
     * @param $Array    Data to Stringify
     *
     * @return          generated JSON string
     *
    */
    private function generatePayload($Array)
    {
        $payload = "";

        if ($this->is_assoc($Array))
        {
            // Single array
            $payload = json_encode($Array);
        }
        else
        {
            // Bulk array
            foreach ($Array as $object)
            {
                if (empty($object))
                {
                    $this->m_Logger->warning("Empty Object Provided in Elastic Interface Bulk Generate");
                }
                else
                {
                    $payload .= json_encode($object);
                    $payload .= '\n'; // Add separator for elastic search.
                }
            }
        }

        return $payload;
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
    private function Submit($Url, $Type, $Array = null)
    {
        $curl = curl_init(ELASTIC_URL . $Url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_TIMEOUT, '25');

        // Set request method to Type
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $Type);

        if ($Array != null)
        {
            $payload = $this->generatePayload($Array);

            if ($payload == "")
            {
                $this->m_Logger->warning("Couldn't generate payload.");
            }
            else
            {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
            }
        }

        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }
}