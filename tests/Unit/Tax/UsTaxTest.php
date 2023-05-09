<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace Tests\Unit\Tax;

use Tests\TestCase;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use Tests\MockAccountData;
use App\DataMapper\Tax\DE\Rule;
use App\DataMapper\Tax\TaxModel;
use App\DataMapper\CompanySettings;
use App\DataMapper\Tax\ZipTax\Response;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @test 
 */
class UsTaxTest extends TestCase
{
    use MockAccountData;
    use DatabaseTransactions;

    private array $mock_response = [
                "geoPostalCode" => "92582",
                "geoCity" => "SAN JACINTO",
                "geoCounty" => "RIVERSIDE",
                "geoState" => "CA",
                "taxSales" => 0.0875,
                "taxUse" => 0.0875,
                "txbService" => "N",
                "txbFreight" => "N",
                "stateSalesTax" => 0.06,
                "stateUseTax" => 0.06,
                "citySalesTax" => 0.01,
                "cityUseTax" => 0.01,
                "cityTaxCode" => "874",
                "countySalesTax" => 0.0025,
                "countyUseTax" => 0.0025,
                "countyTaxCode" => "",
                "districtSalesTax" => 0.015,
                "districtUseTax" => 0.015,
                "district1Code" => "26",
                "district1SalesTax" => 0,
                "district1UseTax" => 0,
                "district2Code" => "26",
                "district2SalesTax" => 0.005,
                "district2UseTax" => 0.005,
                "district3Code" => "",
                "district3SalesTax" => 0,
                "district3UseTax" => 0,
                "district4Code" => "33",
                "district4SalesTax" => 0.01,
                "district4UseTax" => 0.01,
                "district5Code" => "",
                "district5SalesTax" => 0,
                "district5UseTax" => 0,
                "originDestination" => "D",
        ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(
            ThrottleRequests::class
        );

        $this->withoutExceptionHandling();

        $this->makeTestData();
    }

    private function invoiceStub(?string $postal_code = '')
    {
                
        $settings = CompanySettings::defaults();
        $settings->country_id = '840'; // germany

        $tax_data = new TaxModel();
        $tax_data->seller_region = 'US';
        $tax_data->seller_subregion = 'CA';
        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all = true;

        $company = Company::factory()->create([
            'account_id' => $this->account->id,
            'settings' => $settings,
            'tax_data' => $tax_data,
            'calculate_taxes' => true,
        ]);

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'country_id' => 840,
            'shipping_country_id' => 840,
            'has_valid_vat_number' => false,
            'postal_code' => $postal_code,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'status_id' => 1,
            'user_id' => $this->user->id,
            'uses_inclusive_taxes' => false,
            'discount' => 0,
            'line_items' => [
                [
                    'product_key' => 'Test',
                    'notes' => 'Test',
                    'cost' => 100,
                    'quantity' => 1,
                    'tax_name1' => '',
                    'tax_rate1' => 0,
                    'tax_name2' => '',
                    'tax_rate2' => 0,
                    'tax_name3' => '',
                    'tax_rate3' => 0,
                    'type_id' => '1',
                ],
            ],
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_data' => new Response($this->mock_response),
        ]);

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        return $invoice;
    }

    // public function testCompanyTaxAllOffTaxExemptProduct()
    // {

    //     $invoice = $this->invoiceStub('92582');
    //     $client = $invoice->client;
    //     $client->is_tax_exempt = false;
    //     $client->save();

    //     $company = $invoice->company;
    //     $tax_data = $company->tax_data;

    //     $tax_data->regions->US->has_sales_above_threshold = true;
    //     $tax_data->regions->US->tax_all = false;

    //     $company->tax_data = $tax_data;
    //     $company->save();

    //     $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

    //     $this->assertEquals(0, $invoice->line_items[0]->tax_rate1);
    //     $this->assertEquals(100, $invoice->amount);

    // }

    public function testCompanyTaxAllOffButTaxUSRegion()
    {

        $invoice = $this->invoiceStub('92582');
        $client = $invoice->client;
        $client->is_tax_exempt = false;
        $client->save();

        $company = $invoice->company;
        $tax_data = $company->tax_data;

        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all = true;

        $company->tax_data = $tax_data;
        $company->save();

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(8.75, $invoice->line_items[0]->tax_rate1);
        $this->assertEquals(108.75, $invoice->amount);

    }

    public function testCompanyTaxAllOff()
    {

        $invoice = $this->invoiceStub('92582');
        $client = $invoice->client;
        $client->is_tax_exempt = false;
        $client->save();

        $company = $invoice->company;
        $tax_data = $company->tax_data;

        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all = false;

        $company->tax_data = $tax_data;
        $company->save();

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(0, $invoice->line_items[0]->tax_rate1);
        $this->assertEquals(100, $invoice->amount);

    }


    public function testThresholdLevelsAreMet()
    {

        $invoice = $this->invoiceStub('92582');
        $client = $invoice->client;
        $client->is_tax_exempt = true;
        $client->save();


        $company = $invoice->company;
        $tax_data = $company->tax_data;

        $tax_data->regions->US->has_sales_above_threshold = false;
        $tax_data->regions->US->tax_all = true;

        $company->tax_data = $tax_data;
        $company->save();

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(0, $invoice->line_items[0]->tax_rate1);
        $this->assertEquals(100, $invoice->amount);


    }

    public function testHasValidVatMakesNoDifferenceToTaxCalc()
    {
        
        $invoice = $this->invoiceStub('92582');
        $client = $invoice->client;
        $client->has_valid_vat_number = true;
        $client->save();

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(8.75, $invoice->line_items[0]->tax_rate1);
        $this->assertEquals(108.75, $invoice->amount);
    }


    public function testTaxExemption()
    {
        $invoice = $this->invoiceStub('92582');
        $client = $invoice->client;
        $client->is_tax_exempt = true;
        $client->save();

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(0, $invoice->line_items[0]->tax_rate1);
        $this->assertEquals(100, $invoice->amount);
    }

    public function testBasicTaxCalculation()
    {

        $invoice = $this->invoiceStub();
        

        $this->assertEquals(8.75, $invoice->line_items[0]->tax_rate1);
        $this->assertEquals(108.75, $invoice->amount);


    }

}
