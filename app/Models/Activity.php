<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2023. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Models;

use App\Utils\Number;
use App\Utils\Traits\MakesHash;

/**
 * App\Models\Activity
 *
 * @property int $id
 * @property int|null $user_id
 * @property int $company_id
 * @property int|null $client_id
 * @property int|null $client_contact_id
 * @property int|null $account_id
 * @property int|null $project_id
 * @property int|null $vendor_id
 * @property int|null $payment_id
 * @property int|null $invoice_id
 * @property int|null $credit_id
 * @property int|null $invitation_id
 * @property int|null $task_id
 * @property int|null $expense_id
 * @property int|null $activity_type_id
 * @property string $ip
 * @property bool $is_system
 * @property string $notes
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $token_id
 * @property int|null $quote_id
 * @property int|null $subscription_id
 * @property int|null $recurring_invoice_id
 * @property int|null $recurring_expense_id
 * @property int|null $recurring_quote_id
 * @property int|null $purchase_order_id
 * @property int|null $vendor_contact_id
 * @property-read \App\Models\Backup|null $backup
 * @property-read \App\Models\Client|null $client
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\ClientContact|null $contact
 * @property-read \App\Models\Credit|null $credit
 * @property-read \App\Models\Expense|null $expense
 * @property-read mixed $hashed_id
 * @property-read \App\Models\Backup|null $history
 * @property-read \App\Models\Invoice|null $invoice
 * @property-read \App\Models\Payment|null $payment
 * @property-read \App\Models\PurchaseOrder|null $purchase_order
 * @property-read \App\Models\Quote|null $quote
 * @property-read \App\Models\RecurringExpense|null $recurring_expense
 * @property-read \App\Models\RecurringInvoice|null $recurring_invoice
 * @property-read \App\Models\Subscription|null $subscription
 * @property-read \App\Models\Task|null $task
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Vendor|null $vendor
 * @property-read \App\Models\VendorContact|null $vendor_contact
 * @method static \Illuminate\Database\Eloquent\Builder|StaticModel company()
 * @method static \Illuminate\Database\Eloquent\Builder|StaticModel exclude($columns)

 * @mixin \Eloquent
 */
class Activity extends StaticModel
{
    use MakesHash;

    const CREATE_CLIENT = 1; //

    const ARCHIVE_CLIENT = 2; //

    const DELETE_CLIENT = 3; //

    const CREATE_INVOICE = 4; //

    const UPDATE_INVOICE = 5; //

    const EMAIL_INVOICE = 6; //

    const VIEW_INVOICE = 7; //

    const ARCHIVE_INVOICE = 8; //

    const DELETE_INVOICE = 9; //

    const CREATE_PAYMENT = 10; //

    const UPDATE_PAYMENT = 11; //

    const ARCHIVE_PAYMENT = 12; //

    const DELETE_PAYMENT = 13; //

    const CREATE_CREDIT = 14; //

    const UPDATE_CREDIT = 15; //

    const ARCHIVE_CREDIT = 16; //

    const DELETE_CREDIT = 17; //

    const CREATE_QUOTE = 18; //

    const UPDATE_QUOTE = 19; //

    const EMAIL_QUOTE = 20; //

    const VIEW_QUOTE = 21; //

    const ARCHIVE_QUOTE = 22; //

    const DELETE_QUOTE = 23; //

    const RESTORE_QUOTE = 24; //

    const RESTORE_INVOICE = 25; //

    const RESTORE_CLIENT = 26; //

    const RESTORE_PAYMENT = 27; //

    const RESTORE_CREDIT = 28; //

    const APPROVE_QUOTE = 29; //

    const CREATE_VENDOR = 30; //

    const ARCHIVE_VENDOR = 31; //

    const DELETE_VENDOR = 32; //

    const RESTORE_VENDOR = 33; //

    const CREATE_EXPENSE = 34; //

    const ARCHIVE_EXPENSE = 35; //

    const DELETE_EXPENSE = 36; //

    const RESTORE_EXPENSE = 37; //

    const VOIDED_PAYMENT = 39; //

    const REFUNDED_PAYMENT = 40; //

    const FAILED_PAYMENT = 41;

    const CREATE_TASK = 42; //

    const UPDATE_TASK = 43; //

    const ARCHIVE_TASK = 44; //

    const DELETE_TASK = 45; //

    const RESTORE_TASK = 46; //

    const UPDATE_EXPENSE = 47; //

    const CREATE_USER = 48;

