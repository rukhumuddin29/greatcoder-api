<x-mail::message>
{!! $body !!}

<br>
<hr style="border:none; border-top:1px solid #eee; margin: 20px 0;">

<div style="font-family: Arial, sans-serif; color: #333;">
@if($user)
<div style="font-size: 16px; font-weight: bold; margin-bottom: 2px;">{{ $user->name }}</div>
<div style="font-size: 14px; font-weight: bold; color: #000; margin-bottom: 10px;">
{{ $user->designation }} @if($user->department) - {{ $user->department }} @endif
</div>
@endif

@if($company)
@if($company->logo)
<div style="margin-bottom: 10px;">
<img src="{{ config('app.url') . '/storage/' . $company->logo }}" alt="{{ $company->name }}" style="max-height: 40px; display: block;">
</div>
@endif

<div style="font-size: 14px; font-weight: bold; margin-bottom: 4px;">{{ $company->name }}</div>

<div style="font-size: 13px; color: #444; line-height: 1.4; margin-bottom: 4px;">
{{ $company->address }}<br>
@if($company->pincode || $company->city || $company->state || $company->country)
{{ $company->pincode }} {{ $company->city }} {{ $company->state }} {{ $company->country }}
@endif
</div>

@if($company->phone)
<div style="font-size: 13px; color: #444; margin-bottom: 4px;">Phone: {{ $company->phone }}</div>
@endif

@if($company->website)
<div style="font-size: 13px; color: #444; margin-bottom: 4px;">
<a href="{{ $company->website }}" style="color: #007bff; text-decoration: none;">{{ $company->website }}</a>
</div>
@endif
@endif

@if($user)
<div style="font-size: 13px; color: #444; margin-bottom: 8px;">
Email: <a href="mailto:{{ $user->email }}" style="color: #007bff; text-decoration: none;">{{ $user->email }}</a>
</div>
@endif

@if($company)
<div style="font-size: 12px; margin-top: 10px;">
@php
$socials = [];
if($company->facebook) $socials[] = '<a href="'.$company->facebook.'" style="color: #007bff; text-decoration: underline;">FACEBOOK</a>';
if($company->instagram) $socials[] = '<a href="'.$company->instagram.'" style="color: #007bff; text-decoration: underline;">INSTAGRAM</a>';
if($company->youtube) $socials[] = '<a href="'.$company->youtube.'" style="color: #007bff; text-decoration: underline;">YOUTUBE</a>';
if($company->linkedin) $socials[] = '<a href="'.$company->linkedin.'" style="color: #007bff; text-decoration: underline;">LINKEDIN</a>';
@endphp
{!! implode(' | ', $socials) !!}
</div>
@endif
</div>

</x-mail::message>
