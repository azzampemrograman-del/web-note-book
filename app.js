/**
 * NoteBook 3-Panel Application Logic
 */

// State App
const state = {
    notes: [],
    categories: [],
    stats: {},
    activeFilter: 'all', // 'all', 'pinned', 'locked', 'trash', or 'category'
    activeCategoryId: null,
    activeNoteId: null,
    searchQuery: '',
    currentNote: null,
    unlockedNotes: {}, // Cache ID catatan yang sudah berhasil dibuka dengan PIN di sesi ini
    autoSaveTimeout: null,
    isSaving: false
};

// Inisialisasi saat DOM ready
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    loadMetaData();
    loadNotes();
    setupEventListeners();
});

// Setup Dark/Light Mode
function initTheme() {
    const savedTheme = localStorage.getItem('notebook_theme') || 'light';
    if (savedTheme === 'dark') {
        document.documentElement.classList.add('dark');
        document.getElementById('theme-toggle-btn').innerHTML = '<i data-lucide="sun" class="w-4 h-4"></i><span class="text-xs">Light Mode</span>';
    } else {
        document.documentElement.classList.remove('dark');
        document.getElementById('theme-toggle-btn').innerHTML = '<i data-lucide="moon" class="w-4 h-4"></i><span class="text-xs">Dark Mode</span>';
    }
    if (window.lucide) lucide.createIcons();
}

function toggleTheme() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('notebook_theme', isDark ? 'dark' : 'light');
    initTheme();
}

// 1. Ambil Metadata (Kategori & Statistik)
async function loadMetaData() {
    try {
        const res = await fetch('api.php?action=get_meta');
        const data = await res.json();

        if (data.db_error) {
            showDbSetupBanner(data.message, data.hint);
            return;
        }

        if (data.success) {
            hideDbSetupBanner();
            state.categories = data.categories || [];
            state.stats = data.stats || {};
            renderSidebar();
            renderCategorySelect();
        }
    } catch (err) {
        console.error('Error loadMetaData:', err);
        showDbSetupBanner('Tidak dapat menghubungi server database.', 'Pastikan Apache & MySQL di XAMPP sudah berjalan.');
    }
}

// 2. Ambil Daftar Catatan
async function loadNotes(preserveActive = false) {
    try {
        let url = `api.php?action=get_notes&filter=${encodeURIComponent(state.activeFilter)}`;
        if (state.activeFilter === 'category' && state.activeCategoryId) {
            url += `&category_id=${state.activeCategoryId}`;
        }
        if (state.searchQuery.trim() !== '') {
            url += `&search=${encodeURIComponent(state.searchQuery.trim())}`;
        }

        const res = await fetch(url);
        const data = await res.json();

        if (data.db_error) {
            showDbSetupBanner(data.message, data.hint);
            return;
        }

        if (data.success) {
            state.notes = data.notes || [];
            renderNotesList();

            // Atur catatan aktif
            if (!preserveActive || !state.activeNoteId) {
                if (state.notes.length > 0) {
                    selectNote(state.notes[0].id);
                } else {
                    state.activeNoteId = null;
                    renderEmptyEditor();
                }
            }
        }
    } catch (err) {
        console.error('Error loadNotes:', err);
    }
}

