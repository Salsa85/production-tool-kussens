<template>
    <div class="p-6 bg-gray-100 min-h-screen">
        <Head title="Production" />

        <Card class="p-8 max-w-4xl mx-auto bg-white shadow-md rounded-lg">
            <!-- Page Title -->
            <h1 class="text-3xl font-semibold text-gray-800 mb-6">Productie Dashboard</h1>

            <!-- Order Input Section -->
            <div class="mb-6">
                <label for="order-number" class="block text-gray-700 font-bold mb-2">
                    Ordernummer:
                </label>
                <input
                    id="order-number"
                    v-model="orderNumber"
                    type="text"
                    class="form-control text-xl px-4 py-3 w-full border rounded focus:ring focus:ring-blue-300"
                    :class="{ 'bg-green-500 text-white': order }"
                    placeholder="Voer een ordernummer in"
                />
            </div>

            <!-- Buttons -->
            <div class="mb-6">
                <button
                    class="btn-primary flex gap-3 items-center"
                    @click="fetchOrder"
                    :disabled="loading"
                >
                   <span class="text-xl">{{ loading ? 'Laden...' : 'Order Zoeken' }}</span>
                </button>
            </div>

            <!-- Order Found Section -->
            <div v-if="order" class="border-t py-6">
                <label for="colli" class="block text-gray-700 font-bold mb-2">Aantal colli:</label>
                <input
                    id="colli"
                    type="number"
                    class="form-control px-4 py-3 mb-4 w-full border rounded"
                    v-model="colli"
                    min="1"
                    placeholder="Aantal colli"
                />
                <div class="flex gap-4">
                    <button class="btn-secondary text-xl" @click="printLabel">Print Label</button>
                    <button class="btn-secondary text-xl" @click="printPakbon">Print Pakbon</button>
                </div>
            </div>

            <!-- No Order Found -->
            <div v-else-if="searched && !order" class="text-red-500 mt-6">
                Geen order gevonden.
            </div>
        </Card>
    </div>
</template>

<script>
export default {
    data() {
        return {
            orderNumber: '',
            order: null,
            errorMessage: '',
            loading: false,
            searched: false,
            colli: 1,
        };
    },
    methods: {
        async fetchOrder() {
            this.loading = true;
            this.errorMessage = '';
            this.searched = false;
            this.order = null;

            try {
                const response = await Nova.request().get(
                    `/nova-vendor/production/orders/${this.orderNumber}`
                );
                this.order = response.data.order;
            } catch (error) {
                this.errorMessage =
                    error.response?.status === 404
                        ? 'Geen order gevonden.'
                        : 'Er is een fout opgetreden.';
            } finally {
                this.searched = true;
                this.loading = false;
            }
        },
        async printLabel() {
            if (!this.order) {
                this.errorMessage = 'Order niet gevonden.';
                return;
            }

            try {
                const response = await Nova.request().post(
                    `/nova-vendor/production/orders/${this.order.id}/labels`,
                    { colli: this.colli }
                );

                const { label_url } = response.data;
                window.open(label_url, '_blank');
            } catch (error) {
                this.errorMessage =
                    error.response?.data?.message || 'Fout bij het ophalen van labels.';
            }
        },
        printPakbon() {
            if (this.order) {
                const url = `/nova-vendor/production/orders/${this.order.id}/pdf`;
                window.open(url, '_blank');
            } else {
                this.errorMessage = 'Order niet gevonden.';
            }
        },
    },
};
</script>

<style scoped>
.form-control {
    transition: all 0.2s ease-in-out;
}
.form-control:focus {
    outline: none;
    border-color: #4299e1;
    box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.3);
}

.btn-primary {
    background-color: #4299e1; /* Tailwind bg-blue-500 */
    color: #ffffff; /* Tailwind text-white */
    padding: 0.5rem 1rem; /* Tailwind px-4 py-2 */
    border-radius: 0.25rem; /* Tailwind rounded */
    transition: all 0.2s ease-in-out; /* Tailwind transition */
}

.btn-primary:hover {
    background-color: #2b6cb0; /* Tailwind hover:bg-blue-600 */
}

.btn-secondary {
    background-color: #6b7280; /* Tailwind bg-gray-500 */
    color: #ffffff; /* Tailwind text-white */
    padding: 0.5rem 1rem; /* Tailwind px-4 py-2 */
    border-radius: 0.25rem; /* Tailwind rounded */
    transition: all 0.2s ease-in-out; /* Tailwind transition */
}

.btn-secondary:hover {
    background-color: #4b5563; /* Tailwind hover:bg-gray-600 */
}

.btn-primary[disabled],
.btn-secondary[disabled] {
    background-color: #a0aec0; /* Tailwind bg-gray-400 */
    cursor: not-allowed; /* Tailwind cursor-not-allowed */
}

</style>
