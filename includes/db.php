<?php
// Edit these for your machine.
const DB_HOST = '127.0.0.1';
const DB_NAME = 'snakebite';
const DB_USER = 'root';
const DB_PASS = '';
const BASE = '/BioTriage';      // URL folder of this project. Use '' if it is the web root.
const STALE_HOURS = 24;         // Proposal: agree this limit with UH.

date_default_timezone_set('Asia/Manila');

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+08:00'",
        ]);
    }
    return $pdo;
}
