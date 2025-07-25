<?php

namespace Tests\Feature\Components;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_basic_file_upload()
    {
        $view = $this->blade(
            '<x-file-upload name="test_upload" label="Upload Test" />'
        );

        $view->assertSee('Upload Test');
        $view->assertSee('Drop files here or click to browse');
        $view->assertSeeInOrder(['input', 'type="file"', 'name="test_upload"']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_with_custom_accept_types()
    {
        $view = $this->blade(
            '<x-file-upload name="image_upload" accept=".jpg,.png" />'
        );

        $view->assertSee(':accept="accept"', false);
        $view->assertSee('.jpg,.png');
        $view->assertSee('Supported formats');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_with_custom_max_size()
    {
        $view = $this->blade(
            '<x-file-upload name="doc_upload" maxSize="5MB" />'
        );

        $view->assertSee('5MB');
        $view->assertSee('Max');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_required_field()
    {
        $view = $this->blade(
            '<x-file-upload name="required_upload" label="Required Upload" required />'
        );

        $view->assertSee('Required Upload');
        $view->assertSee('*');
        $view->assertSeeInOrder(['input', 'required']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_with_description()
    {
        $view = $this->blade(
            '<x-file-upload name="upload" description="Please upload your document" />'
        );

        $view->assertSee('Please upload your document');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_shows_existing_file_when_provided()
    {
        $existingFile = ['name' => 'document.pdf', 'path' => '/storage/docs/document.pdf'];

        $view = $this->blade(
            '<x-file-upload name="upload" :existingFile="$existingFile" />',
            compact('existingFile')
        );

        $view->assertSee('File uploaded successfully');
        $view->assertSee('View');
        $view->assertSee('Replace');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_alpine_js_functionality()
    {
        $view = $this->blade(
            '<x-file-upload name="upload" />'
        );

        $view->assertSee('x-data="fileUpload(', false);
        $view->assertSee('function fileUpload(config)', false);
        $view->assertSee('handleDrop($event)', false);
        $view->assertSee('handleFileSelect($event)', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_drag_and_drop_events()
    {
        $view = $this->blade(
            '<x-file-upload name="upload" />'
        );

        $view->assertSee('@drop="handleDrop($event)"', false);
        $view->assertSee('@dragover.prevent="dragOver = true"', false);
        $view->assertSee('@dragleave.prevent="dragOver = false"', false);
        $view->assertSee('@dragend.prevent="dragOver = false"', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_shows_file_preview_section()
    {
        $view = $this->blade(
            '<x-file-upload name="upload" preview />'
        );

        $view->assertSee('x-show="selectedFile"', false);
        $view->assertSee('x-text="fileName"', false);
        $view->assertSee('x-text="fileSize"', false);
        $view->assertSee('@click="removeFile()"', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_upload_progress_indicator()
    {
        $view = $this->blade(
            '<x-file-upload name="upload" />'
        );

        $view->assertSee('x-show="uploading"', false);
        $view->assertSee('Uploading...');
        $view->assertSee('x-text="uploadProgress + \'%\'"', false);
        $view->assertSee('bg-usp-blue-pri', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_shows_error_messages()
    {
        $view = $this->blade(
            '<x-file-upload name="upload" />'
        );

        $view->assertSee('x-show="errorMessage"', false);
        $view->assertSee('x-text="errorMessage"', false);
        $view->assertSee('text-red-600', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_image_preview_functionality()
    {
        $view = $this->blade(
            '<x-file-upload name="upload" preview />'
        );

        $view->assertSee('x-if="filePreview && fileType === \'image\'"', false);
        $view->assertSee(':src="filePreview"', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_pdf_icon_for_pdf_files()
    {
        $view = $this->blade(
            '<x-file-upload name="upload" />'
        );

        $view->assertSee('x-if="fileType === \'pdf\'"', false);
        $view->assertSee('bg-red-100', false);
        $view->assertSee('text-red-600', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_file_validation_logic()
    {
        $view = $this->blade(
            '<x-file-upload name="upload" />'
        );

        $view->assertSee('processFile(file)', false);
        $view->assertSee('parseSize(this.maxSize)', false);
        $view->assertSee('Invalid file type', false);
        $view->assertSee('File size exceeds maximum', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_file_utility_functions()
    {
        $view = $this->blade(
            '<x-file-upload name="upload" />'
        );

        $view->assertSee('formatFileSize(bytes)', false);
        $view->assertSee('getFileType(file)', false);
        $view->assertSee('generatePreview(file)', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_supports_download_route_for_existing_files()
    {
        $downloadRoute = 'https://example.com/download/file.pdf';

        $view = $this->blade(
            '<x-file-upload name="upload" :downloadRoute="$downloadRoute" />',
            compact('downloadRoute')
        );

        $view->assertSee('viewExistingFile()', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_usp_brand_colors()
    {
        $view = $this->blade(
            '<x-file-upload name="upload" />'
        );

        $view->assertSee('border-usp-blue-pri', false);
        $view->assertSee('bg-usp-blue-pri/5', false);
        $view->assertSee('hover:border-usp-blue-pri', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_supports_dark_mode()
    {
        $view = $this->blade(
            '<x-file-upload name="upload" />'
        );

        $view->assertSee('dark:border-gray-600', false);
        $view->assertSee('dark:bg-gray-700', false);
        $view->assertSee('dark:text-gray-100', false);
        $view->assertSee('dark:text-gray-400', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_file_replacement_functionality()
    {
        $view = $this->blade(
            '<x-file-upload name="upload" />'
        );

        $view->assertSee('@click="replaceFile()"', false);
        $view->assertSee('x-show="!existingFile || showUpload"', false);
        $view->assertSee('this.showUpload = true', false);
    }
}
