<?php

namespace App\Http\Controllers;

use App\Models\PatientData;
use App\Models\Token;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArduinoController extends Controller
{
    public function getToken()
    {
        $response = Http::asForm()->post('https://api2.arduino.cc/iot/v1/clients/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'A2NvbngNmXEkYJAFUxWsvEIQUo5RwiaU',
            'client_secret' => '0ri1nD84JYMyx9zBS7hA7IpoFRWcYMDLwEtYS6V1ASJVQlH61B5hlFZpBbyPm2VA',
            'audience' => 'https://api2.arduino.cc/iot'
        ]);

        Token::query()->delete();
        Token::query()->create([
            'token' => $response['access_token']
        ]);
    }

    protected function thingPropertiesRequest()
    {
        $id = 'ad170d85-6f98-4191-8a2c-8534d9bb35f3';
        $token = Token::query()->orderByDesc('id')->first('token')->value('token');

        return Http::withToken($token)->get('https://api2.arduino.cc/iot/v2/things/' . $id . '/properties');
    }

    public function getThingProperties()
    {
        $response = $this->thingPropertiesRequest();

        if($response->status() == 401) {
            $this->getToken();
            $response = $this->thingPropertiesRequest();
        }

        if (!$response->successful()) {
            Log::error('Ajajajaj data nie su. Code: ' . $response->status());
            return;
        }

        $data = $response->json();
        foreach ($data as $item) {
            $oldItem = PatientData::query()->where('value_updated_at', $item['value_updated_at'])->where('variable_name', $item['variable_name'])->first();
            if ($oldItem) {
                continue;
            }
            PatientData::query()->create([
                'last_value' => $item['last_value'],
                'value_updated_at' => $item['value_updated_at'],
                'variable_name' => $item['variable_name'],
            ]);
        }
    }

    public function getAllData(Request $request)
    {
        $centrTemps = PatientData::query()->where('variable_name', 'centrTemp')->where('created_at', '>=', Carbon::now()->subHour())->get();
        $perifTemps = PatientData::query()->where('variable_name', 'perifTemp ')->where('created_at', '>=', Carbon::now()->subHour())->get();
        $weights = PatientData::query()->where('variable_name', 'vaha')->where('created_at', '>=', Carbon::now()->subHour())->get();

        return response()->json(['status' => 'success', 'data' => [
            'actualCentrTemp' => $centrTemps->isNotEmpty() ? $centrTemps->last() : null,
            'actualPerifTemp' => $perifTemps->isNotEmpty() ? $perifTemps->last() : null,
            'actualWeight' => $weights->isNotEmpty() ? $weights->last() : null,
            'centrTemps' => $centrTemps,
            'perifTemps' => $perifTemps,
            'weights' => $weights,
        ]]);
    }
}
