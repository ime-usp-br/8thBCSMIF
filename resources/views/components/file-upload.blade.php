@props([
    'name' => 'file',
    'accept' => '.jpg,.jpeg,.png,.pdf',
    'maxSize' => '10MB',
    'label' => '',
    'description' => '',
    'required' => false,
    'preview' => true,
    'existingFile' => null,
    'downloadRoute' => null
])

<div class="space-y-4" x-data="fileUpload({
    name: '{{ $name }}',
    accept: '{{ $accept }}',
    maxSize: '{{ $maxSize }}',
    preview: {{ $preview ? 'true' : 'false' }},
    existingFile: @js($existingFile),
    downloadRoute: @js($downloadRoute)
})">
    @if($label)
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    @if($description)
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
    @endif

    <!-- Existing File Display -->
    <div x-show="existingFile && !selectedFile" class="mb-4">
        <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                <span class="text-green-800 dark:text-green-300 font-medium">
                    {{ __('File uploaded successfully') }}
                </span>
            </div>
            <div class="flex items-center space-x-2">
                <button 
                    type="button"
                    @click="viewExistingFile()"
                    class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    {{ __('View') }}
                </button>
                <button 
                    type="button"
                    @click="replaceFile()"
                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    {{ __('Replace') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Upload Area -->
    <div x-show="!existingFile || showUpload" class="space-y-4">
        <!-- Drag and Drop Area -->
        <div 
            @drop="handleDrop($event)"
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @dragend.prevent="dragOver = false"
            :class="dragOver ? 'border-usp-blue-pri bg-usp-blue-pri/5' : 'border-gray-300 dark:border-gray-600'"
            class="relative border-2 border-dashed rounded-lg p-6 transition-colors duration-200 hover:border-usp-blue-pri hover:bg-usp-blue-pri/5 dark:hover:bg-usp-blue-pri/10">
            
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                
                <div class="mt-4">
                    <label for="{{ $name }}_input" class="cursor-pointer">
                        <span class="mt-2 block text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ __('Drop files here or click to browse') }}
                        </span>
                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Supported formats') }}: {{ $accept }} ({{ __('Max') }}: {{ $maxSize }})
                        </span>
                    </label>
                    <input 
                        type="file" 
                        id="{{ $name }}_input"
                        name="{{ $name }}"
                        :accept="accept"
                        @change="handleFileSelect($event)"
                        class="sr-only"
                        {{ $attributes }}
                        @if($required) required @endif
                    >
                </div>
            </div>
        </div>

        <!-- File Preview -->
        <div x-show="selectedFile" class="mt-4">
            <div class="border border-green-200 dark:border-green-600 rounded-lg p-4 bg-green-50 dark:bg-green-900/20">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <!-- Success Icon -->
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <!-- Image Preview -->
                                <template x-if="filePreview && fileType === 'image'">
                                    <img :src="filePreview" class="h-16 w-16 object-cover rounded-lg border border-gray-300">
                                </template>
                                
                                <!-- PDF Icon -->
                                <template x-if="fileType === 'pdf'">
                                    <div class="h-16 w-16 bg-red-100 dark:bg-red-900/20 rounded-lg flex items-center justify-center">
                                        <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </template>
                                
                                <!-- Generic File Icon -->
                                <template x-if="fileType === 'other'">
                                    <div class="h-16 w-16 bg-gray-100 dark:bg-gray-900/20 rounded-lg flex items-center justify-center">
                                        <svg class="h-8 w-8 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </template>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-green-800 dark:text-green-300">{{ __('File selected successfully') }}</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="fileName"></p>
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="fileSize"></p>
                            </div>
                        </div>
                    </div>
                    
                    <button 
                        type="button"
                        @click="removeFile()"
                        class="flex-shrink-0 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                        title="{{ __('Remove file') }}">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                
                <!-- Upload Progress -->
                <div x-show="uploading" class="mt-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">{{ __('Uploading...') }}</span>
                        <span class="text-gray-600 dark:text-gray-400" x-text="uploadProgress + '%'"></span>
                    </div>
                    <div class="mt-1 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                        <div 
                            class="bg-usp-blue-pri h-2 rounded-full transition-all duration-300"
                            :style="'width: ' + uploadProgress + '%'">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Messages -->
    <div x-show="errorMessage" x-text="errorMessage" class="text-red-600 dark:text-red-400 text-sm mt-2"></div>
</div>

<script>
function fileUpload(config) {
    return {
        dragOver: false,
        selectedFile: null,
        fileName: '',
        fileSize: '',
        fileType: '',
        filePreview: null,
        uploading: false,
        uploadProgress: 0,
        errorMessage: '',
        accept: config.accept,
        maxSize: config.maxSize,
        preview: config.preview,
        existingFile: config.existingFile,
        downloadRoute: config.downloadRoute,
        showUpload: false,
        required: {{ $required ? 'true' : 'false' }},

        handleDrop(e) {
            e.preventDefault();
            this.dragOver = false;
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.processFile(files[0]);
            }
        },

        handleFileSelect(e) {
            const files = e.target.files;
            if (files.length > 0) {
                this.processFile(files[0]);
            }
        },

        processFile(file) {
            this.errorMessage = '';
            
            // Validate file type
            const allowedTypes = this.accept.split(',').map(type => type.trim());
            const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
            
            if (!allowedTypes.includes(fileExtension)) {
                this.errorMessage = `{{ __('Invalid file type. Allowed types') }}: ${this.accept}`;
                return;
            }
            
            // Validate file size (convert maxSize to bytes)
            const maxSizeBytes = this.parseSize(this.maxSize);
            if (file.size > maxSizeBytes) {
                this.errorMessage = `{{ __('File size exceeds maximum allowed size of') }} ${this.maxSize}`;
                return;
            }
            
            this.selectedFile = file;
            this.fileName = file.name;
            this.fileSize = this.formatFileSize(file.size);
            this.fileType = this.getFileType(file);
            
            if (this.preview && this.fileType === 'image') {
                this.generatePreview(file);
            }
        },

        generatePreview(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.filePreview = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        getFileType(file) {
            if (file.type.startsWith('image/')) return 'image';
            if (file.type === 'application/pdf') return 'pdf';
            return 'other';
        },

        parseSize(sizeStr) {
            const units = { B: 1, KB: 1024, MB: 1024 * 1024, GB: 1024 * 1024 * 1024 };
            const match = sizeStr.match(/^(\d+)\s*(B|KB|MB|GB)$/i);
            if (match) {
                return parseInt(match[1]) * units[match[2].toUpperCase()];
            }
            return 10 * 1024 * 1024; // Default 10MB
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        removeFile() {
            this.selectedFile = null;
            this.fileName = '';
            this.fileSize = '';
            this.fileType = '';
            this.filePreview = null;
            this.errorMessage = '';
            this.uploadProgress = 0;
            this.uploading = false;
            
            // Reset file input
            const input = document.getElementById(config.name + '_input');
            if (input) input.value = '';
        },

        replaceFile() {
            this.showUpload = true;
        },

        viewExistingFile() {
            if (this.downloadRoute) {
                window.open(this.downloadRoute, '_blank');
            }
        },

        // Validate form before submission
        validateBeforeSubmit() {
            // Clear any previous error messages
            this.errorMessage = '';
            
            // If it's required and we don't have a file selected or existing file
            if (this.required && !this.selectedFile && !this.existingFile) {
                this.errorMessage = '{{ __("Please select a file") }}';
                return false;
            }
            
            // If we have a selected file, it's valid
            if (this.selectedFile) {
                return true;
            }
            
            // If we have an existing file and no new file selected, it's valid
            if (this.existingFile && !this.selectedFile) {
                return true;
            }
            
            return true;
        },

        // Method to get validation state for form submission
        isValid() {
            return this.validateBeforeSubmit();
        },

        // Method to check if a file is available (either selected or existing)
        hasFile() {
            return this.selectedFile || this.existingFile;
        }
    }
}
</script>