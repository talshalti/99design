<?php
//Header('Content-type: text/xml');
define('DIRECT', false);

require_once("config.php");
require_once("database.php");
require_once("functions.php");




function collectPages()
{
    $database = Initialize_Database_Handler();

	$query = "SELECT a.*, b.SponserName FROM cl_magazines_pages_list as a LEFT JOIN cl_magazines_list as b ON a.MagazineID=b.ID ORDER BY a.ID";

	$results = GetResults($query, $database);

	$file = new SimpleXMLElement('<add encoding="UTF-8"></add>');
	foreach ($results as $row)
	{
	    $xml = $file->addChild("doc");
	    XML_Add_Field("id", "pag".$row["ID"], $xml);
	    XML_Add_Field("type", "page", $xml);
	    XML_Add_Field("catalog_id", "cat".$row["MagazineID"], $xml);
	    XML_Add_Field("page_number", $row["PageNumber"], $xml);
	    XML_Add_Field("description", $row["HtmlDescription"], $xml);
	    XML_Add_Field("img", $row["Image"], $xml);
	    XML_Add_Array("HTML_keywords", explode(",", $row["HtmlKeywords"]), $xml);
	    XML_Add_Array("search_keywords", explode(",", $row["SearchKeywords"]), $xml);
        XML_Add_Field("owner", strtolower(base64_encode($row["SponserName"])), $xml);
	}

	Save_File("pages.xml", html_entity_decode(Format_XML($file)));
}

function Add_Catalog_To_Existing_Sponsor_XML($row, $sponsor_xml)
{
    XML_Add_Field("owned_catalogs", "cat".$row["ID"], $sponsor_xml);
}

function collectCatalogs()
{
    $database = Initialize_Database_Handler();
	$query = "SELECT * FROM cl_magazines_list ORDER BY 'ID'";
	$results = GetResults($query, $database);
	$sponsor_to_id_map = array();
    $current_id = 0;
	$file = new SimpleXMLElement('<add encoding="UTF-8"></add>');
	foreach ($results as $row)
	{
	    $xml = $file->addChild("doc");
	    XML_Add_Field("id", "cat".$row["ID"], $xml);
	    XML_Add_Field("type", "catalog", $xml);
	    XML_Add_Field("name", $row["Name"], $xml);
	    XML_Add_Field("description", $row["Desc"], $xml);
	    XML_Add_Field("img", $row["Image"], $xml);
        XML_Add_Field("hidden_i", $row["HiddenFromLib"], $xml);
	    XML_Add_Array("search_keywords", explode(",", $row["SearchKeywords"]), $xml);
        $sponsor_name = strtolower($row["SponserName"]);
        if(!array_key_exists($sponsor_name, $sponsor_to_id_map))
        {
            $current_id += 1;
            $sponsor_to_id_map[$sponsor_name] = $current_id;
        }
        $sponsor_xml = $xml->addChild("doc");
        XML_Add_Field("id", "spo".$sponsor_to_id_map[$sponsor_name], $sponsor_xml);
        XML_Add_Field("type", "sponsor", $sponsor_xml);
        XML_Add_Field("address", $row["SponserAddress"], $sponsor_xml);
        XML_Add_Field("logo", $row["SponserLogo"], $sponsor_xml);
        XML_Add_Field("phone", $row["SponserPhone"], $sponsor_xml);
        XML_Add_Field("name", $row["SponserAddress"], $sponsor_xml);
	}

	Save_File("catalogs2.xml", html_entity_decode(Format_XML($file)));
}
collectPages();