// 3. Render Sidebar
function renderSidebar() {
    // Update badge statistik
    document.getElementById('badge-all').textContent = state.stats.total_active || 0;
    document.getElementById('badge-pinned').textContent = state.stats.total_pinned || 0;
    document.getElementById('badge-locked').textContent = state.stats.total_locked || 0;
    document.getElementById('badge-trash').textContent = state.stats.total_trash || 0;

    // Render daftar kategori
    const catContainer = document.getElementById('categories-list');
    catContainer.innerHTML = '';

    state.categories.forEach(cat => {
        const isActive = state.activeFilter === 'category' && state.activeCategoryId === parseInt(cat.id);
        const item = document.createElement('button');
        item.className = `w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
            isActive 
                ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' 
                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'
        }`;
        item.onclick = () => setFilter('category', parseInt(cat.id));

        item.innerHTML = `
            <div class="flex items-center gap-2 truncate">
                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: ${cat.color}"></span>
                <span class="truncate">${escapeHtml(cat.name)}</span>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full ${isActive ? 'bg-indigo-200/60 dark:bg-indigo-800/60 text-indigo-800 dark:text-indigo-200' : 'bg-gray-100 dark:bg-gray-800 text-gray-500'}">
                ${cat.notes_count || 0}
            </span>
        `;
        catContainer.appendChild(item);
    });

    // Update active highlight pada filter sistem
    document.querySelectorAll('.filter-item').forEach(btn => {
        const filterType = btn.getAttribute('data-filter');
        if (state.activeFilter === filterType) {
            btn.classList.add('bg-indigo-50', 'text-indigo-700', 'dark:bg-indigo-900/30', 'dark:text-indigo-400');
            btn.classList.remove('text-gray-700', 'dark:text-gray-300');
        } else {
            btn.classList.remove('bg-indigo-50', 'text-indigo-700', 'dark:bg-indigo-900/30', 'dark:text-indigo-400');
            btn.classList.add('text-gray-700', 'dark:text-gray-300');
        }
    });

    if (window.lucide) lucide.createIcons();
}

// 4. Render Dropdown Kategori di Editor
function renderCategorySelect() {
    const select = document.getElementById('note-category-select');
    select.innerHTML = '<option value="">(Tanpa Kategori)</option>';
    state.categories.forEach(cat => {
        const opt = document.createElement('option');
        opt.value = cat.id;
        opt.textContent = cat.name;
        select.appendChild(opt);
    });
}

// 5. Render Daftar Catatan (Panel 2)
function renderNotesList() {
    const container = document.getElementById('notes-list-container');
    const headerTitle = document.getElementById('list-panel-title');
    const headerCount = document.getElementById('list-panel-count');

    // Judul Panel 2
    if (state.activeFilter === 'all') headerTitle.textContent = 'Semua Catatan';
    else if (state.activeFilter === 'pinned') headerTitle.textContent = 'Catatan Tersemat';
    else if (state.activeFilter === 'locked') headerTitle.textContent = 'Catatan Terkunci';
    else if (state.activeFilter === 'trash') headerTitle.textContent = 'Sampah';
    else if (state.activeFilter === 'category') {
        const cat = state.categories.find(c => c.id == state.activeCategoryId);
        headerTitle.textContent = cat ? cat.name : 'Kategori';
    }

    headerCount.textContent = `${state.notes.length} catatan`;
    container.innerHTML = '';

    if (state.notes.length === 0) {
        container.innerHTML = `
            <div class="h-64 flex flex-col items-center justify-center text-center p-6 text-gray-400">
                <i data-lucide="file-question" class="w-12 h-12 mb-3 stroke-[1.5]"></i>
                <p class="text-sm font-medium">Tidak ada catatan ditemukan</p>
                <p class="text-xs text-gray-500 mt-1">Klik tombol "+ Catatan Baru" di atas untuk mulai menulis.</p>
            </div>
        `;
        if (window.lucide) lucide.createIcons();
        return;
    }

    state.notes.forEach(note => {
        const isActive = note.id == state.activeNoteId;
        const card = document.createElement('div');
        card.className = `note-card p-3.5 rounded-xl border cursor-pointer transition-all ${
            isActive 
                ? 'active bg-white dark:bg-gray-800/90 border-indigo-500 shadow-sm ring-1 ring-indigo-500/20' 
                : 'bg-white/70 dark:bg-gray-900/40 border-gray-200 dark:border-gray-800/80 hover:bg-white dark:hover:bg-gray-800/60'
        }`;
        card.onclick = () => selectNote(note.id);

        const dateStr = formatDate(note.updated_at);
        const categoryBadge = note.category_name ? `
            <span class="inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded-md" style="background-color: ${note.category_color}18; color: ${note.category_color}">
                <span class="w-1.5 h-1.5 rounded-full" style="background-color: ${note.category_color}"></span>
                ${escapeHtml(note.category_name)}
            </span>
        ` : '';

        const pinBadge = parseInt(note.is_pinned) === 1 ? `
            <span class="text-amber-500 text-xs flex items-center gap-0.5 font-medium" title="Tersemat">
                <i data-lucide="pin" class="w-3.5 h-3.5 fill-amber-500"></i>
            </span>
        ` : '';

        const lockBadge = parseInt(note.is_locked) === 1 ? `
            <span class="text-rose-500 text-xs flex items-center gap-0.5 font-medium" title="Terkunci dengan PIN">
                <i data-lucide="lock" class="w-3.5 h-3.5"></i>
            </span>
        ` : '';

        card.innerHTML = `
            <div class="flex items-start justify-between gap-2 mb-1.5">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate flex-1">
                    ${escapeHtml(note.title || 'Catatan Tanpa Judul')}
                </h4>
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    ${pinBadge}
                    ${lockBadge}
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mb-2.5 leading-relaxed font-normal">
                ${escapeHtml(note.preview_content || 'Belum ada konten...')}
            </p>
            <div class="flex items-center justify-between text-[11px] text-gray-400">
                <span>${dateStr}</span>
                <div>${categoryBadge}</div>
            </div>
        `;
        container.appendChild(card);
    });

    if (window.lucide) lucide.createIcons();
}

