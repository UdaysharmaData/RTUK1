<?php

namespace App\Services\ExportManager\Formatters;

use App\Models\Invoice;
use App\Modules\User\Models\User;
use App\Modules\Charity\Models\Charity;
use App\Enums\ListSoftDeletedItemsOptionsEnum;
use App\Services\ExportManager\Interfaces\ExportableDataTemplateInterface;

class InvoiceExportableDataFormatter implements ExportableDataTemplateInterface
{
    public function format(mixed $list): array
    {
        $data = [];

        foreach ($list['invoices'] as $invoice) {
            $temp['name'] = $invoice->name;
            $temp['status'] = $invoice->status->name;
			$temp['issue_date'] = date('jS M Y', (strtotime($invoice->issue_date) < 0) ? 0 : strtotime($invoice->issue_date));
			$temp['due_date'] = date('jS M Y', (strtotime($invoice->due_date) < 0) ? 0 : strtotime($invoice->due_date));
			$temp['price'] = $invoice->formatted_price;
			$temp['user'] = null;
			$temp['charity'] = null;

            if ($invoice->invoiceable_type == User::class) {
                $temp['user'] = $invoice->invoiceable?->full_name;
            }

            if ($invoice->invoiceable_type == Charity::class) {
                $temp['charity'] = $invoice->invoiceable?->name;
            }

            $temp['items'] = $this->getInvoiceItemsValue($invoice);

            if (request()->filled('deleted') && (request()->filled('deleted') == ListSoftDeletedItemsOptionsEnum::With->value || request()->filled('deleted') == ListSoftDeletedItemsOptionsEnum::Only->value)) 
                $temp['deleted'] = $invoice->deleted_at?->toDayDateTimeString();

            $data[] = $temp;
        }

        return $data;
    }

    /**
     * Format the value of the categories field
     *
     * @param  Invoice $invoice
     * @return ?string
     */
    private function getInvoiceItemsValue(Invoice $invoice): ?string
    {
        if ($invoice->invoiceItems) {
            $items = null;

            foreach ($invoice->invoiceItems as $key => $item) {
                $key += 1;
                $type = $item['type']->name;
                $price = $item['formatted_price'];
                $discount = $item['discount'] ?? 'N/A';

                $items .=
                    "$key. [Type]: $type, [Discount]: $discount, [Price]: $price" . PHP_EOL
                ;
            }

            return $items ?? 'N/A';
        }

        return 'N/A';
    }
}
