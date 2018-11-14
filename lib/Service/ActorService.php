<?php
declare(strict_types=1);


/**
 * Nextcloud - Social Support
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018, Maxence Lange <maxence@artificial-owl.com>
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Social\Service;


use daita\MySmallPhpTools\Traits\TArrayTools;
use Exception;
use OC\User\NoUserException;
use OCA\Social\Db\ActorsRequest;
use OCA\Social\Exceptions\AccountAlreadyExistsException;
use OCA\Social\Exceptions\ActorDoesNotExistException;
use OCA\Social\Model\ActivityPub\Person;
use OCA\Social\Model\InstancePath;


/**
 * Class ActorService
 *
 * @package OCA\Social\Service
 */
class ActorService {


	use TArrayTools;


	/** @var ConfigService */
	private $configService;

	/** @var ActorsRequest */
	private $actorsRequest;

	/** @var MiscService */
	private $miscService;


	/**
	 * ActorService constructor.
	 *
	 * @param ActorsRequest $actorsRequest
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		ActorsRequest $actorsRequest, ConfigService $configService, MiscService $miscService
	) {
		$this->configService = $configService;
		$this->actorsRequest = $actorsRequest;
		$this->miscService = $miscService;
	}


	/**
	 * @param string $username
	 *
	 * @return Person
	 * @throws ActorDoesNotExistException
	 */

	public function getActor(string $username): Person {
		$actor = $this->actorsRequest->getFromUsername($username);

		return $actor;
	}


	/**
	 * @param string $userId
	 *
	 * @return Person
	 * @throws ActorDoesNotExistException
	 * @throws NoUserException
	 */
	public function getActorFromUserId(string $userId): Person {
		$this->miscService->confirmUserId($userId);
		$actor = $this->actorsRequest->getFromUserId($userId);

		return $actor;
	}


	/**
	 * @param string $search
	 *
	 * @return Person[]
	 */
	public function searchLocalAccounts(string $search): array {
		return $this->actorsRequest->searchFromUsername($search);
	}




	/**
	 * Method should be called by the frontend and will generate a fresh Social account for
	 * the user, using the userId and the username.
	 *
	 * Pair of keys are created at this point.
	 *
	 * Return exceptions if an account already exist for this user or if the username is already
	 * taken
	 *
	 * @param string $userId
	 * @param string $username
	 *
	 * @throws AccountAlreadyExistsException
	 * @throws NoUserException
	 * @throws Exception
	 */
	public function createActor(string $userId, string $username) {

		$this->miscService->confirmUserId($userId);
		$this->checkActorUsername($username);

		try {
			$this->actorsRequest->getFromUsername($username);
			throw new AccountAlreadyExistsException('actor with that name already exist');
		} catch (ActorDoesNotExistException $e) {
			/* we do nohtin */
		}

		try {
			$this->actorsRequest->getFromUserId($userId);
			throw new AccountAlreadyExistsException('account for this user already exist');
		} catch (ActorDoesNotExistException $e) {
			/* we do nohtin */
		}

		$this->configService->setCoreValue('public_webfinger', 'social/lib/webfinger.php');

		$actor = new Person();
		$actor->setUserId($userId);
		$actor->setPreferredUsername($username);

		$this->generateKeys($actor);
		$this->actorsRequest->create($actor);
	}


	/**
	 * @param $username
	 */
	private function checkActorUsername($username) {
		$accepted = 'qwertyuiopasdfghjklzxcvbnm';

		return;
	}


	/**
	 * @param Person $actor
	 */
	private function generateKeys(Person &$actor) {
		$res = openssl_pkey_new(
			[
				"digest_alg"       => "rsa",
				"private_key_bits" => 2048,
				"private_key_type" => OPENSSL_KEYTYPE_RSA,
			]
		);

		openssl_pkey_export($res, $privateKey);
		$publicKey = openssl_pkey_get_details($res)['key'];

		$actor->setPublicKey($publicKey);
		$actor->setPrivateKey($privateKey);
	}


}
