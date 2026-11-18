<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NoteBook — Catatan Penting & Rahasia</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Custom Style -->
    <link rel="stylesheet" href="style.css">
</head>
<body class="h-full bg-gray-50 text-gray-800 dark:bg-gray-950 dark:text-gray-100 flex flex-col antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Top Alert Banner jika Database belum di-setup atau MySQL mati -->
    <div id="db-setup-banner" class="hidden no-print bg-amber-500 text-white px-4 py-2.5 text-sm flex items-center justify-between shadow-md">
        <div class="flex items-center gap-2">
            <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0"></i>
            <div>
                <span id="db-banner-msg" class="font-medium">Perhatian: Database belum terhubung.</span>
                <span id="db-banner-hint" class="text-xs text-amber-100 ml-2"></span>
            </div>
        </div>
        <button id="btn-run-setup" onclick="runAutoSetup()" class="px-3 py-1 bg-white text-amber-800 hover:bg-amber-50 text-xs font-semibold rounded shadow-sm transition">
            Setup Database Otomatis
        </button>
    </div>

    <!-- Main Container 3-Panel Layout -->
    <div class="flex-1 flex overflow-hidden">

        <!-- ============================================== -->
        <!-- PANEL 1: SIDEBAR (Kategori, Folder & Navigasi) -->
        <!-- ============================================== -->
        <aside class="no-print w-60 border-r border-gray-200 dark:border-gray-800/80 bg-gray-100/70 dark:bg-gray-900/60 flex flex-col justify-between flex-shrink-0 select-none">
            <!-- Header Brand -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-800/80 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-sm shadow-indigo-500/30">
                        <i data-lucide="book-marked" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h1 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight leading-none">NoteBook</h1>
                        <span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium tracking-wide uppercase">Catatan Penting</span>
                    </div>
                </div>
            </div>

            <!-- Navigasi Menu & Filter -->
            <div class="flex-1 overflow-y-auto p-3 space-y-5">
                <!-- Filter Utama -->
                <div class="space-y-1">
                    <button data-filter="all" onclick="setFilter('all')" class="filter-item w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-colors text-gray-700 dark:text-gray-300 hover:bg-gray-200/60 dark:hover:bg-gray-800">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="folders" class="w-4 h-4 text-indigo-500"></i>
                            <span>Semua Catatan</span>
                        </div>
                        <span id="badge-all" class="text-xs bg-gray-200/80 dark:bg-gray-800 text-gray-600 dark:text-gray-400 px-2 py-0.5 rounded-full">0</span>
                    </button>

                    <button data-filter="pinned" onclick="setFilter('pinned')" class="filter-item w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-colors text-gray-700 dark:text-gray-300 hover:bg-gray-200/60 dark:hover:bg-gray-800">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="pin" class="w-4 h-4 text-amber-500"></i>
                            <span>Tersemat</span>
                        </div>
                        <span id="badge-pinned" class="text-xs bg-gray-200/80 dark:bg-gray-800 text-gray-600 dark:text-gray-400 px-2 py-0.5 rounded-full">0</span>
                    </button>

                    <button data-filter="locked" onclick="setFilter('locked')" class="filter-item w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-colors text-gray-700 dark:text-gray-300 hover:bg-gray-200/60 dark:hover:bg-gray-800">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="shield-check" class="w-4 h-4 text-rose-500"></i>
                            <span>Terkunci PIN</span>
                        </div>
                        <span id="badge-locked" class="text-xs bg-gray-200/80 dark:bg-gray-800 text-gray-600 dark:text-gray-400 px-2 py-0.5 rounded-full">0</span>
                    </button>

                    <button data-filter="trash" onclick="setFilter('trash')" class="filter-item w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-colors text-gray-700 dark:text-gray-300 hover:bg-gray-200/60 dark:hover:bg-gray-800">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="trash-2" class="w-4 h-4 text-gray-400"></i>
                            <span>Sampah</span>
                        </div>
                        <span id="badge-trash" class="text-xs bg-gray-200/80 dark:bg-gray-800 text-gray-600 dark:text-gray-400 px-2 py-0.5 rounded-full">0</span>
                    </button>
                </div>

                <!-- Bagian Kategori -->
                <div>
                    <div class="flex items-center justify-between px-3 mb-2">
                        <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Kategori</span>
                        <button onclick="openModal('modal-new-category')" class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 text-xs flex items-center gap-1 font-medium transition" title="Tambah Kategori">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i> Tambah
                        </button>
                    </div>
                    <div id="categories-list" class="space-y-0.5">
                        <!-- Dimuat secara dinamis oleh app.js -->
                    </div>
                </div>
            </div>

            <!-- Footer Sidebar: Mode Gelap & Status -->
            <div class="p-3 border-t border-gray-200 dark:border-gray-800/80 flex items-center justify-between">
                <button id="theme-toggle-btn" onclick="toggleTheme()" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-800 transition">
                    <i data-lucide="moon" class="w-4 h-4"></i>
                    <span class="text-xs">Dark Mode</span>
                </button>
                <span class="text-[10px] text-gray-400 font-mono">PHP + MySQL</span>
            </div>
        </aside>

        <!-- ============================================== -->
        <!-- PANEL 2: DAFTAR CATATAN (List & Live Search)  -->
        <!-- ============================================== -->
        <section class="no-print w-80 md:w-88 border-r border-gray-200 dark:border-gray-800/80 bg-gray-50/70 dark:bg-gray-900/30 flex flex-col flex-shrink-0">
            <!-- Header List Catatan & Pencarian -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-800/80 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 id="list-panel-title" class="text-base font-bold text-gray-900 dark:text-white leading-tight">Semua Catatan</h2>
                        <span id="list-panel-count" class="text-xs text-gray-500 dark:text-gray-400">0 catatan</span>
                    </div>
                    <button onclick="createNewNote()" class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition shadow-indigo-600/20 active:scale-95">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Baru</span>
                    </button>
                </div>

                <!-- Input Pencarian Instan -->
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-2.5 text-gray-400"></i>
                    <input id="search-input" type="text" placeholder="Cari catatan cepat (Ctrl+K)..." class="w-full pl-9 pr-3 py-1.5 text-xs bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition placeholder:text-gray-400">
                </div>
            </div>

            <!-- List Catatan Dinamis -->
            <div id="notes-list-container" class="flex-1 overflow-y-auto p-3 space-y-2">
                <!-- Dimuat oleh app.js -->
            </div>
        </section>

        <!-- ============================================== -->
        <!-- PANEL 3: EDITOR UTAMA (Baca, Tulis & Fitur)   -->
        <!-- ============================================== -->
        <main id="editor-panel" class="flex-1 bg-white dark:bg-gray-950 flex flex-col overflow-hidden relative">

            <!-- State 1: Ketika Ada Catatan Aktif -->
            <div id="editor-content-state" class="flex-1 flex flex-col h-full overflow-hidden">
                <!-- Action Toolbar Atas -->
                <div class="no-print h-14 border-b border-gray-200 dark:border-gray-800/80 px-6 flex items-center justify-between bg-white dark:bg-gray-950 flex-shrink-0">
                    <!-- Kiri: Pemilih Kategori -->
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="tag" class="w-3.5 h-3.5 text-gray-400"></i>
                            <select id="note-category-select" class="text-xs bg-transparent border border-gray-200 dark:border-gray-700/80 rounded-md px-2.5 py-1 text-gray-700 dark:text-gray-300 focus:outline-none focus:border-indigo-500 cursor-pointer">
                                <!-- Opsi kategori dimuat via JS -->
                            </select>
                        </div>
                        <span class="text-gray-300 dark:text-gray-700">|</span>
                        <!-- Status Auto-Save -->
                        <span id="save-status" class="text-xs text-gray-400 flex items-center">
                            <span class="text-emerald-500 font-semibold mr-1">✓</span> Tersimpan
                        </span>
                    </div>

                    <!-- Kanan: Tombol Aksi Penting -->
                    <div class="flex items-center gap-2">
                        <!-- Tombol Sematkan (Pin) -->
                        <button id="btn-toggle-pin" onclick="togglePin()" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition" title="Sematkan ke paling atas">
                            <i data-lucide="pin" class="w-4 h-4"></i>
                            <span class="text-xs">Sematkan</span>
                        </button>

                        <!-- Tombol Kunci PIN -->
                        <button id="btn-toggle-lock" onclick="openSetLockModal()" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition" title="Kunci catatan ini dengan PIN">
                            <i data-lucide="unlock" class="w-4 h-4"></i>
                            <span class="text-xs">Kunci PIN</span>
                        </button>

                        <!-- Export Markdown -->
                        <button onclick="exportMarkdown()" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition" title="Download format Markdown (.md)">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span class="text-xs">Export .md</span>
                        </button>

                        <!-- Cetak / Simpan PDF -->
                        <button onclick="printNote()" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition" title="Cetak atau Simpan PDF">
                            <i data-lucide="printer" class="w-4 h-4"></i>
                        </button>

                        <!-- Tombol Pulihkan jika di Sampah -->
                        <button id="btn-restore-note" onclick="restoreCurrentNote()" class="hidden flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-300 transition" title="Pulihkan catatan ini">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                            <span class="text-xs font-semibold">Pulihkan</span>
                        </button>

                        <!-- Tombol Hapus -->
                        <button id="btn-delete-note" onclick="deleteCurrentNote()" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-300 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 transition" title="Hapus catatan">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Markdown Formatting Toolbar -->
                <div class="no-print px-6 py-2 border-b border-gray-100 dark:border-gray-900 flex items-center gap-1 bg-gray-50/50 dark:bg-gray-900/30 overflow-x-auto text-gray-500 dark:text-gray-400">
                    <button onclick="insertMarkdown('bold')" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-800 rounded font-bold text-xs" title="Tebal (Bold)">B</button>
                    <button onclick="insertMarkdown('italic')" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-800 rounded italic text-xs" title="Miring (Italic)">I</button>
                    <button onclick="insertMarkdown('strike')" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-800 rounded line-through text-xs" title="Coret (Strikethrough)">S</button>
                    <span class="text-gray-300 dark:text-gray-700 mx-1">|</span>
                    <button onclick="insertMarkdown('h1')" class="px-2 py-1 hover:bg-gray-200 dark:hover:bg-gray-800 rounded text-xs font-bold" title="Heading 1">H1</button>
                    <button onclick="insertMarkdown('h2')" class="px-2 py-1 hover:bg-gray-200 dark:hover:bg-gray-800 rounded text-xs font-bold" title="Heading 2">H2</button>
                    <span class="text-gray-300 dark:text-gray-700 mx-1">|</span>
                    <button onclick="insertMarkdown('todo')" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-800 rounded text-xs flex items-center gap-1" title="Checklist / To-Do">
                        <i data-lucide="check-square" class="w-3.5 h-3.5"></i> To-Do
                    </button>
                    <button onclick="insertMarkdown('bullet')" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-800 rounded text-xs flex items-center gap-1" title="Daftar Poin">
                        <i data-lucide="list" class="w-3.5 h-3.5"></i> List
                    </button>
                    <button onclick="insertMarkdown('quote')" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-800 rounded text-xs flex items-center gap-1" title="Kutipan">
                        <i data-lucide="quote" class="w-3.5 h-3.5"></i> Quote
                    </button>
                    <button onclick="insertMarkdown('code')" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-800 rounded text-xs font-mono" title="Blok Kode">&lt;/&gt;</button>
                </div>

                <!-- Area Menulis Utama -->
                <div class="flex-1 overflow-y-auto p-6 md:p-8 flex flex-col max-w-4xl w-full mx-auto">
                    <!-- Input Judul Catatan -->
                    <input id="note-title-input" type="text" placeholder="Judul Catatan Penting..." class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white bg-transparent border-none focus:outline-none placeholder:text-gray-300 dark:placeholder:text-gray-700 mb-4 tracking-tight">

                    <!-- Input Isi Konten Catatan -->
                    <textarea id="note-content-input" placeholder="Mulai tulis catatan Anda di sini... Mendukung format markdown (**tebal**, *miring*, - [ ] checklist, dll)." class="editor-textarea flex-1 w-full bg-transparent border-none focus:outline-none text-gray-700 dark:text-gray-300 text-sm md:text-base placeholder:text-gray-400 dark:placeholder:text-gray-600 leading-relaxed"></textarea>
                </div>

                <!-- Footer Editor: Info Jumlah Kata & Waktu -->
                <div class="no-print h-9 border-t border-gray-100 dark:border-gray-900 px-6 flex items-center justify-between text-[11px] text-gray-400 bg-white dark:bg-gray-950 flex-shrink-0">
                    <span id="editor-stats">0 kata · 0 karakter</span>
                    <span>Format Markdown Aktif · Auto-Save</span>
                </div>
            </div>

            <!-- State 2: Ketika Belum Ada Catatan yang Dipilih -->
            <div id="editor-empty-state" class="hidden flex-1 flex flex-col items-center justify-center text-center p-8 text-gray-400">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-gray-900 flex items-center justify-center text-indigo-500 mb-4 shadow-sm">
                    <i data-lucide="pen-tool" class="w-8 h-8 stroke-[1.5]"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-700 dark:text-gray-200 mb-1">Pilih atau Buat Catatan</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mb-5 leading-relaxed">
                    Pilih salah satu catatan dari daftar di panel tengah, atau buat catatan baru sekarang.
                </p>
                <button onclick="createNewNote()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition shadow-indigo-600/20 flex items-center gap-1.5">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Tulis Catatan Baru</span>
                </button>
            </div>

        </main>

    </div>

    <!-- ============================================== -->
    <!-- MODAL: Buka Catatan Terkunci PIN                -->
    <!-- ============================================== -->
    <div id="modal-verify-pin" class="modal-overlay hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-gray-100 dark:border-gray-800 animate-in fade-in zoom-in-95 duration-150">
            <div class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-500 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="lock" class="w-6 h-6"></i>
            </div>
            <h3 class="text-base font-bold text-center text-gray-900 dark:text-white mb-1">Catatan Dilindungi PIN</h3>
            <p id="pin-modal-note-title" class="text-xs text-center text-gray-500 dark:text-gray-400 mb-4 truncate font-medium"></p>
            
            <p id="pin-error-msg" class="hidden text-xs text-center text-rose-500 bg-rose-50 dark:bg-rose-950/40 py-1.5 px-3 rounded-lg mb-3 font-medium">
                PIN salah! Silakan coba lagi.
            </p>

            <form onsubmit="event.preventDefault(); submitVerifyPin();" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1.5">Masukkan PIN Keamanan</label>
                    <input id="verify-pin-input" type="password" maxlength="10" placeholder="••••" class="w-full text-center tracking-widest text-lg font-mono px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-rose-500/30 focus:border-rose-500">
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="closeModal('modal-verify-pin')" class="flex-1 px-4 py-2 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 transition">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold transition shadow-sm shadow-rose-600/30">
                        Buka Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL: Atur PIN Baru (Kunci Catatan)            -->
    <!-- ============================================== -->
    <div id="modal-set-pin" class="modal-overlay hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-gray-100 dark:border-gray-800 animate-in fade-in zoom-in-95 duration-150">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-500 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="shield" class="w-6 h-6"></i>
            </div>
            <h3 class="text-base font-bold text-center text-gray-900 dark:text-white mb-1">Kunci Catatan dengan PIN</h3>
            <p class="text-xs text-center text-gray-500 dark:text-gray-400 mb-4">
                Masukkan minimal 4 digit PIN rahasia untuk melindungi catatan ini dari akses tanpa izin.
            </p>

            <form onsubmit="event.preventDefault(); submitSetPin();" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1.5">Buat PIN (minimal 4 digit)</label>
                    <input id="set-pin-input" type="password" maxlength="10" placeholder="Contoh: 1234" class="w-full text-center tracking-widest text-lg font-mono px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500">
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="closeModal('modal-set-pin')" class="flex-1 px-4 py-2 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 transition">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition shadow-sm shadow-indigo-600/30">
                        Pasang Kunci
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL: Tambah Kategori Baru                    -->
    <!-- ============================================== -->
    <div id="modal-new-category" class="modal-overlay hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-gray-100 dark:border-gray-800 animate-in fade-in zoom-in-95 duration-150">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Tambah Kategori Baru</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Kelompokkan catatan penting Anda dengan rapi.</p>

            <form onsubmit="event.preventDefault(); submitNewCategory();" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1.5">Nama Kategori</label>
                    <input id="new-cat-name" type="text" placeholder="Contoh: Finansial, Kuliah, Project X" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1.5">Warna Label</label>
                    <div class="flex items-center gap-3">
                        <input id="new-cat-color" type="color" value="#6366f1" class="w-10 h-9 p-0.5 rounded border border-gray-200 dark:border-gray-700 bg-transparent cursor-pointer">
                        <span class="text-xs text-gray-400">Pilih warna penanda</span>
                    </div>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="closeModal('modal-new-category')" class="flex-1 px-4 py-2 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 transition">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition shadow-sm shadow-indigo-600/30">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Application Script -->
    <script src="app.js"></script>
    <script>
        // Init icon awal
        lucide.createIcons();
    </script>
</body>
</html>
