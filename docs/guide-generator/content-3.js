// Isi panduan: SPV Sales, Drafter, Produksi, Quality Control, Delivery, Administration.

module.exports = {
  sales_spv: {
    intro: `SPV Sales mengawasi pekerjaan tim sales. Role ini dapat membaca lead, design request, customer,
      project, dan aktivitas seluruh sales, meninjau isi dan nilai setiap penawaran, serta mengatur pemerataan
      beban kerja lewat menu Assignment. SPV tidak membuat lead, penawaran, maupun Request PO.`,

    'dashboard': {
      f: `Ringkasan performa tim sales: penawaran yang berjalan, nilai pipeline, dan pekerjaan yang perlu
        ditinjau.`,
      s: [
        'Periksa kartu ringkasan di baris atas.',
        'Panel penawaran menampilkan dokumen yang perlu ditinjau beserta nilainya.',
      ],
    },

    'assignment': {
      f: `Memantau pemerataan beban kerja tim sales dan memindahkan kepemilikan lead bila diperlukan.
        Menu ini hanya terbuka untuk Administrator dan SPV Sales karena isinya memuat performa seluruh sales
        dan dapat memindahkan lead antar orang.`,
      s: [
        'Tabel Workload Distribution membandingkan Request Masuk, Leads Aktif, Design Request Aktif, Penawaran Aktif, dan Project Aktif tiap sales.',
        'Lead Acceptance Monitoring memperlihatkan berapa prospek yang dikirim dan berapa yang benar-benar diterima tiap sales.',
        'Top Sales Performance mengurutkan sales berdasarkan win rate.',
        'Klik nama sales untuk menampilkan profil dan ringkasannya di panel kanan.',
        'Untuk memindahkan lead: pada panel Reassign Lead / Ownership pilih Lead, periksa kolom Dari, pilih sales tujuan pada kolom Ke, lalu tekan Reassign.',
        'Tombol Export Excel mengunduh tabel assignment untuk dilaporkan ke manajemen.',
      ],
      n: `Memindahkan lead sekaligus memindahkan kepemilikan Customer beserta seluruh riwayatnya. Koordinasikan
        dengan sales lama sebelum melakukannya agar komunikasi dengan customer tidak terputus. Setiap pemindahan
        tercatat pada log aktivitas.`,
    },

    'quotation-approvals': {
      f: `Halaman utama SPV: meninjau seluruh penawaran yang dibuat tim sales.`,
      s: [
        'Gunakan filter status untuk memisahkan penawaran draft, siap dikirim, dan yang sudah dikirim ke customer.',
        'Periksa kolom nilai untuk melihat besaran penawaran.',
        'Klik satu penawaran untuk membuka rinciannya.',
      ],
    },

    'quotation-approval-show': {
      f: `Rincian satu penawaran dari sudut pandang pengawasan: item, spesifikasi, harga, diskon, pajak, dan
        biaya tambahan.`,
      s: [
        'Periksa daftar item beserta spesifikasi dan harga satuannya.',
        'Perhatikan diskon, PPN, biaya tambahan, dan grand total.',
        'Bandingkan dengan target margin untuk menilai kewajaran harga.',
        'Sampaikan koreksi kepada sales pembuat penawaran bila ada yang perlu diperbaiki.',
      ],
    },

    'request-masuk': {
      f: `Memantau prospek yang dikirim Administrator ke tim sales dan seberapa cepat direspons.`,
      s: [
        'Periksa kartu Menunggu Respon untuk melihat prospek yang belum ditindaklanjuti.',
        'Klik satu baris untuk membaca detail request.',
      ],
      n: 'Tombol Terima / Tolak hanya aktif pada akun Sales pemilik request.',
    },

    'leads': {
      f: `Daftar lead seluruh sales beserta tahapan dan nilainya.`,
      s: [
        'Gunakan pencarian dan filter untuk memantau lead tertentu.',
        'Klik satu lead untuk melihat ringkasannya.',
      ],
    },

    'design-requests': {
      f: `Memantau permintaan desain seluruh sales dan progres pengerjaannya oleh Drafter/Produksi.`,
      s: [
        'Periksa kartu Menunggu Produksi dan Sedang Dikerjakan.',
        'Filter berdasarkan PIC Produksi untuk melihat beban tiap drafter.',
      ],
    },

    'customers': {
      f: `Database customer seluruh sales.`,
      s: [
        'Klik satu customer untuk melihat profil dan riwayat transaksinya.',
      ],
    },

    'projects': {
      f: `Daftar project berjalan dari seluruh tim.`,
      s: [
        'Filter berdasarkan status project.',
        'Klik satu project untuk melihat detail dan progresnya.',
      ],
    },

    'activities': {
      f: `Memantau aktivitas follow up seluruh sales.`,
      s: [
        'Gunakan tab periode untuk melihat aktivitas harian, mingguan, atau bulanan.',
        'Perhatikan kartu Overdue untuk aktivitas yang terlambat.',
      ],
      n: 'Tombol Tambah Activity tidak muncul karena aktivitas dicatat oleh sales yang mengerjakannya.',
    },

    'reports': {
      f: `Laporan performa tim sales.`,
      s: [
        'Pilih periode laporan lalu periksa ringkasannya.',
      ],
    },
  },

  drafter: {
    intro: `Drafter menerima Design Request dari Sales dan bertugas mengunggah serta merevisi drawing dan
      dokumen. Spesifikasi teknis rinci dan HPP diisi oleh tim Produksi, bukan Drafter.`,

    'dashboard': {
      f: `Ringkasan pekerjaan Drafter: request yang ditugaskan, yang sedang dikerjakan, dan yang sudah selesai.`,
      s: [
        'Periksa kartu ringkasan di baris atas.',
        'Badge angka pada menu Design Request menunjukkan jumlah request baru yang menunggu dikerjakan.',
      ],
    },

    'design-requests': {
      f: `Daftar Design Request yang ditugaskan kepada Anda.`,
      s: [
        'Filter berdasarkan status dan urgensi.',
        'Dahulukan request bertanda Urgent dan yang deadline-nya paling dekat.',
        'Klik satu request untuk mulai mengerjakannya.',
      ],
    },

    'dr-show': {
      f: `Halaman kerja utama Drafter: membaca permintaan Sales lalu mengunggah drawing dan dokumen hasil kerja.`,
      s: [
        'Panel Request dari Sales memuat kebutuhan customer, catatan sales, dan lampiran sketsa.',
        'Panel Informasi Umum memuat sales pemilik, tanggal request, deadline, urgensi, dan PIC customer.',
        'Gunakan form Upload / Revisi Drawing & Dokumen di bagian bawah untuk mengunggah hasil kerja.',
        'Pilih Jenis dokumen, isi Nama Dokumen, pilih file, lalu tekan Upload.',
        'Bila dokumen adalah revisi, pilih dokumen lama pada kolom Revisi dari dan isi Catatan Revisi.',
        'Tulis Catatan Teknis untuk sales pada panel kanan bila ada hal yang perlu disampaikan.',
        'Perbarui progres pengerjaan agar Sales dapat memantau.',
      ],
      k: [
        ['Jenis', true, 'Kategori dokumen, contoh Gambar sesuai Request Sales, gambar fabrikasi, atau dokumen pendukung.'],
        ['Nama Dokumen', true, 'Nama yang mudah dikenali.'],
        ['Revisi dari', false, 'Pilih "Dokumen baru" untuk unggahan pertama, atau pilih dokumen lama bila ini revisinya.'],
        ['File', true, 'Berkas yang diunggah.'],
        ['Catatan Revisi', false, 'Penjelasan perubahan pada revisi ini.'],
      ],
      n: `Panel Kelengkapan Feedback memperlihatkan apa saja yang masih kurang sebelum request dapat dinyatakan
        selesai. Tab Riwayat, Revisi, dan Dokumen di bagian atas memuat jejak pengerjaan.`,
    },

    'dr-costing': {
      f: `Tampilan Design Request yang sudah masuk tahap costing — spesifikasi dan HPP sedang diisi Produksi.`,
      s: [
        'Periksa item hasil beserta spesifikasi yang sudah tersusun.',
        'Pastikan drawing yang Anda unggah sesuai dengan item yang dihitung Produksi.',
        'Unggah revisi bila ada penyesuaian gambar.',
      ],
    },

    'projects': {
      f: `Daftar project yang melibatkan Anda sebagai bagian dari tim internal.`,
      s: [
        'Klik satu project untuk membuka workspace-nya.',
      ],
    },

    'workspace': {
      f: `Ruang kerja project lintas divisi. Drafter memakainya untuk mengunggah revisi desain saat project
        sudah berjalan.`,
      s: [
        'Tab Informasi Project memuat identitas dan progres project.',
        'Tab Production, QC & Delivery memperlihatkan posisi pekerjaan tiap divisi.',
        'Tab Design Revision dipakai untuk mengunggah revisi desain beserta statusnya.',
      ],
    },

    'tasks': {
      f: `Daftar pekerjaan yang harus diselesaikan, diurutkan berdasarkan tenggat.`,
      s: [
        'Kerjakan tugas dari yang paling dekat tenggatnya.',
        'Klik satu tugas untuk membuka data terkait.',
      ],
    },

    'documents': {
      f: `Seluruh dokumen yang Anda unggah dan dokumen project yang menjadi tanggung jawab Anda.`,
      s: [
        'Cari, preview, atau unduh dokumen.',
      ],
      n: 'Drafter hanya melihat dokumen pada Design Request dan project yang ditugaskan kepadanya.',
    },

    'calendar': {
      f: `Kalender tenggat Design Request dan pekerjaan project.`,
      s: [
        'Klik tanggal untuk melihat tenggat pada hari tersebut.',
      ],
    },

    'reports': {
      f: `Laporan beban dan hasil kerja desain.`,
      s: [
        'Pilih periode lalu periksa ringkasannya.',
      ],
    },
  },

  production: {
    intro: `Produksi mengisi spesifikasi teknis rinci, item hasil, dan HPP pada Design Request, mengelola Master
      Item, serta memperbarui progres produksi pada workspace project.`,

    'dashboard': {
      f: `Halaman awal Produksi berupa daftar project yang perlu dikerjakan.`,
      s: [
        'Periksa project yang menunggu produksi dan yang sedang berjalan.',
        'Klik satu project untuk membuka workspace-nya.',
      ],
    },

    'design-requests': {
      f: `Design Request yang drawing-nya sudah diunggah Drafter dan menunggu pengisian spesifikasi serta HPP.`,
      s: [
        'Badge angka pada menu menunjukkan jumlah request yang menunggu Produksi.',
        'Dahulukan request bertanda Urgent.',
        'Klik satu request untuk mulai mengisi.',
      ],
    },

    'dr-show': {
      f: `Halaman kerja utama Produksi: menyusun item hasil, spesifikasi rinci, dan menghitung HPP yang akan
        dipakai Sales untuk menyusun penawaran.`,
      s: [
        'Isi Estimasi Costing Awal: biaya Material, Produksi, dan Instalasi. Total Estimasi terhitung otomatis.',
        'Pada panel Item Hasil untuk Penawaran, tekan Tambah Item untuk setiap item yang akan masuk penawaran.',
        'Isi Kategori, Nama Item, dan Varian / Model.',
        'Susun Spesifikasi untuk Penawaran per bagian. Tekan Tambah Bagian untuk kelompok baru, Tambah Detail untuk baris detail, dan Tambah Sub-detail untuk rincian di bawahnya.',
        'Tekan Tambah Qty / Harga untuk mencantumkan jumlah dan harga pada baris spesifikasi.',
        'Unggah Gambar Utama Penawaran untuk item tersebut.',
        'Isi Qty, Unit, dan HPP per Item.',
        'Centang Item opsional bila item tidak wajib diambil customer.',
        'Tulis Catatan Teknis untuk Sales bila perlu.',
        'Submit final ke Sales setelah seluruh item, dokumen, dan HPP lengkap.',
      ],
      k: [
        ['Material / Produksi / Instalasi', false, 'Komponen biaya yang membentuk Total Estimasi.'],
        ['Kategori', false, 'Pengelompokan item, contoh Furniture.'],
        ['Nama Item', true, 'Nama item sebagaimana akan tampil di penawaran.'],
        ['Varian / Model', false, 'Kode atau tipe item.'],
        ['Spesifikasi untuk Penawaran', false, 'Susunan bagian dan detail yang akan tercetak pada dokumen penawaran.'],
        ['Gambar Utama Penawaran', false, 'Foto atau render item. Format JPG, PNG, atau WebP, maksimal 10 MB.'],
        ['Qty dan Unit', true, 'Jumlah dan satuan item.'],
        ['HPP per Item', true, 'Harga pokok per unit. Dipakai Sales untuk menghitung margin.'],
        ['Item opsional', false, 'Menandai item sebagai pilihan tambahan, bukan bagian wajib penawaran.'],
      ],
      n: `Panel Kelengkapan Feedback menunjukkan syarat yang belum terpenuhi. Sales baru dapat menyusun penawaran
        setelah Produksi menyelesaikan pengisian ini.`,
    },

    'item-masters': {
      f: `Katalog item standar beserta HPP dan margin bawaan. Dikelola oleh Produksi agar harga di seluruh
        penawaran konsisten.`,
      s: [
        'Cari item berdasarkan kode, nama, atau kategori.',
        'Tekan Tambah Master Item untuk item baru.',
        'Isi kode, kategori, nama, varian, satuan, HPP bawaan, margin bawaan, dan spesifikasi standar.',
        'Nonaktifkan item yang tidak dijual lagi daripada menghapusnya.',
      ],
      n: 'Sales tidak memiliki akses ke halaman ini karena memuat data teknis dan HPP.',
    },

    'workspace': {
      f: `Memperbarui progres produksi satu project dan mengunggah laporan pekerjaan.`,
      s: [
        'Buka tab Production, QC & Delivery.',
        'Pada panel Progress Desain & Produksi, geser atau isi persentase progres terakhir.',
        'Tulis catatan pekerjaan, contoh tahap yang sedang berjalan.',
        'Unggah laporan produksi beserta foto pekerjaan.',
        'Tandai Laporan lengkap bila produksi sudah selesai seluruhnya.',
        'Simpan.',
      ],
      n: 'QC baru dapat mulai bekerja setelah status produksi berubah menjadi Produksi Selesai.',
    },

    'documents': {
      f: `Dokumen project: drawing dari Drafter, laporan produksi, dan dokumen pendukung lain.`,
      s: [
        'Cari, preview, atau unduh dokumen.',
        'Unggah laporan atau foto pekerjaan bila diperlukan.',
      ],
    },

    'calendar': {
      f: `Kalender jadwal dan tenggat pekerjaan produksi.`,
      s: [
        'Klik tanggal untuk melihat pekerjaan pada hari tersebut.',
      ],
    },

    'reports': {
      f: `Laporan hasil dan beban kerja produksi.`,
      s: [
        'Pilih periode lalu periksa ringkasannya.',
      ],
    },
  },

  qc: {
    intro: `Quality Control memeriksa hasil produksi terhadap spesifikasi penawaran sebelum barang dikirim ke
      customer. QC bekerja pada workspace project, tepatnya di tab Production, QC & Delivery.`,

    'dashboard': {
      f: `Halaman awal QC berupa daftar project yang perlu diperiksa.`,
      s: [
        'Periksa project yang produksinya sudah selesai dan menunggu QC.',
        'Klik satu project untuk membuka workspace-nya.',
      ],
    },

    'workspace-spec': {
      f: `Tab Spesifikasi Penawaran — acuan resmi pemeriksaan QC. Isinya adalah item dan spesifikasi yang
        dijanjikan kepada customer.`,
      s: [
        'Baca setiap item beserta jumlah dan spesifikasinya.',
        'Gunakan daftar ini sebagai patokan saat memeriksa barang fisik.',
      ],
      n: 'Checklist QC pada tab berikutnya dibentuk otomatis dari daftar spesifikasi ini.',
    },

    'workspace': {
      f: `Melakukan pemeriksaan QC dan mencatat hasilnya.`,
      s: [
        'Buka tab Production, QC & Delivery.',
        'Panel Quality Control memuat checklist otomatis per item penawaran: jumlah, spesifikasi, kondisi fisik, dan pengujian fungsi.',
        'Centang setiap poin yang sudah diperiksa dan dinyatakan sesuai.',
        'Tulis Catatan QC berisi temuan atau keterangan pemeriksaan.',
        'Unggah Lampiran QC berupa PDF bila ada berita acara atau foto pemeriksaan.',
        'Centang Semua pemeriksaan selesai dan lolos QC bila seluruh item dinyatakan lolos.',
        'Tekan Simpan QC.',
      ],
      n: `Penanda "selesai dan lolos QC" tidak dapat disimpan bila masih ada poin checklist yang belum dicentang.
        Delivery baru dapat menjadwalkan pengiriman setelah QC dinyatakan selesai.`,
    },

    'calendar': {
      f: `Kalender jadwal pemeriksaan dan tenggat project.`,
      s: [
        'Klik tanggal untuk melihat agenda pada hari tersebut.',
      ],
    },

    'profile': {
      f: `Mengubah data diri dan mengganti password akun sendiri.`,
      s: [
        'Perbarui nama, email, jabatan, atau nomor telepon lalu tekan Simpan.',
        'Untuk mengganti password: isi password baru dan konfirmasinya, lalu simpan.',
      ],
    },
  },

  delivery: {
    intro: `Delivery mengatur pengiriman barang ke lokasi customer, mencatat bukti terima, dan menerbitkan
      Delivery Order. Penyelesaian tahap ini adalah syarat sebelum invoice dapat diterbitkan.`,

    'dashboard': {
      f: `Halaman awal Delivery berupa daftar project yang siap atau sedang dikirim.`,
      s: [
        'Periksa project yang QC-nya sudah selesai dan menunggu pengiriman.',
        'Klik satu project untuk membuka workspace-nya.',
      ],
    },

    'workspace': {
      f: `Mengatur jadwal kirim, mencatat penerimaan customer, dan menerbitkan Delivery Order.`,
      s: [
        'Buka tab Production, QC & Delivery lalu perhatikan panel Delivery di sebelah kanan.',
        'Tentukan jadwal pengiriman.',
        'Perbarui status pengiriman mengikuti kondisi sebenarnya: Atur Jadwal, Terjadwal, Dalam Pengiriman, Terkirim, Diterima Customer, lalu Selesai.',
        'Unggah foto barang keluar dan tandai DO/BA keluar.',
        'Setelah barang diterima, isi nama penerima dan tanggal penerimaan, lalu unggah POD (bukti terima).',
        'Unggah foto DO/BA kembali dan tandai selesai.',
        'Gunakan panel Delivery Order untuk membuat dan mengunduh surat jalan.',
        'Simpan.',
      ],
      k: [
        ['Jadwal', false, 'Tanggal dan jam rencana pengiriman.'],
        ['Status pengiriman', true, 'Posisi pengiriman saat ini.'],
        ['Foto barang keluar', false, 'Dokumentasi saat barang meninggalkan gudang.'],
        ['Nama penerima', false, 'Orang di sisi customer yang menerima barang.'],
        ['Tanggal penerimaan', false, 'Kapan barang diterima customer.'],
        ['POD (Proof of Delivery)', false, 'Bukti terima bertanda tangan customer.'],
        ['Catatan pengiriman', false, 'Keterangan kondisi barang atau kendala di lapangan.'],
      ],
      n: `Invoice baru dapat diterbitkan setelah status delivery mencapai <b>Selesai</b> dan penerimaan customer
        dikonfirmasi.`,
    },

    'calendar': {
      f: `Kalender jadwal pengiriman.`,
      s: [
        'Klik tanggal untuk melihat jadwal kirim pada hari tersebut.',
      ],
    },

    'profile': {
      f: `Mengubah data diri dan mengganti password akun sendiri.`,
      s: [
        'Perbarui data lalu tekan Simpan.',
        'Untuk mengganti password: isi password baru dan konfirmasinya, lalu simpan.',
      ],
    },
  },

  administration: {
    intro: `Administration menangani sisi administratif project: konfirmasi pembayaran, bukti potong PPh, dan
      catatan administrasi pada monitoring project.`,

    'dashboard': {
      f: `Halaman awal Administration adalah Project Monitoring — tampilan menyerupai spreadsheet berisi seluruh
        project beserta status operasional dan tagihannya.`,
      s: [
        'Periksa kartu ringkasan: total project, project aktif, produksi selesai, QC selesai, delivery selesai, dan piutang.',
        'Geser tabel ke kanan untuk melihat kolom termin invoice dan checklist proses.',
        'Isi kolom Comment, KP (konfirmasi pembayaran), dan Bukti Potong PPh pada baris yang bersangkutan.',
        'Tekan Simpan pada baris tersebut.',
      ],
      k: [
        ['Comment', false, 'Catatan administrasi terkait project, contoh status pembayaran atau kendala dokumen.'],
        ['KP (Konfirmasi Pembayaran)', false, 'Centang bila pembayaran dari customer sudah dikonfirmasi masuk.'],
        ['Bukti Potong PPh', false, 'Centang bila bukti potong pajak dari customer sudah diterima.'],
      ],
      n: 'Kolom administrasi ini juga dapat diisi oleh Administrator dan Sales.',
    },

    'projects': {
      f: `Daftar project untuk penelusuran administratif.`,
      s: [
        'Klik satu project untuk membuka workspace dan melihat posisi pekerjaannya.',
      ],
    },

    'workspace': {
      f: `Melihat posisi pekerjaan produksi, QC, dan delivery satu project sebagai konteks penagihan.`,
      s: [
        'Buka tab Production, QC & Delivery untuk melihat progres tiap divisi.',
        'Periksa status delivery untuk memastikan apakah project sudah boleh ditagihkan.',
      ],
      n: 'Administration dapat mengunggah revisi desain, namun tidak mengubah data produksi, QC, atau delivery.',
    },

    'calendar': {
      f: `Kalender jadwal dan tenggat project.`,
      s: [
        'Klik tanggal untuk melihat agenda pada hari tersebut.',
      ],
    },

    'reports': {
      f: `Laporan ringkas project dan tagihan.`,
      s: [
        'Pilih periode lalu periksa ringkasannya.',
      ],
    },
  },
};
