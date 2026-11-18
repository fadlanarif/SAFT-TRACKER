<?php
session_start();
require_once 'config/db.php';

// Semak jika ejen belum log masuk
if (!isset($_SESSION['agent_db_id'])) {
    header("Location: index.php?error=Sila log masuk terlebih dahulu.");
    exit();
}

$agent_db_id = $_SESSION['agent_db_id'];

// Dapatkan data penuh ejen semasa dari DB
$user_stmt = $conn->prepare("SELECT * FROM agents WHERE id = ?");
$user_stmt->bind_param("i", $agent_db_id);
$user_stmt->execute();
$current_agent = $user_stmt->get_result()->fetch_assoc();

$agent_name     = $current_agent['name'];
$agent_code     = $current_agent['agent_code'];
$my_profile_pic = (!empty($current_agent['profile_pic']) && file_exists('uploads/' . $current_agent['profile_pic'])) 
    ? 'uploads/' . $current_agent['profile_pic'] 
    : 'https://ui-avatars.com/api/?name=' . urlencode($agent_name) . '&background=5B3E7D&color=fff';

$success_msg = "";
$error_msg   = "";

// 1. PROSES PADAM GAMBAR AKTIVITI
if (isset($_GET['delete_photo_id'])) {
    $del_photo_id = intval($_GET['delete_photo_id']);
    
    $get_photo = $conn->prepare("SELECT photo_path FROM activity_photos WHERE id = ? AND agent_id = ?");
    $get_photo->bind_param("ii", $del_photo_id, $agent_db_id);
    $get_photo->execute();
    $photo_res = $get_photo->get_result();
    
    if ($photo_res->num_rows > 0) {
        $photo_data = $photo_res->fetch_assoc();
        if (file_exists($photo_data['photo_path'])) {
            unlink($photo_data['photo_path']);
        }
        
        $del_stmt = $conn->prepare("DELETE FROM activity_photos WHERE id = ? AND agent_id = ?");
        $del_stmt->bind_param("ii", $del_photo_id, $agent_db_id);
        $del_stmt->execute();
        
        header("Location: dashboard.php?msg=Gambar+aktiviti+berjaya+dipadam");
        exit();
    }
}

// 2. PROSES BORANG KEMASKINI METRIK & MUAT NAIK GAMBAR AKTIVITI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_performance'])) {
    $tpc_amount   = intval($_POST['tpc_amount']);   
    $cases_count  = intval($_POST['cases_count']);  
    $health_cases = intval($_POST['health_cases']);
    $persistency  = intval($_POST['persistency']);  
    $c_amax       = intval($_POST['c_amax']);       
    $qualifiers   = intval($_POST['qualifiers']);   
    $new_cases    = intval($_POST['new_cases']);    
    $bop_recruits = intval($_POST['bop_recruits']); 
    $mtd_amount   = intval($_POST['mtd_amount']);   
    $ytd_amount   = intval($_POST['ytd_amount']);   
    $month_year   = date('Y-m');

    // Kemaskini atau Tambah Metrik Prestasi
    $check_sql = "SELECT id FROM performance WHERE agent_id = ? AND month_year = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("is", $agent_db_id, $month_year);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $update_sql = "UPDATE performance SET 
            tpc_amount = ?, cases_count = ?, health_cases = ?, 
            persistency = ?, c_amax = ?, qualifiers = ?, 
            new_cases = ?, bop_recruits = ?, mtd_amount = ?, ytd_amount = ? 
            WHERE agent_id = ? AND month_year = ?";
        $u_stmt = $conn->prepare($update_sql);
        $u_stmt->bind_param("iiiiiiiiiiis", $tpc_amount, $cases_count, $health_cases, $persistency, $c_amax, $qualifiers, $new_cases, $bop_recruits, $mtd_amount, $ytd_amount, $agent_db_id, $month_year);
        $u_stmt->execute();
    } else {
        $insert_sql = "INSERT INTO performance 
            (agent_id, tpc_amount, cases_count, health_cases, persistency, c_amax, qualifiers, new_cases, bop_recruits, mtd_amount, ytd_amount, month_year) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $i_stmt = $conn->prepare($insert_sql);
        $i_stmt->bind_param("iiiiiiiiiiis", $agent_db_id, $tpc_amount, $cases_count, $health_cases, $persistency, $c_amax, $qualifiers, $new_cases, $bop_recruits, $mtd_amount, $ytd_amount, $month_year);
        $i_stmt->execute();
    }

    // Proses Muat Naik Gambar Aktiviti & Deskripsi
    if (isset($_FILES['activity_photo']) && $_FILES['activity_photo']['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['activity_photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $photo_desc = trim($_POST['activity_description'] ?? '');

        if (in_array($file_ext, $allowed)) {
            if (!is_dir('uploads/activities')) {
                mkdir('uploads/activities', 0777, true);
            }

            $photo_name = time() . '_' . $agent_code . '.' . $file_ext;
            $target_file = 'uploads/activities/' . $photo_name;

            if (move_uploaded_file($_FILES['activity_photo']['tmp_name'], $target_file)) {
                $photo_stmt = $conn->prepare("INSERT INTO activity_photos (agent_id, photo_path, description) VALUES (?, ?, ?)");
                $photo_stmt->bind_param("iss", $agent_db_id, $target_file, $photo_desc);
                $photo_stmt->execute();
            }
        } else {
            $error_msg = "Invalid image format. Please use only JPG, PNG, or WEBP formats";
        }
    }

    $success_msg = "Agency performance & metrics have been successfully updated!";
}

