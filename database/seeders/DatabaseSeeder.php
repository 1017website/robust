<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\CustomerPic;
use App\Models\DesignRequest;
use App\Models\DesignRequestItem;
use App\Models\Lead;
use App\Models\PraLead;
use App\Models\Project;
use App\Models\ProjectTerm;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- Users ----------
        $superadmin = User::create([
            'name' => 'Administrator', 'email' => 'superadmin@robust.test',
            'password' => Hash::make('password'), 'role' => 'administrator',
            'job_title' => 'System Administrator', 'phone' => '081200000000', 'is_active' => true,
        ]);
        $admin = User::create([
            'name' => 'Budi Santoso', 'email' => 'admin@robust.test',
            'password' => Hash::make('password'), 'role' => 'sales_admin',
            'job_title' => 'Sales Admin', 'phone' => '081200000001', 'is_active' => true,
        ]);
        $sales = User::create([
            'name' => 'Rizky Pratama', 'email' => 'sales@robust.test',
            'password' => Hash::make('password'), 'role' => 'sales',
            'job_title' => 'Sales Engineer', 'phone' => '081200000002', 'is_active' => true,
        ]);
        $sales2 = User::create([
            'name' => 'Dewi Lestari', 'email' => 'sales2@robust.test',
            'password' => Hash::make('password'), 'role' => 'sales',
            'job_title' => 'Sales Engineer', 'phone' => '081200000003', 'is_active' => true,
        ]);
        $spv = User::create([
            'name' => 'Siti Rahma', 'email' => 'spv@robust.test',
            'password' => Hash::make('password'), 'role' => 'sales_spv',
            'job_title' => 'SPV Sales', 'phone' => '081200000005', 'is_active' => true,
        ]);
        $drafter = User::create([
            'name' => 'Andi Setiawan', 'email' => 'drafter@robust.test',
            'password' => Hash::make('password'), 'role' => 'drafter',
            'job_title' => 'Drafter / Produksi', 'phone' => '081200000004', 'is_active' => true,
        ]);

        // ---------- Customers ----------
        $c1 = Customer::create([
            'code' => 'CUST-0001', 'name' => 'Universitas Airlangga', 'category' => 'Universitas',
            'email' => 'lab@unair.ac.id', 'phone' => '0315912345', 'city' => 'Surabaya',
            'address' => 'Jl. Airlangga No. 4-6, Surabaya', 'pipeline_stage' => 'won_closing',
            'probability' => 100, 'sales_id' => $sales->id, 'partner_since' => Carbon::now()->subYear(),
            'notes' => 'Klien aktif, beberapa proyek lab kimia.',
        ]);
        CustomerPic::create(['customer_id' => $c1->id, 'name' => 'Dr. Slamet Riyadi', 'position' => 'Kepala Lab', 'phone' => '081233344455', 'email' => 'slamet@unair.ac.id', 'is_primary' => true]);

        $c2 = Customer::create([
            'code' => 'CUST-0002', 'name' => 'Institut Teknologi Bandung', 'category' => 'Universitas',
            'email' => 'procurement@itb.ac.id', 'phone' => '0222500935', 'city' => 'Bandung',
            'address' => 'Jl. Ganesha No. 10, Bandung', 'pipeline_stage' => 'follow_up',
            'probability' => 60, 'sales_id' => $sales->id, 'notes' => 'Sedang evaluasi penawaran lab riset.',
        ]);
        CustomerPic::create(['customer_id' => $c2->id, 'name' => 'Prof. Hendra Wijaya', 'position' => 'Ketua Departemen', 'phone' => '081255566677', 'email' => 'hendra@itb.ac.id', 'is_primary' => true]);

        $c3 = Customer::create([
            'code' => 'CUST-0003', 'name' => 'RS Premier Surabaya', 'category' => 'Rumah Sakit',
            'email' => 'pengadaan@rspremier.co.id', 'phone' => '0315993211', 'city' => 'Surabaya',
            'pipeline_stage' => 'approaching', 'probability' => 35, 'sales_id' => $sales2->id,
            'notes' => 'Butuh lab patologi baru.',
        ]);

        // ---------- Pra Leads ----------
        PraLead::create([
            'code' => 'PL-0001', 'instansi' => 'Politeknik Negeri Malang', 'pic_name' => 'Agus Hermawan',
            'pic_position' => 'Kepala Jurusan', 'phone' => '081299988877', 'email' => 'agus@polinema.ac.id',
            'source' => 'website', 'lab_type' => 'Lab Kimia Dasar', 'location' => 'Malang',
            'initial_need' => 'Pengadaan meja lab dan fume hood untuk lab kimia baru.',
            'est_value_min' => 150000000, 'est_value_max' => 250000000, 'priority' => 'high',
            'status' => 'waiting_acceptance', 'assigned_sales_id' => $sales->id, 'created_by' => $admin->id,
            'sent_at' => Carbon::now()->subDays(2),
        ]);
        PraLead::create([
            'code' => 'PL-0002', 'instansi' => 'SMA Negeri 5 Surabaya', 'pic_name' => 'Sri Wahyuni',
            'pic_position' => 'Wakil Kepala Sekolah', 'phone' => '081277766655', 'source' => 'referensi',
            'lab_type' => 'Lab IPA', 'location' => 'Surabaya', 'initial_need' => 'Renovasi lab IPA terpadu.',
            'est_value_min' => 80000000, 'est_value_max' => 120000000, 'priority' => 'medium',
            'status' => 'waiting_acceptance', 'assigned_sales_id' => $sales->id, 'created_by' => $admin->id,
            'sent_at' => Carbon::now()->subDay(),
        ]);
        PraLead::create([
            'code' => 'PL-0003', 'instansi' => 'PT Kimia Farma', 'pic_name' => 'Bambang Sutejo',
            'pic_position' => 'Manager QC', 'phone' => '081244433322', 'source' => 'whatsapp',
            'lab_type' => 'Lab QC', 'location' => 'Jakarta', 'initial_need' => 'Lab QC dengan fume hood industri.',
            'est_value_min' => 300000000, 'priority' => 'high', 'status' => 'draft', 'created_by' => $admin->id,
        ]);

        // ---------- Lead (accepted) ----------
        $lead = Lead::create([
            'code' => 'LD-0001', 'instansi' => 'Institut Teknologi Bandung', 'pic_name' => 'Prof. Hendra Wijaya',
            'pic_position' => 'Ketua Departemen', 'phone' => '081255566677', 'email' => 'hendra@itb.ac.id',
            'location' => 'Jl. Ganesha No. 10', 'city' => 'Bandung', 'instansi_type' => 'Universitas',
            'source' => 'website', 'lab_name' => 'Lab Riset Material', 'customer_id' => $c2->id,
            'need_description' => 'Pembangunan lab riset material dengan island bench dan fume hood.',
            'scope_items' => ['Island Bench', 'Fume Hood', 'Wall Cabinet', 'Sink Unit'],
            'est_value_min' => 400000000, 'est_value_max' => 550000000, 'priority' => 'high',
            'stage' => 'penawaran', 'status' => 'active', 'sales_id' => $sales->id,
        ]);

        // ---------- Design Request (completed) ----------
        $dr = DesignRequest::create([
            'code' => 'DR-0001', 'customer_name' => 'Institut Teknologi Bandung', 'pic_name' => 'Prof. Hendra Wijaya',
            'project_name' => 'Lab Riset Material', 'lead_id' => $lead->id, 'customer_id' => $c2->id,
            'sales_id' => $sales->id, 'production_pic_id' => $drafter->id,
            'request_date' => Carbon::now()->subDays(10), 'deadline' => Carbon::now()->addDays(4),
            'priority' => 'high', 'short_description' => 'Lab riset material 2 ruang.',
            'detail_need' => 'Island bench 6 unit, fume hood 2 unit, wall cabinet, sink unit lengkap.',
            'scope_checklist' => ['Island Bench', 'Fume Hood', 'Wall Cabinet', 'Sink Unit'],
            'outputs' => ['layout_2d', 'rendering_3d', 'boq', 'cost_estimation'],
            'status' => 'completed', 'progress' => 100,
            'cost_material' => 220000000, 'cost_production' => 90000000, 'cost_installation' => 40000000,
            'cost_total' => 350000000, 'technical_note' => 'Material phenolic resin top, rangka steel powder coating.',
            'submitted_at' => Carbon::now()->subDays(2),
        ]);
        foreach ([
            ['Furniture', 'Island Bench', 'Phenolic top, 3000x1500mm', 6, 'Unit', 28000000],
            ['Furniture', 'Fume Hood', 'Bypass type, 1500mm', 2, 'Unit', 45000000],
            ['Furniture', 'Wall Cabinet', '900x350x750mm', 8, 'Unit', 4500000],
            ['Furniture', 'Sink Unit', 'PP sink + faucet', 4, 'Unit', 6500000],
        ] as $it) {
            DesignRequestItem::create([
                'design_request_id' => $dr->id, 'category' => $it[0], 'name' => $it[1],
                'specification' => $it[2], 'qty' => $it[3], 'unit' => $it[4],
                'unit_price' => $it[5], 'total' => $it[3] * $it[5],
            ]);
        }

        // ---------- Quotation ----------
        $sub = 6 * 28000000 + 2 * 45000000 + 8 * 4500000 + 4 * 6500000; // 320.000.000
        $discount = $sub * 0.05;
        $afterDisc = $sub - $discount;
        $tax = $afterDisc * 0.11;
        $add = 15000000;
        $grand = $afterDisc + $tax + $add;
        $quo = Quotation::create([
            'code' => 'Q-2026-0001', 'design_request_id' => $dr->id, 'lead_id' => $lead->id,
            'customer_id' => $c2->id, 'customer_name' => 'Institut Teknologi Bandung',
            'pic_name' => 'Prof. Hendra Wijaya', 'project_name' => 'Lab Riset Material', 'sales_id' => $sales->id,
            'delivery_method' => 'email', 'quote_date' => Carbon::now()->subDays(1),
            'valid_until' => Carbon::now()->addDays(29), 'subtotal' => $sub,
            'discount_type' => 'percent', 'discount_value' => 5, 'discount_amount' => $discount,
            'tax_percent' => 11, 'tax_amount' => $tax,
            'additional_costs' => [['label' => 'Pengiriman & Instalasi', 'amount' => 15000000]],
            'additional_total' => $add, 'grand_total' => $grand, 'target_margin' => 25, 'status' => 'approved',
            'submitted_for_approval_at' => Carbon::now()->subDays(2), 'approved_by' => $spv->id,
            'approved_at' => Carbon::now()->subDay(), 'approval_note' => 'Seeder: nilai dan scope sudah disetujui.',
        ]);
        foreach ($dr->items as $i => $it) {
            QuotationItem::create([
                'quotation_id' => $quo->id, 'category' => $it->category, 'name' => $it->name,
                'specification' => $it->specification, 'qty' => $it->qty, 'unit' => $it->unit,
                'unit_price' => $it->unit_price, 'total' => $it->total, 'sort_order' => $i,
            ]);
        }

        // ---------- Project (dari quotation won c1) ----------
        $subP = 480000000;
        $taxP = $subP * 0.11;
        $project = Project::create([
            'code' => 'PRJ-2026-0001', 'quotation_id' => null, 'customer_id' => $c1->id,
            'name' => 'Lab Kimia Terpadu UNAIR', 'description' => 'Pembangunan lab kimia terpadu 3 lantai.',
            'category' => 'Laboratory Furniture', 'type' => 'turnkey', 'priority' => 'high', 'status' => 'ongoing',
            'start_date' => Carbon::now()->subDays(20), 'target_date' => Carbon::now()->addDays(60),
            'work_method' => 'Turnkey', 'location' => 'Surabaya',
            'scope_of_work' => 'Supply & install furniture lab kimia 3 lantai.',
            'project_value' => $subP, 'tax_amount' => $taxP, 'total_value' => $subP + $taxP,
            'payment_scheme' => 'DP 30% - Progress 40% - Pelunasan 30%',
            'project_manager_id' => $sales->id, 'internal_team' => [$drafter->id], 'progress' => 45,
        ]);
        foreach ([
            ['DP', 30, Carbon::now()->subDays(18), 'paid'],
            ['Progress', 40, Carbon::now()->addDays(20), 'pending'],
            ['Pelunasan', 30, Carbon::now()->addDays(60), 'pending'],
        ] as $t) {
            ProjectTerm::create([
                'project_id' => $project->id, 'name' => $t[0], 'percentage' => $t[1],
                'amount' => ($subP + $taxP) * $t[1] / 100, 'due_date' => $t[2], 'status' => $t[3],
            ]);
        }

        // ---------- Activities ----------
        Activity::create([
            'code' => 'ACT-0001', 'customer_id' => $c2->id, 'lead_id' => $lead->id, 'sales_id' => $sales->id,
            'type' => 'meeting', 'title' => 'Presentasi penawaran ke ITB', 'description' => 'Presentasi desain & harga.',
            'activity_date' => Carbon::today(), 'activity_time' => '10:00', 'duration_minutes' => 60,
            'status' => 'scheduled', 'pipeline_stage' => 'penawaran', 'next_action' => 'Kirim revisi BOQ',
            'next_followup_date' => Carbon::today()->addDays(3),
        ]);
        Activity::create([
            'code' => 'ACT-0002', 'customer_id' => $c1->id, 'project_id' => $project->id, 'sales_id' => $sales->id,
            'type' => 'visit', 'title' => 'Survey lokasi instalasi UNAIR', 'activity_date' => Carbon::today(),
            'activity_time' => '14:00', 'status' => 'completed', 'pipeline_stage' => 'won_closing',
            'result' => 'Lokasi siap, instalasi lantai 1 minggu depan.',
        ]);
        Activity::create([
            'code' => 'ACT-0003', 'customer_id' => $c3->id, 'sales_id' => $sales2->id,
            'type' => 'call', 'title' => 'Follow up kebutuhan lab patologi',
            'activity_date' => Carbon::tomorrow(), 'activity_time' => '09:30', 'status' => 'scheduled',
            'pipeline_stage' => 'approaching',
        ]);
    }
}
