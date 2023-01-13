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

namespace App\Providers;

use App\Helpers\Mail\GmailTransport;
use App\Helpers\Mail\Office365MailTransport;
use App\Http\Middleware\SetDomainNameDb;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Utils\Ninja;
use App\Utils\TruthSource;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Mail\Mailer;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        Relation::morphMap([
            'invoices'  => Invoice::class,
            'proposals' => Proposal::class,
        ]);

        Blade::if('env', function ($environment) {
            return config('ninja.environment') === $environment;
        });

        Schema::defaultStringLength(191);

        /* Handles setting the correct database with livewire classes */
        if (Ninja::isHosted()) {
            Livewire::addPersistentMiddleware([
                SetDomainNameDb::class,
            ]);
        }

        /* Ensure we don't have stale state in jobs */
        Queue::before(function (JobProcessing $event) {
            App::forgetInstance('truthsource');
        });

        app()->instance(TruthSource::class, new TruthSource());

        // Model::preventLazyLoading(
        //     !$this->app->isProduction()
        // );

        Mail::extend('gmail', function () {
            return new GmailTransport();
        });

        Mail::extend('office365', function () {
            return new Office365MailTransport();
        });

        Mailer::macro('postmark_config', function (string $postmark_key) {
     
            Mailer::setSymfonyTransport(app('mail.manager')->createSymfonyTransport([
                'transport' => 'postmark',
                'token' => $postmark_key
            ]));
     
            return $this;

        });
        
        Mailer::macro('mailgun_config', function ($secret, $domain) {

            Mailer::setSymfonyTransport(app('mail.manager')->createSymfonyTransport([
                'transport' => 'mailgun',
                'secret' => $secret,
                'domain' => $domain,
                'endpoint' => config('services.mailgun.endpoint'),
                'scheme' => config('services.mailgun.scheme'),
            ]));
 
            return $this;
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

}
