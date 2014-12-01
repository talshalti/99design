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
require_once ROOT_DIR . "Classes/Database.php";

/**********************************************************
    Class Definition
***********************************************************/

class ElasticSearch {
    // Logger class
    private $m_Logger;

    // HTTP Interface
    private $m_HttpInterface;

    // PDO Database Inteface
    private $m_Database;

    public function __construct()
    {
        // Initialize the logger
        $this->m_Logger = new Katzgrau\KLogger\Logger(ROOT_DIR . '/logs', Psr\Log\LogLevel::WARNING);

        // Initialize the HTTP Interface
        $this->m_HttpInterface = new HTTPInterface();

        // Initialize the Database
        $this->m_Database = new Database();
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
        $databaseQuery = "SELECT a.*, b.SponserName FROM cl_magazines_pages_list as a LEFT JOIN cl_magazines_list as b ON a.MagazineID=b.ID ORDER BY a.ID";

        $this->m_Database->Query($databaseQuery);
        $this->m_Database->Execute();

        $results = $this->m_Database->GetResultset();
        $submit = array();

        foreach ($results as $row) {

            // If there is no image dont index this element. and log the error
            if (!empty($row["Image"]))
            {
                $submit[] = array("index" =>
                        array(
                            "_index" => "99design",
                            "_type" => "page",
                            "_id" => $row["ID"]
                        ));
                $submit[] = array(
                    "catalog_id" => $row["MagazineID"],
                    "page_number" =>  $row["PageNumber"],
                    "description" => $row["HtmlDescription"],
                    "img" => $row["Image"],
                    "keywords" => explode(",", $row["HtmlKeywords"]),
                    "owner" => strtolower($row["SponserName"])
                );
            }
            else
            {
                $this->m_Logger->warning("No image found while rebuilding the index");
            }
        }

        echo $this->m_HttpInterface->Post("_bulk", $submit);
    }

    /*
     * Sets the mapping for the main pages index
     *
     * @return Curl Result
    */
    public function SetMapping()
    {
        $mapping = array(
            "mappings" => array(
                "page" => array(
                    "properties" => array(
                        "catalog_id" => array(
                            "type" => "integer"
                        ),
                        "page_number" => array(
                            "type" => "integer"
                        ),
                        "description" => array(
                            "type" => "string",
                            "analyzer" => "hebrew"
                        ),
                        "img" => array(
                            "type" => "string",
                            "index" => "no"
                        ),
                        "keywords" => array(
                            "type" => "string",
                            "analyzer" => "hebrew"
                        ),
                        "owner" => array(
                            "type" => "string",
                            "index" => "not_analyzed"
                        )
                    )
                )
            )
        );

        return $this->m_HttpInterface->Put("99design", $mapping);
    }

    /*
    * Parses the GET input and returns the search results, merges the data with more info from the DB
    *
    * @return Mixed adjusted json string with results
   */
    public function Search()
    {
        // Clean Query
        $query = urldecode(filter_input(INPUT_GET, "q", FILTER_SANITIZE_ENCODED));
        $query = preg_replace('/\s+/', ' ', $query);
        // remove any unwanted special symbols
        $query = preg_replace('/[^\x00-\x1F\x80-\xFF|\s]/', '', $query);

        $from = 0;
        $size = ELASTIC_SEARCH_RESULTS_DEFAULT;
        $owner = urldecode(filter_input(INPUT_GET, "o", FILTER_SANITIZE_ENCODED));

        if (isset($_GET["st"])) {
            if ($_GET["st"] != "")
            {
                if ($from = filter_input(INPUT_GET, "st", FILTER_VALIDATE_INT) === false)
                {
                    $from = 0;
                    $this->m_Logger->warning("Wrong start value for search.");
                }
            }
        }

        if (isset($_GET["l"])) {
            if ($_GET["l"] != "")
            {
                if ($size = filter_input(INPUT_GET, "l", FILTER_VALIDATE_INT) === false) {
                    $size = 0;
                    $this->m_Logger->warning("Wrong size value for search.");
                }
                else if ($size > ELASTIC_SEARCH_RESULTS_MAX)
                {
                    $size = 0;
                    $this->m_Logger->warning("Size over max");
                }
            }
        }

        if (isset($_GET["o"])) {
            if ($_GET["o"] != "")
            {
                // remove any unwanted special symbols
                $owner = preg_replace('/[^\x00-\x1F\x80-\xFF|\s]/', '', $owner);
            }
        }

        if ($size > 0)
        {
            $this->GetElasticSearchResults($query, $from, $size, $owner);
        }
        else
        {

        }
        /*$myArr = Create_Query_For_ElasticSearch($words_list);
        $data = json_decode(Query_Elastic_Search($myArr));*/
        //Search(trim($query), true);
    }

   /*
    * Handle the request and return the ES result
    *
    * @return Results Array
    */
    private function GetElasticSearchResults($Query, $Start = 0, $Length = ELASTIC_SEARCH_RESULTS_DEFAULT, $Owner = "")
    {
        $requestArray = array(
            "match_phrase" => array(
                "_all" => array(
                    "query" => $Query,
                    "analyzer" => "hebrew"
                )
            )
        );

        if ($Owner != "")
        {
            $queryArray["query"] = array(
                "filtered"=> array(
                    "filter" => array(
                        "term" => array(
                            "owner" => $Owner
                        )
                    ),
                    "query" => $requestArray
                )
            );
        }
        else
        {
            $queryArray["query"] = $requestArray;
        }

        $queryArray["start"] = $Start;
        $queryArray["size"] = $Length;

        $this->m_HttpInterface->Post(ELASTIC_URL . "99design/_search", $queryArray);
    }
}