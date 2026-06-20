<?php
require_once 'db.php';

class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Complaint Login: Accepts Email, CNIC, or Reg No
     */
    public function login($identifier, $password) {
        // Query checks all 3 columns simultaneously
        $sql = "SELECT * FROM users WHERE 
                (email = :id OR identity_no = :id OR registration_no = :id) 
                AND is_active = 1 LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            return true;
        }
        return false;
    }

    /**
     * Fetch Publicly Available Roles for Registration
     * Excludes 'Super Admin' or system protected roles for security
     */
    public function getPublicRoles() {
        // Exclude super_admin explicitly to prevent privilege escalation
        $stmt = $this->pdo->prepare("SELECT role_name, role_key FROM sys_roles WHERE role_key != 'super_admin' AND is_system_role != 1");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Register New User
     */
    public function register($data) {
        // validation: Check if identifier already exists
        if ($this->checkExists($data['email'], $data['identity_no'], $data['registration_no'])) {
            return "User already exists (Email, CNIC, or Reg No is taken).";
        }

        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, password, role, identity_no, registration_no, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, 0)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['name'], 
                $data['email'], 
                $hash, 
                $data['role'], 
                $data['identity_no'], 
                $data['registration_no']
            ]);
            return true;
        } catch (PDOException $e) {
            return "Database Error: " . $e->getMessage();
        }
    }

    private function checkExists($email, $cnic, $regNo) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? OR identity_no = ? OR registration_no = ?");
        $stmt->execute([$email, $cnic, $regNo]);
        return $stmt->rowCount() > 0;
    }
}
?>