<?php 


/**
* Model class v2
* @author Maghaza Team
*/
abstract class Model
{
    protected static       
        $_pk = 'id', 
        $_data;
    protected
        $_columnsName,
        $_db,
        $_id;

    private 
        $_isNew = false,
        $_modifiedFields = array(),
        $_reflectionObject;
    
    public function __construct()
    {
        $this->_isNew = true;
        $this->_db = DB::getInstance();
    }

    public static function data()
    {
        return self::$_data;
    }

    public static  function setData($data)
    {
        self::$_data = $data;
    }

    public function setId($id)
    {
        $this->_id = $id;
    }

    public function getID()
    {
        return $this->_id;
    }

    public static function db()
    {
        //$this->_db = DB::getInstance();
        return DB::getInstance();
    }

    /**
     * Get the PK field name for this ER class.
     * 
     * @access public
     * @static
     * @return string
     */
    public static function getTablePk ()
    {
        $className = get_called_class();

        return $className::$_pk;
    }

    /**
     * Get the table name
     * 
     * @access public
     * @static
     * @return string
     */
    public static function getTableName()
    {
        $class = get_called_class();
        $reflectionClass = new ReflectionClass($class);
        $value = ($reflectionClass->getProperty('__tableName__')->getValue());    
        if ($value) {
            return ($value);
        }
        else return (get_called_class());
    }

    public static function setTableName($table)
    {
        self::$_tableName = $table;
    }

    /**
     * Get the database name
     * 
     * @access public
     * @static
     * @return string
     */
    public static function getDatabaseName()
    {
        return Config::get('database/db');
    }

    public static function getColumnsName()
    {
       self::db()->describe(self::getTableName());
        if (self::db()->error())
            throw new \Exception(sprintf('Unable to fetch the column names. %s.',self::db()->error()));

        $ret = array();

        foreach (self::db()->results() as $row) {
            $ret[] = $row->Field;
        }

        return $ret;
    }

    public static function find($id = null)
    {
        if ($id == null) {
            die('Please specifiy an id');
        }
        else{
            if (self::getTablePk() == 'id') {
                self::db()->find(self::getTableName(), $id);
            }
            else{
                self::db()->findBy(self::getTableName(), self::getTablePk(), $id);
            }
        }
        if (self::db()->count()) {
            /*$this->_data = (self::db()->getFirst());
            return self::db()->getFirst();*/
            $class = get_called_class();
            $model = new $class();
            $model->setData(self::db()->getFirst());
            $model->appendData($model->data());
            $model->setNotNew();
            $model->setId(self::db()->getFirst()->id);
            return $model;
        }
        return false;
    }

    public static function findBy($by ,$id = null)
    {
        $columns = self::getColumnsName();
        if (!in_array($by, $columns)) {
            die($by.' N\'exsiste pas la base de donnée.');
        }
        if ($id == null) {
           die('Please specifiy an id');
        }
        else{            
            self::db()->findBy(self::getTableName(), $by, $id);
        }

        if (self::db()->count()) {
            $class = get_called_class();
            $model = new $class();
            $model->appendData(self::db()->getFirst());
            $model->setNotNew();
            $model->setId(self::db()->getFirst()->id);
            return $model;
        }
        return false;
    }

    public static function all($limit = 'all', $order = 'id', $method = 'ASC')
    {
        $columns = self::getColumnsName();
        if (!in_array($order, $columns)) {
            die($order.' N\'exsiste pas la base de donnée.');
        }
        if ($method != 'ASC' && $method !='DESC') {
            die('Veuillez choisir un ordre ASC ou DESC.');
        }

        if ($limit == "all") {
            $sql = "select * from ".self::getTableName()." order by $order $method";
        }
        else{
            if(Validate::isNumeric($limit)) die($limit.' N\'est pas une valeur numérique');
            $sql = "select * from ".self::getTableName()." order by $order $method LIMIT $limit";            
        }
        self::db()->query($sql);
        if (self::db()->count() > 0) {
           return self::db()->results();
        }
        else return false;
    }

