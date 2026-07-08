<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;

class LeadCustomerConnector
{
    /**
     * Pastikan setiap Lead memiliki Customer.
     * Customer baru selalu dimulai dari pipeline identify.
     */
    public function ensureForLead(Lead $lead): Customer
    {
        if ($lead->customer_id && $lead->customer) {
            $this->syncCustomerFromLead($lead->customer, $lead);
            return $lead->customer;
        }

        $customer = $this->findExistingCustomer($lead);

        if ($customer) {
            $this->syncCustomerFromLead($customer, $lead);
        } else {
            $customer = $this->createCustomerFromLead($lead);
            $this->syncPrimaryPic($customer, $lead);
        }

        if ((int) $lead->customer_id !== (int) $customer->id) {
            $lead->forceFill(['customer_id' => $customer->id])->save();
        }

        return $customer;
    }

    protected function findExistingCustomer(Lead $lead): ?Customer
    {
        $salesId = $lead->sales_id;

        $query = Customer::query()
            ->where(function ($scope) use ($salesId) {
                $scope->where('sales_id', $salesId)->orWhereNull('sales_id');
            });

        if ($lead->email) {
            return (clone $query)->where('email', $lead->email)->first();
        }

        if ($lead->phone) {
            return (clone $query)->where('phone', $lead->phone)->first();
        }

        return (clone $query)
            ->where('name', $lead->instansi)
            ->first();
    }

    protected function createCustomerFromLead(Lead $lead): Customer
    {
        return Customer::create([
            'code' => CodeGenerator::next(Customer::class, 'CUST', 4),
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
        ]);
    }

    protected function syncCustomerFromLead(Customer $customer, Lead $lead): void
    {
        $payload = [
            'name' => $lead->instansi,
            'category' => $lead->instansi_type,
            'type' => $lead->lab_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'address' => $lead->location,
            'city' => $lead->city,
            'sales_id' => $lead->sales_id ?: $customer->sales_id,
        ];

        // Jangan ubah pipeline_stage di sini. Customer dari lead tetap dimulai identify,
        // lalu perubahan pipeline hanya dilakukan dari menu Customer/Activities.
        $customer->fill($payload);

        if ($customer->isDirty()) {
            $customer->save();
        }

        $this->syncPrimaryPic($customer, $lead);
    }

    protected function syncPrimaryPic(Customer $customer, Lead $lead): void
    {
        if (! $lead->pic_name) {
            return;
        }

        $pic = $customer->pics()->where('is_primary', true)->first();

        $payload = [
            'name' => $lead->pic_name,
            'position' => $lead->pic_position,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'is_primary' => true,
        ];

        if ($pic) {
            $pic->update($payload);
            return;
        }

        $customer->pics()->create($payload);
    }

    protected function buildNotes(Lead $lead): ?string
    {
        $notes = array_filter([
            $lead->initial_note,
            $lead->need_description ? 'Kebutuhan awal: '.$lead->need_description : null,
        ]);

        return $notes ? implode("\n\n", $notes) : null;
    }
}
