@if($role === 'user')
    <div class="flex justify-end">
        <div class="max-w-[85%] bg-[#C1666B] text-white rounded-lg rounded-br-sm px-4 py-2.5 text-sm">
            {!! nl2br(e($text)) !!}
        </div>
    </div>
@else
    <div class="flex justify-start">
        <div class="ai-answer max-w-[90%] bg-gray-50 border border-gray-200 text-gray-800 rounded-lg rounded-bl-sm px-4 py-3 text-sm leading-relaxed">
            {!! $html ?? nl2br(e($text)) !!}
        </div>
    </div>
@endif
