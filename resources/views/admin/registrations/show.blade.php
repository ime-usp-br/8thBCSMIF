<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Registration Details') }} - #{{ $registration->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Back to List Button -->
            <div class="mb-6">
                <a href="{{ route('admin.registrations.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    ← {{ __('Back to Registration List') }}
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Personal Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
                            {{ __('Personal Information') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Full Name') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->full_name }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Email') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->email }}</p>
                            </div>
                            @if($registration->phone_number)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Phone Number') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->phone_number }}</p>
                            </div>
                            @endif
                            @if($registration->nationality)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Nationality') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->nationality }}</p>
                            </div>
                            @endif
                            @if($registration->date_of_birth)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Date of Birth') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->date_of_birth->format('d/m/Y') }}</p>
                            </div>
                            @endif
                            @if($registration->gender)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Gender') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->gender }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Documents -->
                    @if($registration->cpf || $registration->rg_number || $registration->passport_number)
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
                            {{ __('Document Information') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @if($registration->document_country_origin)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Document Country of Origin') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->document_country_origin }}</p>
                            </div>
                            @endif
                            @if($registration->cpf)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('CPF') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->cpf }}</p>
                            </div>
                            @endif
                            @if($registration->rg_number)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('RG Number') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->rg_number }}</p>
                            </div>
                            @endif
                            @if($registration->passport_number)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Passport Number') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->passport_number }}</p>
                            </div>
                            @endif
                            @if($registration->passport_expiry_date)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Passport Expiry Date') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->passport_expiry_date->format('d/m/Y') }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Address -->
                    @if($registration->address_street || $registration->address_city || $registration->address_country)
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
                            {{ __('Address') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @if($registration->address_street)
                            <div class="md:col-span-2">
                                <p class="text-sm font-medium text-gray-500">{{ __('Street Address') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->address_street }}</p>
                            </div>
                            @endif
                            @if($registration->address_city)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('City') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->address_city }}</p>
                            </div>
                            @endif
                            @if($registration->address_state_province)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('State/Province') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->address_state_province }}</p>
                            </div>
                            @endif
                            @if($registration->address_country)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Country') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->address_country }}</p>
                            </div>
                            @endif
                            @if($registration->address_postal_code)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Postal Code') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->address_postal_code }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Professional Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
                            {{ __('Professional Information') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @if($registration->affiliation)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Affiliation') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->affiliation }}</p>
                            </div>
                            @endif
                            @if($registration->position)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Position') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->position }}</p>
                            </div>
                            @endif
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('ABE Member') }}</p>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ $registration->is_abe_member ? __('Yes') : __('No') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Event Participation -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
                            {{ __('Event Participation') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @if($registration->participation_format)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Participation Format') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->participation_format }}</p>
                            </div>
                            @endif
                            @if($registration->arrival_date)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Arrival Date') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->arrival_date->format('d/m/Y') }}</p>
                            </div>
                            @endif
                            @if($registration->departure_date)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Departure Date') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->departure_date->format('d/m/Y') }}</p>
                            </div>
                            @endif
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Transport from GRU Airport') }}</p>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ $registration->needs_transport_from_gru ? __('Yes') : __('No') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Transport from USP') }}</p>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ $registration->needs_transport_from_usp ? __('Yes') : __('No') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Requires Visa Letter') }}</p>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ $registration->requires_visa_letter ? __('Yes') : __('No') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Dietary Restrictions -->
                    @if($registration->dietary_restrictions || $registration->other_dietary_restrictions)
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
                            {{ __('Dietary Information') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @if($registration->dietary_restrictions)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Dietary Restrictions') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->dietary_restrictions }}</p>
                            </div>
                            @endif
                            @if($registration->other_dietary_restrictions)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Other Dietary Restrictions') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->other_dietary_restrictions }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Emergency Contact -->
                    @if($registration->emergency_contact_name || $registration->emergency_contact_phone)
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
                            {{ __('Emergency Contact') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @if($registration->emergency_contact_name)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Contact Name') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->emergency_contact_name }}</p>
                            </div>
                            @endif
                            @if($registration->emergency_contact_relationship)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Relationship') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->emergency_contact_relationship }}</p>
                            </div>
                            @endif
                            @if($registration->emergency_contact_phone)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Contact Phone') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->emergency_contact_phone }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Events and Fees -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
                            {{ __('Events and Fees') }}
                        </h3>
                        @if($registration->events->count() > 0)
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <h4 class="text-md font-medium text-gray-900 mb-2">{{ __('Registered Events') }}</h4>
                                <div class="space-y-2">
                                    @foreach($registration->events as $event)
                                        <div class="flex justify-between items-center bg-white rounded p-3 shadow-sm">
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $event->name }}</p>
                                                <p class="text-sm text-gray-500">{{ __('Code') }}: {{ $event->code }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-medium text-gray-900">
                                                    R$ {{ number_format($event->pivot->price_at_registration, 2, ',', '.') }}
                                                </p>
                                                <p class="text-xs text-gray-500">{{ __('Price at registration') }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <p class="text-gray-500 italic">{{ __('No events associated with this registration') }}</p>
                        @endif

                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">{{ __('Total Registration Fee') }}</span>
                                <span class="text-xl font-bold text-blue-600">
                                    R$ {{ number_format($registration->calculated_fee, 2, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
                            {{ __('Payment Information') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Payment Status') }}</p>
                                @php
                                    $statusColors = [
                                        'pending_payment' => 'bg-yellow-100 text-yellow-800',
                                        'paid_br' => 'bg-green-100 text-green-800',
                                        'paid_int' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                    ];
                                    $statusLabels = [
                                        'pending_payment' => __('Pending Payment'),
                                        'paid_br' => __('Paid (BR)'),
                                        'paid_int' => __('Paid (International)'),
                                        'cancelled' => __('Cancelled'),
                                    ];
                                @endphp
                                <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$registration->payment_status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $statusLabels[$registration->payment_status] ?? $registration->payment_status }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Registration Date') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            @if($registration->payment_uploaded_at)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Payment Proof Uploaded At') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->payment_uploaded_at->format('d/m/Y H:i') }}</p>
                            </div>
                            @endif
                            @if($registration->payment_proof_path)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Payment Proof') }}</p>
                                <a href="{{ route('admin.registrations.download-proof', $registration) }}" 
                                   class="mt-1 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    {{ __('Download Proof') }}
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Administrative Information -->
                    @if($registration->registration_category_snapshot || $registration->notes)
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
                            {{ __('Administrative Information') }}
                        </h3>
                        <div class="grid grid-cols-1 gap-4">
                            @if($registration->registration_category_snapshot)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Registration Category (at time of registration)') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->registration_category_snapshot }}</p>
                            </div>
                            @endif
                            @if($registration->notes)
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ __('Admin Notes') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $registration->notes }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>