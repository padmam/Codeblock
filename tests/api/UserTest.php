<?php namespace api;

class UserTest extends \ApiCase {

	public function test_get() {
		$this->get('/api/v1/users')->seeStatusCode(200);
	}

	/*
	 * Needs token.
	 */
	public function test_update() {
		$this->post('/api/v1/users/1', ['_method' => 'put', 'email' => 'testaren@test.test'], $this->get_headers())->seeStatusCode(201);

		$this->setUser(2);
		$this->post('/api/v1/users/2', ['_method' => 'put', 'email' => 'testaren2@test.test'], $this->get_headers())->seeStatusCode(201);
	}

}