<?php

class Database {

    private $connection;
    private $last_query;
    private $magic_quotes_active;
    private $real_escape_string_exists;

    /*
     * mysql    : 1
     * postgres : 2
     * */
    public $type;
    public $host;
    public $name;
    public $user;


    function __construct($type, $host, $name, $user, $pass){
        $this->type = $type;
        $this->host = $host;
        $this->name = $name;
        $this->user = $user;
        $this->open_connection($host, $name, $user, $pass);

        $this->magic_quotes_active = get_magic_quotes_gpc();
        $this->real_escape_string_exists = function_exists("mysql_real_escape_string");
    }

    public function open_connection($host, $name, $user, $pass){
        if($this->type == 1){
            // create database connection
            $this->connection = mysqli_connect($host, $user, $pass);
            //define function if database connection failed
            if(!$this->connection){
                die("Database connection failed: " . mysql_error());
            } else {
                $dbSelect = mysqli_select_db($this->connection, $name);
                if(!$dbSelect){
                    die("Database connection failed: " . mysql_error());
                }
            }
        } elseif($this->type == 2) {
            // create database connection
            $this->connection = pg_connect("host=".$host." dbname=".$name ." user=". $user ." password=".$pass);
            //define function if database connection failed
            if(!$this->connection){
                die("Database connection failed: " . pg_last_error());
            }
        }
    }

    public function close_connection(){
        if($this->type == 1){
            // close connection
            if(isset($this->connection)){
                mysqli_close($this->connection);
                unset($this->connection);
            }
        } elseif($this->type == 2){
            // close connection
            if(isset($this->connection)){
                pg_close($this->connection);
                unset($this->connection);
            }
        }
    }

    public function query($sql){
        if($this->type == 1){
            $this->last_query = $sql;
            $result = mysqli_query($this->connection, $sql);
            $this->confirm_query($result);
            return $result;
        } elseif($this->type == 2){
            $this->last_query = $sql;
            $result = pg_query($sql);
            $this->confirm_query($result);
            return $result;
        }
    }

    public function escape_value($value){
        if($this->type == 1){
            if( $this->real_escape_string_exists ){//PHP v4.3.0 or higher
                //undo any magic quotes so mysql_real_escape_string can do the work
                if($this->magic_quotes_active){ $value = stripslashes( $value ); }
                $value = mysqli_real_escape_string($this->connection, $value);
            }else{//before PHP v4.3.0
                //if magic qoutes isnt on, add slashes manually
                if(!$this->magic_quotes_active){ $value = addcslashes( $value, "\\" ); }
                //if magic quotes are active, then the slashes already exists
            }
            return $value;
        } elseif($this->type == 2){
            $value = pg_escape_string($this->connection, $value);
            return $value;
        }
    }

    public function fetch_array($result_set){
        if($this->type == 1){
            return mysqli_fetch_array($result_set);
        } elseif($this->type == 2){
            return pg_fetch_array($result_set);
        }
    }

    public function num_rows($result_set){
        if($this->type == 1){
            return mysqli_num_rows($result_set);
        } elseif($this->type == 2) {
            return pg_num_rows($result_set);
        }
    }

    // get the last id inserted over the current db connection
    public function insert_id($table_name=null){
        if($this->type == 1){
            return mysqli_insert_id($this->connection);
        } elseif($this->type == 2){
            // get the last id inserted over the current db connection
            $sql = "SELECT currval(pg_get_serial_sequence('" . $table_name . "','id'));";
            $result = $this->query($sql);
            $array = $this->fetch_array($result);
            $lastID = array_shift($array);
            return $lastID;
        }
    }

    public function affected_rows($result=null){
        if($this->type == 1){
            return mysqli_affected_rows($this->connection);
        } elseif($this->type == 2){
            return pg_affected_rows($result);
        }
    }

    private function confirm_query($result){
        if($this->type == 1){
            if (!$result && mysql_error() != 0) {
                $output = "Database query failed: " . mysql_error() . "<br /><br />";
                $output .= "Last SQL query: " . $this->last_query;
                die( $output );
            }
        } elseif($this->type == 2){
            if (!$result) {
                $output = "Database query failed: " . pg_last_error() . "<br /><br />";
                $output .= "Last SQL query: " . $this->last_query;
                die( $output );
            }
        }
    }
}