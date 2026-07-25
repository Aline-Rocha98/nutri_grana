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

        <script>
            window.authRoutes = {
                login: <?php echo json_encode(route('login'), 15, 512) ?>,
                register: <?php echo json_encode(route('register'), 15, 512) ?>,
                recover: <?php echo json_encode(route('password.request'), 15, 512) ?>,
                home: <?php echo json_encode(route('home'), 15, 512) ?>,
            };
            window.authMessages = {
                emailJaCadastrado: <?php echo json_encode(__('validation.usuario.email.unique'), 15, 512) ?>,
                linkRecuperacaoEnviado: <?php echo json_encode(__('passwords.sent'), 15, 512) ?>,
            };
        </script>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#0c2e24] to-[#1fa67e] px-4">
            <div class="w-full flex justify-center">
                <?php echo e($slot); ?>

            </div>
        </div>
    </body>
</html>
<?php /**PATH C:\xampp\htdocs\laravel\nutriGrana\resources\views/layouts/guest.blade.php ENDPATH**/ ?>