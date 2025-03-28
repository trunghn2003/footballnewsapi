<?php

namespace App\Repositories;

use App\Models\Person;

class PersonRepository
{
    protected $person;

    public function __construct(Person $person)
    {
        $this->person = $person;
    }

    public function syncPerson(array $data, $teamId): Person
    {
        $person = $this->person->updateOrCreate(
            ['id' => $data['id']],
            [
                'name' => $data['name'],
                'position' => $data['position'] ?? null,
                'nationality' => $data['nationality'] ?? null,
                'date_of_birth' => $data['dateOfBirth'] ?? null,
                'last_synced' => now(),
                'last_updated' => now()
            ]
        );
        $person->teams()->syncWithoutDetaching([$teamId]);
        return $person;
    }
    public function upDateOrCreateReferee(array $data): Person
    {
        if (isset($data['id']) && !empty($data['id'])) {
            try {
                return Person::updateOrCreate(
                    ['id' => $data['id']],
                    [
                    'name' => $data['name'],
                    'nationality' => $data['nationality'] ?? null,
                'last_synced' => now(),
                'last_updated' => now(),
                'role' => 'REFEREE'
            ]
        );
        } catch (\Exception $e) {
            return New Person();
        }
        }
    }
}