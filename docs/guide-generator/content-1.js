// Isi panduan: Administrator dan Sales.
// f = fungsi/tujuan halaman, s = langkah pemakaian, k = kolom isian [nama, wajib, penjelasan], n = catatan penting

module.exports = {
  administrator: {
    intro: `Administrator adalah super admin sistem. Seluruh menu terbuka untuk role ini, termasuk data milik
      semua sales. Tugas utamanya: menyiapkan Pra Lead, mendistribusikan prospek ke sales, memantau pipeline,
      memproses Request PO ke Accurate, menerbitkan Invoice, mengelola akun pengguna, dan mengatur sistem.`,

    'dashboard': {
      f: `Ringkasan seluruh perusahaan dalam satu layar: jumlah Pra Lead per status, distribusi penugasan ke
        tiap sales, lead aktif, project berjalan, dan aktivitas terbaru.`,
      s: [
        'Kartu di baris atas menampilkan angka kunci: Pra Lead, Ditugaskan, Menunggu Konfirmasi, Ditolak, Lead Aktif, dan Project Aktif.',
        'Grafik distribusi menunjukkan pembagian Pra Lead per sales — dipakai untuk melihat apakah beban kerja sudah merata.',
        'Daftar Pra Lead terbaru dan aktivitas terbaru dapat diklik untuk langsung membuka datanya.',
      ],
      n: 'Dashboard hanya menampilkan data, tidak ada isian di sini. Semua angka dihitung ulang setiap halaman dibuka.',
    },

    'pipeline': {
      f: `Memantau seluruh pipeline penjualan dalam satu halaman: dari Lead, Design Request, Penawaran, sampai
        Request PO yang masih terbuka.`,
      s: [
        'Kolom kiri memuat tabel pipeline Lead, Design Request, dan Penawaran beserta nilainya.',
        'Kolom kanan memuat daftar Request PO yang masih berjalan lengkap dengan progres checklist kelengkapannya.',
        'Panel SLA menunjukkan pekerjaan yang melewati batas waktu wajar sehingga perlu ditindaklanjuti.',
      ],
      n: 'Halaman ini bersifat monitoring. Untuk mengubah data, buka menu masing-masing.',
    },

    'pra-leads': {
      f: `Titik masuk seluruh prospek. Administrator mencatat prospek mentah di sini, lalu menugaskannya ke
        sales. Setelah dikirim, prospek muncul di menu Request Masuk milik sales.`,
      s: [
        'Kartu status di baris atas memperlihatkan sebaran Pra Lead: Draft, Ditugaskan, Menunggu Konfirmasi Sales, dan Ditolak Sales.',
        'Gunakan kolom pencarian, filter Status, filter Sumber, dan filter tanggal untuk mempersempit daftar.',
        'Klik tombol titik tiga pada kolom AKSI untuk mengubah atau menghapus Pra Lead.',
        'Klik Tambah Pra Lead untuk membuka form input di bagian bawah halaman.',
        'Isi form, pilih Sales PIC, lalu tekan salah satu tombol di baris paling bawah.',
      ],
      k: [
        ['Nama Instansi', true, 'Nama perusahaan, sekolah, rumah sakit, atau instansi calon customer.'],
        ['Divisi', false, 'Bagian yang membutuhkan, contoh Laboratorium, Procurement, atau R&D.'],
        ['PIC', true, 'Nama orang yang dihubungi di instansi tersebut.'],
        ['Jabatan PIC', false, 'Posisi PIC, membantu sales menyesuaikan pendekatan.'],
        ['No. WhatsApp', true, 'Nomor aktif untuk follow up. Format 08xxxxxxxxxx.'],
        ['Email', false, 'Alamat email PIC bila tersedia.'],
        ['Sumber', true, 'Asal prospek. Pilihan: Distributor, Supplier, Loops LabNusantara, Robust Multilab Solusindo, Robust Indonesia - Sinar Lab, MEC.'],
        ['Jenis Kebutuhan', false, 'Ringkasan jenis pekerjaan, contoh renovasi lab atau pengadaan fume hood.'],
        ['Lokasi Project', false, 'Kota atau kabupaten lokasi pekerjaan.'],
        ['Kebutuhan Awal', false, 'Uraian singkat kebutuhan customer sebagai bekal sales.'],
        ['Estimasi Nilai Minimum / Maksimum', false, 'Perkiraan rentang nilai proyek. Boleh dikosongkan bila belum diketahui.'],
        ['Prioritas', false, 'High, Medium, atau Low. Menentukan urutan penanganan sales.'],
        ['Catatan dari Admin', false, 'Pesan internal untuk sales yang menerima prospek.'],
        ['Sales PIC', true, 'Sales yang ditugaskan. Kartu Sales Ditugaskan menampilkan jumlah lead aktif tiap sales beserta rekomendasi beban paling ringan.'],
      ],
      n: `Tiga tombol penyimpanan berbeda fungsi: <b>Simpan Draft</b> menyimpan tanpa menugaskan siapa pun,
        <b>Simpan</b> menyimpan beserta sales terpilih tanpa mengirim notifikasi, dan <b>Kirim ke Sales</b>
        meneruskan prospek sehingga muncul di Request Masuk sales dan statusnya menjadi Menunggu Konfirmasi Sales.`,
    },

    'assignment': {
      f: `Memantau pemerataan beban kerja sales dan memindahkan kepemilikan lead bila diperlukan.`,
      s: [
        'Tabel Workload Distribution membandingkan Request Masuk, Leads Aktif, Design Request Aktif, Penawaran Aktif, dan Project Aktif tiap sales.',
        'Lead Acceptance Monitoring memperlihatkan berapa prospek yang dikirim dan berapa yang benar-benar diterima sales.',
        'Top Sales Performance mengurutkan sales berdasarkan win rate.',
        'Klik nama sales untuk menampilkan profil dan ringkasannya di panel kanan.',
        'Untuk memindahkan lead: pada panel Reassign Lead / Ownership pilih Lead, periksa kolom Dari, pilih sales tujuan pada kolom Ke, lalu tekan Reassign.',
        'Tombol Export Excel mengunduh tabel assignment untuk dilaporkan ke manajemen.',
      ],
      n: `Menu ini hanya terbuka untuk Administrator dan SPV Sales. Memindahkan lead sekaligus memindahkan
        kepemilikan Customer beserta riwayatnya, jadi koordinasikan dengan sales lama terlebih dahulu.`,
    },

    'request-masuk': {
      f: `Menampilkan prospek yang sudah dikirim ke sales beserta status penerimaannya. Administrator memakai
        halaman ini untuk memastikan tidak ada prospek yang menggantung.`,
      s: [
        'Kartu di atas merangkum Request Baru, Diterima Hari Ini, Menunggu Respon, dan Ditolak.',
        'Klik satu baris untuk membuka panel Detail Request di bawah tabel.',
        'Periksa kolom Diterima untuk melihat sudah berapa lama request menunggu respon sales.',
      ],
      n: 'Tombol Terima / Tolak hanya dipakai oleh sales pemilik request.',
    },

    'leads': {
      f: `Daftar seluruh lead dari semua sales. Lead adalah prospek yang sudah diterima sales dan sedang
        digarap.`,
      s: [
        'Gunakan pencarian dan filter untuk menemukan lead tertentu.',
        'Klik satu baris untuk melihat detail lead di panel samping.',
        'Kolom Stage menunjukkan posisi lead pada pipeline: Lead, Design Request, Penawaran, Negosiasi, atau Won.',
      ],
    },

    'activities': {
      f: `Rekap seluruh aktivitas follow up sales: meeting, call, survey lokasi, presentasi, WhatsApp, email,
        dan penyerahan penawaran.`,
      s: [
        'Kartu ringkasan menampilkan aktivitas hari ini, yang masih pending, yang selesai hari ini, dan yang terlambat.',
        'Gunakan tab periode (hari ini / minggu ini / bulan ini) atau klik tanggal pada kalender mini di samping.',
        'Klik satu aktivitas untuk melihat detail dan hasilnya.',
      ],
      n: 'Tombol Tambah Activity hanya muncul pada akun Sales, karena aktivitas dicatat oleh sales yang mengerjakannya.',
    },

    'design-requests': {
      f: `Memantau seluruh permintaan desain dari sales ke Drafter dan Produksi.`,
      s: [
        'Kartu ringkasan menampilkan Total Request, Menunggu Produksi, Sedang Dikerjakan, dan Selesai.',
        'Filter berdasarkan Status, Urgensi, dan PIC Produksi.',
        'Kolom Progress menunjukkan persentase pengerjaan yang diisi Drafter/Produksi.',
        'Klik tanda panah di ujung baris untuk membuka detail request.',
      ],
    },

    'quotations': {
      f: `Daftar seluruh penawaran dari semua sales beserta status dan nilainya.`,
      s: [
        'Filter berdasarkan status: Draft, Siap Dikirim, Dikirim ke Customer, Customer Setuju, Request PO Dibuat, dan lainnya.',
        'Klik satu penawaran untuk membuka detail lengkap beserta itemnya.',
        'Dari detail penawaran tersedia tombol unduh PDF dan Excel.',
      ],
    },

    'quotation-approvals': {
      f: `Halaman monitoring penawaran lintas sales. Dipakai untuk meninjau isi dan nilai penawaran sebelum
        atau sesudah dikirim ke customer.`,
      s: [
        'Gunakan filter status untuk memisahkan penawaran yang masih draft dan yang sudah dikirim.',
        'Klik satu penawaran untuk membuka rincian item, harga, diskon, pajak, dan biaya tambahan.',
      ],
    },

    'request-po': {
      f: `Daftar Request PO — dokumen yang menjembatani penawaran yang dimenangkan dengan pembuatan PO resmi
        di Accurate.`,
      s: [
        'Cari berdasarkan nomor request, nomor PO customer, nomor PO Accurate, atau nama customer.',
        'Filter Status memisahkan Draft, Diajukan ke Accurate, Diproses di Accurate, PO Accurate Dibuat, Produksi, Installasi, Invoicing, Lunas, dan Dibatalkan.',
        'Baris berstatus Draft menampilkan ikon pensil untuk melanjutkan pengisian.',
        'Klik Detail untuk membuka Request PO dan memprosesnya.',
      ],
      n: 'Penawaran bertanda <b>Non-CRM</b> berarti Request PO dibuat dari PO existing yang penawarannya tidak melalui sistem ini.',
    },

    'request-po-show': {
      f: `Memproses satu Request PO: melengkapi data untuk Accurate, mengatur checklist kelengkapan, dan
        memperbarui status setelah PO resmi terbit.`,
      s: [
        'Panel Data Order menampilkan nomor proyek, customer, sales, nilai penawaran, dan status terkini.',
        'Panel Data Input Accurate berisi alamat pengiriman, PIC penerima, NPWP, termin pembayaran, dan estimasi kirim. Lengkapi lalu tekan Simpan Data Accurate.',
        'Panel Checklist Kelengkapan dapat disesuaikan: centang yang sudah beres, hapus item yang tidak diperlukan lewat ikon tempat sampah, atau tambahkan item sendiri. Tekan Simpan Checklist.',
        'Panel Update Accurate di kanan dipakai untuk mengganti status, mengisi No PO Accurate, Tanggal PO Accurate, dan catatan.',
        'Tombol Export PDF menghasilkan dokumen Request PO untuk arsip atau lampiran.',
      ],
      n: `Saat status diubah menjadi <b>PO Accurate Dibuat</b>, sistem otomatis membuat Project dan meneruskannya
        ke Drafter atau langsung ke Produksi. No PO Accurate dan Tanggal PO Accurate wajib diisi pada status ini.`,
    },

    'invoices': {
      f: `Daftar seluruh invoice beserta nilai tagihan dan realisasi pembayarannya.`,
      s: [
        'Cari berdasarkan kode invoice, nama customer, nomor proyek, atau nama project.',
        'Filter status: Draft, Diterbitkan, Dibayar Sebagian, Lunas, dan Dibatalkan.',
        'Panel Siap Ditagihkan memuat Request PO yang delivery-nya sudah selesai namun belum punya invoice.',
      ],
    },

    'invoice-create': {
      f: `Menerbitkan invoice atas satu Request PO, lengkap dengan pembagian termin pembayaran.`,
      s: [
        'Halaman ini dibuka dari tombol Terbitkan Invoice pada detail Request PO atau dari panel Siap Ditagihkan.',
        'Periksa data customer, nomor proyek, dan nilai penawaran yang tampil otomatis.',
        'Isi Tanggal Invoice.',
        'Susun termin pembayaran: deskripsi, persentase, nominal, dan tanggal jatuh tempo. Tambah baris bila termin lebih dari satu.',
        'Tekan Simpan Invoice.',
      ],
      n: `Total seluruh termin <b>harus sama persis</b> dengan grand total penawaran. Bila berbeda, sistem menolak
        dan menampilkan selisihnya. Invoice hanya bisa diterbitkan setelah Delivery menyelesaikan pengiriman dan
        penerimaan customer dikonfirmasi.`,
    },

    'invoice-show': {
      f: `Memantau realisasi pembayaran tiap termin invoice dan mencatat pembayaran yang masuk.`,
      s: [
        'Panel atas menampilkan nilai invoice, yang sudah dibayar, dan sisa tagihan.',
        'Tabel termin memuat deskripsi, persentase, nominal, jatuh tempo, dan statusnya.',
        'Untuk mencatat pembayaran: isi nomor invoice Accurate, nominal yang dibayar, dan tanggal bayar pada termin bersangkutan, lalu simpan.',
        'Tombol Export PDF menghasilkan dokumen invoice.',
      ],
      n: 'Status invoice berubah otomatis menjadi Dibayar Sebagian atau Lunas mengikuti total pembayaran termin.',
    },

    'item-masters': {
      f: `Katalog item standar beserta HPP dan margin bawaannya. Dipakai sebagai sumber saat menyusun item
        penawaran agar harga konsisten.`,
      s: [
        'Cari item berdasarkan kode, nama, atau kategori.',
        'Klik Tambah Master Item untuk menambah item baru.',
        'Isi kode, kategori, nama, varian, satuan, HPP bawaan, margin bawaan, dan spesifikasi.',
        'Nonaktifkan item yang sudah tidak dijual daripada menghapusnya, agar riwayat penawaran lama tetap utuh.',
      ],
      n: 'Master Item dikelola oleh tim Produksi. Administrator memiliki akses sebagai super admin. Sales tidak mengelola data teknis dan HPP.',
    },

    'customers': {
      f: `Database seluruh customer beserta tahap pipeline dan PIC-nya.`,
      s: [
        'Klik satu customer untuk melihat detail di panel samping: PIC, kontak, alamat, riwayat lead, penawaran, dan project.',
        'Kolom Stage menunjukkan posisi customer: Identify, Approaching, Follow Up, Won / Closing, Lost, atau Maintaining.',
      ],
    },

    'projects': {
      f: `Daftar project yang sudah berjalan, hasil dari penawaran yang dimenangkan.`,
      s: [
        'Filter berdasarkan status: Planning, Berjalan, Finishing, Selesai, atau Dibatalkan.',
        'Klik satu project untuk membuka detailnya beserta termin pembayaran dan tim internal.',
      ],
    },

    'project-monitoring': {
      f: `Tampilan menyerupai spreadsheet monitoring: satu baris per project, memuat status produksi, QC,
        delivery, termin invoice, dan kolom administrasi.`,
      s: [
        'Geser tabel ke kanan untuk melihat kolom termin invoice dan checklist proses.',
        'Kolom Comment, KP (konfirmasi pembayaran), dan Bukti Potong PPh dapat diisi langsung pada tabel.',
        'Tekan Simpan pada baris yang diubah.',
      ],
      n: 'Kolom administrasi dapat diisi oleh Administrator, Sales, dan role Administration.',
    },

    'workspace': {
      f: `Ruang kerja satu project yang dilihat bersama oleh seluruh divisi. Tab Informasi Project memuat
        identitas project.`,
      s: [
        'Tab Informasi Project: project manager, penawaran sumber, tanggal mulai, target selesai, lokasi, prioritas, dan scope of work.',
        'Panel Progress Operasional merangkum kemajuan gabungan produksi, QC, dan delivery.',
      ],
    },

    'workspace-ops': {
      f: `Tab Production, QC & Delivery — tempat tiga divisi operasional bekerja pada project yang sama.`,
      s: [
        'Bagian Progress Desain & Produksi diisi Produksi: persentase progres, catatan, dan laporan produksi.',
        'Bagian Quality Control diisi QC: checklist otomatis per item penawaran, catatan QC, dan lampiran.',
        'Bagian Delivery diisi tim Delivery: jadwal kirim, POD, nama penerima, dan Delivery Order.',
        'Administrator dapat melihat dan mengisi seluruh bagian.',
      ],
      n: 'Urutannya berurutan: Produksi selesai lebih dulu, baru QC, baru Delivery. Invoice baru bisa terbit setelah Delivery selesai.',
    },

    'calendar': {
      f: `Kalender gabungan berisi jadwal aktivitas sales dan tenggat pekerjaan.`,
      s: [
        'Klik tanggal untuk melihat daftar agenda pada hari tersebut.',
        'Gunakan navigasi bulan untuk berpindah periode.',
      ],
    },

    'documents': {
      f: `Pusat dokumen seluruh sistem: sketsa dari sales, drawing dari drafter, laporan produksi, dokumen QC,
        dan bukti pengiriman.`,
      s: [
        'Cari dokumen berdasarkan nama atau kategori.',
        'Tekan Preview untuk melihat isi dokumen, atau Download untuk mengunduhnya.',
        'Untuk mengunggah: isi nama dokumen, pilih kategori, pilih file, lalu tekan Upload.',
      ],
      n: 'Dokumen yang dihapus diarsipkan (soft delete), bukan dihapus permanen dari database.',
    },

    'reports': {
      f: `Laporan ringkas performa penjualan dan operasional.`,
      s: [
        'Pilih rentang periode laporan.',
        'Periksa ringkasan nilai pipeline, penawaran, dan project.',
      ],
    },

    'users': {
      f: `Mengelola akun pengguna: menambah, mengubah, menonaktifkan, dan menghapus akun.`,
      s: [
        'Cari akun berdasarkan nama, email, atau jabatan; filter berdasarkan Role dan Status.',
        'Klik ikon pensil untuk mengubah data akun.',
        'Klik ikon toggle untuk mengaktifkan atau menonaktifkan akun tanpa menghapusnya.',
        'Klik ikon tempat sampah untuk menghapus akun. Sistem meminta konfirmasi terlebih dahulu.',
        'Klik Tambah User untuk membuat akun baru.',
      ],
      k: [
        ['Nama', true, 'Nama lengkap pengguna, tampil di sidebar dan riwayat aktivitas.'],
        ['Email', true, 'Dipakai sebagai username saat login. Harus unik.'],
        ['Jabatan', false, 'Keterangan posisi, contoh Sales Engineer atau Drafter.'],
        ['Telepon', false, 'Nomor kontak internal.'],
        ['Role / Hak Akses', true, 'Menentukan menu yang terbuka: Administrator, SPV Sales, Sales, Drafter, Produksi, Quality Control, Delivery, atau Administration.'],
        ['Password', true, 'Minimal 6 karakter, harus diketik dua kali. Saat mengubah akun, kosongkan bila password tidak diganti.'],
        ['Status', false, 'Aktif atau Nonaktif. Akun nonaktif tidak dapat login.'],
      ],
      n: `Pengaman bawaan: akun tidak dapat menghapus atau menonaktifkan dirinya sendiri, sistem menjaga minimal
        satu Administrator tetap ada, dan akun non-Administrator tidak dapat melihat maupun mengubah akun
        Administrator. Menghapus akun bersifat arsip (soft delete), sehingga riwayat pekerjaannya tetap utuh.`,
    },

    'system-settings': {
      f: `Pengaturan sistem: identitas perusahaan, logo, dan perintah pemeliharaan. Hanya Administrator yang
        dapat membuka halaman ini.`,
      s: [
        'Ubah Nama Perusahaan dan Tagline yang tampil di sidebar serta dokumen cetak.',
        'Unggah logo perusahaan dan favicon.',
        'Panel perintah sistem dipakai untuk pemeliharaan teknis seperti membersihkan cache.',
      ],
      n: 'Logo maksimal 2 MB dan favicon maksimal 1 MB.',
    },

    'search': {
      f: `Pencarian global dari kolom di bagian atas layar, mencakup customer, PIC, project, lead, dan aktivitas
        sekaligus.`,
      s: [
        'Ketik kata kunci pada kolom pencarian di header, lalu tekan Enter.',
        'Hasil dikelompokkan per jenis data. Klik salah satu untuk langsung membuka datanya.',
      ],
      n: 'Hasil pencarian mengikuti hak akses. Sales hanya menemukan data miliknya sendiri.',
    },

    'profile': {
      f: `Mengubah data diri dan mengganti password akun sendiri.`,
      s: [
        'Perbarui nama, email, jabatan, atau nomor telepon lalu tekan Simpan.',
        'Untuk mengganti password: isi password baru dan ulangi konfirmasinya, lalu tekan Simpan Password.',
      ],
    },
  },
};