// 6. Pilih Catatan untuk Dibuka di Editor (Panel 3)
async function selectNote(noteId, customPin = null) {
    state.activeNoteId = noteId;
    const noteMeta = state.notes.find(n => n.id == noteId);

    // Cek apakah catatan terkunci dan belum di-unlock
    if (noteMeta && parseInt(noteMeta.is_locked) === 1 && !state.unlockedNotes[noteId] && !customPin) {
        showPinModal(noteId, noteMeta.title);
        return;
    }

    try {
        const pinToUse = customPin || state.unlockedNotes[noteId] || '';
        const res = await fetch(`api.php?action=get_note&id=${noteId}&pin=${encodeURIComponent(pinToUse)}`);
        const data = await res.json();

        if (data.is_locked) {
            showPinModal(noteId, noteMeta ? noteMeta.title : 'Catatan Terkunci', true);
            return;
        }

        if (data.success && data.note) {
            if (customPin) {
                state.unlockedNotes[noteId] = customPin;
            }
            state.currentNote = data.note;
            renderNoteInEditor(data.note);
            renderNotesList(); // update active border
        }
    } catch (err) {
        console.error('Error selectNote:', err);
    }
}

// 7. Render Isi Catatan di Editor Utama
function renderNoteInEditor(note) {
    document.getElementById('editor-empty-state').classList.add('hidden');
    document.getElementById('editor-content-state').classList.remove('hidden');

    const titleInput = document.getElementById('note-title-input');
    const contentInput = document.getElementById('note-content-input');
    const categorySelect = document.getElementById('note-category-select');
    const pinBtn = document.getElementById('btn-toggle-pin');
    const lockBtn = document.getElementById('btn-toggle-lock');
    const trashBtn = document.getElementById('btn-delete-note');
    const restoreBtn = document.getElementById('btn-restore-note');

    titleInput.value = note.title || '';
    contentInput.value = note.content || '';
    categorySelect.value = note.category_id || '';

    // Handle pin button state
    if (parseInt(note.is_pinned) === 1) {
        pinBtn.classList.add('text-amber-600', 'bg-amber-50', 'dark:bg-amber-950/40', 'border-amber-300');
        pinBtn.innerHTML = '<i data-lucide="pin" class="w-4 h-4 fill-amber-500"></i><span class="text-xs font-medium">Tersemat</span>';
    } else {
        pinBtn.classList.remove('text-amber-600', 'bg-amber-50', 'dark:bg-amber-950/40', 'border-amber-300');
        pinBtn.innerHTML = '<i data-lucide="pin" class="w-4 h-4"></i><span class="text-xs">Sematkan</span>';
    }

    // Handle lock button state
    if (parseInt(note.is_locked) === 1) {
        lockBtn.classList.add('text-rose-600', 'bg-rose-50', 'dark:bg-rose-950/40', 'border-rose-300');
        lockBtn.innerHTML = '<i data-lucide="lock" class="w-4 h-4"></i><span class="text-xs font-medium">Terkunci</span>';
    } else {
        lockBtn.classList.remove('text-rose-600', 'bg-rose-50', 'dark:bg-rose-950/40', 'border-rose-300');
        lockBtn.innerHTML = '<i data-lucide="unlock" class="w-4 h-4"></i><span class="text-xs">Kunci PIN</span>';
    }

    // Handle trash view vs active view
    if (parseInt(note.is_deleted) === 1) {
        restoreBtn.classList.remove('hidden');
        trashBtn.innerHTML = '<i data-lucide="trash-2" class="w-4 h-4 text-rose-500"></i><span class="text-xs text-rose-500">Hapus Permanen</span>';
    } else {
        restoreBtn.classList.add('hidden');
        trashBtn.innerHTML = '<i data-lucide="trash-2" class="w-4 h-4"></i><span class="text-xs">Hapus</span>';
    }

    updateWordCount();
    setSaveStatus('Tersimpan');
    if (window.lucide) lucide.createIcons();
}

