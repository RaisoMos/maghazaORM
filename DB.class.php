<?php
/**
 * Connection ORM class v1.1
 * @access public
 * @author maghaza team
 */

class DB 
{
    protected static $_instance; // the instance of the object
    protected 
        $_pdo, // PDO object to access to database
        $_query, // Containing the query that gonna be executed
        $_error = false, //containing errors
        $_results, //containing the result records
        $_count = 0,
        $_lastId, //counting the number of records
        $_connection_error = false, //checking if there is an error while connecting to database
        $_connection_error_message; //getting the connection database


    /**
     * constructor of the class who creates the PDO oject
     * @access protected
     * @return void
     */
    protected function __construct() {
        try {
            $this->_pdo = new PDO(
                Config::get('database/driver').":host=".Config::get('database/host').";dbname=".Config::get('database/db'), 
                Config::get('database/user'), 
                Config::get('database/password'));
        } catch(PDOException $e) {
            $_connection_error = true;
            $_connection_error_message = $e->getMessage();
            die ('Erreur de connection à la base de donnée.');
        }
    }

    /**
     * Getting the unique instance of the oject
     * @access public
     * @return PDO Object
    */
    public static function getInstance() {
        if (!isset(self::$_instance)) {
            self::$_instance = new DB();
        }
        return self::$_instance;
    }

    /**
     * Executing sql query
     * @access public
     * @return the class object
     * @param $sql the sql query to execute
     * @param $params parameters belong to $sql
     */
    public function query($sql, $params = array())
    {
        $this->_error = false;
        if ($this->_query = $this->_pdo->prepare($sql)) {
            //checking parameters 
            //set counter to each value
            $p = 1;
            if (count($params)) {
                foreach ($params as $param) {
                    $this->_query->bindValue($p, $param);
                $p++;
                }
            }
        //execute query
            if ($this->_query->execute()) {
            //returning an object
                $this->_results = $this->_query->fetchAll(PDO::FETCH_OBJ);
                $this->_count = $this->_query->rowCount();
            }else {
                $this->_error = true;
            }

        }
        return $this;
    }

    /**
     *  Execute an sql query
     *  @return the class object or false if there is an error
     *  @param $action specify the action that we want to execute
     *  @param $table specift the table that we want to act with
     *  @param $where an erray that specify the where close
     */
    public function action($action, $table, $where = array()){
        if (count($where) === 3) {
            $operators = array('=','>','<','>=','<=', '!=');

            $field     = $where[0];
            $operator   = $where[1];
            $value      = $where[2];

            if (in_array($operator, $operators)) {
                $sql = $action." FROM {$table} WHERE {$field} {$operator} ?";
                if (!$this->query($sql, array($value))->error()) {
                    return $this;
                }
            }
        }
        return false;
    }

    /**
     *  insert data into a specific table
     *  @return boolean
     *  @param $table specift the table that we want to act with
     *  @param $fields assosiative array that contains what we want to insert
     */
    public function insert($table, $fields = array())
    {
        $keys   = array_keys($fields);
        $values = null;
        $p      = 1;

        foreach ($fields as $field) {
            $values .= '?';
            if ($p < count($fields)) {
                $values .= ', ';
            }
            $p++;
        }

        $sql = "INSERT INTO {$table} (`" . implode('`, `', $keys) ."`) VALUES ({$values})";

        if (!$this->query($sql, $fields)->error()) {
            $this->_lastId = $this->_pdo->lastInsertId();
            return true;
        }
        return false;
    }

    /**
     *  update data in a specific table
     *  @return boolean
     *  @param $table specift the table that we want to act with
     *  @param $id specify the id of the record that gonna be updated
     *  @param $fields assosiative array that contains what we want to update
     */
    public function update($table, $id, $valeur, $fields){
        $set = '';
        $x = 1;

        foreach ($fields as $name => $value) {
            $set .= "{$name} = ?";
            if ($x < count($fields)) {
                $set .= ", ";
            }
            $x++;
        }

        $sql = "UPDATE {$table} set {$set} where {$id} = {$valeur}";

        if (!$this->query($sql, $fields)->error()) {
            return true;
        }
        return false;
    }

    /**
     *  grap all data from a table using a where close
     *  @return the class object or false if there is an error
     *  @param $table specift the table that we want to act with
     *  @param $where represents the where close
     */
    public function get($table, $where){
        return $this->action("SELECT *", $table, $where);
    }

    /**
     *  grap all data from a table
     *  @return the class object or false if there is an error
     *  @param $table specift the table that we want to act with
     */
    public function getAll($table){
        $sql = 'SELECT * FROM '.$table;
        return $this->query($sql);
    }

    /**
     *  find a specific record in a table by id
     *  @return the class object or false if there is an error
     *  @param $table specify the table that we want to act with
     *  @param $id that we want to find
     */
    public function find($table, $id){
        return $this->get($table, array('id', '=', $id));
    }

    /**
     *  find the first record 
     *  @return the class object or false if there is an error
     *  @param $table specify the table that we want to act with
     *  @param $id that we want to find
     */
    public function getFirst(){
        return $this->results()[0];
    }
    /**
     *  find a specific record in a table
     *  @return the class object or false if there is an error
     *  @param $table specify the table that we want to act with
     *  @param $id that we want to find
     */
    public function findBy($table, $by, $value){
        return $this->get($table, array($by, '=', $value));
    }

    /**
     *  delete a specific record in a table
     *  @return the class object or false if there is an error
     *  @param $table specify the table that we want to act with
     *  @param $where the where close that we deal with
     */
    public function delete($table, $where){
        return $this->action('DELETE', $table, $where);
    }

    public function deleteById($table, $id){
        return $this->delete($table, array('id', '=', $id));
    }

    public function describe($table)
    {
        $sql = "describe {$table}";
        $this->query($sql);
    }

    public function results(){
        return $this->_results;
    }
    public function first(){
        return $this->_results[0];
    }
    public function error() {
        return $this->_error;
    }
    public function count(){
        return $this->_count;
    }
    public function getLastInsertId(){
        return $this->_lastId;
    }
} 
?>