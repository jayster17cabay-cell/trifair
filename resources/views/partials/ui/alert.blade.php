@php
    $type = $type ?? 'navy';
    $message = $message ?? null;
    $messages = $messages ?? [];
    $heading = $heading ?? null;
    $dismissible = $dismissible ?? true;

    $icons = [
        'success' => 'bi-check-circle-fill',
        'danger' => 'bi-exclamation-triangle-fill',
        'amber' => 'bi-exclamation-circle-fill',
        'navy' => 'bi-info-circle-fill',
    ];
    $icon = $icons[$type] ?? $icons['navy'];
@endphp

@if (filled($message) || count($messages) > 0)
    <div class="tw-alert tw-alert-{{ $type }}" role="alert" aria-live="{{ $type === 'danger' ? 'assertive' : 'polite' }}">
        <i class="bi {{ $icon }} tw-alert-icon" aria-hidden="true"></i>
        @if (count($messages) > 0)
            <div>
                @if ($heading)
                    <strong>{{ $heading }}</strong>
                @endif
                <ul class="{{ $heading ? 'mt-1 ' : '' }}list-inside list-disc space-y-0.5">
                    @foreach ($messages as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        @else
            <span>{{ $message }}</span>
        @endif
        @if ($dismissible)
            <button type="button" class="tw-alert-close" data-tw-dismiss aria-label="Dismiss">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        @endif
    </div>
@endif
