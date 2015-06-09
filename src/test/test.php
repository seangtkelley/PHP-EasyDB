<?php

include('Database.php');
include('DatabaseTable.php');
include('TestTable.php');


$mysql = new Database(1, "localhost", "chimera", "Keysmash", "y0105Tu9i05!");
$postgres = new Database(2, "localhost", "postgres", "postgres", "root");

Test::checkTableStatus($mysql);
Test::checkTableStatus($postgres);