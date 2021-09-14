<?php


namespace Tyr\Model;


use App\CoreModule\RoleModule\Model\Role;
use Entity\Database\DaoInterface;
use Entity\Model\ModelManager;

class UsersManager extends ModelManager
{
	/**
	 * StockManager constructor.
	 * @param DaoInterface $dao
	 */
	public function __construct(DaoInterface $dao)
	{
		parent::__construct($dao, User::class);
	}

	public function insert($user)
	{
		$id = parent::insert($user);
		foreach ($user->getRoles() as $role) {
			$this->addRole($user, $role);
		}
		return $id;
	}


}