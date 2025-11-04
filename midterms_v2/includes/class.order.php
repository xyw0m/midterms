<?php
/**
 * class.order.php
 * Handles creation and retrieval of sales orders and associated items.
 */

class Order {
    private $db;
    private $order_table = 'orders';
    private $order_items_table = 'order_items';

    /**
     * Constructor now requires the database connection object.
     * @param mysqli $db The database connection object.
     */
    public function __construct(mysqli $db) {
        // Assign the passed-in database connection
        $this->db = $db;

        if (!$this->db) {
            // Handle case where $db is null
            throw new Exception("Database connection not initialized.");
        }
    }

    /**
     * Saves a new order transaction and its items to the database.
     *
     * @param int $user_id ID of the user (cashier/admin) making the sale.
     * @param float $total_amount The calculated total price of the order.
     * @param float $amount_paid The amount paid by the customer.
     * @param float $change_due The change returned to the customer.
     * @param array $cart The associative array of products and quantities.
     * @return bool True on success, false on failure.
     */
    public function createOrder($user_id, $total_amount, $amount_paid, $change_due, $cart) {
        $this->db->begin_transaction();

        try {
            // 1. Insert into the orders table
            $stmt = $this->db->prepare("INSERT INTO " . $this->order_table . " (user_id, total_amount, amount_paid, change_due, cart_details) VALUES (?, ?, ?, ?, ?)");
            
            // Serialize the full cart array for the cart_details column (JSON format)
            $cart_json = json_encode($cart);
            
            $stmt->bind_param("iddds", $user_id, $total_amount, $amount_paid, $change_due, $cart_json);

            if (!$stmt->execute()) {
                throw new Exception("Order insertion failed: " . $stmt->error);
            }
            
            $order_id = $this->db->insert_id;
            $stmt->close();
            
            // 2. Insert into the order_items table for each product
            // Prepare statement once outside the loop
            $stmt = $this->db->prepare("INSERT INTO " . $this->order_items_table . " (order_id, product_id, quantity, price_at_sale) VALUES (?, ?, ?, ?)");
            
            foreach ($cart as $product_id => $item) {
                // Ensure product_id is an integer for binding
                $pid_int = (int)$product_id;
                
                // Binding parameters for order_items table
                $stmt->bind_param("iiid", $order_id, $pid_int, $item['qty'], $item['price']);
                
                if (!$stmt->execute()) {
                    throw new Exception("Order item insertion failed for product ID {$product_id}: " . $stmt->error);
                }
            }
            
            $stmt->close();
            
            // Commit transaction if all inserts were successful
            $this->db->commit();
            return true;

        } catch (Exception $e) {
            // Rollback transaction on any error
            $this->db->rollback();
            error_log("Transaction failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves sales history from the database.
     * @return array List of orders.
     */
    public function getSalesHistory() {
        // Use a JOIN to get the username along with the order
        $query = "
            SELECT 
                o.id, 
                o.order_date, 
                o.total_amount, 
                o.amount_paid,
                o.change_due,
                u.username AS cashier_name
            FROM " . $this->order_table . " o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.order_date DESC
        ";
        
        $result = $this->db->query($query);
        
        $orders = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        return $orders;
    }

    // You can add methods for filtering by date range here later if needed
}
?>
