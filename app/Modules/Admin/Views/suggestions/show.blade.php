@extends('Admin::layout')

@section('title', 'Suggestion: ' . Str::limit($suggestion->title, 40))

@section('content')
<div class="px-4 sm:px-6 lg:px-0">

    {{-- Header --}}
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.suggestions') }}" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div class="flex-1 min-w-0">
            <h1 class="text-xl font-bold text-gray-900 truncate">{{ $suggestion->title }}</h1>
            <p class="text-xs text-gray-400 mt-0.5">Submitted {{ $suggestion->created_at->format('d M Y, g:ia') }}</p>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: main content --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Suggestion body --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center gap-3 mb-4">
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
                    <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full {{ $statusClasses[$suggestion->status] ?? $statusClasses['pending'] }}">
                        {{ $statusLabels[$suggestion->status] ?? ucfirst($suggestion->status) }}
                    </span>
                    <span class="inline-flex px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                        {{ $suggestion->category }}
                    </span>
                    <span class="ml-auto flex items-center gap-1 text-sm font-bold text-[#C1666B]">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l8 8H4z"/></svg>
                        {{ $suggestion->upvotes }} vote{{ $suggestion->upvotes !== 1 ? 's' : '' }}
                    </span>
                </div>
                <p class="text-gray-800 leading-relaxed whitespace-pre-wrap">{{ $suggestion->description }}</p>

                @if($suggestion->reward_given)
                <div class="mt-4 bg-green-50 border border-green-200 rounded-lg px-4 py-3 flex items-center gap-2 text-sm text-green-800">
                    <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ₦{{ number_format($suggestion->reward_amount) }} reward credited to {{ $suggestion->client->name ?? 'client' }}
                </div>
                @endif
            </div>

            {{-- Admin response --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Admin Response</h3>
                @if($suggestion->admin_response)
                <div class="bg-[#C1666B]/5 border-l-4 border-[#C1666B] rounded-r-lg px-4 py-3 mb-4 text-sm text-gray-700 whitespace-pre-wrap">{{ $suggestion->admin_response }}</div>
                @endif
                <form method="POST" action="{{ route('admin.suggestions.update-status', $suggestion->id) }}">
                    @csrf
                    <input type="hidden" name="status" value="{{ $suggestion->status }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        {{ $suggestion->admin_response ? 'Update response' : 'Add a response' }}
                        <span class="text-gray-400 font-normal">(visible to the client on the public board)</span>
                    </label>
                    <textarea name="admin_response" rows="4"
                              placeholder="Write your response here..."
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-[#C1666B] focus:border-[#C1666B] resize-none">{{ old('admin_response', $suggestion->admin_response) }}</textarea>
                    <div class="mt-3 flex justify-end">
                        <button type="submit" class="px-5 py-2 bg-[#C1666B] text-white text-sm font-semibold rounded-lg hover:bg-[#a8555a] transition-colors">
                            Save Response
                        </button>
                    </div>
                </form>
            </div>

            {{-- Voters --}}
            @if($suggestion->votes->isNotEmpty())
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">
                    Who Voted ({{ $suggestion->votes_count }})
                </h3>
                <div class="space-y-2">
                    @foreach($suggestion->votes as $vote)
                    <div class="flex items-center gap-3 text-sm">
                        <div class="w-7 h-7 rounded-full bg-[#C1666B]/10 flex items-center justify-center shrink-0">
                            <span class="text-[#C1666B] font-bold text-xs">{{ strtoupper(substr($vote->client->name ?? 'U', 0, 1)) }}</span>
                        </div>
                        <span class="text-gray-700">{{ $vote->client->name ?? 'Unknown' }}</span>
                        <span class="ml-auto text-xs text-gray-400">{{ $vote->created_at->format('d M Y') }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>

        {{-- Right: actions sidebar --}}
        <div class="space-y-5">

            {{-- Submitter --}}
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Submitted by</h3>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-[#C1666B]/10 flex items-center justify-center shrink-0">
                        <span class="text-[#C1666B] font-bold text-sm">{{ strtoupper(substr($suggestion->client->name ?? 'U', 0, 1)) }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $suggestion->client->name ?? 'Unknown' }}</p>
                        <p class="text-xs text-gray-400">{{ $suggestion->client->email ?? '' }}</p>
                    </div>
                </div>
                @if($suggestion->reviewed_at)
                <p class="text-xs text-gray-400 mt-3">Reviewed {{ $suggestion->reviewed_at->format('d M Y') }}</p>
                @endif
                @if($suggestion->completed_at)
                <p class="text-xs text-green-600 mt-1">Completed {{ $suggestion->completed_at->format('d M Y') }}</p>
                @endif
            </div>

            {{-- Change status --}}
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Change Status</h3>
                <form method="POST" action="{{ route('admin.suggestions.update-status', $suggestion->id) }}">
                    @csrf
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-[#C1666B] focus:border-[#C1666B] mb-3">
                        @foreach($statusLabels as $val => $label)
                        <option value="{{ $val }}" {{ $suggestion->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full py-2 bg-gray-800 text-white text-sm font-semibold rounded-lg hover:bg-gray-700 transition-colors">
                        Update Status
                    </button>
                </form>
            </div>

            {{-- Award reward --}}
            @if(!$suggestion->reward_given)
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Award Reward</h3>
                <p class="text-xs text-gray-500 mb-3">Credit the submitter's delivery balance as a thank-you for an implemented idea.</p>
                <form method="POST" action="{{ route('admin.suggestions.award-reward', $suggestion->id) }}">
                    @csrf
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-sm text-gray-600 shrink-0">₦</span>
                        <input type="number" name="reward_amount" min="1" placeholder="e.g. 2000"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-[#C1666B] focus:border-[#C1666B]">
                    </div>
                    <button type="submit"
                            onclick="return confirm('Award this reward to {{ $suggestion->client->name ?? 'this client' }}?')"
                            class="w-full py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-colors">
                        Give Reward
                    </button>
                </form>
            </div>
            @else
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                <p class="text-sm text-green-800 font-semibold">₦{{ number_format($suggestion->reward_amount) }} rewarded</p>
                <p class="text-xs text-green-600 mt-0.5">Already credited</p>
            </div>
            @endif

            {{-- Danger zone --}}
            <div class="bg-white rounded-lg shadow p-5 border border-red-100">
                <h3 class="text-sm font-semibold text-red-500 uppercase tracking-wider mb-3">Danger Zone</h3>
                <form method="POST" action="{{ route('admin.suggestions.destroy', $suggestion->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            onclick="return confirm('Permanently delete this suggestion?')"
                            class="w-full py-2 bg-red-50 text-red-600 text-sm font-semibold rounded-lg border border-red-200 hover:bg-red-100 transition-colors">
                        Delete Suggestion
                    </button>
                </form>
            </div>

        </div>
    </div>

</div>

@php
    // Make statusLabels available in the form above
    $statusLabels = [
        'pending'      => 'Pending',
        'under_review' => 'Under Review',
        'planned'      => 'Planned',
        'in_progress'  => 'In Progress',
        'completed'    => 'Completed',
        'declined'     => 'Declined',
    ];
@endphp
@endsection
