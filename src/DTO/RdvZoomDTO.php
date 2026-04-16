<?php

namespace App\DTO;

class RdvZoomDTO
{
    public \DateTimeInterface $date;
    public string $titre;
    public int $duree;

    public function __construct(\DateTimeInterface $date, string $titre, int $duree)
    {
        $this->date = $date;
        $this->titre = $titre;
        $this->duree = $duree;
    }
}
