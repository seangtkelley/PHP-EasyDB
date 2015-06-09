<?php

class DatabaseTable {


    protected static $table_name = "";
    protected static $db_fields = array();

    protected static $db_types = array();

    private $database;

    /*
     *
     * Check the status of the database table
     *
     * */
    public static function checkTableStatus($database){
        if($database->type == 1){
            $sql = "SELECT COUNT(*)
                    FROM information_schema.tables
                    WHERE table_schema = '". $database->name ."'
                    AND table_name = '" . static::$table_name . "';";

            $result = $database->query($sql);
            $array = $database->fetch_array($result);
            $count = array_shift($array);
            if($count == 0){
                // users table does not exist, create it

                $sql = "CREATE TABLE " . static::$table_name . " (";
                for($i = 0; $i < count(static::$db_fields); $i++){
                    if( $i == count(static::$db_fields)-1 ){
                        $sql .= static::$db_fields[$i] ." " . static::$db_types[$i];
                    } else {
                        $sql .= static::$db_fields[$i] ." " . static::$db_types[$i] . ", ";
                    }
                }

                $sql .= ") ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";
                $database->query($sql);

                $sql = "ALTER TABLE " . static::$table_name . " ADD PRIMARY KEY (id);";
                $database->query($sql);

                $sql = "ALTER TABLE " . static::$table_name . "
                          MODIFY id int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;";
                $database->query($sql);

            } else {
                // table does exist, but check all columns

                $sql = "";
                for($i = 0; $i < count(static::$db_fields); $i++){
                    $sql = "SELECT COUNT(*)
                            FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = '" . $database->name . "'
                            AND TABLE_NAME = '" . static::$table_name . "'
                            AND COLUMN_NAME = '" . static::$db_fields[$i] . "';";
                    $result = $database->query($sql);
                    $array = $database->fetch_array($result);
                    $count = array_shift($array);
                    if($count == 0){
                        // column doesn't exist

                        $sql = "ALTER TABLE " . static::$table_name . "
                                ADD COLUMN ". static::$db_fields[$i] . " " . static::$db_types[$i] . "
                                    AFTER " . static::$db_fields[$i-1] . ";";

                        $database->query($sql);
                    } else {
                        // column does exist, check for same data types
                        $sql = "SELECT DATA_TYPE
                            FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = '". $database->name ."'
                            AND TABLE_NAME = '" . static::$table_name . "'
                            AND COLUMN_NAME = '" . static::$db_fields[$i] . "';";
                        $result = $database->query($sql);
                        $array = $database->fetch_array($result);
                        $datatype = array_shift($array);
                        if (strpos($datatype, str_replace(" NOT NULL", "", static::$db_types[$i])) !== false) {
                            // all good

                        } else {
                            // column has the wrong data type
                            $sql = "ALTER TABLE " . static::$table_name . "
                                ALTER COLUMN " . static::$db_fields[$i] . " TYPE " . str_replace(" NOT NULL", "", static::$db_types[$i]) . ";";
                            $database->query($sql);
                        }
                    }
                }
            }
        } elseif($database->type == 2) {
            $sql = "SELECT COUNT(*)
                    FROM information_schema.tables
                    WHERE table_schema = 'public'
                    AND table_name = '" . static::$table_name . "';";

            $result = $database->query($sql);
            $array = $database->fetch_array($result);
            $count = array_shift($array);
            if ($count == 0) {
                // users table does not exist

                $sql = "CREATE TABLE " . static::$table_name . " (";
                for ($i = 0; $i < count(static::$db_fields); $i++) {
                    if ($i == count(static::$db_fields) - 1) {
                        $sql .= static::$db_fields[$i] . " " . static::$db_types[$i];
                    } else {
                        $sql .= static::$db_fields[$i] . " " . static::$db_types[$i] . ", ";
                    }
                }

                $sql .= ");";
                $database->query($sql);

                $sql = "ALTER TABLE " . static::$table_name . " OWNER TO " . $database->user . ";";
                $sql .= "CREATE SEQUENCE " . static::$table_name . "_id_seq
                        START WITH 1
                        INCREMENT BY 1
                        NO MINVALUE
                        NO MAXVALUE
                        CACHE 1;";
                $sql .= "ALTER TABLE " . static::$table_name . "_id_seq OWNER TO " . $database->user . ";";
                $sql .= "ALTER SEQUENCE " . static::$table_name . "_id_seq OWNED BY " . static::$table_name . ".id;";
                $sql .= "ALTER TABLE ONLY " . static::$table_name . " ALTER COLUMN id SET DEFAULT nextval('" . static::$table_name . "_id_seq'::regclass);";
                $sql .= "GRANT ALL ON TABLE " . static::$table_name . " TO " . $database->user . ";";
                $database->query($sql);

            } else {
                // table does exist, but check all columns

                $sql = "";
                for ($i = 0; $i < count(static::$db_fields); $i++) {
                    $sql = "SELECT COUNT(*)
                            FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = 'public'
                            AND TABLE_NAME = '" . static::$table_name . "'
                            AND COLUMN_NAME = '" . static::$db_fields[$i] . "';";
                    $result = $database->query($sql);
                    $array = $database->fetch_array($result);
                    $count = array_shift($array);
                    if ($count == 0) {
                        // column doesn't exist

                        $sql = "ALTER TABLE " . static::$table_name . "
                                ADD COLUMN " . static::$db_fields[$i] . " " . static::$db_types[$i] . "
                                    AFTER " . static::$db_fields[$i - 1] . ";";

                        $database->query($sql);
                    } else {
                        // column does exist, check for same data types
                        $sql = "SELECT DATA_TYPE
                            FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = 'public'
                            AND TABLE_NAME = '" . static::$table_name . "'
                            AND COLUMN_NAME = '" . static::$db_fields[$i] . "';";
                        $result = $database->query($sql);
                        $array = $database->fetch_array($result);
                        $datatype = array_shift($array);
                        if (strpos($datatype, str_replace(" NOT NULL", "", static::$db_types[$i])) !== false) {
                            // all good

                        } else {
                            // column has the wrong data type
                            $sql = "ALTER TABLE " . static::$table_name . "
                                ALTER COLUMN " . static::$db_fields[$i] . " TYPE " . str_replace(" NOT NULL", "", static::$db_types[$i]) . ";";
                            $database->query($sql);
                        }
                    }
                }
            }
        }
    }



