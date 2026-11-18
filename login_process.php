<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agent_code = trim($_POST['agent_code']);
    $password = trim($_POST['password']);

    if (empty($agent_code) || empty($password)) {
        header("Location: index.php?error=Sila isi semua medan.");
        exit();
    }

    // Semak ejen/admin dalam pangkalan data menggunakan agent_code
    $stmt = $conn->prepare("SELECT id, agent_code, name, password, role FROM agents WHERE agent_code = ?");
    $stmt->bind_param("s", $agent_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $agent = $result->fetch_assoc();

        // 1. Semak kata laluan sebenar melalui hash
        $is_password_correct = password_verify($password, $agent['password']);

        // 2. Kebenaran khas HANYA untuk akaun admin/leader jika kata laluan 'admin123' atau '123456'
        $is_admin_bypass = ($agent['role'] === 'leader' || $agent['agent_code'] === 'admin') && ($password === 'admin123' || $password === '123456');

        if ($is_password_correct || $is_admin_bypass) {
            
            $_SESSION['agent_db_id'] = $agent['id'];
            $_SESSION['agent_code']  = $agent['agent_code'];
            $_SESSION['agent_name']   = $agent['name'];
            $_SESSION['agent_role']   = $agent['role'];

            // Haluan mengikut peranan
            if ($agent['role'] === 'leader' || $agent['agent_code'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            header("Location: index.php?error=Wrong Password !");
            exit();
        }
    } else {
        header("Location: index.php?error=Agent Code / Admin ID not found");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>