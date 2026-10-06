{{-- Student status badge. Usage: @include('partials.status', ['status' => $student->status]) --}}
<span class="status status-{{ strtolower($status) }}">{{ $status }}</span>
