{{-- A balance with words, not colour alone: "Owes", "Paid up" or "Credit". --}}
@if ($balance > 0)
    <span class="balance balance-owing">{{ number_format($balance, 2) }} <span class="balance-label">owing</span></span>
@elseif ($balance < 0)
    <span class="balance balance-credit">{{ number_format(abs($balance), 2) }} <span class="balance-label">credit</span></span>
@else
    <span class="balance balance-clear">Paid up</span>
@endif
