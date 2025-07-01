<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Registration Details') }} - #{{ $registration->id }}
            </h2>
            <div class="mt-2 sm:mt-0">
                @php
                    $statusColors = [
                        'pending_payment' => 'bg-yellow-100 text-yellow-800',
                        'pending_br_proof_approval' => 'bg-orange-100 text-orange-800',
                        'paid_br' => 'bg-green-100 text-green-800',
                        'invoice_sent_int' => 'bg-blue-100 text-blue-800',
                        'paid_int' => 'bg-green-100 text-green-800',
                        'free' => 'bg-purple-100 text-purple-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                    ];
                    $statusLabels = [
                        'pending_payment' => __('Pending Payment'),
                        'pending_br_proof_approval' => __('Pending BR Proof Approval'),
                        'paid_br' => __('Paid (BR)'),
                        'invoice_sent_int' => __('Invoice Sent (International)'),
                        'paid_int' => __('Paid (International)'),
                        'free' => __('Free'),
                        'cancelled' => __('Cancelled'),
                    ];
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$registration->payment_status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ $statusLabels[$registration->payment_status] ?? $registration->payment_status }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Display success message --}}
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif
            <!-- Back to List Button -->
            <div class="mb-6">
                <a href="{{ route('admin.registrations.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-usp-blue-pri border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-usp-blue-pri/90 focus:bg-usp-blue-pri/90 active:bg-usp-blue-pri focus:outline-none focus:ring-2 focus:ring-usp-blue-pri focus:ring-offset-2 transition ease-in-out duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('Back to Registration List') }}
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Header Section with USP Brand -->
                <div class="bg-gradient-to-r from-usp-blue-pri to-usp-blue-sec px-4 sm:px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-white">
                                {{ $registration->full_name }}
                            </h3>
                            <p class="text-usp-blue-sec/80 text-sm">
                                {{ __('Registration ID') }}: #{{ $registration->id }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-white font-bold text-lg">
                                R$ {{ number_format($registration->calculateCorrectTotalFee(), 2, ',', '.') }}
                            </p>
                            <p class="text-usp-blue-sec/80 text-xs">
                                {{ __('Total Fee') }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 sm:p-6 text-gray-900 dark:text-gray-100">
                    <!-- Personal Information -->
                    <div class="mb-8">
                        <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Personal Information') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('Participant basic information') }}
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Full Name') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->full_name }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Email') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->email }}</p>
                            </div>
                            @if($registration->phone_number)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Phone Number') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->phone_number }}</p>
                            </div>
                            @endif
                            @if($registration->nationality)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Nationality') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->nationality }}</p>
                            </div>
                            @endif
                            @if($registration->date_of_birth)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Date of Birth') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->date_of_birth->format('d/m/Y') }}</p>
                            </div>
                            @endif
                            @if($registration->gender)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Gender') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->gender }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Documents -->
                    @if($registration->cpf || $registration->rg_number || $registration->passport_number)
                    <div class="mb-8">
                        <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Document Information') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('Identity and travel documents') }}
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            @if($registration->document_country_origin)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Document Country of Origin') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->document_country_origin }}</p>
                            </div>
                            @endif
                            @if($registration->cpf)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('CPF') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->cpf }}</p>
                            </div>
                            @endif
                            @if($registration->rg_number)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('RG Number') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->rg_number }}</p>
                            </div>
                            @endif
                            @if($registration->passport_number)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Passport Number') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->passport_number }}</p>
                            </div>
                            @endif
                            @if($registration->passport_expiry_date)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Passport Expiry Date') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->passport_expiry_date->format('d/m/Y') }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Address -->
                    @if($registration->address_street || $registration->address_city || $registration->address_country)
                    <div class="mb-8">
                        <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Address') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('Residence information') }}
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            @if($registration->address_street)
                            <div class="md:col-span-2">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Street Address') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->address_street }}</p>
                            </div>
                            @endif
                            @if($registration->address_city)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('City') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->address_city }}</p>
                            </div>
                            @endif
                            @if($registration->address_state_province)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('State/Province') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->address_state_province }}</p>
                            </div>
                            @endif
                            @if($registration->address_country)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Country') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->address_country }}</p>
                            </div>
                            @endif
                            @if($registration->address_postal_code)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Postal Code') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->address_postal_code }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Professional Information -->
                    <div class="mb-8">
                        <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Professional Information') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('Academic and professional details') }}
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            @if($registration->affiliation)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Affiliation') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->affiliation }}</p>
                            </div>
                            @endif
                            @if($registration->position)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Position') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->position }}</p>
                            </div>
                            @endif
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('ABE Member') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $registration->is_abe_member ? __('Yes') : __('No') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Event Participation -->
                    <div class="mb-8">
                        <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Event Participation') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('Conference participation details') }}
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            @if($registration->participation_format)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Participation Format') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->participation_format }}</p>
                            </div>
                            @endif
                            @if($registration->arrival_date)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Arrival Date') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->arrival_date->format('d/m/Y') }}</p>
                            </div>
                            @endif
                            @if($registration->departure_date)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Departure Date') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->departure_date->format('d/m/Y') }}</p>
                            </div>
                            @endif
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Transport from GRU Airport') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $registration->needs_transport_from_gru ? __('Yes') : __('No') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Transport from USP') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $registration->needs_transport_from_usp ? __('Yes') : __('No') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Requires Visa Letter') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $registration->requires_visa_letter ? __('Yes') : __('No') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Dietary Restrictions -->
                    @if($registration->dietary_restrictions || $registration->other_dietary_restrictions)
                    <div class="mb-8">
                        <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Dietary Information') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('Special dietary requirements') }}
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            @if($registration->dietary_restrictions)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Dietary Restrictions') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->dietary_restrictions }}</p>
                            </div>
                            @endif
                            @if($registration->other_dietary_restrictions)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Other Dietary Restrictions') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->other_dietary_restrictions }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Emergency Contact -->
                    @if($registration->emergency_contact_name || $registration->emergency_contact_phone)
                    <div class="mb-8">
                        <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Emergency Contact') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('Emergency contact information') }}
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            @if($registration->emergency_contact_name)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Contact Name') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->emergency_contact_name }}</p>
                            </div>
                            @endif
                            @if($registration->emergency_contact_relationship)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Relationship') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->emergency_contact_relationship }}</p>
                            </div>
                            @endif
                            @if($registration->emergency_contact_phone)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Contact Phone') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $registration->emergency_contact_phone }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Events and Fees -->
                    <div class="mb-8">
                        <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Events and Fees') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('Conference events and pricing information') }}
                            </p>
                        </div>
                        @if($registration->events->count() > 0)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 sm:p-6 mb-6">
                                <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Registered Events') }}</h4>
                                <div class="space-y-3">
                                    @foreach($registration->events as $event)
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-100 dark:border-gray-600">
                                            <div class="mb-2 sm:mb-0">
                                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $event->name }}</p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-usp-blue-sec/20 text-usp-blue-pri">
                                                        {{ $event->code }}
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="text-left sm:text-right">
                                                <p class="font-bold text-lg text-gray-900 dark:text-gray-100">
                                                    R$ {{ number_format($event->pivot->price_at_registration, 2, ',', '.') }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Price at registration') }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 text-center">
                                <p class="text-gray-500 dark:text-gray-400 italic">{{ __('No events associated with this registration') }}</p>
                            </div>
                        @endif

                        <div class="bg-gradient-to-r from-usp-blue-pri to-usp-blue-sec rounded-lg p-4 sm:p-6 text-white">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center">
                                <div class="mb-2 sm:mb-0">
                                    <span class="text-lg font-semibold">{{ __('Total Registration Fee') }}</span>
                                    <p class="text-sm text-usp-blue-sec/80 mt-1">{{ __('All events combined') }}</p>
                                </div>
                                <span class="text-2xl font-bold">
                                    R$ {{ number_format($registration->calculateCorrectTotalFee(), 2, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="mb-8">
                        <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Payment Information') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('Payment status and proof details') }}
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            <div class="sm:col-span-2 lg:col-span-1">
                                <p class="text-sm font-medium text-gray-500 mb-2">{{ __('Payment Status') }}</p>
                                @php
                                    $statusColors = [
                                        'pending_payment' => 'bg-yellow-100 text-yellow-800',
                                        'pending_br_proof_approval' => 'bg-orange-100 text-orange-800',
                                        'paid_br' => 'bg-green-100 text-green-800',
                                        'invoice_sent_int' => 'bg-blue-100 text-blue-800',
                                        'paid_int' => 'bg-green-100 text-green-800',
                                        'free' => 'bg-purple-100 text-purple-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                    ];
                                    $statusLabels = [
                                        'pending_payment' => __('Pending Payment'),
                                        'pending_br_proof_approval' => __('Pending BR Proof Approval'),
                                        'paid_br' => __('Paid (BR)'),
                                        'invoice_sent_int' => __('Invoice Sent (International)'),
                                        'paid_int' => __('Paid (International)'),
                                        'free' => __('Free'),
                                        'cancelled' => __('Cancelled'),
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium {{ $statusColors[$registration->payment_status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $statusLabels[$registration->payment_status] ?? $registration->payment_status }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Registration Date') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $registration->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            @if($registration->payment_uploaded_at)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Payment Proof Uploaded At') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $registration->payment_uploaded_at->format('d/m/Y H:i') }}</p>
                            </div>
                            @endif
                        </div>
                        
                        @if($registration->payment_proof_path)
                        <div class="mt-6 bg-gray-50 dark:bg-gray-700 rounded-lg p-4 sm:p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                <div class="mb-4 sm:mb-0">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Payment Proof') }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('Download payment verification document') }}</p>
                                </div>
                                <a href="{{ route('admin.registrations.download-proof', $registration) }}" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-usp-blue-pri hover:bg-usp-blue-pri/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-usp-blue-pri transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    {{ __('Download Proof') }}
                                </a>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Payment Status Update Form -->
                        <div class="mt-6 bg-gray-50 dark:bg-gray-700 rounded-lg p-4 sm:p-6">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                <div class="flex-shrink-0">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Update Payment Status') }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('Change the payment status for this registration') }}</p>
                                </div>
                                <form method="POST" action="{{ route('admin.registrations.update-status', $registration) }}" class="w-full lg:w-auto">
                                    @csrf
                                    @method('PATCH')
                                    <div class="flex flex-col gap-3">
                                        <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center min-w-0 lg:min-w-96">
                                            <div class="flex-1 min-w-0">
                                                <label for="payment_status" class="sr-only">{{ __('Payment Status') }}</label>
                                                <select name="payment_status" id="payment_status" 
                                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-usp-blue-pri focus:ring-usp-blue-pri text-sm transition-colors duration-200 hover:border-gray-400 disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <option value="pending_payment" {{ $registration->payment_status === 'pending_payment' ? 'selected' : '' }}>
                                                        {{ __('Pending Payment') }}
                                                    </option>
                                                    <option value="pending_br_proof_approval" {{ $registration->payment_status === 'pending_br_proof_approval' ? 'selected' : '' }}>
                                                        {{ __('Pending BR Proof Approval') }}
                                                    </option>
                                                    <option value="paid_br" {{ $registration->payment_status === 'paid_br' ? 'selected' : '' }}>
                                                        {{ __('Paid (BR)') }}
                                                    </option>
                                                    <option value="invoice_sent_int" {{ $registration->payment_status === 'invoice_sent_int' ? 'selected' : '' }}>
                                                        {{ __('Invoice Sent (International)') }}
                                                    </option>
                                                    <option value="paid_int" {{ $registration->payment_status === 'paid_int' ? 'selected' : '' }}>
                                                        {{ __('Paid (International)') }}
                                                    </option>
                                                    <option value="free" {{ $registration->payment_status === 'free' ? 'selected' : '' }}>
                                                        {{ __('Free') }}
                                                    </option>
                                                    <option value="cancelled" {{ $registration->payment_status === 'cancelled' ? 'selected' : '' }}>
                                                        {{ __('Cancelled') }}
                                                    </option>
                                                </select>
                                            </div>
                                            <button type="submit" 
                                                    class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-usp-blue-pri hover:bg-usp-blue-pri/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-usp-blue-pri transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap">
                                                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                {{ __('Update Status') }}
                                            </button>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="checkbox" name="send_notification" id="send_notification" value="1" checked
                                                   class="h-4 w-4 text-usp-blue-pri focus:ring-usp-blue-pri border-gray-300 rounded">
                                            <label for="send_notification" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                                {{ __('Send email notification to participant') }}
                                            </label>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Administrative Information -->
                    @if($registration->registration_category_snapshot || $registration->notes)
                    <div class="mb-8">
                        <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Administrative Information') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('Internal registration details and notes') }}
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 sm:p-6">
                            <div class="grid grid-cols-1 gap-4 sm:gap-6">
                                @if($registration->registration_category_snapshot)
                                <div>
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Registration Category (at time of registration)') }}</p>
                                    <div class="bg-white dark:bg-gray-800 rounded-md p-3 border border-gray-200 dark:border-gray-600">
                                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $registration->registration_category_snapshot }}</p>
                                    </div>
                                </div>
                                @endif
                                @if($registration->notes)
                                <div>
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Admin Notes') }}</p>
                                    <div class="bg-white dark:bg-gray-800 rounded-md p-3 border border-gray-200 dark:border-gray-600">
                                        <p class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $registration->notes }}</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>