// 8. Tampilan Kosong Ketika Tidak Ada Catatan Terpilih
function renderEmptyEditor() {
    state.currentNote = null;
    document.getElementById('editor-content-state').classList.add('hidden');
    document.getElementById('editor-empty-state').classList.remove('hidden');
}

// 9. Simpan Otomatis (Auto-Save Debounced)
function triggerAutoSave() {
    if (!state.currentNote) return;

    setSaveStatus('Menyimpan...', true);
    clearTimeout(state.autoSaveTimeout);

    state.autoSaveTimeout = setTimeout(() => {
        performSave();
    }, 1200);
}

async function performSave() {
    if (!state.currentNote) return;

    const title = document.getElementById('note-title-input').value;
    const content = document.getElementById('note-content-input').value;
    const categoryId = document.getElementById('note-category-select').value;

    const payload = {
        id: state.currentNote.id,
        title: title,
        content: content,
        category_id: categoryId || null,
        is_pinned: state.currentNote.is_pinned || 0,
        is_locked: state.currentNote.is_locked || 0,
        color_tag: state.currentNote.color_tag || 'default'
    };

    try {
        const res = await fetch('api.php?action=save_note', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            setSaveStatus('Tersimpan otomatis');
            // Refresh notes list snippet without re-selecting note
            loadNotes(true);
            loadMetaData();
        } else {
            setSaveStatus('Gagal menyimpan');
        }
    } catch (err) {
        console.error('Save error:', err);
        setSaveStatus('Koneksi terputus');
    }
}

// 10. Buat Catatan Baru
async function createNewNote() {
    const payload = {
        title: 'Catatan Baru',
        content: '',
        category_id: state.activeFilter === 'category' ? state.activeCategoryId : null,
        is_pinned: state.activeFilter === 'pinned' ? 1 : 0,
        is_locked: 0
    };

    try {
        const res = await fetch('api.php?action=save_note', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success && data.note_id) {
            await loadMetaData();
            await loadNotes();
            selectNote(data.note_id);
            // Fokuskan kursor ke input judul
            setTimeout(() => {
                const titleInput = document.getElementById('note-title-input');
                titleInput.focus();
                titleInput.select();
            }, 100);
        }
    } catch (err) {
        console.error('Create note error:', err);
    }
}

// 11. Hapus Catatan
async function deleteCurrentNote() {
    if (!state.currentNote) return;
    const isTrash = parseInt(state.currentNote.is_deleted) === 1;

    const confirmMsg = isTrash 
        ? 'Hapus catatan ini secara permanen? Data tidak dapat dipulihkan.' 
        : 'Pindahkan catatan ini ke Sampah?';

    if (!confirm(confirmMsg)) return;

    try {
        const res = await fetch('api.php?action=delete_note', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: state.currentNote.id, permanent: isTrash })
        });
        const data = await res.json();

        if (data.success) {
            state.activeNoteId = null;
            await loadMetaData();
            await loadNotes();
        }
    } catch (err) {
        console.error('Delete note error:', err);
    }
}

// 12. Pulihkan Catatan dari Sampah
async function restoreCurrentNote() {
    if (!state.currentNote) return;

    try {
        const res = await fetch('api.php?action=restore_note', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: state.currentNote.id })
        });
        const data = await res.json();

        if (data.success) {
            await loadMetaData();
            await loadNotes();
            selectNote(state.currentNote.id);
        }
    } catch (err) {
        console.error('Restore note error:', err);
    }
}

