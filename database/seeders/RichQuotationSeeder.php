<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DesignRequest;
use App\Models\ItemMaster;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class RichQuotationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $sales = $this->user('sales@robust.test', 'Rizky Pratama', 'sales');
            $spv = $this->user('spv@robust.test', 'Siti Rahma', 'sales_spv');
            $drafter = $this->user('drafter@robust.test', 'Andi Setiawan', 'drafter');

            $customer = Customer::withTrashed()->updateOrCreate(
                ['code' => 'CUST-DEMO-DETAIL'],
                [
                    'name' => 'Institut Teknologi Bandung - Demo Detail',
                    'category' => 'Universitas',
                    'email' => 'procurement.demo@itb.ac.id',
                    'phone' => '0222500935',
                    'city' => 'Bandung',
                    'address' => 'Jl. Ganesha No. 10, Bandung',
                    'pipeline_stage' => 'penawaran',
                    'probability' => 75,
                    'sales_id' => $sales->id,
                    'notes' => 'Data demo untuk verifikasi export penawaran detail.',
                    'deleted_at' => null,
                ]
            );

            $catalog = $this->catalog();
            $masters = [];
            foreach ($catalog as $key => $item) {
                $masters[$key] = ItemMaster::updateOrCreate(
                    ['code' => $item['code']],
                    [
                        'category' => $item['master_category'],
                        'name' => $item['name'],
                        'variant' => $item['variant'],
                        'specification' => $item['specification'],
                        'unit' => 'Unit',
                        'default_cost_price' => 400000,
                        'default_margin' => 20,
                        'is_active' => true,
                    ]
                );
            }

            $designRequest = DesignRequest::withTrashed()->updateOrCreate(
                ['code' => 'DR-DEMO-DETAIL-2026'],
                [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'pic_name' => 'Prof. Hendra Wijaya',
                    'project_name' => 'Demo Penawaran Detail Sesuai Standar Robust 2026',
                    'sales_id' => $sales->id,
                    'production_pic_id' => $drafter->id,
                    'request_date' => today()->subDays(10),
                    'deadline' => today()->addDays(7),
                    'priority' => 'high',
                    'short_description' => 'Demo export detail Wall Bench dan Fume Hood.',
                    'detail_need' => 'Wall bench steel structure serta Fume Hood ECO ukuran 1250 dan 1500 mm.',
                    'scope_checklist' => ['Wall Bench', 'Fume Hood'],
                    'outputs' => ['rendering_3d', 'boq', 'cost_estimation'],
                    'dimensions' => [
                        ['item' => 'Wall Bench', 'size' => '2000 x 700 x 850 mm'],
                        ['item' => 'Fume Hood FH-125 ECO', 'size' => '1250 x 890 x 2350 mm'],
                        ['item' => 'Fume Hood FH-150 ECO', 'size' => '1500 x 890 x 2350 mm'],
                    ],
                    'materials' => [
                        ['item' => 'Wall Bench', 'material' => 'Steel plate + phenolic resin', 'finish' => 'Epoxy powder coating'],
                        ['item' => 'Fume Hood', 'material' => 'Plywood + HPL melamine', 'finish' => 'Chemical resistant'],
                    ],
                    'accessories' => ['Electrical socket', 'Water tap', 'PP sink', 'Blower', 'Ducting'],
                    'cost_material' => 900000,
                    'cost_production' => 200000,
                    'cost_installation' => 100000,
                    'cost_total' => 1200000,
                    'technical_note' => 'Seeder demo menggunakan spesifikasi bertingkat dan gambar dari workbook standar Robust 2026.',
                    'status' => 'completed',
                    'progress' => 100,
                    'submitted_at' => now()->subDays(2),
                    'created_by' => $sales->id,
                    'deleted_at' => null,
                ]
            );

            $designItems = [];
            foreach (array_values($catalog) as $index => $item) {
                $designImage = "design-request-items/demo-detail/{$item['image']}";
                $this->publishImage($item['image'], $designImage);
                $designItems[$index] = $designRequest->items()->updateOrCreate(
                    ['sort_order' => $index],
                    [
                        'item_master_id' => $masters[$item['key']]->id,
                        'category' => null,
                        'name' => $item['name'],
                        'variant' => $item['variant'],
                        'specification' => $item['specification'],
                        'quotation_image_path' => $designImage,
                        'quotation_image_name' => $item['image'],
                        'qty' => 1,
                        'unit' => 'Unit',
                        'unit_price' => 400000,
                        'margin' => 20,
                        'is_optional' => false,
                        'total' => 400000,
                    ]
                );
            }
            $designRequest->items()->whereNotIn('sort_order', array_keys($designItems))->delete();

            $subtotal = count($catalog) * 500000;
            $tax = $subtotal * 0.11;
            $quotation = Quotation::withTrashed()->updateOrCreate(
                ['code' => 'Q-DEMO-DETAIL-2026'],
                [
                    'design_request_id' => $designRequest->id,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'pic_name' => 'Prof. Hendra Wijaya',
                    'project_name' => $designRequest->project_name,
                    'sales_id' => $sales->id,
                    'delivery_method' => 'email',
                    'quote_date' => today(),
                    'valid_until' => today()->addMonth(),
                    'priority' => 'high',
                    'currency' => 'IDR',
                    'customer_note' => 'Demo format detail: section, breakdown Qty/UoM/harga, dan gambar produk.',
                    'subtotal' => $subtotal,
                    'discount_type' => 'percent',
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'tax_percent' => 11,
                    'tax_amount' => $tax,
                    'additional_costs' => [],
                    'additional_total' => 0,
                    'grand_total' => $subtotal + $tax,
                    'target_margin' => 20,
                    'status' => 'ready',
                    'submitted_for_approval_at' => now()->subDays(2),
                    'approved_by' => $spv->id,
                    'approved_at' => now()->subDay(),
                    'approval_note' => 'Data demo export detail sudah disetujui.',
                    'created_by' => $sales->id,
                    'deleted_at' => null,
                ]
            );

            foreach (array_values($catalog) as $index => $item) {
                $quotationImage = "quotation-items/demo-detail/{$item['image']}";
                $this->publishImage($item['image'], $quotationImage);
                $quotation->items()->updateOrCreate(
                    ['sort_order' => $index],
                    [
                        'source_design_request_item_id' => $designItems[$index]->id,
                        'item_master_id' => $masters[$item['key']]->id,
                        'category' => null,
                        'name' => $item['name'],
                        'variant' => $item['variant'],
                        'specification' => $item['specification'],
                        'quotation_image_path' => $quotationImage,
                        'quotation_image_name' => $item['image'],
                        'qty' => 1,
                        'unit' => 'Unit',
                        'cost_price' => 400000,
                        'unit_price' => 500000,
                        'margin' => 20,
                        'is_optional' => false,
                        'total' => 500000,
                    ]
                );
            }
            $quotation->items()->whereNotIn('sort_order', array_keys($designItems))->delete();
        });
    }

    private function user(string $email, string $name, string $role): User
    {
        return User::withTrashed()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'role' => $role,
                'is_active' => true,
                'deleted_at' => null,
            ]
        );
    }

    private function publishImage(string $filename, string $target): void
    {
        $source = database_path("seeders/assets/quotation-detail/{$filename}");
        if (! is_file($source)) {
            throw new RuntimeException("Asset seeder tidak ditemukan: {$source}");
        }

        Storage::disk('public')->put($target, file_get_contents($source));
    }

    private function catalog(): array
    {
        return [
            'wall_bench' => [
                'key' => 'wall_bench',
                'code' => 'ITM-DEMO-WBF-200-S',
                'master_category' => 'Meja Laboratorium',
                'name' => 'WALL BENCH - STEEL STRUCTURE',
                'variant' => 'WALL BENCH WBF-200-S-SRF',
                'image' => 'pegboard-wall-bench.png',
                'specification' => $this->wallBenchSpecification(),
            ],
            'fume_hood_125' => [
                'key' => 'fume_hood_125',
                'code' => 'ITM-DEMO-FH-125-ECO',
                'master_category' => 'Fume Hood',
                'name' => 'FUME HOOD ECO',
                'variant' => 'FUME HOOD FH-125 ECO',
                'image' => 'fume-hood-fh-125-eco.png',
                'specification' => $this->fumeHoodSpecification('FH-125 ECO', 1250),
            ],
            'fume_hood_150' => [
                'key' => 'fume_hood_150',
                'code' => 'ITM-DEMO-FH-150-ECO',
                'master_category' => 'Fume Hood',
                'name' => 'FUME HOOD ECO',
                'variant' => 'FUME HOOD FH-150 ECO',
                'image' => 'fume-hood-fh-150-eco.png',
                'specification' => $this->fumeHoodSpecification('FH-150 ECO', 1500),
            ],
        ];
    }

    private function wallBenchSpecification(): string
    {
        return <<<'SPEC'
[General]
Type: WBF-200-S
Model: Laboratory bench with floor-mounted steel under-bench cabinet
Manufacturer: PT. Robust Multilab Solusindo
Standards Compliance: ISO 9001:2015 & ISO 14001:2015 Certified Manufacturer
SEFA: SEFA 8M 2020
[Dimensions (W x D x H, mm)]
Overall Dimension: 2000 x 700 x 850
[Construction & Materials]
Main Structure: Steel plate structure, thickness 1.2 mm, finished with chemical-resistant epoxy powder coating
Cabinet Body: Steel plate thickness 1.2 mm, finished with chemical-resistant epoxy powder coating
Drawer / Door Panel: Galvanized steel plate thickness 1.2 mm, finished with chemical-resistant epoxy powder coating
Handle: Aluminum handle
Door / Drawer Design: Inset-type door and drawer design with concealed hinges, opening angle up to 110°
Worktop: Phenolic resin worktop, thickness 16 mm, certified tested by TUV: SEFA 8 standard and BS EN 438
Construction Design: Robust, rigid, self-supporting modular system, allows easy knock-down and reassembly
[Under-Bench Cabinet formations]
@ CS (Cabinet Sink) 700 | 1 | pcs
@ DC (Drawer + Cabinet) 500 | 1 | pcs
@ KSM (Knee Space) 800 | 1 | pcs
[Utility & Accessories]
Electrical Socket: Single electric socket, IP55, laboratory grade (Legrand or equivalent)
@ 4 | pcs | 500000
Socket Housing: PVC cable trunking 50 x 100 mm
@ 1 | btg | 500000
Electrical Installation: NYM cable 2.5 mm² or equivalent, with PVC conduit Ø 20 mm for protection
[Water & Sink System]
Water Tap: One-way swivel laboratory water tap (RBS W 101 or equivalent)
@ 1 | pcs | 500000
Laboratory Sink: PP sink with anti-siphon bottle trap
Model: RBS PPS-560 size 560 x 428 x 260 mm
@ 1 | set | 500000
[Peg Board]
Type: PEG-50
Total Dimension: 500 mm x 600 H mm
Board Material: Phenolic resin panel, thickness 6 mm
Peg Material: Molded Polypropylene
Peg Quantity: 18 pcs
Installation: Fixed and easy installation system
@ 1 | set | 500000
[Wall Side Rack]
Total Dimension: 1000 x 300 x 750 (H) mm
Model: Open rack for wall bench with 2 levels of storage
Main Structure: Steel plate structure, thickness 1.2 mm, finished with chemical-resistant epoxy powder coating
Rack Shelves: Steel plate thickness 1.2 mm, finished with chemical-resistant epoxy powder coating
Rack Perimeter: SS304 tube Ø 3/8 inch, thickness 1.2 mm, finished with chemical-resistant epoxy powder coating
Include: Electrical socket housing
@ 1 | set | 500000
[By Others / by Client]
Electrical Supply: 1 phase, 220 V, 16 A, cable NYM 3 x 2.5 mm²
Water Supply: PVC pipe Ø 0.5 inch with ball-valve
Drainage: PVC pipe Ø 1.5 inch
SPEC;
    }

    private function fumeHoodSpecification(string $type, int $width): string
    {
        return <<<SPEC
[General]
Type: {$type}
Manufacturer: PT. Robust Multilab Solusindo
Standards Compliance: ISO 9001:2015 & ISO 14001:2015 Certified Manufacturer
Face Velocity Standard: ANSI / ASHRAE 110 – 2016
Fume Hood Standard: SEFA 1 2020
[Dimensions (W x D x H, mm)]
Overall Dimension: {$width} x 890 x 2350
Upper Cabinet: {$width} x 890 x 1450
Base Cabinet: {$width} x 850 x 900
[Construction & Materials]
Side & Back Wall: Plywood, thickness 18 mm, laminated with HPL melamine
Baffle System: Plywood, thickness 15 mm, laminated with HPL melamine
Worktop: Phenolic resin, laboratory grade, thickness 16 mm, heat resistant up to 120 °C
Frame Structure: Plywood, thickness 18 mm, laminated with HPL melamine
Base Cabinet: Plywood, thickness 18 mm, laminated with HPL melamine
Hardware: Heavy-duty laboratory grade hinges, handles, and leveling feet
[Safety Features]
Sash: Vertical sliding tempered clear safety glass, thickness 6 mm
Operation: Counterbalanced sash system for smooth and stable operation
[Utilities & Accessories]
Electrical Socket: Single electric socket, close-cap
@ 2 | set |
Water Tap: Laboratory water fitting with front control valve
@ 1 | set |
Sink & Drain: PP cup sink Ø 17 cm complete with PP bottle trap
@ 1 | set |
Lighting: Fluorescent lamp, complete with ON/OFF switch
@ 1 | set |
[Blower Specification]
Type: Blower 7
Model: Chemical-resistant centrifugal blower
Material: Polypropylene (PP)
Air Volume: 1200 CFM / 2038 CMH
Static Pressure: 750 Pa
[Motor & Electrical]
Motor Power: 0.35 kW
Voltage / Phase / Frequency: 220 V / 1 phase / 50 Hz
Motor Speed: 2850 RPM
Protection Rating: IP 54
@ Blower Cable NYM 3 x 2.5 mm² | 10 | m
[Ducting]
Duct Material: PVC type D brand Rucika or equal
Duct Diameter: 8 inch
@ Duct Length | 6 | m
[Testing & Commissioning]
Included: IQ (Installation Qualification) & OQ (Operational Qualification)
[By Others / by Client]
Electrical Supply: 1 phase, 220 V, 16 A, cable NYM 3 x 2.5 mm²
Water Supply: PVC pipe Ø 0.5 inch with ball-valve
Drainage: PVC pipe Ø 1.5 inch
SPEC;
    }
}
