<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Registration Details') }} - #{{ $registration->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">{{ __('Registration Information') }}</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p><strong>{{ __('Name') }}:</strong> {{ $registration->full_name }}</p>
                            <p><strong>{{ __('Email') }}:</strong> {{ $registration->email }}</p>
                            <p><strong>{{ __('Payment Status') }}:</strong> {{ $registration->payment_status }}</p>
                            <p><strong>{{ __('Total Fee') }}:</strong> R$ {{ number_format($registration->calculated_fee, 2, ',', '.') }}</p>
                        </div>
                        
                        <div>
                            <p><strong>{{ __('Registration Date') }}:</strong> {{ $registration->created_at->format('d/m/Y H:i') }}</p>
                            @if($registration->payment_proof_path)
                                <p><strong>{{ __('Payment Proof') }}:</strong> 
                                    <a href="{{ route('admin.registrations.download-proof', $registration) }}" 
                                       class="text-blue-600 hover:text-blue-800 underline">
                                        {{ __('Download') }}
                                    </a>
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6">
                        <h4 class="text-md font-medium mb-2">{{ __('Events') }}</h4>
                        @if($registration->events->count() > 0)
                            <ul class="list-disc list-inside">
                                @foreach($registration->events as $event)
                                    <li>{{ $event->name }} - R$ {{ number_format($event->pivot->price_at_registration, 2, ',', '.') }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-500">{{ __('No events associated with this registration') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>