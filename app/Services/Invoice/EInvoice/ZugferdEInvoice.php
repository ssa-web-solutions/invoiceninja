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

namespace App\Services\Invoice\EInvoice;

use App\Models\Invoice;
use App\Models\Product;
use App\Services\AbstractService;
use horstoeko\zugferd\ZugferdProfiles;
use Illuminate\Support\Facades\Storage;
use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdDocumentPdfBuilder;
use horstoeko\zugferd\codelists\ZugferdDutyTaxFeeCategories;

class ZugferdEInvoice extends AbstractService
{

    public function __construct(public Invoice $invoice, private bool $alterPDF, private string $custom_pdf_path = "", private array $tax_map = [])
    {
    }

    public function run()
    {

        $company = $this->invoice->company;
        $client = $this->invoice->client;
        $profile = $client->getSetting('e_invoice_type');

        $profile = match ($profile) {
            "XInvoice_2_2" => ZugferdProfiles::PROFILE_XRECHNUNG_2_2,
            "XInvoice_2_1" => ZugferdProfiles::PROFILE_XRECHNUNG_2_1,
            "XInvoice_2_0" => ZugferdProfiles::PROFILE_XRECHNUNG_2,
            "XInvoice_1_0" => ZugferdProfiles::PROFILE_XRECHNUNG,
            "XInvoice-Extended" => ZugferdProfiles::PROFILE_EXTENDED,
            "XInvoice-BasicWL" => ZugferdProfiles::PROFILE_BASICWL,
            "XInvoice-Basic" => ZugferdProfiles::PROFILE_BASIC,
            default => ZugferdProfiles::PROFILE_EN16931,
        };


        $xrechnung = ZugferdDocumentBuilder::CreateNew($profile);

        $xrechnung
            ->setDocumentSupplyChainEvent(date_create($this->invoice->date))
            ->setDocumentSeller($company->getSetting('name'))
            ->setDocumentSellerAddress($company->getSetting("address1"), $company->getSetting("address2"), "", $company->getSetting("postal_code"), $company->getSetting("city"), $company->country()->iso_3166_2, $company->getSetting("state"))
            ->setDocumentSellerContact($this->invoice->user->first_name." ".$this->invoice->user->last_name, "", $this->invoice->user->phone, "", $this->invoice->user->email)
            ->setDocumentBuyer($client->name, $client->number)
            ->setDocumentBuyerAddress($client->address1, "", "", $client->postal_code, $client->city, $client->country->iso_3166_2, $client->state)
            ->setDocumentBuyerContact($client->primary_contact()->first()->first_name . " " . $client->primary_contact()->first()->last_name, "", $client->primary_contact()->first()->phone, "", $client->primary_contact()->first()->email)
            ->addDocumentPaymentTerm(ctrans("texts.xinvoice_payable", ['payeddue' => date_create($this->invoice->date)->diff(date_create($this->invoice->due_date))->format("%d"), 'paydate' => $this->invoice->due_date]));

            if (!empty($this->invoice->public_notes)) {
            $xrechnung->addDocumentNote($this->invoice->public_notes);
        }
		if (empty($this->invoice->number)){
        	$xrechnung->setDocumentInformation("DRAFT", "380", date_create($this->invoice->date), $this->invoice->client->getCurrencyCode());
        } else {
          $xrechnung->setDocumentInformation($this->invoice->number, "380", date_create($this->invoice->date), $this->invoice->client->getCurrencyCode());
        }
        if (!empty($this->invoice->po_number)) {
            $xrechnung->setDocumentBuyerOrderReferencedDocument($this->invoice->po_number);
        }

        if (empty($client->routing_id)) {
            $xrechnung->setDocumentBuyerReference(ctrans("texts.xinvoice_no_buyers_reference"));
        } else {
            $xrechnung->setDocumentBuyerReference($client->routing_id);
        }
        if (!empty($client->shipping_address1)){
            $xrechnung->setDocumentShipToAddress($client->shipping_address1, $client->shipping_address2, "", $client->shipping_postal_code, $client->shipping_city, $client->shipping_country->iso_3166_2, $client->shipping_state);
        }

        $xrechnung->addDocumentPaymentMean(68, ctrans("texts.xinvoice_online_payment"));

        if (str_contains($company->getSetting('vat_number'), "/")) {
            $xrechnung->addDocumentSellerTaxRegistration("FC", $company->getSetting('vat_number'));
        } else {
            $xrechnung->addDocumentSellerTaxRegistration("VA", $company->getSetting('vat_number'));
        }

        $invoicing_data = $this->invoice->calc();

        //Create line items and calculate taxes
        foreach ($this->invoice->line_items as $index => $item) {
            $xrechnung->addNewPosition($index)
                ->setDocumentPositionGrossPrice($item->gross_line_total)
                ->setDocumentPositionNetPrice($item->line_total);
            if (!empty($item->product_key)){
                if (!empty($item->notes)){
                   $xrechnung->setDocumentPositionProductDetails($item->product_key, $item->notes);
                }
                $xrechnung->setDocumentPositionProductDetails($item->product_key);
            }
            else {
                if (!empty($item->notes)){
                    $xrechnung->setDocumentPositionProductDetails($item->notes);
                }
                else {
                    $xrechnung->setDocumentPositionProductDetails("no product name defined");
                }
            }
            if (isset($item->task_id)) {
                $xrechnung->setDocumentPositionQuantity($item->quantity, "HUR");
            } else {
                $xrechnung->setDocumentPositionQuantity($item->quantity, "H87");
            }
            $linenetamount = $item->line_total;
            if ($item->discount > 0) {
                if ($this->invoice->is_amount_discount) {
                    $linenetamount -= $item->discount;
                } else {
                    $linenetamount -= $linenetamount * ($item->discount / 100);
                }
            }
            $xrechnung->setDocumentPositionLineSummation($linenetamount);
            // According to european law, each line item can only have one tax rate
            if (!(empty($item->tax_name1) && empty($item->tax_name2) && empty($item->tax_name3))) {
                $taxtype = $this->getTaxType($item->tax_id);
                if (!empty($item->tax_name1)) {
                    $xrechnung->addDocumentPositionTax($taxtype, 'VAT', $item->tax_rate1);
                    $this->addtoTaxMap($taxtype, $linenetamount, $item->tax_rate1);
                } elseif (!empty($item->tax_name2)) {
                    $xrechnung->addDocumentPositionTax($taxtype, 'VAT', $item->tax_rate2);
                    $this->addtoTaxMap($taxtype, $linenetamount, $item->tax_rate2);
                } elseif (!empty($item->tax_name3)) {
                    $xrechnung->addDocumentPositionTax($taxtype, 'VAT', $item->tax_rate3);
                    $this->addtoTaxMap($taxtype, $linenetamount, $item->tax_rate3);
                } else {
                    nlog("Can't add correct tax position");
                }
            } else {
                if (!empty($this->invoice->tax_name1)) {
                    $taxtype = $this->getTaxType($this->invoice->tax_name1);
                    $xrechnung->addDocumentPositionTax($taxtype, 'VAT', $this->invoice->tax_rate1);
                    $this->addtoTaxMap($taxtype, $linenetamount, $this->invoice->tax_rate1);
                } elseif (!empty($this->invoice->tax_name2)) {
                    $taxtype = $this->getTaxType($this->invoice->tax_name2);
                    $xrechnung->addDocumentPositionTax($taxtype, 'VAT', $this->invoice->tax_rate2);
                    $this->addtoTaxMap($taxtype, $linenetamount, $this->invoice->tax_rate2);
                } elseif (!empty($this->invoice->tax_name3)) {
                    $taxtype = $this->getTaxType($this->invoice->tax_name3);
                    $xrechnung->addDocumentPositionTax($taxtype, 'VAT', $this->invoice->tax_rate3);
                    $this->addtoTaxMap($taxtype, $linenetamount, $this->invoice->tax_rate3);
                } else {
                    $taxtype = ZugferdDutyTaxFeeCategories::ZERO_RATED_GOODS;
                    $xrechnung->addDocumentPositionTax($taxtype, 'VAT', 0);
                    $this->addtoTaxMap($taxtype, $linenetamount, 0);
                    nlog("Can't add correct tax position");
                }
            }
        }


        if ($this->invoice->isPartial()) {
            $xrechnung->setDocumentSummation($this->invoice->amount, $this->invoice->balance, $invoicing_data->getSubTotal(), $invoicing_data->getTotalSurcharges(), $invoicing_data->getTotalDiscount(), $invoicing_data->getSubTotal(), $invoicing_data->getItemTotalTaxes(), null, $this->invoice->partial);
        } else {
            $xrechnung->setDocumentSummation($this->invoice->amount, $this->invoice->balance, $invoicing_data->getSubTotal(), $invoicing_data->getTotalSurcharges(), $invoicing_data->getTotalDiscount(), $invoicing_data->getSubTotal(), $invoicing_data->getItemTotalTaxes(), null, 0.0);
        }


        foreach ($this->tax_map as $item){
            $xrechnung->addDocumentTax($item["tax_type"], "VAT", $item["net_amount"], $item["tax_rate"]*$item["net_amount"], $item["tax_rate"]*100);
        }
        $disk = config('filesystems.default');

        if (!Storage::disk($disk)->exists($client->e_invoice_filepath($this->invoice->invitations->first()))) {
            Storage::makeDirectory($client->e_invoice_filepath($this->invoice->invitations->first()));
        }

        $xrechnung->writeFile(Storage::disk($disk)->path($client->e_invoice_filepath($this->invoice->invitations->first()) . $this->invoice->getFileName("xml")));
        // The validity can be checked using https://portal3.gefeg.com/invoice/validation or https://e-rechnung.bayern.de/app/#/upload

        if ($this->alterPDF) {
            if ($this->custom_pdf_path != "") {
                $pdfBuilder = new ZugferdDocumentPdfBuilder($xrechnung, $this->custom_pdf_path);
                $pdfBuilder->generateDocument();
                $pdfBuilder->saveDocument($this->custom_pdf_path);
            } else {
                $filepath_pdf = $client->invoice_filepath($this->invoice->invitations->first()) . $this->invoice->getFileName();
                $file = Storage::disk($disk)->exists($filepath_pdf);
                if ($file) {
                    $pdfBuilder = new ZugferdDocumentPdfBuilder($xrechnung, Storage::disk($disk)->path($filepath_pdf));
                    $pdfBuilder->generateDocument();
                    $pdfBuilder->saveDocument(Storage::disk($disk)->path($filepath_pdf));
                }
            }
        }

        return $client->e_invoice_filepath($this->invoice->invitations->first()) . $this->invoice->getFileName("xml");
    }

