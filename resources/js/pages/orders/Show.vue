<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { ref } from 'vue';
import ReservationDialog from '@/components/ReservationDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatCurrency } from '@/lib/currency';
import { index, show } from '@/routes/orders';
import type { OrderDetail, OrderLine } from '@/types';

const props = defineProps<{
    order: OrderDetail;
}>();

const isDialogOpen = ref(false);
const selectedLine = ref<OrderLine | null>(null);

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Orders',
            href: index(),
        },
        {
            title: props.order.number,
            href: show(props.order.id),
        },
    ],
});

function openReservationDialog(line: OrderLine) {
    isDialogOpen.value = true;
    selectedLine.value = line;
}
</script>

<template>
    <Head :title="`Order ${order.number}`" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h1 class="text-xl font-semibold">{{ order.number }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ order.customer_name }}
                </p>
            </div>
            <Badge variant="secondary">{{ order.status }}</Badge>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-sidebar-border/70 bg-muted/50 text-left text-muted-foreground"
                    >
                        <th class="px-4 py-3 font-medium">Product</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Ordered
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            Reserved
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            Remaining
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            Unit price
                        </th>
                        <th class="px-4 py-3 text-right font-medium">Total</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="line in order.lines"
                        :key="line.id"
                        class="border-b border-sidebar-border/70 last:border-0"
                    >
                        <td class="px-4 py-3">
                            <div class="font-medium">
                                {{ line.product.name }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ line.product.sku }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ line.quantity_ordered }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ line.quantity_reserved }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ line.quantity_remaining }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ formatCurrency(line.unit_price) }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ formatCurrency(line.line_total) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="openReservationDialog(line)"
                                v-if="line.quantity_remaining > 0"
                            >
                                Reserve
                            </Button>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr
                        class="border-t border-sidebar-border/70 bg-muted/50 font-medium"
                    >
                        <td class="px-4 py-3" colspan="5">Order total</td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ formatCurrency(order.total) }}
                        </td>
                        <td class="px-4 py-3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <ReservationDialog :line="selectedLine" v-model:open="isDialogOpen" />
</template>
