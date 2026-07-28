<div class="space-y-1">
    <button type="button"
            data-acao="alternar-grupo"
            data-grupo-id="<?php echo e($item['id']); ?>"
            title="<?php echo e($item['rotulo']); ?>"
            class="barra-lateral__item group relative flex w-full items-center gap-3 rounded-xl px-3 py-2.5 transition
                   <?php echo e($item['ativo'] ? 'bg-white/10 text-white' : 'hover:bg-white/5 hover:text-white'); ?>">
        <?php if($item['ativo']): ?>
            <span class="absolute left-0 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full bg-[#1fa67e]"></span>
        <?php endif; ?>
        <span class="material-icons shrink-0 text-xl text-gray-400 group-hover:text-[#1fa67e] <?php echo e($item['ativo'] ? 'text-[#1fa67e]' : ''); ?>">
            <?php echo e($item['iconeMaterial']); ?>

        </span>
        <span data-visivel-expandido class="barra-lateral__texto flex-1 truncate text-left text-sm font-medium"><?php echo e($item['rotulo']); ?></span>
        <span data-visivel-expandido
              data-grupo-chevron="<?php echo e($item['id']); ?>"
              class="material-icons barra-lateral__chevron shrink-0 text-base transition-transform <?php echo e(($gruposAbertos[$item['id']] ?? false) ? 'barra-lateral__chevron--aberto' : ''); ?>">
            expand_more
        </span>
    </button>

    <div data-submenu="<?php echo e($item['id']); ?>"
         class="barra-lateral__submenu ml-5 border-l border-white/10 pl-3 space-y-1 <?php echo e(($gruposAbertos[$item['id']] ?? false) ? 'barra-lateral__submenu--aberto' : ''); ?>"
         <?php if(! ($gruposAbertos[$item['id']] ?? false)): ?> hidden <?php endif; ?>>
        <?php $__currentLoopData = $item['filhos']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $filho): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e($filho['url']); ?>"
               class="relative flex items-center gap-2 rounded-lg py-2 pl-3 pr-2 text-sm transition
                      <?php echo e($filho['ativo'] ? 'bg-white/10 text-white font-medium' : 'text-gray-400 hover:bg-white/5 hover:text-white'); ?>">
                <span class="absolute -left-3 top-1/2 h-px w-3 bg-white/10"></span>
                <span class="truncate"><?php echo e($filho['rotulo']); ?></span>
                <?php if($filho['ativo']): ?>
                    <span class="material-icons ml-auto shrink-0 text-sm text-[#1fa67e]">chevron_right</span>
                <?php endif; ?>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\laravel\nutriGrana\resources\views/components/barra-lateral/item-grupo.blade.php ENDPATH**/ ?>