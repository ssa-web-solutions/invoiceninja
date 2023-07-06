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

namespace App\Http\Requests\Design;

use App\Http\Requests\Request;
use App\Models\Account;

class StoreDesignRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() : bool
    {
        return auth()->user()->isAdmin() && auth()->user()->account->hasFeature(Account::FEATURE_API);
        ;
    }

    public function rules()
    {
        return [
            //'name' => 'required',
            'name' => 'required|unique:designs,name,null,null,company_id,'.auth()->user()->companyId(),
            'design' => 'required|array',
            'design.header' => 'required|min:1',
            'design.body' => 'required|min:1',
            'design.footer' => 'required|min:1',
            'design.includes' => 'required|min:1',
        ];
    }

    public function prepareForValidation()
    {
        $input = $this->all();
        $input['design'] = (isset($input['design']) && is_array($input['design'])) ? $input['design'] : [];

        if (! array_key_exists('product', $input['design']) || is_null($input['design']['product'])) {
            $input['design']['product'] = '';
        }

        if (! array_key_exists('task', $input['design']) || is_null($input['design']['task'])) {
            $input['design']['task'] = '';
        }

        if (! array_key_exists('includes', $input['design']) || is_null($input['design']['includes'])) {
            $input['design']['includes'] = '';
        }

        if (! array_key_exists('footer', $input['design']) || is_null($input['design']['footer'])) {
            $input['design']['footer'] = '';
        }

        if (! array_key_exists('header', $input['design']) || is_null($input['design']['header'])) {
            $input['design']['header'] = '';
        }

        if (! array_key_exists('body', $input['design']) || is_null($input['design']['body'])) {
            $input['design']['body'] = '';
        }

        $this->replace($input);
    }
}
