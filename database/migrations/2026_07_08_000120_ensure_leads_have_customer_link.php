<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $nextCustomerNumber = DB::table('customers')->count() + 1;

        DB::table('leads')
            ->whereNull('customer_id')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunkById(100, function ($leads) use (&$nextCustomerNumber, $now) {
                foreach ($leads as $lead) {
                    $customer = $this->findExistingCustomer($lead);
                    $customerId = $customer?->id;

                    if (! $customerId) {
                        [$code, $nextCustomerNumber] = $this->nextCustomerCode($nextCustomerNumber);

                        $customerId = DB::table('customers')->insertGetId([
                            'code' => $code,
                            'name' => $lead->instansi,
                            'category' => $lead->instansi_type,
                            'type' => $lead->lab_name,
                            'email' => $lead->email,
                            'phone' => $lead->phone,
                            'address' => $lead->location,
                            'city' => $lead->city,
                            'pipeline_stage' => 'identify',
                            'probability' => 0,
                            'status' => 'aktif',
                            'sales_id' => $lead->sales_id,
                            'notes' => $this->buildNotes($lead),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    DB::table('leads')->where('id', $lead->id)->update([
                        'customer_id' => $customerId,
                        'updated_at' => $now,
                    ]);

                    $this->ensurePrimaryPic($customerId, $lead, $now);
                }
            });
    }

    public function down(): void
    {
        // Tidak menghapus customer agar data yang sudah dipakai di pipeline/quotation/project tetap aman.
    }

    protected function findExistingCustomer(object $lead): ?object
    {
        $base = DB::table('customers')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($lead) {
                $query->where('sales_id', $lead->sales_id)->orWhereNull('sales_id');
            });

        if (! empty($lead->email)) {
            return (clone $base)->where('email', $lead->email)->first();
        }

        if (! empty($lead->phone)) {
            return (clone $base)->where('phone', $lead->phone)->first();
        }

        return (clone $base)->where('name', $lead->instansi)->first();
    }

    protected function nextCustomerCode(int $number): array
    {
        do {
            $code = 'CUST-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
            $number++;
        } while (DB::table('customers')->where('code', $code)->exists());

        return [$code, $number];
    }

    protected function ensurePrimaryPic(int $customerId, object $lead, $now): void
    {
        if (empty($lead->pic_name)) {
            return;
        }

        $primaryPic = DB::table('customer_pics')
            ->where('customer_id', $customerId)
            ->where('is_primary', true)
            ->first();

        $payload = [
            'name' => $lead->pic_name,
            'position' => $lead->pic_position,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'is_primary' => true,
            'updated_at' => $now,
        ];

        if ($primaryPic) {
            DB::table('customer_pics')->where('id', $primaryPic->id)->update($payload);
            return;
        }

        $payload['customer_id'] = $customerId;
        $payload['created_at'] = $now;
        DB::table('customer_pics')->insert($payload);
    }

    protected function buildNotes(object $lead): ?string
    {
        $notes = array_filter([
            $lead->initial_note,
            $lead->need_description ? 'Kebutuhan awal: '.$lead->need_description : null,
        ]);

        return $notes ? implode("\n\n", $notes) : null;
    }
};
