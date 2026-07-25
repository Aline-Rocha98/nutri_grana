<?php if (isset($component)) { $__componentOriginal69dc84650370d1d4dc1b42d016d7226b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal69dc84650370d1d4dc1b42d016d7226b = $attributes; } ?>
<?php $component = App\View\Components\GuestLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('guest-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\GuestLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <div class="bg-white/90 backdrop-blur-xl shadow-2xl rounded-2xl w-full max-w-md p-8 border border-white/20">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#1fa67e] tracking-wide">
                NutriGrana
            </h1>
            <p class="text-gray-500 text-sm mt-2">
                Acesse sua conta e continue evoluindo
            </p>
        </div>

        <form id="form-login" method="POST" action="<?php echo e(route('login')); ?>" class="space-y-5" novalidate>
            <?php echo csrf_field(); ?>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-600">
                    Email
                </label>
                <input id="email"
                       type="email"
                       name="email"
                       value="<?php echo e(old('email')); ?>"
                       data-required
                       autofocus
                       autocomplete="username"
                       class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-600">
                    Senha
                </label>
                <input id="password"
                       type="password"
                       name="password"
                       data-required
                       autocomplete="current-password"
                       class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm">
            </div>

            <div class="flex items-center justify-between">
                <label for="remember_me" class="flex items-center text-sm text-gray-600">
                    <input id="remember_me"
                           type="checkbox"
                           name="remember"
                           class="rounded border-gray-300 text-[#1fa67e] focus:ring-[#1fa67e] shadow-sm">
                    <span class="ml-2">
                        Me manter conectado
                    </span>
                </label>

                <?php if(Route::has('password.request')): ?>
                    <a href="<?php echo e(route('password.request')); ?>"
                       class="text-sm text-[#1fa67e] hover:underline">
                        Esqueceu?
                    </a>
                <?php endif; ?>
            </div>

            <button type="submit"
                    class="btn-auth-submit w-full bg-[#1fa67e] hover:bg-[#188f6b] text-white font-semibold py-2.5 rounded-lg shadow-lg transition duration-300">
                Entrar
            </button>

            <div class="text-center text-sm text-gray-500 mt-4">
                Ainda não tem conta?
                <a href="<?php echo e(route('register')); ?>"
                   class="text-[#1fa67e] font-semibold hover:underline">
                    Criar conta
                </a>
            </div>
        </form>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal69dc84650370d1d4dc1b42d016d7226b)): ?>
<?php $attributes = $__attributesOriginal69dc84650370d1d4dc1b42d016d7226b; ?>
<?php unset($__attributesOriginal69dc84650370d1d4dc1b42d016d7226b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal69dc84650370d1d4dc1b42d016d7226b)): ?>
<?php $component = $__componentOriginal69dc84650370d1d4dc1b42d016d7226b; ?>
<?php unset($__componentOriginal69dc84650370d1d4dc1b42d016d7226b); ?>
<?php endif; ?><?php /**PATH C:\xampp\htdocs\laravel\nutriGrana\resources\views/auth/login.blade.php ENDPATH**/ ?>