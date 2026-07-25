<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e(config('app.name', 'NutriGrana')); ?></title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0,0" />

        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="font-sans antialiased bg-[#0c2e24]">
        <div class="flex min-h-screen gap-3 p-3">
            <?php if (isset($component)) { $__componentOriginalf23d1563f401cb84cc7fe5f202949e0e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf23d1563f401cb84cc7fe5f202949e0e = $attributes; } ?>
<?php $component = App\View\Components\BarraLateral::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('barra-lateral'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\BarraLateral::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf23d1563f401cb84cc7fe5f202949e0e)): ?>
<?php $attributes = $__attributesOriginalf23d1563f401cb84cc7fe5f202949e0e; ?>
<?php unset($__attributesOriginalf23d1563f401cb84cc7fe5f202949e0e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf23d1563f401cb84cc7fe5f202949e0e)): ?>
<?php $component = $__componentOriginalf23d1563f401cb84cc7fe5f202949e0e; ?>
<?php unset($__componentOriginalf23d1563f401cb84cc7fe5f202949e0e); ?>
<?php endif; ?>

            <div class="flex min-w-0 flex-1 flex-col">
                <?php if(isset($header)): ?>
                    <header class="mb-3 rounded-2xl bg-white/95 px-6 py-4 shadow-sm border border-white/20">
                        <?php echo e($header); ?>

                    </header>
                <?php endif; ?>

                <main class="flex-1 rounded-[2rem] bg-gray-100 shadow-inner overflow-auto">
                    <?php echo e($slot); ?>

                </main>
            </div>
        </div>
    </body>
</html>
<?php /**PATH C:\xampp\htdocs\laravel\nutriGrana\resources\views/layouts/app.blade.php ENDPATH**/ ?>