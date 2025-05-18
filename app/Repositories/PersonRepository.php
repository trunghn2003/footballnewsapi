<?php

namespace App\Repositories;

use App\Models\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                return new Person();
            }
        }
    }

    public function getPersonByTeamId($teamId)
    {
        return $this->person->where('team_id', $teamId)
            ->where('role', 'PLAYER')
            ->get();
    }

    public function findById($id)
    {
        return $this->person->find($id);
    }

    public function update(int $id, array $data): bool
    {
        try {
            return $this->person->where('id', $id)->update($data);
        } catch (\Exception $e) {
            Log::error('Error updating person: ' . $e->getMessage());
            return false;
        }
    }

    public function findByName($name)
    {
        return $this->person->where('name', 'like', '%' . $name . '%')->first();
    }

    public function create(array $data)
    {
        return $this->person->create($data);
    }

    public function createOrUpdatePersonTeam(array $data)
    {
        return DB::table('person_team')->updateOrInsert(
            [
                'person_id' => $data['person_id'],
                'team_id' => $data['team_id']
            ],
            [
                'position' => $data['position'] ?? null,
                'shirt_number' => $data['shirt_number'] ?? null,
                'role' => $data['role'] ?? 'PLAYER',
                'updated_at' => now()
            ]
        );
    }

    public function genAutoId()
    {
        return $this->person->max('id') + 1;
    }
}
