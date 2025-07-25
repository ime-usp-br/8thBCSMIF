@props([
    'type' => 'text',
    'name' => '',
    'label' => '',
    'placeholder' => '',
    'help' => '',
    'required' => false,
    'disabled' => false,
    'validation' => true,
    'icon' => null,
    'suffix' => null,
    'prefix' => null,
    'options' => [],
    'rows' => 3
])

@php
    $wireModel = $attributes->get('wire:model') ?: $attributes->get('wire:model.blur') ?: $attributes->get('wire:model.live');
    $hasError = isset($errors) && ($errors->has($name) || $errors->has(str_replace(['[', ']'], ['.', ''], $name)));
    $errorKey = isset($errors) && $errors->has($name) ? $name : str_replace(['[', ']'], ['.', ''], $name);
@endphp

<div class="space-y-2" x-data="enhancedInput({ 
    name: '{{ $name }}',
    required: {{ $required ? 'true' : 'false' }},
    validation: {{ $validation ? 'true' : 'false' }}
})">
    
    <!-- Label -->
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500 ml-1">*</span>
            @endif
        </label>
    @endif

    <!-- Help Text -->
    @if($help)
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $help }}</p>
    @endif

    <!-- Input Container -->
    <div class="relative">
        <!-- Prefix -->
        @if($prefix)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500 sm:text-sm">{{ $prefix }}</span>
            </div>
        @endif

        <!-- Icon -->
        @if($icon)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <div class="h-5 w-5 text-gray-400">
                    {!! $icon !!}
                </div>
            </div>
        @endif

        <!-- Input Field -->
        @if($type === 'select')
            <select 
                id="{{ $name }}"
                name="{{ $name }}"
                {{ $attributes->merge([
                    'class' => 'block w-full rounded-md shadow-sm transition-colors duration-200 focus:ring-2 focus:ring-offset-2 ' .
                               ($hasError 
                                   ? 'border-red-300 dark:border-red-600 text-red-900 dark:text-red-100 placeholder-red-300 dark:placeholder-red-400 focus:ring-red-500 focus:border-red-500 dark:focus:ring-red-400 dark:focus:border-red-400' 
                                   : 'border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-usp-blue-pri focus:border-usp-blue-pri dark:focus:ring-usp-blue-sec dark:focus:border-usp-blue-sec') .
                               ($icon || $prefix ? ' pl-10' : '') .
                               ($suffix ? ' pr-10' : ''),
                    'disabled' => $disabled
                ]) }}
                @if($validation && $wireModel) wire:model.blur="{{ str_replace(['wire:model.blur=', 'wire:model.live=', 'wire:model='], '', $wireModel) }}" @endif
                @if($required) required @endif>
                
                @if(!$required && empty($placeholder))
                    <option value="">{{ __('Select an option') }}</option>
                @endif
                
                @foreach($options as $value => $text)
                    <option value="{{ $value }}">{{ $text }}</option>
                @endforeach
            </select>

        @elseif($type === 'textarea')
            <textarea
                id="{{ $name }}"
                name="{{ $name }}"
                rows="{{ $rows }}"
                placeholder="{{ $placeholder }}"
                {{ $attributes->merge([
                    'class' => 'block w-full rounded-md shadow-sm transition-colors duration-200 focus:ring-2 focus:ring-offset-2 resize-none ' .
                               ($hasError 
                                   ? 'border-red-300 dark:border-red-600 text-red-900 dark:text-red-100 placeholder-red-300 dark:placeholder-red-400 focus:ring-red-500 focus:border-red-500 dark:focus:ring-red-400 dark:focus:border-red-400' 
                                   : 'border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-usp-blue-pri focus:border-usp-blue-pri dark:focus:ring-usp-blue-sec dark:focus:border-usp-blue-sec'),
                    'disabled' => $disabled
                ]) }}
                @if($validation && $wireModel) wire:model.blur="{{ str_replace(['wire:model.blur=', 'wire:model.live=', 'wire:model='], '', $wireModel) }}" @endif
                @if($required) required @endif
            ></textarea>

        @else
            <input 
                type="{{ $type }}"
                id="{{ $name }}"
                name="{{ $name }}"
                placeholder="{{ $placeholder }}"
                {{ $attributes->merge([
                    'class' => 'block w-full rounded-md shadow-sm transition-colors duration-200 focus:ring-2 focus:ring-offset-2 ' .
                               ($hasError 
                                   ? 'border-red-300 dark:border-red-600 text-red-900 dark:text-red-100 placeholder-red-300 dark:placeholder-red-400 focus:ring-red-500 focus:border-red-500 dark:focus:ring-red-400 dark:focus:border-red-400' 
                                   : 'border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-usp-blue-pri focus:border-usp-blue-pri dark:focus:ring-usp-blue-sec dark:focus:border-usp-blue-sec') .
                               ($icon || $prefix ? ' pl-10' : '') .
                               ($suffix ? ' pr-10' : ''),
                    'disabled' => $disabled
                ]) }}
                @if($validation && $wireModel) wire:model.blur="{{ str_replace(['wire:model.blur=', 'wire:model.live=', 'wire:model='], '', $wireModel) }}" @endif
                @if($required) required @endif
            />
        @endif

        <!-- Suffix -->
        @if($suffix)
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <span class="text-gray-500 sm:text-sm">{{ $suffix }}</span>
            </div>
        @endif

        <!-- Validation Icons -->
        @if($validation && $wireModel)
            <!-- Loading Spinner -->
            <div wire:loading wire:target="{{ str_replace(['wire:model.blur=', 'wire:model.live=', 'wire:model='], '', $wireModel) }}" 
                 class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <!-- Success Icon -->
            @if(!$hasError && $wireModel)
                <div wire:loading.remove wire:target="{{ str_replace(['wire:model.blur=', 'wire:model.live=', 'wire:model='], '', $wireModel) }}" 
                     class="absolute inset-y-0 right-0 pr-3 flex items-center"
                     x-show="validationState === 'success'">
                    <svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </div>
            @endif

            <!-- Error Icon -->
            @if($hasError)
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <svg class="h-4 w-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
            @endif
        @endif
    </div>

    <!-- Error Message -->
    @if($hasError && isset($errors))
        <p class="text-sm text-red-600 dark:text-red-400 flex items-center">
            <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            {{ $errors->first($errorKey) }}
        </p>
    @endif

    <!-- Character Count (for textarea) -->
    @if($type === 'textarea' && $attributes->has('maxlength'))
        <div class="flex justify-end">
            <span class="text-xs text-gray-500 dark:text-gray-400" 
                  x-data="{ count: $wire.get('{{ str_replace(['wire:model.blur=', 'wire:model.live=', 'wire:model='], '', $wireModel) }}')?.length || 0 }"
                  x-text="count + ' / {{ $attributes->get('maxlength') }}'">
            </span>
        </div>
    @endif
</div>

<script>
function enhancedInput(config) {
    return {
        validationState: null, // null, 'success', 'error'
        
        init() {
            // Listen for Livewire events to update validation state
            if (config.validation) {
                this.$watch('$wire.' + config.name, (value) => {
                    if (value && value.length > 0) {
                        this.validationState = 'success';
                    } else {
                        this.validationState = null;
                    }
                });
            }
        }
    }
}
</script>