// Dapatkan data prestasi ejen semasa
$current_month = date('Y-m');
$my_perf = [
    'tpc_amount' => 0, 'cases_count' => 0, 'health_cases' => 0,
    'persistency' => 0, 'c_amax' => 0, 'qualifiers' => 0,
    'new_cases' => 0, 'bop_recruits' => 0, 'mtd_amount' => 0, 'ytd_amount' => 0
];
$p_sql = "SELECT * FROM performance WHERE agent_id = ? AND month_year = ?";
$p_stmt = $conn->prepare($p_sql);
$p_stmt->bind_param("is", $agent_db_id, $current_month);
$p_stmt->execute();
$p_res = $p_stmt->get_result();
if ($p_res->num_rows > 0) {
    $my_perf = $p_res->fetch_assoc();
}

// Dapatkan senarai gambar aktiviti ejen
$photos_sql = "SELECT * FROM activity_photos WHERE agent_id = ? ORDER BY id DESC";
$photos_stmt = $conn->prepare($photos_sql);
$photos_stmt->bind_param("i", $agent_db_id);
$photos_stmt->execute();
$my_photos = $photos_stmt->get_result();

// Dapatkan Leaderboard Agensi
$leaderboard_sql = "
    SELECT a.agent_code, a.name, a.profile_pic,
           COALESCE(p.tpc_amount, 0) as tpc, 
           COALESCE(p.cases_count, 0) as cases,
           COALESCE(p.persistency, 0) as persistency,
           COALESCE(p.c_amax, 0) as c_amax,
           COALESCE(p.qualifiers, 0) as qualifiers,
           COALESCE(p.new_cases, 0) as new_cases,
           COALESCE(p.bop_recruits, 0) as bop_recruits,
           COALESCE(p.mtd_amount, 0) as mtd_amount,
           COALESCE(p.ytd_amount, 0) as ytd_amount
    FROM agents a
    LEFT JOIN performance p ON a.id = p.agent_id AND p.month_year = '$current_month'
    WHERE a.role != 'leader' AND a.agent_code != 'admin'
    ORDER BY tpc DESC, ytd_amount DESC
