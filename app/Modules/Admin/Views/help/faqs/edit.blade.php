@extends('Admin::layout')

@section('title', 'Edit FAQ')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.help.faqs') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Edit FAQ</h1>
        </div>
    </div>

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('admin.help.faqs.update', $faq->id) }}" method="POST" class="bg-white rounded-lg shadow p-6">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Question *</label>
                <input type="text" name="question" value="{{ old('question', $faq->question) }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Answer *</label>
                <textarea id="answer-editor" name="answer" rows="6"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">{{ old('answer', $faq->answer) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                    <input type="text" name="category" value="{{ old('category', $faq->category) }}" required
                        list="categories-list"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                    <datalist id="categories-list">
                        @foreach($categories as $cat)
                        <option value="{{ $cat }}">
                        @endforeach
                    </datalist>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $faq->sort_order) }}" min="0"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                </div>
            </div>

            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $faq->is_active) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                <span class="ml-2 text-sm text-gray-700">Active (visible to clients)</span>
            </label>
        </div>

        <div class="mt-8 flex gap-3">
            <button type="submit" class="px-6 py-2 text-white rounded-md brand-accent-bg brand-accent-hover">
                Update FAQ
            </button>
            <a href="{{ route('admin.help.faqs') }}" class="px-6 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                Cancel
            </a>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script src="https://cdn.tiny.cloud/1/bx4hxtn8dh14nwvyw0bhgez4j7siz2ukyylhlpm75kxtpfg5/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        tinymce.init({
            selector: '#answer-editor',
            height: 400,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic underline strikethrough | ' +
                      'alignleft aligncenter alignright alignjustify | ' +
                      'bullist numlist outdent indent | link image | ' +
                      'forecolor backcolor | removeformat | code | help',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
            image_advtab: true,
            link_default_target: '_blank',
            link_assume_external_targets: true,
            relative_urls: false,
            remove_script_host: false,
            convert_urls: true,
        });

        document.querySelector('form').addEventListener('submit', function(e) {
            tinymce.triggerSave();
            const content = (tinymce.get('answer-editor').getContent({ format: 'text' }) || '').trim();
            if (!content) {
                e.preventDefault();
                let err = document.getElementById('answer-error');
                if (!err) {
                    err = document.createElement('p');
                    err.id = 'answer-error';
                    err.className = 'mt-1 text-sm text-red-600';
                    err.textContent = 'Answer is required.';
                    document.querySelector('.tox-tinymce').after(err);
                }
            }
        });
    });
</script>
@endpush
