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

namespace App\Filters;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * ExpenseFilters.
 */
class ExpenseFilters extends QueryFilters
{
    /**
     * Filter based on search text.
     *
     * @param string query filter
     * @return Builder
     * @deprecated
     */
    public function filter(string $filter = '') : Builder
    {
        if (strlen($filter) == 0) {
            return $this->builder;
        }

        return  $this->builder->where(function ($query) use ($filter) {
            $query->where('expenses.public_notes', 'like', '%'.$filter.'%')
                          ->orWhere('expenses.custom_value1', 'like', '%'.$filter.'%')
                          ->orWhere('expenses.custom_value2', 'like', '%'.$filter.'%')
                          ->orWhere('expenses.custom_value3', 'like', '%'.$filter.'%')
                          ->orWhere('expenses.custom_value4', 'like', '%'.$filter.'%');
        });
    }

    /**
     * Filter based on client status.
     *
     * Statuses we need to handle
     * - all
     * - logged
     * - pending
     * - invoiced
     * - paid
     * - unpaid
     *
     * @return Builder
     */
    public function client_status(string $value = '') :Builder
    {
        if (strlen($value) == 0) {
            return $this->builder;
        }

        $status_parameters = explode(',', $value);

        if (in_array('all', $status_parameters)) {
            return $this->builder;
        }

        $this->builder->whereNested(function ($query) use($status_parameters){

            if (in_array('logged', $status_parameters)) {

                $query->orWhere(function ($query){
                    $query->where('amount', '>', 0)
                          ->whereNull('invoice_id')
                          ->whereNull('payment_date');
                });
                
            }

            if (in_array('pending', $status_parameters)) {

                $query->orWhere(function ($query){
                    $query->where('should_be_invoiced',true)
                          ->whereNull('invoice_id');
                });
                
            }

            if (in_array('invoiced', $status_parameters)) {

                $query->orWhere(function ($query){
                    $query->whereNotNull('invoice_id');
                });
                
            }

            if (in_array('paid', $status_parameters)) {

                $query->orWhere(function ($query){
                    $query->whereNotNull('payment_date');
                });
                
            }

            if (in_array('unpaid', $status_parameters)) {

                $query->orWhere(function ($query){
                    $query->whereNull('payment_date');
                });
                
            }

        });

        // nlog($this->builder->toSql());

        return $this->builder;
    }

    /**
     * Returns a list of expenses that can be matched to bank transactions
     */
    public function match_transactions($value = '')
    {

        if($value == 'true')
        {
            return $this->builder->where('is_deleted',0)->whereNull('transaction_id');
        }

        return $this->builder;
    }


    /**
     * Filters the list based on the status
     * archived, active, deleted.
     *
     * @param string filter
     * @return Builder
     */
    public function status(string $filter = '') : Builder
    {
        if (strlen($filter) == 0) {
            return $this->builder;
        }

        $table = 'expenses';
        $filters = explode(',', $filter);

        return $this->builder->where(function ($query) use ($filters, $table) {
            $query->whereNull($table.'.id');

            if (in_array(parent::STATUS_ACTIVE, $filters)) {
                $query->orWhereNull($table.'.deleted_at');
            }

            if (in_array(parent::STATUS_ARCHIVED, $filters)) {
                $query->orWhere(function ($query) use ($table) {
                    $query->whereNotNull($table.'.deleted_at');

                    if (! in_array($table, ['users'])) {
                        $query->where($table.'.is_deleted', '=', 0);
                    }
                });
            }

            if (in_array(parent::STATUS_DELETED, $filters)) {
                $query->orWhere($table.'.is_deleted', '=', 1);
            }
        });
    }

    /**
     * Sorts the list based on $sort.
     *
     * @param string sort formatted as column|asc
     * @return Builder
     */
    public function sort(string $sort) : Builder
    {
        $sort_col = explode('|', $sort);

        if (is_array($sort_col) && in_array($sort_col[1], ['asc', 'desc']) && in_array($sort_col[0], ['public_notes', 'date', 'id_number', 'custom_value1', 'custom_value2', 'custom_value3', 'custom_value4'])) {
            return $this->builder->orderBy($sort_col[0], $sort_col[1]);
        }

        return $this->builder;
    }

    /**
     * Returns the base query.
     *
     * @param int company_id
     * @param User $user
     * @return Builder
     * @deprecated
     */
    public function baseQuery(int $company_id, User $user) : Builder
    {
        $query = DB::table('expenses')
            ->join('companies', 'companies.id', '=', 'expenses.company_id')
            ->where('expenses.company_id', '=', $company_id)
            ->select(
                DB::raw('COALESCE(expenses.country_id, companies.country_id) country_id'),
                DB::raw("CONCAT(COALESCE(expense_contacts.first_name, ''), ' ', COALESCE(expense_contacts.last_name, '')) contact"),
                'expenses.id',
                'expenses.private_notes',
                'expenses.custom_value1',
                'expenses.custom_value2',
                'expenses.custom_value3',
                'expenses.custom_value4',
                'expenses.created_at',
                'expenses.created_at as expense_created_at',
                'expenses.deleted_at',
                'expenses.is_deleted',
                'expenses.user_id',
            );

        /*
         * If the user does not have permissions to view all invoices
         * limit the user to only the invoices they have created
         */
        if (Gate::denies('view-list', Expense::class)) {
            $query->where('expenses.user_id', '=', $user->id);
        }

        return $query;
    }

    /**
     * Filters the query by the users company ID.
     *
     * @return Illuminate\Database\Query\Builder
     */
    public function entityFilter()
    {
        return $this->builder->company();
    }
}
