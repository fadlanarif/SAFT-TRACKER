<?php
session_start();
require_once 'config/db.php';

// Semak jika pengguna belum log masuk atau bukan admin/leader
if (!isset($_SESSION['agent_db_id']) || ($_SESSION['agent_role'] !== 'leader' && $_SESSION['agent_code'] !== 'admin')) {
    header("Location: index.php?error=Akses ditolak. Halaman ini khas untuk Admin/Leader sahaja.");
    exit();
}

$admin_name = $_SESSION['agent_name'];
$current_month = date('Y-m');
$msg = "";
$msg_type = "";

// 1. PROSES TAMBAH EJEN BAHARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_agent'])) {
    $new_agent_code = trim($_POST['agent_code']);
    $new_name       = trim($_POST['name']);
    $new_pass       = trim($_POST['password']);
    $hashed_pass    = password_hash($new_pass, PASSWORD_DEFAULT);
    
    // Process Profile Picture, jenis format2 yang dibenarkan
    $profile_pic_name = "default.png";
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_ext, $allowed)) {
            $profile_pic_name = time() . '_' . $new_agent_code . '.' . $file_ext;
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], 'uploads/' . $profile_pic_name);
        }
    }

    $check = $conn->prepare("SELECT id FROM agents WHERE agent_code = ?");
    $check->bind_param("s", $new_agent_code);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        $msg = "Agent Code ini telah wujud!";
        $msg_type = "error";
    } else {
        $add_stmt = $conn->prepare("INSERT INTO agents (agent_code, name, password, role, profile_pic) VALUES (?, ?, ?, 'agent', ?)");
        $add_stmt->bind_param("ssss", $new_agent_code, $new_name, $hashed_pass, $profile_pic_name);
        if ($add_stmt->execute()) {
            $msg = "Ejen baharu berjaya didaftarkan!";
            $msg_type = "success";
        }
    }
}

// 2. PROSES KEMASKINI (UPDATE) EJEN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_agent'])) {
    $agent_db_id  = intval($_POST['agent_db_id']);
    $agent_code   = trim($_POST['agent_code']);
    $name         = trim($_POST['name']);
    $new_pass     = trim($_POST['password']);

    // Dapatkan data asal ejen
    $orig_sql = $conn->prepare("SELECT profile_pic, password FROM agents WHERE id = ?");
    $orig_sql->bind_param("i", $agent_db_id);
    $orig_sql->execute();
    $orig_data = $orig_sql->get_result()->fetch_assoc();
    $profile_pic_name = $orig_data['profile_pic'];

    // Jika gambar profil baharu dimuat naik
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_ext, $allowed)) {
            $profile_pic_name = time() . '_' . $agent_code . '.' . $file_ext;
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], 'uploads/' . $profile_pic_name);
        }
    }

    // Tukar kata laluan jika diisi
    if (!empty($new_pass)) {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        $up_stmt = $conn->prepare("UPDATE agents SET agent_code = ?, name = ?, password = ?, profile_pic = ? WHERE id = ?");
        $up_stmt->bind_param("ssssi", $agent_code, $name, $hashed_pass, $profile_pic_name, $agent_db_id);
    } else {
        $up_stmt = $conn->prepare("UPDATE agents SET agent_code = ?, name = ?, profile_pic = ? WHERE id = ?");
        $up_stmt->bind_param("sssi", $agent_code, $name, $profile_pic_name, $agent_db_id);
    }

    if ($up_stmt->execute()) {
        $msg = "Maklumat ejen berjaya dikemaskini!";
        $msg_type = "success";
    }
}

// 3. PROSES PADAM (DELETE) EJEN
if (isset($_GET['delete_agent_id'])) {
    $del_id = intval($_GET['delete_agent_id']);
    $del_stmt = $conn->prepare("DELETE FROM agents WHERE id = ? AND role != 'leader' AND agent_code != 'admin'");
    $del_stmt->bind_param("i", $del_id);
    if ($del_stmt->execute()) {
        header("Location: admin_dashboard.php?msg=Ejen+berjaya+dipadam");
        exit();
    }
}

