<?php

namespace App\Enum;

enum PostRideValidation: string
{
    case Pending = 'PENDING';
    case Ok = 'OK';
    case Incident = 'INCIDENT';
}