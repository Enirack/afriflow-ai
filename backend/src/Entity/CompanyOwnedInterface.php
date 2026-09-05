<?php

namespace App\Entity;

interface CompanyOwnedInterface
{
    public function getCompany(): ?Company;
}
