<?php
class Database {
    private $host = DB_SERVER;
    private $user = DB_USERNAME;
    private $pass = DB_PASSWORD;
    private $dbname = DB_NAME;

    private $dbh; // Database Handler
    private $stmt; // Statement
public function getOrdersByDateRange($start_date, $end_date) {
        $orders = [];

        // Ensure the end date includes the entire day (up to the last second)
        $end_date_time = $end_date . ' 23:59:59';

        $sql = "SELECT 
                    order_id, 
                    order_date, 
                    total_amount 
                FROM orders 
                WHERE order_date BETWEEN ? AND ? 
                ORDER BY order_date DESC";

        // IMPORTANT: Replace $this->conn with your actual database connection variable 
        // if it's named differently (e.g., $this->db)
        if (isset($this->conn) && $stmt = $this->conn->prepare($sql)) {
            
            // Assuming MySQLi: 'ss' stands for two string parameters
            if (method_exists($stmt, 'bind_param')) {
                $stmt->bind_param("ss", $start_date, $end_date_time);
            }
            
            if ($stmt->execute()) {
                
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $orders[] = $row;
                }
                
                $stmt->close();
            } else {
                // For debugging: prints the error to your PHP error log
                error_log("Database query failed: " . $stmt->error);
            }
        } else {
            error_log("Failed to prepare statement or connection missing.");
        }

        return $orders;
    }
    public function __construct() {
        // Set DSN (Data Source Name)
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        // Create a new PDO instance
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            die("Connection Failed: " . $e->getMessage());
        }
    }

    // Prepare statement with query
    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
    }

    // Bind values
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    // Execute the prepared statement
    public function execute() {
        return $this->stmt->execute();
    }

    // Get result set as array of objects
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Get single record as object
    public function single() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    // Get row count
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    // Get last inserted ID
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }
}
?>