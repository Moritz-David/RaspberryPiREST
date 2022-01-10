<?php
/*
 *   Copyright (c) 2021.
 *   Coded by Moritz David
 *
 *  ----------------------------
 *   LF07 REST-API Version 1.0
 *  ----------------------------
 *
 *   GET and POST data via URL-Parameter
 *   return file format is JSON
 *   Bsp. POST: http://www.example.com/index.php?temp=22.1&room=3.11
 *   Bsp. GET:  http://www.example.com/index.php?id=12
 */

$path = $_SERVER['DOCUMENT_ROOT'];
$path .= "/dbConfig.php";
require_once($path);

const DB_ATTRIBUTE_ID = "id";
const DB_ATTRIBUTE_TEMP = "temperatur";
const DB_ATTRIBUTE_ROOM = "raum";
const DB_ATTRIBUTE_TIMESTAMP = "measuredAt";

const URL_PARAM_ID = "id";
const URL_PARAM_ROOM = "room";
const URL_PARAM_TEMP = "temp";
const URL_PARAM_TIMESTAMP = "timestamp";

$returnValue = "";

// Create database connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    http_response_code(500);
    die("Database error. Connection failed: " . $mysqli->connect_error);
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_GET) && isset($_GET[URL_PARAM_TEMP]) && isset($_GET[URL_PARAM_ROOM])) {
            insertIntoDB($mysqli, $_GET[URL_PARAM_TEMP], $_GET[URL_PARAM_ROOM]);
            $returnValue = "Created a new entry in the Database: temp=" . trim($_GET[URL_PARAM_TEMP]) . " & room=" . trim($_GET[URL_PARAM_ROOM]);
        } else {
            // error no data send
            http_response_code(400);
            die ("Error, no data send");
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // When url parameter is set, return specific dataset
        if (isset($_GET[URL_PARAM_ID])) {
            $returnValue = selectWhereEquals($mysqli, DB_ATTRIBUTE_ID, $_GET[URL_PARAM_ID]);
        } else if (isset($_GET[URL_PARAM_ROOM])) {
            $returnValue = selectWhereEquals($mysqli, DB_ATTRIBUTE_ROOM, $_GET[URL_PARAM_ROOM]);
        } else if (isset($_GET[URL_PARAM_TEMP])) {
            $returnValue = selectWhereEquals($mysqli, DB_ATTRIBUTE_TEMP, $_GET[URL_PARAM_TEMP]);
        } else {
            // When no GET PARAM set, return whole database
            $returnValue = selectAll($mysqli);
        }
    } else {
        http_response_code(400);
        die ("Error, wrong request type.");
    }
}
$mysqli->close();
echo $returnValue;


function temperatureMeasurementObject($id, $temperature, $room, $timestamp)
{
    return array(
        URL_PARAM_ID => $id,
        URL_PARAM_TEMP => $temperature,
        URL_PARAM_ROOM => $room,
        URL_PARAM_TIMESTAMP => $timestamp
    );
}

function getJsonObjectArrayFromDatabaseResult($result)
{
    $list = array();
    while ($row = $result->fetch_assoc()) {
        $id = $row[DB_ATTRIBUTE_ID];
        $temperature = $row[DB_ATTRIBUTE_TEMP];
        $room = $row[DB_ATTRIBUTE_ROOM];
        $timestamp = $row[DB_ATTRIBUTE_TIMESTAMP];

        $list[] = temperatureMeasurementObject($id, $temperature, $room, $timestamp);
    }
    // Convert to json array
    return json_encode($list);
}

function insertIntoDB($databaseConnection, $temperature, $room)
{
    $sql = 'INSERT INTO ' . DB_TABLE . ' (' . DB_ATTRIBUTE_TEMP . ', ' . DB_ATTRIBUTE_ROOM . ') VALUES (?, ?)';

    /* Prepared statement */
    if (!($stmt = $databaseConnection->prepare($sql)) || !$stmt->bind_param("ds", trim($temperature), trim($room)) || !$stmt->execute()) {
        http_response_code(400);
        die("Es ist ein Fehler aufgetreten");
    }
}

function selectWhereEquals($databaseConnection, $dbAttribute, $searchValue)
{
    $sql = 'SELECT * FROM ' . DB_TABLE . ' WHERE ' . $dbAttribute . ' = ? ORDER BY ' . DB_ATTRIBUTE_ID;

    /* Prepared statement */
    if (!($stmt = $databaseConnection->prepare($sql)) || !$stmt->bind_param("s", trim($searchValue)) || !$stmt->execute()) {
        http_response_code(400);
        die("Es ist ein Fehler aufgetreten");
    }

    return getJsonObjectArrayFromDatabaseResult($stmt->get_result());
}

function selectAll($databaseConnection)
{
    $sql = 'SELECT * FROM ' . DB_TABLE . ' ORDER BY ' . DB_ATTRIBUTE_ID;
    return getJsonObjectArrayFromDatabaseResult($databaseConnection->query($sql));
}
