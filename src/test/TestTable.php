<?php

class Test extends DatabaseTable {

    protected static $table_name = "test";
    protected static $db_fields = array(
        'id',
        'test'
    );

    protected static $db_types = array(
        'int(11) NOT NULL',         // id
        'varchar(11) NOT NULL'     // test
    );

    public $id;
    public $test;


}