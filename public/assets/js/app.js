// Buka & Tutup Popup / Dialog
function bukaPopup(popupId) {
const el = document.getElementById(popupId);
if (el) {
    el.style.display = 'flex';
    el.classList.add('show');
}
}

function tutupPopup(popupId) {
const el = document.getElementById(popupId);
if (el) {
    el.style.display = 'none';
    el.classList.remove('show');
}
}

// Komponen Popup Konfirmasi Reusable
function bukaPopupKonfirmasi(opsi) {
const popup = document.getElementById('popup-konfirmasi-global') ||
document.getElementById('modal-konfirmasi-global');
if (!popup) return;

const judulEl   = document.getElementById('konfirmasi-judul');
const pesanEl   = document.getElementById('konfirmasi-pesan');
const btnSubmit = document.getElementById('konfirmasi-btn-submit');
const formEl    = document.getElementById('konfirmasi-form');
const inputsBox = document.getElementById('konfirmasi-hidden-inputs');

judulEl.textContent   = opsi.judul || 'Konfirmasi Tindakan';
pesanEl.textContent   = opsi.pesan || opsi.deskripsi || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
btnSubmit.textContent = opsi.teksTombol || 'Ya, Lanjutkan';

const kelasBtn = opsi.kelasTombol || (opsi.berbahaya ? 'btn-danger' : 'btn-dark');
btnSubmit.className = 'btn ' + kelasBtn;

if (opsi.action) {
    formEl.setAttribute('action', opsi.action);
} else {
    formEl.setAttribute('action', '#');
}

// Bersihkan input hidden sebelumnya lalu isi dengan data baru
inputsBox.innerHTML = '';
if (opsi.fields) {
    for (const key in opsi.fields) {
        if (Object.prototype.hasOwnProperty.call(opsi.fields, key)) {
            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = key;
            input.value = opsi.fields[key];
            inputsBox.appendChild(input);
        }
    }
}

// Eksekusi submit form atau callback custom saat konfirmasi disetujui
formEl.onsubmit = function (e) {
    if (!opsi.action || opsi.action === '#') {
        e.preventDefault();
        tutupPopup('popup-konfirmasi-global');
        tutupPopup('modal-konfirmasi-global');
        if (typeof opsi.onConfirm === 'function') {
            opsi.onConfirm();
        }
    }
};

const targetId = document.getElementById('popup-konfirmasi-global') ? 'popup-konfirmasi-global' :
'modal-konfirmasi-global';
bukaPopup(targetId);
}

// 3. Toggle Tampilkan / Sembunyikan Password
function togglePassword(inputId = 'password', iconId = 'icon-eye', labelId = 'label-toggle-pass') {
const input = document.getElementById(inputId);
const icon  = document.getElementById(iconId);
const label = document.getElementById(labelId);
if (!input) return;

if (input.type === 'password') {
    input.type = 'text';
    if (icon) {
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
    }
    if (label) {
        label.textContent = 'Sembunyikan';
    }
} else {
    input.type = 'password';
    if (icon) {
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
    if (label) {
        label.textContent = 'Tampilkan';
    }
}
}

function togglePasswordInput(inputId, btnEl) {
const input = document.getElementById(inputId);
if (!input) return;
if (input.type === 'password') {
    input.type = 'text';
    btnEl.textContent = 'Sembunyikan';
} else {
    input.type = 'password';
    btnEl.textContent = 'Tampilkan';
}
}

// Dropdown Topbar & Event Listener Popup
document.addEventListener('DOMContentLoaded', function () {
const avatarBtn = document.getElementById('topbar-avatar-btn');
const dropdown  = document.getElementById('profile-dropdown');

if (avatarBtn && dropdown) {
    avatarBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('show');
    });

    document.addEventListener('click', function () {
        dropdown.classList.remove('show');
    });
}

// Tutup popup jika area gelap di luar kotak diklik
const backdrops = document.querySelectorAll('.popup-backdrop, .popup-overlay, .modal-backdrop, .modal-overlay');
backdrops.forEach(function (backdrop) {
    backdrop.addEventListener('click', function (e) {
        if (e.target === backdrop) {
            backdrop.classList.remove('show');
            backdrop.style.display = 'none';
        }
    });
});

// Tutup popup via tombol atribut [data-popup-close] atau [data-modal-close]
const closeButtons = document.querySelectorAll('[data-popup-close], [data-modal-close]');
closeButtons.forEach(function (btn) {
    btn.addEventListener('click', function () {
        const targetId = btn.getAttribute('data-popup-close') || btn.getAttribute('data-modal-close');
        if (targetId) {
            tutupPopup(targetId);
        }
    });
});

// Auto-hide Toast Notification setelah 4 detik
const flashToast = document.querySelector('.flash-banner');
if (flashToast) {
    setTimeout(function () {
        flashToast.classList.add('hide-toast');
        setTimeout(function () {
            flashToast.remove();
        }, 300);
    }, 4000);
}
});