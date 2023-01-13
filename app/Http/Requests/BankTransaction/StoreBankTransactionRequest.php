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

namespace App\Http\Requests\BankTransaction;

use App\Http\Requests\Request;
use App\Models\BankTransaction;
use App\Utils\Traits\MakesHash;

class StoreBankTransactionRequest extends Request
{
    use MakesHash;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() : bool
    {
        return auth()->user()->can('create', BankTransaction::class);
    }

    public function rules()
    {
        
        $rules = [];

        $rules['bank_integration_id'] = 'bail|required|exists:bank_integrations,id,company_id,'.auth()->user()->company()->id.',is_deleted,0';

        return $rules;
    }

    public function prepareForValidation()
    {
        $input = $this->all();

            if(array_key_exists('bank_integration_id', $input) && $input['bank_integration_id'] == "")
                unset($input['bank_integration_id']);
            elseif(array_key_exists('bank_integration_id', $input) && strlen($input['bank_integration_id']) > 1 && !is_numeric($input['bank_integration_id']))
                $input['bank_integration_id'] = $this->decodePrimaryKey($input['bank_integration_id']);

        $this->replace($input);
    }


}