";
$leaderboard_res = $conn->query($leaderboard_sql);
?>
<!DOCTYPE html>
<html lang="ms" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SAFT Advisory Performance Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
</head>
<body class="bg-gradient-to-br from-gray-100 via-gray-200 to-gray-300 dark:from-black dark:via-gray-900 dark:to-black min-h-screen text-gray-900 dark:text-gray-100 transition-colors duration-500 pb-12">

    <!-- Navigasi Atas -->
    <nav class="bg-white/40 dark:bg-white/10 backdrop-blur-xl border-b border-gray-300/60 dark:border-white/10 px-6 py-4 flex flex-wrap justify-between items-center shadow-md dark:shadow-xl sticky top-0 z-40 transition-all duration-500">
        <div class="flex items-center space-x-3">
            <span class="bg-black dark:bg-white text-white dark:text-black px-3 py-1 rounded-xl text-xs font-black tracking-wider uppercase transition-colors duration-500">SAFT TRACKER</span>
            <h1 class="text-lg font-bold tracking-wide text-gray-900 dark:text-white transition-colors duration-500">by SAFt KT IGNITE</h1>
        </div>
        <div class="flex items-center space-x-4 mt-2 sm:mt-0">
            <button 
                type="button" 
                onclick="toggleTheme()" 
                class="p-2.5 rounded-2xl bg-black/5 dark:bg-white/10 hover:bg-black/10 dark:hover:bg-white/20 border border-black/10 dark:border-white/20 text-gray-800 dark:text-white transition-all duration-300 focus:outline-none"
                title="Tukar Mod"
            >
                <svg id="sun-icon" class="w-4 h-4 hidden text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg id="moon-icon" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>

            <button onclick="openProfileModal()" class="flex items-center space-x-3 hover:bg-black/5 dark:hover:bg-white/10 p-1.5 rounded-2xl transition duration-200 border border-transparent hover:border-black/10 dark:hover:border-white/20">
                <img src="<?php echo $my_profile_pic; ?>" alt="Profile Picture" class="w-9 h-9 rounded-full object-cover border-2 border-gray-400 dark:border-white/40 shadow-md">
                <div class="text-right">
                    <p class="text-sm font-extrabold text-gray-900 dark:text-white leading-tight transition-colors duration-500"><?php echo htmlspecialchars($agent_name); ?></p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold transition-colors duration-500">Code: <?php echo htmlspecialchars($agent_code); ?></p>
                </div>
            </button>

            <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-2xl transition duration-200 shadow-lg active:scale-95">
                Logout
            </a>
        </div>
    </nav>

    <!-- Notifikasi -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="max-w-7xl mx-auto px-4 mt-6">
            <div class="p-3.5 bg-green-500/20 border border-green-500/40 text-green-700 dark:text-green-300 text-xs font-semibold rounded-2xl text-center backdrop-blur-md">
                <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Kandungan Utama -->
    <div class="max-w-7xl mx-auto px-4 py-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Bahagian Kiri (2 Kolum): Progress & Leaderboard -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Banner Header My Progress -->
            <div class="bg-white/40 dark:bg-white/10 backdrop-blur-xl border border-white/60 dark:border-white/20 p-6 rounded-3xl shadow-xl dark:shadow-2xl flex justify-between items-center transition-all duration-500">
                <div>
                    <h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-wide transition-colors duration-500">My Progress</h2>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-medium transition-colors duration-500">
                        Performance Presentation Session: <span class="font-extrabold underline text-gray-900 dark:text-white"><?php echo date('F Y'); ?></span>
                    </p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] text-gray-600 dark:text-gray-400 uppercase block font-semibold transition-colors duration-500">Status :</span>
                    <span class="bg-black/10 dark:bg-white/20 text-gray-900 dark:text-white border border-black/20 dark:border-white/30 text-xs px-3.5 py-1.5 rounded-full font-bold shadow-md tracking-wider">LIVE UPDATE</span>
                </div>
            </div>

            <!-- Kad Ringkasan Metrik Ejen Semasa -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white/40 dark:bg-white/10 backdrop-blur-xl p-4 rounded-2xl shadow-md dark:shadow-lg text-center border border-white/60 dark:border-white/20 transition-all duration-500">
                    <span class="text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider">P (Presentation)</span>
                    <p class="text-xl font-black text-gray-900 dark:text-white mt-1"><?php echo number_format($my_perf['persistency']); ?></p>
                </div>
                <div class="bg-white/40 dark:bg-white/10 backdrop-blur-xl p-4 rounded-2xl shadow-md dark:shadow-lg text-center border border-white/60 dark:border-white/20 transition-all duration-500">
                    <span class="text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider">C (Close Case)</span>
                    <p class="text-xl font-black text-gray-900 dark:text-white mt-1"><?php echo number_format($my_perf['c_amax']); ?></p>
                </div>
                <div class="bg-white/40 dark:bg-white/10 backdrop-blur-xl p-4 rounded-2xl shadow-md dark:shadow-lg text-center border border-white/60 dark:border-white/20 transition-all duration-500">
                    <span class="text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Q (Quotation)</span>
                    <p class="text-xl font-black text-gray-900 dark:text-white mt-1"><?php echo number_format($my_perf['qualifiers']); ?></p>
                </div>
                <div class="bg-white/40 dark:bg-white/10 backdrop-blur-xl p-4 rounded-2xl shadow-md dark:shadow-lg text-center border border-white/60 dark:border-white/20 transition-all duration-500">
                    <span class="text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider">NC (New Contact)</span>
                    <p class="text-xl font-black text-gray-900 dark:text-white mt-1"><?php echo number_format($my_perf['new_cases']); ?></p>
                </div>
                <div class="bg-white/40 dark:bg-white/10 backdrop-blur-xl p-4 rounded-2xl shadow-md dark:shadow-lg text-center border border-white/60 dark:border-white/20 transition-all duration-500">
                    <span class="text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider">BOP (Recruits)</span>
                    <p class="text-xl font-black text-gray-900 dark:text-white mt-1"><?php echo number_format($my_perf['bop_recruits']); ?></p>
                </div>
                <div class="bg-white/40 dark:bg-white/10 backdrop-blur-xl p-4 rounded-2xl shadow-md dark:shadow-lg text-center border border-white/60 dark:border-white/20 transition-all duration-500">
                    <span class="text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider">MTD (TPC)</span>
                    <p class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1"><?php echo number_format($my_perf['mtd_amount']); ?></p>
                </div>
                <div class="bg-white/40 dark:bg-white/10 backdrop-blur-xl p-4 rounded-2xl shadow-md dark:shadow-lg text-center border border-white/60 dark:border-white/20 col-span-2 transition-all duration-500">
                    <span class="text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider">YTD (TPC)</span>
                    <p class="text-xl font-black text-gray-900 dark:text-white mt-1"><?php echo number_format($my_perf['ytd_amount']); ?></p>
                </div>
            </div>

            <!-- GALERI GAMBAR AKTIVITI -->
            <div class="bg-white/40 dark:bg-white/10 backdrop-blur-xl border border-white/60 dark:border-white/20 p-6 rounded-3xl shadow-xl dark:shadow-2xl transition-all duration-500">
                <h3 class="text-base font-extrabold text-gray-900 dark:text-white mb-1 transition-colors duration-500 flex items-center gap-2">
                    📸 Photos of Activities & Presentations
                </h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-4 font-medium transition-colors duration-500">
                    Click on the image for a clear view with a full description
                </p>

                <?php if ($my_photos->num_rows > 0): ?>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        <?php while ($photo = $my_photos->fetch_assoc()): ?>
                            <div class="relative group cursor-pointer overflow-hidden rounded-2xl border border-gray-300/60 dark:border-white/20 bg-white/30 dark:bg-white/5 backdrop-blur-md transition-all duration-300 hover:scale-105 hover:shadow-xl"
                                 onclick="openPhotoModal(<?php echo $photo['id']; ?>, '<?php echo htmlspecialchars($photo['photo_path']); ?>', '<?php echo htmlspecialchars(addslashes($photo['description'])); ?>', '<?php echo date('d M Y, h:i A', strtotime($photo['created_at'])); ?>')">
                                <img src="<?php echo htmlspecialchars($photo['photo_path']); ?>" class="w-full h-28 object-cover transition duration-300 group-hover:opacity-80">
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition duration-300 flex items-center justify-center text-white text-[11px] font-bold">
                                    Lihat Butiran
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="p-4 rounded-2xl bg-white/30 dark:bg-white/5 border border-gray-300 dark:border-white/10 text-center text-gray-600 dark:text-gray-400 text-xs font-medium">
                        No activity photos have been uploaded
                    </div>
                <?php endif; ?>
            </div>

            <!-- Kad Jadual Leaderboard -->
            <div class="bg-white/40 dark:bg-white/10 backdrop-blur-xl border border-white/60 dark:border-white/20 rounded-3xl shadow-xl dark:shadow-2xl overflow-hidden transition-all duration-500">
                <div class="p-5 border-b border-gray-300/60 dark:border-white/10 flex justify-between items-center transition-colors duration-500 bg-white/20 dark:bg-transparent">
                    <h3 class="text-base font-extrabold text-gray-900 dark:text-white flex items-center gap-2 transition-colors duration-500">
                        🏆 Agent Leaderboard & Metrics
                    </h3>
                    <span class="text-xs text-gray-600 dark:text-gray-400 font-semibold transition-colors duration-500">Agent Dashboard</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="uppercase text-[11px] text-gray-700 dark:text-gray-400 border-b border-gray-300/60 dark:border-white/10 bg-white/30 dark:bg-white/5 transition-colors duration-500">
                            <tr>
                                <th class="py-3.5 px-3">Ranking</th>
                                <th class="py-3.5 px-3">Agent</th>
                                <th class="py-3.5 px-3 text-center">P</th>
                                <th class="py-3.5 px-3 text-center">C</th>
                                <th class="py-3.5 px-3 text-center">Q</th>
                                <th class="py-3.5 px-3 text-center">NC</th>
                                <th class="py-3.5 px-3 text-center">BOP</th>
                                <th class="py-3.5 px-3 text-right">MTD (TPC)</th>
                                <th class="py-3.5 px-3 text-right">YTD (TPC)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-300/50 dark:divide-white/10 transition-colors duration-500">
                            <?php 
                            $rank = 1;
                            if ($leaderboard_res->num_rows > 0):
                                while($row = $leaderboard_res->fetch_assoc()):
                                    $is_me = ($row['agent_code'] === $agent_code);
                                    $row_pic = (!empty($row['profile_pic']) && file_exists('uploads/' . $row['profile_pic'])) 
                                        ? 'uploads/' . $row['profile_pic'] 
                                        : 'https://ui-avatars.com/api/?name=' . urlencode($row['name']) . '&background=5B3E7D&color=fff';
                            ?>
                                <tr class="<?php echo $is_me ? 'bg-black/10 dark:bg-white/20 font-semibold border-l-4 border-gray-900 dark:border-white' : 'hover:bg-white/30 dark:hover:bg-white/10'; ?> transition duration-200">
                                    <td class="py-3.5 px-3 font-black text-gray-900 dark:text-white">
                                        <?php 
                                            if ($rank === 1) echo "🥇 #1";
                                            elseif ($rank === 2) echo "🥈 #2";
                                            elseif ($rank === 3) echo "🥉 #3";
                                            else echo "#" . $rank;
                                        ?>
                                    </td>
                                    <td class="py-3.5 px-3 flex items-center gap-2">
                                        <img src="<?php echo $row_pic; ?>" alt="Agent Pic" class="w-8 h-8 rounded-full object-cover border border-gray-400 dark:border-white/30 shadow-sm">
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white transition-colors duration-500"><?php echo htmlspecialchars($row['name']); ?></p>
                                            <span class="text-[10px] text-gray-600 dark:text-gray-400 font-medium transition-colors duration-500"><?php echo htmlspecialchars($row['agent_code']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-3 text-center font-bold text-gray-900 dark:text-white"><?php echo number_format($row['persistency']); ?></td>
                                    <td class="py-3.5 px-3 text-center font-medium text-gray-800 dark:text-gray-200"><?php echo number_format($row['c_amax']); ?></td>
                                    <td class="py-3.5 px-3 text-center font-medium text-gray-800 dark:text-gray-200"><?php echo number_format($row['qualifiers']); ?></td>
                                    <td class="py-3.5 px-3 text-center font-bold text-emerald-600 dark:text-emerald-400"><?php echo number_format($row['new_cases']); ?></td>
                                    <td class="py-3.5 px-3 text-center font-bold text-gray-900 dark:text-white"><?php echo number_format($row['bop_recruits']); ?></td>
                                    <td class="py-3.5 px-3 text-right text-emerald-600 dark:text-emerald-400 font-bold"><?php echo number_format($row['mtd_amount']); ?></td>
                                    <td class="py-3.5 px-3 text-right font-black text-gray-900 dark:text-white"><?php echo number_format($row['ytd_amount']); ?></td>
                                </tr>
                            <?php 
                                $rank++;
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="9" class="text-center py-8 text-gray-600 dark:text-gray-400 font-medium">No Record</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Bahagian Kanan (1 Kolum): Borang Kemaskini Metrik & Gambar Aktiviti -->
        <div class="space-y-6">
            <div class="bg-white/40 dark:bg-white/10 backdrop-blur-xl border border-white/60 dark:border-white/20 p-6 rounded-3xl shadow-xl dark:shadow-2xl transition-all duration-500">
                <h3 class="text-lg font-black text-gray-900 dark:text-white mb-1 transition-colors duration-500">Update your metrics</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-5 font-medium transition-colors duration-500">Enter your metric values ​​& upload activity photos</p>

                <?php if (!empty($success_msg)): ?>
                    <div class="mb-4 p-3.5 bg-green-500/20 border border-green-500/40 text-green-700 dark:text-green-300 text-xs font-semibold rounded-2xl text-center backdrop-blur-md">
                        <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error_msg)): ?>
                    <div class="mb-4 p-3.5 bg-red-500/20 border border-red-500/40 text-red-700 dark:text-red-300 text-xs font-semibold rounded-2xl text-center backdrop-blur-md">
                        <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <form action="dashboard.php" method="POST" enctype="multipart/form-data" class="space-y-3">
                    <input type="hidden" name="update_performance" value="1">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-700 dark:text-gray-300 mb-1">P (Presentation):</label>
                            <input type="number" name="persistency" value="<?php echo $my_perf['persistency']; ?>" class="w-full px-3.5 py-2.5 rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-300/80 dark:border-white/10 text-gray-900 dark:text-white text-sm font-bold shadow-inner focus:outline-none focus:ring-2 focus:ring-black/30 dark:focus:ring-white/40 transition-all backdrop-blur-md" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-700 dark:text-gray-300 mb-1">C (Close Case):</label>
                            <input type="number" name="c_amax" value="<?php echo $my_perf['c_amax']; ?>" class="w-full px-3.5 py-2.5 rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-300/80 dark:border-white/10 text-gray-900 dark:text-white text-sm font-bold shadow-inner focus:outline-none focus:ring-2 focus:ring-black/30 dark:focus:ring-white/40 transition-all backdrop-blur-md" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-700 dark:text-gray-300 mb-1">Q (Quotation):</label>
                            <input type="number" name="qualifiers" value="<?php echo $my_perf['qualifiers']; ?>" class="w-full px-3.5 py-2.5 rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-300/80 dark:border-white/10 text-gray-900 dark:text-white text-sm font-bold shadow-inner focus:outline-none focus:ring-2 focus:ring-black/30 dark:focus:ring-white/40 transition-all backdrop-blur-md" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-700 dark:text-gray-300 mb-1">NC (New Contact):</label>
                            <input type="number" name="new_cases" value="<?php echo $my_perf['new_cases']; ?>" class="w-full px-3.5 py-2.5 rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-300/80 dark:border-white/10 text-gray-900 dark:text-white text-sm font-bold shadow-inner focus:outline-none focus:ring-2 focus:ring-black/30 dark:focus:ring-white/40 transition-all backdrop-blur-md" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-700 dark:text-gray-300 mb-1">BOP (Recruits):</label>
                            <input type="number" name="bop_recruits" value="<?php echo $my_perf['bop_recruits']; ?>" class="w-full px-3.5 py-2.5 rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-300/80 dark:border-white/10 text-gray-900 dark:text-white text-sm font-bold shadow-inner focus:outline-none focus:ring-2 focus:ring-black/30 dark:focus:ring-white/40 transition-all backdrop-blur-md" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-700 dark:text-gray-300 mb-1">MTD (TPC):</label>
                            <input type="number" name="mtd_amount" value="<?php echo $my_perf['mtd_amount']; ?>" class="w-full px-3.5 py-2.5 rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-300/80 dark:border-white/10 text-gray-900 dark:text-white text-sm font-bold shadow-inner focus:outline-none focus:ring-2 focus:ring-black/30 dark:focus:ring-white/40 transition-all backdrop-blur-md" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase text-gray-700 dark:text-gray-300 mb-1">YTD (TPC):</label>
                        <input type="number" name="ytd_amount" value="<?php echo $my_perf['ytd_amount']; ?>" class="w-full px-3.5 py-2.5 rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-300/80 dark:border-white/10 text-gray-900 dark:text-white text-sm font-bold shadow-inner focus:outline-none focus:ring-2 focus:ring-black/30 dark:focus:ring-white/40 transition-all backdrop-blur-md" required>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-gray-700 dark:text-gray-300 mb-1">Total TPC:</label>
                            <input type="number" name="tpc_amount" value="<?php echo $my_perf['tpc_amount']; ?>" class="w-full px-3.5 py-2 rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-300/80 dark:border-white/10 text-gray-900 dark:text-white text-xs shadow-inner focus:outline-none focus:ring-2 focus:ring-black/30 dark:focus:ring-white/40 transition-all backdrop-blur-md" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-gray-700 dark:text-gray-300 mb-1">Total Cases:</label>
                            <input type="number" name="cases_count" value="<?php echo $my_perf['cases_count']; ?>" class="w-full px-3.5 py-2 rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-300/80 dark:border-white/10 text-gray-900 dark:text-white text-xs shadow-inner focus:outline-none focus:ring-2 focus:ring-black/30 dark:focus:ring-white/40 transition-all backdrop-blur-md" required>
                        </div>
                    </div>

                    <input type="hidden" name="health_cases" value="<?php echo $my_perf['health_cases']; ?>">

                    <!-- BAHAGIAN MUAT NAIK GAMBAR AKTIVITI & DESKRIPSI -->
                    <div class="pt-3 border-t border-gray-300/60 dark:border-white/10 space-y-3">
                        <h4 class="text-xs font-black uppercase tracking-wider text-gray-900 dark:text-white">📷 Add Activity Image:</h4>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-gray-700 dark:text-gray-300 mb-1">Upload Image:</label>
                            <input type="file" name="activity_photo" accept="image/*" class="w-full text-xs text-gray-700 dark:text-gray-300 bg-white/50 dark:bg-white/5 p-2 rounded-2xl border border-gray-300/80 dark:border-white/10 focus:outline-none file:mr-3 file:py-1 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-black/10 dark:file:bg-white/20 file:text-black dark:file:text-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-gray-700 dark:text-gray-300 mb-1">Activity Description:</label>
                            <textarea name="activity_description" rows="2" placeholder="Example: Sesi pembentangan bersama prospek di Mydin...Ambil medical card RM176" class="w-full px-3.5 py-2 rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-300/80 dark:border-white/10 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-xs focus:outline-none focus:ring-2 focus:ring-black/30 dark:focus:ring-white/40 transition-all backdrop-blur-md"></textarea>
                        </div>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full bg-black hover:bg-gray-800 dark:bg-white dark:hover:bg-gray-200 text-white dark:text-black font-extrabold py-3.5 px-4 rounded-2xl transition duration-200 shadow-xl active:scale-95 mt-4"
                    >
                        SAVE
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 1: EDIT PROFIL EJEN -->
    <div id="profileModal" class="fixed inset-0 bg-black/80 backdrop-blur-md hidden flex items-center justify-center p-4 z-50 transition-all duration-300">
        <div class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-white/20 rounded-3xl max-w-md w-full p-6 shadow-2xl relative text-center">
            <button onclick="closeProfileModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-900 dark:hover:text-white bg-black/5 dark:bg-white/10 hover:bg-black/10 dark:hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-1">Agent Profile</h3>
            <p class="text-xs text-gray-600 dark:text-gray-400 mb-6 font-medium">Update your profile information and photo</p>

            <form action="update_profile.php" method="POST" enctype="multipart/form-data" class="space-y-4 text-left">
                <div class="flex flex-col items-center mb-4">
                    <img src="<?php echo $my_profile_pic; ?>" alt="Large Profile Pic" class="w-24 h-24 rounded-full object-cover border-4 border-gray-300 dark:border-white/20 shadow-md mb-2">
                    <span class="text-xs font-bold text-gray-900 dark:text-white bg-black/5 dark:bg-white/10 border border-black/10 dark:border-white/20 px-3 py-1 rounded-full">Code: <?php echo htmlspecialchars($agent_code); ?></span>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 dark:text-gray-300 mb-1">Full Name:</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($agent_name); ?>" required class="w-full px-4 py-2.5 rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-300/80 dark:border-white/10 text-gray-900 dark:text-white text-sm font-bold focus:outline-none focus:ring-2 focus:ring-black/30 dark:focus:ring-white/40 transition-all backdrop-blur-md">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 dark:text-gray-300 mb-1">Change profile picture:</label>
                    <input type="file" name="profile_pic" accept="image/*" class="w-full text-xs text-gray-700 dark:text-gray-300 bg-white/50 dark:bg-white/5 p-2 rounded-2xl border border-gray-300/80 dark:border-white/10 focus:outline-none file:mr-3 file:py-1 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-black/10 dark:file:bg-white/20 file:text-black dark:file:text-white">
                </div>
                <div class="pt-3 flex gap-3">
                    <button type="button" onclick="closeProfileModal()" class="w-1/2 bg-black/5 dark:bg-white/10 hover:bg-black/10 dark:hover:bg-white/20 border border-black/10 dark:border-white/20 text-gray-900 dark:text-white text-xs font-bold py-3 rounded-2xl transition">
                        CANCEL
                    </button>
                    <button type="submit" class="w-1/2 bg-black dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-200 text-white dark:text-black text-xs font-black py-3 rounded-2xl transition shadow-xl active:scale-95">
                        SAVE PROFILE
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: POPUP GAMBAR AKTIVITI & DESKRIPSI (DENGAN BUTANG PADAM) -->
    <div id="photo-modal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-4 transition-all duration-300">
        <div class="relative w-full max-w-lg bg-white dark:bg-gray-900 border border-gray-300 dark:border-white/20 rounded-3xl p-6 shadow-2xl overflow-hidden text-gray-900 dark:text-gray-100">
            <button onclick="closePhotoModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-900 dark:hover:text-white bg-black/5 dark:bg-white/10 hover:bg-black/10 dark:hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <h3 class="text-base font-extrabold text-gray-900 dark:text-white mb-3">Butiran Aktiviti Ejen</h3>
            <div class="space-y-4">
                <img id="modal-img" src="" class="w-full max-h-80 object-contain rounded-2xl border border-gray-300/60 dark:border-white/10 bg-black/10 dark:bg-black/50">
                <div>
                    <span id="modal-date" class="text-[10px] text-gray-600 dark:text-gray-400 block font-semibold mb-1"></span>
                    <p id="modal-desc" class="text-xs text-gray-800 dark:text-gray-200 leading-relaxed font-medium bg-white/60 dark:bg-white/5 p-3 rounded-xl border border-gray-300 dark:border-white/10"></p>
                </div>
                
                <!-- Butang Padam Gambar -->
                <div class="flex justify-end pt-2 border-t border-gray-200 dark:border-white/10">
                    <a id="btn-delete-photo" href="#" onclick="return confirm('Adakah anda pasti mahu memadam gambar aktiviti ini?');" class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2 rounded-2xl transition shadow-md active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Padam Gambar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Script JavaScript -->
    <script>
        function openProfileModal() {
            document.getElementById('profileModal').classList.remove('hidden');
        }
        function closeProfileModal() {
            document.getElementById('profileModal').classList.add('hidden');
        }

        function openPhotoModal(photoId, path, desc, dateStr) {
            document.getElementById('modal-img').src = path;
            document.getElementById('modal-desc').innerText = desc ? desc : "Tiada deskripsi diberikan.";
            document.getElementById('modal-date').innerText = "Tarikh dimuat naik: " + dateStr;
            document.getElementById('btn-delete-photo').href = "dashboard.php?delete_photo_id=" + photoId;
            document.getElementById('photo-modal').classList.remove('hidden');
        }
        function closePhotoModal() {
            document.getElementById('photo-modal').classList.add('hidden');
        }

        function toggleTheme() {
            const html = document.documentElement;
            const sunIcon = document.getElementById('sun-icon');
            const moonIcon = document.getElementById('moon-icon');

            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                sunIcon.classList.remove('hidden');
                moonIcon.classList.add('hidden');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                sunIcon.classList.add('hidden');
                moonIcon.classList.remove('hidden');
                localStorage.setItem('theme', 'dark');
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            const savedTheme = localStorage.getItem('theme');
            const sunIcon = document.getElementById('sun-icon');
            const moonIcon = document.getElementById('moon-icon');

            if (savedTheme === 'light') {
                document.documentElement.classList.remove('dark');
                if(sunIcon && moonIcon) {
                    sunIcon.classList.remove('hidden');
                    moonIcon.classList.add('hidden');
                }
            } else {
                document.documentElement.classList.add('dark');
                if(sunIcon && moonIcon) {
                    sunIcon.classList.add('hidden');
                    moonIcon.classList.remove('hidden');
                }
            }
        });
    </script>
</body>
</html>