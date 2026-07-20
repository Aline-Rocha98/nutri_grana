<div class="space-y-1">
    <button type="button"
            data-acao="alternar-grupo"
            data-grupo-id="{{ $item['id'] }}"
            title="{{ $item['rotulo'] }}"
            class="barra-lateral__item group relative flex w-full items-center gap-3 rounded-xl px-3 py-2.5 transition
                   {{ $item['ativo'] ? 'bg-white/10 text-white' : 'hover:bg-white/5 hover:text-white' }}">
        @if ($item['ativo'])
            <span class="absolute left-0 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full bg-[#1fa67e]"></span>
        @endif
        <span class="material-icons shrink-0 text-xl text-gray-400 group-hover:text-[#1fa67e] {{ $item['ativo'] ? 'text-[#1fa67e]' : '' }}">
            {{ $item['iconeMaterial'] }}
        </span>
        <span data-visivel-expandido class="barra-lateral__texto flex-1 truncate text-left text-sm font-medium">{{ $item['rotulo'] }}</span>
        <span data-visivel-expandido
              data-grupo-chevron="{{ $item['id'] }}"
              class="material-icons barra-lateral__chevron shrink-0 text-base transition-transform {{ ($gruposAbertos[$item['id']] ?? false) ? 'barra-lateral__chevron--aberto' : '' }}">
            expand_more
        </span>
    </button>

    <div data-submenu="{{ $item['id'] }}"
         class="barra-lateral__submenu ml-5 border-l border-white/10 pl-3 space-y-1 {{ ($gruposAbertos[$item['id']] ?? false) ? 'barra-lateral__submenu--aberto' : '' }}"
         @if (! ($gruposAbertos[$item['id']] ?? false)) hidden @endif>
        @foreach ($item['filhos'] as $filho)
            <a href="{{ $filho['url'] }}"
               class="relative flex items-center gap-2 rounded-lg py-2 pl-3 pr-2 text-sm transition
                      {{ $filho['ativo'] ? 'bg-white/10 text-white font-medium' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <span class="absolute -left-3 top-1/2 h-px w-3 bg-white/10"></span>
                <span class="truncate">{{ $filho['rotulo'] }}</span>
                @if ($filho['ativo'])
                    <span class="material-icons ml-auto shrink-0 text-sm text-[#1fa67e]">chevron_right</span>
                @endif
            </a>
        @endforeach
    </div>
</div>
