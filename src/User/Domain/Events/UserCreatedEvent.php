<?php

declare(strict_types=1);

namespace User\Domain\Events;

use Billing\Application\Listeners\CreateSignupPlanSubscription;
use Easy\EventDispatcher\Attributes\Listener;
use User\Infrastructure\Listeners\SendVerificationEmail;
use User\Infrastructure\Listeners\SendWelcomeEmail;

#[Listener(CreateSignupPlanSubscription::class)]
#[Listener(SendWelcomeEmail::class)]
#[Listener(SendVerificationEmail::class)]
class UserCreatedEvent extends AbstractUserEvent
{
}
