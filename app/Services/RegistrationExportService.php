<?php

namespace App\Services;

use App\Models\Registration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class RegistrationExportService
{
    /**
     * Available columns for export organized by groups
     *
     * @return array<string, array<string, string>>
     */
    public function getAvailableColumns(): array
    {
        return [
            'basic' => [
                'id' => __('ID'),
                'full_name' => __('Full Name'),
                'email' => __('Email'),
                'status' => __('Status'),
                'created_at' => __('Registration Date'),
            ],
            'personal' => [
                'nationality' => __('Nationality'),
                'date_of_birth' => __('Date of Birth'),
                'gender' => __('Gender'),
                'document_country_origin' => __('Document Country'),
                'cpf' => __('CPF'),
                'rg_number' => __('RG Number'),
                'passport_number' => __('Passport Number'),
                'passport_expiry_date' => __('Passport Expiry Date'),
            ],
            'contact' => [
                'phone_number' => __('Phone Number'),
                'address_street' => __('Street Address'),
                'address_city' => __('City'),
                'address_state_province' => __('State/Province'),
                'address_country' => __('Country'),
                'address_postal_code' => __('Postal Code'),
            ],
            'professional' => [
                'affiliation' => __('Affiliation'),
                'position' => __('Position'),
                'is_abe_member' => __('ABE Member'),
            ],
            'conference' => [
                'events' => __('Events'),
                'participation_format' => __('Participation Format'),
                'arrival_date' => __('Arrival Date'),
                'departure_date' => __('Departure Date'),
                'needs_transport_from_gru' => __('Transport from GRU'),
                'needs_transport_from_usp' => __('Transport from USP'),
                'dietary_restrictions' => __('Dietary Restrictions'),
                'other_dietary_restrictions' => __('Other Dietary Restrictions'),
                'emergency_contact_name' => __('Emergency Contact Name'),
                'emergency_contact_relationship' => __('Emergency Contact Relationship'),
                'emergency_contact_phone' => __('Emergency Contact Phone'),
                'requires_visa_letter' => __('Requires Visa Letter'),
            ],
            'administrative' => [
                'registration_category_snapshot' => __('Registration Category'),
                'registration_fee_at_time' => __('Registration Fee at Time'),
                'invoice_sent_at' => __('Invoice Sent Date'),
                'payment_status' => __('Payment Status'),
                'enrollment_proof_status' => __('Enrollment Proof Status'),
                'notes' => __('Notes'),
                'updated_at' => __('Last Updated'),
            ],
        ];
    }

    /**
     * Export registrations to CSV with selected columns
     *
     * @param  Builder<Registration>  $query
     * @param  array<string>  $selectedColumns
     */
    public function exportToCsv(Builder $query, array $selectedColumns): Response
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Registration> $registrations */
        $registrations = $query->with(['events', 'payments', 'enrollmentProof'])->get();
        $availableColumns = $this->flattenAvailableColumns();

        // Filter only valid selected columns
        $validColumns = array_intersect_key($availableColumns, array_flip($selectedColumns));

        if (empty($validColumns)) {
            abort(400, __('No valid columns selected for export.'));
        }

        // Generate CSV content
        $csvContent = $this->generateCsvContent($registrations, $validColumns);

        // Create response with proper headers
        $filename = 'registrations_export_'.now()->format('Y-m-d_H-i').'.csv';

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Generate CSV content from registrations data
     *
     * @param  Collection<int, Registration>  $registrations
     * @param  array<string, string>  $columns
     */
    private function generateCsvContent(Collection $registrations, array $columns): string
    {
        $output = fopen('php://temp', 'r+');

        if ($output === false) {
            throw new \RuntimeException('Unable to create temporary file for CSV generation');
        }

        // Add BOM for UTF-8 support in Excel
        fwrite($output, "\xEF\xBB\xBF");

        // Write headers
        fputcsv($output, array_values($columns));

        // Write data rows
        foreach ($registrations as $registration) {
            $row = [];
            foreach (array_keys($columns) as $column) {
                $row[] = $this->getColumnValue($registration, $column);
            }
            fputcsv($output, $row);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        if ($csvContent === false) {
            throw new \RuntimeException('Unable to read CSV content from temporary file');
        }

        return $csvContent;
    }

    /**
     * Get value for a specific column from registration
     */
    private function getColumnValue(Registration $registration, string $column): string
    {
        switch ($column) {
            case 'events':
                return $registration->events->pluck('code')->join(', ');

            case 'payment_status':
                $latestPayment = $registration->payments->last();

                return $latestPayment ? $latestPayment->status : __('No payment');

            case 'enrollment_proof_status':
                return $registration->enrollmentProof ? $registration->enrollmentProof->status : __('No proof');

            case 'is_abe_member':
                return $registration->is_abe_member ? __('Yes') : __('No');

            case 'needs_transport_from_gru':
                return $registration->needs_transport_from_gru ? __('Yes') : __('No');

            case 'needs_transport_from_usp':
                return $registration->needs_transport_from_usp ? __('Yes') : __('No');

            case 'requires_visa_letter':
                return $registration->requires_visa_letter ? __('Yes') : __('No');

            case 'status':
                return __(ucfirst($registration->status));

            case 'registration_category_snapshot':
                return __($registration->registration_category_snapshot);

            case 'registration_fee_at_time':
                $fee = $registration->calculateCorrectTotalFee();

                return 'R$ '.number_format($fee, 2, ',', '.');

            case 'gender':
                return $registration->gender ? __($registration->gender) : '';

            case 'participation_format':
                return $registration->participation_format ? __($registration->participation_format) : '';

            case 'dietary_restrictions':
                return $registration->dietary_restrictions ? __($registration->dietary_restrictions) : '';

            case 'created_at':
            case 'updated_at':
            case 'invoice_sent_at':
            case 'date_of_birth':
            case 'passport_expiry_date':
            case 'arrival_date':
            case 'departure_date':
                $date = $registration->{$column};

                return $date ? $date->format('d/m/Y H:i') : '';

            default:
                $value = $registration->{$column} ?? '';

                return is_string($value) || is_numeric($value) ? (string) $value : '';
        }
    }

    /**
     * Flatten available columns array for easier processing
     *
     * @return array<string, string>
     */
    private function flattenAvailableColumns(): array
    {
        $flattened = [];
        foreach ($this->getAvailableColumns() as $group => $columns) {
            $flattened = array_merge($flattened, $columns);
        }

        return $flattened;
    }

    /**
     * Get column groups for modal organization
     *
     * @return array<string, string>
     */
    public function getColumnGroups(): array
    {
        return [
            'basic' => __('Basic Information'),
            'personal' => __('Personal Details'),
            'contact' => __('Contact Information'),
            'professional' => __('Professional Information'),
            'conference' => __('Conference Details'),
            'administrative' => __('Administrative'),
        ];
    }
}
