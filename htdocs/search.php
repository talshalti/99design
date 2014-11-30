<?php
/**
 * Created by PhpStorm.
 * User: Shalti
 * Date: 12/11/2014
 * Time: 23:00
 */
ini_set('error_reporting', E_ALL);
define('DIRECT', false);
require_once("config.php");
require_once("database.php");
require_once("functions.php");



function solrGet($url)
{
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_PORT => 8983
    ));
    $output=curl_exec($ch);
    curl_close($ch);
    return $output;
}

function Query_Elastic_Search_Get($data)
{
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
        CURLOPT_PORT => 9200
    ));
    $output=curl_exec($ch);
    curl_close($ch);
    return $output;
}

function Search($words, $must_have_first_word)
{
    if(Invalid_Words($words))
    {
        Print_Error("ERROR OCCURED : Bad input");
        return;
    }
    $words_list = explode(" ",$words);
    list($pages, $numFound) = Get_Relevant_Pages_ID_List_And_Num_Found($words_list, $must_have_first_word);

    if(count($pages) != 0)
    {
        $database = Initialize_Database_Handler();
        $results = Query_Database_For_Pages_Information($pages, $database);
        $return_value = Adapt_Results_To_Output($results);
        echo json_encode(
            array(
                "results"=>$return_value,
                "numFound"=>$numFound
            ));
    }
    else
    {
        if($must_have_first_word && $numFound == 0)
        {
            Search($words, false);
        }
        else
        {
            echo json_encode(
                array(
                    "results"=>[],
                    "numFound"=>$numFound
                ));
        }
    }
}

/**
 * @param $results  mixed - resultset of the query
 * @return array - array of elements, so that each element contains info for the response
 */
function Adapt_Results_To_Output($results)
{
    $return_value = array();
    foreach ($results as $row) {
        $return_value[] = array(
            "sponsorName"           => stripslashes($row["SponserName"]),
            "catalogPageNum"        => $row["PageNumber"],
            "image"                 => $row["pageImage"],
            "catalogImage"          => $row["Image"],
            "sponsorPhone"          => $row["SponserPhone"],
            "sponsorAddress"        => $row["SponserAddress"],
            "sponsorImage"          => $row["SponserLogo"],
            "sponsorImage"          => $row["SponserLogo"],
            "pageID"                => $row["pageID"],
            "catalogID"             => $row["ID"],
            "catalogDescription"    => $row["Desc"],
            "pageDescription"       => $row["HtmlDescription"]
        );
    }
    return $return_value;
}
/**
 * @param $pages array - array of pages id
 * @param $database Database - initialized database
 * @return mixed - the resultset
 */
function Query_Database_For_Pages_Information($pages, $database)
{
    $query = "SELECT a.Image as pageImage, a.ID as pageID, a.*, b.* FROM cl_magazines_pages_list as a " .
        "JOIN cl_magazines_list as b ON a.MagazineID=b.ID " .
        "WHERE a.ID IN (" . implode(", ", $pages) . ") " .
        "ORDER BY FIELD(a.ID,  " . implode(", ", $pages) . ")";
    $database->query($query);
    $database->execute();
    return $database->resultset();
}


/**
 * @param $words_list array
 * @return array
 */
function Get_Relevant_Pages_ID_List_And_Num_Found($words_list, $must_have_first_word)
{
    $myArr = Create_Query_For_ElasticSearch($words_list);
    $data = json_decode(solrGet("http://127.0.0.1/solr/collection1/query?" . http_build_query($myArr)));
    $pages = array();
    foreach ($data->{"response"}->{"docs"} as $doc) {
        $pages[] = substr($doc->{"id"}, 3);
    }

    return array($pages, $data->{"response"}->{"numFound"});
}
/**
 * @param $words array  - the words list
 * @return bool - true if the words list are invalid, false otherwise
 */
function Invalid_Words($words)
{
    return strpos($words, "{") !== false or strpos($words, "[") !== false;
}

/**
 * @param $words_list array - the words list
 * @return array    the query arguments
 */
function Create_Query_For_ElasticSearch($words_list)
{
    $myArr = array();
    $queryContainer = $myArr;
    $phrase = implode(" ", $words_list);
    $query = array(
        "match_phrase" => array(
            "_all" => array(
                "query" => $phrase,
                "analyzer" => "hebrew"
            )
        )
    );

    if (isset($_GET["st"])) {
        $myArr["from"] = $_GET["st"];
    }
    if(isset($_GET["l"])) {
        $myArr["size"] = $_GET["l"];
    }
    if(isset($_GET["o"]))
    {
        $queryContainer = array(
            "filtered"=> array(
                "term" => array(
                    "owner" => $_GET["o"]
                )
            )
        );
    }
    $queryContainer["query"] = $query;
    return $myArr;
}

/**
 * @param $words_list array - the words list
 * @return array    the query arguments
 */
function Create_Query_For_Solr($words_list, $must_have_first_word)
{
    $query = "(" .implode("~0.6 AND ", $words_list). "~0.6)";
    if ($must_have_first_word)
    {
        $query .= " AND description:/".$words_list[0]." .*/";
    }
    $myArr = array(
        "wt" => "json",
        "q" => $query
    );

    if (array_key_exists("type", $_GET)) {
        $myArr["fq"] = "type:" . $_GET["type"];
    }
    if (array_key_exists("st", $_GET)) {
        $myArr["start"] = $_GET["st"];
    }
    if (array_key_exists("l", $_GET)) {
        $myArr["rows"] = $_GET["l"];
    }
    if (array_key_exists("o", $_GET)) {
        $fq = "owner:(" . $_GET["o"] . ")";
        if (array_key_exists("fq", $myArr)) {
            $myArr["fq"] .= " " . $fq;
            return $myArr;
        } else {
            $myArr["fq"] = $fq;
            return $myArr;
        }
    }
    return $myArr;
}

function Print_Error($error_message)
{
    echo json_encode(array(
        "responseType" => "err",
        "msg" => $error_message));
}
function main()
{
    if(isset($_GET["q"]))
    {
        if($_GET["q"] !== "")
        {
            $query = urldecode(filter_input(INPUT_GET, "q", FILTER_SANITIZE_ENCODED));
            $query = preg_replace('/\s+/', ' ', $query);
            Search(trim($query), true);

            return;
        }
    }

    echo json_encode(array());
    return;
}
main();