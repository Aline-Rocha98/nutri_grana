import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useUsuarioAdmin() {
    const pagina = usePage();

    const usuario = computed(() => pagina.props.auth?.usuario ?? null);

    const ehAdministrador = computed(() => Boolean(usuario.value?.ehAdministrador));

    return {
        usuario,
        ehAdministrador,
    };
}