    private function getTaxType($name): string
    {
        $tax_type = null;
        switch ($name) {
            case Product::PRODUCT_TYPE_SERVICE:
            case Product::PRODUCT_TYPE_DIGITAL:
            case Product::PRODUCT_TYPE_PHYSICAL:
            case Product::PRODUCT_TYPE_SHIPPING:
            case Product::PRODUCT_TYPE_REDUCED_TAX:
                $tax_type = ZugferdDutyTaxFeeCategories::STANDARD_RATE;
                break;
            case Product::PRODUCT_TYPE_EXEMPT:
                $tax_type =  ZugferdDutyTaxFeeCategories::EXEMPT_FROM_TAX;
                break;
            case Product::PRODUCT_TYPE_ZERO_RATED:
                $tax_type = ZugferdDutyTaxFeeCategories::ZERO_RATED_GOODS;
                break;
            case Product::PRODUCT_TYPE_REVERSE_TAX:
                $tax_type = ZugferdDutyTaxFeeCategories::VAT_REVERSE_CHARGE;
                break;
        }
        $eu_states = ["AT", "BE", "BG", "HR", "CY", "CZ", "DK", "EE", "FI", "FR", "DE", "EL", "GR", "HU", "IE", "IT", "LV", "LT", "LU", "MT", "NL", "PL", "PT", "RO", "SK", "SI", "ES", "SE", "IS", "LI", "NO", "CH"];
        if (empty($tax_type)) {
            if ((in_array($this->invoice->company->country()->iso_3166_2, $eu_states) && in_array($this->invoice->client->country->iso_3166_2, $eu_states)) && $this->invoice->company->country()->iso_3166_2 != $this->invoice->client->country->iso_3166_2) {
                $tax_type = ZugferdDutyTaxFeeCategories::VAT_EXEMPT_FOR_EEA_INTRACOMMUNITY_SUPPLY_OF_GOODS_AND_SERVICES;
            } elseif (!in_array($this->invoice->client->country->iso_3166_2, $eu_states)) {
                $tax_type = ZugferdDutyTaxFeeCategories::SERVICE_OUTSIDE_SCOPE_OF_TAX;
            } elseif ($this->invoice->client->country->iso_3166_2 == "ES-CN") {
                $tax_type = ZugferdDutyTaxFeeCategories::CANARY_ISLANDS_GENERAL_INDIRECT_TAX;
            } elseif (in_array($this->invoice->client->country->iso_3166_2, ["ES-CE", "ES-ML"])) {
                $tax_type = ZugferdDutyTaxFeeCategories::TAX_FOR_PRODUCTION_SERVICES_AND_IMPORTATION_IN_CEUTA_AND_MELILLA;
            } else {
                nlog("Unkown tax case for xinvoice");
                $tax_type = ZugferdDutyTaxFeeCategories::STANDARD_RATE;
            }
        }
        return $tax_type;
    }
    private function addtoTaxMap(string $tax_type, float $net_amount, float $tax_rate){
        $hash = hash("md5", $tax_type."-".$tax_rate);
        if (array_key_exists($hash, $this->tax_map)){
            $this->tax_map[$hash]["net_amount"] += $net_amount;
        }
        else{
            $this->tax_map[$hash] = [
                "tax_type" => $tax_type,
                "net_amount" => $net_amount,
                "tax_rate" => $tax_rate / 100
            ];
        }
    }

}
