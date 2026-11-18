<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAFT Advisory - Agent Performance Tracker</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Konfigurasi Tailwind untuk menyokong dark mode berasaskan class
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
</head>
<body id="body-bg" class="bg-gradient-to-br from-black via-gray-900 to-black min-h-screen flex items-center justify-center p-4 transition-colors duration-500">

    <!-- Kad Borang Log Masuk (Apple Frosted Glass Style) -->
    <div id="glass-card" class="relative w-full max-w-md p-8 rounded-3xl bg-white/10 backdrop-blur-xl border border-white/20 shadow-2xl text-center transition-all duration-500">
        
        <!-- Toggle Dark / Light Mode Button -->
        <button 
            type="button" 
            onclick="toggleTheme()" 
            class="absolute top-6 right-6 p-2.5 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 text-white transition-all duration-300 focus:outline-none"
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

        <!-- Tajuk Header -->
        <h2 id="txt-welcome" class="text-gray-300 text-sm font-medium tracking-wide transition-colors duration-500">Welcome to</h2>
        
        <!-- Replikasi Logo SAFT KT IGNITE Menggunakan HTML/CSS Text -->
        <div id="logo-text-container" class="inline-flex items-center justify-center gap-3 my-4 text-left transition-colors duration-500 text-white">
            <!-- Bahagian Kiri: SAFt + advisory -->
            <div class="flex flex-col items-center">
                <span class="text-3xl font-black tracking-tighter leading-none uppercase">SAF<span class="lowercase">t</span></span>
                <span class="text-[10px] font-bold tracking-widest leading-none mt-1 uppercase">• advisory •</span>
            </div>
            
            <!-- Bahagian Kanan: KUALA TERENGGANU IGNITE -->
            <div class="flex flex-col justify-center border-l border-current pl-3">
                <span class="text-[10px] font-black uppercase tracking-wider leading-tight">KUALA</span>
                <span class="text-[10px] font-black uppercase tracking-wider leading-tight">TERENGGANU</span>
                <span class="text-[11px] font-black uppercase tracking-widest leading-tight">IGNITE</span>
            </div>
        </div>

        <p id="txt-subtitle" class="text-[11px] text-gray-400 font-semibold mt-1 mb-8 uppercase tracking-widest transition-colors duration-500">
            Agent Performance & Progress Tracker
        </p>

        <!-- Paparan Mesej Ralat Jika Log Masuk Gagal -->
        <?php if (isset($_GET['error'])): ?>
            <div class="mb-6 p-3 bg-white/10 border border-red-500/50 text-red-300 text-xs font-medium rounded-2xl text-center backdrop-blur-md">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Borang Log Masuk -->
        <form action="login_process.php" method="POST" class="text-left space-y-6">
            
            <!-- Bahagian Agent Code -->
            <div>
                <label id="lbl-agent" for="agent_code" class="block text-gray-300 font-semibold mb-2 text-xs uppercase tracking-wider transition-colors duration-500">
                    Agent Code
                </label>
                <input 
                    type="text" 
                    id="agent_code" 
                    name="agent_code" 
                    placeholder="Example : HHC00025"
                    required
                    class="input-glass w-full px-4 py-3.5 rounded-2xl bg-white/5 border border-white/10 text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-white/40 focus:border-transparent transition-all duration-300 backdrop-blur-md"
                >
            </div>

            <!-- Bahagian Password -->
            <div>
                <label id="lbl-pass" for="password" class="block text-gray-300 font-semibold mb-2 text-xs uppercase tracking-wider transition-colors duration-500">
                    Password
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    class="input-glass w-full px-4 py-3.5 rounded-2xl bg-white/5 border border-white/10 text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-white/40 focus:border-transparent transition-all duration-300 backdrop-blur-md"
                >
            </div>

            <!-- Bahagian Button Login -->
            <div class="pt-2 flex justify-center">
                <button 
                    id="btn-login"
                    type="submit" 
                    class="w-full bg-white hover:bg-gray-200 text-black font-bold py-3.5 px-6 rounded-2xl transition duration-200 shadow-lg active:scale-95"
                >
                    Login
                </button>
            </div>
        </form>
    </div>

    <!-- Script JavaScript untuk Toggle Theme -->
    <script>
        function toggleTheme() {
            const body = document.getElementById('body-bg');
            const card = document.getElementById('glass-card');
            const sunIcon = document.getElementById('sun-icon');
            const moonIcon = document.getElementById('moon-icon');
            const logoTextContainer = document.getElementById('logo-text-container');
            
            const txtWelcome = document.getElementById('txt-welcome');
            const txtSubtitle = document.getElementById('txt-subtitle');
            const lblAgent = document.getElementById('lbl-agent');
            const lblPass = document.getElementById('lbl-pass');
            const btnLogin = document.getElementById('btn-login');
            const inputs = document.querySelectorAll('.input-glass');

            const isDark = body.classList.contains('from-black');

            if (isDark) {
                // Tukar ke Light Mode (Frosted White Glass)
                body.className = "bg-gradient-to-br from-gray-200 via-gray-100 to-gray-300 min-h-screen flex items-center justify-center p-4 transition-colors duration-500";
                card.className = "relative w-full max-w-md p-8 rounded-3xl bg-white/40 backdrop-blur-xl border border-white/60 shadow-2xl text-center transition-all duration-500";
                
                sunIcon.classList.remove('hidden');
                moonIcon.classList.add('hidden');

                // Tukar warna teks logo kepada hitam
                logoTextContainer.className = "inline-flex items-center justify-center gap-3 my-4 text-left transition-colors duration-500 text-gray-900";

                txtWelcome.className = "text-gray-600 text-sm font-medium tracking-wide transition-colors duration-500";
                txtSubtitle.className = "text-[11px] text-gray-500 font-semibold mt-1 mb-8 uppercase tracking-widest transition-colors duration-500";
                lblAgent.className = "block text-gray-700 font-semibold mb-2 text-xs uppercase tracking-wider transition-colors duration-500";
                lblPass.className = "block text-gray-700 font-semibold mb-2 text-xs uppercase tracking-wider transition-colors duration-500";
                
                btnLogin.className = "w-full bg-black hover:bg-gray-800 text-white font-bold py-3.5 px-6 rounded-2xl transition duration-200 shadow-lg active:scale-95";

                inputs.forEach(input => {
                    input.className = "input-glass w-full px-4 py-3.5 rounded-2xl bg-white/50 border border-gray-300/80 text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-black/30 focus:border-transparent transition-all duration-300 backdrop-blur-md";
                });

                localStorage.setItem('theme', 'light');
            } else {
                // Tukar ke Dark Mode (Frosted Dark Glass)
                body.className = "bg-gradient-to-br from-black via-gray-900 to-black min-h-screen flex items-center justify-center p-4 transition-colors duration-500";
                card.className = "relative w-full max-w-md p-8 rounded-3xl bg-white/10 backdrop-blur-xl border border-white/20 shadow-2xl text-center transition-all duration-500";
                
                sunIcon.classList.add('hidden');
                moonIcon.classList.remove('hidden');

                // Tukar warna teks logo kepada putih
                logoTextContainer.className = "inline-flex items-center justify-center gap-3 my-4 text-left transition-colors duration-500 text-white";

                txtWelcome.className = "text-gray-300 text-sm font-medium tracking-wide transition-colors duration-500";
                txtSubtitle.className = "text-[11px] text-gray-400 font-semibold mt-2 mb-8 uppercase tracking-widest transition-colors duration-500";
                lblAgent.className = "block text-gray-300 font-semibold mb-2 text-xs uppercase tracking-wider transition-colors duration-500";
                lblPass.className = "block text-gray-300 font-semibold mb-2 text-xs uppercase tracking-wider transition-colors duration-500";
                
                btnLogin.className = "w-full bg-white hover:bg-gray-200 text-black font-bold py-3.5 px-6 rounded-2xl transition duration-200 shadow-lg active:scale-95";

                inputs.forEach(input => {
                    input.className = "input-glass w-full px-4 py-3.5 rounded-2xl bg-white/5 border border-white/10 text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-white/40 focus:border-transparent transition-all duration-300 backdrop-blur-md";
                });

                localStorage.setItem('theme', 'dark');
            }
        }

        // Ingat pilihan pengguna (Light/Dark) dari localStorage
        document.addEventListener("DOMContentLoaded", () => {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'light') {
                toggleTheme();
            }
        });
    </script>
</body>
</html>