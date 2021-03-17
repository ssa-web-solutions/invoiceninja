<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Services\BillingSubscription;

use App\DataMapper\InvoiceItem;
use App\Factory\InvoiceFactory;
use App\Models\ClientSubscription;
use App\Models\Product;
use App\Repositories\InvoiceRepository;

class BillingSubscriptionService
{

    private $billing_subscription;

    public function __construct(BillingSubscription $billing_subscription)
    {
        $this->billing_subscription = $billing_subscription;
    }

    public function createInvoice($data)
    {
       
        $invoice_repo = new InvoiceRepository();

        // $data = [
        //     'client_id' =>,
        //     'date' => Y-m-d,
        //     'invitations' => [
        //                         'client_contact_id' => hashed_id
        //                      ],
        //      'line_items' => [],        
        // ];

        $invoice = $invoice_repo->save($data, InvoiceFactory::create($billing_subscription->company_id, $billing_subscription->user_id));
        /*
        
        If trial_enabled -> return early

            -- what we need to know that we don't already
            -- Has a promo code been entered, and does it match
            -- Is this a recurring subscription
            -- 

            1. Is this a recurring product?
            2. What is the quantity? ie is this a multi seat product ( does this mean we need this value stored in the client sub?)
        */
       
       return $invoice;

    }

    private function createLineItems($quantity)
    {
        $line_items = [];
        
        $product = $this->billing_subscription->product;

        $item = new InvoiceItem;
        $item->quantity = $quantity;
        $item->product_key = $product->product_key;
        $item->notes = $product->notes;
        $item->cost = $product->price;
        //$item->type_id need to switch whether the subscription is a service or product

        $line_items[] = $item;


        //do we have a promocode? enter this as a line item.

        return $line_items;
    }

    private function convertInvoiceToRecurring()
    {
        //The first invoice is a plain invoice - the second is fired on the recurring schedule.
    }

    public function createClientSubscription($payment_hash, $recurring_invoice_id = null)
    {
        //create the client sub record
        
        //?trial enabled?
        $cs = new ClientSubscription();
        $cs->subscription_id = $this->billing_subscription->id;
        $cs->company_id = $this->billing_subscription->company_id;

        // client_id
        $cs->save();
    }

    public function triggerWebhook($payment_hash)
    {
        //hit the webhook to after a successful onboarding
    }

    public function fireNotifications()
    {
        //scan for any notification we are required to send
    }
}