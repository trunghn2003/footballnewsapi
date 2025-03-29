<?php

namespace App\DTO;

class PersonDTO implements \JsonSerializable
{
    private int $id;
    private string $name;
    private ?string $firstName;
    private ?string $lastName;
    private ?string $displayName;
    private ?string $dateOfBirth;
    private ?string $gender;
    private ?string $shortName;
    private ?string $tla;
    private ?string $pos;
    private ?string $weight;
    private ?string $height;
    private ?string $nationality;
    private ?string $placeOfBirth;
    private ?string $countryOfBirth;
    private ?string $image;
    private ?string $role;

    public function __construct(
        int $id,
        string $name,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $displayName = null,
        ?string $dateOfBirth = null,
        ?string $gender = null,
        ?string $shortName = null,
        ?string $tla = null,
        ?string $pos = null,
        ?string $weight = null,
        ?string $height = null,
        ?string $nationality = null,
        ?string $placeOfBirth = null,
        ?string $countryOfBirth = null,
        ?string $image = null,
        ?string $role = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->displayName = $displayName;
        $this->dateOfBirth = $dateOfBirth;
        $this->gender = $gender;
        $this->shortName = $shortName;
        $this->tla = $tla;
        $this->pos = $pos;
        $this->weight = $weight;
        $this->height = $height;
        $this->nationality = $nationality;
        $this->placeOfBirth = $placeOfBirth;
        $this->countryOfBirth = $countryOfBirth;
        $this->image = $image;
        $this->role = $role;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'display_name' => $this->displayName,
            'date_of_birth' => $this->dateOfBirth,
            'gender' => $this->gender,
            'short_name' => $this->shortName,
            'tla' => $this->tla,
            'pos' => $this->pos,
            'weight' => $this->weight,
            'height' => $this->height,
            'nationality' => $this->nationality,
            'place_of_birth' => $this->placeOfBirth,
            'country_of_birth' => $this->countryOfBirth,
            'image' => $this->image,
            'role' => $this->role
        ];
    }
}