// 13. Toggle Pin
async function togglePin() {
    if (!state.currentNote) return;
    const newStatus = parseInt(state.currentNote.is_pinned) === 1 ? 0 : 1;
    state.currentNote.is_pinned = newStatus;

    try {
        await fetch('api.php?action=toggle_pin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: state.currentNote.id })
        });
        renderNoteInEditor(state.currentNote);
        await loadMetaData();
        await loadNotes(true);
    } catch (err) {
        console.error('Toggle pin error:', err);
    }
}

// 14. Kunci / Buka Kunci Catatan (Set PIN)
function openSetLockModal() {
    if (!state.currentNote) return;

    if (parseInt(state.currentNote.is_locked) === 1) {
        // Jika sudah terkunci, tawarkan untuk melepas kunci
        if (confirm('Buka/Lepas kunci PIN dari catatan ini?')) {
            state.currentNote.is_locked = 0;
            performSave();
            renderNoteInEditor(state.currentNote);
        }
    } else {
        // Tampilkan modal pasang PIN
        document.getElementById('set-pin-input').value = '';
        document.getElementById('modal-set-pin').classList.remove('hidden');
    }
}

async function submitSetPin() {
    const pin = document.getElementById('set-pin-input').value.trim();
    if (!pin || pin.length < 4) {
        alert('PIN minimal 4 digit angka/karakter!');
        return;
    }

    state.currentNote.is_locked = 1;
    state.unlockedNotes[state.currentNote.id] = pin;

    const payload = {
        id: state.currentNote.id,
        title: document.getElementById('note-title-input').value,
        content: document.getElementById('note-content-input').value,
        category_id: document.getElementById('note-category-select').value || null,
        is_pinned: state.currentNote.is_pinned || 0,
        is_locked: 1,
        pin_code: pin
    };

    try {
        await fetch('api.php?action=save_note', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        closeModal('modal-set-pin');
        renderNoteInEditor(state.currentNote);
        loadNotes(true);
        loadMetaData();
        alert('Catatan berhasil dikunci dengan PIN!');
    } catch (err) {
        console.error(err);
    }
}

// 15. Modal Buka PIN Catatan
function showPinModal(noteId, title, isError = false) {
    const modal = document.getElementById('modal-verify-pin');
    document.getElementById('pin-modal-note-title').textContent = title || 'Catatan Terkunci';
    const pinInput = document.getElementById('verify-pin-input');
    pinInput.value = '';
    
    const errMsg = document.getElementById('pin-error-msg');
    if (isError) {
        errMsg.classList.remove('hidden');
    } else {
        errMsg.classList.add('hidden');
    }

    modal.setAttribute('data-note-id', noteId);
    modal.classList.remove('hidden');
    setTimeout(() => pinInput.focus(), 100);
}

function submitVerifyPin() {
    const modal = document.getElementById('modal-verify-pin');
    const noteId = modal.getAttribute('data-note-id');
    const pin = document.getElementById('verify-pin-input').value.trim();

    if (!pin) return;

    selectNote(noteId, pin);
    closeModal('modal-verify-pin');
}

// 16. Kategori Baru
async function submitNewCategory() {
    const name = document.getElementById('new-cat-name').value.trim();
    const color = document.getElementById('new-cat-color').value;

    if (!name) {
        alert('Nama kategori tidak boleh kosong!');
        return;
    }

    try {
        const res = await fetch('api.php?action=save_category', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, color })
        });
        const data = await res.json();

        if (data.success) {
            closeModal('modal-new-category');
            document.getElementById('new-cat-name').value = '';
            await loadMetaData();
            setFilter('category', data.id);
        } else {
            alert(data.message);
        }
    } catch (err) {
        console.error(err);
    }
}

