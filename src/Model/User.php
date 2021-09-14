<?php

namespace Tyr\Model;

use Entity\Database\Attributes\Column;
use Entity\Database\Attributes\ManyToMany;
use Entity\Database\Attributes\Table;
use Entity\Model\Model;


/**
 * @relation::with:App\CoreModule\RoleModule\Role::type:OneToMany
 * @relation::with:App\CoreModule\RoleModule\Role::type:OneToMany
 * @Association(with=ClassName, type=AssociationType)
 * @description Represents base user in App
 */

/**
 * @Table(name="users")
 **/
#[Table('users')]
class User extends Model
{

	#[Column('name', 'string')]
	protected string $name = "";

	#[Column('email', 'string')]
	protected string $email = "";

	#[Column('password', 'string')]
	protected string $password = "";

	#[ManyToMany('App\CoreModule\RoleModule\Model\Role')]
	protected array $roles = [];

	public function __construct(array $datas = [])
	{
		//parent::__construct($datas);
	}

	public function setName(string $name)
	{
		if ($name != null) {
			$this->name = $name;
		}
	}

	public function getName()
	{
		return $this->name;
	}

	public function setEmail(string $email)
	{
		if ($email != null) {
			$this->email = $email;
		}
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function setPassword(string $pass)
	{
		$this->password = $pass;
	}

	public function initPassword(string $pass): bool
	{
		$pass = password_hash($pass, PASSWORD_DEFAULT);
		if (is_string($pass)) {
			$this->password = $pass;
			return true;
		}
		return false;
	}

	public function getPassword()
	{
		return $this->password;
	}

	public function verifyPassword(string $pass)
	{
		return password_verify($pass, $this->password) ?: false;
	}

	public function setRoles($roles)
	{
		$this->roles = $roles;
	}

	public function getRoles()
	{
		return $this->roles;
	}

	public function getId(): int
	{
		return $this->id;
	}
}