    const UPDATE_USER = 49;

    const ARCHIVE_USER = 50;

    const DELETE_USER = 51;

    const RESTORE_USER = 52;

    const MARK_SENT_INVOICE = 53; // not needed?

    const PAID_INVOICE = 54; //

    const EMAIL_INVOICE_FAILED = 57;

    const REVERSED_INVOICE = 58; //

    const CANCELLED_INVOICE = 59; //

    const VIEW_CREDIT = 60; //

    const UPDATE_CLIENT = 61; //

    const UPDATE_VENDOR = 62; //

    const INVOICE_REMINDER1_SENT = 63;

    const INVOICE_REMINDER2_SENT = 64;

    const INVOICE_REMINDER3_SENT = 65;

    const INVOICE_REMINDER_ENDLESS_SENT = 66;

    const CREATE_SUBSCRIPTION = 80;

    const UPDATE_SUBSCRIPTION = 81;

    const ARCHIVE_SUBSCRIPTION = 82;

    const DELETE_SUBSCRIPTION = 83;

    const RESTORE_SUBSCRIPTION = 84;

    const CREATE_RECURRING_INVOICE = 100;

    const UPDATE_RECURRING_INVOICE = 101;

    const ARCHIVE_RECURRING_INVOICE = 102;

    const DELETE_RECURRING_INVOICE = 103;

    const RESTORE_RECURRING_INVOICE = 104;

    const CREATE_RECURRING_QUOTE = 110;

    const UPDATE_RECURRING_QUOTE = 111;

    const ARCHIVE_RECURRING_QUOTE = 112;

    const DELETE_RECURRING_QUOTE = 113;

    const RESTORE_RECURRING_QUOTE = 114;

    const CREATE_RECURRING_EXPENSE = 120;

    const UPDATE_RECURRING_EXPENSE = 121;

    const ARCHIVE_RECURRING_EXPENSE = 122;

    const DELETE_RECURRING_EXPENSE = 123;

    const RESTORE_RECURRING_EXPENSE = 124;

    const CREATE_PURCHASE_ORDER = 130;

    const UPDATE_PURCHASE_ORDER = 131;

    const ARCHIVE_PURCHASE_ORDER = 132;

    const DELETE_PURCHASE_ORDER = 133;

    const RESTORE_PURCHASE_ORDER = 134;

    const EMAIL_PURCHASE_ORDER = 135;

    const VIEW_PURCHASE_ORDER = 136;

    const ACCEPT_PURCHASE_ORDER = 137;

    const PAYMENT_EMAILED = 138;

    const VENDOR_NOTIFICATION_EMAIL = 139;

    protected $casts = [
        'is_system' => 'boolean',
        'updated_at' => 'timestamp',
        'created_at' => 'timestamp',
        'deleted_at' => 'timestamp',
    ];

    protected $appends = [
        'hashed_id',
    ];

    protected $with = [
        'backup',
    ];


    public function getHashedIdAttribute(): string
    {
        return $this->encodePrimaryKey($this->id);
    }


    public function getEntityType()
    {
        return self::class;
    }

