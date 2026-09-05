<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CopilotAskInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 1000)]
    public string $question;
}
