<script setup lang="ts">
import { useForm, useHttp } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import InventoryReservationController from '@/actions/App/Http/Controllers/InventoryReservationController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as warehouseStockIndex } from '@/routes/warehouses/stock';
import type { OrderLine } from '@/types';
import type { WarehouseProductStock } from '@/types';

const props = defineProps<{
    line: OrderLine | null;
}>();

const open = defineModel<boolean>('open', { required: true });
const warehouses = ref<WarehouseProductStock[]>([]);

const form = useForm({
    warehouse_id: null,
    quantity: 1,
});

const http = useHttp();

async function loadWarehouseStock(productId: number) {
    const data = (await http.submit(
        warehouseStockIndex({ product: productId }),
    )) as { warehouses: WarehouseProductStock[] };

    warehouses.value = data.warehouses ?? [];
}

watch(
    () => props.line,
    (line) => {
        warehouses.value = [];

        if (line) {
            loadWarehouseStock(line.product.id);
        }
    },
);

const maxQuantityToReserve = computed(() => {
    if (props.line && warehouses.value) {
        return Math.min(
            props.line.quantity_remaining,
            warehouses.value.find((w) => w.id == form.warehouse_id)?.stock ?? 0,
        );
    }

    return 0;
});

function submit() {
    if (!props.line) {
        return;
    }

    const orderLineId = props.line.id;

    form.transform((data) => ({
        ...data,
        order_line_id: orderLineId,
    })).post(InventoryReservationController.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Reserve</DialogTitle>
                <DialogDescription v-if="line?.product">
                    {{ line.product.name }} ({{ line.product.sku }})
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <Label for="qty_ordered">Quantity ordered</Label>
                        <p class="mt-2 text-center">
                            {{ line?.quantity_ordered }}
                        </p>
                    </div>
                    <div>
                        <Label for="qty_ordered">Quantity reserved</Label>
                        <p class="mt-2 text-center">
                            {{ line?.quantity_reserved }}
                        </p>
                    </div>
                    <div>
                        <Label for="qty_ordered">Quantity remaining</Label>
                        <p class="mt-2 text-center">
                            {{ line?.quantity_remaining }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="warehouse">Warehouse</Label>
                    <Select v-model="form.warehouse_id">
                        <SelectTrigger id="warehouse" class="w-full">
                            <SelectValue placeholder="Select a warehouse" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="warehouse in warehouses"
                                :key="warehouse.id"
                                :value="warehouse.id"
                                :disabled="warehouse.stock == 0"
                            >
                                {{ warehouse.name }} ({{ warehouse.code }})
                                (stock: {{ warehouse.stock }})
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.warehouse_id" />
                </div>

                <div v-if="form.warehouse_id">
                    <Label for="qty_ordered">Quantity to reserve</Label>
                    <Input
                        class="mt-2"
                        v-model="form.quantity"
                        type="number"
                        :min="1"
                        :max="maxQuantityToReserve"
                    />
                    <p class="mt-0.5 text-xs text-gray-700">
                        Max: {{ maxQuantityToReserve }}
                    </p>
                    <InputError :message="form.errors.quantity" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :disabled="
                            form.processing || form.warehouse_id === null
                        "
                    >
                        {{ form.processing ? 'Saving...' : 'Reserve' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
