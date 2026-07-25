<?php
    /** @var \App\Models\ContasBancarias\ContaBancaria $conta */

    $temLancamentos = $conta->total_lancamentos > 0;
    $classeSaldo = (float) $conta->saldo_inicial >= 0 ? 'text-[#1fa67e]' : 'text-red-600';
    $logoBanco = \App\Data\BancosSugeridos::logoPorNome($conta->nome);
    $dadosEdicao = [
        'nome' => $conta->nome,
        'saldo_inicial' => number_format((float) $conta->saldo_inicial, 2, ',', '.'),
        'tipo' => $conta->tipo->value,
        'padrao_desconto' => $conta->padrao_desconto?->value,
        'exibir_resumo' => $conta->exibir_resumo?->value,
        'url_atualizar' => route('contas-bancarias.atualizar', $conta),
    ];
?>

<div class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50/80 transition">
    <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#e8f7f1] text-[#1fa67e]">
        <?php if($logoBanco): ?>
            <img src="<?php echo e($logoBanco); ?>" alt="<?php echo e($conta->nome); ?>" class="h-full w-full object-cover">
        <?php else: ?>
            <span class="material-symbols-outlined text-[22px]">
                <?php echo e($conta->tipo->value === 'poupanca' ? 'savings' : 'account_balance'); ?>

            </span>
        <?php endif; ?>
    </div>

    <div class="min-w-0 flex-1">
        <p class="truncate font-semibold text-gray-900"><?php echo e($conta->nome); ?></p>
        <p class="text-sm text-gray-500"><?php echo e($conta->tipo->rotulo()); ?></p>
    </div>

    <div class="text-right shrink-0">
        <p class="font-semibold <?php echo e($classeSaldo); ?>">
            R$ <?php echo e(number_format((float) $conta->saldo_inicial, 2, ',', '.')); ?>

        </p>
    </div>

    <div class="flex items-center gap-1 shrink-0">
        <button
            type="button"
            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800"
            title="Editar"
            data-conta='<?php echo json_encode($dadosEdicao, 15, 512) ?>'
            x-on:click="abrirEditarDoBotao($event)"
        >
            <span class="material-symbols-outlined text-[20px]">edit</span>
        </button>

        <form method="POST" action="<?php echo e(route('contas-bancarias.atualizar', $conta)); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <input type="hidden" name="nome" value="<?php echo e($conta->nome); ?>">
            <input type="hidden" name="tipo" value="<?php echo e($conta->tipo->value); ?>">
            <input type="hidden" name="arquivada" value="<?php echo e($conta->arquivada ? 0 : 1); ?>">
            <input type="hidden" name="padrao_desconto" value="<?php echo e($conta->padrao_desconto?->value); ?>">
            <input type="hidden" name="exibir_resumo" value="<?php echo e($conta->exibir_resumo?->value); ?>">
            <button
                type="submit"
                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                title="<?php echo e($conta->arquivada ? 'Desarquivar' : 'Arquivar'); ?>"
            >
                <span class="material-symbols-outlined text-[20px]">
                    <?php echo e($conta->arquivada ? 'unarchive' : 'archive'); ?>

                </span>
            </button>
        </form>

        <?php if($temLancamentos): ?>
            <button
                type="button"
                class="rounded-lg p-2 text-gray-300 cursor-not-allowed"
                title="Não é possível excluir: há lançamentos vinculados"
                disabled
            >
                <span class="material-symbols-outlined text-[20px]">delete</span>
            </button>
        <?php else: ?>
            <form
                method="POST"
                action="<?php echo e(route('contas-bancarias.excluir', $conta)); ?>"
                class="js-form-excluir-conta"
            >
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button
                    type="submit"
                    class="rounded-lg p-2 text-gray-500 hover:bg-red-50 hover:text-red-600"
                    title="Excluir"
                >
                    <span class="material-symbols-outlined text-[20px]">delete</span>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\laravel\nutriGrana\resources\views/contas-bancarias/partials/linha-conta.blade.php ENDPATH**/ ?>