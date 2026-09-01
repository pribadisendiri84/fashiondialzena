<div class="row-actions">
  @if($item->trashed())
    @if(! empty($restore) && auth()->user()?->can('manage-users'))
    <form method="post" action="{{ $restore }}">
      @csrf
      <button class="btn gray compact" type="submit">Pulihkan</button>
    </form>
    @endif
  @else
    @isset($edit)
      <a class="btn gray compact" href="{{ $edit }}">{{ $editLabel ?? 'Edit' }}</a>
    @endisset
    @if(! empty($destroy))
    <form method="post" action="{{ $destroy }}" onsubmit="return confirm('{{ $confirm }}')">
      @csrf
      @method('DELETE')
      <button class="btn red compact" type="submit">Hapus</button>
    </form>
    @endif
  @endif
</div>
