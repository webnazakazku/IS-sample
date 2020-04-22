<?php

namespace AppTests\Presenters;

use Mangoweb\Tester\PresenterTester\PresenterTester;
use Nette;
use Nette\Application\IResponse;
use Nette\Security\User;
use Nette\Security\Identity;

$testContainerFactory = require __DIR__ . '/bootstrap.php';

trait TPresenter
{

	/** @var PresenterTester */
	protected $presenterTester;

	public function __construct(PresenterTester $presenterTester)
	{
		$this->presenterTester = $presenterTester;
	}

	public function getLoggedInIdentity(User $user, $id = null, $roles = null, $data = null)
	{
		$identity = new Identity($id, $roles, $data);
		$user->login($identity);

		return $identity;
	}

	public function checkAction(Nette\Security\IIdentity $identity, string $presenterName, array $parameters = [])
	{
		$testRequest = $this->presenterTester->createRequest($presenterName)
			->withParameters($parameters)
			->withIdentity($identity);

		$testResponse = $this->presenterTester->execute($testRequest);
		$testResponse->assertRenders();
	}

	public function checkResponse(Nette\Security\IIdentity $identity, string $presenterName, array $parameters, string $responseClass)
	{
		$testRequest = $this->presenterTester->createRequest($presenterName)
			->withParameters($parameters)
			->withIdentity($identity);

		$testResponse = $this->presenterTester->execute($testRequest);
		$testResponse->assertHasResponse($responseClass);
	}
}
