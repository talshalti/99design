<?php

// Header('Content-type: text/xml');
define('DIRECT', false);

require_once("config.php");
require_once("database.php");
require_once("functions.php");




function main($sponsor, $offset, $limit)
{
    $database = Initialize_Database_Handler();
    $query = "SELECT * FROM cl_magazines_list WHERE LOWER(SponserName) = :sponsor LIMIT :limit OFFSET :offset";
    $database->query($query);
    $database->bind(":sponsor", $sponsor, PDO::PARAM_STR);
    $database->bind(":limit", $limit, PDO::PARAM_INT);
    $database->bind(":offset", $offset, PDO::PARAM_INT);
    $database->execute();
    $results = $database->resultset();

    $arr = array();
    foreach ($results as $row)
    {
        $arr[] = array(
            "description" => $row["Desc"],
            "name" => $row["Name"],
            "image" => $row["Image"],
            "id" =>$row["ID"] );
    }
    echo json_encode($arr);
}

// #FIXME : change to api_key
if(array_key_exists("sponsor", $_REQUEST))
{
    $sponsor = $_REQUEST["sponsor"];
    if(array_key_exists( "l", $_REQUEST))
    {
        $limit = $_REQUEST["l"];
    }
    else
    {
        $limit = 20;
    }

    if(array_key_exists("st", $_REQUEST))
    {
        $start = $_REQUEST["st"];
    }
    else
    {
        $start = 0;
    }

    main(strtolower($sponsor), $start, $limit);
}