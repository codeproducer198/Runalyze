<?php

namespace Runalyze\Model\Account;

class UserRoleTest extends \PHPUnit\Framework\TestCase
{

	public function testCheckRoleName()
	{
        $this->assertEquals( 'ROLE_USER', UserRole::getRoleName(1));
        $this->assertEquals( 'ROLE_ADMIN', UserRole::getRoleName(2));
	}
}
