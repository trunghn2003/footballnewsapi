<?php
namespace App\DTO;
class AreaDTO implements \JsonSerializable
{
    private $id;
    private ?String  $name;
    private ?String $code;
    private ?String $flag;

    /**
     * Summary of __construct
     * @param mixed $id
     * @param mixed $name
     * @param mixed $code
     * @param mixed $flag
     */
    public function __construct($id, $name, $code, $flag)
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->flag = $flag;
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
