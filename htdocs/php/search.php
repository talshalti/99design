<?php
/**
 * Created by PhpStorm.
 * User: Shalti
 * Date: 12/11/2014
 * Time: 23:00
 */
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
function Search($words)
{
    if(Invalid_Words($words))
    {
        Print_Error("ERROR OCCURED : Bad input");
        return;
    }
    $words_list = explode(" ",$words);
    $pages = Get_Relevant_Pages_ID_List($words_list);
    if(count($pages) != 0)
    {
        $database = Initialize_Database_Handler();
        $results = Query_Database_For_Pages_Information($pages, $database);
        $return_value = Adapt_Results_To_Output($results);
        echo json_encode($return_value);
    }
    else
    {
        echo json_encode(array());
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
            "sponsorName"           => $row["SponserName"],
            "catalogPageNum"        => $row["PageNumber"],
            "image"                 => $row["pageImage"],
            "catalogImage"          => $row["Image"],
            "sponsorPhone"          => $row["SponserPhone"],
            "sponsorAddress"        => $row["SponserAddress"],
            "sponsorImage"          => $row["SponserLogo"],
            "pageID"                => $row["pageID"],
            "catalogID"             => $row["ID"],
            "catalogDescription"    => $row["Desc"]
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
function Get_Relevant_Pages_ID_List($words_list)
{
    $myArr = Create_Query_For_Solr($words_list);
    $data = json_decode(solrGet("http://127.0.0.1/solr/collection1/query?" . http_build_query($myArr)));
    $pages = array();
    foreach ($data->{"response"}->{"docs"} as $doc) {
        $pages[] = substr($doc->{"id"}, 3);
    }
    return $pages;
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
function Create_Query_For_Solr($words_list)
{
    $myArr = array(
        "wt" => "json",
        "q" => '"' . implode(" ", $words_list) . '"~' . abs(count($words_list) * 1.5) . ' ' . implode("~ ", $words_list) . "~"
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
    if(!array_key_exists("q", $_GET))
    {
        Print_Error("enter a query");
        return;
    }
    Search($_GET["q"]);
}
main();