<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\CustomerPic;
use App\Models\DesignRequest;
use App\Models\DesignRequestItem;
use App\Models\Invoice;
use App\Models\InvoiceTerm;
use App\Models\Lead;
use App\Models\PraLead;
use App\Models\Project;
use App\Models\ProjectTerm;
use App\Models\ProjectWorkflow;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data demo untuk peragaan dan penyusunan panduan.
 *
 * Seluruh record memakai kode berawalan *-DEMO-* dan ditulis dengan updateOrCreate,
 * sehingga seeder aman dijalankan berulang dan tidak menyentuh data lain.
 *
 *   php artisan db:seed --class=DemoDataSeeder
 *
 * Seluruh data ini dapat dibersihkan kembali dengan menghapus record yang kodenya
 * mengandung "-DEMO-" sebelum sistem dipakai untuk data sesungguhnya.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $users = $this->users();

            $customers = $this->customers($users);
            $this->praLeads($users);
            $leads = $this->leads($users, $customers);
            $this->activities($users, $customers, $leads);
            $designRequests = $this->designRequests($users, $customers, $leads);
            $quotations = $this->quotations($users, $customers, $leads, $designRequests);
            $projects = $this->projects($users, $customers, $quotations);
            $purchaseOrders = $this->purchaseOrderRequests($users, $quotations, $projects);
            $this->invoices($users, $purchaseOrders, $projects);
        });
    }

    /** @return array<string, User> */
    private function users(): array
    {
        $emails = [
            'admin' => 'superadmin@robust.test',
            'sales' => 'sales@robust.test',
            'sales2' => 'sales2@robust.test',
            'spv' => 'spv@robust.test',
            'drafter' => 'drafter@robust.test',
            'production' => 'production@robust.test',
            'qc' => 'qc@robust.test',
            'delivery' => 'delivery@robust.test',
            'administration' => 'administration@robust.test',
        ];

        $users = [];
        foreach ($emails as $key => $email) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                throw new \RuntimeException("Akun demo {$email} belum ada. Jalankan php artisan migrate --seed terlebih dahulu.");
            }
            $users[$key] = $user;
        }

        return $users;
    }

    /** @return array<string, Customer> */
    private function customers(array $users): array
    {
        $rows = [
            ['CUST-DEMO-01', 'Universitas Airlangga', 'Universitas', 'lab@unair.ac.id', '0315912345', 'Surabaya', 'Jl. Airlangga No. 4-6, Surabaya', 'won_closing', 100, 'sales', 'Dr. Slamet Riyadi', 'Kepala Laboratorium', '081233344455'],
            ['CUST-DEMO-02', 'Institut Teknologi Sepuluh Nopember', 'Universitas', 'procurement@its.ac.id', '0315994251', 'Surabaya', 'Kampus ITS Sukolilo, Surabaya', 'follow_up', 65, 'sales', 'Prof. Hendra Wijaya', 'Ketua Departemen', '081255566677'],
            ['CUST-DEMO-03', 'RS Premier Surabaya', 'Rumah Sakit', 'pengadaan@rspremier.co.id', '0315993211', 'Surabaya', 'Jl. Nginden Intan Barat, Surabaya', 'approaching', 35, 'sales2', 'dr. Ratna Kusuma', 'Manajer Penunjang Medis', '081266677788'],
            ['CUST-DEMO-04', 'PT Cheil Jedang Indonesia', 'Industri', 'qc@cj.co.id', '0321865200', 'Kabupaten Jombang', 'Desa Jati Gedong, Ploso, Jombang', 'won_closing', 100, 'sales', 'Bili Prasetyo', 'Supervisor QC', '085855902999'],
            ['CUST-DEMO-05', 'PT Kimia Farma Tbk', 'BUMN', 'lab@kimiafarma.co.id', '0217982424', 'Jakarta Timur', 'Jl. Veteran No. 9, Jakarta', 'identify', 15, 'sales2', 'Bambang Sutejo', 'Manager QC', '081244433322'],
            ['CUST-DEMO-06', 'Politeknik Negeri Malang', 'Sekolah', 'lab@polinema.ac.id', '0341404424', 'Malang', 'Jl. Soekarno Hatta No. 9, Malang', 'maintaining', 100, 'sales', 'Agus Hermawan', 'Kepala Jurusan', '081299988877'],
        ];

        $customers = [];
        foreach ($rows as $row) {
            [$code, $name, $category, $email, $phone, $city, $address, $stage, $probability, $owner, $picName, $picPosition, $picPhone] = $row;

            $customer = Customer::withTrashed()->updateOrCreate(['code' => $code], [
                'name' => $name,
                'category' => $category,
                'type' => $category,
                'email' => $email,
                'phone' => $phone,
                'city' => $city,
                'area' => $city,
                'address' => $address,
                'pipeline_stage' => $stage,
                'probability' => $probability,
                'status' => 'aktif',
                'sales_id' => $users[$owner]->id,
                'partner_since' => in_array($stage, ['won_closing', 'maintaining'], true) ? now()->subYear() : null,
                'notes' => 'Data demo untuk peragaan sistem.',
                'deleted_at' => null,
            ]);

            CustomerPic::updateOrCreate(
                ['customer_id' => $customer->id, 'name' => $picName],
                ['position' => $picPosition, 'phone' => $picPhone, 'email' => $email, 'is_primary' => true]
            );

            $customers[$code] = $customer;
        }

        return $customers;
    }

    private function praLeads(array $users): void
    {
        $rows = [
            ['PL-DEMO-01', 'PT Sanbe Farma', 'Rina Marlina', 'Manager QA', '081211122233', 'supplier', 'Lab QC Farmasi', 'Bandung', 'Pengadaan fume hood dan meja instrumen untuk lab QC.', 250000000, 400000000, 'high', 'draft', null, null],
            ['PL-DEMO-02', 'SMA Negeri 5 Surabaya', 'Sri Wahyuni', 'Wakil Kepala Sekolah', '081277766655', 'distributor', 'Lab IPA Terpadu', 'Surabaya', 'Renovasi lab IPA terpadu dua ruang.', 80000000, 120000000, 'medium', 'assigned', 'sales', null],
            ['PL-DEMO-03', 'Politeknik Negeri Malang', 'Agus Hermawan', 'Kepala Jurusan', '081299988877', 'mec', 'Lab Kimia Dasar', 'Malang', 'Pengadaan meja lab dan fume hood untuk lab kimia baru.', 150000000, 250000000, 'high', 'waiting_acceptance', 'sales', null],
            ['PL-DEMO-04', 'RS Premier Surabaya', 'dr. Ratna Kusuma', 'Manajer Penunjang Medis', '081266677788', 'loops_lab_nusantara', 'Lab Patologi', 'Surabaya', 'Lab patologi anatomi baru di lantai 3.', 300000000, 450000000, 'high', 'accepted', 'sales2', null],
            ['PL-DEMO-05', 'CV Mitra Analitika', 'Doni Saputra', 'Direktur', '081288899900', 'robust_multilab_solusindo', 'Lab Uji Air', 'Semarang', 'Lab uji kualitas air skala kecil.', 40000000, 70000000, 'low', 'rejected', 'sales2', 'Nilai proyek di bawah batas minimum dan lokasi di luar coverage.'],
        ];

        foreach ($rows as $row) {
            [$code, $instansi, $pic, $position, $phone, $source, $labType, $location, $need, $min, $max, $priority, $status, $owner, $rejectReason] = $row;

            PraLead::withTrashed()->updateOrCreate(['code' => $code], [
                'instansi' => $instansi,
                'pic_name' => $pic,
                'pic_position' => $position,
                'phone' => $phone,
                'email' => strtolower(str_replace(' ', '.', $pic)).'@contoh.test',
                'source' => $source,
                'lab_type' => $labType,
                'location' => $location,
                'initial_need' => $need,
                'est_value_min' => $min,
                'est_value_max' => $max,
                'priority' => $priority,
                'status' => $status,
                'assigned_sales_id' => $owner ? $users[$owner]->id : null,
                'created_by' => $users['admin']->id,
                'reject_reason' => $rejectReason,
                'sent_at' => in_array($status, ['waiting_acceptance', 'accepted', 'rejected'], true) ? now()->subDays(3) : null,
                'responded_at' => in_array($status, ['accepted', 'rejected'], true) ? now()->subDays(2) : null,
                'deleted_at' => null,
            ]);
        }
    }

    /** @return array<string, Lead> */
    private function leads(array $users, array $customers): array
    {
        $rows = [
            ['LD-DEMO-01', 'CUST-DEMO-05', 'PT Kimia Farma Tbk', 'Bambang Sutejo', 'Manager QC', 'Jakarta Timur', 'BUMN', 'supplier', 'Lab QC Produksi', 'lead', 'sales2', 200000000, 320000000, 'medium'],
            ['LD-DEMO-02', 'CUST-DEMO-03', 'RS Premier Surabaya', 'dr. Ratna Kusuma', 'Manajer Penunjang Medis', 'Surabaya', 'Rumah Sakit', 'loops_lab_nusantara', 'Lab Patologi Anatomi', 'design_request', 'sales2', 300000000, 450000000, 'high'],
            ['LD-DEMO-03', 'CUST-DEMO-02', 'Institut Teknologi Sepuluh Nopember', 'Prof. Hendra Wijaya', 'Ketua Departemen', 'Surabaya', 'Universitas', 'mec', 'Lab Riset Material', 'penawaran', 'sales', 400000000, 550000000, 'high'],
            ['LD-DEMO-04', 'CUST-DEMO-06', 'Politeknik Negeri Malang', 'Agus Hermawan', 'Kepala Jurusan', 'Malang', 'Sekolah', 'distributor', 'Lab Kimia Dasar', 'negosiasi', 'sales', 150000000, 250000000, 'medium'],
            ['LD-DEMO-05', 'CUST-DEMO-04', 'PT Cheil Jedang Indonesia', 'Bili Prasetyo', 'Supervisor QC', 'Kabupaten Jombang', 'Industri', 'robust_indonesia_sinar_lab', 'Lab QC Pangan', 'won', 'sales', 500000000, 650000000, 'high'],
            ['LD-DEMO-06', 'CUST-DEMO-01', 'Universitas Airlangga', 'Dr. Slamet Riyadi', 'Kepala Laboratorium', 'Surabaya', 'Universitas', 'distributor', 'Lab Kimia Terpadu', 'won', 'sales', 450000000, 600000000, 'high'],
        ];

        $leads = [];
        foreach ($rows as $row) {
            [$code, $customerCode, $instansi, $pic, $position, $city, $type, $source, $labName, $stage, $owner, $min, $max, $priority] = $row;
            $customer = $customers[$customerCode];

            $leads[$code] = Lead::withTrashed()->updateOrCreate(['code' => $code], [
                'customer_id' => $customer->id,
                'instansi' => $instansi,
                'division' => 'Laboratorium',
                'pic_name' => $pic,
                'pic_position' => $position,
                'phone' => $customer->pics()->first()?->phone ?: '081200000000',
                'email' => $customer->email,
                'location' => $customer->address ?: $city,
                'city' => $city,
                'instansi_type' => $type,
                'source' => $source,
                'lab_name' => $labName,
                'need_description' => "Kebutuhan {$labName} lengkap dengan bench, storage, dan safety equipment.",
                'scope_items' => ['Wall Bench', 'Island Bench', 'Fume Hood', 'Storage Cabinet'],
                'capacity' => '20 pengguna',
                'est_value_min' => $min,
                'est_value_max' => $max,
                'priority' => $priority,
                'stage' => $stage,
                'status' => 'aktif',
                'initial_note' => 'Data demo untuk peragaan sistem.',
                'initial_followup_date' => now()->addDays(3),
                'contact_preference' => 'WhatsApp',
                'best_contact_time' => 'Pagi (09.00 - 11.00)',
                'sales_id' => $users[$owner]->id,
                'created_by' => $users[$owner]->id,
                'deleted_at' => null,
            ]);
        }

        return $leads;
    }

    private function activities(array $users, array $customers, array $leads): void
    {
        $rows = [
            ['ACT-DEMO-01', 'meeting', 'Presentasi awal kebutuhan lab', 'CUST-DEMO-02', 'LD-DEMO-03', 'sales', 0, 'completed', 'Customer setuju lanjut ke tahap desain.'],
            ['ACT-DEMO-02', 'survey_lokasi', 'Survey lokasi lab lantai 3', 'CUST-DEMO-03', 'LD-DEMO-02', 'sales2', 0, 'scheduled', null],
            ['ACT-DEMO-03', 'call', 'Follow up hasil penawaran', 'CUST-DEMO-06', 'LD-DEMO-04', 'sales', 1, 'scheduled', null],
            ['ACT-DEMO-04', 'whatsapp', 'Kirim katalog fume hood', 'CUST-DEMO-05', 'LD-DEMO-01', 'sales2', -3, 'completed', 'Katalog terkirim, menunggu respons.'],
            ['ACT-DEMO-05', 'presentasi', 'Presentasi teknis ke tim procurement', 'CUST-DEMO-02', 'LD-DEMO-03', 'sales', 2, 'scheduled', null],
            ['ACT-DEMO-06', 'follow_up', 'Follow up PO customer', 'CUST-DEMO-04', 'LD-DEMO-05', 'sales', -1, 'pending', null],
            ['ACT-DEMO-07', 'email', 'Kirim revisi penawaran', 'CUST-DEMO-06', 'LD-DEMO-04', 'sales', -5, 'completed', 'Revisi harga sudah dikirim.'],
            ['ACT-DEMO-08', 'penawaran', 'Serah terima dokumen penawaran', 'CUST-DEMO-01', 'LD-DEMO-06', 'sales', -7, 'completed', 'Penawaran diterima customer.'],
        ];

        foreach ($rows as $row) {
            [$code, $type, $title, $customerCode, $leadCode, $owner, $dayOffset, $status, $result] = $row;

            Activity::withTrashed()->updateOrCreate(['code' => $code], [
                'customer_id' => $customers[$customerCode]->id,
                'lead_id' => $leads[$leadCode]->id,
                'sales_id' => $users[$owner]->id,
                'type' => $type,
                'title' => $title,
                'description' => 'Aktivitas demo untuk peragaan modul Activities dan Calendar.',
                'activity_date' => now()->addDays($dayOffset)->toDateString(),
                'activity_time' => '10:00',
                'duration_minutes' => 60,
                'pipeline_stage' => $customers[$customerCode]->pipeline_stage,
                'status' => $status,
                'result' => $result,
                'next_action' => $status === 'completed' ? 'Siapkan dokumen tindak lanjut.' : null,
                'next_followup_date' => now()->addDays(max(1, $dayOffset + 5))->toDateString(),
                'created_by' => $users[$owner]->id,
                'deleted_at' => null,
            ]);
        }
    }

    /** @return array<string, DesignRequest> */
    private function designRequests(array $users, array $customers, array $leads): array
    {
        $rows = [
            ['DR-DEMO-01', 'CUST-DEMO-05', 'LD-DEMO-01', 'Lab QC Produksi', 'draft', 0, 'normal'],
            ['DR-DEMO-02', 'CUST-DEMO-03', 'LD-DEMO-02', 'Lab Patologi Anatomi', 'assigned', 0, 'urgent'],
            ['DR-DEMO-03', 'CUST-DEMO-06', 'LD-DEMO-04', 'Lab Kimia Dasar', 'drawing_uploaded', 25, 'normal'],
            ['DR-DEMO-04', 'CUST-DEMO-02', 'LD-DEMO-03', 'Lab Riset Material', 'costing', 60, 'urgent'],
            ['DR-DEMO-05', 'CUST-DEMO-04', 'LD-DEMO-05', 'Lab QC Pangan', 'completed', 100, 'normal'],
        ];

        $designRequests = [];
        foreach ($rows as $row) {
            [$code, $customerCode, $leadCode, $projectName, $status, $progress, $priority] = $row;
            $customer = $customers[$customerCode];
            $lead = $leads[$leadCode];
            $completed = $status === 'completed';

            $designRequest = DesignRequest::withTrashed()->updateOrCreate(['code' => $code], [
                'lead_id' => $lead->id,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'pic_name' => $lead->pic_name,
                'project_name' => $projectName,
                'sales_id' => $lead->sales_id,
                'production_pic_id' => $users['drafter']->id,
                'request_date' => now()->subDays(12)->toDateString(),
                'deadline' => now()->addDays(5)->toDateString(),
                'priority' => $priority,
                'short_description' => "Perancangan {$projectName} sesuai kebutuhan customer.",
                'lab_type' => $projectName,
                'capacity' => '20 pengguna',
                'detail_need' => 'Wall bench, island bench, fume hood, storage cabinet, dan sink unit lengkap dengan utilitas.',
                'scope_checklist' => ['Wall Bench', 'Island Bench', 'Fume Hood', 'Storage Cabinet'],
                'outputs' => ['layout_2d', 'rendering_3d', 'boq', 'cost_estimation'],
                'extra_note' => 'Data demo untuk peragaan modul Design Request.',
                'production_note' => 'Mohon prioritaskan layout 2D terlebih dahulu.',
                'status' => $status,
                'progress' => $progress,
                'cost_material' => $completed ? 220000000 : null,
                'cost_production' => $completed ? 90000000 : null,
                'cost_installation' => $completed ? 40000000 : null,
                'cost_total' => $completed ? 350000000 : null,
                'technical_note' => $completed ? 'Top phenolic resin, rangka steel powder coating.' : null,
                'submitted_at' => $completed ? now()->subDays(2) : null,
                'created_by' => $lead->sales_id,
                'deleted_at' => null,
            ]);

            if ($completed) {
                $items = [
                    ['Furniture', 'Island Bench', 'Phenolic top, 3000x1500mm', 6, 'Unit', 28000000],
                    ['Furniture', 'Fume Hood', 'Bypass type, 1500mm', 2, 'Unit', 45000000],
                    ['Furniture', 'Storage Cabinet', '900x450x1800mm', 8, 'Unit', 6500000],
                    ['Furniture', 'Sink Unit', 'PP sink + faucet', 4, 'Unit', 6500000],
                ];
                foreach ($items as $item) {
                    DesignRequestItem::updateOrCreate(
                        ['design_request_id' => $designRequest->id, 'name' => $item[1]],
                        [
                            'category' => $item[0],
                            'specification' => $item[2],
                            'qty' => $item[3],
                            'unit' => $item[4],
                            'unit_price' => $item[5],
                            'total' => $item[3] * $item[5],
                        ]
                    );
                }
            }

            $designRequests[$code] = $designRequest;
        }

        return $designRequests;
    }

    /** @return array<string, Quotation> */
    private function quotations(array $users, array $customers, array $leads, array $designRequests): array
    {
        $rows = [
            ['Q-DEMO-01', 'CUST-DEMO-05', 'LD-DEMO-01', null, 'Lab QC Produksi', 'draft', 'sales2'],
            ['Q-DEMO-02', 'CUST-DEMO-03', 'LD-DEMO-02', null, 'Lab Patologi Anatomi', 'ready', 'sales2'],
            ['Q-DEMO-03', 'CUST-DEMO-02', 'LD-DEMO-03', 'DR-DEMO-04', 'Lab Riset Material', 'sent_to_customer', 'sales'],
            ['Q-DEMO-04', 'CUST-DEMO-06', 'LD-DEMO-04', 'DR-DEMO-03', 'Lab Kimia Dasar', 'customer_accepted', 'sales'],
            ['Q-DEMO-05', 'CUST-DEMO-04', 'LD-DEMO-05', 'DR-DEMO-05', 'Lab QC Pangan', 'request_po_created', 'sales'],
            ['Q-DEMO-06', 'CUST-DEMO-01', 'LD-DEMO-06', null, 'Lab Kimia Terpadu', 'request_po_created', 'sales'],
            ['Q-DEMO-07', 'CUST-DEMO-02', 'LD-DEMO-03', null, 'Lab Riset Material - Revisi 2', 'draft', 'sales'],
            ['Q-DEMO-08', 'CUST-DEMO-06', 'LD-DEMO-04', null, 'Lab Kimia Dasar Polinema', 'request_po_created', 'sales'],
        ];

        $quotations = [];
        foreach ($rows as $row) {
            [$code, $customerCode, $leadCode, $designRequestCode, $projectName, $status, $owner] = $row;
            $customer = $customers[$customerCode];
            $lead = $leads[$leadCode];

            $subtotal = 320000000;
            $discount = $subtotal * 0.05;
            $afterDiscount = $subtotal - $discount;
            $tax = $afterDiscount * 0.11;
            $additional = 15000000;

            $quotation = Quotation::withTrashed()->updateOrCreate(['code' => $code], [
                'design_request_id' => $designRequestCode ? $designRequests[$designRequestCode]->id : null,
                'lead_id' => $lead->id,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'pic_name' => $lead->pic_name,
                'project_name' => $projectName,
                'sales_id' => $users[$owner]->id,
                'delivery_method' => 'email',
                'quote_date' => now()->subDays(6)->toDateString(),
                'valid_until' => now()->addDays(24)->toDateString(),
                'priority' => 'medium',
                'currency' => 'IDR',
                'creation_mode' => 'form',
                'customer_note' => 'Harga sudah termasuk pengiriman dan instalasi di lokasi customer.',
                'internal_note' => 'Data demo untuk peragaan modul Penawaran.',
                'subtotal' => $subtotal,
                'discount_type' => 'percent',
                'discount_value' => 5,
                'discount_amount' => $discount,
                'tax_percent' => 11,
                'tax_amount' => $tax,
                'additional_costs' => [['label' => 'Pengiriman & Instalasi', 'amount' => $additional]],
                'additional_total' => $additional,
                'grand_total' => $afterDiscount + $tax + $additional,
                'target_margin' => 25,
                'status' => $status,
                'sent_at' => in_array($status, ['sent_to_customer', 'customer_accepted', 'request_po_created'], true) ? now()->subDays(4) : null,
                'customer_response_at' => in_array($status, ['customer_accepted', 'request_po_created'], true) ? now()->subDays(2) : null,
                'customer_response_note' => in_array($status, ['customer_accepted', 'request_po_created'], true) ? 'Customer setuju, PO menyusul.' : null,
                'created_by' => $users[$owner]->id,
                'deleted_at' => null,
            ]);

            $items = [
                ['Furniture', 'Island Bench', 'Phenolic top, 3000x1500mm', 6, 28000000, 0],
                ['Furniture', 'Fume Hood', 'Bypass type, 1500mm', 2, 45000000, 1],
                ['Furniture', 'Storage Cabinet', '900x450x1800mm', 8, 6500000, 2],
                ['Furniture', 'Sink Unit', 'PP sink + faucet', 4, 6500000, 3],
            ];
            foreach ($items as $item) {
                QuotationItem::updateOrCreate(
                    ['quotation_id' => $quotation->id, 'name' => $item[1]],
                    [
                        'category' => $item[0],
                        'specification' => $item[2],
                        'qty' => $item[3],
                        'unit' => 'Unit',
                        'cost_price' => $item[4] * 0.7,
                        'unit_price' => $item[4],
                        'margin' => 30,
                        'is_optional' => false,
                        'total' => $item[3] * $item[4],
                        'sort_order' => $item[5],
                    ]
                );
            }

            $quotations[$code] = $quotation;
        }

        return $quotations;
    }

    /** @return array<string, Project> */
    private function projects(array $users, array $customers, array $quotations): array
    {
        $rows = [
            ['PRJ-DEMO-01', 'Q-DEMO-05', 'CUST-DEMO-04', 'Lab QC Pangan CJ Jombang', 'ongoing', 55, [
                'production_status' => 'production',
                'production_progress' => 60,
                'production_note' => 'Fabrikasi bench tahap finishing.',
                'delivery_status' => 'scheduling',
            ]],
            ['PRJ-DEMO-02', 'Q-DEMO-06', 'CUST-DEMO-01', 'Lab Kimia Terpadu UNAIR', 'finishing', 85, [
                'production_status' => 'production_finished',
                'production_progress' => 100,
                'production_report_completed' => true,
                'production_note' => 'Seluruh item selesai diproduksi.',
                'qc_completed' => true,
                'qc_note' => 'Seluruh item lolos pemeriksaan visual dan fungsi.',
                'administration_comment' => 'DP sudah masuk, menunggu bukti potong PPh.',
                'payment_confirmation_completed' => true,
                'delivery_status' => 'in_transit',
                'delivery_scheduled_at' => now()->addDays(2),
            ]],
            ['PRJ-DEMO-03', null, 'CUST-DEMO-06', 'Renovasi Lab Kimia Polinema', 'planning', 10, [
                'production_status' => 'stock',
                'production_progress' => 0,
                'delivery_status' => 'scheduling',
            ]],
            ['PRJ-DEMO-04', 'Q-DEMO-08', 'CUST-DEMO-06', 'Lab Kimia Dasar Polinema', 'done', 100, [
                'production_status' => 'production_finished',
                'production_progress' => 100,
                'production_report_completed' => true,
                'production_note' => 'Produksi selesai dan sudah diserahkan ke QC.',
                'qc_completed' => true,
                'qc_note' => 'Seluruh item lolos QC.',
                'administration_comment' => 'Pembayaran DP dan bukti potong PPh lengkap.',
                'payment_confirmation_completed' => true,
                'withholding_tax_receipt_completed' => true,
                'delivery_status' => 'completed',
                'delivery_scheduled_at' => now()->subDays(6),
                'customer_receiver_name' => 'Agus Hermawan',
                'customer_received_at' => now()->subDays(4),
                'delivery_note' => 'Barang diterima lengkap dan sesuai.',
                'delivery_out_completed' => true,
                'delivery_returned_completed' => true,
            ]],
        ];

        $projects = [];
        foreach ($rows as $row) {
            [$code, $quotationCode, $customerCode, $name, $status, $progress, $workflow] = $row;
            $quotation = $quotationCode ? $quotations[$quotationCode] : null;
            $value = (float) ($quotation?->subtotal ?? 300000000);
            $tax = (float) ($quotation?->tax_amount ?? $value * 0.11);

            $project = Project::withTrashed()->updateOrCreate(['code' => $code], [
                'quotation_id' => $quotation?->id,
                'customer_id' => $customers[$customerCode]->id,
                'name' => $name,
                'description' => 'Project demo untuk peragaan alur operasional lintas divisi.',
                'category' => 'Laboratory Furniture',
                'type' => 'turnkey',
                'priority' => 'high',
                'status' => $status,
                'start_date' => now()->subDays(20)->toDateString(),
                'target_date' => now()->addDays(45)->toDateString(),
                'work_method' => 'Turnkey',
                'location' => $customers[$customerCode]->city,
                'scope_of_work' => 'Supply, delivery, dan instalasi furniture laboratorium.',
                'project_value' => $value,
                'tax_amount' => $tax,
                'total_value' => $value + $tax,
                'currency' => 'IDR',
                'payment_scheme' => 'DP 30% - Progress 40% - Pelunasan 30%',
                'project_manager_id' => $quotation?->sales_id ?: $users['sales']->id,
                'internal_team' => [$users['drafter']->id, $users['production']->id],
                'progress' => $progress,
                'created_by' => $users['admin']->id,
                'deleted_at' => null,
            ]);

            foreach ([
                ['DP', 30, now()->subDays(15), 'paid'],
                ['Progress', 40, now()->addDays(20), 'pending'],
                ['Pelunasan', 30, now()->addDays(50), 'pending'],
            ] as $term) {
                ProjectTerm::updateOrCreate(
                    ['project_id' => $project->id, 'name' => $term[0]],
                    [
                        'percentage' => $term[1],
                        'amount' => ($value + $tax) * $term[1] / 100,
                        'due_date' => $term[2]->toDateString(),
                        'status' => $term[3],
                    ]
                );
            }

            ProjectWorkflow::updateOrCreate(['project_id' => $project->id], $workflow + [
                'production_updated_by' => $users['production']->id,
                'production_updated_at' => now()->subDays(3),
                'qc_updated_by' => ! empty($workflow['qc_completed']) ? $users['qc']->id : null,
                'qc_updated_at' => ! empty($workflow['qc_completed']) ? now()->subDays(2) : null,
                'administration_updated_by' => ! empty($workflow['administration_comment']) ? $users['administration']->id : null,
                'administration_updated_at' => ! empty($workflow['administration_comment']) ? now()->subDay() : null,
                'delivery_updated_by' => $users['delivery']->id,
                'delivery_updated_at' => now()->subDay(),
            ]);

            $projects[$code] = $project;
        }

        return $projects;
    }

    /** @return array<string, PurchaseOrderRequest> */
    private function purchaseOrderRequests(array $users, array $quotations, array $projects): array
    {
        $rows = [
            ['RPO-DEMO-01', 'Q-DEMO-04', 'PRJ-DEMO-DRAFT', 'draft', null, null],
            ['RPO-DEMO-02', 'Q-DEMO-05', 'RBS 2770826', 'submitted', null, null],
            ['RPO-DEMO-03', 'Q-DEMO-06', 'RBS 2770901', 'po_created', 'ACC-PO-2026-0091', 'PO sudah dibuat di Accurate.'],
            ['RPO-DEMO-04', 'Q-DEMO-08', 'RBS 2770915', 'po_created', 'ACC-PO-2026-0104', 'Delivery selesai, siap ditagihkan.'],
        ];

        $purchaseOrders = [];
        foreach ($rows as $row) {
            [$code, $quotationCode, $projectNumber, $status, $accurateNumber, $accurateNote] = $row;
            $quotation = $quotations[$quotationCode];
            $isDraft = $status === 'draft';

            $purchaseOrders[$code] = PurchaseOrderRequest::updateOrCreate(['code' => $code], [
                'project_number' => $isDraft ? null : $projectNumber,
                'quotation_id' => $quotation->id,
                'customer_id' => $quotation->customer_id,
                'customer_name' => $quotation->customer_name,
                'customer_area' => $quotation->customer?->city,
                'customer_division' => 'Laboratorium',
                'requested_by' => $quotation->sales_id,
                'request_date' => $isDraft ? null : now()->subDays(3)->toDateString(),
                'customer_po_number' => $isDraft ? null : '55006147'.rand(10, 99),
                'delivery_address' => $isDraft ? null : $quotation->customer?->address,
                'delivery_pic_name' => $isDraft ? null : $quotation->pic_name,
                'delivery_pic_phone' => $isDraft ? null : '081200000000',
                'npwp_name' => $isDraft ? null : $quotation->customer_name,
                'npwp_number' => $isDraft ? null : '01.234.567.8-999.000',
                'payment_term' => $isDraft ? null : 'DP 30%, Progress 40%, Pelunasan 30%',
                'expected_delivery_date' => $isDraft ? null : now()->addDays(30)->toDateString(),
                'checklist' => $isDraft ? null : collect(PurchaseOrderRequest::defaultChecklistItems())
                    ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'checked' => $status === 'po_created'])
                    ->values()
                    ->all(),
                'checklist_completed_at' => $status === 'po_created' ? now()->subDay() : null,
                'admin_note' => $isDraft ? 'Menunggu nomor proyek dan PO customer.' : 'Mohon segera diproses ke Accurate.',
                'status' => $status,
                'accurate_po_number' => $accurateNumber,
                'accurate_po_date' => $accurateNumber ? now()->subDay()->toDateString() : null,
                'accurate_note' => $accurateNote,
                'processed_at' => $accurateNumber ? now()->subDay() : null,
            ]);
        }

        return $purchaseOrders;
    }

    private function invoices(array $users, array $purchaseOrders, array $projects): void
    {
        $requestPo = $purchaseOrders['RPO-DEMO-03'];
        $project = $projects['PRJ-DEMO-02'];
        $subtotal = (float) $project->project_value;
        $tax = (float) $project->tax_amount;
        $grand = $subtotal + $tax;

        $invoice = Invoice::updateOrCreate(['code' => 'INV-DEMO-01'], [
            'purchase_order_request_id' => $requestPo->id,
            'invoice_date' => now()->subDays(2)->toDateString(),
            'customer_name' => $requestPo->customer_name,
            'project_number' => $requestPo->project_number,
            'project_name' => $project->name,
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'installation_amount' => 0,
            'grand_total' => $grand,
            'paid_total' => $grand * 0.3,
            'status' => 'partial',
            'note' => 'Data demo untuk peragaan modul Invoice.',
            'created_by' => $users['admin']->id,
        ]);

        foreach ([
            [1, 'DP 30%', 30, now()->subDays(2), 'paid'],
            [2, 'Progress 40%', 40, now()->addDays(20), 'issued'],
            [3, 'Pelunasan 30%', 30, now()->addDays(50), 'planned'],
        ] as $term) {
            [$number, $description, $percentage, $dueDate, $status] = $term;
            $amount = $grand * $percentage / 100;

            InvoiceTerm::updateOrCreate(
                ['invoice_id' => $invoice->id, 'term_number' => $number],
                [
                    'description' => $description,
                    'percentage' => $percentage,
                    'amount' => $amount,
                    'due_date' => $dueDate->toDateString(),
                    'issued_date' => $status === 'planned' ? null : now()->subDays(2)->toDateString(),
                    'accurate_invoice_number' => $status === 'paid' ? 'ACC-INV-2026-0031' : null,
                    'paid_amount' => $status === 'paid' ? $amount : 0,
                    'paid_date' => $status === 'paid' ? now()->subDay()->toDateString() : null,
                    'status' => $status,
                ]
            );
        }
    }
}
