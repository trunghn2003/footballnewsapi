<?php

namespace App\Mapper;

use App\DTO\PersonDTO;
use App\Models\Person;

class PersonMapper
{
    public static function toDTO(Person $person): PersonDTO
    {
        return new PersonDTO(
            $person->id,
            $person->name,
            $person->first_name,
            $person->last_name,
            $person->display_name,
            $person->date_of_birth,
            $person->gender,
            $person->short_name,
            $person->tla,
            $person->pos,
            $person->weight,
            $person->height,
            $person->nationality,
            $person->place_of_birth,
            $person->country_of_birth,
            $person->image,
            $person->role
        );
    }
}
