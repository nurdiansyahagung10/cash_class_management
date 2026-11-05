<?php

namespace App\Enums;

enum LedgerEntryType: string
{
    case AccountVerified = 'ACCOUNT_VERIFIED';
    case PhoneBound = 'PHONE_BOUND';
    case InvoiceCreated = 'INVOICE_CREATED';
    case PaymentConfirmed = 'PAYMENT_CONFIRMED';
    case ExpenseApproved = 'EXPENSE_APPROVED';
    case AccountDeactivated = 'ACCOUNT_DEACTIVATED';
    case Reversal = 'REVERSAL';
}