// 17. Format Textarea Markdown Shortcuts
function insertMarkdown(format) {
    const textarea = document.getElementById('note-content-input');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = textarea.value.substring(start, end);
    let replacement = '';

    switch(format) {
        case 'bold': replacement = `**${selected || 'teks tebal'}**`; break;
        case 'italic': replacement = `*${selected || 'teks miring'}*`; break;
        case 'strike': replacement = `~~${selected || 'teks coret'}~~`; break;
        case 'h1': replacement = `\n# ${selected || 'Judul Bagian'}\n`; break;
        case 'h2': replacement = `\n## ${selected || 'Sub Judul'}\n`; break;
        case 'todo': replacement = `\n- [ ] ${selected || 'Tugas baru'}`; break;
        case 'quote': replacement = `\n> ${selected || 'Kutipan penting'}\n`; break;
        case 'code': replacement = `\n\`\`\`\n${selected || 'kode di sini'}\n\`\`\`\n`; break;
        case 'bullet': replacement = `\n- ${selected || 'Poin catatan'}`; break;
    }

    textarea.setRangeText(replacement, start, end, 'end');
    textarea.focus();
    triggerAutoSave();
    updateWordCount();
}

// 18. Export Catatan ke Markdown & Cetak / PDF
function exportMarkdown() {
    if (!state.currentNote) return;
    const title = document.getElementById('note-title-input').value || 'catatan';
    const content = document.getElementById('note-content-input').value || '';
    const fullText = `# ${title}\n\n${content}`;

    const blob = new Blob([fullText], { type: 'text/markdown;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${title.toLowerCase().replace(/[^a-z0-9]/g, '_')}.md`;
    a.click();
    URL.revokeObjectURL(url);
}

function printNote() {
    window.print();
}

// 19. Setup Event Listeners
function setupEventListeners() {
    // Input title & content auto-save
    const titleInput = document.getElementById('note-title-input');
    const contentInput = document.getElementById('note-content-input');
    const categorySelect = document.getElementById('note-category-select');

    titleInput.addEventListener('input', triggerAutoSave);
    contentInput.addEventListener('input', () => {
        triggerAutoSave();
        updateWordCount();
    });
    categorySelect.addEventListener('change', triggerAutoSave);

    // Search input
    let searchTimeout = null;
    const searchInput = document.getElementById('search-input');
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = e.target.value;
            loadNotes();
        }, 300);
    });

    // Keyboard shortcut Cmd/Ctrl + S untuk force save
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            performSave();
        }
        // Esc untuk tutup modal
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay').forEach(m => m.classList.add('hidden'));
        }
    });
}

// Filter Navigation
function setFilter(filterType, categoryId = null) {
    state.activeFilter = filterType;
    state.activeCategoryId = categoryId;
    renderSidebar();
    loadNotes();
}

function setSaveStatus(text, saving = false) {
    const el = document.getElementById('save-status');
    el.innerHTML = saving 
        ? `<span class="inline-block w-2 h-2 rounded-full bg-amber-400 animate-pulse mr-1"></span> ${text}`
        : `<span class="text-emerald-500 font-semibold mr-1">✓</span> ${text}`;
}

function updateWordCount() {
    const text = document.getElementById('note-content-input').value;
    const chars = text.length;
    const words = text.trim() === '' ? 0 : text.trim().split(/\s+/).length;
    document.getElementById('editor-stats').textContent = `${words} kata · ${chars} karakter`;
}

// Database Setup Banner & Wizard
function showDbSetupBanner(msg, hint) {
    const banner = document.getElementById('db-setup-banner');
    document.getElementById('db-banner-msg').textContent = msg;
    document.getElementById('db-banner-hint').textContent = hint || '';
    banner.classList.remove('hidden');
}

function hideDbSetupBanner() {
    document.getElementById('db-setup-banner').classList.add('hidden');
}

async function runAutoSetup() {
    const btn = document.getElementById('btn-run-setup');
    btn.disabled = true;
    btn.innerHTML = 'Sedang Inisialisasi...';

    try {
        const res = await fetch('setup.php');
        const data = await res.json();

        if (data.success) {
            alert('Sukses: ' + data.message);
            hideDbSetupBanner();
            loadMetaData();
            loadNotes();
        } else {
            alert('Gagal: ' + data.message + '\n\n' + (data.hint || ''));
        }
    } catch (err) {
        alert('Gagal menghubungi setup.php: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Setup Database Otomatis';
    }
}

// Modal Helper
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Escape HTML utility
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr.replace(/-/g, '/'));
    const now = new Date();
    
    const isToday = date.toDateString() === now.toDateString();
    if (isToday) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}
