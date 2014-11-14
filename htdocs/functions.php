<?php

function Initialize_Database_Handler()
{
    // Initialize Database Handler
    $database = new Database();

    // Set UTF-8 Encoding
    $database->query("SET NAMES 'utf8'");
    $database->execute();

    return $database;
}

function Print_Array($array)
{
    echo "<pre>";
    print_r($array);
    echo "</pre>";
}

function XML_Add_Field($name, $data, $xml)
{
    $filed = $xml->addChild('field', $data);
    $filed->addAttribute('name', $name);
}

function Format_XML(&$simpleXmlObject)
{
    $dom = dom_import_simplexml($simpleXmlObject)->ownerDocument;
    $dom->formatOutput = true;
    return $dom->saveXML();
}

function XML_Add_Array($name, $array, $xml)
{
    foreach($array as $elem)
    {
        XML_Add_Field($name, $elem, $xml);
    }
}

function GetResults($query, $database)
{
    $database->query($query);
    $database->execute();
    return $database->resultset();
}

function Save_File($filename, $content)
{
    file_put_contents($filename, $content);
}