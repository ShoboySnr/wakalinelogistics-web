@extends('Admin::layout')

@section('title', 'FAQs')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('admin.help.tickets') }}" class="text-sm text-gray-500 hover:text-gray-700">Support Tickets</a>
                <span class="text-gray-400">/</span>
                <span class="text-sm font-medium text-gray-900">FAQs</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Frequently Asked Questions</h1>
            <p class="text-sm text-gray-500 mt-1">Manage FAQ content shown to clients</p>
        </div>
        <a href="{{ route('admin.help.faqs.create') }}" class="px-4 py-2 text-center text-white rounded-md brand-accent-bg brand-accent-hover whitespace-nowrap">
            Add New FAQ
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    @if($categories->isNotEmpty())
    <div class="flex flex-wrap gap-2 mb-6">
        <span class="text-sm text-gray-600 self-center">Filter:</span>
        @foreach($categories as $cat)
        <span class="px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">{{ $cat }}</span>
        @endforeach
    </div>
    @endif

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Question</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($faqs as $faq)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900 max-w-md">{{ $faq->question }}</p>
                            <p class="text-xs text-gray-500 mt-1 truncate max-w-md">{{ Str::limit($faq->answer, 100) }}</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-block px-2 py-1 text-xs font-medium bg-blue-50 text-blue-700 rounded-full">{{ $faq->category }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $faq->sort_order }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $faq->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $faq->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.help.faqs.edit', $faq->id) }}" class="text-[#C1666B] hover:text-[#A85559]">Edit</a>
                                <form action="{{ route('admin.help.faqs.delete', $faq->id) }}" method="POST" onsubmit="return confirm('Delete this FAQ?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-700">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                            No FAQs found. <a href="{{ route('admin.help.faqs.create') }}" class="text-[#C1666B] hover:underline">Create the first one.</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
