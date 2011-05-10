<?php
    
    /**
     * Mysqli db driver
     * Part of database class
     */

    class Driver_Mysqli {

        private $mysqli;
        private $HostName;
        private $UserName;
        private $Password;
        private $DatabaseName;
        public  $ErrorInfo;
        private $result;
        private $cachedQueries = array();

        function __construct() {
            
        }

        function __destruct() {
            mysqli_free_result($this->result);
            $this->stmt->close();
        }
        
        /**
         *
         * @param mixed $dns Array of connection information
         */
        function setDsn($dns) {
            $this->HostName = $dns['dbhost'];
            $this->UserName = $dns['username'];
            $this->Password = $dns['password'];
            $this->DatabaseName = $dns['database'];
        }

        #	The following method establish a connection with the database server and on success return TRUE, on failure return FALSE
        #	On failure ErrorInfo property contains the error information.

        function dbConnect() {
            $this->mysqli = new mysqli($this->HostName, $this->UserName, $this->Password, $this->DatabaseName);
            if (!$this->mysqli) {
                $this->ErrorInfo = mysqli_connect_error();
                return FALSE;
            } else {
                return TRUE;
            }
        }



        function dbClose() {
            $this->mysqli->close();
        }


        /**
         *  Function to perform dml operations
         * @param mixed $Query Query to be executed
         * @return query execution status 
         */

        function setQuery($Query) {
            $ExecStatus = $this->mysqli->query($Query);
            if ($ExecStatus === FALSE) {
                $this->ErrorInfo = $this->mysqli->error;
                return FALSE;
            } else {
                return $ExecStatus;
            }
        }

        
        # On Success returns number of records corresponding to the query, else return 0	

        function numberOfRecords($Query) {
            $RowCount = 0;
            $this->result = $this->mysqli->query($Query);
            if ($this->result) {
                $RowCount = $this->result->num_rows;
                return $RowCount;
            } else {
                $this->ErrorInfo = $this->mysqli->error;
                return $RowCount;
            }
        }


        /**
         *  Function to return set of rows 
         * @param mixed $Query Query to be executed
         * @return two dimentional array of records 
         */

        function readValues($Query) {
            $ResultData = array();
            $this->result = $this->mysqli->query($Query);

            if ($this->result) {
                $RowCount = $this->result->num_rows;
                for ($i = 0; $i < $RowCount; $i++)
                    $ResultData[$i] = $this->result->fetch_array();
                return $ResultData;
            } else {
                $this->ErrorInfo = $this->mysqli->error;
                return $ResultData;
            }
        }


        /**
         *  Function to return a single row
         * @param type Query to be executed
         * @return single array of record
         */

        function readValue($Query) {
            $ResultData = array();
            $this->result = $this->mysqli->query($Query);

            if ($this->result) {
                $ResultData[0] = $this->result->fetch_array();
                return $ResultData[0];
            } else {
                $this->ErrorInfo = $this->mysqli->error;
                return $ResultData;
            }
        }


        /**
         *  Method to return last inserted id
         * @return int last inserted id
         */

        function getInsertId() {
            return $this->mysqli->insert_id;
        }
        
        # this function is not necessary
        function readField($Query) {
            $ResultData = array();
            $this->result = $this->mysqli->query($Query);

            if ($this->result) {
                $ResultData[0] = $this->result->fetch_array();
                return $ResultData[0];
            } else {
                $this->ErrorInfo = $this->mysqli->error;
                return $ResultData;
            }
        }


        # Method to execute Stored Procedures with return value

        function execProc($qry) {
            $result = array();
            $result = $this->mysqli->query($qry);
            if ($result) {
                $row = $result->fetch_array();
                return $row;
                $result->free();
            } else {
                $this->ErrorInfo = $this->mysqli->error;
                return $result;
            }
        }

        function execProcOne($qry) {
            $result = array();
            $res = array();
            $result = $this->mysqli->query($qry);
            if ($result) {
                while ($row = $result->fetch_array()) {
                    $res[] = $row;
                }
                return $res;
                $result->free();
            } else {
                $this->ErrorInfo = $this->mysqli->error;
                return $result;
            }
        }

        function setProc($Query) {
            $ExecStatus = $this->mysqli->query($Query);
            if ($ExecStatus === FALSE) {
                $this->ErrorInfo = $this->mysqli->error;
                return FALSE;
            } else {
                return $ExecStatus;
            }
        }
        
        /**
         * Method to escape special characters from a string
         * @param string $text
         * @return escaped string 
         */
        
        function escape($text) {
            $text = mysqli_real_escape_string($this->mysqli, $text);
            return $text;
        }

        function quoteEscape($text) {
            $type = gettype($text);
            switch ($type) {
                case 'integer':
                    return $text;
                    break;
                default:
                    return "'" . $this->escape($text) . "'";
            }
        }

        /**
         * @param mixed $query   Query to be prepared for executing
         *                      multiple times
         * Usage  "SELECT * FROM table_name WHERE column = ? "
         *        "INSERT INTO table_name(field1,field2) VALUES(?,?) ";
         * returns true if the query is a valid mysql statement else throws an
         * exception
         * 
         */
        function prepare($query) {
            if (!($this->stmt = $this->isCached($query))) {
                $this->stmt = $this->mysqli->prepare($query);
                $this->cacheQuery($query, $this->stmt);
            }
            if (!($this->stmt instanceof mysqli_stmt))
                throw new Exception('Invalide query : ' . $this->mysqli->error);
            else {
                return true;
            }
        }

        /**
         *
         * @param mixed   $values  Array or single value that need to be processed
         *                         using already created prepared statment.
         * @param boolean $isManip Whether the query is a data manipulation
         *                         query or not. 
         * @return mixed  $output  If @see $isManip is true return value will be
         *                         boolean else it will be an array
         * Usage execute(value); or execute(array(value1,value2), true);
         */
        function execute($values, $isManip = false, &$stmt = '') {
            $stmt = ($stmt == '') ? $this->stmt : $stmt;
            //check whether the statment is a valid prepared statment.
            if (!($stmt instanceof mysqli_stmt))
                throw new Exception('Invalide query : ' . $this->mysqli->error);
            $ptype = '';

            //Check if  $values is an array or not
            if (is_array($values)) {
                // Check the datatype of each value in the array and generate
                // corresponding typestring $ptype
                foreach ($values as $value) {
                    if (is_int($value))
                        $ptype .= 'i';
                    elseif (is_float($value))
                        $ptype .='d';
                    else
                        $ptype .= 's';
                    $params[] = $value;
                }
            }
            else {
                if (is_int($values))
                    $ptype .= 'i';
                elseif (is_float($values))
                    $ptype .='d';
                else
                    $ptype .= 's';
                $params[] = $values;
            }

            //Combine the $params array, $this->stmt and $ptype to be passed as
            //an argument for call_user_func_array()
            array_unshift($params, $stmt, $ptype);
            call_user_func_array('mysqli_stmt_bind_param', $this->refValues($params));

            //Execute the prepared statment
            $output = $stmt->execute();

            // Build the output array if its not a data manipulation query
            if ($isManip === false) {
                $data = $stmt->result_metadata();
                $fields = array();
                $out = array();

                $fields[0] = $stmt;
                $count = 1;

                while ($field = mysqli_fetch_field($data)) {
                    $fields[$count] = &$out[$field->name];
                    $count++;
                }
                call_user_func_array('mysqli_stmt_bind_result', $fields);
                $i = 0;
                while ($stmt->fetch()) {
                    $result[] = $out;
                }
                if (count($result) == 1)
                    $output = array_pop($result);
                else
                    $output = $result;
            }
            if (isset($output))
                return $output;
            else
                return false;
        }

        // Php version above 5.3 need arguments of mysqli_stmt_bind_param
        // function to be passed by reference. This function will recieve an array
        // and will change each array elements to a reference to array values.
        function refValues($params) {
            if (strnatcmp(phpversion(), '5.3') >= 0) {
                for ($i = 1; $i < count($params); $i++)
                    $params[$i] = &$params[$i];
            }
            return $params;
        }

        function numColumns() {
            $cols = @mysqli_num_fields($this->result);
        }

        function getColumns() {
            $column = 0;
            $columns = array();
            $numCols = $this->numColumns();
            for ($column = 0; $column < $numCols; $column++) {
                $columnInfo = @mysqli_fetch_field_direct($this->result, $column);
                $columns[$columnInfo->name] = $column;
            }
            print_r($columns);
        }

        /**
         *  Add a prepared statement to an array so tahat it can be reused
         *  if the same query is executed again
         * @param mixed $name
         * @param mysqli_stmt $stmt
         */
        function cacheQuery($name, $stmt) {
            $this->cachedQueries[$name] = $stmt;
        }

        /**
         * Check if query is cached or not. If yes returns the statement object
         * @param mixed $name
         * @return mysqli_stmt
         */
        function isCached($name) {
            if (array_key_exists($name, $this->cachedQueries)) {
                return $this->cachedQueries[$name];
            } else {
                return false;
            }
        }

    }

    # Close class definition
?>