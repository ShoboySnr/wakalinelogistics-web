@extends('Admin::layout')

@section('title', 'Feature Suggestions')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">

    {{-- Header --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Feature Suggestions</h1>
            <p class="text-sm text-gray-500 mt-1">Review and respond to community feature requests</p>
        </div>
        <a href="/suggestions" target="_blank"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            Public Board
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-3 md:grid-cols-6 gap-3 mb-6">
        @foreach([
            ['label' => 'Total',       'value' => $stats['total'],        'color' => 'text-gray-900'],
            ['label' => 'Pending',     'value' => $stats['pending'],      'color' => 'text-amber-600'],
            ['label' => 'Under Review','value' => $stats['under_review'], 'color' => 'text-blue-600'],
            ['label' => 'Planned',     'value' => $stats['planned'],      'color' => 'text-purple-600'],
            ['label' => 'Completed',   'value' => $stats['completed'],    'color' => 'text-green-600'],
            ['label' => 'Declined',    'value' => $stats['declined'],     'color' => 'text-red-500'],
        ] as $stat)
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-2xl font-bold {{ $stat['color'] }}">{{ $stat['value'] }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $stat['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6 flex flex-col sm:flex-row gap-3">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search title or description..."
               class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm focus:ring-[#C1666B] focus:border-[#C1666B]">
        <select name="status" class="px-4 py-2 border border-gray-300 rounded-md text-sm focus:ring-[#C1666B] focus:border-[#C1666B]">
            <option value="all">All Statuses</option>
            @foreach(['pending' => 'Pending', 'under_review' => 'Under Review', 'planned' => 'Planned', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'declined' => 'Declined'] as $val => $label)
            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="category" class="px-4 py-2 border border-gray-300 rounded-md text-sm focus:ring-[#C1666B] focus:border-[#C1666B]">
            <option value="">All Categories</option>
            @foreach(['UI/UX', 'Integration', 'Automation', 'Reporting', 'Mobile', 'Other'] as $cat)
            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
        </select>
        <select name="sort_by" class="px-4 py-2 border border-gray-300 rounded-md text-sm focus:ring-[#C1666B] focus:border-[#C1666B]">
            <option value="upvotes"    {{ request('sort_by', 'upvotes') === 'upvotes'    ? 'selected' : '' }}>Sort: Top Voted</option>
            <option value="created_at" {{ request('sort_by') === 'created_at'            ? 'selected' : '' }}>Sort: Newest</option>
            <option value="status"     {{ request('sort_by') === 'status'                ? 'selected' : '' }}>Sort: Status</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-[#C1666B] text-white text-sm font-medium rounded-md hover:bg-[#a8555a]">
            Filter
        </button>
        @if(request()->hasAny(['search','status','category','sort_by']))
        <a href="{{ route('admin.suggestions') }}" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 text-center">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($suggestions->isEmpty())
        <div class="text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            <p class="text-sm">No suggestions found.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Suggestion</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Votes</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($suggestions as $s)
                    @php
                        $statusClasses = [
                            'pending'      => 'bg-gray-100 text-gray-600',
                            'under_review' => 'bg-blue-100 text-blue-700',
                            'planned'      => 'bg-purple-100 text-purple-700',
                            'in_progress'  => 'bg-amber-100 text-amber-700',
                            'completed'    => 'bg-green-100 text-green-700',
                            'declined'     => 'bg-red-100 text-red-600',
                        ];
                        $statusLabels = [
                            'pending'      => 'Pending',
                            'under_review' => 'Under Review',
                            'planned'      => 'Planned',
                            'in_progress'  => 'In Progress',
                            'completed'    => 'Completed',
                            'declined'     => 'Declined',
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 max-w-xs">
                            <p class="font-semibold text-gray-900 truncate">{{ $s->title }}</p>
                            <p class="text-xs text-gray-400 truncate mt-0.5">{{ Str::limit($s->description, 80) }}</p>
                            @if($s->admin_response)
                            <span class="inline-flex items-center gap-1 text-[10px] text-[#C1666B] mt-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                Response added
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $s->client->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $s->category }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-bold text-[#C1666B]">{{ $s->upvotes }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusClasses[$s->status] ?? $statusClasses['pending'] }}">
                                {{ $statusLabels[$s->status] ?? ucfirst($s->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-400 whitespace-nowrap text-xs">{{ $s->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.suggestions.show', $s->id) }}"
                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-[#C1666B] border border-[#C1666B] rounded-md hover:bg-[#C1666B] hover:text-white transition-colors">
                                Review
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($suggestions->hasPages())
        <div class="px-4 py-4 border-t border-gray-200">
            {{ $suggestions->links() }}
        </div>
        @endif
        @endif
    </div>

</div>
@endsection
