<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Auth;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Route;
use Presentation\Response\Api\Auth\AuthResponse;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\NotFoundException;
use Presentation\Middlewares\CaptchaMiddleware;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Application\Commands\CreateUserCommand;
use User\Domain\Exceptions\EmailTakenException;
use User\Domain\Repositories\UserRepositoryInterface;
use Shared\Domain\ValueObjects\WalletAddress;

#[Middleware(CaptchaMiddleware::class)]
#[Route(path: '/signup', method: RequestMethod::POST)]
class SignupRequestHandler extends AuthApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher,
        private UserRepositoryInterface $repo,

        #[Inject('option.site.user_accounts_enabled')]
        private bool $userAccountsEnabled = true,

        #[Inject('option.site.user_signup_enabled')]
        private bool $userSignupEnabled = true,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $walletaddress = $request->getParsedBody()->wallet_address ?? null;
        if ($walletaddress) {
            $walletAddressObject = new WalletAddress($walletaddress);
            $_user = $this->repo->ofWalletAddress($walletAddressObject);
            if ($_user) {
                return new AuthResponse($_user);
            }
        }
        if (!$walletaddress) {
            $this->validateRequest($request);
        }

        $payload = (object) $request->getParsedBody();
        $email = $walletaddress ? $walletaddress : $payload->email;
        $cmd = new CreateUserCommand(
            $email,
            $payload->first_name ?? '',
            $payload->last_name ?? ''
        );
        if (!$walletaddress) {
            $flag = false;
            $cmd->setPassword($payload->password);
        } else {
            $cmd->setWalletAddress($walletaddress);
            if (!property_exists($payload, 'locale')) $flag = true;
            else $flag = false;
        }

        if (property_exists($payload, 'locale')) {
            $cmd->setLanguage($payload->locale);
        }

        if (property_exists($payload, 'ip')) {
            $cmd->setIp($payload->ip);
        }

        if (property_exists($payload, 'country_code')) {
            $cmd->setCountryCode($payload->country_code);
        }

        if (property_exists($payload, 'city_name')) {
            $cmd->setCityName($payload->city_name);
        }

        try {
            $user = $this->dispatcher->dispatch($cmd);
        } catch (EmailTakenException $th) {
            throw new HttpException(
                message: $th->getMessage(),
                param: 'email'
            );
        }

        return new AuthResponse($user, array('isNew' => $flag));
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        if (!$this->userAccountsEnabled || !$this->userSignupEnabled) {
            throw new NotFoundException();
        }
        $this->validator->validateRequest($req, [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email',
            'password' => 'required|string',
            'locale' => 'string|max:5',
            'ip' => 'ip',
            'country_code' => 'string|max:2',
            'city_name' => 'string|max:150'
        ]);
    }
}