    /*
     *
     * Find all rows
     *
     * */
    public static function find_all($database){
        return static::find_by_sql($database, "SELECT * FROM " . static::$table_name);
    }

    /*
     *
     * Find row by id
     *
     * */
    public static function find_by_id($database, $id=0){
        $result_array = static::find_by_sql($database, "SELECT * FROM " . static::$table_name ." WHERE id={$id} LIMIT 1");
        return !empty($result_array) ? array_shift($result_array) : false;
    }

    /*
     *
     * Find row by SQL
     *
     * */
    public static function find_by_sql($database, $sql=""){
        $result_set = $database->query($sql);
        $object_array = array();
        $i = 0;
        while($row = $database->fetch_array($result_set)){
            $object_array[$i] = static::instantiate($row);
            $object_array[$i]->database = $database;
            $i++;
        }
        return $object_array;
    }

    /*
     *
     * Count all rows in table
     *
     * */
    public static function count_all($database){
        $sql = "SELECT COUNT(*) FROM " . static::$table_name;
        $result_set = $database->query($sql);
        $row = $database->fetch_array($result_set);
        return array_shift($row);
    }

    /*
     *
     * Using a database result row, instantiate an instance
     * of the class
     *
     * */
    private static function instantiate($record){
        $object = new static;

        foreach($record as $attribute=>$value){
            if($object->has_attribute($attribute)){
                $object->$attribute = $value;
            }
        }
        return $object;
    }

    /*
     *
     * Check it the class has a specified attribute
     *
     * */
    private function has_attribute($attribute){
        $object_vars = $this->attributes();
        return array_key_exists($attribute, $object_vars);
    }

    /*
     *
     * Get all attributes of the class
     *
     * */
    protected function attributes(){
        $attributes = array();
        foreach(static::$db_fields as $field){
            if(property_exists($this, $field)){
                $attributes[$field] = $this->$field;
            }
        }
        return $attributes;
    }

    /*
     *
     * Either update or create the row based on if
     * the instance has a id or not
     *
     * */
    public function save(){
        return isset($this->id) ? $this->update($this->database) : $this->create($this->database);
    }

    /*
     *
     * Create the database row
     *
     * */
    public function create(){
        // - INSERT INTO table (key, key) VALUES ('value', 'value')
        // - single quotes around all values
        // - escape all values to prevent SQL injection
        $sql = "INSERT INTO " . static::$table_name . " (";
        for ($i=1; $i<count(static::$db_fields); $i++){
            if($i == count(static::$db_fields) - 1) {
                $sql = $sql . static::$db_fields[$i];
            } else {
                $sql = $sql . static::$db_fields[$i] . ", ";
            }
        }

        $sql .= ") VALUES ('";
        for ($i=1; $i<count(static::$db_fields); $i++){
            if($i == count(static::$db_fields) - 1) {
                $TEMPNAME = static::$db_fields[$i];
                $sql .= $this->database->escape_value($this->$TEMPNAME) . "')";
            } else {
                $TEMPNAME = static::$db_fields[$i];
                $sql .= $this->database->escape_value($this->$TEMPNAME) . "', '";
            }
        }

        if($this->database->query($sql)){
            $this->id = $this->database->insert_id(static::$table_name);
            return true;
        } else {
            return false;
        }
    }

    /*
     *
     * Update the existing row
     *
     * */
    public function update(){
        // - UPDATE table SET key='value', key='value' WHERE condition
        $sql = "UPDATE " . static::$table_name ." SET ";
        for ($i=1; $i<count(static::$db_fields); $i++){
            if($i == count(static::$db_fields) - 1) {
                $TEMPNAME = static::$db_fields[$i];
                $sql .= static::$db_fields[$i] . "='" . $this->database->escape_value($this->$TEMPNAME) . "'";
            } else {
                $TEMPNAME = static::$db_fields[$i];
                $sql .= static::$db_fields[$i] . "='" . $this->database->escape_value($this->$TEMPNAME) . "', ";
            }
        }

        $sql .= " WHERE id=" . $this->database->escape_value($this->id);
        $result = $this->database->query($sql);
        return ($this->database->affected_rows($result) == 1) ? true : false;
    }

    /*
     *
     * Delete the row
     *
     * */
    public function delete(){
        // - DELETE FROM table WHERE condition LIMIT 1
        $sql = "DELETE FROM " . static::$table_name;
        $sql .= " WHERE id=" . $this->database->escape_value($this->id);
        $sql .= " LIMIT 1";
        $result = $this->database->query($sql);
        return ($this->database->affected_rows($result) == 1) ?  true : false;
    }
}