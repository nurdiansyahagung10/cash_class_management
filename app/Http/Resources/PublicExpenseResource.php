<?php

namespace App\Http\Resources;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expense for the public API: notes, the receipt file and who recorded it are left out.
 *
 * @mixin Expense
 */
class PublicExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'amount' => $this->amount,
            'spent_at' => $this->spent_at->toDateString(),
            'has_receipt' => $this->receipt_path !== null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
