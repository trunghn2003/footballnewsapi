<?php

namespace App\DTO;

class AreaDTO implements \JsonSerializable
{
    /**
     * @param int|string|null $id
     * @param string|null $name
     * @param string|null $code
     * @param string|null $flag
     */
    public function __construct(
        private $id,
        private ?string $name,
        private ?string $code,
        private ?string $flag
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'flag' => $this->flag,
        ];
    }
}
