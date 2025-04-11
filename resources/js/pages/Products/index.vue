<script setup>
import { ref, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { CirclePlus, Pencil, Trash } from 'lucide-vue-next'

const props = defineProps({
    products: Array,
    errors: Object,
    flash: Object
})

const form = useForm({
    nombre: '',
    precio: ''
})

const editing = ref(false)
const editingProductId = ref(null)
const showCreateForm = ref(false)

const startEdit = (product) => {
    editing.value = true
    editingProductId.value = product.id
    form.nombre = product.nombre
    form.precio = product.precio
    showCreateForm.value = false
}

const cancelEdit = () => {
    editing.value = false
    editingProductId.value = null
    form.reset()
}

const startCreate = () => {
    editing.value = false
    editingProductId.value = null
    form.reset()
    showCreateForm.value = true
}

const saveProduct = () => {
    if (editing.value) {
        form.put(`/products/${editingProductId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                editing.value = false
                editingProductId.value = null
                form.reset()
            }
        })
    } else {
        form.post('/products', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset()
                showCreateForm.value = false
            }
        })
    }
}

const deleteProduct = (id) => {
    if (!window.confirm('¿Estás seguro de que deseas eliminar este producto?')) return;

    router.delete(`/products/${id}`, {
        preserveScroll: true,
        onError: (errors) => {
            console.error('Ha ocurrido un error:', errors);
        }
    });
}

const page = usePage()
const flashMessage = computed(() => page.props.flash?.message)
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Productos', url: '/products' }]">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Flash Message -->
            <div v-if="flashMessage" class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded mb-4">
                {{ flashMessage }}
            </div>

            <!-- Create Button -->
            <div class="flex">
                <button
                    @click="startCreate"
                    class="px-4 py-2 rounded-md border border-black dark:border-white text-black dark:text-white bg-white dark:bg-black hover:bg-gray-100 dark:hover:bg-gray-900 flex items-center gap-2"
                >
                    <CirclePlus size="18" /> Crear
                </button>
            </div>

            <!-- Form (conditionally rendered) -->
            <div v-if="editing || showCreateForm" class="bg-white dark:bg-black p-4 rounded-xl shadow mb-4 border border-black dark:border-white">
                <h2 class="text-lg font-medium mb-4 text-black dark:text-white">{{ editing ? 'Editar Producto' : 'Crear Producto' }}</h2>
                <form @submit.prevent="saveProduct" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white">Nombre</label>
                        <input
                            v-model="form.nombre"
                            type="text"
                            class="mt-1 block w-full rounded-md border border-black dark:border-white bg-white dark:bg-black text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-600"
                        >
                        <div v-if="form.errors.nombre" class="text-red-500 text-sm mt-1">{{ form.errors.nombre }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white">Precio</label>
                        <input
                            v-model="form.precio"
                            type="number"
                            step="0.01"
                            class="mt-1 block w-full rounded-md border border-black dark:border-white bg-white dark:bg-black text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-600"
                        >
                        <div v-if="form.errors.precio" class="text-red-500 text-sm mt-1">{{ form.errors.precio }}</div>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="submit"
                            class="inline-flex justify-center py-2 px-4 border border-black dark:border-white text-black dark:text-white bg-white dark:bg-black hover:bg-gray-100 dark:hover:bg-gray-900 text-sm font-medium rounded-md"
                            :disabled="form.processing"
                        >
                            {{ editing ? 'Actualizar' : 'Guardar' }}
                        </button>
                        <button
                            type="button"
                            @click="editing ? cancelEdit() : showCreateForm = false"
                            class="inline-flex justify-center py-2 px-4 border border-black dark:border-white text-black dark:text-white bg-white dark:bg-black hover:bg-gray-100 dark:hover:bg-gray-900 text-sm font-medium rounded-md"
                        >
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Products Table -->
            <div class="relative flex-1 rounded-xl border border-black dark:border-white">
                <table class="w-full text-sm text-left text-black dark:text-white bg-white dark:bg-black">
                    <caption class="p-5 text-lg font-semibold text-left text-black dark:text-white bg-white dark:bg-black">
                        Lista de Productos
                    </caption>
                    <thead class="text-xs text-black dark:text-white uppercase bg-white dark:bg-black border-b border-black dark:border-white">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-[100px]">ID</th>
                        <th scope="col" class="px-6 py-3">Nombre</th>
                        <th scope="col" class="px-6 py-3">Precio</th>
                        <th scope="col" class="px-6 py-3 text-center">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-if="products && products.length === 0" class="border-b border-black dark:border-white bg-white dark:bg-black">
                        <td colspan="4" class="px-6 py-4 text-center text-black dark:text-white">No hay productos disponibles</td>
                    </tr>
                    <tr v-for="product in products" :key="product.id" class="border-b border-black dark:border-white bg-white dark:bg-black">
                        <td class="px-6 py-4 text-black dark:text-white">{{ product.id }}</td>
                        <td class="px-6 py-4 text-black dark:text-white">{{ product.nombre }}</td>
                        <td class="px-6 py-4 text-black dark:text-white">
                            {{ new Intl.NumberFormat('es-MX', {style: 'currency', currency: 'MXN'}).format(product.precio) }}
                        </td>
                        <td class="px-6 py-4 flex justify-center gap-2">
                            <button
                                @click="startEdit(product)"
                                class="p-2 rounded-md border border-black dark:border-white text-black dark:text-white bg-white dark:bg-black hover:bg-gray-100 dark:hover:bg-gray-900"
                            >
                                <Pencil size="16" />
                            </button>
                            <button
                                @click="deleteProduct(product.id)"
                                class="p-2 rounded-md border border-black dark:border-white text-black dark:text-white bg-white dark:bg-black hover:bg-gray-100 dark:hover:bg-gray-900"
                            >
                                <Trash size="16" />
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
