<form method="post" action="{{ $action }}">
    @csrf
    @if(isset($method))
    @method($method)
    @endif
    <div class="stack">
        {{ $slot }}
    </div>
</form>