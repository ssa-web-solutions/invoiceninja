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

namespace App\Transformers;

use App\Models\Account;
use App\Models\BankTransaction;
use App\Models\BankTransactionRule;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Invoice;
use App\Transformers\VendorTransformer;
use App\Utils\Traits\MakesHash;

/**
 * Class BankTransactionRuleTransformer.
 */
class BankTransactionRuleTransformer extends EntityTransformer
{
    use MakesHash;

    /**
     * @var array
     */
    protected $defaultIncludes = [
    ];

    /**
     * @var array
     */
    protected $availableIncludes = [
        'company',
        'vendor',
        'client',
        'expense_category',
    ];

    /**
     * @param BankTransaction $bank_integration
     * @return array
     */
    public function transform(BankTransactionRule $bank_transaction_rule)
    {
        return [
            'id' => (string) $this->encodePrimaryKey($bank_transaction_rule->id),
            'name' => (string) $bank_transaction_rule->name,
            'rules' => $bank_transaction_rule->rules ?: (array) [],
            'auto_convert' => (bool) $bank_transaction_rule->auto_convert,
            'matches_on_all' => (bool) $bank_transaction_rule->matches_on_all,
            'applies_to' => (string) $bank_transaction_rule->applies_to,
            'client_id' => $this->encodePrimaryKey($bank_transaction_rule->client_id) ?: '',
            'vendor_id' => $this->encodePrimaryKey($bank_transaction_rule->vendor_id) ?: '',
            'category_id' => $this->encodePrimaryKey($bank_transaction_rule->category_id) ?: '',
            'is_deleted' => (bool) $bank_transaction_rule->is_deleted,
            'created_at' => (int) $bank_transaction_rule->created_at,
            'updated_at' => (int) $bank_transaction_rule->updated_at,
            'archived_at' => (int) $bank_transaction_rule->deleted_at,
        ];
    }

    public function includeCompany(BankTransactionRule $bank_transaction_rule)
    {
        $transformer = new CompanyTransformer($this->serializer);

        return $this->includeItem($bank_transaction_rule->company, $transformer, Company::class);
    }

    public function includeClient(BankTransactionRule $bank_transaction_rule)
    {
        $transformer = new ClientTransformer($this->serializer);

        return $this->includeItem($bank_transaction_rule->expense, $transformer, Client::class);
    }

    public function includeVendor(BankTransactionRule $bank_transaction_rule)
    {
        $transformer = new VendorTransformer($this->serializer);

        return $this->includeItem($bank_transaction_rule->vendor, $transformer, Vendor::class);
    }

}
