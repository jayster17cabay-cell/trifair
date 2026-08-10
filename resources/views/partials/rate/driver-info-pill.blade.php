{{--
    DriverInfoPill — rounded pill/chip showing the driver's identity.

    Circular navy avatar with gold initials + "Your driver" label above the
    driver's full name, wrapped in a light gray rounded-pill container.

    Usage:
        @include('partials.rate.driver-info-pill', ['driverName' => $operator->user->name])

    Styles: `.driver-pill`, `.driver-avatar`, `.driver-meta`, `.driver-label`,
    `.driver-name` (in resources/css/tailwind.css).
--}}
@php
    $driverName = $driverName ?? ($operator->user->name ?? '');
    $nameParts = preg_split('/\s+/', trim($driverName));
    if (count($nameParts) >= 2) {
        $initials = strtoupper(mb_substr($nameParts[0], 0, 1) . mb_substr(end($nameParts), 0, 1));
    } elseif (count($nameParts) === 1) {
        $initials = strtoupper(mb_substr($driverName, 0, 2));
    } else {
        $initials = '';
    }
@endphp
<div class="driver-pill" role="group" aria-label="Your driver: {{ $driverName }}">
    <span class="driver-avatar" aria-hidden="true">{{ $initials }}</span>
    <span class="driver-meta">
        <span class="driver-label">Your driver</span>
        <span class="driver-name">{{ $driverName }}</span>
    </span>
</div>
