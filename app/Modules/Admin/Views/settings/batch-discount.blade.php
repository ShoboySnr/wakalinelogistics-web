@extends('Admin::layout')

@section('title', 'Same-Zone Batch Discount')

@section('content')
<div class="px-4 sm:px-6 lg:px-0 max-w-4xl">

    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Same-Zone Batch Discount</h1>
        <p class="text-sm text-gray-500 mt-1">
            Reward clients for stacking deliveries into a zone a rider is already visiting.
        </p>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.batch-discount.update') }}" class="bg-white rounded-lg shadow p-6">
        @csrf

        <label class="flex items-center gap-3 mb-6 cursor-pointer">
            <input type="hidden" name="enabled" value="0">
            <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $enabled))
                   class="h-4 w-4 rounded border-gray-300 text-[#C1666B] focus:ring-[#C1666B]">
            <span class="text-sm font-medium text-gray-900">Enable same-zone batch discount</span>
        </label>

        <h2 class="text-sm font-semibold text-gray-900 mb-1">Discount bands</h2>
        <p class="text-xs text-gray-500 mb-3">
            Bands must not overlap, and only the last one may be left open-ended.
        </p>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase">
                        <th class="pb-2 pr-3 font-medium">Orders to zone (from)</th>
                        <th class="pb-2 pr-3 font-medium">Up to</th>
                        <th class="pb-2 pr-3 font-medium">Discount %</th>
                        <th class="pb-2 w-10"></th>
                    </tr>
                </thead>
                <tbody id="tierRows">
                    @php $rows = old('tiers', $tiers ?: [['min' => 2, 'max' => 6, 'percent' => 10]]); @endphp
                    @foreach($rows as $i => $tier)
                    <tr class="tier-row">
                        <td class="py-1.5 pr-3">
                            <input type="number" name="tiers[{{ $i }}][min]" value="{{ $tier['min'] ?? '' }}" min="2"
                                   class="w-28 px-2 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#C1666B]">
                        </td>
                        <td class="py-1.5 pr-3">
                            <input type="number" name="tiers[{{ $i }}][max]" value="{{ $tier['max'] ?? '' }}" min="2"
                                   placeholder="no limit"
                                   class="w-28 px-2 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#C1666B]">
                        </td>
                        <td class="py-1.5 pr-3">
                            <input type="number" name="tiers[{{ $i }}][percent]" value="{{ $tier['percent'] ?? '' }}"
                                   min="0" max="100" step="0.01"
                                   class="w-28 px-2 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#C1666B]">
                        </td>
                        <td class="py-1.5">
                            <button type="button" class="removeTier text-gray-400 hover:text-red-600 text-lg leading-none" title="Remove band">&times;</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <button type="button" id="addTier" class="mt-3 text-sm font-medium brand-accent-text hover:text-[#a8555a]">+ Add band</button>

        <p class="text-xs text-gray-500 mt-4">
            Leaving the table empty switches the discount off, even if the toggle above is on.
        </p>

        <div class="mt-6 pt-4 border-t border-gray-200 flex gap-3">
            <button type="submit" class="px-6 py-2 text-white rounded-md brand-accent-bg brand-accent-hover text-sm font-medium">
                Save settings
            </button>
            <a href="{{ route('admin.settings') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm">
                Back to settings
            </a>
        </div>
    </form>

</div>

<script>
(function () {
    const body = document.getElementById('tierRows');

    document.getElementById('addTier').addEventListener('click', function () {
        const index = body.querySelectorAll('.tier-row').length;
        const row = document.createElement('tr');
        row.className = 'tier-row';
        row.innerHTML = `
            <td class="py-1.5 pr-3"><input type="number" name="tiers[${index}][min]" min="2"
                class="w-28 px-2 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#C1666B]"></td>
            <td class="py-1.5 pr-3"><input type="number" name="tiers[${index}][max]" min="2" placeholder="no limit"
                class="w-28 px-2 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#C1666B]"></td>
            <td class="py-1.5 pr-3"><input type="number" name="tiers[${index}][percent]" min="0" max="100" step="0.01"
                class="w-28 px-2 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#C1666B]"></td>
            <td class="py-1.5"><button type="button" class="removeTier text-gray-400 hover:text-red-600 text-lg leading-none" title="Remove band">&times;</button></td>`;
        body.appendChild(row);
    });

    // Row indexes must stay contiguous after a removal or PHP receives gaps.
    body.addEventListener('click', function (e) {
        if (!e.target.classList.contains('removeTier')) return;
        e.target.closest('tr').remove();
        body.querySelectorAll('.tier-row').forEach(function (row, i) {
            row.querySelectorAll('input').forEach(function (input) {
                input.name = input.name.replace(/tiers\[\d+\]/, 'tiers[' + i + ']');
            });
        });
    });
})();
</script>
@endsection
