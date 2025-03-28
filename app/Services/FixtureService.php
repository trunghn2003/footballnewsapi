<?php

namespace App\Services;

use App\Repositories\FixtureRepository;
use App\Repositories\PersonRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FixtureService
{
    private FixtureRepository $fixtureRepository;
    private string $apiToken;       
    private string $apiUrlFootball;
    private PersonRepository $personRepository;
    public function __construct(FixtureRepository $fixtureRepository, PersonRepository $personRepository)
    {
        $this->fixtureRepository = $fixtureRepository;
        $this->apiToken = env('API_FOOTBALL_TOKEN');
        $this->apiUrlFootball = env('API_FOOTBALL_URL');
        $this->personRepository = $personRepository;
    }

    public function syncFixtures()
    {
        // try {
            $names = [
                'PL',
                'CL',
                'FL1',
                // 'WC',
                'BL1',
                'SA',
                'PD',
             ];
            foreach ($names as $name) {
                $response = Http::withHeaders([
                    'X-Auth-Token' => $this->apiToken
                ])->get("{$this->apiUrlFootball}/competitions/{$name}/matches");
            
                if (!$response->successful()) {
                    throw new \Exception("API request failed: {$response->status()}");
                }

                $datas = $response->json()['matches'];
                // dd($datas);
               
                DB::beginTransaction();

                if (isset($datas) && is_array($datas) ) {
                    foreach ($datas as $data) {
                        $seasonId = $data['season']['id'];
                        // dd($data, $seasonId);
                        if(isset($data['homeTeam']) && isset($data['awayTeam']) ){
                        $this->fixtureRepository->createOrUpdate($data);
                        }
                    }
                }
            
                DB::commit();
            }

            return [
                'success' => true   
            ];
        // } 
        // catch (\Exception $e) {
            
        //     Log::error("Competition sync failed: {$e->getMessage()}");
        //     DB::rollBack();
        //     return [
        //         'success' => false,
        //         'error' => $e->getMessage()
        //     ];
        // }
    }
}
