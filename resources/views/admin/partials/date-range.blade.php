@php
  $today = now()->toDateString();
  $dayCount = \Illuminate\Support\Carbon::parse($from)->diffInDays($to) + 1;
@endphp
<form method="get" class="filter-bar">
  @foreach(($keep ?? []) as $key => $value)
    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
  @endforeach
  <span class="filter-label">
    @include('admin.partials.icon', ['name' => 'calendar'])
    Periode
  </span>
  <div class="date-group">
    <input type="date" name="from" value="{{ $from }}" max="{{ $today }}" aria-label="Tanggal mulai">
    <span class="date-sep">—</span>
    <input type="date" name="to" value="{{ $to }}" max="{{ $today }}" aria-label="Tanggal akhir">
  </div>
  <button class="btn" type="submit">Terapkan</button>
  @unless($isDefaultRange)
    <a class="btn ghost" href="{{ $resetUrl }}">Bulan ini</a>
  @endunless
  <span class="filter-note">{{ $periodLabel }} · {{ $dayCount }} hari</span>
</form>
