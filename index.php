<?php
/*
 *  Copyright (c) 2022.
 *  Coded by Moritz David
 *
 * ----------------------------
 *  Raspberry Pi Temperature Project
 *       REST-API Version 1.1
 * ----------------------------
 *
 *  Handles GET and POST-Requests and reads data via URL-Parameter
 *  Returns the result in JSON
 *  Bsp. POST-Request: http://www.example.com/index.php?temp=22.1&room=3.11
 *  Bsp. GET-Request:  http://www.example.com/index.php?id=12
 *
 *  Available URL-Parameter:
 *  id, temp, room, timestamp, totalentrycount
 *
 *  Necessary URL-Parameter for POST requests:
 *  temp, room
 *
 */

// Please check if this filepath is correct
$path = $_SERVER['DOCUMENT_ROOT'];
$path .= "/dbConfig.php";
require_once($path);

const DB_ATTRIBUTE_ID = "id";
const DB_ATTRIBUTE_TEMP = "temperatur";
const DB_ATTRIBUTE_ROOM = "raum";
const DB_ATTRIBUTE_TIMESTAMP = "measuredAt";

const URL_PARAM_ID = "id";
const URL_PARAM_TEMP = "temp";
const URL_PARAM_ROOM = "room";
const URL_PARAM_TIMESTAMP = "timestamp";
const URL_PARAM_TOTAL_ENTRY_COUNT = "totalentrycount";

$returnValue = "";

try {
    // Create database connection
    $databaseConnection = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $databaseConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $databaseConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_GET) && isset($_GET[URL_PARAM_TEMP]) && isset($_GET[URL_PARAM_ROOM])) {
            if (insertIntoDB($databaseConnection, $_GET[URL_PARAM_TEMP], $_GET[URL_PARAM_ROOM])) {
                $returnValue = "Successfully created a new entry in the Database.";
            } else {
                http_response_code(400);
                die("Error, creating new entry failed.");
            }
        } else {
            http_response_code(400);
            die ("Error, no data provided");
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // When url parameter is set, return specific dataset
        if (isset($_GET[URL_PARAM_ID])) {
            $returnValue = selectWhereEquals($databaseConnection, DB_ATTRIBUTE_ID, utf8_encode($_GET[URL_PARAM_ID]));
        } else if (isset($_GET[URL_PARAM_TEMP])) {
            $returnValue = selectWhereEquals($databaseConnection, DB_ATTRIBUTE_TEMP, utf8_encode($_GET[URL_PARAM_TEMP]));
        } else if (isset($_GET[URL_PARAM_ROOM])) {
            $returnValue = selectWhereEquals($databaseConnection, DB_ATTRIBUTE_ROOM, utf8_encode($_GET[URL_PARAM_ROOM]));
        } else if (isset($_GET[URL_PARAM_TOTAL_ENTRY_COUNT])) {
            if (utf8_encode($_GET[URL_PARAM_TOTAL_ENTRY_COUNT]) == "true") {
                $returnValue = getTotalEntryCount($databaseConnection);
            }
        } else if (sizeof($_GET) == 0) {
            // When no GET PARAM set, return whole database
            $returnValue = selectAll($databaseConnection);
        }
    } else {
        http_response_code(400);
        die ("Error, wrong request type.");
    }
} catch (PDOException $e) {
    http_response_code(500);
    die("Database error: " . $e);
}

if (!empty($returnValue)) {
    echo $returnValue;
} else {
    http_response_code(500);
}
$databaseConnection = null;


function temperatureMeasurementObject($id, $temperature, $room, $timestamp)
{
    return array(
        URL_PARAM_ID => $id,
        URL_PARAM_TEMP => $temperature,
        URL_PARAM_ROOM => $room,
        URL_PARAM_TIMESTAMP => $timestamp
    );
}

function insertIntoDB($databaseConnection, $temperature, $room)
{
    if (!empty($temperature) && !empty($room)) {
        $sqlQuery = "INSERT INTO " . DB_TABLE . " (" . DB_ATTRIBUTE_TEMP . ", " . DB_ATTRIBUTE_ROOM . ") VALUES (?, ?)";
        $stmt = $databaseConnection->prepare($sqlQuery);
        return $stmt->execute(array(utf8_encode($temperature), utf8_encode($room)));
    } else {
        return false;
    }
}

function selectWhereEquals($databaseConnection, $dbAttribute, $searchValue)
{
    if (!empty($searchValue)) {
        $sqlQuery = "SELECT * FROM " . DB_TABLE . " WHERE " . $dbAttribute . " = ? ORDER BY " . DB_ATTRIBUTE_ID;
        $stmt = $databaseConnection->prepare($sqlQuery);
        $stmt->execute(array($searchValue));
        return getJsonObjectArrayFromDatabaseConnection($stmt);
    } else {
        return null;
    }
}

function selectAll($databaseConnection)
{
    $sqlQuery = "SELECT * FROM " . DB_TABLE . " ORDER BY " . DB_ATTRIBUTE_ID;
    $stmt = $databaseConnection->prepare($sqlQuery);
    $stmt->execute();
    return getJsonObjectArrayFromDatabaseConnection($stmt);
}

function getTotalEntryCount($databaseConnection)
{
    $sqlQuery = "SELECT COUNT(" . DB_ATTRIBUTE_ID . ") AS " . URL_PARAM_TOTAL_ENTRY_COUNT . " FROM " . DB_TABLE . " LIMIT 1";
    $stmt = $databaseConnection->prepare($sqlQuery);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $data = $stmt->fetch();
    return json_encode(array(URL_PARAM_TOTAL_ENTRY_COUNT => $data[URL_PARAM_TOTAL_ENTRY_COUNT]), JSON_NUMERIC_CHECK);
}

function getJsonObjectArrayFromDatabaseConnection($stmt)
{
    $data = array();

    // loop through the query results array
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt->fetchAll() as $row) {
        $id = utf8_encode($row[DB_ATTRIBUTE_ID]);
        $temperature = utf8_encode($row[DB_ATTRIBUTE_TEMP]);
        $room = utf8_encode($row[DB_ATTRIBUTE_ROOM]);
        $timestamp = utf8_encode($row[DB_ATTRIBUTE_TIMESTAMP]);

        if (!empty($id) || !empty($temperature) || !empty($room) || !empty($timestamp)) {
            $data[] = temperatureMeasurementObject($id, $temperature, $room, $timestamp);
        }
    }

    // Convert to json array
    return json_encode($data, JSON_NUMERIC_CHECK);
}
