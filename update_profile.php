<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['agent_db_id'])) {
    header("Location: index.php?error=Sila log masuk terlebih dahulu.");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agent_db_id = $_SESSION['agent_db_id'];
    $new_name    = trim($_POST['name']);

    // Dapatkan gambar profil sedia ada
    $stmt = $conn->prepare("SELECT profile_pic, agent_code FROM agents WHERE id = ?");
    $stmt->bind_param("i", $agent_db_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $profile_pic_name = $res['profile_pic'];
    $agent_code = $res['agent_code'];

    // Jika ada gambar profil baharu dimuat naik
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_ext, $allowed)) {
            // Pastikan folder uploads wujud
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            $profile_pic_name = time() . '_' . $agent_code . '.' . $file_ext;
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], 'uploads/' . $profile_pic_name);
        }
    }

    // Kemaskini dalam pangkalan data
    $update_stmt = $conn->prepare("UPDATE agents SET name = ?, profile_pic = ? WHERE id = ?");
    $update_stmt->bind_param("ssi", $new_name, $profile_pic_name, $agent_db_id);

    if ($update_stmt->execute()) {
        $_SESSION['agent_name'] = $new_name;
        header("Location: dashboard.php?msg=Profil+berjaya+dikemaskini!");
    } else {
        header("Location: dashboard.php?error=Gagal+kemaskini+profil.");
    }
    exit();
}
?>