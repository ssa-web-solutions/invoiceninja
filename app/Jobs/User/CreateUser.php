<?php
/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2020. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Jobs\User;

use App\DataMapper\CompanySettings;
use App\DataMapper\DefaultSettings;
use App\Events\User\UserWasCreated;
use App\Models\CompanyUser;
use App\Models\User;
use App\Utils\Traits\MakesHash;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CreateUser
{
    use MakesHash;
    use Dispatchable;

    protected $request;

    protected $account;

    protected $company;

    protected $company_owner;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public function __construct(array $request, $account, $company, $company_owner = false)
    {
        $this->request = $request;
        $this->account = $account;
        $this->company = $company;
        $this->company_owner = $company_owner;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() : ?User
    {
        $user = new User();
        $user->account_id = $this->account->id;
        $user->password = bcrypt($this->request['password']);
        $user->accepted_terms_version = config('ninja.terms_version');
        $user->confirmation_code = $this->createDbHash(config('database.default'));
        $user->fill($this->request);
        $user->email = $this->request['email'];//todo need to remove this in production
        $user->last_login = now();
        $user->ip = request()->ip();
        $user->save();

        $user->companies()->attach($this->company->id, [
            'account_id' => $this->account->id,
            'is_owner' => $this->company_owner,
            'is_admin' => 1,
            'is_locked' => 0,
            'permissions' => '',
            'notifications' => CompanySettings::notificationDefaults(),
            //'settings' => DefaultSettings::userSettings(),
            'settings' => null,
        ]);
        
        event(new UserWasCreated($user, $this->company));

        return $user;
    }
}