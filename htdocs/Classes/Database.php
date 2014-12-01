<?php
if(!defined('DIRECT')){die('Direct access not permitted');}
/**
 * User: Astrazone.com
 * Date: 7/24/13
 * Time: 4:39 PM
 */

/**********************************************************
    Class Definition
***********************************************************/
class Database{
    // Database handler - PDO object
    private $m_DatabaseHandler;

    // Store the error
    private $m_Error = "";

    // Statement created
    private $m_Statement;

    public function __construct(){
        // Set DSN
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
        // Set options
        $options = array(
            PDO::ATTR_PERSISTENT    => true,
            PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION
        );

        // Create a new PDO instance
        try
        {
            $this->m_DatabaseHandler = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        }
        // Catch any errors
        catch(PDOException $e){
            $this->m_Error = $e->getMessage();
        }
    }

    /* Generate a prepared statement
     *
     * @param $Query    String query to prepare
     *
    */
    public function query($Query){
        $this->m_Statement = $this->m_DatabaseHandler->prepare($Query);
    }

    /* Bind a value to a parameter in the prepared statement
     *
     * @param $Param    Parameter name
     * @param $Value    Value to bind
     * @pamar $Type     Parameter type (optional)
     *
    */
    public function bind($Param, $Value, $Type = null){
        if (is_null($Type)) {
            switch (true) {
                case is_int($Value):
                    $Type = PDO::PARAM_INT;
                    break;
                case is_bool($Value):
                    $Type = PDO::PARAM_BOOL;
                    break;
                case is_null($Value):
                    $Type = PDO::PARAM_NULL;
                    break;
                default:
                    $Type = PDO::PARAM_STR;
            }
        }
        $this->m_Statement->bindValue($Param, $Value, $Type);
    }

    /*
     * Execute the statement and return the result
    */
    public function Execute(){
        return $this->m_Statement->execute();
    }

    /*
     * Return the error message
     *
     * @return Error string
    */
    public function getError(){
        return $this->m_Error;
    }

    /*
     * Return the array of data after the execution
    */
    public function GetResultset(){
        $this->execute();
        return $this->m_Statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
     * Return a single object of data after the execution
    */
    public function GetSingle(){
        $this->execute();
        return $this->m_Statement->fetch(PDO::FETCH_ASSOC);
    }

    /*
     * Begin the transaction. Start collecting the executions, don't send yet.
    */
	public function BeginTransaction(){
        $this->m_DatabaseHandler->beginTransaction();
    }

    /*
     * Send all collected executions.
    */
	public function Commit(){
        $this->m_DatabaseHandler->commit();
    }

    /*
     * Return the row count of the last execution
     *
     * @return Number of rows
    */
    public function GetRowCount(){
        return $this->m_Statement->rowCount();
    }

    /*
     * Return the last inserted ID
     *
     * @return ID
    */
    public function GetLastInsertId(){
        return $this->m_DatabaseHandler->lastInsertId();
    }

    /*
     * Returns valuable debug parameters
     *
     * @return debug data
    */
    public function GetDebugDumpParams(){
        return $this->m_Statement->debugDumpParams();
    }
}