<?php namespace integration;

class MenuControllerTest extends \IntegrationCase {

	public function setUp()
	{
		parent::setUp();
		$this->setUpDb();
	}

	public function test_sign_up($email = 'test@test.test')
	{
		$user = ['username' => 'test' ,'email' => $email, 'password' => 'testtest'];
		$this->visit('login')
			->submitForm('Sign up', $user)
			->seeInDatabase('users', $this->removeField($user, 'password'))
			->see('Your user has been created, use the link in the mail to activate your user.')
			->seePageIs('/login');
	}

	public function test_sign_in(){
		$this->sign_in();
	}

	public function test_forgot_password(){
		$email = 'test@test.test';
		$this->test_sign_up($email);
		$this->visit('login')
			->submitForm('Send password', ['email' => $email])
			->see('A new password have been sent to you.')
			->seePageIs('/login');
	}

	public function test_Home_page(){
		$this->visit('/')->see('<h2>Welcome</h2>');

		$this->sign_in();
		$this->visit('/')
			->see('What is new?')
			->seePageIs('browse');

		$this->visit('logout');
	}

	public function test_Browse_page(){
		$this->visit('browse')->see('What is new?');
	}

	public function test_blog_page(){
		$this->visit('blog')->see('<h2>Blog</h2>');
	}

	public function test_Contact_page(){
		$this->visit('contact')
			->submitForm('Send', ['name' => 'test', 'email' => 'test@test.test', 'subject' => 'test', 'message' => 'test'])
			->see('Your contact message have been send.')
			->seePageIs('contact');
	}
}