    public static function allWhere($where = array(), $limit = 'all', $order = 'id', $method = 'ASC')
    {
        $columns = self::getColumnsName();
        if (!in_array($order, $columns)) {
            die($order.' N\'exsiste pas la base de donnée.');
        }
        if ($method != 'ASC' && $method !='DESC') {
            die('Veuillez choisir un ordre ASC ou DESC.');
        }

        $condition = '';
        $values = array();
        foreach ($where as $case) {
            if(isset($case[3])){
                $condition .= "{$case[0]} {$case[1]} ? {$case[3]} ";
                $values[] = $case[2];                
            }else{                
                $condition .= "{$case[0]} {$case[1]} ?";
                $values[] = $case[2];
                break;
            }
        }

        if ($limit == "all") {
            $sql = "select * from ".self::getTableName()." where {$condition} order by $order $method";
        }
        else{
            if(Validate::isNumeric($limit)) die($limit.' N\'est pas une valeur numérique');
            $sql = "select * from ".self::getTableName()." where {$condition} order by $order $method LIMIT $limit";            
        }
        self::db()->query($sql, $values);
        if (self::db()->count() > 0) {
           return self::db()->results();
        }
        else return false;
    }

    public static function getFields($fields = array(), $id = 'all', $order = 'id', $method = 'ASC', $limit = 'all')
    {
        $toGet = '';
        $x = 1;
        $columns = self::getColumnsName();
        if (!in_array($order, $columns)) {
            die($order.' N\'exsiste pas la base de donnée.');
        }
        if ($method != 'ASC' && $method !='DESC') {
            die('Veuillez choisir un ordre ASC ou DESC.');
        }
        foreach ($fields as $field) {
            if (!in_array($field, $columns)) {
                die($field.' N\'existe pas dans la base de donnée.');
            }
            $toGet .= $field;   
            if ($x < count($fields)) {
                $toGet .=', ';
                $x++;
            }
        }
        if ($limit == 'all') {
            if ($id == 'all') {
                $sql = "select {$toGet} from ".self::getTableName()." order by $order $method";
            }
            else {
                $sql = "select {$toGet} from ".self::getTableName()." where ".self::getTablePk()." = ? order by $order $method";
            }
        }
        else{
            if(!is_numeric($limit)) die($limit.' N\'est pas une valeur numérique');
            else {                
                $limit = (int)$limit;
                if ($id == 'all') {
                    $sql = "select {$toGet} from ".self::getTableName()." order by $order $method limit $limit";
                }
                else {
                    $sql ="select {$toGet} from ".self::getTableName()." where ".self::getTablePk()." = ? order by $order $method limit $limit";
                    echo $sql;
                }
            }
        }
        
       self::db()->query($sql, array($id));

        if (self::db()->count() > 0) {
            return self::db()->results();
        }
        else return false;        
    }

    public static function getFieldsWhere($fields = array(), $where = array(), $order = 'id', $method = 'DESC', $limit = 'all')
    {
        $toGet = '';
        $x = 1;

        $columns = self::getColumnsName();
        if (!in_array($order, $columns)) {
            die($order.' N\'exsiste pas la base de donnée.');
        }
        if ($method != 'ASC' && $method !='DESC') {
            die('Veuillez choisir un ordre ASC ou DESC.');
        }

        foreach ($fields as $field) {
            $toGet .= $field;   
            if ($x < count($fields)) {
                $toGet .=', ';
                $x++;
            }
        }
        // where usename = field AND password = $field
        /*where(
            array('user' = $value),
            array($field, $operator, $value) 
        );*/
        $condition = '';
        $values = array();
        foreach ($where as $case) {
            if(isset($case[3])){
                $condition .= "{$case[0]} {$case[1]} ? {$case[3]} ";
                $values[] = $case[2];                
            }else{                
                $condition .= "{$case[0]} {$case[1]} ?";
                $values[] = $case[2];
                break;
            }
        }
        if (!count($where)) {
            if ($limit == 'all') {
                $sql = "select {$toGet} from ".self::getTableName()." order by {$order} {$method}";
            }
            else $sql = "select {$toGet} from ".self::getTableName()." order by {$order} {$method} limit {$limit}";
            self::db()->query($sql);
        }
        else{
            if ($limit == 'all') {
                $sql = "select {$toGet} from ".self::getTableName()." where {$condition} order by {$order} {$method}";
            }
            else $sql ="select {$toGet} from ".self::getTableName()." where {$condition} order by {$order} {$method} limit {$limit}";
           self::db()->query($sql, $values);
        }

        if (self::db()->count() > 0) {
            return self::db()->results();
        }
        else return false;        
    }

