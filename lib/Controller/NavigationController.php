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

namespace OCA\Social\Controller;


use daita\MySmallPhpTools\Traits\TArrayTools;
use daita\MySmallPhpTools\Traits\TNCDataResponse;
use OC\User\NoUserException;
use OCA\Social\AppInfo\Application;
use OCA\Social\Exceptions\AccountAlreadyExistsException;
use OCA\Social\Exceptions\SocialAppConfigException;
use OCA\Social\Service\ActorService;
use OCA\Social\Service\ConfigService;
use OCA\Social\Service\MiscService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;

class NavigationController extends Controller {


	use TArrayTools;
	use TNCDataResponse;


	/** @var string */
	private $userId;

	/** @var IConfig */
	private $config;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ActorService */
	private $actorService;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;

	/** @var IL10N */
	private $l10n;

	/**
	 * NavigationController constructor.
	 *
	 * @param IRequest $request
	 * @param string $userId
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 * @param ActorService $actorService
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 * @param IL10N $l10n
	 */
	public function __construct(
		IRequest $request, $userId, IConfig $config, IURLGenerator $urlGenerator,
		ActorService $actorService, ConfigService $configService, MiscService $miscService,
		IL10N $l10n
	) {
		parent::__construct(Application::APP_NAME, $request);

		$this->userId = $userId;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;

		$this->actorService = $actorService;
		$this->configService = $configService;
		$this->miscService = $miscService;
		$this->l10n = $l10n;
	}


	/**
	 * Display the navigation page of the Social app.
	 *
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @return TemplateResponse
	 * @throws NoUserException
	 */
	public function navigate($path = ''): TemplateResponse {
		$data = [
			'serverData' => [
				'public'   => false,
				'firstrun' => false,
				'setup'    => false
			]
		];

		try {
			$this->configService->getCloudAddress();
			$data['serverData']['setup'] = true;
		} catch (SocialAppConfigException $e) {
		}

		try {
			$this->actorService->createActor($this->userId, $this->userId);
			$data['serverData']['firstrun'] = true;
		} catch (AccountAlreadyExistsException $e) {
			// we do nothing
		}

		return new TemplateResponse(Application::APP_NAME, 'main', $data);
	}


	/**
	 * Display the navigation page of the Social app.
	 *
	 * @NoCSRFRequired
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @return DataResponse
	 */
	public function test(): DataResponse {

		$setup = false;
		try {
			$address = $this->configService->getCloudAddress(true);
			$setup = true;
		} catch (SocialAppConfigException $e) {
		}

		return $this->success(
			[
				'version' => $this->configService->getAppValue('installed_version'),
				'setup'   => $setup
			]
		);
	}


	/**
	 * Display the navigation page of the Social app.
	 *
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @return TemplateResponse
	 * @throws NoUserException
	 */
	public function timeline($path = ''): TemplateResponse {
		return $this->navigate();
	}

	/**
	 * Display the navigation page of the Social app.
	 *
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @return TemplateResponse
	 * @throws NoUserException
	 */
	public function account($path = ''): TemplateResponse {
		return $this->navigate();
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @param $username
	 *
	 * @return RedirectResponse|PublicTemplateResponse
	 * @throws NoUserException
	 */
	public function public($username) {
		if (\OC::$server->getUserSession()
						->isLoggedIn()) {
			return $this->navigate();
		}

		$data = [
			'serverData' => [
				'public' => true,
			]
		];
		$page = new PublicTemplateResponse(Application::APP_NAME, 'main', $data);
		$page->setHeaderTitle($this->l10n->t('Social') . ' ' . $username);

		return $page;
	}

}
