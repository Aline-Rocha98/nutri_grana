<aside id="barra-lateral"
       class="barra-lateral barra-lateral--expandida relative flex shrink-0 flex-col rounded-[2rem] bg-[#151a18] text-gray-300 transition-all duration-300 min-h-[calc(100vh-1.5rem)]"
       data-grupos-abertos='<?php echo json_encode($gruposAbertos, 15, 512) ?>'>
    <button type="button"
            data-acao="alternar-expansao"
            class="absolute -right-3 top-8 z-20 flex h-7 w-7 items-center justify-center rounded-full bg-[#1fa67e] text-white shadow-lg hover:bg-[#188f6b] transition"
            aria-label="Alternar barra lateral">
        <span data-icone-alternar class="material-icons text-base leading-none">chevron_left</span>
    </button>

    <div class="px-4 pt-5 pb-4 border-b border-white/10">
        <div class="barra-lateral__cabecalho-perfil flex items-center gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#1fa67e]/20 text-sm font-bold text-[#1fa67e]">
                <?php echo e($perfilUsuario['iniciais']); ?>

            </div>
            <div data-visivel-expandido class="barra-lateral__texto min-w-0">
                <p class="text-xs text-gray-500">Olá 👋</p>
                <p class="truncate text-sm font-semibold text-white"><?php echo e($perfilUsuario['nome']); ?></p>
            </div>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <?php $__currentLoopData = $itensMenu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if($item['tipo'] === 'link'): ?>
                <?php echo $__env->make('components.barra-lateral.item-link', ['item' => $item], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php else: ?>
                <?php echo $__env->make('components.barra-lateral.item-grupo', ['item' => $item, 'gruposAbertos' => $gruposAbertos], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </nav>

    <div class="border-t border-white/10 p-3">
        <button type="button"
                data-acao="sair"
                data-url-logout="<?php echo e(route('logout')); ?>"
                title="Sair"
                class="barra-lateral__item flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-gray-400 transition hover:bg-red-500/10 hover:text-red-300">
            <span class="material-icons shrink-0 text-xl">logout</span>
            <span data-visivel-expandido class="barra-lateral__texto">Sair</span>
        </button>
    </div>
</aside>
<?php /**PATH C:\xampp\htdocs\laravel\nutriGrana\resources\views/components/barra-lateral.blade.php ENDPATH**/ ?>