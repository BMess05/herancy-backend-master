<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemSetting;

class CurrencyController extends Controller
{
    /**
     * Endppoint to be triggered by cron job
     */
    public function updateConversionRate() {
        $req_url = 'https://v6.exchangerate-api.com/v6/355aa8c9e8fc4b7be2c8d012/latest/USD';
        $response_json = file_get_contents($req_url);
        if(false !== $response_json) {
            try {
                $response = json_decode($response_json);
                if('success' === $response->result) {
                    $kenya_price = $response->conversion_rates->KES;
                    $india_price = $response->conversion_rates->INR;
                    $res = SystemSetting::updateOrCreate([
                        'key' => 'usd_to_kenya'
                    ],[
                        'value' => $kenya_price
                    ]);
                    $res = SystemSetting::updateOrCreate([
                        'key' => 'usd_to_india'
                    ],[
                        'value' => $india_price
                    ]);
                    if($res) {
                        return response()->json(['success' => true, 'message' => 'Exchange rates updated.']);
                    }
                }
                return response()->json(['success' => false, 'message' => 'Could not fetch data from API.']);
            }
            catch(Exception $e) {
                return response()->json(['success' => false, 'message' => 'Something went wrong, please try again.']);
            }

        }
    }

    public function getCurrencyConversion() {
        $rates = SystemSetting::select('key', 'value')->whereIn('key', ['usd_to_kenya', 'usd_to_india'])->get()->toArray();
        $result = [];
        foreach($rates as $rate) {
            $result[$rate['key']] = $rate['value'];
        }
        return response()->json(['success' => true, 'message' => 'Conversion rates found.', 'data' => $result]);
    }
}