    public function insert()
    {
        if (!$this->isNew()) {
            die('Record is not new, can\'t update');
        }
        $toAdd = array();
        $columns = self::getColumnsName();
        foreach ($columns as $column) {
            if ($column == self::getTablePk()) {
                continue;
            }
            if (empty($this->{$column})) {
                $toAdd[$column] = '';
            }
            $toAdd[$column] = $this->{$column};
        }
        /*
        $this->db()->insert(self::getTableName(), $toAdd);*/
        if(!$this->db()->insert(self::getTableName(), $toAdd)){
            return false;
        }
        else {
            $this->setId(self::db()->getLastInsertId());
            $this->setNotNew();
            $this->_modifiedFields = array();
            return true;
        }
    }

    public function update()
    {
        if ($this->isNew()) {
            die('can\'t update new record');
        }
        else{
            $toUpdate = $this->getModifiedFields();
            $res = self::db()->update(self::getTableName(), self::getTablePk(), $this->getId(), $toUpdate);
            if ($res) {
                $this->_modifiedFields = array();
                return true;
            }
            else return false;
        }

    }

    public function save()
    {
        if ($this->isNew()) {
            return $this->insert();
        }
        else return $this->update();
    }

    public function isNew()
    {
        return $this->_isNew;
    }

    public function setNotNew()
    {
        $this->_isNew = false;
    }

    public function delete()
    {
        if (self::db()->delete(self::getTableName(), array(self::getTablePk(), '=', $this->getId()))->error()) {
            return false;
        }
        else return true;

        //self::db()->delete(self::getTableName(), array(self::getTablePk(), '=', $this->getId()));
    }

    /**
     * Get a value for a particular field or all values.
     * 
     * @access public
     * @param string $fieldName If false (default), the entire record will be returned as an array.
     * @return array | string
     */
    public function get ($fieldName = false)
    {
        // return all data
        if ($fieldName === false)
            return self::convertObjectToArray($this);

        return $this->{$fieldName};
    }
    /**
     * Convert an object to an array.
     *
     * @access public
     * @static
     * @param object $object
     * @return array
     */
    public static function convertObjectToArray ($object)
    { 
        if (!is_object($object))
            return $object;

        $array = array();
        $r = new ReflectionObject($object);

        foreach ($r->getProperties(ReflectionProperty::IS_PUBLIC) AS $key => $value)
        {
            $key = $value->getName();
            $value = $value->getValue($object);
        
            $array[$key] = is_object($value) ? self::convertObjectToArray($value) : $value;
        }

        return $array;
    }

    /**
     * Set a new value for a particular field.
     * 
     * @access public
     * @param string $fieldName
     * @param string $newValue
     * @return void
     */
    public function set ($fieldName, $newValue)
    {
        // if changed, mark object as modified
        if ($this->{$fieldName} != $newValue)
            $this->modifiedFields($fieldName, $newValue);

        $this->{$fieldName} = $newValue;
        
        return $this;
    }

    /**
     * Check if our record has been modified since boot up.
     * This is only available if you use set() to change the object.
     * 
     * @access public
     * @return array | false
     */
    public function isModified ()
    {
        return (count($this->modifiedFields) > 0) ? $this->modifiedFields : false;
    }

    /**
     * Mark a field as modified & add the change to our history.
     * 
     * @access private
     * @param string $fieldName
     * @param string $newValue
     * @return void
     */
    private function modifiedFields ($fieldName, $newValue)
    {
        // add modified field to a list
        if (!isset($this->_modifiedFields[$fieldName]))
        {
            $this->_modifiedFields[$fieldName] = $newValue;

            return;
        }

        // already modified, initiate a numerical array
        if (!is_array($this->_modifiedFields[$fieldName]))
            $this->_modifiedFields[$fieldName] = array($this->_modifiedFields[$fieldName]);

        // add new change to array
        $this->_modifiedFields[$fieldName][] = $newValue;
    }

    public function getModifiedFields()
    {
        return $this->_modifiedFields;
    }
    public function appendData($data)
    {
        $this->setId($data->{self::getTablePk()});
        foreach ($data as $key => $add) {
           $this->{$key} = $add;
        }
    }
}

?>