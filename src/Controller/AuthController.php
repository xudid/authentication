<?php

namespace App\CoreModule\AuthModule;

use Core\Interface\Controller;
use App\CoreModule\AuthModule\views\LoginForm;
use App\CoreModule\AuthModule\views\NewPasswordForm;
use App\CoreModule\AuthModule\views\ResetPasswordForm;
use App\CoreModule\RoleModule\Model\Role;
use App\CoreModule\UserModule\Model\User;
use App\Security\TokenProvider;
use Core\Session\Session;
use Core\Mail\Account;
use Core\Mail\Mailer;
use Core\Mail\Message;
use Core\Mail\Recepient;
use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use Ui\Handler\RequestHandler;

/**
 * Class AuthController
 * @package App\CoreModule\AuthModule
 */
class AuthController extends BaseController
{
	/**
	 * @var mixed|string|null
	 */
	private TokenProvider $tokenProvider;

	public function __construct()
	{
		parent::__construct();
		$this->tokenProvider = App::get(TokenProvider::class);
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function login()
	{
		try {
			return $this->render(new LoginForm($this));

		}catch (Exception $e) {
			$this->app->internalError($e->getMessage());
		}
	}

	/**
	 * @return bool
	 */
	public function logout()
	{
		if (Session::exists()) {
			Session::start();
		}
		Session::destroy();
		$this->redirectTo('/login');
	}

	/**
	 * @param App $app
	 */
	public function auth()
	{
		$manager = $this->getModelManager(User::class);

		$name = $_POST['user_name'];
		$password = $_POST['user_password'];

		$result = $manager->findBy(['name' => $name]);
		$user = $result[0];
		if ($user && $user->verifyPassword($password)) {
			if (!Session::exists()) {
				Session::start();
				\session_regenerate_id();
			}
			Session::set('user', $user);

			$url = $this->restoreAskedUrl();
			if ($url) {
				$this->resetAskedUrl();
				$this->app->redirectTo($url);

			} else {
				$this->app->redirectTo('/');
			}
		} else {
			$this->app->redirectTo('/login');
		}
	}

	/**
	 * [isloggedin description]
	 * @return bool [description]
	 */
	public function isloggedin()
	{
		if (!Session::exists()) {
			Session::start();
		}
		if (Session::exists() && Session::has('user')) {
			return Session::get('user');
		} else {
			return false;
		}
	}

	public function userHasRole(Role $role): bool
	{
		if (!Session::exists()) {
			Session::start();
		}
		if (Session::has('user')) {
			$user = Session::get('user');
			$modelManager = $this->app->getModelManager(User::class);
			$roles = $modelManager->findAssociationValuesBy(Role::class, $user);
			if ($roles) {
				$roles = array_column($roles, 'name');
				return in_array($role->getName(), $roles);
			}
		}
		return false;
	}

	/**
	 * [saveAskedUrl description]
	 * @param string $url [description]
	 */
	public function saveAskedUrl(string $url)
	{
		if (!Session::exists()) {
			Session::start();
		}
		try {
			Session::set('STORED_URL', $url);
		} catch (Exception $e) {
		}
	}

	/**
	 * [restoreAskedUrl description]
	 * @return string|null [description]
	 */
	public function restoreAskedUrl(): string
	{
		if (!Session::exists()) {
			Session::start();
		}
		if (Session::has('STORED_URL')) {
			return Session::get('STORED_URL');
		}
		return false;
	}

	public function resetAskedUrl()
	{
		if (!Session::exists()) {
			Session::start();
		}
		Session::delete('STORED_URL');
	}

	public function getResetToken()
	{
		$form = new ResetPasswordForm($this->formFactory(User::class));
		return $this->render($form);

	}

	public function sendResetMail()
	{
		$user = new User();
		$handler = new RequestHandler(ServerRequest::fromGlobals());
		$handler->handle($user);
		$queryBuilder = $this->modelManager(User::class)->builder();
		$request = $queryBuilder->select()->from('users')->where('email', '=', $user->getEmail());
		$result = $queryBuilder->execute($request);
		if ($result) {
			$user = User::hydrate($result[0]);
			//$mailAccount =  App->get('MailAccount'); ....
			//mailSender = App->get('MailSender')
			$account = new Account('192.168.226.56', null, null);
			$account->setPort('1025');
			$mailer = new Mailer($account);
			// use with dev with maildev
			$mailer->smtpSecure()->smtpAutoTLS();
			$token = $this->tokenProvider->getUserToken($user);
			$href =  'http://192.168.226.56/password/recovery/' . $token;
			$message = new Message(
				new Recepient('no-reply', 'no-reply@mowjo.fr'),
				new Recepient('clt1', 'clt1@mowjo.fr'),
				'recuperation mot de passe',
				"<h1>Recuperation de mot de passe</h1>
    <a href=$href>Veuillez cliquer içi pour réinitialiser votre mot de passe</a>"
			);
			$mailer->send($message);
			return $this->render("Un mail de réinitialisation a été envoyé à : " . $user->getEmail());

		} else {
			$this->redirect('/');
		}
	}

	public function resetPassword(string $token)
	{
		// Todo escape token (avoid sql injection)
		if (!$this->tokenProvider->isValid($token)) {
			return $this->render("Le lien que vous utilisez n'est pas valide");
		}
		return $this->render(new NewPasswordForm($token));
	}

	public function registerPassword()
	{
		$httpRequest = ServerRequest::fromGlobals();
		$datas = $httpRequest->getParsedBody();

		if (!array_key_exists('user_email', $datas)) {
			$this->redirect('/');
		}

		// get user by mail
		$user = $this->getUserWithHttpRequest();
		if (!$user) {
			$this->redirect('/');
		}
		if (!array_key_exists('token', $datas)) {
			$this->redirect('/');
		}
		$token = $datas['token'];
		if (!$this->tokenProvider->isValid($token)) {
			$this->redirect('/');
		}
		// verify that user owns token and is not used and not
		//expirated
		if (!array_key_exists('user_password', $datas)) {
			$this->redirect('/');
		}
		$password = $datas['user_password'];

		if (!array_key_exists('user_password_confirmation', $datas)) {
			$this->redirect('/');
		}
		$passwordConfirmation = $datas['user_password_confirmation'];

		if ($password !== $passwordConfirmation) {
			$this->redirect('/');
		}


	}
	private function getUserWithHttpRequest()
	{
		$user = new User();
		$handler = new RequestHandler(ServerRequest::fromGlobals());
		$handler->handle($user);

		if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
			$this->redirect('/');
		}
		$queryBuilder = $this->modelManager(User::class)->builder();
		$request = $queryBuilder->select()->from('users')->where('email', '=', $user->getEmail());
		$result = $queryBuilder->execute($request);
		if ($result) {
			return User::hydrate($result[0]);
		}
		return false;
	}
}
