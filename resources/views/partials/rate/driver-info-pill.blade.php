{{--
    DriverInfoPill — rounded pill/chip showing the driver's identity.

    Navy avatar circle + "Your driver" label above the driver's full name,
    wrapped in a light gray rounded-pill container. No initials are shown.

    Usage:
        @include('partials.rate.driver-info-pill', ['driverName' => $operator->user->name])

    Styles: `.driver-pill`, `.driver-avatar`, `.driver-meta`, `.driver-label`,
    `.driver-name` (in resources/css/tailwind.css).
--}}
@php
    $driverName = $driverName ?? ($operator->user->name ?? '');
@endphp
<div class="driver-pill" role="group" aria-label="Your driver: {{ $driverName }}">
    <span class="driver-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
    <span class="driver-meta">
        <span class="driver-label">Your driver</span>
        <span class="driver-name">{{ $driverName }}</span>
    </span>
</div>
