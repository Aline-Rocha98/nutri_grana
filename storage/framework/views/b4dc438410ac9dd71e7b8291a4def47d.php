<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Contas bancárias
            </h2>
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#198a68] transition"
                x-data
                x-on:click="$dispatch('abrir-criar-conta-bancaria')"
            >
                <span class="material-symbols-outlined text-base leading-none">add</span>
                Adicionar conta
            </button>
        </div>
     <?php $__env->endSlot(); ?>

    <div
        class="p-6 lg:p-8 space-y-6"
        x-data="contaBancariaPage({
            tipos: <?php echo e(Js::from($tipos)); ?>,
            bancosSugeridos: <?php echo e(Js::from($bancosSugeridos)); ?>,
            urlCriar: <?php echo e(Js::from(route('contas-bancarias.criar'))); ?>,
            old: {
                nome: <?php echo e(Js::from(old('nome', ''))); ?>,
                saldo_inicial: <?php echo e(Js::from(old('saldo_inicial', '0,00'))); ?>,
                tipo: <?php echo e(Js::from(old('tipo', $tipos[0]['valor'] ?? 'corrente'))); ?>,
                padrao_desconto: <?php echo e(Js::from(old('padrao_desconto'))); ?>,
                exibir_resumo: <?php echo e(Js::from(old('exibir_resumo'))); ?>,
            },
        })"
        x-on:abrir-criar-conta-bancaria.window="abrirCriar()"
        x-on:tipo-conta-bancaria-alterado="onTipoAlterado($event)"
        x-on:abrir-select2-conta.window="onAbrirSelect2($event)"
    >
        <?php if(session('sucesso')): ?>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <?php echo e(session('sucesso')); ?>

            </div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $erro): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($erro); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php
            $contasAtivas = $contasBancarias->where('arquivada', false);
            $contasArquivadas = $contasBancarias->where('arquivada', true);
            $saldoGeral = $contasAtivas->sum(fn ($conta) => (float) $conta->saldo_inicial);
        ?>

        <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6">
            <p class="text-sm font-medium text-gray-500">Saldo geral</p>
            <p class="mt-1 text-2xl font-bold text-[#1fa67e]">
                R$ <?php echo e(number_format($saldoGeral, 2, ',', '.')); ?>

            </p>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Minhas contas</h3>
            </div>

            <div class="divide-y divide-gray-100">
                <?php $__empty_1 = true; $__currentLoopData = $contasAtivas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php echo $__env->make('contas-bancarias.partials.linha-conta', ['conta' => $conta], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="px-6 py-10 text-center text-sm text-gray-500">
                        Nenhuma conta cadastrada. Clique em <strong>Adicionar conta</strong> para começar.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if($contasArquivadas->isNotEmpty()): ?>
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 opacity-80">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Arquivadas</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php $__currentLoopData = $contasArquivadas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php echo $__env->make('contas-bancarias.partials.linha-conta', ['conta' => $conta], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php echo $__env->make('contas-bancarias.partials.modal-formulario', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\laravel\nutriGrana\resources\views/contas-bancarias/index.blade.php ENDPATH**/ ?>