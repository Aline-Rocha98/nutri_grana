<a href="{{ $item['url'] }}"
   title="{{ $item['rotulo'] }}"
   class="barra-lateral__item group relative flex items-center gap-3 rounded-xl px-3 py-2.5 transition
          {{ $item['ativo'] ? 'bg-white/10 text-white' : 'hover:bg-white/5 hover:text-white' }}">
    @if ($item['ativo'])
        <span class="absolute left-0 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full bg-[#1fa67e]"></span>
    @endif
    <span class="material-icons shrink-0 text-xl text-gray-400 group-hover:text-[#1fa67e] {{ $item['ativo'] ? 'text-[#1fa67e]' : '' }}">
        {{ $item['iconeMaterial'] }}
    </span>
    <span data-visivel-expandido class="barra-lateral__texto truncate text-sm font-medium">{{ $item['rotulo'] }}</span>
</a>
