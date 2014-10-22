<?php namespace Stevebauman\Maintenance\Validators;

use Stevebauman\Maintenance\Validators\AbstractValidator;

class AuthRegisterValidator extends AbstractValidator {
	
	protected $rules = array(
                'first_name' => 'required|max:100',
                'last_name' => 'required|max:100',
		'email' => 'required|email|max:250',
                'password' => 'required|confirmed|max:250',
                'password_confirmation' => 'required|max:250'
	);
	
}