<?php
require_once 'class.database.php';

class Product {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Add a new product
    public function addProduct($name, $price, $image_path, $added_by_user_id) {
        $this->db->query('INSERT INTO products (name, price, image_path, added_by_user_id) VALUES (:name, :price, :image_path, :added_by_user_id)');
        $this->db->bind(':name', $name);
        $this->db->bind(':price', $price);
        $this->db->bind(':image_path', $image_path);
        $this->db->bind(':added_by_user_id', $added_by_user_id);

        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    // Get all products
    public function getProducts() {
        $this->db->query('SELECT p.*, u.username AS added_by_username FROM products p JOIN users u ON p.added_by_user_id = u.id ORDER BY p.date_added DESC');
        return $this->db->resultSet();
    }

    // Get product by ID
    public function getProductById($id) {
        $this->db->query('SELECT * FROM products WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Update product
    public function updateProduct($id, $name, $price, $image_path = null) {
        if ($image_path) {
            $this->db->query('UPDATE products SET name = :name, price = :price, image_path = :image_path WHERE id = :id');
            $this->db->bind(':image_path', $image_path);
        } else {
            $this->db->query('UPDATE products SET name = :name, price = :price WHERE id = :id');
        }
        $this->db->bind(':id', $id);
        $this->db->bind(':name', $name);
        $this->db->bind(':price', $price);

        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    // Delete product
    public function deleteProduct($id) {
        $this->db->query('DELETE FROM products WHERE id = :id');
        $this->db->bind(':id', $id);

        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }
}
?>