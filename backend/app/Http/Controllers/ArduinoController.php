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
            $item = PatientData::query()->create([
                'last_value' => $item['last_value'],
                'value_updated_at' => $item['value_updated_at'],
                'variable_name' => $item['variable_name'],
            ]);
            if ($item) {
                $this->sendToIsCloud(['id' => $item->id, 'text' => $item->variable_name, 'value' => $item->last_value]);
            }
        }
    }

    public function getAllData(Request $request)
    {
        $centrTemps = PatientData::query()->where('variable_name', 'centrTemp')->where('created_at', '>=', Carbon::now()->subHour())->get();
        $perifTemps = PatientData::query()->where('variable_name', 'perifTemp')->where('created_at', '>=', Carbon::now()->subHour())->get();
        $weights = PatientData::query()->where('variable_name', 'vaha')->where('created_at', '>=', Carbon::now()->subDay())->get();
        $lastHourWeights = PatientData::query()->where('variable_name', 'vaha')->where('created_at', '>=', Carbon::now()->subHour())->get();

        return response()->json(['status' => 'success', 'data' => [
            'actualCentrTemp' => $centrTemps->isNotEmpty() ? $centrTemps->last() : null,
            'actualPerifTemp' => $perifTemps->isNotEmpty() ? $perifTemps->last() : null,
            'actualWeight' => $weights->isNotEmpty() ? $weights->last() : null,
            'weightOneHourBefore' => $lastHourWeights->isNotEmpty() ? $lastHourWeights->last() : null,
            'centrTemps' => $centrTemps,
            'perifTemps' => $perifTemps,
            'weights' => $weights,
        ]]);
    }

    public function sendToIsCloud($entry)
    {
        $mapText = [
          'vaha' => 'urine',
          'centrTemp' => 'central temp',
          'perifTemp' => 'periferal temp',
        ];

        $id = 'ID-' . $entry['id'];
        $text = $mapText[$entry['text']];
        $value = $entry['value'];
        $unit = $entry['text'] == 'vaha' ? 'ml' : 'celsius';

        $data = [
            'resourceType' => 'Observation',
            'id' => $id,
            'meta' => [
                'profile' => [
                    'http://hl7.org/fhir/StructureDefinition/vitalsigns'
                ]
            ],
            'status' => 'final',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'vital-signs',
                            'display' => 'Vital Signs'
                        ]
                    ],
                    'text' => 'Vital Signs'
                ]
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => '8302-2',
                        'display' => 'Urine'
                    ]
                ],
                'text' => $text
            ],
            'subject' => [
                'reference' => 'Patient/example'
            ],
            'effectiveDateTime' => Carbon::now()->format('Y-m-d'),
            'valueQuantity' => [
                'value' => $value,
                'unit' => $unit,
                'system' => 'http://unitsofmeasure.org',
                'code' => '[in_i]'
            ]
        ];

        $response = Http::withHeaders(['x-api-key' => 'bNFrmoQjpN7B9UzFxfWldh81WdG4qPl6l6DYuFnd'])
            ->post('https://fhir.n3o2k67smq89.static-test-account.isccloud.io/Observation', $data);

        if (!$response->successful()) {
            Log::alert('Cannot send data to cloud. Code: ' . $response->status());
        }
    }
}