// Dapatkan Senarai Semua Ejen
$leaderboard_sql = "
    SELECT a.id as agent_db_id, a.agent_code, a.name, a.role, a.profile_pic, a.password,
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

// Semak Ejen yang sedang dipilih untuk EDIT
$edit_agent = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $e_stmt = $conn->prepare("SELECT * FROM agents WHERE id = ?");
    $e_stmt->bind_param("i", $edit_id);
    $e_stmt->execute();
    $edit_agent = $e_stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SAFT Advisory</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
</head>
<body id="body-bg" class="bg-gradient-to-br from-black via-gray-900 to-black min-h-screen text-gray-100 transition-colors duration-500 pb-12">

    <!-- Navigasi Atas Admin (Frosted Glass Header) -->
    <nav id="nav-bar" class="bg-white/10 backdrop-blur-xl border-b border-white/10 px-6 py-4 flex flex-wrap justify-between items-center shadow-xl sticky top-0 z-50 transition-all duration-500">
        <div class="flex items-center space-x-3">
            <span id="badge-admin" class="bg-white text-black px-3 py-1 rounded-xl text-xs font-black tracking-wider uppercase transition-colors duration-500">ADMIN PANEL</span>
            <h1 id="txt-header-title" class="text-lg font-bold tracking-wide text-white transition-colors duration-500">SAFT Advisory Leader Screen</h1>
        </div>

        <div class="flex items-center space-x-4 mt-2 sm:mt-0">
            <!-- Toggle Dark / Light Mode Button -->
            <button 
                type="button" 
                onclick="toggleTheme()" 
                id="btn-theme-toggle"
                class="p-2.5 rounded-2xl bg-white/10 hover:bg-white/20 border border-white/20 text-white transition-all duration-300 focus:outline-none"
                title="Tukar Mod"
            >
                <!-- Ikon Sun (Light Mode) -->
                <svg id="sun-icon" class="w-4 h-4 hidden text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <!-- Ikon Moon (Dark Mode) -->
                <svg id="moon-icon" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>

            <div class="text-right">
                <p id="txt-admin-name" class="text-sm font-extrabold text-white transition-colors duration-500"><?php echo htmlspecialchars($admin_name); ?></p>
                <p id="txt-admin-role" class="text-xs text-gray-400 font-medium transition-colors duration-500">Admin</p>
            </div>

            <a href="logout.php" class="bg-red-600/80 hover:bg-red-600 text-white text-xs font-bold px-4 py-2.5 rounded-2xl transition duration-200 shadow-lg backdrop-blur-md active:scale-95">
                Logout
            </a>
        </div>
    </nav>

    <!-- Kandungan Utama Admin Dashboard -->
    <div class="max-w-7xl mx-auto px-4 py-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Bahagian Kiri (2 Kolum): Live Leaderboard -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Kad Banner Header Carta Prestasi -->
            <div id="banner-card" class="bg-white/10 backdrop-blur-xl border border-white/20 p-6 rounded-3xl shadow-2xl flex justify-between items-center transition-all duration-500">
                <div>
                    <h2 id="txt-banner-title" class="text-2xl font-black text-white tracking-wide transition-colors duration-500">SAFT Agent Performance Chart</h2>
                    <p id="txt-banner-sub" class="text-xs text-gray-400 mt-1 font-medium transition-colors duration-500">Presentation Session - Month: <span class="font-extrabold underline text-white"><?php echo date('F Y'); ?></span></p>
                </div>
                <span id="badge-live" class="bg-white/20 text-white border border-white/30 text-xs px-3.5 py-1.5 rounded-full font-bold shadow-md tracking-wider">ADMIN MONITORING</span>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <div class="p-3.5 bg-green-500/20 border border-green-500/40 text-green-300 text-xs font-semibold rounded-2xl text-center backdrop-blur-md">
                    <?php echo htmlspecialchars($_GET['msg']); ?>
                </div>
            <?php endif; ?>

            <!-- Kad Jadual Leaderboard Ejen -->
            <div id="table-card" class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl shadow-2xl overflow-hidden transition-all duration-500">
                <div id="table-header" class="p-5 border-b border-white/10 flex justify-between items-center transition-colors duration-500">
                    <h3 id="txt-table-title" class="text-base font-extrabold text-white flex items-center gap-2 transition-colors duration-500">
                        🏆 Current Agent Rankings (Ranking TPC)
                    </h3>
                    <span id="txt-table-sub" class="text-xs text-gray-400 font-semibold transition-colors duration-500">Month <?php echo date('m/Y'); ?></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead id="tbl-thead" class="uppercase text-[11px] text-gray-400 border-b border-white/10 bg-white/5 transition-colors duration-500">
                            <tr>
                                <th class="py-3.5 px-4">Ranking</th>
                                <th class="py-3.5 px-4">Agent</th>
                                <th class="py-3.5 px-4 text-center">P</th>
                                <th class="py-3.5 px-4 text-center">C</th>
                                <th class="py-3.5 px-4 text-center">NC</th>
                                <th class="py-3.5 px-4 text-right">MTD (TPC)</th>
                                <th class="py-3.5 px-4 text-right">YTD (TPC)</th>
                                <th class="py-3.5 px-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="tbl-tbody" class="divide-y divide-white/10 transition-colors duration-500">
                            <?php 
                            $rank = 1;
                            if ($leaderboard_res->num_rows > 0):
                                while($row = $leaderboard_res->fetch_assoc()):
                                    $img_src = (!empty($row['profile_pic']) && file_exists('uploads/' . $row['profile_pic'])) ? 'uploads/' . $row['profile_pic'] : 'https://via.placeholder.com/150';
                            ?>
                                <tr class="hover:bg-white/10 transition duration-200">
                                    <td class="py-3.5 px-4 font-black">
                                        <?php 
                                            if ($rank === 1) echo "🥇 #1";
                                            elseif ($rank === 2) echo "🥈 #2";
                                            elseif ($rank === 3) echo "🥉 #3";
                                            else echo "#" . $rank;
                                        ?>
                                    </td>
                                    <td class="py-3.5 px-4 flex items-center gap-3">
                                        <img src="<?php echo $img_src; ?>" class="w-8 h-8 rounded-full object-cover border border-white/30 shadow-md">
                                        <div>
                                            <p class="font-bold text-sm leading-tight"><?php echo htmlspecialchars($row['name']); ?></p>
                                            <span class="text-[10px] text-gray-400 font-medium"><?php echo htmlspecialchars($row['agent_code']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-bold text-white"><?php echo number_format($row['persistency']); ?></td>
                                    <td class="py-3.5 px-4 text-center font-medium"><?php echo number_format($row['c_amax']); ?></td>
                                    <td class="py-3.5 px-4 text-center font-bold text-emerald-400"><?php echo number_format($row['new_cases']); ?></td>
                                    <td class="py-3.5 px-4 text-right text-emerald-400 font-bold"><?php echo number_format($row['mtd_amount']); ?></td>
                                    <td class="py-3.5 px-4 text-right font-black text-white"><?php echo number_format($row['ytd_amount']); ?></td>
                                    <td class="py-3.5 px-4 text-center space-x-1.5">
                                        <a href="admin_dashboard.php?edit_id=<?php echo $row['agent_db_id']; ?>" class="bg-blue-500/80 hover:bg-blue-500 text-white text-[10px] px-2.5 py-1 rounded-xl font-bold transition shadow">Edit</a>
                                        <a href="admin_dashboard.php?delete_agent_id=<?php echo $row['agent_db_id']; ?>" onclick="return confirm('Padam akaun ejen ini?');" class="bg-red-500/80 hover:bg-red-500 text-white text-[10px] px-2.5 py-1 rounded-xl font-bold transition shadow">Delete</a>
                                    </td>
                                </tr>
                            <?php 
                                $rank++;
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="8" class="text-center py-8 text-gray-400 font-medium">Belum ada ejen berdaftar.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Bahagian Kanan (1 Kolum): Borang Tambah / Edit Ejen -->
        <div class="space-y-6">
            
            <div id="form-card" class="bg-white/10 backdrop-blur-xl border border-white/20 p-6 rounded-3xl shadow-2xl transition-all duration-500">
                <h3 id="txt-form-title" class="text-lg font-black text-white mb-1 transition-colors duration-500">
                    <?php echo $edit_agent ? 'Kemaskini Ejen' : 'Register Agent'; ?>
                </h3>
                <p id="txt-form-sub" class="text-xs text-gray-400 mb-6 font-medium transition-colors duration-500">
                    <?php echo $edit_agent ? 'Ubah profil, kata laluan atau gambar ejen.' : 'Add a new SAFT Advisory agent profile'; ?>
                </p>

                <?php if (!empty($msg)): ?>
                    <div class="mb-5 p-3.5 <?php echo $msg_type === 'success' ? 'bg-green-500/20 border-green-500/40 text-green-300' : 'bg-red-500/20 border-red-500/40 text-red-300'; ?> border text-xs font-semibold rounded-2xl text-center backdrop-blur-md">
                        <?php echo $msg; ?>
                    </div>
                <?php endif; ?>

                <form action="admin_dashboard.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <?php if ($edit_agent): ?>
                        <input type="hidden" name="update_agent" value="1">
                        <input type="hidden" name="agent_db_id" value="<?php echo $edit_agent['id']; ?>">
                    <?php else: ?>
                        <input type="hidden" name="add_agent" value="1">
                    <?php endif; ?>

                    <div>
                        <label id="lbl-agent-code" class="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-2 transition-colors duration-500">Agent Code:</label>
                        <input type="text" name="agent_code" value="<?php echo $edit_agent ? htmlspecialchars($edit_agent['agent_code']) : ''; ?>" required class="input-glass w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-white/40 transition-all duration-300 backdrop-blur-md">
                    </div>

                    <div>
                        <label id="lbl-agent-name" class="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-2 transition-colors duration-500">Full Name:</label>
                        <input type="text" name="name" value="<?php echo $edit_agent ? htmlspecialchars($edit_agent['name']) : ''; ?>" required class="input-glass w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-white/40 transition-all duration-300 backdrop-blur-md">
                    </div>

                    <div>
                        <label id="lbl-agent-pass" class="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-2 transition-colors duration-500">
                            <?php echo $edit_agent ? 'Kata Laluan Baharu (Biarkan kosong jika tak tukar):' : 'Temporary Password:'; ?>
                        </label>
                        <input type="password" name="password" <?php echo $edit_agent ? '' : 'required'; ?> class="input-glass w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-white/40 transition-all duration-300 backdrop-blur-md">
                    </div>

                    <div>
                        <label id="lbl-agent-pic" class="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-2 transition-colors duration-500">Profile Picture:</label>
                        <input type="file" name="profile_pic" accept="image/*" class="input-glass w-full text-xs text-gray-300 bg-white/5 p-2.5 rounded-2xl border border-white/10 focus:outline-none file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-white/20 file:text-white transition-all duration-300">
                    </div>

                    <button 
                        id="btn-submit"
                        type="submit" 
                        class="w-full bg-white hover:bg-gray-200 text-black font-extrabold py-3.5 px-4 rounded-2xl transition duration-200 shadow-xl active:scale-95 mt-2"
                    >
                        <?php echo $edit_agent ? 'Simpan Perubahan' : '+ Add Agent'; ?>
                    </button>

                    <?php if ($edit_agent): ?>
                        <a href="admin_dashboard.php" id="lnk-cancel" class="block text-center text-xs text-gray-400 hover:text-white underline mt-3 font-semibold transition">Batal Edit / Tambah Ejen Baharu</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Script JavaScript untuk Toggle Theme -->
    <script>
        function toggleTheme() {
            const body = document.getElementById('body-bg');
            const nav = document.getElementById('nav-bar');
            const sunIcon = document.getElementById('sun-icon');
            const moonIcon = document.getElementById('moon-icon');
            
            const badgeAdmin = document.getElementById('badge-admin');
            const txtHeaderTitle = document.getElementById('txt-header-title');
            const txtAdminName = document.getElementById('txt-admin-name');
            const txtAdminRole = document.getElementById('txt-admin-role');

            const bannerCard = document.getElementById('banner-card');
            const txtBannerTitle = document.getElementById('txt-banner-title');
            const txtBannerSub = document.getElementById('txt-banner-sub');
            const badgeLive = document.getElementById('badge-live');

            const tableCard = document.getElementById('table-card');
            const tableHeader = document.getElementById('table-header');
            const txtTableTitle = document.getElementById('txt-table-title');
            const txtTableSub = document.getElementById('txt-table-sub');
            const tblThead = document.getElementById('tbl-thead');
            const tblTbody = document.getElementById('tbl-tbody');

            const formCard = document.getElementById('form-card');
            const txtFormTitle = document.getElementById('txt-form-title');
            const txtFormSub = document.getElementById('txt-form-sub');
            const btnSubmit = document.getElementById('btn-submit');
            const lnkCancel = document.getElementById('lnk-cancel');

            const labels = [
                document.getElementById('lbl-agent-code'),
                document.getElementById('lbl-agent-name'),
                document.getElementById('lbl-agent-pass'),
                document.getElementById('lbl-agent-pic')
            ];

            const inputs = document.querySelectorAll('.input-glass');

            const isDark = body.classList.contains('from-black');

            if (isDark) {
                // Tukar ke Light Mode (Frosted White Glass)
                body.className = "bg-gradient-to-br from-gray-200 via-gray-100 to-gray-300 min-h-screen text-gray-900 transition-colors duration-500 pb-12";
                nav.className = "bg-white/40 backdrop-blur-xl border-b border-white/60 px-6 py-4 flex flex-wrap justify-between items-center shadow-md sticky top-0 z-50 transition-all duration-500";
                
                sunIcon.classList.remove('hidden');
                moonIcon.classList.add('hidden');

                badgeAdmin.className = "bg-black text-white px-3 py-1 rounded-xl text-xs font-black tracking-wider uppercase transition-colors duration-500";
                txtHeaderTitle.className = "text-lg font-bold tracking-wide text-gray-900 transition-colors duration-500";
                txtAdminName.className = "text-sm font-extrabold text-gray-900 transition-colors duration-500";
                txtAdminRole.className = "text-xs text-gray-600 font-medium transition-colors duration-500";

                bannerCard.className = "bg-white/40 backdrop-blur-xl border border-white/60 p-6 rounded-3xl shadow-xl flex justify-between items-center transition-all duration-500";
                txtBannerTitle.className = "text-2xl font-black text-gray-900 tracking-wide transition-colors duration-500";
                txtBannerSub.className = "text-xs text-gray-600 mt-1 font-medium transition-colors duration-500";
                badgeLive.className = "bg-black/10 text-gray-900 border border-black/20 text-xs px-3.5 py-1.5 rounded-full font-bold shadow-sm tracking-wider";

                tableCard.className = "bg-white/40 backdrop-blur-xl border border-white/60 rounded-3xl shadow-xl overflow-hidden transition-all duration-500";
                tableHeader.className = "p-5 border-b border-gray-300/60 flex justify-between items-center transition-colors duration-500 bg-white/20";
                txtTableTitle.className = "text-base font-extrabold text-gray-900 flex items-center gap-2 transition-colors duration-500";
                txtTableSub.className = "text-xs text-gray-600 font-semibold transition-colors duration-500";
                tblThead.className = "uppercase text-[11px] text-gray-700 border-b border-gray-300/60 bg-white/30 transition-colors duration-500";
                tblTbody.className = "divide-y divide-gray-300/50 text-gray-900 transition-colors duration-500";

                formCard.className = "bg-white/40 backdrop-blur-xl border border-white/60 p-6 rounded-3xl shadow-xl transition-all duration-500";
                txtFormTitle.className = "text-lg font-black text-gray-900 mb-1 transition-colors duration-500";
                txtFormSub.className = "text-xs text-gray-600 mb-6 font-medium transition-colors duration-500";
                btnSubmit.className = "w-full bg-black hover:bg-gray-800 text-white font-extrabold py-3.5 px-4 rounded-2xl transition duration-200 shadow-xl active:scale-95 mt-2";
                if(lnkCancel) lnkCancel.className = "block text-center text-xs text-gray-600 hover:text-black underline mt-3 font-semibold transition";

                labels.forEach(lbl => {
                    if(lbl) lbl.className = "block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2 transition-colors duration-500";
                });

                inputs.forEach(input => {
                    input.className = "input-glass w-full px-4 py-3 rounded-2xl bg-white/50 border border-gray-300/80 text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-black/30 transition-all duration-300 backdrop-blur-md file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-black/10 file:text-black";
                });

                localStorage.setItem('theme', 'light');
            } else {
                // Tukar ke Dark Mode (Frosted Dark Glass)
                body.className = "bg-gradient-to-br from-black via-gray-900 to-black min-h-screen text-gray-100 transition-colors duration-500 pb-12";
                nav.className = "bg-white/10 backdrop-blur-xl border-b border-white/10 px-6 py-4 flex flex-wrap justify-between items-center shadow-xl sticky top-0 z-50 transition-all duration-500";
                
                sunIcon.classList.add('hidden');
                moonIcon.classList.remove('hidden');

                badgeAdmin.className = "bg-white text-black px-3 py-1 rounded-xl text-xs font-black tracking-wider uppercase transition-colors duration-500";
                txtHeaderTitle.className = "text-lg font-bold tracking-wide text-white transition-colors duration-500";
                txtAdminName.className = "text-sm font-extrabold text-white transition-colors duration-500";
                txtAdminRole.className = "text-xs text-gray-400 font-medium transition-colors duration-500";

                bannerCard.className = "bg-white/10 backdrop-blur-xl border border-white/20 p-6 rounded-3xl shadow-2xl flex justify-between items-center transition-all duration-500";
                txtBannerTitle.className = "text-2xl font-black text-white tracking-wide transition-colors duration-500";
                txtBannerSub.className = "text-xs text-gray-400 mt-1 font-medium transition-colors duration-500";
                badgeLive.className = "bg-white/20 text-white border border-white/30 text-xs px-3.5 py-1.5 rounded-full font-bold shadow-md tracking-wider";

                tableCard.className = "bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl shadow-2xl overflow-hidden transition-all duration-500";
                tableHeader.className = "p-5 border-b border-white/10 flex justify-between items-center transition-colors duration-500";
                txtTableTitle.className = "text-base font-extrabold text-white flex items-center gap-2 transition-colors duration-500";
                txtTableSub.className = "text-xs text-gray-400 font-semibold transition-colors duration-500";
                tblThead.className = "uppercase text-[11px] text-gray-400 border-b border-white/10 bg-white/5 transition-colors duration-500";
                tblTbody.className = "divide-y divide-white/10 transition-colors duration-500";

                formCard.className = "bg-white/10 backdrop-blur-xl border border-white/20 p-6 rounded-3xl shadow-2xl transition-all duration-500";
                txtFormTitle.className = "text-lg font-black text-white mb-1 transition-colors duration-500";
                txtFormSub.className = "text-xs text-gray-400 mb-6 font-medium transition-colors duration-500";
                btnSubmit.className = "w-full bg-white hover:bg-gray-200 text-black font-extrabold py-3.5 px-4 rounded-2xl transition duration-200 shadow-xl active:scale-95 mt-2";
                if(lnkCancel) lnkCancel.className = "block text-center text-xs text-gray-400 hover:text-white underline mt-3 font-semibold transition";

                labels.forEach(lbl => {
                    if(lbl) lbl.className = "block text-xs font-bold uppercase tracking-wider text-gray-300 mb-2 transition-colors duration-500";
                });

                inputs.forEach(input => {
                    input.className = "input-glass w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-white/40 transition-all duration-300 backdrop-blur-md file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-white/20 file:text-white";
                });

                localStorage.setItem('theme', 'dark');
            }
        }

        // Semak tema asal dari localStorage semasa halaman dimuatkan
        document.addEventListener("DOMContentLoaded", () => {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'light') {
                toggleTheme();
            }
        });
    </script>
</body>
</html>