    public function backup(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Backup::class);
    }

    public function history(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Backup::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function contact(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ClientContact::class, 'client_contact_id', 'id')->withTrashed();
    }

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function invoice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }


    public function vendor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }


    public function recurring_invoice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class)->withTrashed();
    }

    public function credit(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Credit::class)->withTrashed();
    }

    public function quote(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Quote::class)->withTrashed();
    }

    public function subscription(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subscription::class)->withTrashed();
    }

    public function payment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Payment::class)->withTrashed();
    }

    public function expense(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Expense::class)->withTrashed();
    }


    public function recurring_expense(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RecurringExpense::class)->withTrashed();
    }

    public function purchase_order(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class)->withTrashed();
    }

    public function vendor_contact(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(VendorContact::class)->withTrashed();
    }

    public function task(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Task::class)->withTrashed();
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function activity_string()
    {
        $intersect = [
            ':invoice',
            ':client',
            ':contact',
            ':user',
            ':vendor',
            ':quote',
            ':credit',
            ':payment',
            ':task',
            ':expense',
            ':purchase_order',
            ':subscription',
            ':recurring_invoice',
            ':recurring_expense',
            ':amount',
            ':balance',
            ':number',
            ':payment_amount',
            ':gateway',
            ':adjustment'
        ];

        $found_variables = array_intersect(explode(" ", trans("texts.activity_{$this->activity_type_id}")), $intersect);

        $replacements = [];

        foreach($found_variables as $var) {
            $replacements = array_merge($replacements, $this->matchVar($var));
        }

        if($this->client) {
            $replacements['client'] = ['label' => $this?->client?->present()->name() ?? '', 'hashed_id' => $this->client->hashed_id ?? ''];
        }
        
        if($this->vendor) {
            $replacements['vendor'] = ['label' => $this?->vendor?->present()->name() ?? '', 'hashed_id' => $this->vendor->hashed_id ?? ''];
        }

        if($this->activity_type_id == 4 && $this->recurring_invoice) {
            $replacements['recurring_invoice'] = ['label' => $this?->recurring_invoice?->number ?? '', 'hashed_id' => $this->recurring_invoice->hashed_id ?? ''];
        }

        $replacements['activity_type_id'] = $this->activity_type_id;
        $replacements['id'] = $this->id;
        $replacements['hashed_id'] = $this->hashed_id;
        $replacements['notes'] = $this->notes ?? '';
        $replacements['created_at'] = $this->created_at ?? '';
        $replacements['ip'] = $this->ip ?? '';

        return $replacements;

    }

    private function matchVar(string $variable)
    {
        $system = ctrans('texts.system');
        
        $translation = '';
        
        match($variable) {
            ':invoice' => $translation = [substr($variable, 1) => [ 'label' => $this?->invoice?->number ?? '', 'hashed_id' => $this->invoice?->hashed_id ?? '']],
            ':user' => $translation =  [substr($variable, 1) => [ 'label' => $this?->user?->present()->name() ?? $system, 'hashed_id' => $this->user->hashed_id ?? '']],
            ':quote' => $translation =  [substr($variable, 1) => [ 'label' => $this?->quote?->number ?? '', 'hashed_id' => $this->quote->hashed_id ?? '']],
            ':credit' => $translation =  [substr($variable, 1) => [ 'label' => $this?->credit?->number ?? '', 'hashed_id' => $this->credit->hashed_id ?? '']],
            ':payment' => $translation =  [substr($variable, 1) => [ 'label' => $this?->payment?->number ?? '', 'hashed_id' => $this->payment->hashed_id ?? '']],
            ':task' => $translation =  [substr($variable, 1) => [ 'label' => $this?->task?->number ?? '', 'hashed_id' => $this->task->hashed_id ?? '']],
            ':expense' => $translation =  [substr($variable, 1) => [ 'label' => $this?->expense?->number ?? '', 'hashed_id' => $this->expense->hashed_id ?? '']],
            ':purchase_order' => $translation =  [substr($variable, 1) => [ 'label' => $this?->purchase_order?->number ?? '', 'hashed_id' => $this->purchase_order->hashed_id ?? '']],
            ':subscription' => $translation =  [substr($variable, 1) => [ 'label' => $this?->subscription?->number ?? '', 'hashed_id' => $this->subscription->hashed_id ?? '' ]],
            ':recurring_invoice' => $translation =  [substr($variable, 1) =>[ 'label' =>  $this?->recurring_invoice?->number ??'', 'hashed_id' => $this->recurring_invoice->hashed_id ?? '']],
            ':recurring_expense' => $translation =  [substr($variable, 1) => [ 'label' => $this?->recurring_expense?->number ??'', 'hashed_id' => $this->recurring_expense->hashed_id ?? '']],
            ':payment_amount' => $translation =  [substr($variable, 1) =>[ 'label' =>  Number::formatMoney($this?->payment?->amount, $this?->payment?->client ?? $this->company) ?? '', 'hashed_id' => '']],
            ':adjustment' => $translation =  [substr($variable, 1) =>[ 'label' =>  Number::formatMoney($this?->payment?->refunded, $this?->payment?->client ?? $this->company) ?? '', 'hashed_id' => '']],
            ':ip' => $translation = [ 'ip' => $this->ip ?? ''],
            ':contact' => $translation = $this->resolveContact(),
            default => $translation = [],
        };

        return $translation;
    }

    private function resolveContact() : array
    {
        $contact = $this->contact ? $this->contact : $this->vendor_contact;

        $entity = $this->contact ? $this->client : $this->vendor;

        $contact_entity = $this->contact ? 'clients' : 'vendors';

        return ['contact' => [ 'label' => $contact?->present()->name() ?? '', 'hashed_id' => $entity->hashed_id ?? '', 'contact_entity' => $contact_entity]];
    }
}
