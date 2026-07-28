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
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Home
            </h2>
            <span class="text-sm text-gray-500"><?php echo e(now()->format('d/m/Y')); ?></span>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="p-6 lg:p-8 space-y-6">
        <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-[#1fa67e]">
                Olá, <?php echo e($usuario->nome); ?>!
            </h3>
            <p class="mt-2 text-gray-600">
                Pequenas ações geram grandes resultados. Organize suas finanças pelo menu lateral.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6 text-center">
                <h4 class="font-semibold text-gray-800">Meses</h4>
                <p class="mt-2 text-sm text-gray-500">Gerencie seus meses financeiros</p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6 text-center">
                <h4 class="font-semibold text-gray-800">Lançamentos</h4>
                <p class="mt-2 text-sm text-gray-500">Visualize todos os seus lançamentos</p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6 text-center">
                <h4 class="font-semibold text-gray-800">Categorias</h4>
                <p class="mt-2 text-sm text-gray-500">Gerencie suas categorias</p>
            </div>
        </div>
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
<?php /**PATH C:\xampp\htdocs\laravel\nutriGrana\resources\views/home.blade.php ENDPATH**/ ?>