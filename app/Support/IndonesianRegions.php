<?php

namespace App\Support;

/**
 * Daftar seluruh kota dan kabupaten di Indonesia (38 provinsi, 514 kabupaten/kota).
 *
 * Dipakai sebagai saran (datalist) pada input Kota di Lead dan Customer. Input
 * tetap berupa teks bebas sehingga penulisan lain atau wilayah baru tetap bisa
 * dimasukkan manual tanpa menunggu daftar ini diperbarui.
 */
class IndonesianRegions
{
    /**
     * Provinsi => ['kota' => [...], 'kabupaten' => [...]]
     *
     * @return array<string, array{kota: array<int, string>, kabupaten: array<int, string>}>
     */
    public static function provinces(): array
    {
        return [
            'Aceh' => [
                'kota' => ['Banda Aceh', 'Langsa', 'Lhokseumawe', 'Sabang', 'Subulussalam'],
                'kabupaten' => ['Aceh Barat', 'Aceh Barat Daya', 'Aceh Besar', 'Aceh Jaya', 'Aceh Selatan', 'Aceh Singkil', 'Aceh Tamiang', 'Aceh Tengah', 'Aceh Tenggara', 'Aceh Timur', 'Aceh Utara', 'Bener Meriah', 'Bireuen', 'Gayo Lues', 'Nagan Raya', 'Pidie', 'Pidie Jaya', 'Simeulue'],
            ],
            'Sumatera Utara' => [
                'kota' => ['Binjai', 'Gunungsitoli', 'Medan', 'Padangsidimpuan', 'Pematangsiantar', 'Sibolga', 'Tanjungbalai', 'Tebing Tinggi'],
                'kabupaten' => ['Asahan', 'Batu Bara', 'Dairi', 'Deli Serdang', 'Humbang Hasundutan', 'Karo', 'Labuhanbatu', 'Labuhanbatu Selatan', 'Labuhanbatu Utara', 'Langkat', 'Mandailing Natal', 'Nias', 'Nias Barat', 'Nias Selatan', 'Nias Utara', 'Padang Lawas', 'Padang Lawas Utara', 'Pakpak Bharat', 'Samosir', 'Serdang Bedagai', 'Simalungun', 'Tapanuli Selatan', 'Tapanuli Tengah', 'Tapanuli Utara', 'Toba'],
            ],
            'Sumatera Barat' => [
                'kota' => ['Bukittinggi', 'Padang', 'Padang Panjang', 'Pariaman', 'Payakumbuh', 'Sawahlunto', 'Solok'],
                'kabupaten' => ['Agam', 'Dharmasraya', 'Kepulauan Mentawai', 'Lima Puluh Kota', 'Padang Pariaman', 'Pasaman', 'Pasaman Barat', 'Pesisir Selatan', 'Sijunjung', 'Solok', 'Solok Selatan', 'Tanah Datar'],
            ],
            'Riau' => [
                'kota' => ['Dumai', 'Pekanbaru'],
                'kabupaten' => ['Bengkalis', 'Indragiri Hilir', 'Indragiri Hulu', 'Kampar', 'Kepulauan Meranti', 'Kuantan Singingi', 'Pelalawan', 'Rokan Hilir', 'Rokan Hulu', 'Siak'],
            ],
            'Kepulauan Riau' => [
                'kota' => ['Batam', 'Tanjungpinang'],
                'kabupaten' => ['Bintan', 'Karimun', 'Kepulauan Anambas', 'Lingga', 'Natuna'],
            ],
            'Jambi' => [
                'kota' => ['Jambi', 'Sungai Penuh'],
                'kabupaten' => ['Batanghari', 'Bungo', 'Kerinci', 'Merangin', 'Muaro Jambi', 'Sarolangun', 'Tanjung Jabung Barat', 'Tanjung Jabung Timur', 'Tebo'],
            ],
            'Sumatera Selatan' => [
                'kota' => ['Lubuklinggau', 'Pagar Alam', 'Palembang', 'Prabumulih'],
                'kabupaten' => ['Banyuasin', 'Empat Lawang', 'Lahat', 'Muara Enim', 'Musi Banyuasin', 'Musi Rawas', 'Musi Rawas Utara', 'Ogan Ilir', 'Ogan Komering Ilir', 'Ogan Komering Ulu', 'Ogan Komering Ulu Selatan', 'Ogan Komering Ulu Timur', 'Penukal Abab Lematang Ilir'],
            ],
            'Kepulauan Bangka Belitung' => [
                'kota' => ['Pangkalpinang'],
                'kabupaten' => ['Bangka', 'Bangka Barat', 'Bangka Selatan', 'Bangka Tengah', 'Belitung', 'Belitung Timur'],
            ],
            'Bengkulu' => [
                'kota' => ['Bengkulu'],
                'kabupaten' => ['Bengkulu Selatan', 'Bengkulu Tengah', 'Bengkulu Utara', 'Kaur', 'Kepahiang', 'Lebong', 'Mukomuko', 'Rejang Lebong', 'Seluma'],
            ],
            'Lampung' => [
                'kota' => ['Bandar Lampung', 'Metro'],
                'kabupaten' => ['Lampung Barat', 'Lampung Selatan', 'Lampung Tengah', 'Lampung Timur', 'Lampung Utara', 'Mesuji', 'Pesawaran', 'Pesisir Barat', 'Pringsewu', 'Tanggamus', 'Tulang Bawang', 'Tulang Bawang Barat', 'Way Kanan'],
            ],
            'DKI Jakarta' => [
                'kota' => ['Jakarta Barat', 'Jakarta Pusat', 'Jakarta Selatan', 'Jakarta Timur', 'Jakarta Utara'],
                'kabupaten' => ['Kepulauan Seribu'],
            ],
            'Jawa Barat' => [
                'kota' => ['Bandung', 'Banjar', 'Bekasi', 'Bogor', 'Cimahi', 'Cirebon', 'Depok', 'Sukabumi', 'Tasikmalaya'],
                'kabupaten' => ['Bandung', 'Bandung Barat', 'Bekasi', 'Bogor', 'Ciamis', 'Cianjur', 'Cirebon', 'Garut', 'Indramayu', 'Karawang', 'Kuningan', 'Majalengka', 'Pangandaran', 'Purwakarta', 'Subang', 'Sukabumi', 'Sumedang', 'Tasikmalaya'],
            ],
            'Banten' => [
                'kota' => ['Cilegon', 'Serang', 'Tangerang', 'Tangerang Selatan'],
                'kabupaten' => ['Lebak', 'Pandeglang', 'Serang', 'Tangerang'],
            ],
            'Jawa Tengah' => [
                'kota' => ['Magelang', 'Pekalongan', 'Salatiga', 'Semarang', 'Surakarta', 'Tegal'],
                'kabupaten' => ['Banjarnegara', 'Banyumas', 'Batang', 'Blora', 'Boyolali', 'Brebes', 'Cilacap', 'Demak', 'Grobogan', 'Jepara', 'Karanganyar', 'Kebumen', 'Kendal', 'Klaten', 'Kudus', 'Magelang', 'Pati', 'Pekalongan', 'Pemalang', 'Purbalingga', 'Purworejo', 'Rembang', 'Semarang', 'Sragen', 'Sukoharjo', 'Tegal', 'Temanggung', 'Wonogiri', 'Wonosobo'],
            ],
            'DI Yogyakarta' => [
                'kota' => ['Yogyakarta'],
                'kabupaten' => ['Bantul', 'Gunungkidul', 'Kulon Progo', 'Sleman'],
            ],
            'Jawa Timur' => [
                'kota' => ['Batu', 'Blitar', 'Kediri', 'Madiun', 'Malang', 'Mojokerto', 'Pasuruan', 'Probolinggo', 'Surabaya'],
                'kabupaten' => ['Bangkalan', 'Banyuwangi', 'Blitar', 'Bojonegoro', 'Bondowoso', 'Gresik', 'Jember', 'Jombang', 'Kediri', 'Lamongan', 'Lumajang', 'Madiun', 'Magetan', 'Malang', 'Mojokerto', 'Nganjuk', 'Ngawi', 'Pacitan', 'Pamekasan', 'Pasuruan', 'Ponorogo', 'Probolinggo', 'Sampang', 'Sidoarjo', 'Situbondo', 'Sumenep', 'Trenggalek', 'Tuban', 'Tulungagung'],
            ],
            'Bali' => [
                'kota' => ['Denpasar'],
                'kabupaten' => ['Badung', 'Bangli', 'Buleleng', 'Gianyar', 'Jembrana', 'Karangasem', 'Klungkung', 'Tabanan'],
            ],
            'Nusa Tenggara Barat' => [
                'kota' => ['Bima', 'Mataram'],
                'kabupaten' => ['Bima', 'Dompu', 'Lombok Barat', 'Lombok Tengah', 'Lombok Timur', 'Lombok Utara', 'Sumbawa', 'Sumbawa Barat'],
            ],
            'Nusa Tenggara Timur' => [
                'kota' => ['Kupang'],
                'kabupaten' => ['Alor', 'Belu', 'Ende', 'Flores Timur', 'Kupang', 'Lembata', 'Malaka', 'Manggarai', 'Manggarai Barat', 'Manggarai Timur', 'Nagekeo', 'Ngada', 'Rote Ndao', 'Sabu Raijua', 'Sikka', 'Sumba Barat', 'Sumba Barat Daya', 'Sumba Tengah', 'Sumba Timur', 'Timor Tengah Selatan', 'Timor Tengah Utara'],
            ],
            'Kalimantan Barat' => [
                'kota' => ['Pontianak', 'Singkawang'],
                'kabupaten' => ['Bengkayang', 'Kapuas Hulu', 'Kayong Utara', 'Ketapang', 'Kubu Raya', 'Landak', 'Melawi', 'Mempawah', 'Sambas', 'Sanggau', 'Sekadau', 'Sintang'],
            ],
            'Kalimantan Tengah' => [
                'kota' => ['Palangka Raya'],
                'kabupaten' => ['Barito Selatan', 'Barito Timur', 'Barito Utara', 'Gunung Mas', 'Kapuas', 'Katingan', 'Kotawaringin Barat', 'Kotawaringin Timur', 'Lamandau', 'Murung Raya', 'Pulang Pisau', 'Seruyan', 'Sukamara'],
            ],
            'Kalimantan Selatan' => [
                'kota' => ['Banjarbaru', 'Banjarmasin'],
                'kabupaten' => ['Balangan', 'Banjar', 'Barito Kuala', 'Hulu Sungai Selatan', 'Hulu Sungai Tengah', 'Hulu Sungai Utara', 'Kotabaru', 'Tabalong', 'Tanah Bumbu', 'Tanah Laut', 'Tapin'],
            ],
            'Kalimantan Timur' => [
                'kota' => ['Balikpapan', 'Bontang', 'Samarinda'],
                'kabupaten' => ['Berau', 'Kutai Barat', 'Kutai Kartanegara', 'Kutai Timur', 'Mahakam Ulu', 'Paser', 'Penajam Paser Utara'],
            ],
            'Kalimantan Utara' => [
                'kota' => ['Tarakan'],
                'kabupaten' => ['Bulungan', 'Malinau', 'Nunukan', 'Tana Tidung'],
            ],
            'Sulawesi Utara' => [
                'kota' => ['Bitung', 'Kotamobagu', 'Manado', 'Tomohon'],
                'kabupaten' => ['Bolaang Mongondow', 'Bolaang Mongondow Selatan', 'Bolaang Mongondow Timur', 'Bolaang Mongondow Utara', 'Kepulauan Sangihe', 'Kepulauan Siau Tagulandang Biaro', 'Kepulauan Talaud', 'Minahasa', 'Minahasa Selatan', 'Minahasa Tenggara', 'Minahasa Utara'],
            ],
            'Gorontalo' => [
                'kota' => ['Gorontalo'],
                'kabupaten' => ['Boalemo', 'Bone Bolango', 'Gorontalo', 'Gorontalo Utara', 'Pohuwato'],
            ],
            'Sulawesi Tengah' => [
                'kota' => ['Palu'],
                'kabupaten' => ['Banggai', 'Banggai Kepulauan', 'Banggai Laut', 'Buol', 'Donggala', 'Morowali', 'Morowali Utara', 'Parigi Moutong', 'Poso', 'Sigi', 'Tojo Una-Una', 'Tolitoli'],
            ],
            'Sulawesi Barat' => [
                'kota' => [],
                'kabupaten' => ['Majene', 'Mamasa', 'Mamuju', 'Mamuju Tengah', 'Pasangkayu', 'Polewali Mandar'],
            ],
            'Sulawesi Selatan' => [
                'kota' => ['Makassar', 'Palopo', 'Parepare'],
                'kabupaten' => ['Bantaeng', 'Barru', 'Bone', 'Bulukumba', 'Enrekang', 'Gowa', 'Jeneponto', 'Kepulauan Selayar', 'Luwu', 'Luwu Timur', 'Luwu Utara', 'Maros', 'Pangkajene dan Kepulauan', 'Pinrang', 'Sidenreng Rappang', 'Sinjai', 'Soppeng', 'Takalar', 'Tana Toraja', 'Toraja Utara', 'Wajo'],
            ],
            'Sulawesi Tenggara' => [
                'kota' => ['Baubau', 'Kendari'],
                'kabupaten' => ['Bombana', 'Buton', 'Buton Selatan', 'Buton Tengah', 'Buton Utara', 'Kolaka', 'Kolaka Timur', 'Kolaka Utara', 'Konawe', 'Konawe Kepulauan', 'Konawe Selatan', 'Konawe Utara', 'Muna', 'Muna Barat', 'Wakatobi'],
            ],
            'Maluku' => [
                'kota' => ['Ambon', 'Tual'],
                'kabupaten' => ['Buru', 'Buru Selatan', 'Kepulauan Aru', 'Kepulauan Tanimbar', 'Maluku Barat Daya', 'Maluku Tengah', 'Maluku Tenggara', 'Seram Bagian Barat', 'Seram Bagian Timur'],
            ],
            'Maluku Utara' => [
                'kota' => ['Ternate', 'Tidore Kepulauan'],
                'kabupaten' => ['Halmahera Barat', 'Halmahera Selatan', 'Halmahera Tengah', 'Halmahera Timur', 'Halmahera Utara', 'Kepulauan Sula', 'Pulau Morotai', 'Pulau Taliabu'],
            ],
            'Papua' => [
                'kota' => ['Jayapura'],
                'kabupaten' => ['Biak Numfor', 'Jayapura', 'Keerom', 'Kepulauan Yapen', 'Mamberamo Raya', 'Sarmi', 'Supiori', 'Waropen'],
            ],
            'Papua Barat' => [
                'kota' => [],
                'kabupaten' => ['Fakfak', 'Kaimana', 'Manokwari', 'Manokwari Selatan', 'Pegunungan Arfak', 'Teluk Bintuni', 'Teluk Wondama'],
            ],
            'Papua Barat Daya' => [
                'kota' => ['Sorong'],
                'kabupaten' => ['Maybrat', 'Raja Ampat', 'Sorong', 'Sorong Selatan', 'Tambrauw'],
            ],
            'Papua Selatan' => [
                'kota' => [],
                'kabupaten' => ['Asmat', 'Boven Digoel', 'Mappi', 'Merauke'],
            ],
            'Papua Tengah' => [
                'kota' => [],
                'kabupaten' => ['Deiyai', 'Dogiyai', 'Intan Jaya', 'Mimika', 'Nabire', 'Paniai', 'Puncak', 'Puncak Jaya'],
            ],
            'Papua Pegunungan' => [
                'kota' => [],
                'kabupaten' => ['Jayawijaya', 'Lanny Jaya', 'Mamberamo Tengah', 'Nduga', 'Pegunungan Bintang', 'Tolikara', 'Yahukimo', 'Yalimo'],
            ],
        ];
    }

    /**
     * Daftar datar untuk datalist: nama kota ditulis apa adanya, kabupaten diberi
     * awalan "Kabupaten" agar tidak tertukar dengan kota bernama sama
     * (contoh: "Bandung" vs "Kabupaten Bandung").
     *
     * @return array<string, string> nama wilayah => provinsi
     */
    public static function cities(): array
    {
        $result = [];

        foreach (self::provinces() as $province => $regions) {
            foreach ($regions['kota'] as $kota) {
                $result[$kota] = $province;
            }
            foreach ($regions['kabupaten'] as $kabupaten) {
                $result['Kabupaten '.$kabupaten] = $province;
            }
        }

        ksort($result, SORT_NATURAL | SORT_FLAG_CASE);

        return $result;
    }

    /** @return array<int, string> */
    public static function cityNames(): array
    {
        return array_keys(self::cities());
    }
}
