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

namespace App\Services\Tax\Providers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ZipTax implements TaxProviderInterface
{

    private string $endpoint = 'https://api.zip-tax.com/request/v40';

    private string $api_key = '';

    public function __construct(protected array $address)
    {
    }

    public function run()
    {

        $response = $this->callApi(['key' => $this->api_key, 'address' => $this->address]);

        if($response->successful())
            return $response->json();

        if($this->postal_code) {
           $response = $this->callApi(['key' => $this->api_key, 'address' => $this->postal_code]);

            if($response->successful())
                return $response->json();

        }

        $response->throw();

    }
    
    public function setApiCredentials($api_key): self
    {
        $this->api_key = $api_key;

        return $this;
    }

    /**
     * callApi
     *
     * @param  array $parameters
     * @return Response
     */
    private function callApi(array $parameters): Response
    {
        $response = Http::retry(3, 1000)->withHeaders([])->get($this->endpoint, $parameters);

        return $response;

    }
}
