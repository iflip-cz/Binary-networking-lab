<?php
// Copy this file to  config.php  and fill in THIS server's database credentials.
//
// config.php is gitignored, so each environment (local XAMPP / webzdarma /
// Oracle VPS) keeps its own copy and a deploy never overwrites it.
//
// If config.php is missing, backend/funcDB.php falls back to the local XAMPP
// defaults shown below.

return [
    "host"    => "localhost",      // webzdarma often uses a named host, not localhost
    "dbname"  => "projekt_zwa",    // the DB name your host assigned you
    "user"    => "root",           // webzdarma/VPS give you a real user, not root
    "pass"    => "",               // and a real password
    "charset" => "utf8mb4",
];
