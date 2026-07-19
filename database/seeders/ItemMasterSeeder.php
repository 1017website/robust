<?php

namespace Database\Seeders;

use App\Models\ItemMaster;
use Illuminate\Database\Seeder;

class ItemMasterSeeder extends Seeder
{
    public function run(): void
    {
        $items = array_map(static fn (array $item): array => [
            'code' => $item[0],
            'category' => $item[1],
            'name' => $item[2],
            'variant' => $item[3],
            'specification' => $item[4],
            'unit' => 'Unit',
            'default_cost_price' => 0,
            'default_margin' => 0,
            'is_active' => true,
        ], [
            ['ITM-0001', 'Meja Laboratorium', 'Meja Layout', 'Layout L', 'Top phenolic resin, rangka steel powder coating, menyesuaikan ukuran ruangan'],
            ['ITM-0002', 'Meja Laboratorium', 'Meja Layout', 'Layout U', 'Top phenolic resin, rangka steel powder coating, menyesuaikan ukuran ruangan'],
            ['ITM-0003', 'Storage', 'Base Cabinet', 'Laci 3', 'Kabinet steel powder coating, tiga laci, central lock'],
            ['ITM-0004', 'Storage', 'Base Cabinet', 'Laci 4', 'Kabinet steel powder coating, empat laci, central lock'],
            ['ITM-0005', 'Meja Laboratorium', 'Wall Bench', '1200 x 750 x 850 mm', 'Top phenolic resin, rangka steel powder coating, adjustable foot'],
            ['ITM-0006', 'Meja Laboratorium', 'Wall Bench', '1500 x 750 x 850 mm', 'Top phenolic resin, rangka steel powder coating, adjustable foot'],
            ['ITM-0007', 'Meja Laboratorium', 'Wall Bench', '1800 x 750 x 850 mm', 'Top phenolic resin, rangka steel powder coating, adjustable foot'],
            ['ITM-0008', 'Meja Laboratorium', 'Island Bench', '2400 x 1500 x 850 mm', 'Top phenolic resin dua sisi, rangka steel powder coating'],
            ['ITM-0009', 'Meja Laboratorium', 'Island Bench', '3000 x 1500 x 850 mm', 'Top phenolic resin dua sisi, rangka steel powder coating'],
            ['ITM-0010', 'Meja Laboratorium', 'Island Bench', '3600 x 1500 x 850 mm', 'Top phenolic resin dua sisi, rangka steel powder coating'],
            ['ITM-0011', 'Meja Laboratorium', 'Corner Bench', '900 x 900 x 850 mm', 'Top phenolic resin, rangka steel powder coating'],
            ['ITM-0012', 'Meja Laboratorium', 'Meja Persiapan', '1500 x 750 x 850 mm', 'Top phenolic resin, rangka steel powder coating'],
            ['ITM-0013', 'Meja Laboratorium', 'Meja Instrumen', '1200 x 750 x 850 mm', 'Top phenolic resin, rangka heavy duty, dilengkapi leveling foot'],
            ['ITM-0014', 'Meja Laboratorium', 'Meja Timbang', '900 x 750 x 850 mm', 'Top granite anti-vibration, rangka heavy duty'],
            ['ITM-0015', 'Meja Laboratorium', 'Meja Komputer', '1200 x 600 x 750 mm', 'Top HPL, tray keyboard, jalur manajemen kabel'],
            ['ITM-0016', 'Meja Laboratorium', 'Mobile Bench', '1200 x 600 x 850 mm', 'Top phenolic resin, caster wheel dengan rem'],
            ['ITM-0017', 'Storage', 'Base Cabinet', 'Pintu 1', 'Kabinet steel powder coating, satu pintu dan adjustable shelf'],
            ['ITM-0018', 'Storage', 'Base Cabinet', 'Pintu 2', 'Kabinet steel powder coating, dua pintu dan adjustable shelf'],
            ['ITM-0019', 'Storage', 'Base Cabinet', 'Laci 2 dan Pintu 1', 'Kabinet steel powder coating kombinasi dua laci dan satu pintu'],
            ['ITM-0020', 'Storage', 'Wall Cabinet', 'Pintu Solid', 'Kabinet gantung steel powder coating, pintu solid dan adjustable shelf'],
            ['ITM-0021', 'Storage', 'Wall Cabinet', 'Pintu Kaca', 'Kabinet gantung steel powder coating, pintu kaca dan adjustable shelf'],
            ['ITM-0022', 'Storage', 'Tall Cabinet', 'Pintu Solid', 'Kabinet tinggi steel powder coating, pintu solid dan adjustable shelf'],
            ['ITM-0023', 'Storage', 'Tall Cabinet', 'Pintu Kaca', 'Kabinet tinggi steel powder coating, pintu kaca dan adjustable shelf'],
            ['ITM-0024', 'Storage', 'Chemical Storage Cabinet', 'Ventilated', 'Kabinet penyimpanan bahan kimia dengan tray penahan tumpahan dan ventilasi'],
            ['ITM-0025', 'Storage', 'Flammable Cabinet', 'Safety Cabinet', 'Kabinet penyimpanan bahan mudah terbakar dengan double wall'],
            ['ITM-0026', 'Fume Hood', 'Fume Hood', 'Bypass 1200 mm', 'Tipe bypass, worktop phenolic resin, sash tempered glass, tanpa blower'],
            ['ITM-0027', 'Fume Hood', 'Fume Hood', 'Bypass 1500 mm', 'Tipe bypass, worktop phenolic resin, sash tempered glass, tanpa blower'],
            ['ITM-0028', 'Fume Hood', 'Fume Hood', 'Bypass 1800 mm', 'Tipe bypass, worktop phenolic resin, sash tempered glass, tanpa blower'],
            ['ITM-0029', 'Fume Hood', 'Walk-In Fume Hood', '1800 mm', 'Tipe walk-in untuk peralatan tinggi, sash tempered glass, tanpa blower'],
            ['ITM-0030', 'Fume Hood', 'Blower Fume Hood', 'Centrifugal PP', 'Blower centrifugal bahan polypropylene tahan bahan kimia'],
            ['ITM-0031', 'Sink & Utility', 'Sink Unit', 'PP Sink Single Bowl', 'Sink polypropylene, faucet laboratorium dan base cabinet'],
            ['ITM-0032', 'Sink & Utility', 'Sink Unit', 'PP Sink Double Bowl', 'Double bowl polypropylene sink, faucet laboratorium dan base cabinet'],
            ['ITM-0033', 'Sink & Utility', 'Sink Unit', 'Stainless Steel', 'Sink stainless steel, faucet dan base cabinet'],
            ['ITM-0034', 'Sink & Utility', 'Laboratory Faucet', 'Three Way', 'Faucet laboratorium tiga outlet dengan coating tahan bahan kimia'],
            ['ITM-0035', 'Sink & Utility', 'Gas Cock', 'Single Way', 'Gas cock satu outlet dengan coating tahan bahan kimia'],
            ['ITM-0036', 'Sink & Utility', 'Pegboard', 'PP Pegboard', 'Pegboard polypropylene lengkap dengan drip tray dan peg'],
            ['ITM-0037', 'Safety Equipment', 'Emergency Eyewash', 'Deck Mounted', 'Emergency eyewash dipasang pada meja atau sink'],
            ['ITM-0038', 'Safety Equipment', 'Emergency Eyewash', 'Pedestal', 'Emergency eyewash berdiri dengan bowl dan push plate'],
            ['ITM-0039', 'Safety Equipment', 'Safety Shower', 'Wall Mounted', 'Emergency safety shower pemasangan dinding'],
            ['ITM-0040', 'Safety Equipment', 'Safety Shower & Eyewash', 'Combination', 'Kombinasi emergency shower dan eyewash'],
            ['ITM-0041', 'Aksesori', 'Reagent Shelf', 'Single Tier', 'Rak reagen satu tingkat dengan rangka steel powder coating'],
            ['ITM-0042', 'Aksesori', 'Reagent Shelf', 'Double Tier', 'Rak reagen dua tingkat dengan rangka steel powder coating'],
            ['ITM-0043', 'Aksesori', 'Service Column', 'Ceiling Mounted', 'Kolom servis untuk instalasi listrik, data, air, dan gas'],
            ['ITM-0044', 'Aksesori', 'Electrical Socket Module', 'Double Socket', 'Modul stop kontak ganda untuk meja laboratorium'],
            ['ITM-0045', 'Aksesori', 'Task Light', 'LED', 'Lampu kerja LED untuk meja atau rak laboratorium'],
        ]);

        ItemMaster::upsert(
            $items,
            ['code'],
            ['category', 'name', 'variant', 'specification', 'unit', 'is_active']
        );
    }
}
