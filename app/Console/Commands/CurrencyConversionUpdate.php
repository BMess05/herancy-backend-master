<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Api\CurrencyController;
use App\Models\SystemSetting;

class CurrencyConversionUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency_conversion:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates currency conversion rates in database twice a day';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
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
                        return 1; // response()->json(['success' => true, 'message' => 'Exchange rates updated.']);
                    }
                }
                return 0; // response()->json(['success' => false, 'message' => 'Could not fetch data from API.']);
            }
            catch(Exception $e) {
                return 0; // response()->json(['success' => false, 'message' => 'Something went wrong, please try again.']);
            }

        }
        // return 0;
    }
}
