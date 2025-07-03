<?php
// Configuration de la base de données

class Database {
    private $host = '185.207.226.9';
    private $db_name = 'rsneay_cakkysin_db';
    private $username = 'rsneay_cakkysin_db';
    private $password = 'w%WL*b86-f!OI54u';
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Erreur de connexion: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// Fonction globale pour obtenir une connexion
function getDB() {
    $database = new Database();
    return $database->getConnection();
}
?>