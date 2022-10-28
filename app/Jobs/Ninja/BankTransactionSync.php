<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2022. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Jobs\Ninja;

use App\Jobs\Bank\ProcessBankTransactions;
use App\Libraries\MultiDB;
use App\Models\Account;
use App\Utils\Ninja;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class BankTransactionSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // if (! Ninja::isHosted()) {
        //     return;
        // }

        //multiDB environment, need to
        foreach (MultiDB::$dbs as $db) {
            MultiDB::setDB($db);

            nlog("syncing transactions");

            $this->syncTransactions();
        }
    }

    public function syncTransactions()
    {
        $a = Account::with('bank_integrations')->whereNotNull('bank_integration_account_id')->cursor()->each(function ($account){

            // $queue = Ninja::isHosted() ? 'bank' : 'default';

            // if($account->isPaid())
            // {

                $account->bank_integrations->each(function ($bank_integration) use ($account){
                    
                    ProcessBankTransactions::dispatchSync($account->bank_integration_account_id, $bank_integration);

                });

            // }

        });
    }

}
