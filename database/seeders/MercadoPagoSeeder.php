<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Gateway;
use Illuminate\Support\Str;

class MercadoPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $gateway = new Gateway();
        $gateway->name = 'Mercado Pago';
        $gateway->key = Str::lower(Str::random(32));
        $gateway->provider = 'MercadoPago';
        $gateway->is_offsite = false;
        $gateway->fields = json_encode(['publicKey' => '', 'accessToken' => '']);
        $gateway->visible = true;
        $gateway->site_url = 'https://www.mercadopago.com.br/developers/pt/guides/online-payments/checkout-pro/introduction';
        $gateway->default_gateway_type_id = 1;
        $gateway->save();
    }
}
