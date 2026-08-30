// Isi panduan: Sales.

module.exports = {
  sales: {
    intro: `Sales adalah pemilik proses penjualan dari prospek masuk sampai order terbit. Sejak role Sales Admin
      dihapus, Sales juga mewarisi sebagian pekerjaan back office: Pra Leads, Invoice, Project Monitoring, dan
      Manage User. Pada menu penjualan — Leads, Customers, Design Request, Penawaran, Project, dan Activities —
      Sales hanya melihat data miliknya sendiri. Menu back office memuat data lintas sales karena sifat
      pekerjaannya memang menyeluruh. Assignment tidak termasuk: pemindahan kepemilikan lead adalah kewenangan
      Administrator dan SPV Sales.`,

    'dashboard': {
      f: `Ringkasan performa pribadi: lead aktif, penawaran aktif, project berjalan, deal won bulan ini, dan
        pencapaian target.`,
      s: [
        'Kartu di baris atas memuat angka kunci milik Anda sendiri.',
        'Funnel Pipeline Penjualan memperlihatkan jumlah dan nilai pada tiap tahap: Leads, Design Request, Penawaran, Negosiasi, dan Won.',
        'Grafik Performa Penjualan membandingkan nilai pipeline dan deal won enam bulan terakhir.',
        'Panel Leads Terbaru, Ringkasan Aktivitas, dan To Do / Tindak Lanjut memuat pekerjaan yang menunggu.',
        'Tombol pintas Tambah Activity dan Lead Baru tersedia di kanan atas.',
      ],
    },

    'request-masuk': {
      f: `Kotak masuk prospek yang dikirim Administrator. Di sinilah Sales memutuskan menerima atau menolak
        sebuah prospek.`,
      s: [
        'Periksa kartu ringkasan: Request Baru, Diterima Hari Ini, Menunggu Respon, dan Ditolak.',
        'Gunakan tab Semua Request / Hari Ini / Minggu Ini serta filter prioritas untuk menyaring.',
        'Klik satu baris; panel Detail Request terbuka di bawah tabel.',
        'Baca Informasi Customer, Kebutuhan Awal, Catatan Administrator, dan Estimasi Nilai Proyek.',
        'Tekan Terima Menjadi Lead bila prospek digarap, atau Tolak Request bila tidak.',
      ],
      n: `Setelah diterima, request berpindah ke menu <b>Leads</b> dan otomatis menjadi milik Anda. Bila menolak,
        alasan penolakan wajib diisi (maksimal 500 karakter) agar Administrator dapat menindaklanjuti.`,
    },

    'leads': {
      f: `Daftar seluruh lead yang menjadi tanggung jawab Anda beserta tahapannya.`,
      s: [
        'Cari lead berdasarkan nama instansi, PIC, atau kebutuhan.',
        'Filter berdasarkan stage dan prioritas.',
        'Klik satu lead untuk melihat ringkasannya pada panel samping.',
        'Tombol Tambah Lead Baru dipakai hanya untuk prospek yang tidak berasal dari alur Pra Lead.',
      ],
    },

    'lead-create': {
      f: `Mencatat prospek baru yang Anda dapatkan sendiri, di luar distribusi Administrator.`,
      s: [
        'Isi bagian Informasi Customer, Sumber Lead, Informasi Tambahan, Kebutuhan Awal, dan Estimasi & Prioritas.',
        'Tekan Simpan Lead di bagian bawah layar.',
        'Setelah tersimpan, muncul konfirmasi berisi ID Lead yang dapat disalin.',
      ],
      k: [
        ['Nama Instansi / Perusahaan', true, 'Nama resmi calon customer.'],
        ['Divisi', false, 'Bagian yang membutuhkan, contoh Laboratorium atau Procurement.'],
        ['PIC (Person In Charge)', true, 'Nama orang yang dihubungi.'],
        ['Jabatan PIC', false, 'Posisi PIC di instansinya.'],
        ['No. WhatsApp', true, 'Nomor aktif untuk follow up.'],
        ['Email', false, 'Alamat email PIC.'],
        ['Lokasi', true, 'Alamat lengkap lokasi customer.'],
        ['Kota', true, 'Tersedia saran seluruh kota dan kabupaten di Indonesia (38 provinsi, 514 wilayah). Nama kabupaten diawali kata "Kabupaten" agar tidak tertukar dengan kota bernama sama, misalnya Bandung dan Kabupaten Bandung. Nama lain tetap bisa diketik manual.'],
        ['Tipe Instansi', true, 'Universitas, Sekolah, Rumah Sakit, Industri, Pemerintah, BUMN, BUMD, Laboratorium Swasta, Distributor, Kontraktor, atau Lainnya.'],
        ['Sumber Lead', true, 'Distributor, Supplier, Loops LabNusantara, Robust Multilab Solusindo, Robust Indonesia - Sinar Lab, atau MEC.'],
        ['Referensi / Dari', false, 'Nama orang atau pihak yang merekomendasikan.'],
        ['Catatan Awal', false, 'Konteks awal tentang lead ini.'],
        ['Tanggal Follow Up Awal', false, 'Rencana kontak berikutnya.'],
        ['Preferensi Kontak', false, 'WhatsApp, Telepon, Email, Meeting Offline, atau Meeting Online.'],
        ['Waktu Kontak Terbaik', false, 'Contoh: Pagi (09.00 - 11.00).'],
        ['Nama Laboratorium / Proyek', true, 'Nama lab atau proyek yang akan dikerjakan.'],
        ['Deskripsi Kebutuhan', false, 'Uraian kebutuhan, maksimal 500 karakter.'],
        ['Daftar Kebutuhan', false, 'Centang item yang dibutuhkan: Wall Bench, Fume Hood, Storage Cabinet, Sink Area, Meja Praktikum, Meja Instrumen, Safety Equipment, atau Lainnya.'],
        ['Estimasi Dari / Sampai (Rp)', false, 'Rentang perkiraan nilai proyek.'],
        ['Prioritas Lead', true, 'High, Medium, atau Low.'],
      ],
    },

    'lead-show': {
      f: `Halaman kerja satu lead: seluruh informasi customer, riwayat aktivitas, dan tombol untuk melanjutkan
        ke tahap berikutnya.`,
      s: [
        'Periksa Informasi Customer dan Kebutuhan yang sudah tercatat.',
        'Lihat riwayat aktivitas follow up beserta hasilnya.',
        'Gunakan tombol tindakan untuk membuat Design Request atau Penawaran dari lead ini.',
        'Tekan Edit bila ada data yang perlu diperbaiki.',
      ],
    },

    'lead-edit': {
      f: `Melengkapi atau memperbaiki data lead yang sudah tersimpan.`,
      s: [
        'Susunan form sama persis dengan form Tambah Lead, hanya sudah terisi data lama.',
        'Ubah bagian yang perlu diperbaiki lalu tekan Simpan Perubahan.',
      ],
      n: 'Perubahan data lead ikut memperbarui data Customer yang terhubung.',
    },

    'activities': {
      f: `Mencatat dan memantau seluruh follow up ke customer agar tidak ada yang terlewat.`,
      s: [
        'Kartu ringkasan menampilkan Today Activities, Pending Activities, Completed Today, dan Overdue.',
        'Gunakan tab periode atau klik tanggal pada kalender mini untuk menyaring.',
        'Klik satu aktivitas untuk melihat detail, lalu perbarui statusnya bila sudah dikerjakan.',
        'Tekan Tambah Activity untuk mencatat rencana atau hasil follow up baru.',
      ],
    },

    'activity-create': {
      f: `Mencatat satu aktivitas follow up: rencana maupun yang sudah terjadi.`,
      s: [
        'Isi seluruh kolom pada bagian kiri, atur status dan tindak lanjut pada panel kanan.',
        'Tekan Simpan.',
      ],
      k: [
        ['Tipe', true, 'Meeting, Call, Survey Lokasi, Presentasi, Follow Up, WhatsApp, Email, atau Penawaran.'],
        ['Customer', false, 'Pilih customer terkait. Daftar menampilkan nama, area, dan divisi.'],
        ['Pipeline Stage', false, 'Kosongkan untuk mengikuti stage customer saat ini. Bila diisi, stage customer ikut diperbarui setelah aktivitas disimpan.'],
        ['Judul', true, 'Ringkasan aktivitas, contoh "Presentasi awal kebutuhan lab".'],
        ['Deskripsi', false, 'Rincian pembahasan atau rencana.'],
        ['Tanggal', true, 'Tanggal pelaksanaan aktivitas.'],
        ['Waktu', false, 'Jam pelaksanaan.'],
        ['Durasi (menit)', false, 'Perkiraan lama aktivitas.'],
        ['Status', true, 'Scheduled, In Progress, Pending, Completed, atau Cancelled.'],
        ['Tindak Lanjut', false, 'Langkah berikutnya setelah aktivitas ini.'],
        ['Tgl Follow Up', false, 'Kapan tindak lanjut dijadwalkan.'],
      ],
      n: 'Aktivitas yang tersimpan langsung muncul di menu Activities dan Calendar.',
    },

    'design-requests': {
      f: `Daftar permintaan desain yang Anda kirim ke Drafter dan Produksi, beserta progres pengerjaannya.`,
      s: [
        'Pantau kartu Total Request, Menunggu Produksi, Sedang Dikerjakan, dan Selesai.',
        'Filter berdasarkan Status, Urgensi, dan PIC Produksi.',
        'Klik tanda panah di ujung baris untuk membuka detail request.',
      ],
    },

    'dr-create': {
      f: `Meminta desain, spesifikasi, BOQ, atau costing kepada tim Drafter dan Produksi.`,
      s: [
        'Pada bagian 1 Informasi Dasar, pilih data master Lead atau Customer agar identitas dan kebutuhan terisi otomatis.',
        'Isi Nomor Design Request bila memakai penomoran internal sendiri; kosongkan untuk nomor otomatis.',
        'Lengkapi bagian 2 Kebutuhan Customer: jenis laboratorium, ruang lingkup, kapasitas, dan detail kebutuhan.',
        'Unggah sketsa atau dokumen referensi pada bagian 3. Progres unggah tampil sebagai bar persentase.',
        'Centang output yang diminta pada bagian 4: Layout 2D, Rendering 3D, Shop Drawing, BOQ, dan Cost Estimation.',
        'Pilih Drafter pada panel Assignment. Panel Suggest Drafter menunjukkan siapa yang beban kerjanya paling ringan.',
        'Tekan Simpan & Kirim ke Drafter bila brief sudah lengkap, atau Simpan Draf bila masih menunggu sketsa dari customer.',
      ],
      k: [
        ['Nomor Design Request', false, 'Boleh diisi manual mengikuti penomoran internal, contoh RBS/DR/VIII/2026-014. Bila dikosongkan, sistem membuat nomor otomatis DR-001, DR-002, dan seterusnya. Nomor tidak boleh sama dengan Design Request lain.'],
        ['Ambil dari Master Lead / Customer', false, 'Mengisi Customer, PIC, Nama Proyek, dan kebutuhan secara otomatis.'],
        ['Customer / Instansi', true, 'Nama customer.'],
        ['PIC Customer', true, 'Orang yang dihubungi di sisi customer.'],
        ['Nama Proyek', true, 'Nama pekerjaan yang akan didesain.'],
        ['Tanggal Request', true, 'Tanggal permintaan dibuat.'],
        ['Deadline', true, 'Batas waktu hasil desain dibutuhkan. Tidak boleh lebih awal dari tanggal request.'],
        ['Status Urgensi', true, 'Normal atau Urgent.'],
        ['Deskripsi Singkat', true, 'Ringkasan kebutuhan, maksimal 500 karakter.'],
        ['Jenis Laboratorium / Area', true, 'Contoh: Lab Kimia, Lab QC, atau Lab Patologi.'],
        ['Ruang Lingkup', true, 'Centang item yang dibutuhkan. Bila memilih Lainnya, kolom keterangan wajib diisi.'],
        ['Kapasitas / Pengguna', false, 'Perkiraan jumlah pengguna lab.'],
        ['Detail Kebutuhan', true, 'Uraian teknis, maksimal 1000 karakter.'],
        ['Upload sketsa', false, 'Maksimal 5 file, 80 MB per file. Format PDF, JPG, PNG, WEBP, HEIC, DOC/DOCX, atau XLS/XLSX.'],
        ['Output yang Diminta', false, 'Hasil kerja yang diharapkan dari Drafter/Produksi.'],
        ['Catatan Tambahan', false, 'Informasi pelengkap, maksimal 500 karakter.'],
        ['Drafter', true, 'Drafter yang ditugaskan mengerjakan.'],
        ['Catatan untuk Drafter', false, 'Pesan khusus untuk pengerjaan, maksimal 300 karakter.'],
      ],
      n: `Setelah dikirim, status menjadi <b>Assigned</b> dan request muncul di akun Drafter terpilih.
        <b>Simpan Draf</b> menyimpan tanpa mengirim apa pun ke Drafter; draf dilanjutkan lewat ikon pensil pada
        daftar Design Request atau tombol Lanjutkan &amp; Kirim ke Drafter pada halaman detail. Lampiran yang
        sudah diunggah pada draf tetap tersimpan. Batas unggah 80 MB per file mengikuti konfigurasi PHP di
        server; bila server dibatasi lebih rendah, unggahan besar akan ditolak sebelum sampai ke aplikasi.`,
    },

    'dr-show': {
      f: `Memantau hasil kerja Drafter dan Produksi atas satu Design Request, serta meminta revisi bila perlu.`,
      s: [
        'Periksa spesifikasi, item hasil, dan rincian HPP yang diisi Produksi.',
        'Buka tab Dokumen untuk mengunduh drawing dan lampiran.',
        'Bila hasil belum sesuai, tulis alasannya pada kolom request revisi lalu kirim.',
        'Bila sudah sesuai, lanjutkan membuat penawaran dari Design Request ini.',
      ],
      n: `Revisi hanya dapat diminta setelah status <b>Completed</b>, dan hanya satu revisi terbuka pada satu
        waktu. Saat revisi diminta, spesifikasi serta HPP dikosongkan dan Drafter wajib mengunggah drawing
        revisi sebelum Produksi mengisi ulang.`,
    },

    'quotations': {
      f: `Daftar penawaran Anda beserta status, nilai, dan tindakan lanjutannya.`,
      s: [
        'Filter berdasarkan status penawaran.',
        'Klik satu penawaran untuk membuka detailnya.',
        'Tekan Buat Penawaran Baru untuk memulai penawaran.',
      ],
    },

    'quo-create': {
      f: `Menyusun penawaran untuk customer melalui wizard empat langkah: Info Dasar, Item Penawaran, Harga,
        dan Review.`,
      s: [
        'Langkah 1 Info Dasar: pilih Design Request yang sudah diselesaikan Produksi agar customer, item, gambar, dan spesifikasi termuat otomatis. Bisa juga tanpa Design Request.',
        'Tentukan Cara Membuat Penawaran: Buat Penawaran (isi item dan harga di sistem) atau Upload Penawaran (unggah Excel/PDF yang sudah jadi).',
        'Tekan Lanjut untuk masuk ke langkah Item Penawaran, lalu susun item, spesifikasi, qty, dan harga satuan.',
        'Pada langkah Harga, atur diskon, PPN, dan biaya tambahan seperti pengiriman dan instalasi.',
        'Pada langkah Review, periksa seluruh isi lalu simpan.',
      ],
      k: [
        ['Pilih Design Request', false, 'Sumber data otomatis dari hasil kerja Produksi.'],
        ['Link Customer Master', false, 'Menghubungkan penawaran ke data customer yang sudah ada.'],
        ['Customer', true, 'Nama customer pada dokumen penawaran.'],
        ['PIC', false, 'Nama penerima penawaran di sisi customer.'],
        ['Nama Proyek', true, 'Judul pekerjaan pada dokumen penawaran.'],
        ['Metode Pengiriman', true, 'Email atau Hardcopy.'],
        ['Tanggal Penawaran', true, 'Tanggal dokumen diterbitkan.'],
        ['Berlaku Sampai', true, 'Batas masa berlaku harga.'],
        ['Prioritas', false, 'High, Medium, atau Low.'],
        ['Mata Uang', false, 'Bawaan IDR.'],
        ['Catatan untuk Customer', false, 'Teks yang ikut tercetak pada dokumen penawaran.'],
        ['Cara Membuat Penawaran', true, 'Buat Penawaran atau Upload Penawaran.'],
        ['Dokumen Pendukung Penawaran', false, 'Opsional, maksimal 5 file, 10 MB per file. Format PDF, Word, Excel, JPG, atau PNG.'],
      ],
    },

    'quo-edit': {
      f: `Mengubah penawaran yang masih berstatus Draft atau Siap Dikirim.`,
      s: [
        'Wizard yang sama terbuka dengan data yang sudah terisi.',
        'Perbaiki item, harga, atau ketentuan yang perlu diubah.',
        'Simpan perubahan.',
      ],
      n: 'Penawaran yang sudah dikirim ke customer atau sudah dibuatkan Request PO tidak dapat diubah lagi.',
    },

    'quo-show': {
      f: `Detail satu penawaran: rincian item, harga, dan seluruh tombol tindak lanjut.`,
      s: [
        'Periksa rincian item, spesifikasi, subtotal, diskon, PPN, biaya tambahan, dan grand total.',
        'Unduh dokumen lewat tombol PDF atau Excel untuk dikirim ke customer.',
        'Tekan Tandai Dikirim ke Customer setelah dokumen benar-benar dikirim.',
        'Setelah customer merespons, tekan Won bila setuju atau Lost bila menolak.',
        'Bila customer setuju, lanjutkan dengan membuat Request PO.',
      ],
    },

    'request-po': {
      f: `Daftar Request PO milik Anda: penghubung antara penawaran yang dimenangkan dan PO resmi di Accurate.`,
      s: [
        'Cari berdasarkan nomor request, PO customer, PO Accurate, atau nama customer.',
        'Filter status untuk memisahkan draf dan yang sudah diajukan.',
        'Baris berstatus Draft menampilkan ikon pensil untuk melanjutkan pengisian.',
      ],
    },

    'rpo-create': {
      f: `Membuat Request PO, baik dari penawaran yang tersimpan di CRM maupun dari PO existing yang
        penawarannya dibuat di luar sistem.`,
      s: [
        'Pilih sumber: Penawaran CRM atau PO Existing / Non-CRM.',
        'Bila memilih Penawaran CRM, pilih penawaran dari daftar. Hanya penawaran yang belum punya Request PO yang tampil.',
        'Bila memilih PO Existing / Non-CRM, isi nama project, nomor penawaran eksternal (opsional), total nilai PO, dan sales penanggung jawab. Sistem membuat catatan penawaran eksternal otomatis agar alur Accurate, Project, dan Invoice tetap terhubung.',
        'Lengkapi data customer, nomor PO customer, dan unggah bukti PO.',
        'Isi data pengiriman dan billing pada bagian Data untuk Input Accurate.',
        'Atur Checklist Kelengkapan: centang yang sudah beres, hapus item yang tidak diperlukan lewat ikon tempat sampah, atau tambah item sendiri.',
        'Tekan Simpan & Ajukan Request PO bila data lengkap, atau Simpan Draf (Pending) bila belum.',
      ],
      k: [
        ['Sumber Request PO', true, 'Penawaran CRM atau PO Existing / Non-CRM.'],
        ['Pilih Penawaran', true, 'Wajib bila sumbernya Penawaran CRM.'],
        ['Nama Project / Order', true, 'Wajib bila sumbernya PO Existing / Non-CRM.'],
        ['No Penawaran Eksternal', false, 'Nomor penawaran versi luar CRM, sebagai referensi.'],
        ['Total Nilai PO', true, 'Wajib bila sumbernya PO Existing. Isi total akhir termasuk pajak bila berlaku.'],
        ['Sales Penanggung Jawab', true, 'Wajib bila sumbernya PO Existing dan pengisi bukan Sales sendiri.'],
        ['Nomor Proyek', true, 'Nomor proyek internal, diisi manual.'],
        ['Nama Customer', true, 'Nama customer pada PO.'],
        ['Area / Lokasi Customer', false, 'Kota atau lokasi customer.'],
        ['Divisi Customer', false, 'Bagian customer yang memesan.'],
        ['Tanggal Request', true, 'Tanggal Request PO dibuat.'],
        ['No PO Customer', false, 'Nomor PO yang diterbitkan customer.'],
        ['Upload PO Customer / Lampiran', false, 'Maksimal 5 MB. Format PDF, JPG, PNG, DOC/DOCX, atau XLS/XLSX.'],
        ['Alamat Pengiriman / Lokasi Project', false, 'Alamat tujuan kirim barang.'],
        ['PIC Penerima / Project dan No HP PIC', false, 'Orang yang menerima barang di lokasi.'],
        ['Nama NPWP / Billing dan Nomor NPWP', false, 'Data pajak untuk penagihan.'],
        ['Termin Pembayaran', false, 'Contoh: DP 50%, pelunasan 50% sebelum kirim.'],
        ['Estimasi Tanggal Kirim', false, 'Perkiraan jadwal pengiriman.'],
        ['Catatan Internal', false, 'Pesan untuk proses input PO di Accurate.'],
      ],
      n: `Kolom bertanda bintang hanya wajib saat <b>diajukan</b>. Tombol <b>Simpan Draf (Pending)</b> menyimpan
        tanpa validasi kelengkapan; draf belum diteruskan ke Accurate, belum bisa diekspor PDF, dan belum bisa
        ditagihkan.`,
    },

    'rpo-draft-edit': {
      f: `Melanjutkan Request PO yang sebelumnya disimpan sebagai draf.`,
      s: [
        'Buka dari ikon pensil pada daftar Request PO, atau tombol Lanjutkan & Ajukan pada halaman detail.',
        'Form terbuka dengan data draf yang sudah terisi.',
        'Lengkapi kolom bertanda bintang.',
        'Tekan Ajukan Request PO, atau Simpan Draf (Pending) bila masih ada yang kurang.',
      ],
      n: 'Draf tidak membuat record baru saat diajukan — Request PO yang sama berubah statusnya menjadi Diajukan ke Accurate.',
    },

    'rpo-show': {
      f: `Detail Request PO: memantau posisi order dan mengelola checklist kelengkapannya.`,
      s: [
        'Periksa Data Order dan status terkini.',
        'Lengkapi Data Input Accurate bila masih ada yang kosong.',
        'Sesuaikan Checklist Kelengkapan lalu tekan Simpan Checklist.',
        'Panel Progress Checklist menunjukkan persentase kelengkapan.',
        'Unduh dokumen lewat Export PDF.',
      ],
    },

    'customers': {
      f: `Database customer yang menjadi tanggung jawab Anda.`,
      s: [
        'Klik satu customer untuk melihat detail pada panel samping.',
        'Periksa riwayat lead, penawaran, dan project customer tersebut.',
      ],
    },

    'customer-create': {
      f: `Mendaftarkan customer baru secara manual.`,
      s: [
        'Isi identitas perusahaan, kontak, alamat, dan PIC utama.',
        'Atur stage pipeline dan probability.',
        'Tekan Simpan.',
      ],
      k: [
        ['Nama Customer', true, 'Nama resmi perusahaan atau instansi.'],
        ['Kategori', false, 'Pendidikan, Universitas, Sekolah, Rumah Sakit, Laboratorium Swasta, Industri, Farmasi, Kesehatan, Pemerintah, BUMN, BUMD, Distributor, Kontraktor, atau Lainnya.'],
        ['Jenis Industri', false, 'Keterangan bidang usaha.'],
        ['Website', false, 'Alamat situs resmi.'],
        ['Email dan No. Telepon', false, 'Kontak utama perusahaan.'],
        ['Alamat', false, 'Alamat lengkap.'],
        ['Kota', false, 'Tersedia saran seluruh kota dan kabupaten di Indonesia; nama lain tetap bisa diketik manual.'],
        ['Area / Lokasi Customer', false, 'Penanda lokasi lebih rinci, contoh Gedung A, Jakarta.'],
        ['Divisi Customer', false, 'Bagian yang menjadi mitra, contoh Laboratorium atau Engineering.'],
        ['Nama PIC dan Jabatan PIC', false, 'PIC utama customer.'],
        ['Probability (%)', false, 'Perkiraan peluang closing, 0 sampai 100.'],
        ['Sales Owner', true, 'Sales pemilik customer. Terisi otomatis bila Anda seorang Sales.'],
        ['Mulai Menjadi Partner', false, 'Tanggal customer mulai bekerja sama.'],
        ['Catatan', false, 'Informasi penting lain tentang customer.'],
      ],
    },

    'customer-show': {
      f: `Profil lengkap satu customer beserta seluruh riwayat transaksinya.`,
      s: [
        'Periksa identitas, kontak, dan PIC.',
        'Telusuri riwayat lead, aktivitas, penawaran, dan project.',
        'Tekan Edit untuk memperbarui data.',
      ],
    },

    'projects': {
      f: `Daftar project yang Anda kelola sebagai project manager.`,
      s: [
        'Filter berdasarkan status project.',
        'Klik satu project untuk membuka detail dan termin pembayarannya.',
      ],
    },

    'project-create': {
      f: `Membuat project dari penawaran yang sudah dimenangkan.`,
      s: [
        'Bagian 1: pilih penawaran berstatus Won / Customer Setuju. Data customer, PIC, sales, dan nilai penawaran termuat otomatis.',
        'Bagian 2 Informasi Project: nama project, kode, deskripsi, kategori, jenis, prioritas, dan status awal.',
        'Bagian 3 Informasi Pelaksanaan: tanggal mulai, target selesai, metode pengerjaan, lokasi, dan ruang lingkup pekerjaan.',
        'Bagian 4 Informasi Nilai & Pembayaran: nilai project, PPN, dan total terisi otomatis dari penawaran. Atur skema serta termin pembayaran.',
        'Bagian 5 Tim Project: tentukan Project Manager, tim internal, dan vendor eksternal.',
        'Bagian 6 dan 7: lampirkan dokumen pendukung dan catatan tambahan.',
        'Tekan Simpan Project.',
      ],
      k: [
        ['Penawaran', true, 'Penawaran Won yang belum memiliki project.'],
        ['Nama Project', true, 'Judul project.'],
        ['Kategori Project', true, 'Klasifikasi pekerjaan.'],
        ['Status Awal', true, 'Planning, Berjalan, Finishing, Selesai, atau Dibatalkan.'],
        ['Tanggal Mulai dan Tanggal Selesai (Target)', true, 'Rentang waktu pengerjaan.'],
        ['Metode Pengerjaan', true, 'Turnkey, Supply Only, atau Instalasi.'],
        ['Lokasi Project', true, 'Alamat pelaksanaan pekerjaan.'],
        ['Skema Pembayaran', true, 'Contoh: DP 30% - Progress 40% - Pelunasan 30%.'],
        ['Project Manager', true, 'Penanggung jawab project.'],
      ],
      n: 'Nilai project, pajak, dan total ditarik dari penawaran terkait dan tidak dapat diubah manual.',
    },

    'project-show': {
      f: `Detail project: informasi pelaksanaan, termin pembayaran, tim, dan tautan ke workspace operasional.`,
      s: [
        'Periksa progres, tanggal, dan nilai project.',
        'Telusuri termin pembayaran beserta statusnya.',
        'Buka Workspace Project untuk melihat pekerjaan Produksi, QC, dan Delivery.',
      ],
    },

    'pra-leads': {
      f: `Sejak role Sales Admin digabungkan, Sales juga dapat mencatat dan mendistribusikan Pra Lead.`,
      s: [
        'Cara pemakaiannya sama persis dengan bab Administrator: isi form, pilih Sales PIC, lalu Simpan Draft, Simpan, atau Kirim ke Sales.',
      ],
    },

    'invoices': {
      f: `Menerbitkan dan memantau invoice atas Request PO yang pengirimannya sudah selesai.`,
      s: [
        'Periksa panel Siap Ditagihkan untuk melihat Request PO yang boleh diinvoice.',
        'Terbitkan invoice, lalu catat pembayaran tiap termin.',
      ],
    },

    'project-monitoring': {
      f: `Monitoring seluruh project dalam format spreadsheet, termasuk kolom administrasi.`,
      s: [
        'Geser tabel ke kanan untuk melihat termin invoice dan checklist proses.',
        'Isi kolom Comment, KP, dan Bukti Potong PPh lalu tekan Simpan pada baris tersebut.',
      ],
    },

    'users': {
      f: `Mengelola akun pengguna. Sales dapat menambah, mengubah, menonaktifkan, dan menghapus akun
        non-Administrator.`,
      s: [
        'Gunakan ikon pensil untuk mengubah, toggle untuk mengaktifkan/menonaktifkan, dan ikon tempat sampah untuk menghapus.',
        'Tekan Tambah User untuk membuat akun baru.',
      ],
      n: `Akun Administrator tidak tampil dan tidak dapat diubah oleh Sales, dan Sales tidak dapat membuat akun
        ber-role Administrator.`,
    },

    'calendar': {
      f: `Kalender aktivitas dan tenggat pekerjaan Anda.`,
      s: [
        'Klik tanggal untuk melihat agenda hari tersebut.',
        'Tombol Tambah Activity tersedia langsung dari halaman ini.',
      ],
    },

    'documents': {
      f: `Dokumen milik customer dan project yang menjadi tanggung jawab Anda.`,
      s: [
        'Cari, preview, atau unduh dokumen.',
        'Unggah dokumen baru dengan mengisi nama, kategori, dan file.',
      ],
      n: 'Sales hanya melihat dokumen yang terkait dengan datanya sendiri.',
    },

    'reports': {
      f: `Laporan performa penjualan pribadi.`,
      s: [
        'Pilih periode laporan.',
        'Periksa ringkasan pipeline, penawaran, dan project.',
      ],
    },
  },
};
